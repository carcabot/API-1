<?php

declare(strict_types=1);

namespace App\Tests\Domain\Command\ApplicationRequest;

use App\Domain\Command\ApplicationRequest\UpdateCustomized;
use App\Domain\Command\ApplicationRequest\UpdateCustomizedHandler;
use App\Entity\ApplicationRequest;
use App\Entity\TariffRate;
use App\Enum\ApplicationRequestType;
use PHPUnit\Framework\TestCase;

class UpdateCustomizedTest extends TestCase
{
    public function testUpdateCustomizedTrue()
    {
        $tariffRateProphecy = $this->prophesize(TariffRate::class);
        $tariffRateProphecy->isCustomizable()->willReturn(true);
        $tariffRate = $tariffRateProphecy->reveal();

        $applicationRequestProphecy = $this->prophesize(ApplicationRequest::class);
        $applicationRequestProphecy->getTariffRate()->willReturn($tariffRate);
        $applicationRequestProphecy->getType()->willReturn(new ApplicationRequestType(ApplicationRequestType::CONTRACT_APPLICATION));
        $applicationRequestProphecy->setCustomized(true)->shouldBeCalled();
        $applicationRequest = $applicationRequestProphecy->reveal();

        $updateCustomizedHandler = new UpdateCustomizedHandler();
        $updateCustomizedHandler->handle(new UpdateCustomized($applicationRequest));
    }

    public function testUpdateCustomizedFalse()
    {
        $tariffRateProphecy = $this->prophesize(TariffRate::class);
        $tariffRateProphecy->isCustomizable()->willReturn(null);
        $tariffRate = $tariffRateProphecy->reveal();

        $applicationRequestProphecy = $this->prophesize(ApplicationRequest::class);
        $applicationRequestProphecy->getTariffRate()->willReturn($tariffRate);
        $applicationRequestProphecy->getType()->willReturn(new ApplicationRequestType(ApplicationRequestType::CONTRACT_APPLICATION));
        $applicationRequestProphecy->setCustomized(null)->shouldBeCalled();
        $applicationRequest = $applicationRequestProphecy->reveal();

        $updateCustomizedHandler = new UpdateCustomizedHandler();
        $updateCustomizedHandler->handle(new UpdateCustomized($applicationRequest));
    }

    public function testUpdateCustomizedNoTariffRate()
    {
        $applicationRequestProphecy = $this->prophesize(ApplicationRequest::class);
        $applicationRequestProphecy->getTariffRate()->willReturn(null);
        $applicationRequestProphecy->setCustomized()->shouldNotBeCalled();
        $applicationRequest = $applicationRequestProphecy->reveal();

        $updateCustomizedHandler = new UpdateCustomizedHandler();
        $updateCustomizedHandler->handle(new UpdateCustomized($applicationRequest));
    }

    public function testUpdateCustomizedNotContractApplication()
    {
        $tariffRateProphecy = $this->prophesize(TariffRate::class);
        $tariffRateProphecy->isCustomizable()->willReturn(true);
        $tariffRate = $tariffRateProphecy->reveal();

        $applicationRequestProphecy = $this->prophesize(ApplicationRequest::class);
        $applicationRequestProphecy->getTariffRate()->willReturn($tariffRate);
        $applicationRequestProphecy->getType()->willReturn(new ApplicationRequestType(ApplicationRequestType::ACCOUNT_CLOSURE));
        $applicationRequestProphecy->setCustomized(true)->shouldNotBeCalled();
        $applicationRequest = $applicationRequestProphecy->reveal();

        $updateCustomizedHandler = new UpdateCustomizedHandler();
        $updateCustomizedHandler->handle(new UpdateCustomized($applicationRequest));
    }
}
