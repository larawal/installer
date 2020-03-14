<?php

namespace Laravel\Installer\Console\Tests;

use Larawal\Installer\Console\InstallCommand;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\Filesystem\Filesystem;

class InstallCommandTest extends TestCase
{
    public function test_it_can_install_from_config_file()
    {
        $scaffoldDirectoryName = 'tests/output/my-blog';
        $scaffoldDirectory = __DIR__.'/../'.$scaffoldDirectoryName;

        if (file_exists($scaffoldDirectory)) {
            (new Filesystem)->remove($scaffoldDirectory);
        }

        mkdir($scaffoldDirectory);
        copy('tests/fixtures/larawal.json', $scaffoldDirectory . '/larawal.json');

        $app = new Application('Larawal');
        $app->add(new InstallCommand);

        $tester = new CommandTester($app->find('install'));

        $statusCode = $tester->execute(['--path' => $scaffoldDirectoryName]);

        $this->assertEquals($statusCode, 0);
        $this->assertDirectoryExists($scaffoldDirectory.'/vendor');
        $this->assertFileExists($scaffoldDirectory.'/.env');
    }
}
