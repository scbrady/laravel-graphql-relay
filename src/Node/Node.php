<?php

namespace Nuwave\Relay\Node;

class Node
{
    /**
     * Takes a type name and an ID specific to that type name, and returns a
     * "global ID" that is unique among all types.
     *
     * @param  string $type
     * @param  string|integer $id
     * @return string
     */
    public static function toGlobalId($type, $id)
    {
        return base64_encode($type . ':' . $id);
    }

    /**
     * Takes the "global ID" created by toGlobalID, and returns the type name and ID
     * used to create it.
     *
     * @param  string $id
     * @return array
     */
    public static function fromGlobalId($id)
    {
        return explode(":", base64_decode($id));
    }

    /**
     * Get the id from the global id.
     *
     * @param  string $id
     * @return string
     */
    public static function idFromGlobalId($id)
    {
        list($type, $id) = static::fromGlobalId($id);

        return (int) $id;
    }

    /**
     * Get the type from the global id.
     *
     * @param  string $id
     * @return string
     */
    public static function typeFromGlobalId($id)
    {
        list($type, $id) = static::fromGlobalId($id);

        return $type;
    }
}