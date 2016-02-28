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
                    return Node::encodeGlobalId(get_called_class(), $this->getIdentifier($obj));
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
     * Generate Relay compliant edges.
     *
     * @return array
     */
    public function getConnections()
    {
        return collect($this->connections())->transform(function ($edge, $name) {
            $edge['resolve'] = function ($collection, array $args, ResolveInfo $info) use ($name) {
                $edges = $this->getItems($collection, $info, $name);
                $edgeLength = $edges->count();
                $edgesPerPage = $edgeLength;
                $currentPage = 1;

                if (array_key_exists('first', $args)) {
                    $edgesPerPage = $args['first'];

                    if ($edgesPerPage < $edgeLength) {
                        $after = array_key_exists('after', $args) ? $this->decodeCursor($args['after']) : 0;

                        $currentPage = floor(($edgesPerPage + $after) / $edgesPerPage);

                        $edges = $edges
                            ->slice($edgesPerPage * ($currentPage - 1))
                            ->take($edgesPerPage);
                    }
                } else if (array_key_exists('last', $args)) {
                    $edgesPerPage = $args['last'];

                    if ($edgesPerPage < $edgeLength) {
                        $before = array_key_exists('before', $args)
                            ? $this->decodeCursor($args['before']) : 0;

                        $currentPage = floor(($edgesPerPage + $before) / $edgesPerPage);

                        $edges = $edges
                            ->reverse()
                            ->slice($edgesPerPage * ($currentPage - 1))
                            ->take($edgesPerPage);
                    }
                }

                if (!$edgesPerPage || !$currentPage) {
                    throw new Exception('You must specify "first & after" or "last & before" arguments for connections.');
                }

                return [
                    'edges' => new LengthAwarePaginator($edges, $edgeLength, $edgesPerPage, $currentPage),
                    'args' => $args
                ];
            };

            return $edge;

        })->toArray();
    }

    /**
     * @param             $collection
     * @param ResolveInfo $info
     * @param             $name
     * @return mixed|Collection
     */
    protected function getItems($collection, ResolveInfo $info, $name)
    {
        $items = [];

        if ($collection instanceof Model) {
            // Selects only the fields requested, instead of select *
            $items = method_exists($collection, $name)
                ? $collection->$name()->select(...$this->getSelectFields($info))->get()
                : $collection->getAttribute($name);

            return $items;
        } elseif (is_object($collection) && method_exists($collection, 'get')) {
            $items = $collection->get($name);
            return $items;
        } elseif (is_array($collection) && isset($collection[$name])) {
            $items = new Collection($collection[$name]);
            return $items;
        }

        return $items;
    }

    /**
     * Select only certain fields on queries instead of all fields.
     *
     * @param ResolveInfo $info
     * @return array
     */
    protected function getSelectFields(ResolveInfo $info)
    {
        $foreignKeys = [];

        return collect($info->getFieldSelection(4)['edges']['node'])
            ->reject(function ($value, $key) use (&$foreignKeys) {
                if (is_array($value)) {
                    $foreignKeys[$key.'_id'] = true;
                    return true;
                } else {
                    return false;
                }
            })
            ->merge($foreignKeys)
            ->keys()->toArray();
    }

    /**
     * Decode cursor from query arguments.
     *
     * @param  string  $cursor
     * @return integer
     */
    public function decodeCursor($cursor)
    {

        return Node::decodeRelayId($cursor);
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
