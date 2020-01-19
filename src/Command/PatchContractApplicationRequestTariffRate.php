<?php

declare(strict_types=1);

namespace App\Command;

use App\Entity\AddonService;
use App\Entity\ApplicationRequest;
use App\Entity\TariffRate;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class PatchContractApplicationRequestTariffRate extends Command
{
    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @param EntityManagerInterface $entityManager
     * @param LoggerInterface        $logger
     */
    public function __construct(EntityManagerInterface $entityManager, LoggerInterface $logger)
    {
        parent::__construct();

        $this->entityManager = $entityManager;
        $this->logger = $logger;
    }

    /**
     * {@inheritdoc}
     */
    protected function configure(): void
    {
        $this
            ->setName('app:patch:contract-application-tariff-rate')
            ->setDescription('Update existing application request.')
            ->addOption('ids', null, InputOption::VALUE_REQUIRED, 'Application Request to be updated along with corresponding contract.', null)
            ->addOption('tariff-rate', null, InputOption::VALUE_OPTIONAL, 'Tariff Rate to be used.', null)
            ->addOption('addons', null, InputOption::VALUE_OPTIONAL, 'AddonService to be added.', null)
            ->setHelp(<<<'EOF'
The %command.name% command patches the existing application request and/or contract with the new tariff rate data.
EOF
            )
        ;
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output): ?int
    {
        $io = new SymfonyStyle($input, $output);
        $ids = $input->getOption('ids');
        $addons = $input->getOption('addons');
        $tariffRate = $input->getOption('tariff-rate');
        $applicationRequestIds = [];
        $addonIds = [];
        $baseTariffRate = null;

        if (null !== $ids) {
            $applicationRequestIds = \array_unique(\explode(',', (string) $ids));
        }

        if (null !== $addons) {
            $addonIds = \array_unique(\explode(',', (string) $addons));
        }

        if (null !== $tariffRate) {
            $baseTariffRate = $this->entityManager->getRepository(TariffRate::class)->findOneBy(['tariffRateNumber' => $tariffRate, 'isBasedOn' => null]);

            if (null === $baseTariffRate) {
                $baseTariffRate = $this->entityManager->getRepository(TariffRate::class)->find((int) $tariffRate);
            }
        }

        foreach ($applicationRequestIds as $id) {
            $applicationRequest = $this->entityManager->getRepository(ApplicationRequest::class)->findOneBy(['applicationRequestNumber' => $id]);

            if (null === $applicationRequest) {
                $applicationRequest = $this->entityManager->getRepository(ApplicationRequest::class)->find((int) $id);
            }

            $addonServices = [];
            $clonedAddonServices = [];
            if (\count($addonIds) > 0) {
                $addonServices = $this->entityManager->getRepository(AddonService::class)->findBy(['id' => $addonIds]);
            }

            if (null !== $applicationRequest) {
                $oldTariffRate = $applicationRequest->getTariffRate();

                if (null !== $oldTariffRate) {
                    if (null === $baseTariffRate) {
                        $baseTariffRate = $oldTariffRate->getIsBasedOn();
                    }

                    if (null !== $baseTariffRate) {
                        $newTariffRate = clone $baseTariffRate;
                        $newTariffRate->setIsBasedOn($baseTariffRate);

                        $this->entityManager->persist($newTariffRate);

                        $applicationRequest->setTariffRate($newTariffRate);
                        $applicationRequest->clearAddonServices();

                        foreach ($addonServices as $addonService) {
                            $clone = clone $addonService;
                            $clone->setIsBasedOn($addonService);
                            $this->entityManager->persist($clone);

                            $clonedAddonServices[] = $clone;
                        }

                        if (\count($clonedAddonServices) > 0) {
                            foreach ($clonedAddonServices as $clonedAddonService) {
                                $applicationRequest->addAddonService($clonedAddonService);
                            }
                        }

                        if (null !== $applicationRequest->getContract()) {
                            $contract = $applicationRequest->getContract();
                            $contract->setTariffRate($newTariffRate);
                            $contract->clearAddonServices();

                            if (\count($clonedAddonServices) > 0) {
                                foreach ($clonedAddonServices as $clonedAddonService) {
                                    $contract->addAddonService($clonedAddonService);
                                }
                            }

                            $this->entityManager->persist($contract);
                        }

                        // don't delete if parent
                        if (null !== $oldTariffRate->getIsBasedOn()) {
                            $this->entityManager->remove($oldTariffRate);
                        }
                        $this->entityManager->flush();
                    }
                }
            }
        }

        return 0;
    }
}
