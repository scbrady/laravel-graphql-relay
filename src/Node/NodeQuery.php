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
        // Here, we decode the base64 id and get the id of the type
        // as well as the type's name.
        list($typeClass, $id) = Node::decodeGlobalId($args['id']);

        foreach (config('graphql.types') as $type => $class) {
            if ($typeClass == $class) {
                $objectType = app($typeClass);

                $model = $objectType->resolveById($id);

                if (is_array($model)) {
                    $model['graphqlType'] = $type;
                } elseif (is_object($model)) {
                    $model->graphqlType = $type;
                }

                return $model;
            }
        }

        return null;
    }
}
