<?php

namespace Nuwave\Relay\Support;

use GraphQL;
use Closure;
use GraphQL\Type\Definition\Type;
use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\ResolveInfo;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Nuwave\Relay\Connections\EdgeType;
use Nuwave\Relay\Node\Node;

abstract class RelayConnection extends GraphQLType
{
    /**
     * The edge resolver for this connection type
     *
     * @var \Closure
     */
    protected $edgeResolver;

    /**
     * The container instance of GraphQL.
     *
     * @var
     */
    protected $graphQL;

    /**
     * The pageInfo resolver for this connection type.
     *
     * @var \Closure
     */
    protected $pageInfoResolver;

    /**
     * The name of the edge (i.e. `User`).
     *
     * @var string
     */
    protected $name = '';

    public function __construct()
    {
        parent::__construct();

        $this->graphQL = app('graphql');
    }

    /**
     * Special fields present on this connection type.
     *
     * @return array
     */
    public function fields()
    {
        return [];
    }

    /**
     * Fields that exist on every connection.
     *
     * @return array
     */
    protected function baseFields()
    {
        return [
            'pageInfo' => [
                'type' => Type::nonNull($this->graphQL->type('pageInfo')),
                'description' => 'Information to aid in pagination.',
                'resolve' => function ($collection) {
                    return $collection;
                },
            ],
            'edges' => [
                'type' => Type::listOf($this->buildEdgeType($this->name, $this->type())),
                'description' => 'Information to aid in pagination.',
                'resolve' => function ($collection) {
                    return $this->injectCursor($collection);
                },
            ]
        ];
    }

    /**
     * Build the edge type for this connection.
     *
     * @param $name
     * @param $type
     * @return ObjectType
     */
    protected function buildEdgeType($name, $type)
    {
        $edge = new EdgeType($name, $type);

        return $edge->toType();
    }

    /**
     * Inject encoded cursor into collection items.
     *
     * @param  mixed $collection
     * @return mixed
     */
    protected function injectCursor($collection)
    {
        if ($collection instanceof LengthAwarePaginator) {
            $page = $collection->currentPage();

            $collection->each(function ($item, $x) use ($page) {
                $cursor        = ($x + 1) * $page;
                $encodedCursor = Node::encodeGlobalId('arrayconnection', $cursor);
                if (is_array($item)) {
                    $item['relayCursor'] = $encodedCursor;
                } else {
                    if (is_object($item) && is_array($item->attributes)) {
                        $item->attributes['relayCursor'] = $encodedCursor;
                    } else {
                        $item->relayCursor = $encodedCursor;
                    }
                }
            });
        }

        return $collection;
    }

    /**
     * Get id from encoded cursor.
     *
     * @param  string $cursor
     * @return integer
     */
    protected function getCursorId($cursor)
    {
        return (int) Node::decodeRelayId($cursor);
    }

    /**
     * Convert the Fluent instance to an array.
     *
     * @return array
     */
    public function toArray()
    {
        $fields = array_merge($this->baseFields(), $this->fields());

        return [
            'name' => ucfirst($this->name),
            'description' => 'A connection to a list of items.',
            'fields' => $fields,
            'resolve' => function ($root, $args, ResolveInfo $info) {
                return $this->resolve($root, $args, $info, $this->name);
            }
        ];
    }

    /**
     * Create the instance of the connection type.
     *
     * @param Closure $pageInfoResolver
     * @param Closure $edgeResolver
     * @return ObjectType
     */
    public function toType(Closure $pageInfoResolver = null, Closure $edgeResolver = null)
    {
        $this->pageInfoResolver = $pageInfoResolver;

        $this->edgeResolver = $edgeResolver;

        return new ObjectType($this->toArray());
    }

    /**
     * Dynamically retrieve the value of an attribute.
     *
     * @param  string  $key
     * @return mixed
     */
    public function __get($key)
    {
        $attributes = $this->getAttributes();

        return isset($attributes[$key]) ? $attributes[$key] : null;
    }

    /**
     * Dynamically check if an attribute is set.
     *
     * @param  string  $key
     * @return boolean
     */
    public function __isset($key)
    {
        return isset($this->getAttributes()[$key]);
    }

    /**
     * Get the type of nodes at the end of this connection.
     *
     * @return mixed
     */
    abstract public function type();
}