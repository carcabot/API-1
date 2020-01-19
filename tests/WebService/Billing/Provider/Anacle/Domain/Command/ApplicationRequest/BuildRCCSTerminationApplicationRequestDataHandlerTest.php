<?php

declare(strict_types=1);
/**
 * Created by PhpStorm.
 * User: mufeer
 * Date: 15/4/19
 * Time: 5:02 PM.
 */

namespace App\Tests\WebService\Billing\Provider\Anacle\Domain\Command\ApplicationRequest;

use App\Entity\ApplicationRequest;
use App\Entity\Contract;
use App\WebService\Billing\Provider\Anacle\Domain\Command\ApplicationRequest\BuildRCCSTerminationApplicationRequestData;
use App\WebService\Billing\Provider\Anacle\Domain\Command\ApplicationRequest\BuildRCCSTerminationApplicationRequestDataHandler;
use PHPUnit\Framework\TestCase;

class BuildRCCSTerminationApplicationRequestDataHandlerTest extends TestCase
{
    public function testRCCSTerminationDataHandlerDefault()
    {
        $now = new \DateTime();
        $timezone = new \DateTimeZone('Asia/Singapore');
        $now->setTimezone($timezone);

        $contractProphecy = $this->prophesize(Contract::class);
        $contractProphecy->getContractNumber()->willReturn('SWCC123456');
        $contractProphecy = $contractProphecy->reveal();

        $applicationRequestProphecy = $this->prophesize(ApplicationRequest::class);
        $applicationRequestProphecy->getApplicationRequestNumber()->willReturn('SWAP123456');
        $applicationRequestProphecy->getContract()->willReturn($contractProphecy);
        $applicationRequestProphecy->getTerminationDate()->willReturn($now);

        $applicationRequest = $applicationRequestProphecy->reveal();

        $expectedRccsTerminationData = [
            'CRMRCCSTerminationRequestNumber' => 'SWAP123456',
            'FRCContractNumber' => 'SWCC123456',
            'TerminationDate' => $now->format('Y-m-d\TH:i:s'),
        ];

        $rccsTerminationApplicationRequestData = new BuildRCCSTerminationApplicationRequestData($applicationRequest);

        $buildRccsTerminationApplicationRequestDataHandler = new BuildRCCSTerminationApplicationRequestDataHandler();

        $actualRccsTerminationData = $buildRccsTerminationApplicationRequestDataHandler->handle($rccsTerminationApplicationRequestData);

        $this->assertEquals($expectedRccsTerminationData, $actualRccsTerminationData);
    }
}
