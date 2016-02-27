<?php

namespace Nuwave\Relay\Support;

class RelayField
{
    /**
     * Field name.
     *
     * @var string
     */
    public $name;

    /**
     * Field namespace.
     *
     * @var string
     */
    public $namespace;

    /**
     * Middleware to be applied to field.
     *
     * @var array
     */
    public $middleware = [];

    /**
     * Create in instance of schema field.
     *
     * @param string $name
     * @param string $namespace
     */
    public function __construct($name, $namespace)
    {
        $this->name = $name;
        $this->namespace = $namespace;
    }

    /**
     * Attach middleware to field.
     *
     * @param array $attributes
     */
    public function addMiddleware(array $attributes)
    {
        $this->middleware = array_unique(array_merge($this->middleware, array_flatten($attributes)));
    }

    /**
     * Add middleware to collection.
     *
     * @param  string $middleware
     * @return void
     */
    protected function attachMiddleware($middleware)
    {
        $this->middleware[] = $middleware;
    }

    /**
     * Get field attributes.
     *
     * @return array
     */
    public function getAttributes()
    {
        return [
            'namespace' => $this->namespace,
            'middleware' => $this->middleware
        ];
    }

    /**
     * Attach middleware(s) to field.
     *
     * @param  array|string $middlewares
     * @return self
     */
    public function middleware($middlewares)
    {
        $middlewares = is_array($middlewares) ? $middlewares : [$middlewares];

        foreach ($middlewares as $middleware) {
            $this->attachMiddleware($middleware);
        }

        return $this;
    }
}
