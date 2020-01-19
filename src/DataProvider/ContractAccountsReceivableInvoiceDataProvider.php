<?php

declare(strict_types=1);

namespace App\DataProvider;

use ApiPlatform\Core\DataProvider\ItemDataProviderInterface;
use ApiPlatform\Core\DataProvider\RestrictedDataProviderInterface;
use App\Entity\AccountsReceivableInvoice;
use App\Entity\ApplicationRequest;
use App\Entity\Contract;
use App\Enum\ApplicationRequestType;
use App\WebService\Billing\ClientInterface as WebServiceClient;
use Doctrine\ORM\EntityManagerInterface;

class ContractAccountsReceivableInvoiceDataProvider implements ItemDataProviderInterface, RestrictedDataProviderInterface
{
    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    /**
     * @var WebServiceClient
     */
    private $webServiceClient;

    public function __construct(EntityManagerInterface $entityManager, WebServiceClient $webServiceClient)
    {
        $this->entityManager = $entityManager;
        $this->webServiceClient = $webServiceClient;
    }

    public function supports(string $resourceClass, string $operationName = null, array $context = []): bool
    {
        return AccountsReceivableInvoice::class === $resourceClass;
    }

    public function getItem(string $resourceClass, $id, string $operationName = null, array $context = [])
    {
        list($id, $invoiceNumber) = $id;
        $data = null;

        $contract = $this->entityManager->getRepository(Contract::class)->find($id);
        $contractApplication = null;

        if ($contract instanceof Contract) {
            $applicationRequests = $this->entityManager->getRepository(ApplicationRequest::class)->findBy(['contract' => $contract->getId()]);

            foreach ($applicationRequests as $applicationRequest) {
                if (ApplicationRequestType::CONTRACT_APPLICATION === $applicationRequest->getType()->getValue()) {
                    $contractApplication = $applicationRequest;
                    break;
                }
            }
        }

        if (\in_array('accounts_receivable_invoice_attachment_read', $context, true)) {
            $data = $this->webServiceClient->getARInvoiceAttachment($invoiceNumber, $contract, $contractApplication);
        } elseif (\in_array('accounts_receivable_invoice_read', $context, true)) {
            $data = $this->webServiceClient->getARInvoice($invoiceNumber, $contract, $contractApplication);
        }

        return $data;
    }
}
