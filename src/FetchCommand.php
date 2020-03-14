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
            ->setDescription('Fetch files from Larawal registry')
            ->addArgument('brick', InputArgument::REQUIRED);
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
        $directory = getcwd();

        /*
        if (! $input->getOption('force')) {
            $this->verifyApplicationDoesntExist($directory);
        }
        */

        $output->writeln('<info>Registry lookup...</info>');

        $brickUrl = $this->registryLookup($brick);
        $brickFile = $this->tempFile();

        $this->download($brickUrl, $brickFile)
             ->extract($brickFile, $directory)
             ->prepareWritableDirectories($directory, $output)
             ->cleanUp($brickFile);

        /*
        $composer = $this->findComposer();

        $commands = [
            $composer.' install --no-scripts',
            $composer.' run-script post-root-package-install',
            $composer.' run-script post-create-project-cmd',
            $composer.' run-script post-autoload-dump',
        ];

        if ($input->getOption('no-ansi')) {
            $commands = array_map(function ($value) {
                return $value.' --no-ansi';
            }, $commands);
        }

        if ($input->getOption('quiet')) {
            $commands = array_map(function ($value) {
                return $value.' --quiet';
            }, $commands);
        }

        $process = Process::fromShellCommandline(implode(' && ', $commands), $directory, null, null, null);

        if ('\\' !== DIRECTORY_SEPARATOR && file_exists('/dev/tty') && is_readable('/dev/tty')) {
            try {
                $process->setTty(true);
            } catch (RuntimeException $e) {
                $output->writeln('Warning: '.$e->getMessage());
            }
        }

        $process->run(function ($type, $line) use ($output) {
            $output->write($line);
        });
        */
        //if ($process->isSuccessful()) {
            $output->writeln('<comment>Brick ready! Build something amazing.</comment>');
        //}

        return 0;
    }
}
