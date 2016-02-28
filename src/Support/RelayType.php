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
     * Generate Relay compliant edges.
     *
     * @return array
     */
    public function getConnections()
    {
        return collect($this->connections())->transform(function ($edge, $name) {
            $edge['resolve'] = function ($collection, array $args, ResolveInfo $info) use ($name) {
                $edgeLength   = $this->getEdgeLength($collection, $name);

                $edgesPerPage = $edgeLength;

                $currentPage  = 1;

                if (array_key_exists('first', $args)) {
                    $edgesPerPage = $args['first'];

                    if ($edgesPerPage < $edgeLength) {
                        $after = array_key_exists('after', $args)
                            ? $this->decodeCursor($args['after']) : 0;

                        $currentPage = $this->getCurrentPage($edgesPerPage, $after);

                        $offset = $this->getPageOffset($edgesPerPage, $currentPage);

                        $edges = $this
                            ->getPagedItems($collection, $offset, $edgesPerPage, $info, $name);

                    }
                } elseif (array_key_exists('last', $args)) {
                    $edgesPerPage = $args['last'];

                    if ($edgesPerPage < $edgeLength) {
                        $before = array_key_exists('before', $args)
                            ? $this->decodeCursor($args['before']) : 0;

                        $currentPage = $this->getCurrentPage($edgesPerPage, $before);

                        $offset = $this->getPageOffset($edgesPerPage, $currentPage);

                        $edges = $this
                            ->getPagedItems($collection, $offset, $edgesPerPage, $info, $name, 'desc');
                    }
                }

                if (!$edgesPerPage || !$currentPage) {
                    throw new Exception('You must specify "first & after" or "last & before" arguments for connections.');
                }

                return [
                    'args' => $args,
                    'edges' => new LengthAwarePaginator($edges, $edgeLength, $edgesPerPage, $currentPage),
                    'totalCount' => $edgeLength,
                ];
            };

            return $edge;

        })->toArray();
    }

    /**
     * Get the number of edges per page.
     *
     * @param $perPage
     * @param $offset
     * @return float
     */
    protected function getCurrentPage($perPage, $offset)
    {
        return floor(($perPage + $offset) / $perPage);
    }

    /**
     * Get the number of items related to the colleciton.
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
     * Get a list of paged items.
     *
     * @param        $collection
     * @param        $offset
     * @param        $edgesPerPage
     * @param        $info
     * @param        $name
     * @param string $order
     * @return Collection|mixed
     */
    protected function getPagedItems($collection, $offset, $edgesPerPage, $info, $name, $order = 'asc')
    {
        if (method_exists($collection, $name)) {

            return $collection
                ->$name()
                ->orderBy('created_at', $order)
                ->skip($offset)
                ->take($edgesPerPage)
                ->select(...$this->getSelectFields($info))
                ->get();

        } else {
            $collection = $collection->$name;

            $collection = $order === 'asc' ? $collection : $collection->reverse();

            return $collection
                ->slice($offset)
                ->take($edgesPerPage);
        }
    }

    /**
     * Get the page offset.
     *
     * @param $perPage
     * @param $currentPage
     * @return mixed
     */
    protected function getPageOffset($perPage, $currentPage)
    {
        return $perPage * ($currentPage - 1);
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
