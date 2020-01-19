<?php

declare(strict_types=1);

namespace App\Model;

use App\Entity\ApplicationRequest;
use App\Enum\ApplicationRequestStatus;
use App\Enum\ApplicationRequestType;

class ApplicationRequestNumberGenerator
{
    const LENGTH = 9;
    const PREFIX = 'A';
    const ACC_CLOSURE_PREFIX = 'APAC';
    const CONTRACT_RENEWAL_PREFIX = 'APCR';
    const DRAFT_PREFIX = 'APDFT';
    const DRAFT_LENGTH = 5;
    const GIRO_TERMINATION_PREFIX = 'APGT';
    const RCCS_TERMINATION_PREFIX = 'APRT';
    const OTHER_TYPE_LENGTH = 6;
    const TRANSFER_OUT_PREFIX = 'APTO';
    const TYPE = 'application_request';

    /**
     * @var RunningNumberGenerator
     */
    private $runningNumberGenerator;

    /**
     * @var array|null
     */
    private $runningNumberParameters;

    /**
     * @var \DateTimeZone
     */
    private $timezone;

    /**
     * @param RunningNumberGenerator $runningNumberGenerator
     * @param array|null             $runningNumberParameters
     * @param string|null            $timezone
     */
    public function __construct(RunningNumberGenerator $runningNumberGenerator, ?array $runningNumberParameters = null, ?string $timezone = null)
    {
        $this->runningNumberGenerator = $runningNumberGenerator;
        $this->runningNumberParameters = $runningNumberParameters;
        if (null !== $timezone) {
            $this->timezone = new \DateTimeZone($timezone);
        }
    }

    /**
     * Generates a application request number.
     *
     * @param ApplicationRequest $applicationRequest
     *
     * @return string
     */
    public function generate(ApplicationRequest $applicationRequest)
    {
        $type = self::TYPE;
        $length = self::LENGTH;
        $prefix = self::PREFIX;
        $series = $length;
        $prefixDateSuffix = '';

        if (\in_array($applicationRequest->getStatus()->getValue(),
            [
                ApplicationRequestStatus::DRAFT,
                ApplicationRequestStatus::PARTNER_DRAFT,
                ApplicationRequestStatus::PENDING,
            ], true
        )) {
            $length = self::DRAFT_LENGTH;
            $prefix = self::DRAFT_PREFIX;
            $series = $length;
        } else {
            $applicationRequestType = $applicationRequest->getType()->getValue();
            if (ApplicationRequestType::ACCOUNT_CLOSURE === $applicationRequestType) {
                $type = ApplicationRequestType::ACCOUNT_CLOSURE;
                if (!empty($this->runningNumberParameters['account_closure_prefix'])) {
                    $prefix = $this->runningNumberParameters['account_closure_prefix'];
                } else {
                    $prefix = self::ACC_CLOSURE_PREFIX;
                }
            }

            if (ApplicationRequestType::CONTRACT_APPLICATION === $applicationRequestType) {
                if (!empty($this->runningNumberParameters['contract_application_prefix'])) {
                    $type = ApplicationRequestType::CONTRACT_APPLICATION;
                    $prefix = $this->runningNumberParameters['contract_application_prefix'];
                } else {
                    $type = self::TYPE;
                    $prefix = self::PREFIX;
                }
            }

            if (ApplicationRequestType::CONTRACT_RENEWAL === $applicationRequestType) {
                $type = ApplicationRequestType::CONTRACT_RENEWAL;
                if (!empty($this->runningNumberParameters['contract_renewal_prefix'])) {
                    $prefix = $this->runningNumberParameters['contract_renewal_prefix'];
                } else {
                    $prefix = self::CONTRACT_RENEWAL_PREFIX;
                }
            }

            if (ApplicationRequestType::GIRO_TERMINATION === $applicationRequestType) {
                $type = ApplicationRequestType::GIRO_TERMINATION;
                if (!empty($this->runningNumberParameters['giro_termination_prefix'])) {
                    $prefix = $this->runningNumberParameters['giro_termination_prefix'];
                } else {
                    $prefix = self::GIRO_TERMINATION_PREFIX;
                }
            }

            if (ApplicationRequestType::RCCS_TERMINATION === $applicationRequestType) {
                $type = ApplicationRequestType::RCCS_TERMINATION;
                if (!empty($this->runningNumberParameters['rccs_termination_prefix'])) {
                    $prefix = $this->runningNumberParameters['rccs_termination_prefix'];
                } else {
                    $prefix = self::RCCS_TERMINATION_PREFIX;
                }
            }

            if (ApplicationRequestType::TRANSFER_OUT === $applicationRequestType) {
                $type = ApplicationRequestType::TRANSFER_OUT;
                if (!empty($this->runningNumberParameters['transfer_out_prefix'])) {
                    $prefix = $this->runningNumberParameters['transfer_out_prefix'];
                } else {
                    $prefix = self::TRANSFER_OUT_PREFIX;
                }
            }

            if (!empty($this->runningNumberParameters['application_request_length'])) {
                $length = (int) $this->runningNumberParameters['application_request_length'];
            } else {
                if ('application_request' === $type) {
                    $length = self::LENGTH;
                    $series = $length;
                } else {
                    $length = self::OTHER_TYPE_LENGTH;
                    $series = $length;
                }
            }

            if (!empty($this->runningNumberParameters['application_request_series'])) {
                $series = $this->runningNumberParameters['application_request_series'];
                $now = new \DateTime();
                $now->setTimezone($this->timezone);
                $prefixDateSuffix = $now->format($series);
            }
        }

        if ($series === $length) {
            $numberPrefix = $prefix;
        } else {
            $numberPrefix = $prefix.$prefixDateSuffix;
            $length = $length - 4;
        }

        $nextNumber = $this->runningNumberGenerator->getNextNumber($type, (string) $series);
        $applicationRequestNumber = \sprintf('%s%s', $numberPrefix, \str_pad((string) $nextNumber, $length, '0', STR_PAD_LEFT));

        return $applicationRequestNumber;
    }
}
