<?php

declare(strict_types=1);

namespace App\Entity;

use ApiPlatform\Core\Annotation\ApiProperty;
use ApiPlatform\Core\Annotation\ApiResource;
use App\Enum\QueueName;
use Doctrine\ORM\Mapping as ORM;

/**
 * A Cron jobs schedule.
 *
 * @ORM\Entity
 * @ApiResource(attributes={
 *     "normalization_context"={"groups"={"cron_job_schedule_read"}},
 *     "denormalization_context"={"groups"={"cron_job_schedule_write"}}
 * })
 */
class CronJobSchedule
{
    use Traits\BlameableTrait;
    use Traits\TimestampableTrait;

    /**
     * @var int
     *
     * @ORM\Column(type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @var bool Determines whether the job has been enabled.
     *
     * @ORM\Column(type="boolean", nullable=false)
     * @ApiProperty()
     */
    protected $enabled;

    /**
     * @var \DateInterval|null Time interval for job.
     *
     * @ORM\Column(type="dateinterval", nullable=true)
     * @ApiProperty()
     */
    protected $intervals;

    /**
     * @var string Job type.
     *
     * @ORM\Column(type="string", nullable=false)
     * @ApiProperty()
     */
    protected $jobType;

    /**
     * @var QueueName The queue this job belongs to.
     *
     * @ORM\Column(type="queue_name_enum")
     * @ApiProperty()
     */
    protected $queue;

    /**
     * @var \DateTime|null Time of the job.
     *
     * @ORM\Column(type="time", nullable=true)
     */
    protected $time;

    /**
     * @return int
     */
    public function getId(): int
    {
        return $this->id;
    }

    /**
     * Gets enabled.
     *
     * @return bool
     */
    public function isEnabled(): bool
    {
        return $this->enabled;
    }

    /**
     * Sets enabled.
     *
     * @param bool $enabled
     *
     * @return $this
     */
    public function setEnabled(bool $enabled)
    {
        $this->enabled = $enabled;

        return $this;
    }

    /**
     * Gets intervals.
     *
     * @return \DateInterval|null
     */
    public function getIntervals(): ?\DateInterval
    {
        return $this->intervals;
    }

    /**
     * Sets interval.
     *
     * @param \DateInterval|null $intervals
     *
     * @return $this
     */
    public function setIntervals(?\DateInterval $intervals)
    {
        $this->intervals = $intervals;

        return $this;
    }

    /**
     * Gets jobType.
     *
     * @return string
     */
    public function getJobType(): string
    {
        return $this->jobType;
    }

    /**
     * Sets jobType.
     *
     * @param string $jobType
     *
     * @return $this
     */
    public function setJobType(string $jobType)
    {
        $this->jobType = $jobType;

        return $this;
    }

    /**
     * Gets queue.
     *
     * @return QueueName
     */
    public function getQueue(): QueueName
    {
        return $this->queue;
    }

    /**
     * Sets queue.
     *
     * @param QueueName $queue
     *
     * @return $this
     */
    public function setQueue(QueueName $queue)
    {
        $this->queue = $queue;

        return $this;
    }

    /**
     * Gets time.
     *
     * @return \DateTime|null
     */
    public function getTime(): ?\DateTime
    {
        return $this->time;
    }

    /**
     * Sets time.
     *
     * @param \DateTime|null $time
     *
     * @return $this
     */
    public function setTime(?\DateTime $time)
    {
        $this->time = $time;

        return $this;
    }
}
