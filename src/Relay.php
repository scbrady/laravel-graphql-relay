<?php

namespace Nuwave\Relay;

use Closure;
use Nuwave\Relay\Support\RelayField;

class Relay
{
    /**
     * Connections present in query.
     *
     * @var array
     */
    public $connections = [];

    /**
     * Schema middleware stack.
     *
     * @var array
     */
    protected $middlewareStack = [];

    /**
     * Mutation collection.
     *
     * @var array
     */
    protected $mutations = [];

    /**
     * Current namespace.
     *
     * @var array
     */
    protected $namespace = '';

    /**
     * Query collection.
     *
     * @var array
     */
    protected $queries = [];

    /**
     * Type collection.
     *
     * @var array
     */
    protected $types = [];

    /**
     * Add a connection to collection.
     *
     * @param  string $name
     * @param  string $namespace
     * @return RelayField
     */
    public function connection($name, $namespace)
    {
        $connectionType = $this->createField($name.'Connection', $namespace);

        $this->types[$connectionType->name] = $connectionType->namespace;

        return $connectionType;
    }

    /**
     * Get field and attach necessary middleware.
     *
     * @param  string $name
     * @param  string $namespace
     * @return RelayField
     */
    protected function createField($name, $namespace)
    {
        $field = new RelayField($name, $this->getClassName($namespace));

        if ($this->hasMiddlewareStack()) {
            $field->addMiddleware($this->middlewareStack);
        }

        return $field;
    }

    /**
     * Get mutations.
     *
     * @return array
     */
    public function getMutations()
    {
        return $this->mutations;
    }

    /**
     * Get queries.
     *
     * @return array
     */
    public function getQueries()
    {
        return $this->queries;
    }

    /**
     * Get the registered types.
     *
     * @return array
     */
    public function getTypes()
    {
        return $this->types;
    }

    /**
     * Get class name.
     *
     * @param  string $namespace
     * @return string
     */
    protected function getClassName($namespace)
    {
        return empty(trim($this->namespace)) ? $namespace : trim($this->namespace, '\\') . '\\' . $namespace;
    }

    /**
     * Group child elements.
     *
     * @param  array   $attributes
     * @param  Closure $callback
     * @return void
     */
    public function group(array $attributes, Closure $callback)
    {
        $oldNamespace = $this->namespace;

        if (isset($attributes['middleware'])) {
            $this->middlewareStack[] = $attributes['middleware'];
        }

        if (isset($attributes['namespace'])) {
            $this->namespace  .= '\\' . trim($attributes['namespace'], '\\');
        }

        $callback();

        if (isset($attributes['middleware'])) {
            array_pop($this->middlewareStack);
        }

        if (isset($attributes['namespace'])) {
            $this->namespace = $oldNamespace;
        }
    }

    /**
     * Check if middleware stack is empty.
     *
     * @return boolean
     */
    protected function hasMiddlewareStack()
    {
        return ! empty($this->middlewareStack);
    }

    /**
     * Add a mutation to collection.
     *
     * @param string $name
     * @param array $namespace
     * @return RelayField
     */
    public function mutation($name, $namespace)
    {
        $mutation = $this->createField($name, $namespace);

        $this->mutations[$mutation->name] = $mutation->namespace;

        return $mutation;
    }

    /**
     * Add a query to collection.
     *
     * @param string $name
     * @param array $namespace
     * @return RelayField
     */
    public function query($name, $namespace)
    {
        $query = $this->createField($name, $namespace);

        $this->queries[$query->name] = $query->namespace;

        return $query;
    }

    /**
     * Add a type to collection.
     *
     * @param  string $name
     * @param  string $namespace
     * @return RelayField
     */
    public function type($name, $namespace)
    {
        $type = $this->createField($name, $namespace);

        $this->types[$type->name] = $type->namespace;

        return $type;
    }
}
