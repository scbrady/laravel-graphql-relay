<?php

namespace Nuwave\Relay\Connections;

use GraphQL\Type\Definition\Type;
use Nuwave\Relay\Node\Node;
use Nuwave\Relay\Support\GraphQLType;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class PageInfoType extends GraphQLType
{
    /**
     * Attributes of PageInfo.
     *
     * @var array
     */
    protected $attributes = [
        'name' => 'pageInfo',
        'description' => 'Information to aid in pagination.'
    ];

    /**
     * Fields available on PageInfo.
     *
     * @return array
     */
    public function fields()
    {
        return [
            'hasNextPage' => [
                'type' => Type::nonNull(Type::boolean()),
                'description' => 'When paginating forwards, are there more items?',
                'resolve' => function ($collection) {
                    if ($collection instanceof LengthAwarePaginator) {
                        return $collection->hasMorePages();
                    }

                    return false;
                }
            ],
            'hasPreviousPage' => [
                'type' => Type::nonNull(Type::boolean()),
                'description' => 'When paginating backwards, are there more items?',
                'resolve' => function ($collection) {
                    if ($collection instanceof LengthAwarePaginator) {
                        return $collection->currentPage() > 1;
                    }

                    return false;
                }
            ],
            'startCursor' => [
                'type' => Type::string(),
                'description' => 'When paginating backwards, the cursor to continue.',
                'resolve' => function ($collection) {
                    if ($collection instanceof LengthAwarePaginator) {
                        return Node::encodeGlobalId(
                            'arrayconnection',
                            $collection->firstItem() * $collection->currentPage()
                        );
                    }

                    return null;
                }
            ],
            'endCursor' => [
                'type' => Type::string(),
                'description' => 'When paginating forwards, the cursor to continue.',
                'resolve' => function ($collection) {
                    if ($collection instanceof LengthAwarePaginator) {
                        return Node::encodeGlobalId(
                            'arrayconnection',
                            $collection->lastItem() * $collection->currentPage()
                        );
                    }

                    return null;
                }
            ]
        ];
    }
}
