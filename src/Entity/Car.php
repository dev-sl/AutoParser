<?php declare(strict_types=1);

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Table(name="car",
 *    uniqueConstraints={
 *        @ORM\UniqueConstraint(name="car_unique",
 *            columns={"car_id", "site_id"})
 *    }
 * )
 * @ORM\Entity
 */
class Car
{
    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="integer", nullable=false)
     */
    private $car_id;

    /**
     * @ORM\Column(type="integer", nullable=false)
     */
    private $site_id;

    public function getId()
    {
        return $this->id;
    }

    public function getCarId(): int
    {
        return $this->car_id;
    }

    public function setCarId(int $car_id): self
    {
        $this->car_id = $car_id;

        return $this;
    }

    public function getSiteId(): int
    {
        return $this->site_id;
    }

    public function setSiteId(int $site_id): self
    {
        $this->site_id = $site_id;

        return $this;
    }
}
