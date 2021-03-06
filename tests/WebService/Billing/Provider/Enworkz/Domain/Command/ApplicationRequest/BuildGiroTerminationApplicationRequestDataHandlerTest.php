<?php

declare(strict_types=1);
/**
 * Created by PhpStorm.
 * User: mufeer
 * Date: 16/4/19
 * Time: 6:40 PM.
 */

namespace App\Tests\WebService\Billing\Provider\Enworkz\Domain\Command\ApplicationRequest;

use App\Entity\ApplicationRequest;
use App\Entity\Contract;
use App\WebService\Billing\Provider\Enworkz\Domain\Command\ApplicationRequest\BuildGiroTerminationApplicationRequestData;
use App\WebService\Billing\Provider\Enworkz\Domain\Command\ApplicationRequest\BuildGiroTerminationApplicationRequestDataHandler;
use PHPUnit\Framework\TestCase;

class BuildGiroTerminationApplicationRequestDataHandlerTest extends TestCase
{
    public function testGiroTerminationDataHandlerDefault()
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

        $expectedGiroTerminationData = [
            'CRMGIROTerminationRequestNumber' => 'SWAP123456',
            'FRCContractNumber' => 'SWCC123456',
            'TerminationDate' => $now->format('Y-m-d'),
        ];

        $giroTerminationApplicationRequestData = new BuildGiroTerminationApplicationRequestData($applicationRequest);

        $buildGiroTerminationApplicationRequestDataHandler = new BuildGiroTerminationApplicationRequestDataHandler();

        $actualGiroTerminationData = $buildGiroTerminationApplicationRequestDataHandler->handle($giroTerminationApplicationRequestData);

        $this->assertEquals($expectedGiroTerminationData, $actualGiroTerminationData);
    }
}
