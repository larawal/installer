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

class InstallCommand extends BaseCommand
{
    /**
     * Configure the command options.
     *
     * @return void
     */
    protected function configure()
    {
        $this
            ->setName('install')
            ->setDescription('Install all dependencies')
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

        $output->writeln('<info>Configuration lookup...</info>');

        $directory = $input->getOption('path');
        $config = $this->configLookup($directory);

        if (isset($config['from'])) {
            $brickUrl = $this->registryLookup($config['from']);
            $this->fetchBrick($brickUrl, $directory, $output);
        }

        $commands = ['install --no-scripts'];

        $commands = $this->appendRequire($commands, $config);

        $commands = $this->appendRequireDev($commands, $config);

        $commands = $this->appendPostInstall($commands, $config);

        $process = $this->runCommands($commands, $directory, $input, $output);

        if ($process->isSuccessful()) {
            $output->writeln('<comment>Brick ready! Build something amazing.</comment>');
        }

        return 0;
    }
}
