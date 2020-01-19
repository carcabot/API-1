<?php

declare(strict_types=1);

namespace App\Tests\Model;

use App\Entity\ApplicationRequest;
use App\Enum\ApplicationRequestStatus;
use App\Enum\ApplicationRequestType;
use App\Model\ApplicationRequestNumberGenerator;
use App\Model\RunningNumberGenerator;
use PHPUnit\Framework\TestCase;

class ApplicationRequestNumberGeneratorTest extends TestCase
{
    public function testGenerateDefaultAccountClosureApplicationRequestNumber()
    {
        $length = 6;
        $prefix = 'APAC';
        $type = 'ACCOUNT_CLOSURE';

        $applicationRequestProphecy = $this->prophesize(ApplicationRequest::class);
        $applicationRequestProphecy->getStatus()->willReturn(new ApplicationRequestStatus(ApplicationRequestStatus::IN_PROGRESS));
        $applicationRequestProphecy->getType()->willReturn(new ApplicationRequestType(ApplicationRequestType::ACCOUNT_CLOSURE));
        $applicationRequest = $applicationRequestProphecy->reveal();

        $runningNumberGeneratorProphecy = $this->prophesize(RunningNumberGenerator::class);
        $runningNumberGeneratorProphecy->getNextNumber($type, (string) $length)->shouldBeCalled()->willReturn(1);
        $runningNumberGenerator = $runningNumberGeneratorProphecy->reveal();

        $applicationRequestNumberGenerator = new ApplicationRequestNumberGenerator($runningNumberGenerator);
        $applicationRequestNumber = $applicationRequestNumberGenerator->generate($applicationRequest);

        $this->assertEquals($applicationRequestNumber, 'APAC000001');
    }

    public function testGenerateDefaultApplicationRequestNumber()
    {
        $length = 9;
        $prefix = 'A';
        $type = 'application_request';

        $applicationRequestProphecy = $this->prophesize(ApplicationRequest::class);
        $applicationRequestProphecy->getStatus()->willReturn(new ApplicationRequestStatus(ApplicationRequestStatus::IN_PROGRESS));
        $applicationRequestProphecy->getType()->willReturn(new ApplicationRequestType(ApplicationRequestType::CONTRACT_APPLICATION));
        $applicationRequest = $applicationRequestProphecy->reveal();

        $runningNumberGeneratorProphecy = $this->prophesize(RunningNumberGenerator::class);
        $runningNumberGeneratorProphecy->getNextNumber($type, (string) $length)->shouldBeCalled()->willReturn(1);
        $runningNumberGenerator = $runningNumberGeneratorProphecy->reveal();

        $applicationRequestNumberGenerator = new ApplicationRequestNumberGenerator($runningNumberGenerator);
        $applicationRequestNumber = $applicationRequestNumberGenerator->generate($applicationRequest);

        $this->assertEquals($applicationRequestNumber, 'A000000001');
    }

    public function testGenerateDefaultContractRenewalApplicationRequestNumber()
    {
        $length = 6;
        $prefix = 'APCR';
        $type = 'CONTRACT_RENEWAL';

        $applicationRequestProphecy = $this->prophesize(ApplicationRequest::class);
        $applicationRequestProphecy->getStatus()->willReturn(new ApplicationRequestStatus(ApplicationRequestStatus::IN_PROGRESS));
        $applicationRequestProphecy->getType()->willReturn(new ApplicationRequestType(ApplicationRequestType::CONTRACT_RENEWAL));
        $applicationRequest = $applicationRequestProphecy->reveal();

        $runningNumberGeneratorProphecy = $this->prophesize(RunningNumberGenerator::class);
        $runningNumberGeneratorProphecy->getNextNumber($type, (string) $length)->shouldBeCalled()->willReturn(1);
        $runningNumberGenerator = $runningNumberGeneratorProphecy->reveal();

        $applicationRequestNumberGenerator = new ApplicationRequestNumberGenerator($runningNumberGenerator);
        $applicationRequestNumber = $applicationRequestNumberGenerator->generate($applicationRequest);

        $this->assertEquals($applicationRequestNumber, 'APCR000001');
    }

    public function testGenerateDefaultGiroTerminationApplicationRequestNumber()
    {
        $length = 6;
        $prefix = 'APGT';
        $type = 'GIRO_TERMINATION';

        $applicationRequestProphecy = $this->prophesize(ApplicationRequest::class);
        $applicationRequestProphecy->getStatus()->willReturn(new ApplicationRequestStatus(ApplicationRequestStatus::IN_PROGRESS));
        $applicationRequestProphecy->getType()->willReturn(new ApplicationRequestType(ApplicationRequestType::GIRO_TERMINATION));
        $applicationRequest = $applicationRequestProphecy->reveal();

        $runningNumberGeneratorProphecy = $this->prophesize(RunningNumberGenerator::class);
        $runningNumberGeneratorProphecy->getNextNumber($type, (string) $length)->shouldBeCalled()->willReturn(1);
        $runningNumberGenerator = $runningNumberGeneratorProphecy->reveal();

        $applicationRequestNumberGenerator = new ApplicationRequestNumberGenerator($runningNumberGenerator);
        $applicationRequestNumber = $applicationRequestNumberGenerator->generate($applicationRequest);

        $this->assertEquals($applicationRequestNumber, 'APGT000001');
    }

    public function testGenerateDefaultTransferOutApplicationRequestNumber()
    {
        $length = 6;
        $prefix = 'APTO';
        $type = 'TRANSFER_OUT';

        $applicationRequestProphecy = $this->prophesize(ApplicationRequest::class);
        $applicationRequestProphecy->getStatus()->willReturn(new ApplicationRequestStatus(ApplicationRequestStatus::IN_PROGRESS));
        $applicationRequestProphecy->getType()->willReturn(new ApplicationRequestType(ApplicationRequestType::TRANSFER_OUT));
        $applicationRequest = $applicationRequestProphecy->reveal();

        $runningNumberGeneratorProphecy = $this->prophesize(RunningNumberGenerator::class);
        $runningNumberGeneratorProphecy->getNextNumber($type, (string) $length)->shouldBeCalled()->willReturn(1);
        $runningNumberGenerator = $runningNumberGeneratorProphecy->reveal();

        $applicationRequestNumberGenerator = new ApplicationRequestNumberGenerator($runningNumberGenerator);
        $applicationRequestNumber = $applicationRequestNumberGenerator->generate($applicationRequest);

        $this->assertEquals($applicationRequestNumber, 'APTO000001');
    }

    public function testGenerateDraftApplicationRequestNumber()
    {
        $length = 5;
        $prefix = 'APDFT';
        $type = 'application_request';

        $applicationRequestProphecy = $this->prophesize(ApplicationRequest::class);
        $applicationRequestProphecy->getStatus()->willReturn(new ApplicationRequestStatus(ApplicationRequestStatus::DRAFT));
        $applicationRequest = $applicationRequestProphecy->reveal();

        $runningNumberGeneratorProphecy = $this->prophesize(RunningNumberGenerator::class);
        $runningNumberGeneratorProphecy->getNextNumber($type, (string) $length)->shouldBeCalled()->willReturn(1);
        $runningNumberGenerator = $runningNumberGeneratorProphecy->reveal();

        $applicationRequestNumberGenerator = new ApplicationRequestNumberGenerator($runningNumberGenerator);
        $applicationRequestNumber = $applicationRequestNumberGenerator->generate($applicationRequest);

        $this->assertEquals($applicationRequestNumber, 'APDFT00001');
    }

    public function testGenerateAccountClosureApplicationRequestNumber()
    {
        $timezone = 'Asia/Singapore';
        $now = new \DateTime();
        $now->setTimezone(new \DateTimeZone($timezone));
        $parameters = [
            'application_request_series' => 'ym',
            'application_request_length' => '10',
            'account_closure_prefix' => 'SWAC',
        ];
        $series = $parameters['application_request_series'];
        $type = 'ACCOUNT_CLOSURE';

        $applicationRequestProphecy = $this->prophesize(ApplicationRequest::class);
        $applicationRequestProphecy->getStatus()->willReturn(new ApplicationRequestStatus(ApplicationRequestStatus::IN_PROGRESS));
        $applicationRequestProphecy->getType()->willReturn(new ApplicationRequestType(ApplicationRequestType::ACCOUNT_CLOSURE));
        $applicationRequest = $applicationRequestProphecy->reveal();

        $runningNumberGeneratorProphecy = $this->prophesize(RunningNumberGenerator::class);
        $runningNumberGeneratorProphecy->getNextNumber($type, $series)->shouldBeCalled()->willReturn(1);
        $runningNumberGenerator = $runningNumberGeneratorProphecy->reveal();

        $applicationRequestNumberGenerator = new ApplicationRequestNumberGenerator($runningNumberGenerator, $parameters, $timezone);
        $applicationRequestNumber = $applicationRequestNumberGenerator->generate($applicationRequest);

        $prefixDateSuffix = $now->format($parameters['application_request_series']);
        $this->assertEquals($applicationRequestNumber, 'SWAC'.$prefixDateSuffix.'000001');
    }

    public function testGenerateContractApplicationApplicationRequestNumber()
    {
        $timezone = 'Asia/Singapore';
        $now = new \DateTime();
        $now->setTimezone(new \DateTimeZone($timezone));
        $parameters = [
            'application_request_series' => 'ym',
            'application_request_length' => '10',
            'contract_application_prefix' => 'SWAP',
        ];
        $series = $parameters['application_request_series'];
        $type = 'CONTRACT_APPLICATION';

        $applicationRequestProphecy = $this->prophesize(ApplicationRequest::class);
        $applicationRequestProphecy->getStatus()->willReturn(new ApplicationRequestStatus(ApplicationRequestStatus::IN_PROGRESS));
        $applicationRequestProphecy->getType()->willReturn(new ApplicationRequestType(ApplicationRequestType::CONTRACT_APPLICATION));
        $applicationRequest = $applicationRequestProphecy->reveal();

        $runningNumberGeneratorProphecy = $this->prophesize(RunningNumberGenerator::class);
        $runningNumberGeneratorProphecy->getNextNumber($type, $series)->shouldBeCalled()->willReturn(1);
        $runningNumberGenerator = $runningNumberGeneratorProphecy->reveal();

        $applicationRequestNumberGenerator = new ApplicationRequestNumberGenerator($runningNumberGenerator, $parameters, $timezone);
        $applicationRequestNumber = $applicationRequestNumberGenerator->generate($applicationRequest);
        $prefixDateSuffix = $now->format($parameters['application_request_series']);
        $this->assertEquals($applicationRequestNumber, 'SWAP'.$prefixDateSuffix.'000001');
    }

    public function testGenerateContractRenewalApplicationRequestNumber()
    {
        $timezone = 'Asia/Singapore';
        $now = new \DateTime();
        $now->setTimezone(new \DateTimeZone($timezone));
        $parameters = [
            'application_request_series' => 'ym',
            'application_request_length' => '10',
            'contract_renewal_prefix' => 'SWCR',
        ];
        $series = $parameters['application_request_series'];
        $type = 'CONTRACT_RENEWAL';

        $applicationRequestProphecy = $this->prophesize(ApplicationRequest::class);
        $applicationRequestProphecy->getStatus()->willReturn(new ApplicationRequestStatus(ApplicationRequestStatus::IN_PROGRESS));
        $applicationRequestProphecy->getType()->willReturn(new ApplicationRequestType(ApplicationRequestType::CONTRACT_RENEWAL));
        $applicationRequest = $applicationRequestProphecy->reveal();

        $runningNumberGeneratorProphecy = $this->prophesize(RunningNumberGenerator::class);
        $runningNumberGeneratorProphecy->getNextNumber($type, $series)->shouldBeCalled()->willReturn(1);
        $runningNumberGenerator = $runningNumberGeneratorProphecy->reveal();

        $applicationRequestNumberGenerator = new ApplicationRequestNumberGenerator($runningNumberGenerator, $parameters, $timezone);
        $applicationRequestNumber = $applicationRequestNumberGenerator->generate($applicationRequest);

        $prefixDateSuffix = $now->format($parameters['application_request_series']);
        $this->assertEquals($applicationRequestNumber, 'SWCR'.$prefixDateSuffix.'000001');
    }

    public function testGenerateGiroTerminationApplicationRequestNumber()
    {
        $timezone = 'Asia/Singapore';
        $now = new \DateTime();
        $now->setTimezone(new \DateTimeZone($timezone));
        $parameters = [
            'application_request_series' => 'ym',
            'application_request_length' => '10',
            'giro_termination_prefix' => 'SWGT',
        ];
        $series = $parameters['application_request_series'];
        $type = 'GIRO_TERMINATION';

        $applicationRequestProphecy = $this->prophesize(ApplicationRequest::class);
        $applicationRequestProphecy->getStatus()->willReturn(new ApplicationRequestStatus(ApplicationRequestStatus::IN_PROGRESS));
        $applicationRequestProphecy->getType()->willReturn(new ApplicationRequestType(ApplicationRequestType::GIRO_TERMINATION));
        $applicationRequest = $applicationRequestProphecy->reveal();

        $runningNumberGeneratorProphecy = $this->prophesize(RunningNumberGenerator::class);
        $runningNumberGeneratorProphecy->getNextNumber($type, $series)->shouldBeCalled()->willReturn(1);
        $runningNumberGenerator = $runningNumberGeneratorProphecy->reveal();

        $applicationRequestNumberGenerator = new ApplicationRequestNumberGenerator($runningNumberGenerator, $parameters, $timezone);
        $applicationRequestNumber = $applicationRequestNumberGenerator->generate($applicationRequest);

        $prefixDateSuffix = $now->format($parameters['application_request_series']);
        $this->assertEquals($applicationRequestNumber, 'SWGT'.$prefixDateSuffix.'000001');
    }

    public function testGenerateTransferOutApplicationRequestNumber()
    {
        $timezone = 'Asia/Singapore';
        $now = new \DateTime();
        $now->setTimezone(new \DateTimeZone($timezone));
        $parameters = [
            'application_request_series' => 'ym',
            'application_request_length' => '10',
            'transfer_out_prefix' => 'SWTB',
        ];
        $series = $parameters['application_request_series'];
        $type = 'TRANSFER_OUT';

        $applicationRequestProphecy = $this->prophesize(ApplicationRequest::class);
        $applicationRequestProphecy->getStatus()->willReturn(new ApplicationRequestStatus(ApplicationRequestStatus::IN_PROGRESS));
        $applicationRequestProphecy->getType()->willReturn(new ApplicationRequestType(ApplicationRequestType::TRANSFER_OUT));
        $applicationRequest = $applicationRequestProphecy->reveal();

        $runningNumberGeneratorProphecy = $this->prophesize(RunningNumberGenerator::class);
        $runningNumberGeneratorProphecy->getNextNumber($type, $series)->shouldBeCalled()->willReturn(1);
        $runningNumberGenerator = $runningNumberGeneratorProphecy->reveal();

        $applicationRequestNumberGenerator = new ApplicationRequestNumberGenerator($runningNumberGenerator, $parameters, $timezone);
        $applicationRequestNumber = $applicationRequestNumberGenerator->generate($applicationRequest);

        $prefixDateSuffix = $now->format('ym');
        $this->assertEquals($applicationRequestNumber, 'SWTB'.$prefixDateSuffix.'000001');
    }

    public function testGeneratePartnerDraftApplicationRequestNumber()
    {
        $length = 5;
        $prefix = 'APDFT';
        $type = 'application_request';

        $applicationRequestProphecy = $this->prophesize(ApplicationRequest::class);
        $applicationRequestProphecy->getStatus()->willReturn(new ApplicationRequestStatus(ApplicationRequestStatus::PARTNER_DRAFT));
        $applicationRequest = $applicationRequestProphecy->reveal();

        $runningNumberGeneratorProphecy = $this->prophesize(RunningNumberGenerator::class);
        $runningNumberGeneratorProphecy->getNextNumber($type, (string) $length)->shouldBeCalled()->willReturn(2);
        $runningNumberGenerator = $runningNumberGeneratorProphecy->reveal();

        $applicationRequestNumberGenerator = new ApplicationRequestNumberGenerator($runningNumberGenerator);
        $applicationRequestNumber = $applicationRequestNumberGenerator->generate($applicationRequest);

        $this->assertEquals($applicationRequestNumber, 'APDFT00002');
    }
}
