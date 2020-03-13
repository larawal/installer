<?php

namespace Laravel\Installer\Console;
use GuzzleHttp\Client;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;

class Handler extends Command
{
    /**
     * Rename the Laravel application instance.
     *
     * @var \Illuminate\Contracts\Foundation\Application
     */
    protected $laravel = 'larawal';

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
            ->addOption('from', null, InputOption::VALUE_NONE, 'Prepare a new assestement');
    }

    /**
     * Download the temporary Zip to the given file.
     *
     * @param  string  $zipFile
     * @param  string  $version
     * @return $this
     */
    protected function download($zipFile, string $version)
    {
        switch ($version) {
            case 'blog':
                $filename = 'larawal-blog.zip';
                break;
        }

        $response = (new Client)->get('https://belicedigital.com/'.$filename);

        file_put_contents($zipFile, $response->getBody());

        return $this;
    }

}