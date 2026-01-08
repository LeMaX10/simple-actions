<?php
declare(strict_types=1);

namespace LeMaX10\SimpleActions\Tests;

use Illuminate\Container\Container;
use Illuminate\Database\Capsule\Manager as DB;
use Illuminate\Events\Dispatcher;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Facade;
use LeMaX10\SimpleActions\Action;
use PHPUnit\Framework\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected Container $app;

    protected function setUp(): void
    {
        parent::setUp();

        $this->setUpContainer();
        $this->setUpDatabase();
        $this->setUpEventDispatcher();
        $this->setUpCache();
    }

    protected function tearDown(): void
    {
        Cache::flush();
        $this->dropDatabase();

        parent::tearDown();
    }

    protected function setUpContainer(): void
    {
        $this->app = new Container();
        Container::setInstance($this->app);
        Facade::setFacadeApplication($this->app);

        $this->app->instance('app', $this->app);
    }

    protected function setUpDatabase(): void
    {
        $db = new DB();

        $db->addConnection([
            'driver'    => 'sqlite',
            'database'  => ':memory:',
            'prefix'    => '',
        ]);

        $db->setAsGlobal();
        $db->bootEloquent();

        DB::schema()->dropIfExists('test_models');
        DB::schema()->create('test_models', function ($table) {
            $table->increments('id');
            $table->string('name');
            $table->timestamps();
        });

        $this->app->singleton('db', fn () => $db->getDatabaseManager());
    }

    protected function dropDatabase(): void
    {
        try {
            DB::schema()->dropIfExists('test_models');
        } catch (\Throwable) {
           
        }
    }

    protected function setUpEventDispatcher(): void
    {
        $dispatcher = new Dispatcher($this->app);

        $this->app->singleton('events', fn () => $dispatcher);

        Action::setEventDispatcher($dispatcher);
    }

    protected function setUpCache(): void
    {
        $cache = new \Illuminate\Cache\ArrayStore();
        $repository = new \Illuminate\Cache\Repository($cache);

        $this->app->singleton('cache', fn () => new class($repository) {
            public function __construct(private $repository) {}

            public function driver()
            {
                return $this->repository;
            }

            public function store($name = null)
            {
                return $this->repository;
            }

            public function __call($method, $parameters)
            {
                return $this->repository->$method(...$parameters);
            }
        });

        $this->app->singleton('cache.store', fn () => $repository);
    }
}


