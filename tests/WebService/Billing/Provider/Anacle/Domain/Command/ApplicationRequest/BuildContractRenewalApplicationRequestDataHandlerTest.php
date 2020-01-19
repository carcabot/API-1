<?php
/**
 * Created by PhpStorm.
 * User: mufeer
 * Date: 15/4/19
 * Time: 11:05 AM.
 */
declare(strict_types=1);

namespace App\Tests\WebService\Billing\Provider\Anacle\Domain\Command\ApplicationRequest;

use App\Entity\ApplicationRequest;
use App\Entity\Contract;
use App\Entity\DigitalDocument;
use App\Entity\TariffRate;
use App\WebService\Billing\Provider\Anacle\Domain\Command\ApplicationRequest\BuildContractRenewalApplicationRequestData;
use App\WebService\Billing\Provider\Anacle\Domain\Command\ApplicationRequest\BuildContractRenewalApplicationRequestDataHandler;
use App\WebService\Billing\Services\DataMapper;
use PHPUnit\Framework\TestCase;

class BuildContractRenewalApplicationRequestDataHandlerTest extends TestCase
{
    public function testContractCustomizationIndicatorIsTrue()
    {
        $now = new \DateTime();
        $timezone = new \DateTimeZone('Asia/Singapore');
        $now->setTimezone($timezone);

        $contractProphecy = $this->prophesize(Contract::class);
        $contractProphecy->getContractNumber()->willReturn('SWCC123456');
        $contractProphecy = $contractProphecy->reveal();

        $tariffRateProphecy = $this->prophesize(TariffRate::class);
        $tariffRateProphecy->getTariffRateNumber()->willReturn('SWPP123456');
        $tariffRateProphecy = $tariffRateProphecy->reveal();

        $supplementaryFilesProphecy = $this->prophesize(DigitalDocument::class);
        $supplementaryFilesProphecy->getContentPath()->willReturn('');
        $supplementaryFilesProphecy->getName()->willReturn('testAttachmentName');
        $supplementaryFilesProphecy->getUrl()->willReturn('');
        $supplementaryFilesProphecy = $supplementaryFilesProphecy->reveal();

        $applicationRequestProphecy = $this->prophesize(ApplicationRequest::class);
        $applicationRequestProphecy->getApplicationRequestNumber()->willReturn('SWAP123456');
        $applicationRequestProphecy->getContract()->willReturn($contractProphecy);
        $applicationRequestProphecy->getTariffRate()->willReturn($tariffRateProphecy);
        $applicationRequestProphecy->getPreferredStartDate()->willReturn($now);
        $applicationRequestProphecy->isCustomized()->willReturn(true);
        $applicationRequestProphecy->getSupplementaryFiles()->willReturn([$supplementaryFilesProphecy]);

        $applicationRequest = $applicationRequestProphecy->reveal();

        $expectedApplicationRequestData = [
            'CRMFRCReContractNumber' => 'SWAP123456',
            'FRCContractNumber' => 'SWCC123456',
            'ContractStartDate' => $now->format('Ymd'),
            'PromoCode' => 'SWPP123456',
            'ContractCustomizationIndicator' => 1,
            'Attachments' => [
                [
                    'Attachment' => [
                        'FileName' => 'testAttachmentName',
                        'ContentType' => '',
                        'FileBytes' => '',
                    ],
                ],
            ],
        ];

        $contractRenewalApplicationRequestData = new BuildContractRenewalApplicationRequestData($applicationRequest);

        $dataMapperProphecy = $this->prophesize(DataMapper::class);

        $dataMapperProphecy->mapAttachment($supplementaryFilesProphecy)->willReturn([
            'Attachment' => [
                'FileName' => 'testAttachmentName',
                'ContentType' => '',
                'FileBytes' => '',
            ],
        ]);
        $dataMapperProphecy = $dataMapperProphecy->reveal();

        $buildContractRenewalApplicationRequestDataHandler = new BuildContractRenewalApplicationRequestDataHandler($dataMapperProphecy);
        $actualApplicationRequestData = $buildContractRenewalApplicationRequestDataHandler->handle($contractRenewalApplicationRequestData);

        $this->assertEquals($expectedApplicationRequestData, $actualApplicationRequestData);
    }

    public function testContractCustomizationIndicatorIsFalse()
    {
        $now = new \DateTime();
        $timezone = new \DateTimeZone('Asia/Singapore');
        $now->setTimezone($timezone);

        $contractProphecy = $this->prophesize(Contract::class);
        $contractProphecy->getContractNumber()->willReturn('SWCC123456');
        $contractProphecy = $contractProphecy->reveal();

        $tariffRateProphecy = $this->prophesize(TariffRate::class);
        $tariffRateProphecy->getTariffRateNumber()->willReturn('SWPP123456');
        $tariffRateProphecy = $tariffRateProphecy->reveal();

        $supplementaryFilesProphecy = $this->prophesize(DigitalDocument::class);
        $supplementaryFilesProphecy->getContentPath()->willReturn('');
        $supplementaryFilesProphecy->getName()->willReturn('testAttachmentName');
        $supplementaryFilesProphecy->getUrl()->willReturn('');
        $supplementaryFilesProphecy = $supplementaryFilesProphecy->reveal();

        $applicationRequestProphecy = $this->prophesize(ApplicationRequest::class);
        $applicationRequestProphecy->getApplicationRequestNumber()->willReturn('SWAP123456');
        $applicationRequestProphecy->getContract()->willReturn($contractProphecy);
        $applicationRequestProphecy->getTariffRate()->willReturn($tariffRateProphecy);
        $applicationRequestProphecy->getPreferredStartDate()->willReturn($now);
        $applicationRequestProphecy->isCustomized()->willReturn(false);
        $applicationRequestProphecy->getSupplementaryFiles()->willReturn([$supplementaryFilesProphecy]);

        $applicationRequest = $applicationRequestProphecy->reveal();

        $expectedApplicationRequestData = [
            'CRMFRCReContractNumber' => 'SWAP123456',
            'FRCContractNumber' => 'SWCC123456',
            'ContractStartDate' => $now->format('Ymd'),
            'PromoCode' => 'SWPP123456',
            'ContractCustomizationIndicator' => 0,
            'Attachments' => [
                [
                    'Attachment' => [
                        'FileName' => 'testAttachmentName',
                        'ContentType' => '',
                        'FileBytes' => '',
                    ],
                ],
            ],
        ];

        $contractRenewalApplicationRequestData = new BuildContractRenewalApplicationRequestData($applicationRequest);

        $dataMapperProphecy = $this->prophesize(DataMapper::class);

        $dataMapperProphecy->mapAttachment($supplementaryFilesProphecy)->willReturn([
            'Attachment' => [
                'FileName' => 'testAttachmentName',
                'ContentType' => '',
                'FileBytes' => '',
            ],
        ]);
        $dataMapperProphecy = $dataMapperProphecy->reveal();

        $buildContractRenewalApplicationRequestDataHandler = new BuildContractRenewalApplicationRequestDataHandler($dataMapperProphecy);
        $actualApplicationRequestData = $buildContractRenewalApplicationRequestDataHandler->handle($contractRenewalApplicationRequestData);

        $this->assertEquals($expectedApplicationRequestData, $actualApplicationRequestData);
    }
}
