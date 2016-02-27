<?php

namespace Nuwave\Relay\Connections;

use GraphQL\Type\Definition\Type;

class Connection
{
    /**
     * Get the default arguments for a connection.
     *
     * @return array
     */
    public static function backwardConnectionArgs()
    {
        return [
            'before' => [
                'type' => Type::string()
            ],
            'last' => [
                'type' => Type::int()
            ]
        ];
    }

    /**
     * Get the default arguments for a connection.
     *
     * @return array
     */
    public static function forwardConnectionArgs()
    {
        return [
            'after' => [
                'type' => Type::string()
            ],
            'first' => [
                'type' => Type::int()
            ],
        ];
    }

    /**
     * Get the default arguments for a connection.
     *
     * @return array
     */
    public static function connectionArgs()
    {
        return array_merge(
            self::forwardConnectionArgs(),
            self::backwardConnectionArgs()
        );
    }
}