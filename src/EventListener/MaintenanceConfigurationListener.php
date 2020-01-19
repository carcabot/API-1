<?php

declare(strict_types=1);

namespace App\EventListener;

use App\Disque\JobType;
use App\Entity\MaintenanceConfiguration;
use App\Enum\MaintenanceConfigurationStatus;
use Disque\Queue\Job as DisqueJob;
use Disque\Queue\Queue as DisqueQueue;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\Event\GetResponseForControllerResultEvent;
use Symfony\Component\HttpKernel\Exception\ServiceUnavailableHttpException;

class MaintenanceConfigurationListener
{
    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    /**
     * @var DisqueQueue
     */
    private $cronQueue;

    /**
     * @var \DateTimeZone
     */
    private $timezone;

    /**
     * @var array|null
     */
    private $url;

    /**
     * @param EntityManagerInterface $entityManager
     * @param DisqueQueue            $cronQueue
     * @param string                 $timezone
     * @param array|null             $url
     */
    public function __construct(EntityManagerInterface $entityManager, DisqueQueue $cronQueue, string $timezone, ?array $url)
    {
        $this->entityManager = $entityManager;
        $this->cronQueue = $cronQueue;
        $this->timezone = new \DateTimeZone($timezone);
        $this->url = $url;
    }

    /**
     * @param GetResponseEvent $event
     */
    public function onKernelRequest(GetResponseEvent $event)
    {
        $request = $event->getRequest();
        $origin = $request->headers->get('Origin');
        $data = $request->attributes->get('data');

        if (null === $origin) {
            return;
        }

        if ($data instanceof MaintenanceConfiguration && Request::METHOD_GET === $request->getMethod()) {
            return;
        }

        if (!empty($this->url['partnership_portal_url'])) {
            if ($origin === $this->url['partnership_portal_url']) {
                $this->checkMaintenance('PARTNERSHIP_PORTAL');
            }
        }

        if (!empty($this->url['customer_portal_url'])) {
            if ($origin === $this->url['customer_portal_url']) {
                $this->checkMaintenance('PORTAL');
            }
        }

        if (!empty($this->url['homepage_url'])) {
            if ($origin === $this->url['homepage_url']) {
                $this->checkMaintenance('HOMEPAGE');
            }
        }
    }

    /**
     * @param GetResponseForControllerResultEvent $event
     */
    public function onPostWrite(GetResponseForControllerResultEvent $event)
    {
        $maintenanceConfiguration = $event->getControllerResult();
        $request = $event->getRequest();

        if (!$maintenanceConfiguration instanceof MaintenanceConfiguration) {
            return;
        }

        if (!\in_array($request->getMethod(), [
            Request::METHOD_POST,
            Request::METHOD_PUT,
        ], true)) {
            return;
        }

        if (MaintenanceConfigurationStatus::CANCELLED === $maintenanceConfiguration->getStatus()->getValue()) {
            return;
        }

        $now = new \DateTime('now', $this->timezone);
        $endtime = new \DateTime('now', $this->timezone);
        $endtime->setTime(23, 59, 59);

        if ($maintenanceConfiguration->getPlannedStartDate() <= $endtime && $maintenanceConfiguration->getPlannedStartDate() > $now) {
            $this->cronQueue->schedule(new DisqueJob([
                'data' => [
                    'id' => $maintenanceConfiguration->getId(),
                    'type' => 'start',
                ],
                'type' => JobType::CRON_UPDATE_MAINTENANCE_STATUS,
            ]), $maintenanceConfiguration->getPlannedStartDate());
        }

        if ($maintenanceConfiguration->getPlannedEndDate() <= $endtime && $maintenanceConfiguration->getPlannedEndDate() > $now) {
            $this->cronQueue->schedule(new DisqueJob([
                'data' => [
                    'id' => $maintenanceConfiguration->getId(),
                    'type' => 'end',
                ],
                'type' => JobType::CRON_UPDATE_MAINTENANCE_STATUS,
            ]), $maintenanceConfiguration->getPlannedEndDate());
        }
    }

    private function checkMaintenance(string $origin)
    {
        $qb = $this->entityManager->getRepository(MaintenanceConfiguration::class)->createQueryBuilder('mc');
        $expr = $qb->expr();

        $maintenanceConfigurations = $qb
            ->where($expr->eq(<<<'SQL'
                    jsonb_contains(CAST(mc.usedIn AS jsonb), :param)
SQL
                , $expr->literal(true)))
            ->andWhere($expr->in('mc.status', ':statuses'))
            ->andWhere($expr->andX($expr->lte('mc.plannedStartDate', ':now'), $expr->gte('mc.plannedEndDate', ':now')))
            ->setParameter('param', \json_encode($origin))
            ->setParameter('statuses', ['PENDING', 'ACTIVE'])
            ->setParameter('now', new \DateTime())
            ->getQuery()
            ->getResult();
        if (\count($maintenanceConfigurations) <= 0) {
            return;
        }
        $maintenanceConfiguration = $maintenanceConfigurations[0];

        throw new ServiceUnavailableHttpException(null, \sprintf('Service Temporarily Unavailable due to Maintenance/%s', $maintenanceConfiguration->getId()));
    }
}
