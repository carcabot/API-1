<?php

declare(strict_types=1);

namespace App\WebService\PaymentGateway;

use App\Entity\Contract;
use App\Entity\Payment;
use App\Enum\PaymentMode;

class DummyClient implements ClientInterface
{
    public function getPaymentUrl(Payment $payment, Contract $contract, PaymentMode $paymentMode = null)
    {
        return null;
    }
}
