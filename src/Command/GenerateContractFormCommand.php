<?php

declare(strict_types=1);

namespace App\Command;

use App\Entity\Contract;
use App\Enum\DocumentType;
use App\Model\ContractApplicationRequestStatusUpdater;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class GenerateContractFormCommand extends Command
{
    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    /**
     * @var ContractApplicationRequestStatusUpdater
     */
    private $contractApplicationRequestStatusUpdater;

    /**
     * @param EntityManagerInterface                  $entityManager
     * @param ContractApplicationRequestStatusUpdater $contractApplicationRequestStatusUpdater
     */
    public function __construct(EntityManagerInterface $entityManager, ContractApplicationRequestStatusUpdater $contractApplicationRequestStatusUpdater)
    {
        parent::__construct();

        $this->entityManager = $entityManager;
        $this->contractApplicationRequestStatusUpdater = $contractApplicationRequestStatusUpdater;
    }

    /**
     * {@inheritdoc}
     */
    protected function configure(): void
    {
        $this
            ->setName('app:generate:contract-form')
            ->setDescription('Creates a contract form.')
            ->addOption('id', null, InputOption::VALUE_REQUIRED, 'Create for which contract (id)', null)
        ;
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output): ?int
    {
        $io = new SymfonyStyle($input, $output);

        $id = $input->getOption('id');

        $contract = $this->entityManager->getRepository(Contract::class)->findOneBy(['contractNumber' => $id]);

        if (null === $contract) {
            $contract = $this->entityManager->getRepository(Contract::class)->find($id);
        }

        if (null !== $contract) {
            $io->success(\sprintf('Contract #%s found.', $id));
            $contractForm = $this->contractApplicationRequestStatusUpdater->getContractForm($contract);

            if (null !== $contractForm) {
                foreach ($contract->getFiles() as $file) {
                    if (DocumentType::CUSTOMER_CONTRACT_FORM === $file->getType()->getValue()) {
                        $contract->removeFile($file);
                    }
                }
                $contract->addFile($contractForm);

                $this->entityManager->persist($contract);
                $this->entityManager->flush();

                $io->success(\sprintf('Contract Form generated for #%s.', $id));
            } else {
                $io->text('No Contract Form generated.');
            }
        } else {
            $io->error('Contract not found');
        }

        return 0;
    }
}
