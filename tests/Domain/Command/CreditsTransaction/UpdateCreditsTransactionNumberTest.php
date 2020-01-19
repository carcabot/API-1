<?php

declare(strict_types=1);

namespace App\Tests\Domain\Command\CreditsTransaction;

use App\Domain\Command\CreditsTransaction\UpdateCreditsTransactionNumber;
use App\Domain\Command\CreditsTransaction\UpdateCreditsTransactionNumberHandler;
use App\Entity\CreditsTransaction;
use App\Model\CreditsTransactionNumberGenerator;
use PHPUnit\Framework\TestCase;

class UpdateCreditsTransactionNumberTest extends TestCase
{
    public function testUpdateCreditsTransactionNumber()
    {
        $length = 8;
        $prefix = 'TX';
        $number = 1;

        $creditsTransactionProphecy = $this->prophesize(CreditsTransaction::class);
        $creditsTransactionProphecy->setCreditsTransactionNumber('TX00000001')->shouldBeCalled();
        $creditsTransaction = $creditsTransactionProphecy->reveal();

        $creditsTransactionNumberGeneratorProphecy = $this->prophesize(CreditsTransactionNumberGenerator::class);
        $creditsTransactionNumberGeneratorProphecy->generate($creditsTransaction)->willReturn(\sprintf('%s%s', $prefix, \str_pad((string) $number, $length, '0', STR_PAD_LEFT)));
        $creditsTransactionNumberGenerator = $creditsTransactionNumberGeneratorProphecy->reveal();

        $updateCreditsTransactionNumberHandler = new UpdateCreditsTransactionNumberHandler($creditsTransactionNumberGenerator);
        $updateCreditsTransactionNumberHandler->handle(new UpdateCreditsTransactionNumber($creditsTransaction));
    }
}
