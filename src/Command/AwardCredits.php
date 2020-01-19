<?php

declare(strict_types=1);

namespace App\Command;

use App\Domain\Command\Contract\UpdatePointCreditsActions as UpdateContractCreditsAction;
use App\Domain\Command\CustomerAccount\UpdatePointCreditsActions as UpdateCustomerCreditsAction;
use App\Domain\Command\UpdateCreditsAction\UpdateTransaction;
use App\Entity\Contract;
use App\Entity\CreditsScheme;
use App\Entity\CustomerAccount;
use App\Entity\EarnContractCreditsAction;
use App\Entity\EarnCustomerCreditsAction;
use App\Enum\ActionStatus;
use Doctrine\ORM\EntityManagerInterface;
use GuzzleHttp\Client as GuzzleClient;
use League\Tactician\CommandBus;
use League\Uri\Modifiers\AppendSegment;
use League\Uri\Schemes\Http as HttpUri;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class AwardCredits extends Command
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
     * @var string
     */
    private $documentConverterHost;

    /**
     * @param CommandBus             $commandBus
     * @param EntityManagerInterface $entityManager
     * @param string                 $documentConverterHost
     */
    public function __construct(CommandBus $commandBus, EntityManagerInterface $entityManager, string $documentConverterHost)
    {
        parent::__construct();

        $this->commandBus = $commandBus;
        $this->entityManager = $entityManager;
        $this->documentConverterHost = $documentConverterHost;
    }

    /**
     * {@inheritdoc}
     */
    protected function configure(): void
    {
        $this
            ->setName('app:award:credits')
            ->setDescription('Awards credits to a specified customer account or contract.')
            ->addOption('account-number', null, InputOption::VALUE_OPTIONAL, 'For which account?', null)
            ->addOption('account-type', null, InputOption::VALUE_OPTIONAL, 'Award credits to which type of account. (Contract or CustomerAccount)', null)
            ->addOption('amount', null, InputOption::VALUE_OPTIONAL, 'The amount to award.', null)
            ->addOption('scheme-id', null, InputOption::VALUE_OPTIONAL, 'The credit scheme to use.', null)
            ->addOption('file-url', null, InputOption::VALUE_OPTIONAL, 'For batch imports of credits', null)
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

        $accountNumber = $input->getOption('account-number');
        $accountType = $input->getOption('account-type');
        $amount = $input->getOption('amount');
        $schemeId = $input->getOption('scheme-id');
        $fileUrl = $input->getOption('file-url');
        $data = [];

        $propertyClasses = [
            'accountNumber' => CustomerAccount::class,
            'contractNumber' => Contract::class,
        ];

        $commandClasses = [
            'Contract' => UpdateContractCreditsAction::class,
            'CustomerAccount' => UpdateCustomerCreditsAction::class,
        ];

        $creditsClasses = [
            'Contract' => EarnContractCreditsAction::class,
            'CustomerAccount' => EarnCustomerCreditsAction::class,
        ];

        if (null !== $fileUrl) {
            $tempFile = \tmpfile();
            $curl = \curl_init();
            \curl_setopt($curl, CURLOPT_URL, $fileUrl);
            \curl_setopt($curl, CURLOPT_FILE, $tempFile);
            \curl_exec($curl);
            \curl_close($curl);

            $io->text('Uploading to document converter...');

            $baseUri = HttpUri::createFromString($this->documentConverterHost);
            $modifier = new AppendSegment('application_requests/xml/event_activity');
            $uri = $modifier->process($baseUri);

            $client = new GuzzleClient();
            $multipartContent = [
                'headers' => [
                    'User-Agent' => 'U-Centric API',
                ],
                'multipart' => [
                    [
                        'name' => 'file',
                        'filename' => \uniqid().'.xml',
                        'contents' => $tempFile,
                    ],
                ],
            ];

            if (\filesize(\stream_get_meta_data($tempFile)['uri']) > 0) {
                $uploadResponse = $client->request('POST', $uri, $multipartContent);
                $data = \json_decode((string) $uploadResponse->getBody(), true);
            } else {
                $io->text('No file found.');

                return 0;
            }
        }

        if (empty($data)) {
            $requiredOptions = [
                'account-number' => 'accountNumber',
                'account-type' => 'accountType',
                'scheme-id' => 'schemeId',
            ];

            foreach ($requiredOptions as $option => $value) {
                if (empty($$value)) {
                    $io->error($option.' is a required option.');

                    return 0;
                }
            }

            switch ($accountType) {
                case 'Contract':
                    $propertyName = 'contractNumber';
                    break;
                case 'CustomerAccount':
                    $propertyName = 'accountNumber';
                    break;
                default:
                    $io->error('Unrecognized account type: '.$accountType);

                    return 0;
            }

            $inputData = [
                'schemeId' => $schemeId,
                'amount' => $amount,
            ];

            $inputData[$propertyName] = $accountNumber;
            $data[] = $inputData;
        }

        $outputTable = [];

        foreach ($data as $inputData) {
            $accountNumber = null;
            $amount = 0;
            $class = null;
            $className = null;
            $commandClass = null;
            $creditsClass = null;
            $propertyName = null;
            $schemeId = null;

            foreach ($propertyClasses as $name => $propertyClass) {
                if (!empty($inputData[$name])) {
                    $class = $propertyClass;
                    $className = \explode('\\', $class);
                    $className = \array_pop($className);
                    $propertyName = $name;
                    $commandClass = $commandClasses[$className];
                    $creditsClass = $creditsClasses[$className];
                    $accountNumber = $inputData[$name];
                    $amount = $inputData['amount'];
                    $schemeId = $inputData['schemeId'];
                    break;
                }
            }

            if (null !== $class) {
                try {
                    $object = $this->entityManager->getRepository($class)->findOneBy([$propertyName => $accountNumber]);

                    if (null !== $object) {
                        $qb = $this->entityManager->getRepository(CreditsScheme::class)->createQueryBuilder('credit');
                        $expr = $qb->expr();

                        $creditsSchemes = $qb->where($expr->lte('credit.validFrom', ':now'))
                            ->andWhere($expr->gte('credit.validThrough', ':now'))
                            ->andWhere($expr->isNull('credit.isBasedOn'))
                            ->andWhere($expr->eq('credit.schemeId', ':schemeId'))
                            ->setParameter('now', new \DateTime())
                            ->setParameter('schemeId', $schemeId)
                            ->orderBy('credit.dateCreated', 'DESC')
                            ->getQuery()
                            ->getResult();

                        if (\count($creditsSchemes) > 0) {
                            $parentCreditsScheme = $creditsSchemes[0];

                            $creditsScheme = clone $parentCreditsScheme;
                            $creditsScheme->setIsBasedOn($parentCreditsScheme);

                            $this->entityManager->persist($creditsScheme);

                            if (!empty($amount) && \is_numeric($amount)) {
                                $amount = (string) \round($amount, 2);
                            } elseif (null !== $creditsScheme->getAmount()->getValue()) {
                                $amount = (string) \round($creditsScheme->getAmount()->getValue(), 2);
                            }

                            if ($amount > 0) {
                                $earnCreditsAction = new $creditsClass();
                                $earnCreditsAction->setAmount($amount);
                                $earnCreditsAction->setEndTime(new \DateTime());
                                $earnCreditsAction->setStartTime(new \DateTime());
                                $earnCreditsAction->setStatus(new ActionStatus(ActionStatus::COMPLETED));

                                $earnCreditsAction->setObject($object);
                                $earnCreditsAction->setScheme($creditsScheme);

                                $this->commandBus->handle(new UpdateTransaction($earnCreditsAction));
                                $this->commandBus->handle(new $commandClass($object, $earnCreditsAction));

                                $this->entityManager->flush();

                                $outputTable[] = [
                                    $className,
                                    $accountNumber,
                                    $amount,
                                    'SUCCESS',
                                ];
                            } else {
                                $outputTable[] = [
                                    $className,
                                    $accountNumber,
                                    $amount,
                                    'ERROR - Amount is 0',
                                ];
                            }
                        }
                    } else {
                        $outputTable[] = [
                            $className,
                            $accountNumber,
                            $amount,
                            'ERROR - Not found!',
                        ];
                    }
                } catch (\Exception $e) {
                    $outputTable[] = [
                        $className,
                        $accountNumber,
                        $amount,
                        'ERROR - '.$e->getMessage(),
                    ];
                }
            }
        }

        $io->newLine();
        $io->table(['Entity', 'Account Number', 'Amount', 'Status'], $outputTable);
        $io->newLine();

        return 0;
    }
}
