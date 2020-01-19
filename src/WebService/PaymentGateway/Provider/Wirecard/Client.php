<?php

declare(strict_types=1);

namespace App\WebService\PaymentGateway\Provider\Wirecard;

use App\Entity\Contract;
use App\Entity\Payment;
use App\Enum\ApplicationRequestStatus;
use App\Enum\ApplicationRequestType;
use App\Enum\PaymentMode;
use App\WebService\PaymentGateway\ClientInterface;
use League\Tactician\CommandBus;
use League\Uri\Components\Query;
use League\Uri\Modifiers\MergeQuery;
use League\Uri\Schemes\Http as HttpUri;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

class Client implements ClientInterface
{
    /**
     * @var string
     */
    private $merchantId;

    /**
     * @var string
     */
    private $merchantRecurringId;

    /**
     * @var string
     */
    private $merchantSecret;

    /**
     * @var string|null
     */
    private $returnUrl = null;

    /**
     * @var string|null
     */
    private $statusUrl = null;

    /**
     * @var \DateTimeZone
     */
    private $timezone;

    /**
     * @var CommandBus
     */
    private $commandBus;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var HttpUri
     */
    private $baseUri;

    /**
     * @var string
     */
    private $profile;

    public function __construct(array $config, \DateTimeZone $timezone, CommandBus $commandBus, LoggerInterface $logger)
    {
        $this->merchantId = $config['merchant_id'];
        $this->merchantRecurringId = $config['merchant_recurring_id'];
        $this->merchantSecret = $config['merchant_secret'];

        if (!empty($config['return_url'])) {
            $this->returnUrl = $config['return_url'];
        }

        if (!empty($config['status_url'])) {
            $this->statusUrl = $config['status_url'];
        }

        $this->timezone = $timezone;
        $this->commandBus = $commandBus;
        $this->logger = $logger;
        $this->baseUri = HttpUri::createFromString($config['merchant_url']);
        $this->profile = $config['profile'];
    }

    public function getPaymentUrl(Payment $payment, Contract $contract, PaymentMode $paymentMode = null)
    {
        $now = new \DateTime();
        $now->setTimezone($this->timezone);

        $timeout = $now->modify('+5 minutes')->format('Y-m-d-H:i:s');

        $parameters = [
            'mid' => $this->merchantId,
            'ref' => $payment->getPaymentNumber(),
            'cur' => $payment->getAmount()->getCurrency(),
            'amt' => \number_format((float) $payment->getAmount()->getValue(), 2, '.', ''),
            'transtype' => 'sale',
            'rcard' => '04',
            'version' => 2,
            'validity' => $timeout,
            'userfield1' => $contract->getContractNumber(),
            'userfield2' => $contract->getMsslAccountNumber(),
            'userfield3' => $contract->getEbsAccountNumber(),
        ];

        if (null !== $this->returnUrl) {
            $parameters['returnurl'] = $this->returnUrl;
        }

        if (null !== $this->statusUrl) {
            $parameters['statusurl'] = $this->statusUrl;
        }

        if ('iswitch' === $this->profile) {
            $applicationRequest = $this->getApplicationRequest($contract);
            $isRCCS = false;
            if (null !== $contract->getPaymentMode() && PaymentMode::MANUAL !== $contract->getPaymentMode()->getValue()) {
                if (PaymentMode::RCCS === $contract->getPaymentMode()->getValue()) {
                    throw new BadRequestHttpException('RCCS is activated.');
                }
            } else {
                if (null !== $paymentMode) {
                    if (PaymentMode::RCCS === $paymentMode->getValue()) {
                        $isRCCS = true;
                    }
                } else {
                    if (null !== $applicationRequest && null !== $applicationRequest->getPaymentMode() && PaymentMode::RCCS === $applicationRequest->getPaymentMode()->getValue()) {
                        $isRCCS = true;
                    }
                }
            }

            if ($isRCCS) {
                $parameters['recurrentid'] = 'INIT';
                $parameters['subsequentmid'] = !empty($this->merchantRecurringId) ? $this->merchantRecurringId : $this->merchantId;
                $parameters['userfield1'] = '';
                $parameters['userfield2'] = $contract->getContractNumber();
                $parameters['userfield3'] = '';
            } else {
                $parameters['userfield1'] = $contract->getContractNumber();
                $parameters['userfield2'] = '';
                $parameters['userfield3'] = '';
            }
        } else {
            if (true === $contract->isRecurringOption()) {
                $parameters['recurrentid'] = 'INIT';
                $parameters['subsequentmid'] = $this->merchantId;
            }
        }

        if (null !== $payment->getInvoiceNumber()) {
            $parameters['userfield4'] = $payment->getInvoiceNumber();
        }

        $securitySequence = \sprintf('%s%s%s%s%s%s', $parameters['amt'], $parameters['ref'], $parameters['cur'], $parameters['mid'], $parameters['transtype'], $this->merchantSecret);

        $parameters['signature'] = \hash('sha512', $securitySequence);

        $query = Query::createFromParams($parameters);
        $modifier = new MergeQuery($query->__toString());
        $uri = $modifier->process($this->baseUri);

        return $uri->__toString();
    }

    protected function getApplicationRequest(Contract $contract)
    {
        $applicationRequests = $contract->getCustomer()->getApplicationRequests();
        $appRequest = null;

        if (\count($applicationRequests) > 0) {
            $date = $applicationRequests[0]->getDateModified();
            foreach ($applicationRequests as $applicationRequest) {
                if (ApplicationRequestType::CONTRACT_APPLICATION === $applicationRequest->getType()->getValue()
                    && null !== $applicationRequest->getContract() && $applicationRequest->getContract()->getContractNumber() === $contract->getContractNumber()
                    && $applicationRequest->getDateModified() >= $date && ApplicationRequestStatus::COMPLETED === $applicationRequest->getStatus()->getValue()
                ) {
                    $appRequest = $applicationRequest;
                    $date = $applicationRequest->getDateModified();
                }
            }
        }

        return $appRequest;
    }
}
