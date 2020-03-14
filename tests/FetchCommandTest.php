<?php

namespace Laravel\Installer\Console\Tests;

use Larawal\Installer\Console\NewCommand;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\Filesystem\Filesystem;

class FetchCommandTest extends TestCase
{
    public function test_it_can_scaffold_a_new_laravel_app()
    {
        $scaffoldDirectoryName = 'tests/output/my-app';
        $scaffoldDirectory = __DIR__.'/../'.$scaffoldDirectoryName;

        if (file_exists($scaffoldDirectory)) {
            (new Filesystem)->remove($scaffoldDirectory);
        }

        $app = new Application('Larawal Installer');
        $app->add(new NewCommand);

        $tester = new CommandTester($app->find('new'));

        $statusCode = $tester->execute(['name' => $scaffoldDirectoryName, '--from' => 'blog']);

        $this->assertEquals($statusCode, 0);
        $this->assertDirectoryExists($scaffoldDirectory.'/vendor');
        $this->assertFileExists($scaffoldDirectory.'/.env');
        $this->assertFileExists($scaffoldDirectory.'/resources/views/auth/login.blade.php');
    }
}
