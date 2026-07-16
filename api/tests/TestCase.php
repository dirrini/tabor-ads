<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    public function createApplication()
    {
        foreach ([
            'APP_ENV' => 'testing',
            'APP_KEY' => 'base64:bG9jYWwtZGV2LWtleS0zMi1ieXRlcy1sb25nISEhISE=',
            'BROADCAST_CONNECTION' => 'reverb',
            'DB_CONNECTION' => 'sqlite',
            'DB_DATABASE' => ':memory:',
            'DB_URL' => '',
            'REVERB_APP_ID' => 'testing-app',
            'REVERB_APP_KEY' => 'testing-key',
            'REVERB_APP_SECRET' => 'testing-secret',
            'REVERB_HOST' => 'localhost',
            'REVERB_PORT' => '8080',
            'REVERB_SCHEME' => 'http',
        ] as $name => $value) {
            putenv($name.'='.$value);
            $_ENV[$name] = $value;
            $_SERVER[$name] = $value;
        }

        $app = parent::createApplication();

        if ($app->make('config')->get('database.default') !== 'sqlite') {
            throw new \RuntimeException('Testes devem executar exclusivamente com SQLite.');
        }

        return $app;
    }
}
