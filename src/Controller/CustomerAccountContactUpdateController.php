<?php

declare(strict_types=1);

namespace App\Controller;

use ApiPlatform\Core\Api\IriConverterInterface;
use App\Disque\JobType;
use App\Entity\CustomerAccount;
use App\Entity\Person;
use Disque\Queue\Job as DisqueJob;
use Disque\Queue\Queue as DisqueQueue;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Http\Message\ServerRequestInterface;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\SerializerInterface;
use Zend\Diactoros\Response\EmptyResponse;

class CustomerAccountContactUpdateController
{
    /**
     * @var IriConverterInterface
     */
    private $iriConverter;

    /**
     * @var EntityManagerInterface
     */
    private $entityManger;

    /**
     * @var SerializerInterface
     */
    private $serializer;

    /**
     * @var DisqueQueue
     */
    private $webServicesQueue;

    /**
     * @param IriConverterInterface  $iriConverter
     * @param EntityManagerInterface $entityManager
     * @param SerializerInterface    $serializer
     * @param DisqueQueue            $webServicesQueue
     */
    public function __construct(IriConverterInterface $iriConverter, EntityManagerInterface $entityManager, SerializerInterface $serializer, DisqueQueue $webServicesQueue)
    {
        $this->iriConverter = $iriConverter;
        $this->entityManger = $entityManager;
        $this->serializer = $serializer;
        $this->webServicesQueue = $webServicesQueue;
    }

    /**
     * @Route("/update/customer_account_contact", methods={"POST"})
     *
     * @param ServerRequestInterface $request
     */
    public function __invoke(ServerRequestInterface $request)
    {
        $params = \json_decode($request->getBody()->getContents(), true);
        $personDetails = null;
        $previousName = null;
        $isChanged = false;

        if (isset($params['id'])) {
            try {
                $customerAccount = $this->iriConverter->getItemFromIri($params['id']);
            } catch (\Exception $ex) {
                return new BadRequestHttpException('Wrong iri provided.');
            }

            if (!$customerAccount instanceof CustomerAccount) {
                return new BadRequestHttpException('Customer Account not found');
            }
        } else {
            return new BadRequestHttpException('The id field is required.');
        }

        if (isset($params['personDetails'])) {
            $personDetails = $this->serializer->deserialize(\json_encode($params['personDetails']), Person::class, 'json', ['groups' => [
                'person_write',
                'contact_point_write',
                'identification_write',
            ]]);

            if ($personDetails instanceof Person) {
                $currentPersonDetails = $customerAccount->getPersonDetails();

                if (null !== $currentPersonDetails) {
                    if ($currentPersonDetails->getName() !== $personDetails->getName()) {
                        $isChanged = true;
                        $previousName = $personDetails->getName();
                    }

                    $contactPoint = $personDetails->getContactPoints()[0];
                    $currentContactPoint = $currentPersonDetails->getContactPoints()[0];

                    if (\count($contactPoint->getEmails()) > 0 && \count($currentContactPoint->getEmails()) > 0 && $contactPoint->getEmails()[0] !== $currentContactPoint->getEmails()[0]) {
                        $isChanged = true;
                    }

                    if (\count($contactPoint->getFaxNumbers()) > 0 && \count($currentContactPoint->getFaxNumbers()) > 0 && $contactPoint->getFaxNumbers()[0]->getNationalNumber() !== $currentContactPoint->getFaxNumbers()[0]->getNationalNumber()) {
                        $isChanged = true;
                    }

                    if (\count($contactPoint->getMobilePhoneNumbers()) > 0 && \count($currentContactPoint->getMobilePhoneNumbers()) > 0 && $contactPoint->getMobilePhoneNumbers()[0]->getNationalNumber() !== $currentContactPoint->getMobilePhoneNumbers()[0]->getNationalNumber()) {
                        $isChanged = true;
                    }

                    if (\count($contactPoint->getTelephoneNumbers()) > 0 && \count($currentContactPoint->getTelephoneNumbers()) > 0 && $contactPoint->getTelephoneNumbers()[0]->getNationalNumber() !== $currentContactPoint->getTelephoneNumbers()[0]->getNationalNumber()) {
                        $isChanged = true;
                    }
                }
            }
        }

        if ($isChanged) {
            $job = new DisqueJob([
                'data' => [
                    'id' => $customerAccount->getId(),
                    'previousName' => $previousName,
                ],
                'type' => JobType::CUSTOMER_ACCOUNT_CONTACT_UPDATE,
            ]);

            $this->webServicesQueue->push($job);
        }

        return new EmptyResponse(200);
    }
}
