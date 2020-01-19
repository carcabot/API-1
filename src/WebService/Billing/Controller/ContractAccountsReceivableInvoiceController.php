<?php

declare(strict_types=1);

namespace App\WebService\Billing\Controller;

use App\DataProvider\ContractAccountsReceivableInvoiceDataProvider;
use App\Entity\AccountsReceivableInvoice;

class ContractAccountsReceivableInvoiceController
{
    /**
     * @var ContractAccountsReceivableInvoiceDataProvider
     */
    private $contractInvoiceDataProvider;

    /**
     * @param ContractAccountsReceivableInvoiceDataProvider $contractInvoiceDataProvider
     */
    public function __construct(ContractAccountsReceivableInvoiceDataProvider $contractInvoiceDataProvider)
    {
        $this->contractInvoiceDataProvider = $contractInvoiceDataProvider;
    }

    public function __invoke($id, $invoiceNumber)
    {
        return $this->contractInvoiceDataProvider->getItem(AccountsReceivableInvoice::class, [$id, $invoiceNumber], 'GET', ['accounts_receivable_invoice_read']);
    }
}
