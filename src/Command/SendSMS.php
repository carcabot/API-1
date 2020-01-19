<?php

declare(strict_types=1);

namespace App\Command;

use App\Model\SmsUpdater;
use App\WebService\SMS\ClientInterface;
use App\WebService\SMS\DummyClient;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class SendSMS extends Command
{
    /**
     * @var ClientInterface
     */
    private $smsClient;

    /**
     * @var SmsUpdater
     */
    private $smsUpdater;

    /**
     * @param ClientInterface $smsClient
     * @param SmsUpdater      $smsUpdater
     */
    public function __construct(ClientInterface $smsClient, SmsUpdater $smsUpdater)
    {
        parent::__construct();

        $this->smsClient = $smsClient;
        $this->smsUpdater = $smsUpdater;
    }

    /**
     * {@inheritdoc}
     */
    protected function configure(): void
    {
        $this
            ->setName('app:web-service:send-sms')
            ->setDescription('Sends SMS to specified number.')
            ->addOption('recipient', null, InputOption::VALUE_OPTIONAL, 'The recipient of the SMS.', null)
            ->addOption('message', null, InputOption::VALUE_OPTIONAL, 'The SMS content.', null)
            ->setHelp(<<<'EOF'
The %command.name% command tests the sms provider.
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

        $recipient = $input->getOption('recipient');
        $message = $input->getOption('message');

        $testNumber = '60162478267';

        if (empty($message)) {
            $message = 'Test message <1>';
        }

        if (!$this->smsClient instanceof DummyClient) {
            $io->success(\sprintf('SMS Client class %s found.', \get_class($this->smsClient)));
            $io->comment('Sending SMS...');

            if (empty($recipient)) {
                $recipient = $testNumber;
            }

            if (!empty($recipient) && !empty($message)) {
                $result = $this->smsClient->send($recipient, $message);
                $io->text(\json_encode($result));

                $this->smsUpdater->create($result);
            } else {
                $io->error('Not enough parameters. Requires recipient number AND message.');
            }
        }

        return 0;
    }
}
