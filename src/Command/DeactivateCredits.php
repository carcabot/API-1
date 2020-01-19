<?php

declare(strict_types=1);

namespace App\Command;

use App\Domain\Command\Contract\UpdatePointCreditsActions;
use App\Domain\Command\UpdateCreditsAction\UpdateTransaction;
use App\Entity\ApplicationRequest;
use App\Entity\Contract;
use App\Entity\DeactivateContractCreditsAction;
use App\Enum\ActionStatus;
use Doctrine\ORM\EntityManagerInterface;
use League\Tactician\CommandBus;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class DeactivateCredits extends Command
{
    /**
     * @var CommandBus
     */
    private $commandBus;

    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    /**
     * @param CommandBus             $commandBus
     * @param EntityManagerInterface $entityManager
     */
    public function __construct(CommandBus $commandBus, EntityManagerInterface $entityManager)
    {
        parent::__construct();

        $this->commandBus = $commandBus;
        $this->entityManager = $entityManager;
    }

    /**
     * {@inheritdoc}
     */
    protected function configure(): void
    {
        $this
            ->setName('app:deactivate:contract-credits')
            ->setDescription('Deactivates credits for a specified contract.')
            ->addOption('object', null, InputOption::VALUE_OPTIONAL, 'For which specific contract', null)
            ->addOption('instrument', null, InputOption::VALUE_OPTIONAL, 'The application request responsible for the deactivation', null)
            ->setHelp(<<<'EOF'
The %command.name% command patch referral points.
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

        $objectNumber = $input->getOption('object');
        $instrumentNumber = $input->getOption('instrument');

        $instrument = null;
        $object = $this->entityManager->getRepository(Contract::class)->findOneBy(['contractNumber' => $objectNumber]);

        if (!empty($instrumentNumber)) {
            $instrument = $this->entityManager->getRepository(ApplicationRequest::class)->findOneBy(['applicationRequestNumber' => $instrumentNumber]);
        }

        if (null !== $object) {
            if ($object->getPointCreditsBalance()->getValue() > 0) {
                // deactivate credits
                $deactivateContractCreditAction = new DeactivateContractCreditsAction();
                $deactivateContractCreditAction->setAmount($object->getPointCreditsBalance()->getValue());
                if (null !== $instrument && null !== $instrument->getDateModified()) {
                    $deactivateContractCreditAction->setEndTime($instrument->getDateModified());
                    $deactivateContractCreditAction->setStartTime($instrument->getDateModified());
                }
                $deactivateContractCreditAction->setStatus(new ActionStatus(ActionStatus::COMPLETED));

                $deactivateContractCreditAction->setDescription('Contract Closure');
                $deactivateContractCreditAction->setInstrument($instrument);
                $deactivateContractCreditAction->setObject($object);

                $this->commandBus->handle(new UpdateTransaction($deactivateContractCreditAction));
                $this->commandBus->handle(new UpdatePointCreditsActions($object, $deactivateContractCreditAction));

                $this->entityManager->persist($deactivateContractCreditAction);
                $this->entityManager->flush();
            }
        }

        return 0;
    }
}
