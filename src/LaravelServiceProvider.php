<?php

namespace Nuwave\Relay;

use GraphQL\GraphQL;
use Nuwave\Relay\Commands\FieldMakeCommand;
use Nuwave\Relay\Commands\MutationMakeCommand;
use Nuwave\Relay\Commands\QueryMakeCommand;
use Nuwave\Relay\Commands\SchemaCommand;
use Nuwave\Relay\Commands\TypeMakeCommand;
use Illuminate\Support\ServiceProvider as BaseProvider;

class LaravelServiceProvider extends BaseProvider
{
    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        $this->publishes([__DIR__ . '/../config/config.php' => config_path('relay.php')]);

        $this->mergeConfigFrom(__DIR__ . '/../config/config.php', 'relay');

        $this->registerSchema();
    }

    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        $this->commands([
            SchemaCommand::class,
            MutationMakeCommand::class,
            FieldMakeCommand::class,
            QueryMakeCommand::class,
            TypeMakeCommand::class,
        ]);

        $this->app->singleton(GraphQL::class);

        $this->app->singleton(Relay::class);
    }

    /**
     * Register schema mutations and queries.
     *
     * @return void
     */
    protected function registerSchema()
    {
        require_once app_path(config('relay.schema_path'));

        $this->setGraphQLConfig();

        $this->registerRelayTypes();

        $this->initializeTypes();
    }

    /**
     * Register the default relay types in the schema.
     *
     * @return void
     */
    protected function registerRelayTypes()
    {
        $relay = $this->app['relay'];

        app();

        $relay->group(['namespace' => 'Nuwave\\Relay'], function () use ($relay) {
            $relay->query('node', 'Node\\NodeQuery');
            $relay->type('node', 'Node\\NodeType');
            $relay->type('pageInfo', 'Connections\\PageInfoType');
        });
    }

    /**
     * Set GraphQL configuration variables.
     *
     * @return void
     */
    protected function setGraphQLConfig()
    {
        $relay = $this->app['relay'];

        config([
            'graphql.schema.mutation' => $relay->getMutations(),
            'graphql.schema.query' => $relay->getQueries(),
            'graphql.types' => $relay->getTypes(),
        ]);
    }

    /**
     * Initialize GraphQL types array.
     *
     * @return void
     */
    protected function initializeTypes()
    {
        foreach(config('graphql.types') as $name => $type) {
            $this->app['graphql']->addType($type, $name);
        }
    }
}
