<?php

declare(strict_types=1);
/**
 * Created by PhpStorm.
 * User: mufeer
 * Date: 25/4/19
 * Time: 4:19 PM.
 */

namespace App\Tests\Model;

use ApiPlatform\Core\Api\IriConverterInterface;
use App\Entity\TariffRate;
use App\Model\TariffRateUpdater;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Serializer\SerializerInterface;

class TariffRateUpdaterTest extends TestCase
{
    public function testTariffRateUpdaterWithCustomizableAndInternalUseOnlyAsTrue()
    {
        $tariffRateData = [
            'tariffRateNumber' => 'TR123456',
            'customizable' => 'true',
            'internalUseOnly' => 'true',
            'startDate' => '',
            'validFrom' => '',
            'validThrough' => '',
        ];

        $tariffRateProphecy = $this->prophesize(TariffRate::class);
        $tariffRate = $tariffRateProphecy->reveal();

        $tariffRateRepositoryProphecy = $this->prophesize(EntityRepository::class);
        $tariffRateRepositoryProphecy->findOneBy(['tariffRateNumber' => 'TR123456'])->willReturn($tariffRate);
        $tariffRateRepository = $tariffRateRepositoryProphecy->reveal();

        $serializerInterfaceProphecy = $this->prophesize(SerializerInterface::class);
        $serializerInterfaceProphecy->deserialize(\json_encode(['tariffRateNumber' => 'TR123456',
            'customizable' => true,
            'internalUseOnly' => true,
            'startDate' => null,
            'validFrom' => null,
            'validThrough' => null,
            '@id' => '', ]), TariffRate::class, 'jsonld', ['tariff_rate_write'])->willReturn($tariffRate);
        $serializerInterface = $serializerInterfaceProphecy->reveal();

        $iriConverterInterfaceProphecy = $this->prophesize(IriConverterInterface::class);
        $iriConverterInterfaceProphecy->getIriFromItem($tariffRate)->shouldBeCalled();
        $iriConverterInterface = $iriConverterInterfaceProphecy->reveal();

        $entityManagerProphecy = $this->prophesize(EntityManagerInterface::class);
        $entityManagerProphecy->getRepository(TariffRate::class)->willReturn($tariffRateRepository);
        $entityManagerProphecy->persist($tariffRate)->shouldBeCalled();
        $entityManagerProphecy->flush()->shouldBeCalled();
        $entityManager = $entityManagerProphecy->reveal();

        $tariffRateUpdater = new TariffRateUpdater($entityManager, $iriConverterInterface, $serializerInterface);
        $tariffRateUpdater->processArrayData([$tariffRateData]);
    }

    public function testTariffRateUpdaterWithCustomizableAndInternalUseOnlyAsFalse()
    {
        $tariffRateData = [
            'tariffRateNumber' => 'TR123456',
            'customizable' => 'false',
            'internalUseOnly' => 'false',
            'startDate' => '',
            'validFrom' => '',
            'validThrough' => '',
        ];

        $tariffRateProphecy = $this->prophesize(TariffRate::class);
        $tariffRate = $tariffRateProphecy->reveal();

        $tariffRateRepositoryProphecy = $this->prophesize(EntityRepository::class);
        $tariffRateRepositoryProphecy->findOneBy(['tariffRateNumber' => 'TR123456'])->willReturn($tariffRate);
        $tariffRateRepository = $tariffRateRepositoryProphecy->reveal();

        $serializerInterfaceProphecy = $this->prophesize(SerializerInterface::class);
        $serializerInterfaceProphecy->deserialize(\json_encode(['tariffRateNumber' => 'TR123456',
            'customizable' => false,
            'internalUseOnly' => false,
            'startDate' => null,
            'validFrom' => null,
            'validThrough' => null,
            '@id' => '', ]), TariffRate::class, 'jsonld', ['tariff_rate_write'])->willReturn($tariffRate);
        $serializerInterface = $serializerInterfaceProphecy->reveal();

        $iriConverterInterfaceProphecy = $this->prophesize(IriConverterInterface::class);
        $iriConverterInterfaceProphecy->getIriFromItem($tariffRate)->shouldBeCalled();
        $iriConverterInterface = $iriConverterInterfaceProphecy->reveal();

        $entityManagerProphecy = $this->prophesize(EntityManagerInterface::class);
        $entityManagerProphecy->getRepository(TariffRate::class)->willReturn($tariffRateRepository);
        $entityManagerProphecy->persist($tariffRate)->shouldBeCalled();
        $entityManagerProphecy->flush()->shouldBeCalled();
        $entityManager = $entityManagerProphecy->reveal();

        $tariffRateUpdater = new TariffRateUpdater($entityManager, $iriConverterInterface, $serializerInterface);
        $tariffRateUpdater->processArrayData([$tariffRateData]);
    }

    public function testTariffRateUpdaterWithNoExistingTariffRateFound()
    {
        $tariffRateData = [
            'tariffRateNumber' => 'TR123456',
            'customizable' => 'false',
            'internalUseOnly' => 'false',
            'startDate' => '',
            'validFrom' => '',
            'validThrough' => '',
        ];

        $tariffRateProphecy = $this->prophesize(TariffRate::class);
        $tariffRate = $tariffRateProphecy->reveal();

        $tariffRateRepositoryProphecy = $this->prophesize(EntityRepository::class);
        $tariffRateRepositoryProphecy->findOneBy(['tariffRateNumber' => 'TR123456'])->willReturn(null);
        $tariffRateRepository = $tariffRateRepositoryProphecy->reveal();

        $serializerInterfaceProphecy = $this->prophesize(SerializerInterface::class);
        $serializerInterfaceProphecy->deserialize(\json_encode(['tariffRateNumber' => 'TR123456',
            'customizable' => false,
            'internalUseOnly' => false,
            'startDate' => null,
            'validFrom' => null,
            'validThrough' => null,
            'status' => 'NEW', ]), TariffRate::class, 'jsonld', ['tariff_rate_write'])->willReturn($tariffRate);
        $serializerInterface = $serializerInterfaceProphecy->reveal();

        $iriConverterInterfaceProphecy = $this->prophesize(IriConverterInterface::class);
        $iriConverterInterface = $iriConverterInterfaceProphecy->reveal();

        $entityManagerProphecy = $this->prophesize(EntityManagerInterface::class);
        $entityManagerProphecy->getRepository(TariffRate::class)->willReturn($tariffRateRepository);
        $entityManagerProphecy->persist($tariffRate)->shouldBeCalled();
        $entityManagerProphecy->flush()->shouldBeCalled();
        $entityManager = $entityManagerProphecy->reveal();

        $tariffRateUpdater = new TariffRateUpdater($entityManager, $iriConverterInterface, $serializerInterface);
        $tariffRateUpdater->processArrayData([$tariffRateData]);
    }
}
