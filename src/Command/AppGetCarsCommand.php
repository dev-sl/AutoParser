<?php declare(strict_types=1);

namespace App\Command;

use App\Entity\Car;
use App\Service\AutoParseTelegram;
use App\SiteTypes;
use Doctrine\ORM\EntityManager;
use Sunra\PhpSimple\HtmlDomParser;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class AppGetCarsCommand
 * @package App\Command
 */
class AppGetCarsCommand extends Command
{
    /**
     * @var string
     */
    protected static $defaultName = 'app:get-cars';

    /**
     * @var EntityManager
     */
    private $em;

    /**
     * @var AutoParseTelegram
     */
    private $telegram;

    /**
     * @var array
     */
    private const SITES = [
        SiteTypes::RST      => 'http://rst.ua',
        SiteTypes::AUTO_RIA => 'https://auto.ria.com'
    ];

    /**
     * @var string SEPARATOR
     */
    private const SEPARATOR = '?';

    /**
     * AppGetCarsCommand constructor.
     * @param EntityManager $em
     * @param AutoParseTelegram $telegram
     * @param null $name
     */
    public function __construct(EntityManager $em, AutoParseTelegram $telegram, $name = null)
    {
        $this->em = $em;
        $this->telegram = $telegram;

        parent::__construct($name);
    }

    /**
     * {@inheritdoc}
     * TODO: This method will refactoring
     */
    protected function execute(InputInterface $input, OutputInterface $output): void
    {

        $insideCars = $this->em->getRepository(Car::class)->findAll();

        $inside_car_ids = [];
        foreach ($insideCars as $car) {
            $inside_car_ids[$car->getSiteId()][] = $car->getCarId();
        }

        foreach (self::SITES as $type => $site) {

            switch ($type) {
                case SiteTypes::RST:
                    $new_car_ids = $this->parseRst($inside_car_ids);
                    break;
                case SiteTypes::AUTO_RIA:
                    $new_car_ids = $this->parseAutoRia($inside_car_ids);
                    break;
                default:
                    throw new \Exception('Undefined type');
            }

            if (!$new_car_ids) {
                $output->writeln("At $site new cars not found");

                continue;
            }

            foreach ($new_car_ids as $site_type => $car_ids) {
                foreach ($car_ids['new_car_ids'] as $car_id) {
                    $car = new Car();
                    $car->setCarId($car_id)
                        ->setSiteId($site_type);

                    $this->em->persist($car);

                    $this->telegram->sendMessage(
                        sprintf('Found new car: %s', self::SITES[$site_type] . $car_ids['external_car_link'][$car_id], '/')
                    );
                }

                $this->em->flush();

                $output->writeln(
                    sprintf('New cars: %s',
                        implode(',', $car_ids['new_car_ids'])
                    )
                );
            }
        }
    }

    public function parseRst(array $inside_car_ids)
    {
        $url = $this->buildUrl(self::SITES[SiteTypes::RST], '/oldcars/renault/megane/', [
            'year' => [2012, 2016],
            'price' => [8000, 15000],
            'photos' => true,
            'engine' => [1500, 1600],
            'gear' => implode(',', [2,3,4,5]),
            'fuel' => 2,
            'drive' => false,
            'condition' => false,
            'results' => 4,
            'saled' => true,
            'body' => [5],
            'from' => 'sfrom',
        ]);

        $dom = HtmlDomParser::str_get_html(file_get_contents($url));

        $externalCarLinks = $dom->find('.rst-page-wrap .rst-ocb-i a');

        $external_car_ids = [];
        $external_car_link = [];
        foreach ($externalCarLinks as $carLink) {
            $carId = (int) preg_replace('/\D/', '', $carLink->href);
            $external_car_ids[] = $carId;
            $external_car_link[$carId] = $carLink->href;
        }

        $new_car_ids = array_diff($external_car_ids, $inside_car_ids[SiteTypes::RST]);

        if (!$new_car_ids) {
            return false;
        }

        return [
            SiteTypes::RST => [
                'new_car_ids' => $new_car_ids,
                'external_car_link' => $external_car_link
            ]
        ];
    }

    public function parseAutoRia(array $inside_car_ids)
    {
        $url = $this->buildUrl(self::SITES[SiteTypes::AUTO_RIA], '/search/', [
            'category_id' => 1,
            'marka_id' => [62],
            'model_id' => [586],
            's_yers' => [2012],
            'po_yers' => [2016],
            'price_ot' => 8000,
            'price_do' => 15000,
            'currency' => 1,
            'abroad' => 2,
            'custom' => 1,
            'type' => [null, 2],
            'gearbox' => [1],
            'fuelRatesType' => 'city',
            'engineVolumeFrom' => null,
            'engineVolumeTo' => null,
            'power_name' => 1,
            'top' => 1,
            'countpage' => 10,
            'with_photo' => true,
        ]);

        $dom = HtmlDomParser::str_get_html(file_get_contents($url));

        $externalCarLinks = $dom->find('#searchResults section.ticket-item .item a');

        $external_car_ids = [];
        $external_car_link = [];
        foreach ($externalCarLinks as $carLink) {
            $carId = (int) preg_replace('/\D/', '', $carLink->href);
            $external_car_ids[] = $carId;
            $external_car_link[$carId] = str_replace(self::SITES[SiteTypes::AUTO_RIA], '', $carLink->href);
        }

        $new_car_ids = array_diff($external_car_ids, $inside_car_ids[SiteTypes::AUTO_RIA] ?? []);

        if (!$new_car_ids) {
            return false;
        }

        return [
            SiteTypes::AUTO_RIA => [
                'new_car_ids' => $new_car_ids,
                'external_car_link' => $external_car_link
            ]
        ];
    }

    /**
     * @param string $site
     * @param string $urn
     * @param array $parameters
     * @return string
     */
    private function buildUrl(string $site, string $urn, array $parameters): string
    {
        return ltrim($site, '/'). $urn . self::SEPARATOR . http_build_query($parameters);
    }
}
