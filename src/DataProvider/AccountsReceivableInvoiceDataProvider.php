<?php

declare(strict_types=1);

namespace App\DataProvider;

use ApiPlatform\Core\DataProvider\ItemDataProviderInterface;
use ApiPlatform\Core\DataProvider\RestrictedDataProviderInterface;
use App\Entity\AccountsReceivableInvoice;
use App\WebService\Billing\ClientInterface as WebServiceClient;

class AccountsReceivableInvoiceDataProvider implements ItemDataProviderInterface, RestrictedDataProviderInterface
{
    /**
     * @var WebServiceClient
     */
    private $webServiceClient;

    public function __construct(WebServiceClient $webServiceClient)
    {
        $this->webServiceClient = $webServiceClient;
    }

    public function supports(string $resourceClass, string $operationName = null, array $context = []): bool
    {
        return AccountsReceivableInvoice::class === $resourceClass;
    }

    public function getItem(string $resourceClass, $id, string $operationName = null, array $context = [])
    {
        $invoiceNumber = $id;
        $data = null;

        if (\in_array('accounts_receivable_invoice_attachment_read', $context, true)) {
            $data = $this->webServiceClient->getARInvoiceAttachment($invoiceNumber);
        } elseif (\in_array('accounts_receivable_invoice_read', $context, true)) {
            $data = $this->webServiceClient->getARInvoice($invoiceNumber);
        }

        return $data;
    }
}
