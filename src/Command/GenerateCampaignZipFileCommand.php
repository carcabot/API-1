<?php

declare(strict_types=1);

namespace App\Command;

use App\Entity\Campaign;
use App\Entity\DirectMailCampaignSourceListItem;
use App\Model\DirectMailCampaignFileGenerator;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class GenerateCampaignZipFileCommand extends Command
{
    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    /**
     * @var DirectMailCampaignFileGenerator
     */
    private $directMailCampaignFileGenerator;

    /**
     * @param EntityManagerInterface          $entityManager
     * @param DirectMailCampaignFileGenerator $directMailCampaignFileGenerator
     */
    public function __construct(EntityManagerInterface $entityManager, DirectMailCampaignFileGenerator $directMailCampaignFileGenerator)
    {
        parent::__construct();

        $this->entityManager = $entityManager;
        $this->directMailCampaignFileGenerator = $directMailCampaignFileGenerator;
    }

    /**
     * {@inheritdoc}
     */
    protected function configure(): void
    {
        $this
            ->setName('app:generate:direct-mail-campaign-zip')
            ->setDescription('Creates the zip file for a direct mail campaign.')
            ->addOption('id', null, InputOption::VALUE_REQUIRED, 'Create for which campaign (id)', null)
        ;
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output): ?int
    {
        $io = new SymfonyStyle($input, $output);

        $id = $input->getOption('id');

        $campaign = $this->entityManager->getRepository(Campaign::class)->findOneBy(['campaignNumber' => (string) $id]);

        if (null === $campaign) {
            $campaign = $this->entityManager->getRepository(Campaign::class)->find((int) $id);
        }

        if (null !== $campaign) {
            $io->success(\sprintf('Campaign #%s found.', $id));
            $filePaths = [];

            $io->section('Generating pdfs ...');

            $progressBar = new ProgressBar($output);
            $startPdfTime = \microtime(true);
            foreach ($campaign->getRecipientLists() as $recipientList) {
                foreach ($recipientList->getItemListElement() as $recipientListItem) {
                    if ($recipientListItem instanceof DirectMailCampaignSourceListItem) {
                        $filePaths[] = $this->directMailCampaignFileGenerator->generatePdf($campaign, $recipientListItem);
                        $progressBar->advance();
                    }
                }
            }
            $endPdfTime = \microtime(true);
            $progressBar->finish();
            $io->comment(\sprintf('Generated all %d PDFs in %s seconds.', \count($filePaths), $endPdfTime - $startPdfTime));

            $io->section('Generating zip file ...');
            $startZipTime = \microtime(true);
            $internalDocumentZipFile = $this->directMailCampaignFileGenerator->generateInternalDocumentZip($filePaths, $campaign);
            $endZipTime = \microtime(true);

            if (null !== $internalDocumentZipFile) {
                $io->comment(\sprintf('Generated zip file in %s seconds.', $endZipTime - $startZipTime));
                $campaign->setFile($internalDocumentZipFile);

                $this->entityManager->persist($campaign);
                $this->entityManager->flush();

                $io->success(\sprintf('Campaign zip file generated for #%s.', $id));
            } else {
                $io->text('No zip file generated.');
            }
        } else {
            $io->error('Campaign not found');
        }

        return 0;
    }
}
