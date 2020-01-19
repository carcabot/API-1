<?php

declare(strict_types=1);

namespace App\Tests\Domain\Command\ApplicationRequest;

use App\Domain\Command\ApplicationRequest\UpdateApplicationRequestAddonServices;
use App\Domain\Command\ApplicationRequest\UpdateApplicationRequestAddonServicesHandler;
use App\Entity\AddonService;
use App\Entity\ApplicationRequest;
use PHPUnit\Framework\TestCase;

class UpdateApplicationRequestAddonServicesTest extends TestCase
{
    public function testNoAddonServices()
    {
        $applicationRequestProphecy = $this->prophesize(ApplicationRequest::class);
        $applicationRequestProphecy->getAddonServices()->willReturn([]);
        $applicationRequest = $applicationRequestProphecy->reveal();

        $applicationRequestProphecy->clearAddonServices()->shouldNotBeCalled();
        $applicationRequestProphecy->addAddonService()->shouldNotBeCalled();

        $updateApplicationRequestAddonServicesHandler = new UpdateApplicationRequestAddonServicesHandler();
        $updateApplicationRequestAddonServicesHandler->handle(new UpdateApplicationRequestAddonServices($applicationRequest));
    }

    public function testParentAddonServices()
    {
        $parentAddon1Service = new AddonService();
        $parentAddon1Service->setIsBasedOn(null);

        $parentAddon2Service = new AddonService();
        $parentAddon2Service->setIsBasedOn(null);

        $addon1Service = clone $parentAddon1Service;
        $addon1Service->setIsBasedOn($parentAddon1Service);

        $addon2Service = clone $parentAddon2Service;
        $addon2Service->setIsBasedOn($parentAddon2Service);

        $applicationRequestProphecy = $this->prophesize(ApplicationRequest::class);
        $applicationRequestProphecy->getAddonServices()->willReturn([$parentAddon1Service, $parentAddon2Service]);
        $applicationRequest = $applicationRequestProphecy->reveal();

        $applicationRequestProphecy->clearAddonServices()->shouldBeCalled();
        $applicationRequestProphecy->addAddonService($addon1Service)->shouldBeCalled();
        $applicationRequestProphecy->addAddonService($addon2Service)->shouldBeCalled();

        $updateApplicationRequestAddonServicesHandler = new UpdateApplicationRequestAddonServicesHandler();
        $updateApplicationRequestAddonServicesHandler->handle(new UpdateApplicationRequestAddonServices($applicationRequest));
    }

    public function testDoubleParentAddonServices()
    {
        $parentparentAddon1Service = new AddonService();
        $parentparentAddon1Service->setIsBasedOn(null);

        $parentAddon1Service = new AddonService();
        $parentAddon1Service->setIsBasedOn($parentparentAddon1Service);

        $parentAddon2Service = new AddonService();
        $parentAddon2Service->setIsBasedOn(null);

        $addon1Service = clone $parentparentAddon1Service;
        $addon1Service->setIsBasedOn($parentparentAddon1Service);

        $addon2Service = clone $parentAddon2Service;
        $addon2Service->setIsBasedOn($parentAddon2Service);

        $applicationRequestProphecy = $this->prophesize(ApplicationRequest::class);
        $applicationRequestProphecy->getAddonServices()->willReturn([$parentAddon1Service, $parentAddon2Service]);
        $applicationRequest = $applicationRequestProphecy->reveal();

        $applicationRequestProphecy->clearAddonServices()->shouldBeCalled();
        $applicationRequestProphecy->addAddonService($addon1Service)->shouldBeCalled();
        $applicationRequestProphecy->addAddonService($addon2Service)->shouldBeCalled();

        $updateApplicationRequestAddonServicesHandler = new UpdateApplicationRequestAddonServicesHandler();
        $updateApplicationRequestAddonServicesHandler->handle(new UpdateApplicationRequestAddonServices($applicationRequest));
    }
}
