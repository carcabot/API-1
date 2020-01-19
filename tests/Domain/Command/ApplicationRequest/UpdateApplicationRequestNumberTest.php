<?php

declare(strict_types=1);

namespace App\Tests\Domain\Command\ApplicationRequest;

use App\Domain\Command\ApplicationRequest\UpdateApplicationRequestNumber;
use App\Domain\Command\ApplicationRequest\UpdateApplicationRequestNumberHandler;
use App\Entity\ApplicationRequest;
use App\Model\ApplicationRequestNumberGenerator;
use PHPUnit\Framework\TestCase;

class UpdateApplicationRequestNumberTest extends TestCase
{
    public function testUpdateApplicationRequestNumber()
    {
        $length = 8;
        $prefix = 'A-';
        $type = 'application_request';
        $number = 1;

        $applicationRequestProphecy = $this->prophesize(ApplicationRequest::class);
        $applicationRequestProphecy->setApplicationRequestNumber('A-00000001')->shouldBeCalled();
        $applicationRequest = $applicationRequestProphecy->reveal();

        $applicationRequestNumberGeneratorProphecy = $this->prophesize(ApplicationRequestNumberGenerator::class);
        $applicationRequestNumberGeneratorProphecy->generate($applicationRequest)->willReturn(\sprintf('%s%s', $prefix, \str_pad((string) $number, $length, '0', STR_PAD_LEFT)));
        $applicationRequestNumberGenerator = $applicationRequestNumberGeneratorProphecy->reveal();

        $updateApplicationRequestNumberHandler = new UpdateApplicationRequestNumberHandler($applicationRequestNumberGenerator);
        $updateApplicationRequestNumberHandler->handle(new UpdateApplicationRequestNumber($applicationRequest));
    }
}
