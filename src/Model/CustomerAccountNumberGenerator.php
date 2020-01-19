<?php

declare(strict_types=1);

namespace App\Model;

use App\Entity\CustomerAccount;
use App\Enum\AccountCategory;

class CustomerAccountNumberGenerator
{
    const LENGTH = 8;
    const CUSTOMER_PREFIX = 'C-';
    const CUSTOMER_TYPE = 'customer_account';
    const USER_PREFIX = 'U-';
    const USER_TYPE = 'user_account';
    const PARTNER_PREFIX = 'P-';
    const PARTNER_TYPE = 'partner';
    const PARTNER_CONTACT_PERSON_PREFIX = 'PC-';
    const PARTNER_CONTACT_PERSON_TYPE = 'partner_contact_person';

    /**
     * @var \DateTimeZone
     */
    private $timezone;

    /**
     * @var array
     */
    private $runningNumberParameters;

    /**
     * @var RunningNumberGenerator
     */
    private $runningNumberGenerator;

    /**
     * @param string                 $timezone
     * @param array                  $runningNumberParameters
     * @param RunningNumberGenerator $runningNumberGenerator
     */
    public function __construct(string $timezone, array $runningNumberParameters, RunningNumberGenerator $runningNumberGenerator)
    {
        $this->timezone = new \DateTimeZone($timezone);
        $this->runningNumberParameters = $runningNumberParameters;
        $this->runningNumberGenerator = $runningNumberGenerator;
    }

    /**
     * Generates a customer account number.
     *
     * @param CustomerAccount $customerAccount
     *
     * @return string|null
     */
    public function generate(CustomerAccount $customerAccount)
    {
        $prefix = self::USER_PREFIX;
        $prefixDateSuffix = '';
        $length = self::LENGTH;
        $series = $length;

        $accountCategories = $customerAccount->getCategories();

        if (0 !== \count($accountCategories)) {
            if (!empty($this->runningNumberParameters['customer_account_prefix'])) {
                $prefix = $this->runningNumberParameters['customer_account_prefix'];
            } else {
                $prefix = self::CUSTOMER_PREFIX;
            }

            if (!empty($this->runningNumberParameters['customer_account_length'])) {
                $length = (int) $this->runningNumberParameters['customer_account_length'];
            }

            if (!empty($this->runningNumberParameters['customer_account_series'])) {
                $series = $this->runningNumberParameters['customer_account_series'];
                $now = new \DateTime();
                $now->setTimezone($this->timezone);
                $prefixDateSuffix = $now->format($series);
            }

            // hack for shit requirement
            if (\in_array(AccountCategory::SALES_REPRESENTATIVE, $accountCategories, true)) {
                return null;
            }

            if (\in_array(AccountCategory::PARTNER, $accountCategories, true)) {
                if (!empty($this->runningNumberParameters['partner_number_prefix'])) {
                    $prefix = $this->runningNumberParameters['partner_number_prefix'];
                } else {
                    $prefix = self::PARTNER_PREFIX;
                }

                if (!empty($this->runningNumberParameters['partner_number_length'])) {
                    $length = (int) $this->runningNumberParameters['partner_number_length'];
                }

                if (!empty($this->runningNumberParameters['partner_number_series'])) {
                    $series = $this->runningNumberParameters['partner_number_series'];
                    $now = new \DateTime();
                    $now->setTimezone($this->timezone);
                    $prefixDateSuffix = $now->format($series);
                }
            }

            // hack for shit requirement
            if (\in_array(AccountCategory::PARTNER_CONTACT_PERSON, $accountCategories, true)) {
                $prefix = self::PARTNER_CONTACT_PERSON_PREFIX;

                if (!empty($this->runningNumberParameters['partner_number_length'])) {
                    $length = (int) $this->runningNumberParameters['partner_number_length'];
                }

                if (!empty($this->runningNumberParameters['partner_number_series'])) {
                    $series = $this->runningNumberParameters['partner_number_series'];
                    $now = new \DateTime();
                    $now->setTimezone($this->timezone);
                    $prefixDateSuffix = $now->format($series);
                }
            }
        }

        if ($series === $length) {
            $numberPrefix = $prefix;
        } else {
            $numberPrefix = $prefix.$prefixDateSuffix;
        }

        $nextNumber = $this->runningNumberGenerator->getNextNumber($prefix, (string) $series);
        $customerAccountNumber = \sprintf('%s%s', $numberPrefix, \str_pad((string) $nextNumber, $length, '0', STR_PAD_LEFT));

        return $customerAccountNumber;
    }
}
