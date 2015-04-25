<?php

class SortableTestBase extends Orchestra\Testbench\TestCase
{
    public function setUp()
    {
        parent::setUp();
        $artisan = $this->app->make('artisan');
        $artisan->call(
            'migrate',
            array(
                '--database' => 'testbench',
                '--path' => '../tests/migrations',
            )
        );
    }

    protected function getEnvironmentSetUp($app)
    {
        $app['path.base'] = __DIR__ . '/../src';

        $app['config']->set('database.default', 'testbench');
        $app['config']->set(
            'database.connections.testbench',
            array(
                'driver' => 'sqlite',
                'database' => ':memory:',
                'prefix' => '',
            )
        );
    }
}