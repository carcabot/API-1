<?php

declare(strict_types=1);

namespace App\WebService\Billing\Controller;

use App\DataProvider\AccountsReceivableInvoiceDataProvider;
use App\Entity\AccountsReceivableInvoice;

class AccountsReceivableInvoiceAttachmentController
{
    /**
     * @var AccountsReceivableInvoiceDataProvider
     */
    private $arInvoiceDataProvider;

    /**
     * @param AccountsReceivableInvoiceDataProvider $arInvoiceDataProvider
     */
    public function __construct(AccountsReceivableInvoiceDataProvider $arInvoiceDataProvider)
    {
        $this->arInvoiceDataProvider = $arInvoiceDataProvider;
    }

    public function __invoke(string $id)
    {
        return $this->arInvoiceDataProvider->getItem(AccountsReceivableInvoice::class, $id, 'GET', ['accounts_receivable_invoice_attachment_read']);
    }
}
