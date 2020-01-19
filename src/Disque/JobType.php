<?php

declare(strict_types=1);

namespace App\Disque;

class JobType
{
    const ADMIN_NOTIFICATION = 'admin:notification';

    const AFFILIATE_PROGRAM_PROCESS_FETCH_TRANSACTION = 'affiliate-program:process:fetch-transaction';
    const AFFILIATE_PROGRAM_QUEUE_FETCH_TRANSACTION = 'affiliate-program:queue:fetch-transaction';
    const AFFILIATE_PROGRAM_GENERATE_URL = 'affiliate-program:generate:url';
    const AFFILIATE_PROGRAM_GENERATED_URL = 'affiliate-program:generated:url';

    const APPLICATION_REQUEST_CANCELLED = 'application-request:cancelled:application-request';
    const APPLICATION_REQUEST_COMPLETED = 'application-request:completed:application-request';
    const APPLICATION_REQUEST_REJECTED = 'application-request:rejected:application-request';
    const APPLICATION_REQUEST_SUBMIT = 'application-request:submit:application-request';
    const APPLICATION_REQUEST_SUBMITTED = 'application-request:submitted:application-request';
    const APPLICATION_REQUEST_SUBMITTED_PENDING_AUTHORIZATION = 'application-request:submitted:pending-authorization';
    const APPLICATION_REQUEST_NOTIFY_AUTHORIZATION_URL = 'application-request:notify:authorization-url';
    const APPLICATION_REQUEST_SCHEDULE_NOTIFY_AUTHORIZATION_URL = 'application-request:schedule:notify-authorization-url';
    const APPLICATION_REQUEST_EXPIRE_AUTHORIZATION_URL = 'application-request:expire:authorization-url';
    const APPLICATION_REQUEST_SCHEDULE_EXPIRE_AUTHORIZATION_URL = 'application-request:schedule:expire-authorization-url';
    const APPLICATION_REQUEST_REPORT_GENERATE = 'application-request:generate:report';
    const APPLICATION_REQUEST_UPDATE_CACHE_TABLE = 'application-request:update:cache-table';

    const CONTRACT_UPDATE_CACHE_TABLE = 'contract:update:cache-table';
    const CONTRACT_UPDATE_PAYMENT_MODE = 'contract:update:payment-mode';

    const CAMPAIGN_END = 'campaign:end:campaign';
    const CAMPAIGN_EXECUTE = 'campaign:execute:campaign';
    const CAMPAIGN_EXECUTE_SCHEDULE = 'campaign:execute:schedule';
    const CLEAN_CONTRACT_DATA = 'contract:clean:data';
    const CLEAN_CUSTOMER_CONTACT_POINT = 'customer:clean:contact-point';
    const CLEAN_CUSTOMER_DATA = 'customer:clean:data';

    const CONTRACT_END_NOTIFY_TEN_DAYS_NOTICE = 'contract:notify:ten-days-notice';
    const CONTRACT_END_NOTIFY_THREE_DAYS_NOTICE = 'contract:notify:three-days-notice';

    const CRON_CHECK_APPLICATION_REQUEST_STATUS_HISTORY = 'cron:check:application-request-status-history';
    const CRON_DOWNLOAD_CONTRACT_APPLICATION_RETURN = 'cron:download:contract-application-return';
    const CRON_GENERATE_PARTNER_CONTRACT_APPLICATION_REPORT = 'cron:generate:partner-contract-application-report';
    const CRON_ORDER_BILL_REBATE_SUBMIT = 'cron:submit:order-bill-rebate';
    const CRON_PARTNER_GENERATE_COMMISSION_STATEMENT = 'cron:generate:partner-commission-statement';
    const CRON_PROCESS_ACCOUNT_CLOSURE_APPLICATION = 'cron:process:account-closure-application';
    const CRON_PROCESS_CONTRACT_APPLICATION = 'cron:process:contract-application-request';
    const CRON_PROCESS_CONTRACT_RENEWAL_APPLICATION = 'cron:process:contract-renewal-application-request';
    const CRON_PROCESS_CUSTOMER_ACCOUNT_BLACKLIST_UPDATE = 'cron:process:customer-account-update-blackList';
    const CRON_PROCESS_CREDITS_WITHDRAWAL_RETURN = 'cron:process:credits-withdrawal-return';
    const CRON_PROCESS_EVENT_ACTIVITY = 'cron:process:event-activity';
    const CRON_PROCESS_MASS_ACCOUNT_CLOSURE = 'cron:process:mass-account-closure';
    const CRON_PROCESS_MASS_CONTRACT_APPLICATION = 'cron:process:mass-contract-application-request';
    const CRON_PROCESS_MASS_CONTRACT_RENEWAL_APPLICATION = 'cron:process:mass-contract-renewal-application-request';
    const CRON_PROCESS_MASS_TRANSFER_OUT = 'cron:process:mass-transfer-out';
    const CRON_PROCESS_RCCS_TERMINATION = 'cron:process:rccs-termination';
    const CRON_PROCESS_TARIFF_RATE_RECONCILIATION = 'cron:process:tariff-rate-reconciliation';
    const CRON_PROCESS_TRANSFER_OUT_APPLICATION = 'cron:process:transfer-out-application';
    const CRON_SCHEDULE_UPDATE_MAINTENANCE_STATUS = 'cron:schedule:update-maintenance-status';
    const SCHEDULE_RECURRING_CAMPAIGN_JOBS = 'campaign:schedule:recurring-campaign-jobs';
    const CRON_UPDATE_MAINTENANCE_STATUS = 'cron:update:maintenance-status';
    const CRON_UPLOAD_CONTRACT_APPLICATION_RECONCILIATION = 'cron:upload:contract-application-reconciliation';
    const CRON_UPLOAD_CONTRACT_APPLICATION_RECONCILIATION_LEFTOVER = 'cron:upload:contract-application-reconciliation-leftover';
    const CRON_UPLOAD_RCCS_TERMINATION_APPLICATION_RECONCILIATION = 'cron:upload:rccs-termination-application-reconciliation';

    const CUSTOMER_ACCOUNT_EMAIL_ACTIVITY_CREATED = 'customer-account:created:email-activity';
    const CUSTOMER_ACCOUNT_SMS_CUSTOMER_SERVICE_FEEDBACK_ACTIVITY_CREATED = 'customer-account:created:sms-customer-service-feedback-activity';
    const CONTRACT_REPORT_GENERATE = 'contract:generate:report';
    const CREDITS_ACTION_REPORT_GENERATE = 'credits-action:generate:report';
    const CUSTOMER_ACCOUNT_RELATIONSHIP_REPORT_GENERATE = 'customer-account-relationship:generate:report';
    const CUSTOMER_ACCOUNT_REPORT_GENERATE = 'customer-account:generate:report';
    const CUSTOMER_ACCOUNT_CONTACT_UPDATE = 'customer-account:update:contact';
    const CUSTOMER_PORTAL_DISABLE = 'customer:disable:portal';
    const CUSTOMER_PORTAL_ENABLED_UPDATE = 'customer:update:customer-portal-enabled';
    const FIX_DAMN_BRIDGE = 'fix:damn:bridge';

    const LEAD_ASSIGNED_ASSIGNEE = 'lead:assigned:assignee';
    const LEAD_CONVERT_STATUS = 'lead:convert:status';
    const LEAD_EMAIL_ACTIVITY_CREATED = 'lead:created:email-activity';
    const LEAD_SMS_CUSTOMER_SERVICE_FEEDBACK_ACTIVITY_CREATED = 'lead:created:sms-customer-service-feedback-activity';
    const LEAD_REPORT_GENERATE = 'lead:generate:report';
    const EMAIL_CAMPAIGN_EXECUTED = 'campaign:executed:email-campaign';
    const EMAIL_CAMPAIGN_SCHEDULED = 'campaign:scheduled:email-campaign';
    const GENERATE_PARTNER_CONTRACT_APPLICATION_REPORT = 'application-request:generate:partner-contract-application-report';

    const MESSAGE_END = 'message:end:message';
    const MESSAGE_EXECUTE = 'message:execute:message';
    const MESSAGE_EXECUTE_SCHEDULE = 'message:execute:schedule';
    const MIGRATE_CONTRACT_ACTION = 'migrate:contract:action';

    const ORDER_REPORT_GENERATE = 'order:generate:report';
    const PARTNER_GENERATE_COMMISSION_STATEMENT = 'partner:generate:commission-statement';
    const PARTNER_GENERATED_COMMISSION_STATEMENT = 'partner:generated:commission-statement';
    const PARTNER_GENERATED_CONTRACT_APPLICATION_REPORT = 'partner:generated:contract-application-report';

    const QUOTATION_SENT = 'quotation:sent:quotation';
    const QUOTATION_UPDATE_FILE = 'quotation:update:file';

    const USER_GENERATED_TWO_FACTOR_AUTHENTICATION_CODE = 'user:generated:two-factor-authentication-code';

    const TICKET_CREATED = 'ticket:created:ticket';
    const TICKET_EMAIL_ACTIVITY_CREATED = 'ticket:created:email-activity';
    const TICKET_REPORT_GENERATE = 'ticket:generate:report';

    const UPDATE_REFERRAL_EARNING_INSTRUMENT = 'earn-contract-credits-action:update:instrument';

    const USER_CREATED = 'user:created:user';
    const USER_PASSWORD_RESET_REQUESTED = 'user:password-reset-requested:user';
    const USER_SIGNED_UP = 'user:signed-up:user';
    const USER_REPORT_GENERATE = 'user:generate:report';

    const WITHDRAW_CREDITS_ACTION_SUBMIT = 'withdraw-credits-action:submit:withdraw-credits-action';

    const SCHEDULE_CAMPAIGN_JOBS = 'campaign:schedule:jobs';
    const SCHEDULE_CUSTOMER_PORTAL_DISABLE = 'customer:schedule:customer-portal-disable';
    const SCHEDULE_MESSAGE_JOBS = 'message:schedule:jobs';
    const CONTRACT_END_NOTIFY = 'contract:notify:end';

    const MOTHER_OF_JOBS = 'whyidoeverything';
    const MOTHER_OF_CAMPAIGN = 'campaign:whyidoeverything';
    const MOTHER_OF_APPLICATION_REQUEST = 'application-request:whyidoeverything';
    const MOTHER_OF_CONTRACT = 'contract:whyidoeverything';
    const MOTHER_OF_MESSAGE = 'message:whyidoeverything';
}
