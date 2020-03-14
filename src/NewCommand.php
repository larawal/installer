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

class NewCommand extends BaseCommand
{
    /**
     * Configure the command options.
     *
     * @return void
     */
    protected function configure()
    {
        $this
            ->setName('new')
            ->setDescription('Build a new Larawal application')
            ->addArgument('name', InputArgument::OPTIONAL)
            ->addOption('from', null, InputOption::VALUE_REQUIRED, 'Prepare a new assestement', 'laravel');
    }

    /**
     * Execute the command.
     *
     * @param  \Symfony\Component\Console\Input\InputInterface  $input
     * @param  \Symfony\Component\Console\Output\OutputInterface  $output
     * @return int
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->checkExtensions();

        $name = $input->getArgument('name');

        $directory = $name && $name !== '.' ? getcwd().'/'.$name : getcwd();

        /*
        if (! $input->getOption('force')) {
            $this->verifyApplicationDoesntExist($directory);
        }
        */

        $output->writeln('<info>Crafting application...</info>');

        $fromBrick = $input->getOption('from');

        $brickUrl = $this->registryLookup($fromBrick);

        $this->fetchBrick($brickUrl, $directory, $output);

        if ($this->hasConfig($directory)) {
            $config = $this->configLookup($directory);
            $commands = ['install --no-scripts'];
            $commands = $this->appendPostInstall($commands, $config);
            $process = $this->runCommands($commands, $directory, $input, $output);
            $this->deleteConfig($directory);
            if ($process->isSuccessful()) {
                $output->writeln('<comment>Configuration applied.</comment>');
            }
        }

        $output->writeln('<comment>Application ready! Build something amazing.</comment>');

        return 0;
    }
}
