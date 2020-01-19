<?php

declare(strict_types=1);

namespace App\Command;

use App\Entity\ApplicationRequest;
use App\Model\AuthorizationLetterFileGenerator;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class GenerateAuthorizationLetterFileCommand extends Command
{
    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    /**
     * @var AuthorizationLetterFileGenerator
     */
    private $authorizationLetterFileGenerator;

    /**
     * @param EntityManagerInterface           $entityManager
     * @param AuthorizationLetterFileGenerator $authorizationLetterFileGenerator
     */
    public function __construct(EntityManagerInterface $entityManager, AuthorizationLetterFileGenerator $authorizationLetterFileGenerator)
    {
        parent::__construct();

        $this->authorizationLetterFileGenerator = $authorizationLetterFileGenerator;
        $this->entityManager = $entityManager;
    }

    /**
     * {@inheritdoc}
     */
    protected function configure(): void
    {
        $this
            ->setName('app:generate:authorization-letter')
            ->setDescription('Creates an authorization letter.')
            ->addOption('id', null, InputOption::VALUE_REQUIRED, 'Create for which application request (id)', null)
        ;
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output): ?int
    {
        $io = new SymfonyStyle($input, $output);

        $id = $input->getOption('id');

        $applicationRequest = $this->entityManager->getRepository(ApplicationRequest::class)->findOneBy(['applicationRequestNumber' => (string) $id]);

        if (null === $applicationRequest) {
            $applicationRequest = $this->entityManager->getRepository(ApplicationRequest::class)->find((int) $id);
        }

        if (null !== $applicationRequest) {
            $io->success(\sprintf('Application Request #%s found.', $id));
            $authorizationLetterFilePath = $this->authorizationLetterFileGenerator->generatePdf($applicationRequest);
            $authorizationLetter = $this->authorizationLetterFileGenerator->convertFileToDigitalDocument($authorizationLetterFilePath);

            if (null !== $authorizationLetter) {
                $applicationRequest->addSupplementaryFile($authorizationLetter);

                $this->entityManager->persist($applicationRequest);
                $this->entityManager->flush();

                $io->success(\sprintf('Authorization Letter generated for #%s.', $id));
            } else {
                $io->text('No Authorization Letter generated.');
            }
        } else {
            $io->error('Application Request not found');
        }

        return 0;
    }
}
