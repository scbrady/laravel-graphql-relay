<?php

namespace Nuwave\Relay\Support;

use Illuminate\Support\Fluent;
use GraphQL\Type\Definition\ObjectType;

class GraphQLType extends Fluent
{
    /**
     * Type attributes.
     *
     * @return array
     */
    public function attributes()
    {
        return [];
    }

    /**
     * Type fields.
     *
     * @return array
     */
    public function fields()
    {
        return [];
    }

    /**
     * Get the attributes of the type.
     *
     * @return array
     */
    public function getAttributes()
    {
        $attributes = array_merge(
            $this->attributes,
            ['fields' => $this->getFields()],
            $this->attributes()
        );

        if(sizeof($this->interfaces())) {
            $attributes['interfaces'] = $this->interfaces();
        }

        return $attributes;
    }

    /**
     * The resolver for a specific field.
     *
     * @param $name
     * @param $field
     * @return \Closure|null
     */
    protected function getFieldResolver($name, $field)
    {
        if(isset($field['resolve'])) {
            return $field['resolve'];
        } else if(method_exists($this, 'resolve'.studly_case($name).'Field')) {
            $resolver = array($this, 'resolve'.studly_case($name).'Field');

            return function() use ($resolver) {
                return call_user_func_array($resolver, func_get_args());
            };
        }

        return null;
    }

    /**
     * Get the fields of the type.
     *
     * @return array
     */
    public function getFields()
    {
        $fields = $this->fields();
        $allFields = [];
        foreach($fields as $name => $field)
        {
            if(is_string($field))
            {
                $field = app($field);
                $field->name = $name;
                $allFields[$name] = $field->toArray();
            }
            else
            {
                $resolver = $this->getFieldResolver($name, $field);
                if($resolver)
                {
                    $field['resolve'] = $resolver;
                }
                $allFields[$name] = $field;
            }
        }

        return $allFields;
    }

    /**
     * Type interfaces.
     *
     * @return array
     */
    public function interfaces()
    {
        return [];
    }

    /**
     * Convert the object to an array.
     *
     * @return array
     */
    public function toArray()
    {
        return $this->getAttributes();
    }

    /**
     * Convert this class to its ObjectType.
     *
     * @return ObjectType
     */
    public function toType()
    {
        return new ObjectType($this->toArray());
    }

    /**
     * Dynamically retrieve the value of an attribute.
     *
     * @param  string  $key
     * @return mixed
     */
    public function __get($key)
    {
        $attributes = $this->getAttributes();

        return isset($attributes[$key]) ? $attributes[$key]:null;
    }

    /**
     * Dynamically check if an attribute is set.
     *
     * @param  string  $key
     * @return bool
     */
    public function __isset($key)
    {
        return isset($this->getAttributes()[$key]);
    }
}
