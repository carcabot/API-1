<?php

declare(strict_types=1);

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * A table to keep track of running numbers.
 *
 * @ORM\Entity
 * @ORM\Table(uniqueConstraints={@ORM\UniqueConstraint(name="idx_unq_type_series", columns={"type", "series"})})
 */
class RunningNumber
{
    use Traits\TimestampableTrait;

    /**
     * @var string
     *
     * @ORM\Column(name="type", type="string", length=255, nullable=false)
     * @ORM\Id
     */
    private $type;

    /**
     * @var string
     *
     * @ORM\Column(name="series", type="string", nullable=false)
     * @ORM\Id
     */
    private $series;

    /**
     * @var int
     *
     * @ORM\Column(name="number", type="integer", nullable=false)
     */
    private $number;

    /**
     * Set type.
     *
     * @param string $type
     *
     * @return $this
     */
    public function setType(string $type)
    {
        $this->type = $type;

        return $this;
    }

    /**
     * Get type.
     *
     * @return string
     */
    public function getType(): string
    {
        return $this->type;
    }

    /**
     * Set series.
     *
     * @param string $series
     *
     * @return $this
     */
    public function setSeries(string $series)
    {
        $this->series = $series;

        return $this;
    }

    /**
     * Get series.
     *
     * @return string
     */
    public function getSeries(): string
    {
        return $this->series;
    }

    /**
     * Set number.
     *
     * @param int $number
     *
     * @return $this
     */
    public function setNumber(int $number)
    {
        $this->number = $number;

        return $this;
    }

    /**
     * Get number.
     *
     * @return int
     */
    public function getNumber(): int
    {
        return $this->number;
    }
}
