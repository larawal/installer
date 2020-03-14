<?php

namespace Larawal\Installer\Console;

use GuzzleHttp\Client;
use RuntimeException;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Filesystem\Exception\IOExceptionInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Process\Process;
use ZipArchive;

class FetchCommand extends BaseCommand
{
    /**
     * Configure the command options.
     *
     * @return void
     */
    protected function configure()
    {
        $this
            ->setName('fetch')
            ->setDescription('Fetch brick from Larawal registry')
            ->addArgument('brick', InputArgument::REQUIRED)
            ->addOption('path', null, InputOption::VALUE_REQUIRED, 'Install on specific path', getcwd());
    }

    /**
     * Execute the command.
     *
     *
     * @param  \Symfony\Component\Console\Input\InputInterface  $input
     * @param  \Symfony\Component\Console\Output\OutputInterface  $output
     * @return int
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->checkExtensions();

        $brick = $input->getArgument('brick');
        $directory = $input->getOption('path');

        /*
        if (! $input->getOption('force')) {
            $this->verifyApplicationDoesntExist($directory);
        }
        */

        $output->writeln('<info>Registry lookup...</info>');

        $brickUrl = $this->registryLookup($brick);

        $this->fetchBrick($brickUrl, $directory, $output);

        $output->writeln('<comment>Brick ready! Build something amazing.</comment>');

        return 0;
    }
}
