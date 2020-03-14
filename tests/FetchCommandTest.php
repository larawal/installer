<?php

namespace Laravel\Installer\Console\Tests;

use Larawal\Installer\Console\FetchCommand;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\Filesystem\Filesystem;

class FetchCommandTest extends TestCase
{
    public function test_it_can_fetch_brick_from_registry()
    {
        $scaffoldDirectoryName = 'tests/output/my-blog';
        $scaffoldDirectory = __DIR__.'/../'.$scaffoldDirectoryName;

        if (file_exists($scaffoldDirectory)) {
            (new Filesystem)->remove($scaffoldDirectory);
        }

        $app = new Application('Larawal');
        $app->add(new FetchCommand);

        $tester = new CommandTester($app->find('fetch'));

        $statusCode = $tester->execute(['brick' => 'blog', '--path' => $scaffoldDirectoryName]);

        $this->assertEquals($statusCode, 0);
        $this->assertDirectoryExists($scaffoldDirectory.'/app');
        $this->assertFileExists($scaffoldDirectory.'/.env.example');
    }
}
