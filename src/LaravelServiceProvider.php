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

        $this->bootSchema();
    }

    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->singleton(GraphQL::class);
    }

    /**
     * Boot the schema mutations and queries.
     *
     * @return void
     */
    protected function bootSchema()
    {
        $schema = config('graphql');

        $this->registerRelayTypes();

        // Add each type to the GraphQL container
        foreach($schema['types'] as $name => $type) {
            $this->app[GraphQL::class]->addType($type, $name);
        }

        // Add each connection type to the GraphQL container
        foreach($schema['connectionTypes'] as $name => $type) {
            $this->app[GraphQL::class]->addType($type, ucfirst($name.'Connection'));
        }
    }

    /**
     * Register the commands provided by this package.
     *
     * @return void
     */
    protected function registerCommands()
    {
        $this->commands([
            SchemaCommand::class,
            MutationMakeCommand::class,
            FieldMakeCommand::class,
            QueryMakeCommand::class,
            TypeMakeCommand::class,
        ]);
    }

    /**
     * Register the default relay types in the schema.
     *
     * @return void
     */
    protected function registerRelayTypes()
    {
        $types = array_merge([
            'node' => NodeType::class,
            'pageInfo' => PageInfoType::class,
        ], config('relay.types'));

        $queries = array_merge([
            'node' => NodeQuery::class,
        ], config('relay.queries'));

        config([
            'relay.queries' => $queries,
            'relay.types' => $types,
        ]);
    }
}
