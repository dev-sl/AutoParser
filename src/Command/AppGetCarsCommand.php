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
     * @var string $defaultName
     */
    protected static $defaultName = 'app:get-cars';

    /**
     * @var EntityManager $em
     */
    private $em;

    /**
     * @var AutoParseTelegram $telegram
     */
    private $telegram;

    /**
     * @var string SITE
     */
    private const SITE = 'http://rst.ua';

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
        $url = $this->buildUrl([
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

        $insideCars = $this->em->getRepository(Car::class)->findAll();

        $inside_car_ids = [];
        foreach ($insideCars as $car) {
            $inside_car_ids[] = $car->getCarId();
        }

        $external_car_ids = [];
        $external_car_link = [];
        foreach ($externalCarLinks as $carLink) {
            $carId = (int) preg_replace('/\D/', '', $carLink->href);
            $external_car_ids[] = $carId;
            $external_car_link[$carId] = $carLink->href;
        }

        $new_car_ids = array_diff($external_car_ids, $inside_car_ids);

        if (!$new_car_ids) {
            $output->writeln('New cars not found');

            return;
        }

        foreach ($new_car_ids as $car_id) {
            $car = new Car();
            $car->setCarId($car_id)
                ->setSiteId(SiteTypes::RST);

            $this->em->persist($car);

            $this->telegram->sendMessage(
                sprintf('Found new car: %s', self::SITE  . $external_car_link[$car_id], '/')
            );
        }

        $this->em->flush();

        $output->writeln(
            sprintf('New cars: %s',
                implode(',', $new_car_ids)
            )
        );
    }

    /**
     * @param array $parameters
     * @return string
     */
    private function buildUrl(array $parameters): string
    {
        return self::SITE . '/oldcars/renault/megane/' . self::SEPARATOR . http_build_query($parameters);
    }
}
