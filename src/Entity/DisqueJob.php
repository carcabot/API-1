<?php

declare(strict_types=1);

namespace App\Entity;

use ApiPlatform\Core\Annotation\ApiProperty;
use ApiPlatform\Core\Annotation\ApiResource;
use App\Enum\JobStatus;
use App\Enum\QueueName;
use Doctrine\ORM\Mapping as ORM;

/**
 * Disque jobs.
 *
 * @ORM\Entity
 * @ApiResource(attributes={
 *     "normalization_context"={"groups"={"disque_job_read"}},
 *     "denormalization_context"={"groups"={"disque_job_write"}},
 *     "filters"={
 *         "disque_job.search",
 *     },
 * })
 */
class DisqueJob
{
    use Traits\BlameableTrait;
    use Traits\TimestampableTrait;

    /**
     * @var int|null
     *
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @var string[] The job body.
     *
     * @ORM\Column(type="json", nullable=false, options={"jsonb"=true})
     * @ApiProperty()
     */
    protected $body;

    /**
     * @var string The job number.
     *
     * @ORM\Column(type="text", nullable=false)
     * @ApiProperty()
     */
    protected $jobNumber;

    /**
     * @var QueueName The job queue.
     *
     * @ORM\Column(type="queue_name_enum", nullable=false)
     * @ApiProperty()
     */
    protected $queue;

    /**
     * @var JobStatus
     *
     * @ORM\Column(type="job_status_enum", nullable=false)
     * @ApiProperty()
     */
    protected $status;

    /**
     * @var string The job type.
     *
     * @ORM\Column(type="text", nullable=false)
     * @ApiProperty()
     */
    protected $type;

    /**
     * Gets id.
     *
     * @return int|null
     */
    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * Sets body.
     *
     * @param array $body
     *
     * @return $this
     */
    public function setBody(array $body)
    {
        $this->body = $body;

        return $this;
    }

    /**
     * Gets body.
     *
     * @return array
     */
    public function getBody(): array
    {
        return $this->body;
    }

    /**
     * Sets jobNumber.
     *
     * @param string $jobNumber
     *
     * @return $this
     */
    public function setJobNumber(string $jobNumber)
    {
        $this->jobNumber = $jobNumber;

        return $this;
    }

    /**
     * Gets jobNumber.
     *
     * @return string
     */
    public function getJobNumber(): string
    {
        return $this->jobNumber;
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
     * Gets queue.
     *
     * @return QueueName
     */
    public function getQueue(): QueueName
    {
        return $this->queue;
    }

    /**
     * Gets status.
     *
     * @return JobStatus
     */
    public function getStatus(): JobStatus
    {
        return $this->status;
    }

    /**
     * Sets status.
     *
     * @param JobStatus $status
     *
     * @return $this
     */
    public function setStatus(JobStatus $status)
    {
        $this->status = $status;

        return $this;
    }

    /**
     * Sets type.
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
     * Gets type.
     *
     * @return string
     */
    public function getType(): string
    {
        return $this->type;
    }
}
