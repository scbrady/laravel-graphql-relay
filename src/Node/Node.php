<?php

namespace Nuwave\Relay\Node;

class Node
{
    /**
     * Create global id.
     *
     * @param  string $type
     * @param  string|integer $id
     * @return string
     */
    public static function encodeGlobalId($type, $id)
    {
        return base64_encode($type . ':' . $id);
    }

    /**
     * Decode the global id.
     *
     * @param  string $id
     * @return array
     */
    public static function decodeGlobalId($id)
    {
        return explode(":", base64_decode($id));
    }

    /**
     * Get the decoded id.
     *
     * @param  string $id
     * @return string
     */
    public static function decodeRelayId($id)
    {
        list($type, $id) = static::decodeGlobalId($id);

        return $id;
    }

    /**
     * Get the decoded GraphQL Type.
     *
     * @param  string $id
     * @return string
     */
    public static function decodeRelayType($id)
    {
        list($type, $id) = static::decodeGlobalId($id);

        return $type;
    }
}