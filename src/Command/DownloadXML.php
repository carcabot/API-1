<?php

declare(strict_types=1);

namespace App\Command;

use League\Uri\Schemes\Ftp as FtpUri;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class DownloadXML extends Command
{
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * {@inheritdoc}
     */
    protected function configure(): void
    {
        $this
            ->setName('app:web-service:download-xml')
            ->setDescription('Download an XML file.')
            ->addOption('path', null, InputOption::VALUE_REQUIRED, 'path', null)
            ->addOption('ftp-url', null, InputOption::VALUE_REQUIRED, 'FTP URL', null)
            ->addOption('ftp-username', null, InputOption::VALUE_REQUIRED, 'FTP username', null)
            ->addOption('ftp-password', null, InputOption::VALUE_REQUIRED, 'FTP password', null)
            ->setHelp(<<<'EOF'
The %command.name% command downloads an XML file.
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

        $path = $input->getOption('path');
        $ftpUrl = (string) $input->getOption('ftp-url');
        $ftpUsername = $input->getOption('ftp-username');
        $ftpPassword = $input->getOption('ftp-password');

        $ftpUri = FtpUri::createFromString($ftpUrl);
        $ftp = \ftp_ssl_connect($ftpUri->getHost(), $ftpUri->getPort() ?? 21);

        if (true === \ftp_login($ftp, $ftpUsername, $ftpPassword)) {
            \ftp_pasv($ftp, true);

            if (\ftp_size($ftp, $path) < 1) {
                $io->text('Filesize is less than 1.');
                $io->newLine();

                $io->text('Closing ftp connection..');
                \ftp_close($ftp);

                return 0;
            }

            $io->text('Downloading...');
            $io->newLine();
            $tempFile = \tmpfile();
            \ftp_get($ftp, \stream_get_meta_data($tempFile)['uri'], $path, FTP_BINARY);
            $io->text('File contents: '.\fread($tempFile, \filesize(\stream_get_meta_data($tempFile)['uri'])));
        }

        $io->newLine();
        $io->text('Closing ftp connection..');
        \ftp_close($ftp);

        return 0;
    }
}
