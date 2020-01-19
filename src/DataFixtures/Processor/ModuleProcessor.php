<?php

declare(strict_types=1);

namespace App\DataFixtures\Processor;

use App\Entity\Module;

// use App\Enum\ModuleCategory;
// use App\Enum\ModuleType;
// use Doctrine\ORM\EntityManagerInterface;
// use Fidry\AliceDataFixtures\ProcessorInterface;

class ModuleProcessor extends Module
{
}

// @todo comment out to make phpstan happy for now! We will come back later!
// class ModuleProcessor extends Module implements ProcessorInterface
// {
//     /**
//      * @var EntityManagerInterface
//      */
//     private $entityManager;

//     /**
//      * @param EntityManagerInterface $entityManager
//      */
//     public function __construct(EntityManagerInterface $entityManager)
//     {
//         parent::__construct();
//         $this->entityManager = $entityManager;
//     }

//     public function preProcess(string $id, $object): void
//     {
//         if (!$object instanceof Module) {
//             return;
//         }

//         // $report = new Module();
//         // $report->setName(new ModuleType(ModuleType::REPORT));
//         // $report->setDescription($object->getDescription().' Report');
//         // $report->setCategory(new ModuleCategory(ModuleCategory::CRM));
//         // $report->setEnabled(true);

//         $configuration = new Module();
//         $configuration->setName(new ModuleType(ModuleType::CONFIGURATION));
//         $configuration->setDescription($object->getDescription().' Configuration');
//         $configuration->setCategory(new ModuleCategory(ModuleCategory::CRM));
//         $configuration->setEnabled(true);

//         $template = new Module();
//         $template->setName(new ModuleType(ModuleType::TEMPLATE));
//         $template->setDescription($object->getDescription().' Template');
//         $template->setCategory(new ModuleCategory(ModuleCategory::CRM));
//         $template->setEnabled(true);

//         //assign report sub module to each required main module
//         switch ($object->getName()->getValue()) {
//             case ModuleType::AFFILIATE_PROGRAM_MANAGEMENT:
//                 $this->entityManager->persist($configuration);
//                 $this->entityManager->flush();
//                 $object->addChild($configuration);
//                 break;
//             case ModuleType::APPLICATION_REQUEST_MANAGEMENT:
//                 $this->entityManager->persist($configuration);
//                 $this->entityManager->persist($report);
//                 $this->entityManager->flush();
//                 $object->addChild($configuration);
//                 $object->addChild($report);
//                 break;
//             case ModuleType::CASE_MANAGEMENT:
//                 $this->entityManager->persist($configuration);
//                 $this->entityManager->flush();
//                 $object->addChild($configuration);
//                 break;
//             case ModuleType::LEAD_MANAGEMENT:
//                 $this->entityManager->persist($configuration);
//                 $this->entityManager->persist($report);
//                 $this->entityManager->flush();
//                 $object->addChild($configuration);
//                 $object->addChild($report);
//                 break;
//             case ModuleType::LOYALTY_MANAGEMENT:
//                 $this->entityManager->persist($configuration);
//                 $this->entityManager->flush();
//                 $object->addChild($configuration);
//                 break;
//             case ModuleType::TARIFF_RATE_MANAGEMENT:
//                 $this->entityManager->persist($configuration);
//                 $this->entityManager->persist($report);
//                 $this->entityManager->persist($template);
//                 $this->entityManager->flush();
//                 $object->addChild($configuration);
//                 $object->addChild($report);
//                 $object->addChild($template);
//                 break;
//             case ModuleType::CAMPAIGN_MANAGEMENT:
//                 $this->entityManager->persist($configuration);
//                 $this->entityManager->persist($report);
//                 $this->entityManager->flush();
//                 $object->addChild($configuration);
//                 $object->addChild($report);
//                 break;
//             case ModuleType::QUOTATION_MANAGEMENT:
//                 $this->entityManager->persist($configuration);
//                 $this->entityManager->persist($report);
//                 $this->entityManager->persist($template);
//                 $this->entityManager->flush();
//                 $object->addChild($configuration);
//                 $object->addChild($report);
//                 $object->addChild($template);
//                 break;
//         }
//     }

//     public function postProcess(string $id, $object): void
//     {
//         if (!$object instanceof Module) {
//             return;
//         }

//         $module = $this->entityManager->getRepository(Module::class)->createQueryBuilder('md');

//         $administrationSubModules = [ModuleType::AUTHORIZATION_MANAGEMENT, ModuleType::USER_MANAGEMENT, ModuleType::CONFIGURATION_MANAGEMENT];
//         $saleSubModules = [ModuleType::APPLICATION_REQUEST_MANAGEMENT, ModuleType::PARTNERSHIP_MANAGEMENT, ModuleType::QUOTATION_MANAGEMENT];
//         $serviceSubModules = [ModuleType::CUSTOMER_MANAGEMENT, ModuleType::CASE_MANAGEMENT];
//         $marketingSubModules = [ModuleType::LEAD_MANAGEMENT, ModuleType::TARIFF_RATE_MANAGEMENT, ModuleType::CAMPAIGN_MANAGEMENT, ModuleType::AFFILIATE_PROGRAM_MANAGEMENT, ModuleType::LOYALTY_MANAGEMENT];
//         $customerPortalSubModules = [ModuleType::CUSTOMER_PORTAL_MANAGEMENT];

//         $authorizationManagementSubModules = [ModuleType::ROLE, ModuleType::PROFILE, ModuleType::DEPARTMENT];
//         $userManagementSubModules = [ModuleType::ADMINISTRATOR];
//         $configurationManagementSubModules = [ModuleType::GLOBAL_SETTING];
//         $applicationRequestSubModules = [ModuleType::APPLICATION_REQUEST];
//         $partnershipManagementSubModules = [ModuleType::PARTNERSHIP, ModuleType::COMMISSION_RATE];
//         $quotationManagementSubModules = [ModuleType::QUOTATION];
//         $customerManagementSubModules = [ModuleType::CUSTOMER];
//         $caseManagementSubModules = [ModuleType::CASE];
//         $leadManagementSubModules = [ModuleType::LEAD, ModuleType::LIST_MANAGEMENT];
//         $tariffRateManagementSubModules = [ModuleType::TARIFF_RATE];
//         $campaignManagementSubModules = [ModuleType::CAMPAIGN, ModuleType::SOURCE_BUILDER, ModuleType::MAIL_BUILDER, ModuleType::SMS_BUILDER, ModuleType::UNSUBSCRIBE];
//         $affiliateProgramManagementSubModules = [ModuleType::AFFILIATE_PROGRAM];
//         $customerPortalManagementSubModules = [ModuleType::USER];

//         switch ($object->getName()->getValue()) {
//             case ModuleType::ADMINISTRATION:
//                 $subModules = $module->select('md')
//                     ->where(
//                         $module->expr()->in('md.name', ':name')
//                     )
//                     ->setParameters([
//                         'name' => $administrationSubModules,
//                     ])
//                     ->getQuery()
//                     ->getResult();
//                 foreach ($subModules as $subModule) {
//                     $object->addChild($subModule);
//                 }
//                 break;
//             case ModuleType::SALE:
//                 $subModules = $module->select('md')
//                     ->where(
//                         $module->expr()->in('md.name', ':name')
//                     )
//                     ->setParameters([
//                         'name' => $saleSubModules,
//                     ])
//                     ->getQuery()
//                     ->getResult();
//                 foreach ($subModules as $subModule) {
//                     $object->addChild($subModule);
//                 }
//                 break;
//             case ModuleType::SERVICE:
//                 $subModules = $module->select('md')
//                     ->where(
//                         $module->expr()->in('md.name', ':name')
//                     )
//                     ->setParameters([
//                         'name' => $serviceSubModules,
//                     ])
//                     ->getQuery()
//                     ->getResult();
//                 foreach ($subModules as $subModule) {
//                     $object->addChild($subModule);
//                 }
//                 break;
//             case ModuleType::MARKETING:
//                 $subModules = $module->select('md')
//                     ->where(
//                         $module->expr()->in('md.name', ':name')
//                     )
//                     ->setParameters([
//                         'name' => $marketingSubModules,
//                     ])
//                     ->getQuery()
//                     ->getResult();
//                 foreach ($subModules as $subModule) {
//                     $object->addChild($subModule);
//                 }
//                 break;
//             case ModuleType::CUSTOMER_PORTAL:
//                 $subModules = $module->select('md')
//                     ->where(
//                         $module->expr()->in('md.name', ':name')
//                     )
//                     ->setParameters([
//                         'name' => $customerPortalSubModules,
//                     ])
//                     ->getQuery()
//                     ->getResult();
//                 foreach ($subModules as $subModule) {
//                     $object->addChild($subModule);
//                 }
//                 break;
//             case ModuleType::AUTHORIZATION_MANAGEMENT:
//                 $subModules = $module->select('md')
//                     ->where(
//                         $module->expr()->in('md.name', ':name')
//                     )
//                     ->setParameters([
//                         'name' => $authorizationManagementSubModules,
//                     ])
//                     ->getQuery()
//                     ->getResult();
//                 foreach ($subModules as $subModule) {
//                     $object->addChild($subModule);
//                 }
//                 break;
//             case ModuleType::USER_MANAGEMENT:
//                 $subModules = $module->select('md')
//                     ->where(
//                         $module->expr()->in('md.name', ':name')
//                     )
//                     ->setParameters([
//                         'name' => $userManagementSubModules,
//                     ])
//                     ->getQuery()
//                     ->getResult();
//                 foreach ($subModules as $subModule) {
//                     $object->addChild($subModule);
//                 }
//                 break;
//             case ModuleType::CONFIGURATION_MANAGEMENT:
//                 $subModules = $module->select('md')
//                     ->where(
//                         $module->expr()->in('md.name', ':name')
//                     )
//                     ->setParameters([
//                         'name' => $configurationManagementSubModules,
//                     ])
//                     ->getQuery()
//                     ->getResult();
//                 foreach ($subModules as $subModule) {
//                     $object->addChild($subModule);
//                 }
//                 break;
//             case ModuleType::APPLICATION_REQUEST_MANAGEMENT:
//                 $subModules = $module->select('md')
//                     ->where(
//                         $module->expr()->in('md.name', ':name')
//                     )
//                     ->setParameters([
//                         'name' => $applicationRequestSubModules,
//                     ])
//                     ->getQuery()
//                     ->getResult();
//                 foreach ($subModules as $subModule) {
//                     $object->addChild($subModule);
//                 }
//                 break;
//             case ModuleType::PARTNERSHIP_MANAGEMENT:
//                 $subModules = $module->select('md')
//                     ->where(
//                         $module->expr()->in('md.name', ':name')
//                     )
//                     ->setParameters([
//                         'name' => $partnershipManagementSubModules,
//                     ])
//                     ->getQuery()
//                     ->getResult();
//                 foreach ($subModules as $subModule) {
//                     $object->addChild($subModule);
//                 }
//                 break;
//             case ModuleType::QUOTATION_MANAGEMENT:
//                 $subModules = $module->select('md')
//                     ->where(
//                         $module->expr()->in('md.name', ':name')
//                     )
//                     ->setParameters([
//                         'name' => $quotationManagementSubModules,
//                     ])
//                     ->getQuery()
//                     ->getResult();
//                 foreach ($subModules as $subModule) {
//                     $object->addChild($subModule);
//                 }
//                 break;
//             case ModuleType::CUSTOMER_MANAGEMENT:
//                 $subModules = $module->select('md')
//                     ->where(
//                         $module->expr()->in('md.name', ':name')
//                     )
//                     ->setParameters([
//                         'name' => $customerManagementSubModules,
//                     ])
//                     ->getQuery()
//                     ->getResult();
//                 foreach ($subModules as $subModule) {
//                     $object->addChild($subModule);
//                 }
//                 break;
//             case ModuleType::CASE_MANAGEMENT:
//                 $subModules = $module->select('md')
//                     ->where(
//                         $module->expr()->in('md.name', ':name')
//                     )
//                     ->setParameters([
//                         'name' => $caseManagementSubModules,
//                     ])
//                     ->getQuery()
//                     ->getResult();
//                 foreach ($subModules as $subModule) {
//                     $object->addChild($subModule);
//                 }
//                 break;
//             case ModuleType::LEAD_MANAGEMENT:
//                 $subModules = $module->select('md')
//                     ->where(
//                         $module->expr()->in('md.name', ':name')
//                     )
//                     ->setParameters([
//                         'name' => $leadManagementSubModules,
//                     ])
//                     ->getQuery()
//                     ->getResult();
//                 foreach ($subModules as $subModule) {
//                     $object->addChild($subModule);
//                 }
//                 break;
//             case ModuleType::TARIFF_RATE_MANAGEMENT:
//                 $subModules = $module->select('md')
//                     ->where(
//                         $module->expr()->in('md.name', ':name')
//                     )
//                     ->setParameters([
//                         'name' => $tariffRateManagementSubModules,
//                     ])
//                     ->getQuery()
//                     ->getResult();
//                 foreach ($subModules as $subModule) {
//                     $object->addChild($subModule);
//                 }
//                 break;
//             case ModuleType::CAMPAIGN_MANAGEMENT:
//                 $subModules = $module->select('md')
//                     ->where(
//                         $module->expr()->in('md.name', ':name')
//                     )
//                     ->setParameters([
//                         'name' => $campaignManagementSubModules,
//                     ])
//                     ->getQuery()
//                     ->getResult();
//                 foreach ($subModules as $subModule) {
//                     $object->addChild($subModule);
//                 }
//                 break;
//             case ModuleType::AFFILIATE_PROGRAM_MANAGEMENT:
//                 $subModules = $module->select('md')
//                     ->where(
//                         $module->expr()->in('md.name', ':name')
//                     )
//                     ->setParameters([
//                         'name' => $affiliateProgramManagementSubModules,
//                     ])
//                     ->getQuery()
//                     ->getResult();
//                 foreach ($subModules as $subModule) {
//                     $object->addChild($subModule);
//                 }
//                 break;
//             case ModuleType::CUSTOMER_PORTAL_MANAGEMENT:
//                 $subModules = $module->select('md')
//                     ->where(
//                         $module->expr()->in('md.name', ':name')
//                     )
//                     ->setParameters([
//                         'name' => $customerPortalManagementSubModules,
//                     ])
//                     ->getQuery()
//                     ->getResult();
//                 foreach ($subModules as $subModule) {
//                     $object->addChild($subModule);
//                 }
//                 break;
//         }
//         $this->entityManager->persist($object);
//         $this->entityManager->flush();
//     }
// }
