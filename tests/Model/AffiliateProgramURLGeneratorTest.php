<?php

declare(strict_types=1);
/**
 * Created by PhpStorm.
 * User: mufeer
 * Date: 18/4/19
 * Time: 1:42 PM.
 */

namespace App\Tests\Model;

use App\Disque\JobType;
use App\Entity\AffiliateProgram;
use App\Entity\CustomerAccount;
use App\Entity\CustomerAccountAffiliateProgramUrl;
use App\Enum\AffiliateWebServicePartner;
use App\Enum\URLStatus;
use App\Model\AffiliateProgramURLGenerator;
use App\WebService\Affiliate\ClientFactory;
use App\WebService\Affiliate\Provider\TheAffiliateGateway\Client;
use Disque\Queue\Job;
use Disque\Queue\Queue;
use Doctrine\Common\Persistence\ObjectRepository;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;

class AffiliateProgramURLGeneratorTest extends TestCase
{
    public function testGenerateAffiliateProgramURLWithAffiliateProgramProviderAsLAZADA_HASOFFERS()
    {
        $now = new \DateTime();

        $customerAccountAffiliateProgramURLProphecy = $this->prophesize(CustomerAccountAffiliateProgramUrl::class);
        $customerAccountAffiliateProgramURLProphecy->getStatus()->willReturn(new URLStatus(URLStatus::ACTIVE));
        $customerAccountAffiliateProgramURLProphecy->getUrl()->willReturn('www.testUrl.com');
        $customerAccountAffiliateProgramURL = $customerAccountAffiliateProgramURLProphecy->reveal();

        $customerAccountProphecy = $this->prophesize(CustomerAccount::class);
        $customerAccountProphecy->getId()->willReturn(123456);
        $customerAccount = $customerAccountProphecy->reveal();

        $affiliateProgramProphecy = $this->prophesize(AffiliateProgram::class);
        $affiliateProgramProphecy->getId()->willReturn(123456);
        $affiliateProgramProphecy->getProvider()->willReturn(new AffiliateWebServicePartner(AffiliateWebServicePartner::LAZADA_HASOFFERS));
        $affiliateProgramProphecy->getValidFrom()->willReturn(null);
        $affiliateProgramProphecy->getValidThrough()->willReturn($now->setDate(2020, 10, 31));
        $affiliateProgram = $affiliateProgramProphecy->reveal();

        $objectRepositoryProphecy = $this->prophesize(ObjectRepository::class);
        $objectRepositoryProphecy->findOneBy(['affiliateProgram' => 123456, 'customer' => 123456])->willReturn($customerAccountAffiliateProgramURL);
        $objectRepository = $objectRepositoryProphecy->reveal();

        $entityManagerProphecy = $this->prophesize(EntityManager::class);
        $entityManagerProphecy->getRepository(CustomerAccountAffiliateProgramUrl::class)->shouldBeCalled()->willReturn($objectRepository);
        $entityManager = $entityManagerProphecy->reveal();

        $clientFactoryProphecy = $this->prophesize(ClientFactory::class);
        $clientFactory = $clientFactoryProphecy->reveal();

        $disqueQueueProphecy = $this->prophesize(Queue::class);
        $disqueQueue = $disqueQueueProphecy->reveal();

        $expectedTrackingUrl = 'www.testUrl.com';

        $affiliateProgramUrlGenerator = new AffiliateProgramURLGenerator($clientFactory, $disqueQueue, $entityManager);

        $actualTrackingUrl = $affiliateProgramUrlGenerator->generate($affiliateProgram, $customerAccount);

        $this->assertEquals($expectedTrackingUrl, $actualTrackingUrl);
    }

    public function testGenerateAffiliateProgramURLWithClientFactory()
    {
        $now = new \DateTime();

        $customerAccountAffiliateProgramURLProphecy = $this->prophesize(CustomerAccountAffiliateProgramUrl::class);
        $customerAccountAffiliateProgramURLProphecy->getStatus()->willReturn(new URLStatus(URLStatus::ACTIVE));
        $customerAccountAffiliateProgramURLProphecy->getUrl()->willReturn('www.testUrl.com');
        $customerAccountAffiliateProgramURL = $customerAccountAffiliateProgramURLProphecy->reveal();

        $customerAccountProphecy = $this->prophesize(CustomerAccount::class);
        $customerAccountProphecy->getId()->willReturn(123456);
        $customerAccountProphecy->getAccountNumber()->willReturn('SWCC123456');
        $customerAccount = $customerAccountProphecy->reveal();

        $affiliateProgramProphecy = $this->prophesize(AffiliateProgram::class);
        $affiliateProgramProphecy->getId()->willReturn(123456);
        $affiliateProgramProphecy->getProvider()->willReturn(new AffiliateWebServicePartner(AffiliateWebServicePartner::TAG));
        $affiliateProgramProphecy->getValidFrom()->willReturn(null);
        $affiliateProgramProphecy->getBaseTrackingUrl()->willReturn('www.testBaseTrackingUrl.com');
        $affiliateProgramProphecy->getValidThrough()->willReturn($now->setDate(2020, 10, 31));
        $affiliateProgram = $affiliateProgramProphecy->reveal();

        $entityManagerProphecy = $this->prophesize(EntityManager::class);
        $entityManager = $entityManagerProphecy->reveal();

        $clientProphecy = $this->prophesize(Client::class);
        $clientProphecy->generateTrackingUrl('www.testBaseTrackingUrl.com', ['customerAccountNumber' => 'SWCC123456'])->willReturn('www.testBaseTrackingUrl.com');
        $client = $clientProphecy->reveal();

        $clientFactoryProphecy = $this->prophesize(ClientFactory::class);
        $clientFactoryProphecy->getClient('TAG')->willReturn($client);
        $clientFactory = $clientFactoryProphecy->reveal();

        $disqueQueueProphecy = $this->prophesize(Queue::class);
        $disqueQueue = $disqueQueueProphecy->reveal();

        $expectedTrackingUrl = 'www.testBaseTrackingUrl.com';

        $affiliateProgramUrlGenerator = new AffiliateProgramURLGenerator($clientFactory, $disqueQueue, $entityManager);

        $actualTrackingUrl = $affiliateProgramUrlGenerator->generate($affiliateProgram, $customerAccount);

        $this->assertEquals($expectedTrackingUrl, $actualTrackingUrl);
    }

    public function testGenerateAffiliateProgramURLWithValidFromGreaterThanCurrentTimeAndNotNull()
    {
        $now = new \DateTime();

        $customerAccountProphecy = $this->prophesize(CustomerAccount::class);
        $customerAccountProphecy->getId()->willReturn(123456);
        $customerAccountProphecy->getAccountNumber()->willReturn('SWCC123456');
        $customerAccount = $customerAccountProphecy->reveal();

        $affiliateProgramProphecy = $this->prophesize(AffiliateProgram::class);
        $affiliateProgramProphecy->getId()->willReturn(123456);
        $affiliateProgramProphecy->getProvider()->willReturn(null);
        $affiliateProgramProphecy->getValidFrom()->willReturn($now->setDate(2020, 10, 31));
        $affiliateProgramProphecy->getBaseTrackingUrl()->willReturn('www.testBaseTrackingUrl.com');
        $affiliateProgramProphecy->getValidThrough()->willReturn(null);
        $affiliateProgram = $affiliateProgramProphecy->reveal();

        $entityManagerProphecy = $this->prophesize(EntityManager::class);
        $entityManager = $entityManagerProphecy->reveal();

        $clientFactoryProphecy = $this->prophesize(ClientFactory::class);
        $clientFactory = $clientFactoryProphecy->reveal();

        $disqueQueueProphecy = $this->prophesize(Queue::class);
        $disqueQueue = $disqueQueueProphecy->reveal();

        $expectedTrackingUrl = '';

        $affiliateProgramUrlGenerator = new AffiliateProgramURLGenerator($clientFactory, $disqueQueue, $entityManager);

        $actualTrackingUrl = $affiliateProgramUrlGenerator->generate($affiliateProgram, $customerAccount);

        $this->assertEquals($expectedTrackingUrl, $actualTrackingUrl);
    }

    public function testGenerateAffiliateProgramURLWithValidThroughLessThanCurrentTimeAndNotNull()
    {
        $now = new \DateTime();

        $customerAccountProphecy = $this->prophesize(CustomerAccount::class);
        $customerAccountProphecy->getId()->willReturn(123456);
        $customerAccountProphecy->getAccountNumber()->willReturn('SWCC123456');
        $customerAccount = $customerAccountProphecy->reveal();

        $affiliateProgramProphecy = $this->prophesize(AffiliateProgram::class);
        $affiliateProgramProphecy->getId()->willReturn(123456);
        $affiliateProgramProphecy->getProvider()->willReturn(null);
        $affiliateProgramProphecy->getValidFrom()->willReturn(null);
        $affiliateProgramProphecy->getBaseTrackingUrl()->willReturn('www.testBaseTrackingUrl.com');
        $affiliateProgramProphecy->getValidThrough()->willReturn($now->setDate(2018, 10, 31));
        $affiliateProgram = $affiliateProgramProphecy->reveal();

        $entityManagerProphecy = $this->prophesize(EntityManager::class);
        $entityManager = $entityManagerProphecy->reveal();

        $clientFactoryProphecy = $this->prophesize(ClientFactory::class);
        $clientFactory = $clientFactoryProphecy->reveal();

        $disqueQueueProphecy = $this->prophesize(Queue::class);
        $disqueQueue = $disqueQueueProphecy->reveal();

        $expectedTrackingUrl = '';

        $affiliateProgramUrlGenerator = new AffiliateProgramURLGenerator($clientFactory, $disqueQueue, $entityManager);

        $actualTrackingUrl = $affiliateProgramUrlGenerator->generate($affiliateProgram, $customerAccount);

        $this->assertEquals($expectedTrackingUrl, $actualTrackingUrl);
    }

    public function testGenerateAffiliateProgramURLWithCustomerAffiliateProgramUrlAsNull()
    {
        $now = new \DateTime();

        $customerAccountAffiliateProgramURLProphecy = $this->prophesize(CustomerAccountAffiliateProgramUrl::class);
        $customerAccountAffiliateProgramURLProphecy->getUrl()->willReturn('www.testUrl.com');
        $customerAccountAffiliateProgramURL = $customerAccountAffiliateProgramURLProphecy->reveal();

        $customerAccountProphecy = $this->prophesize(CustomerAccount::class);
        $customerAccountProphecy->getId()->willReturn(123456);
        $customerAccount = $customerAccountProphecy->reveal();

        $affiliateProgramProphecy = $this->prophesize(AffiliateProgram::class);
        $affiliateProgramProphecy->getId()->willReturn(123456);
        $affiliateProgramProphecy->getProvider()->willReturn(new AffiliateWebServicePartner(AffiliateWebServicePartner::LAZADA_HASOFFERS));
        $affiliateProgramProphecy->getValidFrom()->willReturn(null);
        $affiliateProgramProphecy->getValidThrough()->willReturn($now->setDate(2020, 10, 31));
        $affiliateProgram = $affiliateProgramProphecy->reveal();

        $objectRepositoryProphecy = $this->prophesize(ObjectRepository::class);
        $objectRepositoryProphecy->findOneBy(['affiliateProgram' => 123456, 'customer' => 123456])->willReturn(null);
        $objectRepository = $objectRepositoryProphecy->reveal();

        $entityManagerProphecy = $this->prophesize(EntityManagerInterface::class);
        $entityManagerProphecy->getRepository(CustomerAccountAffiliateProgramUrl::class)->shouldBeCalled()->willReturn($objectRepository);
        $entityManager = $entityManagerProphecy->reveal();

        $clientFactoryProphecy = $this->prophesize(ClientFactory::class);
        $clientFactory = $clientFactoryProphecy->reveal();

        $disqueJobProphecy = $this->prophesize(Job::class);
        $disqueJob = $disqueJobProphecy->reveal();

        $disqueQueueProphecy = $this->prophesize(Queue::class);
        $disqueQueueProphecy->push(new Job([
            'data' => [
                'affiliateProgram' => 123456,
                'customer' => 123456,
                ],
            'type' => JobType::AFFILIATE_PROGRAM_GENERATE_URL, ]))->shouldBeCalled();
        $disqueQueue = $disqueQueueProphecy->reveal();

        $expectedTrackingUrl = '';

        $affiliateProgramUrlGenerator = new AffiliateProgramURLGenerator($clientFactory, $disqueQueue, $entityManager);

        $actualTrackingUrl = $affiliateProgramUrlGenerator->generate($affiliateProgram, $customerAccount);

        $this->assertEquals($expectedTrackingUrl, $actualTrackingUrl);
    }
}
