<?php

declare(strict_types=1);

namespace App\Enum;

use MyCLabs\Enum\Enum;

/**
 * A list of document types.
 */
class DocumentType extends Enum
{
    /**
     * @var string Indicates document type of the application request.
     */
    const APPLICATION_REQUEST_REPORT = 'APPLICATION_REQUEST_REPORT';

    /**
     * @var string Indicates document type of the billing partner.
     */
    const BILLING_PARTNER = 'BILLING_PARTNER';

    /**
     * @var string Indicates document type of the campaign.
     */
    const CAMPAIGN_REPORT = 'CAMPAIGN_REPORT';

    /**
     * @var string Indicates document type of the contract report.
     */
    const CONTRACT_REPORT = 'CONTRACT_REPORT';

    /**
     * @var string Indicates document type of the credits action report.
     */
    const CREDITS_ACTION_REPORT = 'CREDITS_ACTION_REPORT';

    /**
     * @var string Indicates document type of the customer account relationship report.
     */
    const CUSTOMER_ACCOUNT_RELATIONSHIP_REPORT = 'CUSTOMER_ACCOUNT_RELATIONSHIP_REPORT';

    /**
     * @var string Indicates document type of the customer account report.
     */
    const CUSTOMER_ACCOUNT_REPORT = 'CUSTOMER_ACCOUNT_REPORT';

    /**
     * @var string Indicates document type of the customer contract form.
     */
    const CUSTOMER_CONTRACT_FORM = 'CUSTOMER_CONTRACT_FORM';

    /**
     * @var string Indicates document type of the direct mail campaign.
     */
    const DIRECT_MAIL_CAMPAIGN = 'DIRECT_MAIL_CAMPAIGN';

    /**
     * @var string Indicates document type of the lead report.
     */
    const LEAD_REPORT = 'LEAD_REPORT';

    /**
     * @var string Indicates document type of the offer catalog.
     */
    const OFFER_CATALOG = 'OFFER_CATALOG';

    /**
     * @var string Indicates document type of order report.
     */
    const ORDER_REPORT = 'ORDER_REPORT';

    /**
     * @var string Indicates document type of the partner commission statement.
     */
    const PARTNER_COMMISSION_STATEMENT = 'PARTNER_COMMISSION_STATEMENT';

    /**
     * @var string Indicates document type of the partner contract application request report.
     */
    const PARTNER_CONTRACT_APPLICATION_REQUEST_REPORT = 'PARTNER_CONTRACT_APPLICATION_REQUEST_REPORT';

    /**
     * @var string Indicates document type of the ticket report.
     */
    const TICKET_REPORT = 'TICKET_REPORT';

    /**
     * @var string Indicates document type of the user report.
     */
    const USER_REPORT = 'USER_REPORT';
}
