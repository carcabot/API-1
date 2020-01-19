<?php

declare(strict_types=1);

namespace App\Enum;

use MyCLabs\Enum\Enum;

/**
 * A list of module types.
 */
class ModuleType extends Enum
{
    /**
     * @var string Indicates that the module is activity sms history type.
     */
    const ACTIVITY_SMS_HISTORY = 'ACTIVITY_SMS_HISTORY';

    /**
     * @var string Indicates that the module is admin user type.
     */
    const ADMIN_USER = 'ADMIN_USER';

    /**
     * @var string Indicates that the module is admin user management type.
     */
    const ADMIN_USER_MANAGEMENT = 'ADMIN_USER_MANAGEMENT';

    /**
     * @var string Indicates that the module is administration type.
     */
    const ADMINISTRATION = 'ADMINISTRATION';

    /**
     * @var string Indicates that the module is affiliate program type.
     */
    const AFFILIATE_PROGRAM = 'AFFILIATE_PROGRAM';

    /**
     * @var string Indicates that the module is affiliate program configuration type.
     */
    const AFFILIATE_PROGRAM_CONFIGURATION = 'AFFILIATE_PROGRAM_CONFIGURATION';

    /**
     * @var string Indicates that the module is affiliate program management type.
     */
    const AFFILIATE_PROGRAM_MANAGEMENT = 'AFFILIATE_PROGRAM_MANAGEMENT';

    /**
     * @var string Indicates that the module is announcement type.
     */
    const ANNOUNCEMENT = 'ANNOUNCEMENT';

    /**
     * @var string Indicates that the module is application request type.
     */
    const APPLICATION_REQUEST = 'APPLICATION_REQUEST';

    /**
     * @var string Indicates that the module is application request management type.
     */
    const APPLICATION_REQUEST_MANAGEMENT = 'APPLICATION_REQUEST_MANAGEMENT';

    /**
     * @var string Indicates that the module is application request renewal type.
     */
    const APPLICATION_REQUEST_RENEWAL = 'APPLICATION_REQUEST_RENEWAL';

    /**
     * @var string Indicates that the module is application request report type.
     */
    const APPLICATION_REQUEST_REPORT = 'APPLICATION_REQUEST_REPORT';

    /**
     * @var string Indicates that the module is authorization management type.
     */
    const AUTHORIZATION_MANAGEMENT = 'AUTHORIZATION_MANAGEMENT';

    /**
     * @var string Indicates that the module is campaign type.
     */
    const CAMPAIGN = 'CAMPAIGN';

    /**
     * @var string Indicates that the module is campaign configuration type.
     */
    const CAMPAIGN_CONFIGURATION = 'CAMPAIGN_CONFIGURATION';

    /**
     * @var string Indicates that the module is campaign management type.
     */
    const CAMPAIGN_MANAGEMENT = 'CAMPAIGN_MANAGEMENT';

    /**
     * @var string Indicates that the module is campaign SMS template type.
     */
    const CAMPAIGN_SMS_TEMPLATE = 'CAMPAIGN_SMS_TEMPLATE';

    /**
     * @var string Indicates that the module is campaign template type.
     */
    const CAMPAIGN_TEMPLATE = 'CAMPAIGN_TEMPLATE';

    /**
     * @var string Indicates that the module is client homepage type.
     */
    const CLIENT_HOMEPAGE = 'CLIENT_HOMEPAGE';

    /**
     * string Indicates that the module is commission rate type.
     */
    const COMMISSION_RATE = 'COMMISSION_RATE';

    /**
     * string Indicates that the module is configuration management type.
     */
    const CONFIGURATION_MANAGEMENT = 'CONFIGURATION_MANAGEMENT';

    /**
     * @var string Indicates that the module is contract report type.
     */
    const CONTRACT_REPORT = 'CONTRACT_REPORT';

    /**
     * @var string Indicates that the module is customer account type.
     */
    const CUSTOMER_ACCOUNT = 'CUSTOMER_ACCOUNT';

    /**
     * @var string Indicates that the module is customer account factsheet type.
     */
    const CUSTOMER_ACCOUNT_FACTSHEET = 'CUSTOMER_ACCOUNT_FACTSHEET';

    /**
     * @var string Indicates that the module is customer account management type.
     */
    const CUSTOMER_ACCOUNT_MANAGEMENT = 'CUSTOMER_ACCOUNT_MANAGEMENT';

    /**
     * @var string Indicates that the module is customer account relationship report type.
     */
    const CUSTOMER_ACCOUNT_RELATIONSHIP_REPORT = 'CUSTOMER_ACCOUNT_RELATIONSHIP_REPORT';

    /**
     * @var string Indicates that the module is customer account report type.
     */
    const CUSTOMER_ACCOUNT_REPORT = 'CUSTOMER_ACCOUNT_REPORT';

    /**
     * @var string Indicates that the module is customer portal type.
     */
    const CUSTOMER_PORTAL = 'CUSTOMER_PORTAL';

    /**
     * @var string Indicates that the module is customer portal report type.
     */
    const CUSTOMER_PORTAL_REPORT = 'CUSTOMER_PORTAL_REPORT';

    /**
     * @var string Indicates that the module is dashboard type.
     */
    const DASHBOARD = 'DASHBOARD';

    /**
     * @var string Indicates that the module is department type.
     */
    const DEPARTMENT = 'DEPARTMENT';

    /**
     * @var string Indicates that the module is faq type.
     */
    const FAQ = 'FAQ';

    /**
     * @var string Indicates that the module is faq configuration type.
     */
    const FAQ_CONFIGURATION = 'FAQ_CONFIGURATION';

    /**
     * @var string Indicates that the module is global setting type.
     */
    const GLOBAL_SETTING = 'GLOBAL_SETTING';

    /**
     * @var string Indicates that the module is lead type.
     */
    const LEAD = 'LEAD';

    /**
     * @var string Indicates that the module is lead list management type.
     */
    const LEAD_LIST_MANAGEMENT = 'LEAD_LIST_MANAGEMENT';

    /**
     * @var string Indicates that the module is lead management type.
     */
    const LEAD_MANAGEMENT = 'LEAD_MANAGEMENT';

    /**
     * @var string Indicates that the module is lead report type.
     */
    const LEAD_REPORT = 'LEAD_REPORT';

    /**
     * @var string Indicates that the module is loyalty configuration type.
     */
    const LOYALTY_CONFIGURATION = 'LOYALTY_CONFIGURATION';

    /**
     * @var string Indicates that the module is loyalty management type.
     */
    const LOYALTY_MANAGEMENT = 'LOYALTY_MANAGEMENT';

    /**
     * @var string Indicates that the module is maintenance configuration type.
     */
    const MAINTENANCE_CONFIGURATION = 'MAINTENANCE_CONFIGURATION';

    /**
     * @var string Indicates that the module is marketing type.
     */
    const MARKETING = 'MARKETING';

    /**
     * @var string Indicates that the module is marketing report type.
     */
    const MARKETING_REPORT = 'MARKETING_REPORT';

    /**
     * @var string Indicates that the module is message centre type.
     */
    const MESSAGE_CENTRE = 'MESSAGE_CENTRE';

    /**
     * @var string Indicates that the module is offer type.
     */
    const OFFER = 'OFFER';

    /**
     * @var string Indicates that the module is offer catalog type.
     */
    const OFFER_CATALOG = 'OFFER_CATALOG';

    /**
     * @var string Indicates that the module is order type.
     */
    const ORDER = 'ORDER';

    /**
     * @var string Indicates that the module is order report type.
     */
    const ORDER_REPORT = 'ORDER_REPORT';

    /**
     * @var string Indicates that the module is partner type.
     */
    const PARTNER = 'PARTNER';

    /**
     * @var string Indicates that the module is partnership management type.
     */
    const PARTNERSHIP_MANAGEMENT = 'PARTNERSHIP_MANAGEMENT';

    /**
     * @var string Indicates that the module is partnership portal type.
     */
    const PARTNERSHIP_PORTAL = 'PARTNERSHIP_PORTAL';

    /**
     * @var string Indicates that the module is point credits action report type.
     */
    const POINT_CREDITS_ACTION_REPORT = 'POINT_CREDITS_ACTION_REPORT';

    /**
     * @var string Indicates that the module is profile type.
     */
    const PROFILE = 'PROFILE';

    /**
     * @var string Indicates that the module is promotion configuration type.
     */
    const PROMOTION_CONFIGURATION = 'PROMOTION_CONFIGURATION';

    /**
     * @var string Indicates that the module is promotion management type.
     */
    const PROMOTION_MANAGEMENT = 'PROMOTION_MANAGEMENT';

    /**
     * @var string Indicates that the module is promotion type.
     */
    const PROMOTION = 'PROMOTION';

    /**
     * @var string Indicates that the module is quotation type.
     */
    const QUOTATION = 'QUOTATION';

    /**
     * @var string Indicates that the module is push notification type.
     */
    const PUSH_NOTIFICATION = 'PUSH_NOTIFICATION';

    /**
     * @var string Indicates that the module is push notification configuration type.
     */
    const PUSH_NOTIFICATION_CONFIGURATION = 'PUSH_NOTIFICATION_CONFIGURATION';

    /**
     * @var string Indicates that the module is quotation or contract type.
     */
    const QUOTATION_CONTRACT = 'QUOTATION_CONTRACT';

    /**
     * @var string Indicates that the module is quotation configuration type.
     */
    const QUOTATION_CONFIGURATION = 'QUOTATION_CONFIGURATION';

    /**
     * @var string Indicates that the module is quotation management type.
     */
    const QUOTATION_MANAGEMENT = 'QUOTATION_MANAGEMENT';

    /**
     * @var string Indicates that the module is report type.
     */
    const REPORT = 'REPORT';

    /**
     * @var string Indicates that the module is role type.
     */
    const ROLE = 'ROLE';

    /**
     * @var string Indicates that the module is sales type.
     */
    const SALES = 'SALES';

    /**
     * @var string Indicates that the module is sales report type.
     */
    const SALES_REPORT = 'SALES_REPORT';

    /**
     * @var string Indicates that the module is service report type.
     */
    const SERVICE_REPORT = 'SERVICE_REPORT';

    /**
     * @var string Indicates that the module is services type.
     */
    const SERVICES = 'SERVICES';

    /**
     * @var string Indicates that the module is SMS feedback management type.
     */
    const SMS_FEEDBACK_MANAGEMENT = 'SMS_FEEDBACK_MANAGEMENT';

    /**
     * @var string Indicates that the module is SMS feedback report type.
     */
    const SMS_FEEDBACK_REPORT = 'SMS_FEEDBACK_REPORT';

    /**
     * @var string Indicates that the module is source list type.
     */
    const SOURCE_LIST = 'SOURCE_LIST';

    /**
     * @var string Indicates that the module is source unsubscription list type.
     */
    const SOURCE_UNSUBSCRIPTION_LIST = 'SOURCE_UNSUBSCRIPTION_LIST';

    /**
     * @var string Indicates that the module is tariff rate type type.
     */
    const TARIFF_RATE = 'TARIFF_RATE';

    /**
     * @var string Indicates that the module is tariff rate management type.
     */
    const TARIFF_RATE_MANAGEMENT = 'TARIFF_RATE_MANAGEMENT';

    /**
     * @var string Indicates that the module is ticket type.
     */
    const TICKET = 'TICKET';

    /**
     * @var string Indicates that the module is ticket configuration type.
     */
    const TICKET_CONFIGURATION = 'TICKET_CONFIGURATION';

    /**
     * @var string Indicates that the module is ticket management type.
     */
    const TICKET_MANAGEMENT = 'TICKET_MANAGEMENT';

    /**
     * @var string Indicates that the module is ticket report type.
     */
    const TICKET_REPORT = 'TICKET_REPORT';

    /**
     * @var string Indicates that the module is update credits action type.
     */
    const UPDATE_CREDITS_ACTION = 'UPDATE_CREDITS_ACTION';

    /**
     * @var string Indicates that the module is user type.
     */
    const USER = 'USER';

    /**
     * @var string Indicates that the module is user management type.
     */
    const USER_MANAGEMENT = 'USER_MANAGEMENT';

    /**
     * @var string Indicates that the module is user report type.
     */
    const USER_REPORT = 'USER_REPORT';
}
