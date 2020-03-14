<?php

namespace Laravel\Installer\Console\Tests;

use Larawal\Installer\Console\AddCommand;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\Filesystem\Filesystem;

class AddCommandTest extends TestCase
{
    public function test_it_can_add_brick_from_registry()
    {
        $scaffoldDirectoryName = 'tests/output/my-blog';
        $scaffoldDirectory = __DIR__.'/../'.$scaffoldDirectoryName;

        if (file_exists($scaffoldDirectory)) {
            (new Filesystem)->remove($scaffoldDirectory);
        }

        mkdir($scaffoldDirectory);
        copy('tests/fixtures/composer.json', $scaffoldDirectory . '/composer.json');

        $app = new Application('Larawal');
        $app->add(new AddCommand);

        $tester = new CommandTester($app->find('add'));

        $statusCode = $tester->execute(['brick' => 'blog', '--path' => $scaffoldDirectoryName]);

        $this->assertEquals($statusCode, 0);
        $this->assertDirectoryExists($scaffoldDirectory.'/vendor');
    }
}
