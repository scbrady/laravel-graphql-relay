<?php

namespace Nuwave\Relay\Node;

use GraphQL;
use GraphQL\Type\Definition\Type;
use GraphQL\Type\Definition\ResolveInfo;
use Nuwave\Relay\Support\GraphQLQuery;

class NodeQuery extends GraphQLQuery
{
    /**
     * Associated GraphQL Type.
     *
     * @return mixed
     */
    public function type()
    {
        return $this->graphQL->type('node');
    }

    /**
     * Query attributes.
     *
     * @var array
     */
    protected $attributes = [
        'name' => 'node',
        'description' => 'Fetches an object given its ID.'
    ];

    /**
     * Arguments available on node query.
     *
     * @return array
     */
    public function args()
    {
        return [
            'id' => [
                'name' => 'id',
                'type' => Type::nonNull(Type::id())
            ]
        ];
    }

    /**
     * Resolve query.
     *
     * @param  string     $root
     * @param  array      $args
     * @param ResolveInfo $info
     * @return array|\Illuminate\Database\Eloquent\Model
     */
    public function resolve($root, array $args, ResolveInfo $info)
    {
        list($typeClass, $id) = Node::fromGlobalId($args['id']);

        $types = collect($this->graphQL->getTypes());

        $objectType = app($types[$types->search($typeClass)]);

        $model = $objectType->resolveById($id);

        $model->graphqlType = $type;

        return $model ?: null;
    }
}
