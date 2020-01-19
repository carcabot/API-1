<?php

declare(strict_types=1);

namespace App\Model;

use App\Entity\TariffDailyRate;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Form\Exception\AlreadySubmittedException;

class TariffDailyRateValidator
{
    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    /**
     * @var \DateTimeZone
     */
    private $timezone;

    /**
     * @param EntityManagerInterface $entityManager
     * @param string                 $timezone
     */
    public function __construct(EntityManagerInterface $entityManager, string $timezone)
    {
        $this->entityManager = $entityManager;
        $this->timezone = new \DateTimeZone($timezone);
    }

    /**
     * @param TariffDailyRate $object
     */
    public function validate(TariffDailyRate $object)
    {
        $startOfToday = new \DateTime('today midnight', $this->timezone);
        $endOfToday = new \DateTime('tomorrow midnight', $this->timezone);
        $startOfToday->setTimezone(new \DateTimeZone('UTC'));
        $endOfToday->setTimezone(new \DateTimeZone('UTC'));

        $expr = $this->entityManager->getExpressionBuilder();

        $tariffDR = $this->entityManager->getRepository(TariffDailyRate::class)->createQueryBuilder('tdr');
        $tariffDailyRates = $tariffDR->select('tdr')
                ->where(
                    $expr->eq('tdr.tariffRate', ':tariffRate')
                )
                ->setParameters([
                    'tariffRate' => $object->getTariffRate(),
                ])
                ->getQuery()
                ->getResult();
        if (\iter\count($tariffDailyRates) > 0) {
            foreach ($tariffDailyRates as $tariffDailyRate) {
                if ($tariffDailyRate->getDateCreated() >= $startOfToday && $tariffDailyRate->getDateCreated() < $endOfToday) {
                    throw new AlreadySubmittedException('Daily rate for today has been added.');
                    break;
                }
            }
        }
    }
}
