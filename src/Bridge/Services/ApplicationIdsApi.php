<?php

declare(strict_types=1);
/**
 * Created by PhpStorm.
 * User: mufeer
 * Date: 19/1/19
 * Time: 6:18 PM.
 */

namespace App\Bridge\Services;

use App\Document\OldApplicationIds;
use App\Entity\RunningNumber;
use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;

class ApplicationIdsApi
{
    /**
     * @var DocumentManager
     */
    private $documentManager;

    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @param DocumentManager        $documentManager
     * @param EntityManagerInterface $entityManager
     * @param LoggerInterface        $logger
     */
    public function __construct(DocumentManager $documentManager, EntityManagerInterface $entityManager, LoggerInterface $logger)
    {
        $this->documentManager = $documentManager;
        $this->entityManager = $entityManager;
        $this->logger = $logger;
    }

    /**
     * Create applicationids into database.
     *
     * @param OldApplicationIds $applicationIds
     */
    public function createIds(OldApplicationIds $applicationIds)
    {
        $runningNumber = new RunningNumber();

        if (!empty($applicationIds->getNextNumber()) && null !== $applicationIds->getNextNumber()) {
            $runningNumber->setNumber($applicationIds->getNextNumber() - 1);
        }

        if (!empty($applicationIds->getType()) && null !== $applicationIds->getType()) {
            if (false !== \stripos($applicationIds->getType(), 'CONTRACT_APP')) {
                $runningNumber->setType('CONTRACT_APPLICATION');
            } else {
                $runningNumber->setType($applicationIds->getType());
            }
        } else {
            return;
        }

        if (!empty($applicationIds->getDatePrefix()) && false !== $applicationIds->getDatePrefix()) {
            $runningNumber->setSeries('ym');
        }

        $this->entityManager->persist($runningNumber);
        $this->entityManager->flush();
    }
}
