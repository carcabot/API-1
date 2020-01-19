<?php

declare(strict_types=1);

namespace App\Enum;

use MyCLabs\Enum\Enum;

/**
 * A list of sources.
 */
class Source extends Enum
{
    /**
     * @var string Indicates that the source is from the administrative portal.
     */
    const ADMINISTRATIVE_PORTAL = 'ADMINISTRATIVE_PORTAL';

    /**
     * @var string Indicates that the source is from an advertisement.
     */
    const ADVERTISEMENT = 'ADVERTISEMENT';

    /**
     * @var string Indicates that the source is from an application request.
     */
    const APPLICATION_REQUEST = 'APPLICATION_REQUEST';

    /**
     * @var string Indicates that the source is from an association.
     */
    const ASSOCIATION = 'ASSOCIATION';

    /**
     * @var string Indicates that the source is from a bank.
     */
    const BANK = 'BANK';

    /**
     * @var string Indicates that the source is from the billing provider.
     */
    const BILLING_PORTAL = 'BILLING_PORTAL';

    /**
     * @var string Indicates that the source is from a campaign.
     */
    const CAMPAIGN = 'CAMPAIGN';

    /**
     * @var string Indicates that the source is from the homepage.
     */
    const CLIENT_HOMEPAGE = 'CLIENT_HOMEPAGE';

    /**
     * @var string Indicates that the source is from the contact center.
     */
    const CONTACT_CENTER = 'CONTACT_CENTER';

    /**
     * @var string Indicates that the source is from a contact person.
     */
    const CONTACT_PERSON = 'CONTACT_PERSON';

    /**
     * @var string Indicates that the source is from a contract.
     */
    const CONTRACT = 'CONTRACT';

    /**
     * @var string Indicates that the source is from a customer.
     */
    const CUSTOMER = 'CUSTOMER';

    /**
     * @var string Indicates that the source is from DBSPDDA.
     */
    const DBSPDDA = 'DBSPDDA';

    /**
     * @var string Indicates that the source is from DBSRCCS.
     */
    const DBSRCCS = 'DBSRCCS';

    /**
     * @var string Indicates that the source is from a digital marketing.
     */
    const DIGITAL_MARKETING = 'DIGITAL_MARKETING';

    /**
     * @var string Indicates that the source is from a direct marketing.
     */
    const DIRECT_MARKETING = 'DIRECT_MARKETING';

    /**
     * @var string Indicates that the source is from an email.
     */
    const EMAIL = 'EMAIL';

    /**
     * @var string Indicates that the source is a transfer from ES Power.
     */
    const ES_POWER_TRANSFER = 'ES_POWER_TRANSFER';

    /**
     * @var string Indicates that the source is a novation from ES Power.
     */
    const ES_POWER_NOVATION = 'ES_POWER_NOVATION';

    /**
     * @var string Indicates that the source is from an event.
     */
    const EVENT = 'EVENT';

    /**
     * @var string Indicates that the source is from an external list.
     */
    const EXTERNAL_LIST = 'EXTERNAL_LIST';

    /**
     * @var string Indicates that the source is from a face to face encounter.
     */
    const FACE_TO_FACE = 'FACE_TO_FACE';

    /**
     * @var string Indicates that the source is from the letter.
     */
    const LETTER = 'LETTER';

    /**
     * @var string Indicates that the source is from the lead.
     */
    const LEAD = 'LEAD';

    /**
     * @var string Indicates that the source is from a manual entry.
     */
    const MANUAL_ENTRY = 'MANUAL_ENTRY';

    /**
     * @var string Indicates that the source is from a media.
     */
    const MEDIA = 'MEDIA';

    /**
     * @var string Indicates that the source is from a migration.
     */
    const MIGRATED = 'MIGRATED';

    /**
     * @var string Indicates that the source is from the partnership portal.
     */
    const PARTNERSHIP_PORTAL = 'PARTNERSHIP_PORTAL';

    /**
     * @var string Indicates that the source is from a quotation.
     */
    const QUOTATION = 'QUOTATION';

    /**
     * @var string Indicates that the source is from RCCS.
     */
    const RCCS = 'RCCS';

    /**
     * @var string Indicates that the source is from a referral.
     */
    const REFERRAL = 'REFERRAL';

    /**
     * @var string Indicates that the source is from the SSP.
     */
    const SELF_SERVICE_PORTAL = 'SELF_SERVICE_PORTAL';

    /**
     * @var string Indicates that the source is from a staff.
     */
    const STAFF = 'STAFF';

    /**
     * @var string Indicates that the source is from a telemarketing.
     */
    const TELEMARKETING = 'TELEMARKETING';

    /**
     * @var string Indicates that the source is from a telephone.
     */
    const TELEPHONE = 'TELEPHONE';

    /**
     * @var string Indicates that the source is from a tender.
     */
    const TENDER = 'TENDER';

    /**
     * @var string Indicates that the source is from a walk in.
     */
    const WALK_IN = 'WALK_IN';
}
