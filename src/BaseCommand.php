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
use DirectoryIterator;
use ZipArchive;

abstract class BaseCommand extends Command
{
    /**
     * Verify that the application does not already exist.
     *
     * @param  string  $directory
     * @return void
     */
    protected function checkExtensions()
    {
        if (!extension_loaded('zip')) {
            throw new RuntimeException('The Zip PHP extension is not installed. Please install it and try again.');
        }
    }

    /**
     * Verify that the application does not already exist.
     *
     * @param  string  $directory
     * @return void
     */
    protected function verifyApplicationDoesntExist($directory)
    {
        if ((is_dir($directory) || is_file($directory)) && $directory != getcwd()) {
            throw new RuntimeException('Application already exists!');
        }
    }

    /**
     * Generate a random temporary filename.
     *
     * @return string
     */
    protected function makeFilename()
    {
        return getcwd().'/larawal_'.md5(time().uniqid()).'.zip';
    }

    /**
     * Download the temporary Zip to the given file.
     *
     * @param  string  $zipFile
     * @param  string  $version
     * @return $this
     */
    protected function download($url, string $zipFile)
    {
        $response = (new Client)->get($url . '?ts=' . time());

        file_put_contents($zipFile, $response->getBody());

        return $this;
    }

    /**
     * Download the temporary Zip to the given file.
     *
     * @param  string  $zipFile
     * @param  string  $version
     * @return $this
     */
    protected function registryLookup($brick)
    {
        $response = (new Client)->get('https://larawal.github.io/registry/registry.json?ts='.time());

        $registry = json_decode($response->getBody(), true);

        if (empty($registry['version'])) {
            throw new RuntimeException('Registry is broken!');
        }

        if (isset($registry['shortcuts'][$brick])) {
            $brick = $registry['shortcuts'][$brick];
        }

        if (empty($registry['bricks'][$brick])) {
            throw new RuntimeException("Brick not found: {$brick}");
        }

        $url = $registry['bricks'][$brick];

        if (filter_var($url, FILTER_VALIDATE_URL) === false) {
            throw new RuntimeException("Brick '{$brick}' invalid url on registry: {$url}");
        }

        return $url;
    }

    /**
     * Extract the Zip file into the given directory.
     *
     * @param  string  $zipFile
     * @param  string  $directory
     * @return $this
     */
    protected function extract($zipFile, $directory)
    {
        $archive = new ZipArchive;

        $response = $archive->open($zipFile, ZipArchive::CHECKCONS);

        if ($response === ZipArchive::ER_NOZIP) {
            throw new RuntimeException('The zip file could not download. Verify that you are able to access: http://cabinet.laravel.com/latest.zip');
        }

        $tempDir = $this->tempDir();

        $archive->extractTo($tempDir);

        $this->moveFiles($tempDir . DIRECTORY_SEPARATOR . $archive->getNameIndex(0), $directory);

        $archive->close();

        return $this;
    }

    /**
     * Clean-up the Zip file.
     *
     * @param  string  $zipFile
     * @return $this
     */
    protected function cleanUp($zipFile)
    {
        @chmod($zipFile, 0777);

        @unlink($zipFile);

        return $this;
    }

    /**
     * Make sure the storage and bootstrap cache directories are writable.
     *
     * @param  string  $appDirectory
     * @param  \Symfony\Component\Console\Output\OutputInterface  $output
     * @return $this
     */
    protected function prepareWritableDirectories($appDirectory, OutputInterface $output)
    {
        $filesystem = new Filesystem;

        try {
            $filesystem->chmod($appDirectory.DIRECTORY_SEPARATOR.'bootstrap/cache', 0755, 0000, true);
            $filesystem->chmod($appDirectory.DIRECTORY_SEPARATOR.'storage', 0755, 0000, true);
        } catch (IOExceptionInterface $e) {
            $output->writeln('<comment>You should verify that the "storage" and "bootstrap/cache" directories are writable.</comment>');
        }

        return $this;
    }

    /**
     * Get the version that should be downloaded.
     *
     * @param  \Symfony\Component\Console\Input\InputInterface  $input
     * @return string
     */
    protected function getVersion(InputInterface $input)
    {
        if ($input->getOption('blog')) {
            return 'blog';
        }

        return 'master';
    }

    /**
     * Get the composer command for the environment.
     *
     * @return string
     */
    protected function findComposer()
    {
        $composerPath = getcwd().'/composer.phar';

        if (file_exists($composerPath)) {
            return '"'.PHP_BINARY.'" '.$composerPath;
        }

        return 'composer';
    }

    /**
     * Get the composer command for the environment.
     *
     * @return string
     */
    protected function tempDir()
    {
        $tempFile=tempnam(sys_get_temp_dir(), '');
        if (file_exists($tempFile)) { unlink($tempFile); }
        mkdir($tempFile);
        if (is_dir($tempFile)) { return $tempFile; }
    }

    /**
     *
     * @param $brickUrl
     * @param $directory
     */
    protected function fetchBrick($brickUrl, $directory, $output)
    {
        $brickFile = $this->tempFile();
        $this->download($brickUrl, $brickFile)
            ->extract($brickFile, $directory)
            ->prepareWritableDirectories($directory, $output)
            ->cleanUp($brickFile);
    }

    /**
     * Get the composer command for the environment.
     *
     * @return string
     */
    protected function tempFile()
    {
        $tempFile=tempnam(sys_get_temp_dir(), '');
        if (file_exists($tempFile)) { unlink($tempFile); }
        if (is_dir($tempFile)) {
            return;
        }
        return $tempFile;
    }

    /**
     * Recursively move files from one directory to another
     *
     * @param String $src - Source of files being moved
     * @param String $dest - Destination of files being moved
     */
    protected function moveFiles($src, $dest)
    {

        // If source is not a directory stop processing
        if(!is_dir($src)) return false;

        // If the destination directory does not exist create it
        if(!is_dir($dest)) {
            if(!mkdir($dest)) {
                // If the destination directory could not be created stop processing
                return false;
            }
        }

        // Open the source directory to read in files
        $i = new DirectoryIterator($src);
        foreach($i as $f) {
            if($f->isFile()) {
                rename($f->getRealPath(), "$dest/" . $f->getFilename());
            } else if(!$f->isDot() && $f->isDir()) {
                $this->moveFiles($f->getRealPath(), "$dest/$f");
                @unlink($f->getRealPath());
            }
        }
        @unlink($src);
    }

    /**
     * Recursively move files from one directory to another
     *
     * @param String $src - Source of files being moved
     * @param String $dest - Destination of files being moved
     */
    protected function copyFiles($files, $src, $dest)
    {
        // If source is not a directory stop processing
        if (!is_dir($src)) return false;

        // If the destination directory does not exist create it
        if (!is_dir($dest)) {
            if(!mkdir($dest)) {
                // If the destination directory could not be created stop processing
                return false;
            }
        }

        foreach ($files as $file) {
            copy($src.'/'.$file, $dest.'/'.$file);
        }
    }

    /**
     * Recursively move files from one directory to another
     *
     * @param String $src - Source of files being moved
     * @param String $dest - Destination of files being moved
     */
    protected function deleteConfig($directory)
    {
        $configFile = $directory . '/larawal.json';

        if (file_exists($configFile)) {
            @unlink($configFile);
        }
    }

    /**
     * Recursively move files from one directory to another
     *
     * @param String $src - Source of files being moved
     * @param String $dest - Destination of files being moved
     */
    protected function hasConfig($directory)
    {
        $configFile = $directory . '/larawal.json';

        return file_exists($configFile);
    }

    /**
     * Recursively move files from one directory to another
     *
     * @param String $src - Source of files being moved
     * @param String $dest - Destination of files being moved
     */
    protected function configLookup($directory)
    {
        $configFile = $directory . '/larawal.json';

        if (!file_exists($configFile)) {
            throw new RuntimeException('Configuration file not found: larawal.json');
        }

        $config = json_decode(file_get_contents($configFile), true);

        if (!is_array($config) || !$config) {
            throw new RuntimeException('Configuration file syntax error: larawal.json');
        }

        return $config;
    }

    /**
     *
     */
    protected function runCommands($commands, $directory, $input, $output)
    {
        $composer = $this->findComposer();

        $commands = array_map(function ($value) use ($composer) {
            return $composer . ' ' . $value;
        }, $commands);

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

        return $process;
    }

    /**
     *
     */
    protected function appendPostInstall($commands, $config)
    {
        if (isset($config['scripts']['post-install']) && is_array($config['scripts']['post-install'])) {
            foreach ($config['scripts']['post-install'] as $script) {
                $commands[] = 'run-script '.$script;
            }
        }
        return $commands;
    }

    /**
     *
     */
    protected function appendPostAdd($commands, $config)
    {
        if (isset($config['scripts']['post-add']) && is_array($config['scripts']['post-add'])) {
            foreach ($config['scripts']['post-add'] as $script) {
                $commands[] = 'run-script '.$script;
            }
        }
        return $commands;
    }

    /**
     *
     */
    protected function appendRequire($commands, $config)
    {
        if (isset($config['require']) && is_array($config['require'])) {
            foreach ($config['require'] as $library => $version) {
                $commands[] = 'require ' . $library . ':' . $version;
            }
        }

        return $commands;
    }

    /**
     *
     */
    protected function appendRequireDev($commands, $config)
    {
        if (isset($config['require-dev']) && is_array($config['require-dev'])) {
            foreach ($config['require-dev'] as $library => $version) {
                $commands[] = 'require '.$library.':'.$version.' --dev';
            }
        }

        return $commands;
    }
}
