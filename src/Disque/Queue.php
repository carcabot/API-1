<?php

declare(strict_types=1);

namespace App\Disque;

use App\Entity\DisqueJob;
use App\Enum\JobStatus;
use App\Enum\QueueName;
use Disque\Queue\JobInterface;
use Disque\Queue\Queue as DisqueQueue;
use Doctrine\ORM\EntityManagerInterface;

class Queue extends DisqueQueue
{
    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    /**
     * Create a queue.
     *
     * @param Client                 $client        Client
     * @param string                 $name          Queue name
     * @param EntityManagerInterface $entityManager Entity manager
     */
    public function __construct(Client $client, $name, EntityManagerInterface $entityManager)
    {
        parent::__construct($client, $name);
        $this->entityManager = $entityManager;
    }

    /**
     * Check that we are connected to a node, and if not connect.
     *
     * @throws Disque\Connection\ConnectionException
     */
    private function checkConnected()
    {
        if (!$this->client->isConnected()) {
            $this->client->connect();
        }
    }

    /**
     * Acknowledges a Job as properly handled.
     *
     * @param JobInterface $job Job
     */
    public function processed(JobInterface $job)
    {
        $this->checkConnected();
        $this->client->ackJob($job->getId());

        if ($job->getNacks() > 0 || $job->getAdditionalDeliveries() > 0) {
            $status = new JobStatus(JobStatus::FAILED);
        } else {
            $status = new JobStatus(JobStatus::COMPLETED);
        }

        $disqueJob = $this->entityManager->getRepository(DisqueJob::class)->findOneBy(['jobNumber' => $job->getId()]);

        if (null !== $disqueJob) {
            $disqueJob->setStatus($status);
            $this->entityManager->persist($disqueJob);
            $this->entityManager->flush();
        }
    }

    /**
     * Pushes a job into the queue.
     *
     * @param JobInterface $job     Job
     * @param array        $options ADDJOB options sent to the client
     *
     * @return JobInterface Job pushed
     */
    public function push(JobInterface $job, array $options = [])
    {
        $job = parent::push($job, $options);

        $exclusions = [
            JobType::APPLICATION_REQUEST_UPDATE_CACHE_TABLE,
            JobType::CONTRACT_UPDATE_CACHE_TABLE,
        ];

        $queueNames = [
            'application_requests' => new QueueName(QueueName::APPLICATION_REQUEST),
            'campaigns' => new QueueName(QueueName::CAMPAIGN),
            'contracts' => new QueueName(QueueName::CONTRACT),
            'cron' => new QueueName(QueueName::CRON),
            'emails' => new QueueName(QueueName::EMAIL),
            'messages' => new QueueName(QueueName::MESSAGE),
            'reports' => new QueueName(QueueName::REPORT),
            'web_services' => new QueueName(QueueName::WEB_SERVICE),
        ];

        if (isset($queueNames[$this->name])) {
            $disqueJob = new DisqueJob();
            $jobType = $job->getBody()['type'] ?? 'Unknown';

            if (!\in_array($jobType, $exclusions, true)) {
                if (!empty($options['delay'])) {
                    $status = new JobStatus(JobStatus::SCHEDULED);
                } else {
                    $status = new JobStatus(JobStatus::QUEUED);
                }

                $disqueJob->setBody($job->getBody());
                $disqueJob->setJobNumber($job->getId());
                $disqueJob->setQueue($queueNames[$this->name]);
                $disqueJob->setStatus($status);
                $disqueJob->setType($jobType);

                $this->entityManager->persist($disqueJob);
                $this->entityManager->flush();
            }
        }

        return $job;
    }
}
