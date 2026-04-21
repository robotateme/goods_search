<?php
declare(strict_types=1);


namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\Config;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Config::set('database.default', 'sqlite');
        Config::set('database.connections.sqlite.database', ':memory:');
        Config::set('session.driver', 'array');
        Config::set('cache.default', 'array');
        Config::set('queue.default', 'sync');
        Config::set('mail.default', 'array');
        Config::set('search.driver', 'database');
    }
}
