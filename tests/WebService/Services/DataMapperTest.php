<?php

declare(strict_types=1);
/**
 * Created by PhpStorm.
 * User: mufeer
 * Date: 2/5/19
 * Time: 10:37 AM.
 */

namespace App\Tests\WebService\Services;

use App\Entity\ContactPoint;
use App\Entity\DigitalDocument;
use App\Entity\Identification;
use App\Entity\PostalAddress;
use App\Enum\ContractType;
use App\Enum\IdentificationName;
use App\Enum\PostalAddressType;
use App\Enum\RefundType;
use App\WebService\Billing\Services\DataMapper;
use libphonenumber\PhoneNumber;
use PHPUnit\Framework\TestCase;
use Vich\UploaderBundle\Storage\StorageInterface;

class DataMapperTest extends TestCase
{
    public function testMapAddressFieldsWithPostalAddressTypeAsMailing_Address()
    {
        $postalAddressProphecy = $this->prophesize(PostalAddress::class);
        $postalAddressProphecy->getAddressCountry()->willReturn('testAddressCountry');
        $postalAddressProphecy->getAddressRegion()->willReturn('testAddressRegion');
        $postalAddressProphecy->getBuildingName()->willReturn('testBuildingName');
        $postalAddressProphecy->getFloor()->willReturn('testFloor');
        $postalAddressProphecy->getHouseNumber()->willReturn('testHouseNumber');
        $postalAddressProphecy->getPostalCode()->willReturn('testPostalCode');
        $postalAddressProphecy->getStreetAddress()->willReturn('testStreetAddress');
        $postalAddressProphecy->getType()->willReturn(new PostalAddressType(PostalAddressType::MAILING_ADDRESS));
        $postalAddressProphecy->getUnitNumber()->willReturn('testUnitNumber');
        $postalAddress = $postalAddressProphecy->reveal();

        $expectedAddressData = [
            'MailingAddress' => 'TESTHOUSENUMBER TESTSTREETADDRESS TESTBUILDINGNAME',
            'MailingUnitNumber' => '#TESTFLOOR-TESTUNITNUMBER',
            'MailingPostalCode' => 'testPostalCode',
        ];

        $storageInterfaceProphecy = $this->prophesize(StorageInterface::class);
        $storageInterface = $storageInterfaceProphecy->reveal();

        $dataMapper = new DataMapper($storageInterface);
        $actualAddressData = $dataMapper->mapAddressFields($postalAddress);

        $this->assertEquals($expectedAddressData, $actualAddressData);
    }

    public function testMapAddressFieldsWithPostalAddressTypeAsPremise_Address()
    {
        $postalAddressProphecy = $this->prophesize(PostalAddress::class);
        $postalAddressProphecy->getAddressCountry()->willReturn('testAddressCountry');
        $postalAddressProphecy->getAddressRegion()->willReturn('testAddressRegion');
        $postalAddressProphecy->getBuildingName()->willReturn('testBuildingName');
        $postalAddressProphecy->getFloor()->willReturn('testFloor');
        $postalAddressProphecy->getHouseNumber()->willReturn('testHouseNumber');
        $postalAddressProphecy->getPostalCode()->willReturn('testPostalCode');
        $postalAddressProphecy->getStreetAddress()->willReturn('testStreetAddress');
        $postalAddressProphecy->getType()->willReturn(new PostalAddressType(PostalAddressType::PREMISE_ADDRESS));
        $postalAddressProphecy->getUnitNumber()->willReturn('testUnitNumber');
        $postalAddress = $postalAddressProphecy->reveal();

        $expectedAddressData = [
            'PremisesAddress' => 'TESTHOUSENUMBER TESTSTREETADDRESS TESTBUILDINGNAME',
            'PremisesUnitNumber' => '#TESTFLOOR-TESTUNITNUMBER',
            'PremisesPostalCode' => 'testPostalCode',
        ];

        $storageInterfaceProphecy = $this->prophesize(StorageInterface::class);
        $storageInterface = $storageInterfaceProphecy->reveal();

        $dataMapper = new DataMapper($storageInterface);
        $actualAddressData = $dataMapper->mapAddressFields($postalAddress);

        $this->assertEquals($expectedAddressData, $actualAddressData);
    }

    public function testMapAddressFieldsWithPostalAddressTypeAsRefund_Address()
    {
        $postalAddressProphecy = $this->prophesize(PostalAddress::class);
        $postalAddressProphecy->getAddressCountry()->willReturn('testAddressCountry');
        $postalAddressProphecy->getAddressRegion()->willReturn('testAddressRegion');
        $postalAddressProphecy->getBuildingName()->willReturn('testBuildingName');
        $postalAddressProphecy->getFloor()->willReturn('testFloor');
        $postalAddressProphecy->getHouseNumber()->willReturn('testHouseNumber');
        $postalAddressProphecy->getPostalCode()->willReturn('testPostalCode');
        $postalAddressProphecy->getStreetAddress()->willReturn('testStreetAddress');
        $postalAddressProphecy->getType()->willReturn(new PostalAddressType(PostalAddressType::REFUND_ADDRESS));
        $postalAddressProphecy->getUnitNumber()->willReturn('testUnitNumber');
        $postalAddress = $postalAddressProphecy->reveal();

        $expectedAddressData = [
            'RefundAddressCountry' => 'testAddressCountry',
            'RefundAddressState' => 'testAddressRegion',
            'RefundAddressCity' => 'testAddressRegion',
            'RefundPostalCode' => 'testPostalCode',
            'RefundAddressLine1' => 'TESTHOUSENUMBER TESTSTREETADDRESS TESTBUILDINGNAME',
            'RefundAddressLine2' => '#TESTFLOOR-TESTUNITNUMBER',
        ];

        $storageInterfaceProphecy = $this->prophesize(StorageInterface::class);
        $storageInterface = $storageInterfaceProphecy->reveal();

        $dataMapper = new DataMapper($storageInterface);
        $actualAddressData = $dataMapper->mapAddressFields($postalAddress);

        $this->assertEquals($expectedAddressData, $actualAddressData);
    }

    public function testMapContactPoints()
    {
        $phoneNumberProphecy = $this->prophesize(PhoneNumber::class);
        $phoneNumberProphecy->getCountryCode()->willReturn(60);
        $phoneNumberProphecy->getNationalNumber()->willReturn(111111);
        $phoneNumber = $phoneNumberProphecy->reveal();

        $contactPointProphecy = $this->prophesize(ContactPoint::class);
        $contactPointProphecy->getEmails()->willReturn(['test@test.com']);
        $contactPointProphecy->getMobilePhoneNumbers()->willReturn([$phoneNumber]);
        $contactPointProphecy->getTelephoneNumbers()->willReturn([$phoneNumber]);
        $contactPointProphecy->getFaxNumbers()->willReturn([$phoneNumber]);
        $contactPoint = $contactPointProphecy->reveal();

        $storageInterfaceProphecy = $this->prophesize(StorageInterface::class);
        $storageInterface = $storageInterfaceProphecy->reveal();

        $expectedContactData = [
            'email' => 'test@test.com',
            'mobile_number' => [
                'country_code' => 60,
                'number' => 111111,
            ],
            'phone_number' => [
                'country_code' => 60,
                'number' => 111111,
            ],
            'fax_number' => [
                'country_code' => 60,
                'number' => 111111,
            ],
        ];

        $dataMapper = new DataMapper($storageInterface);
        $actualContactData = $dataMapper->mapContactPoints([$contactPoint]);

        $this->assertEquals($expectedContactData, $actualContactData);
    }

    public function testMapContractSubTypeWithTypeAsLanded()
    {
        $storageInterfaceProphecy = $this->prophesize(StorageInterface::class);
        $storageInterface = $storageInterfaceProphecy->reveal();

        $expectedData = 'Landed';

        $dataMapper = new DataMapper($storageInterface);
        $actualData = $dataMapper->mapContractSubtype('LANDED');

        $this->assertEquals($expectedData, $actualData);
    }

    public function testMapContractSubTypeWithTypeAsCondominium()
    {
        $storageInterfaceProphecy = $this->prophesize(StorageInterface::class);
        $storageInterface = $storageInterfaceProphecy->reveal();

        $expectedData = 'Condo';

        $dataMapper = new DataMapper($storageInterface);
        $actualData = $dataMapper->mapContractSubtype('CONDOMINIUM');

        $this->assertEquals($expectedData, $actualData);
    }

    public function testMapContractSubTypeWithTypeAsEducationalInstitutions()
    {
        $storageInterfaceProphecy = $this->prophesize(StorageInterface::class);
        $storageInterface = $storageInterfaceProphecy->reveal();

        $expectedData = 'Educational Institutions';

        $dataMapper = new DataMapper($storageInterface);
        $actualData = $dataMapper->mapContractSubtype('EDUCATIONAL_INSTITUTIONS');

        $this->assertEquals($expectedData, $actualData);
    }

    public function testMapContractSubTypeWithTypeAsCharitableOrganisations()
    {
        $storageInterfaceProphecy = $this->prophesize(StorageInterface::class);
        $storageInterface = $storageInterfaceProphecy->reveal();

        $expectedData = 'Charitable Organisations';

        $dataMapper = new DataMapper($storageInterface);
        $actualData = $dataMapper->mapContractSubtype('CHARITABLE_ORGANISATIONS');

        $this->assertEquals($expectedData, $actualData);
    }

    public function testMapContractSubTypeWithTypeAsConstruction()
    {
        $storageInterfaceProphecy = $this->prophesize(StorageInterface::class);
        $storageInterface = $storageInterfaceProphecy->reveal();

        $expectedData = 'Construction';

        $dataMapper = new DataMapper($storageInterface);
        $actualData = $dataMapper->mapContractSubtype('CONSTRUCTION');

        $this->assertEquals($expectedData, $actualData);
    }

    public function testMapContractSubTypeWithTypeAsDorimitories()
    {
        $storageInterfaceProphecy = $this->prophesize(StorageInterface::class);
        $storageInterface = $storageInterfaceProphecy->reveal();

        $expectedData = 'Dormitories';

        $dataMapper = new DataMapper($storageInterface);
        $actualData = $dataMapper->mapContractSubtype('DORMITORIES');

        $this->assertEquals($expectedData, $actualData);
    }

    public function testMapContractSubTypeWithTypeAsElectronics_SemiConductors()
    {
        $storageInterfaceProphecy = $this->prophesize(StorageInterface::class);
        $storageInterface = $storageInterfaceProphecy->reveal();

        $expectedData = 'Electronics/ Semiconductors';

        $dataMapper = new DataMapper($storageInterface);
        $actualData = $dataMapper->mapContractSubtype('ELECTRONICS_SEMICONDUCTORS');

        $this->assertEquals($expectedData, $actualData);
    }

    public function testMapContractSubTypeWithTypeAsFB_Outlets()
    {
        $storageInterfaceProphecy = $this->prophesize(StorageInterface::class);
        $storageInterface = $storageInterfaceProphecy->reveal();

        $expectedData = 'F&B Outlets';

        $dataMapper = new DataMapper($storageInterface);
        $actualData = $dataMapper->mapContractSubtype('F_B_OUTLETS');

        $this->assertEquals($expectedData, $actualData);
    }

    public function testMapContractSubTypeWithTypeAsHotels()
    {
        $storageInterfaceProphecy = $this->prophesize(StorageInterface::class);
        $storageInterface = $storageInterfaceProphecy->reveal();

        $expectedData = 'Hotels';

        $dataMapper = new DataMapper($storageInterface);
        $actualData = $dataMapper->mapContractSubtype('HOTELS');

        $this->assertEquals($expectedData, $actualData);
    }

    public function testMapContractSubTypeWithTypeAsLogistics()
    {
        $storageInterfaceProphecy = $this->prophesize(StorageInterface::class);
        $storageInterface = $storageInterfaceProphecy->reveal();

        $expectedData = 'Logistics';

        $dataMapper = new DataMapper($storageInterface);
        $actualData = $dataMapper->mapContractSubtype('LOGISTICS');

        $this->assertEquals($expectedData, $actualData);
    }

    public function testMapContractSubTypeWithTypeAsMCST_CONDOS()
    {
        $storageInterfaceProphecy = $this->prophesize(StorageInterface::class);
        $storageInterface = $storageInterfaceProphecy->reveal();

        $expectedData = 'MCST - Condos';

        $dataMapper = new DataMapper($storageInterface);
        $actualData = $dataMapper->mapContractSubtype('MCST_CONDOS');

        $this->assertEquals($expectedData, $actualData);
    }

    public function testMapContractSubTypeWithTypeAsOfficeRealEstate()
    {
        $storageInterfaceProphecy = $this->prophesize(StorageInterface::class);
        $storageInterface = $storageInterfaceProphecy->reveal();

        $expectedData = 'Office Real Estate';

        $dataMapper = new DataMapper($storageInterface);
        $actualData = $dataMapper->mapContractSubtype('OFFICE_REAL_ESTATE');

        $this->assertEquals($expectedData, $actualData);
    }

    public function testMapContractSubTypeWithTypeAsOtherHeavyManufacturing()
    {
        $storageInterfaceProphecy = $this->prophesize(StorageInterface::class);
        $storageInterface = $storageInterfaceProphecy->reveal();

        $expectedData = 'Other Heavy Manufacturing';

        $dataMapper = new DataMapper($storageInterface);
        $actualData = $dataMapper->mapContractSubtype('OTHER_HEAVY_MANUFACTURING');

        $this->assertEquals($expectedData, $actualData);
    }

    public function testMapContractSubTypeWithTypeAsOtherLightManufacturing()
    {
        $storageInterfaceProphecy = $this->prophesize(StorageInterface::class);
        $storageInterface = $storageInterfaceProphecy->reveal();

        $expectedData = 'Other Light Manufacturing';

        $dataMapper = new DataMapper($storageInterface);
        $actualData = $dataMapper->mapContractSubtype('OTHER_LIGHT_MANUFACTURING');

        $this->assertEquals($expectedData, $actualData);
    }

    public function testMapContractSubTypeWithTypeAsOthers()
    {
        $storageInterfaceProphecy = $this->prophesize(StorageInterface::class);
        $storageInterface = $storageInterfaceProphecy->reveal();

        $expectedData = 'Others';

        $dataMapper = new DataMapper($storageInterface);
        $actualData = $dataMapper->mapContractSubtype('OTHERS');

        $this->assertEquals($expectedData, $actualData);
    }

    public function testMapContractSubTypeWithTypeAsPharmaceuticals()
    {
        $storageInterfaceProphecy = $this->prophesize(StorageInterface::class);
        $storageInterface = $storageInterfaceProphecy->reveal();

        $expectedData = 'Pharmaceuticals';

        $dataMapper = new DataMapper($storageInterface);
        $actualData = $dataMapper->mapContractSubtype('PHARMACEUTICALS');

        $this->assertEquals($expectedData, $actualData);
    }

    public function testMapContractSubTypeWithTypeAsPorts()
    {
        $storageInterfaceProphecy = $this->prophesize(StorageInterface::class);
        $storageInterface = $storageInterfaceProphecy->reveal();

        $expectedData = 'Ports';

        $dataMapper = new DataMapper($storageInterface);
        $actualData = $dataMapper->mapContractSubtype('PORTS');

        $this->assertEquals($expectedData, $actualData);
    }

    public function testMapContractSubTypeWithTypeAsPrecisionIndustries()
    {
        $storageInterfaceProphecy = $this->prophesize(StorageInterface::class);
        $storageInterface = $storageInterfaceProphecy->reveal();

        $expectedData = 'Precision Industries';

        $dataMapper = new DataMapper($storageInterface);
        $actualData = $dataMapper->mapContractSubtype('PRECISION_INDUSTRIES');

        $this->assertEquals($expectedData, $actualData);
    }

    public function testMapContractSubTypeWithTypeAsRefineriesPetrochemicals()
    {
        $storageInterfaceProphecy = $this->prophesize(StorageInterface::class);
        $storageInterface = $storageInterfaceProphecy->reveal();

        $expectedData = 'Refineries & Petrochemicals';

        $dataMapper = new DataMapper($storageInterface);
        $actualData = $dataMapper->mapContractSubtype('REFINERIES_PETROCHEMICALS');

        $this->assertEquals($expectedData, $actualData);
    }

    public function testMapContractSubTypeWithTypeAsRetailOutlets()
    {
        $storageInterfaceProphecy = $this->prophesize(StorageInterface::class);
        $storageInterface = $storageInterfaceProphecy->reveal();

        $expectedData = 'Retail outlets';

        $dataMapper = new DataMapper($storageInterface);
        $actualData = $dataMapper->mapContractSubtype('RETAIL_OUTLETS');

        $this->assertEquals($expectedData, $actualData);
    }

    public function testMapContractSubTypeWithTypeAsShoppingMalls()
    {
        $storageInterfaceProphecy = $this->prophesize(StorageInterface::class);
        $storageInterface = $storageInterfaceProphecy->reveal();

        $expectedData = 'Shopping Malls';

        $dataMapper = new DataMapper($storageInterface);
        $actualData = $dataMapper->mapContractSubtype('SHOPPING_MALLS');

        $this->assertEquals($expectedData, $actualData);
    }

    public function testMapContractSubTypeWithTypeAsTransportation()
    {
        $storageInterfaceProphecy = $this->prophesize(StorageInterface::class);
        $storageInterface = $storageInterfaceProphecy->reveal();

        $expectedData = 'Transportation';

        $dataMapper = new DataMapper($storageInterface);
        $actualData = $dataMapper->mapContractSubtype('TRANSPORTATION');

        $this->assertEquals($expectedData, $actualData);
    }

    public function testMapContractSubTypeWithTypeAsNull()
    {
        $storageInterfaceProphecy = $this->prophesize(StorageInterface::class);
        $storageInterface = $storageInterfaceProphecy->reveal();

        $expectedData = null;

        $dataMapper = new DataMapper($storageInterface);
        $actualData = $dataMapper->mapContractSubtype(null);

        $this->assertEquals($expectedData, $actualData);
    }

    public function testMapContractSubTypeWithTypeAsExecutiveFlatHDB()
    {
        $storageInterfaceProphecy = $this->prophesize(StorageInterface::class);
        $storageInterface = $storageInterfaceProphecy->reveal();

        $expectedData = '5-Room/ Executive - HDB';

        $dataMapper = new DataMapper($storageInterface);
        $actualData = $dataMapper->mapContractSubtype('EXECUTIVE_FLAT_HDB');

        $this->assertEquals($expectedData, $actualData);
    }

    public function testMapContractSubTypeWithTypeAsFiveRoomFlatHDB()
    {
        $storageInterfaceProphecy = $this->prophesize(StorageInterface::class);
        $storageInterface = $storageInterfaceProphecy->reveal();

        $expectedData = '5-Room/ Executive - HDB';

        $dataMapper = new DataMapper($storageInterface);
        $actualData = $dataMapper->mapContractSubtype('5_ROOM_FLAT_HDB');

        $this->assertEquals($expectedData, $actualData);
    }

    public function testMapContractSubTypeWithTypeAsFourRoomFlatHDB()
    {
        $storageInterfaceProphecy = $this->prophesize(StorageInterface::class);
        $storageInterface = $storageInterfaceProphecy->reveal();

        $expectedData = '4-Room HDB';

        $dataMapper = new DataMapper($storageInterface);
        $actualData = $dataMapper->mapContractSubtype('4_ROOM_FLAT_HDB');

        $this->assertEquals($expectedData, $actualData);
    }

    public function testMapContractSubTypeWithTypeAsThreeRoomFlatHDB()
    {
        $storageInterfaceProphecy = $this->prophesize(StorageInterface::class);
        $storageInterface = $storageInterfaceProphecy->reveal();

        $expectedData = '3-Room HDB';

        $dataMapper = new DataMapper($storageInterface);
        $actualData = $dataMapper->mapContractSubtype('3_ROOM_FLAT_HDB');

        $this->assertEquals($expectedData, $actualData);
    }

    public function testMapContractSubTypeWithTypeAsTwoRoomFlatHDB()
    {
        $storageInterfaceProphecy = $this->prophesize(StorageInterface::class);
        $storageInterface = $storageInterfaceProphecy->reveal();

        $expectedData = '2-Room HDB';

        $dataMapper = new DataMapper($storageInterface);
        $actualData = $dataMapper->mapContractSubtype('2_ROOM_FLAT_HDB');

        $this->assertEquals($expectedData, $actualData);
    }

    public function testMapContractSubTypeWithTypeAsOneRoomFlatHDB()
    {
        $storageInterfaceProphecy = $this->prophesize(StorageInterface::class);
        $storageInterface = $storageInterfaceProphecy->reveal();

        $expectedData = '1-Room HDB';

        $dataMapper = new DataMapper($storageInterface);
        $actualData = $dataMapper->mapContractSubtype('1_ROOM_FLAT_HDB');

        $this->assertEquals($expectedData, $actualData);
    }

    public function testMapContractTypeWithTypeAsCommercial()
    {
        $storageInterfaceProphecy = $this->prophesize(StorageInterface::class);
        $storageInterface = $storageInterfaceProphecy->reveal();

        $expectedData = 'C';

        $dataMapper = new DataMapper($storageInterface);
        $actualData = $dataMapper->mapContractType(new ContractType(ContractType::COMMERCIAL));

        $this->assertEquals($expectedData, $actualData);
    }

    public function testMapContractTypeWithTypeAsResidential()
    {
        $storageInterfaceProphecy = $this->prophesize(StorageInterface::class);
        $storageInterface = $storageInterfaceProphecy->reveal();

        $expectedData = 'R';

        $dataMapper = new DataMapper($storageInterface);
        $actualData = $dataMapper->mapContractType(new ContractType(ContractType::RESIDENTIAL));

        $this->assertEquals($expectedData, $actualData);
    }

    public function testMapContractTypeWithoutContractType()
    {
        $storageInterfaceProphecy = $this->prophesize(StorageInterface::class);
        $storageInterface = $storageInterfaceProphecy->reveal();

        $expectedData = null;

        $dataMapper = new DataMapper($storageInterface);
        $actualData = $dataMapper->mapContractType(null);

        $this->assertEquals($expectedData, $actualData);
    }

    public function testMapIdentifierByKeyWithDefaultValues()
    {
        $identifierProphecy = $this->prophesize(Identification::class);
        $identifierProphecy->getName()->willReturn(new IdentificationName(IdentificationName::NATIONAL_REGISTRATION_IDENTITY_CARD));
        $identifierProphecy->getValidThrough()->willReturn(null);
        $identifierProphecy->getValue()->willReturn('nric123456');
        $identifier = $identifierProphecy->reveal();

        $storageInterfaceProphecy = $this->prophesize(StorageInterface::class);
        $storageInterface = $storageInterfaceProphecy->reveal();

        $expectedData = 'NRIC123456';

        $dataMapper = new DataMapper($storageInterface);
        $actualData = $dataMapper->mapIdentifierByKey([$identifier], 'NATIONAL_REGISTRATION_IDENTITY_CARD');

        $this->assertEquals($expectedData, $actualData);
    }

    public function testMapIdentifierByKeyWithoutDefaultValues()
    {
        $identifierProphecy = $this->prophesize(Identification::class);
        $identifierProphecy->getName()->willReturn(new IdentificationName(IdentificationName::UNIQUE_ENTITY_NUMBER));
        $identifier = $identifierProphecy->reveal();

        $storageInterfaceProphecy = $this->prophesize(StorageInterface::class);
        $storageInterface = $storageInterfaceProphecy->reveal();

        $expectedData = null;

        $dataMapper = new DataMapper($storageInterface);
        $actualData = $dataMapper->mapIdentifierByKey([$identifier], 'NATIONAL_REGISTRATION_IDENTITY_CARD');

        $this->assertEquals($expectedData, $actualData);
    }

    public function testMapRefundTypeWithTypeAsBillOffset()
    {
        $storageInterfaceProphecy = $this->prophesize(StorageInterface::class);
        $storageInterface = $storageInterfaceProphecy->reveal();

        $expectedData = 'O';

        $dataMapper = new DataMapper($storageInterface);
        $actualData = $dataMapper->mapRefundType(new RefundType(RefundType::BILL_OFFSET));

        $this->assertEquals($expectedData, $actualData);
    }

    public function testMapRefundTypeWithTypeAsFullRefund()
    {
        $storageInterfaceProphecy = $this->prophesize(StorageInterface::class);
        $storageInterface = $storageInterfaceProphecy->reveal();

        $expectedData = 'R';

        $dataMapper = new DataMapper($storageInterface);
        $actualData = $dataMapper->mapRefundType(new RefundType(RefundType::FULL_REFUND));

        $this->assertEquals($expectedData, $actualData);
    }

    public function testMapRCCSStatusWithStatusAsActive()
    {
        $storageInterfaceProphecy = $this->prophesize(StorageInterface::class);
        $storageInterface = $storageInterfaceProphecy->reveal();

        $expectedData = 'ACTIVE';

        $dataMapper = new DataMapper($storageInterface);
        $actualData = $dataMapper->mapRCCSStatus('Active');

        $this->assertEquals($expectedData, $actualData);
    }

    public function testMapRCCSStatusWithStatusAsCancelled()
    {
        $storageInterfaceProphecy = $this->prophesize(StorageInterface::class);
        $storageInterface = $storageInterfaceProphecy->reveal();

        $expectedData = 'CANCELLED';

        $dataMapper = new DataMapper($storageInterface);
        $actualData = $dataMapper->mapRCCSStatus('Cancelled');

        $this->assertEquals($expectedData, $actualData);
    }

    public function testMapRCCSStatusWithStatusAsDisabled()
    {
        $storageInterfaceProphecy = $this->prophesize(StorageInterface::class);
        $storageInterface = $storageInterfaceProphecy->reveal();

        $expectedData = 'DISABLED';

        $dataMapper = new DataMapper($storageInterface);
        $actualData = $dataMapper->mapRCCSStatus('Disabled');

        $this->assertEquals($expectedData, $actualData);
    }

    public function testMapRCCSStatusWithStatusAsPendingApproval()
    {
        $storageInterfaceProphecy = $this->prophesize(StorageInterface::class);
        $storageInterface = $storageInterfaceProphecy->reveal();

        $expectedData = 'PENDING_APPROVAL';

        $dataMapper = new DataMapper($storageInterface);
        $actualData = $dataMapper->mapRCCSStatus('Pending Approval');

        $this->assertEquals($expectedData, $actualData);
    }

    public function testMapRCCSStatusWithStatusAsPendingEffective()
    {
        $storageInterfaceProphecy = $this->prophesize(StorageInterface::class);
        $storageInterface = $storageInterfaceProphecy->reveal();

        $expectedData = 'PENDING_EFFECTIVE';

        $dataMapper = new DataMapper($storageInterface);
        $actualData = $dataMapper->mapRCCSStatus('Pending Effective');

        $this->assertEquals($expectedData, $actualData);
    }

    public function testMapRCCSStatusWithStatusAsPendingReview()
    {
        $storageInterfaceProphecy = $this->prophesize(StorageInterface::class);
        $storageInterface = $storageInterfaceProphecy->reveal();

        $expectedData = 'PENDING_REVIEW';

        $dataMapper = new DataMapper($storageInterface);
        $actualData = $dataMapper->mapRCCSStatus('Pending Review');

        $this->assertEquals($expectedData, $actualData);
    }

    public function testMapRCCSStatusWithStatusAsPendingTerminate()
    {
        $storageInterfaceProphecy = $this->prophesize(StorageInterface::class);
        $storageInterface = $storageInterfaceProphecy->reveal();

        $expectedData = 'PENDING_TERMINATION';

        $dataMapper = new DataMapper($storageInterface);
        $actualData = $dataMapper->mapRCCSStatus('Pending Terminate');

        $this->assertEquals($expectedData, $actualData);
    }

    public function testMapRCCSStatusWithStatusAsTerminated()
    {
        $storageInterfaceProphecy = $this->prophesize(StorageInterface::class);
        $storageInterface = $storageInterfaceProphecy->reveal();

        $expectedData = 'TERMINATED';

        $dataMapper = new DataMapper($storageInterface);
        $actualData = $dataMapper->mapRCCSStatus('Terminated');

        $this->assertEquals($expectedData, $actualData);
    }

    public function testMapRCCSStatusWithStatusAsNull()
    {
        $storageInterfaceProphecy = $this->prophesize(StorageInterface::class);
        $storageInterface = $storageInterfaceProphecy->reveal();

        $expectedData = null;

        $dataMapper = new DataMapper($storageInterface);
        $actualData = $dataMapper->mapRCCSStatus(null);

        $this->assertEquals($expectedData, $actualData);
    }

    public function testMapGiroStatusWithStatusAsActive()
    {
        $storageInterfaceProphecy = $this->prophesize(StorageInterface::class);
        $storageInterface = $storageInterfaceProphecy->reveal();

        $expectedData = 'ACTIVE';

        $dataMapper = new DataMapper($storageInterface);
        $actualData = $dataMapper->mapGiroStatus('Active');

        $this->assertEquals($expectedData, $actualData);
    }

    public function testMapGiroStatusWithStatusAsApproved()
    {
        $storageInterfaceProphecy = $this->prophesize(StorageInterface::class);
        $storageInterface = $storageInterfaceProphecy->reveal();

        $expectedData = 'APPROVED';

        $dataMapper = new DataMapper($storageInterface);
        $actualData = $dataMapper->mapGiroStatus('Approved');

        $this->assertEquals($expectedData, $actualData);
    }

    public function testMapGiroStatusWithStatusAsCancelled()
    {
        $storageInterfaceProphecy = $this->prophesize(StorageInterface::class);
        $storageInterface = $storageInterfaceProphecy->reveal();

        $expectedData = 'CANCELLED';

        $dataMapper = new DataMapper($storageInterface);
        $actualData = $dataMapper->mapGiroStatus('Cancelled');

        $this->assertEquals($expectedData, $actualData);
    }

    public function testMapGiroStatusWithStatusAsPendingBankApproval()
    {
        $storageInterfaceProphecy = $this->prophesize(StorageInterface::class);
        $storageInterface = $storageInterfaceProphecy->reveal();

        $expectedData = 'PENDING_BANK_APPROVAL';

        $dataMapper = new DataMapper($storageInterface);
        $actualData = $dataMapper->mapGiroStatus('Pending Bank Approval');

        $this->assertEquals($expectedData, $actualData);
    }

    public function testMapGiroStatusWithStatusAsPendingDocumentReview()
    {
        $storageInterfaceProphecy = $this->prophesize(StorageInterface::class);
        $storageInterface = $storageInterfaceProphecy->reveal();

        $expectedData = 'PENDING_DOCUMENT_REVIEW';

        $dataMapper = new DataMapper($storageInterface);
        $actualData = $dataMapper->mapGiroStatus('Pending Document Review');

        $this->assertEquals($expectedData, $actualData);
    }

    public function testMapGiroStatusWithStatusAsPendingEffective()
    {
        $storageInterfaceProphecy = $this->prophesize(StorageInterface::class);
        $storageInterface = $storageInterfaceProphecy->reveal();

        $expectedData = 'PENDING_EFFECTIVE';

        $dataMapper = new DataMapper($storageInterface);
        $actualData = $dataMapper->mapGiroStatus('Pending Effective');

        $this->assertEquals($expectedData, $actualData);
    }

    public function testMapGiroStatusWithStatusAsPendingInternalProcessing()
    {
        $storageInterfaceProphecy = $this->prophesize(StorageInterface::class);
        $storageInterface = $storageInterfaceProphecy->reveal();

        $expectedData = 'PENDING_INTERNAL_PROCESSING';

        $dataMapper = new DataMapper($storageInterface);
        $actualData = $dataMapper->mapGiroStatus('Pending Internal Processing');

        $this->assertEquals($expectedData, $actualData);
    }

    public function testMapGiroStatusWithStatusAsPendingTermination()
    {
        $storageInterfaceProphecy = $this->prophesize(StorageInterface::class);
        $storageInterface = $storageInterfaceProphecy->reveal();

        $expectedData = 'PENDING_TERMINATION';

        $dataMapper = new DataMapper($storageInterface);
        $actualData = $dataMapper->mapGiroStatus('Pending Termination');

        $this->assertEquals($expectedData, $actualData);
    }

    public function testMapGiroStatusWithStatusAsRejected()
    {
        $storageInterfaceProphecy = $this->prophesize(StorageInterface::class);
        $storageInterface = $storageInterfaceProphecy->reveal();

        $expectedData = 'REJECTED';

        $dataMapper = new DataMapper($storageInterface);
        $actualData = $dataMapper->mapGiroStatus('Rejected');

        $this->assertEquals($expectedData, $actualData);
    }

    public function testMapGiroStatusWithStatusAsTerminated()
    {
        $storageInterfaceProphecy = $this->prophesize(StorageInterface::class);
        $storageInterface = $storageInterfaceProphecy->reveal();

        $expectedData = 'TERMINATED';

        $dataMapper = new DataMapper($storageInterface);
        $actualData = $dataMapper->mapGiroStatus('Terminated');

        $this->assertEquals($expectedData, $actualData);
    }

    public function testMapGiroStatusWithStatusAsWithdrawn()
    {
        $storageInterfaceProphecy = $this->prophesize(StorageInterface::class);
        $storageInterface = $storageInterfaceProphecy->reveal();

        $expectedData = 'WITHDRAWN';

        $dataMapper = new DataMapper($storageInterface);
        $actualData = $dataMapper->mapGiroStatus('Withdrawn');

        $this->assertEquals($expectedData, $actualData);
    }

    public function testMapGiroStatusWithStatusAsNull()
    {
        $storageInterfaceProphecy = $this->prophesize(StorageInterface::class);
        $storageInterface = $storageInterfaceProphecy->reveal();

        $expectedData = null;

        $dataMapper = new DataMapper($storageInterface);
        $actualData = $dataMapper->mapGiroStatus(null);

        $this->assertEquals($expectedData, $actualData);
    }

    public function testMapAttachmentWithContentPathAndURIAsNull()
    {
        $attachmentProphecy = $this->prophesize(DigitalDocument::class);
        $attachmentProphecy->getContentPath()->willReturn('testContentPath');
        $attachment = $attachmentProphecy->reveal();

        $storageInterfaceProphecy = $this->prophesize(StorageInterface::class);
        $storageInterfaceProphecy->resolveUri($attachment, 'contentFile')->willReturn(null);
        $storageInterface = $storageInterfaceProphecy->reveal();

        $expectedData = [];

        $dataMapper = new DataMapper($storageInterface);
        $actualData = $dataMapper->mapAttachment($attachment);

        $this->assertEquals($expectedData, $actualData);
    }
}
