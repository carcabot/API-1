<?php

declare(strict_types=1);

namespace App\Model;

use App\Domain\Command\ApplicationRequest\UpdateApplicationRequestNumber;
use App\Entity\ApplicationRequest;
use App\Entity\Contract;
use Doctrine\ORM\EntityManagerInterface;
use League\Tactician\CommandBus;
use Psr\Log\LoggerInterface;
use Symfony\Component\Serializer\SerializerInterface;

class ContractApplicationRequestRenewalCreator
{
    /**
     * @var CommandBus
     */
    private $commandBus;

    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var SerializerInterface
     */
    private $serializer;

    /**
     * @var string
     */
    private $tempDir;

    /**
     * @var \DateTimeZone
     */
    private $timezone;

    /**
     * @param string                 $tempDir
     * @param string                 $timezone
     * @param LoggerInterface        $logger
     * @param CommandBus             $commandBus
     * @param EntityManagerInterface $entityManager
     * @param SerializerInterface    $serializer
     */
    public function __construct(string $tempDir, string $timezone, LoggerInterface $logger, CommandBus $commandBus, EntityManagerInterface $entityManager, SerializerInterface $serializer)
    {
        $this->commandBus = $commandBus;
        $this->entityManager = $entityManager;
        $this->logger = $logger;
        $this->serializer = $serializer;
        $this->tempDir = $tempDir;
        $this->timezone = new \DateTimeZone($timezone);
    }

    public function processArrayData(array $data): array
    {
        $results = [];
        foreach ($data as $dataum) {
            $results[] = $this->createApplicationRequest($dataum);
        }

        return $results;
    }

    public function createApplicationRequest(array $data): array
    {
        $applicationRequest = null;

        try {
            if (isset($data['applicationRequest'])) {
                $applicationRequestData = $data['applicationRequest'];

                $contractData = $data['contract'];
                /**
                 * @var Contract|null
                 */
                $contract = null;
                if (isset($data['contract'])) {
                    $contract = $this->entityManager->getRepository(Contract::class)->findOneBy(['contractNumber' => $contractData['contractNumber']]);
                } else {
                    $this->logger->info('Mass uploading contract renewal fail. No contract in file.');
                    throw new \Exception('Mass uploading contract renewal fail. No contract in file.');
                }

                if (null !== $contract) {
                    if (isset($applicationRequestData['supplementaryFiles'])) {
                        $supplementaryFiles = $applicationRequestData['supplementaryFiles'];
                        foreach ($supplementaryFiles as $index => $supplementaryFile) {
                            $tempFileName = \uniqid();
                            $tempFilePath = $this->tempDir.'/'.$tempFileName;
                            $tempFile = new \SplFileObject('php://filter/convert.base64-decode/resource='.$tempFilePath, 'w');
                            $tempFile->fwrite($supplementaryFile['contentFile']);

                            $applicationRequestData['supplementaryFiles'][$index]['contentFile'] = $tempFilePath;
                        }
                    }

                    $applicationRequest = $this->serializer->deserialize(\json_encode($applicationRequestData), ApplicationRequest::class, 'json', ['groups' => [
                        'application_request_write',
                        'digital_document_write',
                    ]]);

                    if ($applicationRequest instanceof ApplicationRequest) {
                        $applicationRequest->setContract($contract);

                        $applicationRequest->setCustomer($contract->getCustomer());
                        $applicationRequest->setCustomerType($contract->getCustomer()->getType());

                        if (null !== $contract->getCustomer()->getPersonDetails()) {
                            $personDetails = clone $contract->getCustomer()->getPersonDetails();
                            $this->entityManager->persist($personDetails);

                            $applicationRequest->setPersonDetails($personDetails);
                        }

                        if (null !== $contract->getCustomer()->getCorporationDetails()) {
                            $corporationDetails = clone $contract->getCustomer()->getCorporationDetails();
                            $this->entityManager->persist($corporationDetails);

                            $applicationRequest->setCorporationDetails($corporationDetails);
                        }

                        $applicationRequest->setContactPerson($contract->getContactPerson());

                        $utcTimezone = new \DateTimeZone('UTC');

                        if (isset($applicationRequestData['preferredStartDate'])) {
                            $startDate = new \DateTime($applicationRequestData['preferredStartDate'], $this->timezone);
                            $applicationRequest->setPreferredStartDate($startDate->setTimezone($utcTimezone));
                        }

                        $contractAddresses = $contract->getAddresses();
                        foreach ($contractAddresses as $contractAddress) {
                            $applicationRequestAddress = clone $contractAddress->getAddress();
                            $this->entityManager->persist($applicationRequestAddress);
                            $applicationRequest->addAddress($applicationRequestAddress);
                        }

                        $this->entityManager->getConnection()->beginTransaction();
                        $this->entityManager->getConnection()->exec('LOCK TABLE running_numbers IN SHARE ROW EXCLUSIVE MODE;');
                        $this->commandBus->handle(new UpdateApplicationRequestNumber($applicationRequest));
                        $this->entityManager->persist($applicationRequest);
                        $this->entityManager->flush();
                        $this->entityManager->getConnection()->commit();

                        return [
                            'FRCReContractNumber' => $applicationRequest->getExternalApplicationRequestNumber(),
                            'CRMFRCReContractNumber' => $applicationRequest->getApplicationRequestNumber(),
                            'ProcessStatus' => 1,
                            'Message' => 'New Application Create Successful.',
                        ];
                    }
                }

                $this->logger->info(\sprintf('Mass uploading contract renewal fail. Contract with number %s does not exist.', $contractData['contractNumber']));
                throw new \Exception(\sprintf('Mass uploading contract renewal fail. Contract with number %s does not exist.', $contractData['contractNumber']));
            }

            $this->logger->info('Mass uploading contract renewal fail. No data.');
            throw new \Exception('Mass uploading contract renewal fail. No data.');
        } catch (\Exception $ex) {
            $this->entityManager->clear();

            return[
                'FRCReContractNumber' => 'Mass uploading contract renewal fail. No data.' !== $ex->getMessage() ? $data['applicationRequest']['externalApplicationRequestNumber'] : '',
                'CRMFRCReContractNumber' => '',
                'ProcessStatus' => 0,
                'Message' => $ex->getMessage(),
            ];
        }
    }
}
