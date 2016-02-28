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
        'name' => 'PageInfo',
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
            'endCursor' => [
                'type' => Type::string(),
                'description' => 'When paginating forwards, the cursor to continue.',
                'resolve' => function (array $root) {
                    $edges = $root['edges'];

                    if ($edges instanceof LengthAwarePaginator) {
                        return $edges->last()->relayCursor;
                    }

                    return null;
                }
            ],
            'hasNextPage' => [
                'type' => Type::nonNull(Type::boolean()),
                'description' => 'When paginating forwards, are there more items?',
                'resolve' => function (array $root) {
                    $edges = $root['edges'];

                    if (array_key_exists('first', $root['args']) && $edges instanceof LengthAwarePaginator) {
                        return $edges->hasMorePages();
                    } else {
                        return false;
                    }
                }
            ],
            'hasPreviousPage' => [
                'type' => Type::nonNull(Type::boolean()),
                'description' => 'When paginating backwards, are there more items?',
                'resolve' => function (array $root) {
                    $edges = $root['edges'];

                    if (array_key_exists('last', $root['args']) && $edges instanceof LengthAwarePaginator) {
                        return $edges->hasMorePages();
                    } else {
                        return false;
                    }
                }
            ],
            'startCursor' => [
                'type' => Type::string(),
                'description' => 'When paginating backwards, the cursor to continue.',
                'resolve' => function (array $root) {
                    $edges = $root['edges'];

                    if ($edges instanceof LengthAwarePaginator) {
                        return $edges->last()->relayCursor;
                    }

                    return null;
                }
            ],
        ];
    }
}
