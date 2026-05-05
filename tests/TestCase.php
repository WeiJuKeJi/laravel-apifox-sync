<?php

namespace Weijukeji\LaravelApifoxSync\Tests;

use Orchestra\Testbench\TestCase as Orchestra;
use Weijukeji\LaravelApifoxSync\ApifoxSyncServiceProvider;

abstract class TestCase extends Orchestra
{
    protected function getPackageProviders($app): array
    {
        return [
            ApifoxSyncServiceProvider::class,
        ];
    }
}
