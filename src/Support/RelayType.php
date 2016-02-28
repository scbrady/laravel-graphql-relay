<?php

namespace Nuwave\Relay\Support;

use Exception;
use GraphQL\Type\Definition\ResolveInfo;
use GraphQL\Type\Definition\Type;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\LengthAwarePaginator;
use Nuwave\Relay\Node\Node;


abstract class RelayType extends GraphQLType
{

    /**
     * List of fields with global identifier.
     *
     * @return array
     */
    public function fields()
    {
        return array_merge($this->relayFields(), $this->getConnections(), [
            'id' => [
                'type'        => Type::nonNull(Type::id()),
                'description' => 'ID of type.',
                'resolve'     => function ($obj) {
                    return Node::toGlobalId(get_called_class(), $this->getIdentifier($obj));
                },
            ],
        ]);
    }

    /**
     * Available connections for type.
     *
     * @return array
     */
    protected function connections()
    {
        return [];
    }

    /**
     * Decode cursor from query arguments.
     *
     * @param  string  $cursor
     * @return integer
     */
    public function decodeCursor($cursor)
    {
        return Node::idFromGlobalId($cursor);
    }

    /**
     * Generate Relay compliant edges.
     *
     * @return array
     */
    public function getConnections()
    {
        return collect($this->connections())->transform(function ($edge, $name) {
            $edge['resolve'] = function ($collection, array $args, ResolveInfo $info) use ($name) {

                // Let edges be the result of calling RelayConnection::applyCursorsToEdges
                $edges = $this->getItems($collection, $name, $info);
                $cursor = $this->getCursor($args);

                // If first is set:
                if (array_key_exists('first', $args)) {
                    $edgesPerPage = $args['first'];

                    // If edges has length greater than than first:
                    $edgeLength = $edges->count();

                    if ($edgesPerPage < $edgeLength) {
                        $edges = $edges
                            ->slice($cursor)
                            ->take($edgesPerPage);
                    }

                }

                // If last is set:
                elseif (array_key_exists('last', $args)) {
                    $edgesPerPage = $args['last'];

                    // If edges has length greater than than last:
                    $edgeLength = $edges->count();

                    if ($edgesPerPage < $edgeLength) {
                        $edges = $edges
                            ->reverse()
                            ->slice($cursor)
                            ->take($edgesPerPage);
                    }

                }

                // Something went wrong
                else {
                    throw new Exception('You must specify "first & after" or "last & before" arguments for connections.');
                }

                $edges = new LengthAwarePaginator(
                    $edges,
                    $edgeLength,
                    $edgesPerPage,
                    ($edgesPerPage < $edgeLength) ? $this->getCurrentPage($edgesPerPage, $cursor) : 1
                );

                return [
                    'args' => $args,
                    'edges' => $edges,
                    'totalCount' => $edgeLength,
                ];
            };

            return $edge;

        })->toArray();
    }

    /**
     * Get the current cursor position given the user args.
     *
     * @param $args
     * @return int
     */
    protected function getCursor($args)
    {
        if (array_key_exists('after', $args)) {
            return $this->decodeCursor($args['after']);
        } elseif (array_key_exists('before', $args)) {
            return $this->decodeCursor($args['before']);
        } else {
            return 0;
        }
    }

    /**
     * Get the number of edges per page.
     *
     * @param $edgesPerPage
     * @param $cursor
     * @return float
     */
    protected function getCurrentPage($edgesPerPage, $cursor)
    {
        return floor(($edgesPerPage + $cursor) / $edgesPerPage);
    }

    /**
     * Get the number of items related to the collection.
     *
     * @param $collection
     * @param $name
     * @return mixed
     */
    protected function getEdgeLength($collection, $name)
    {
        if (method_exists($collection, $name)) {
            return $collection->$name()->count();
        } else {
            return $collection->$name->count();
        }
    }

    /**
     * Get the identifier of the type.
     *
     * @param \Illuminate\Database\Eloquent\Model $obj
     * @return mixed
     */
    public function getIdentifier(Model $obj)
    {
        return $obj->id;
    }

    /**
     * Get the id of the last item in a collection.
     *
     * @param $edges
     * @return int
     */
    public function getLastItemId($edges)
    {
        return $edges->last()->id;
    }

    /**
     * Get a list of paged items.
     *
     * @param $collection
     * @param $name
     * @return Collection|mixed
     */
    protected function getItems($collection, $name)
    {
        if (method_exists($collection, $name)) {
            return $collection->$name()->get();
        } else {
            return $collection->$name;
        }
    }

    /**
     * List of available interfaces.
     *
     * @return array
     */
    public function interfaces()
    {
        return [
            $this->graphQL->type('node')
        ];
    }

    /**
     * Get list of available fields for type.
     *
     * @return array
     */
    abstract protected function relayFields();

    /**
     * Fetch type data by id.
     *
     * @param string $id
     *
     * @return mixed
     */
    abstract public function resolveById($id);
}
