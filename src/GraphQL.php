<?php

namespace Nuwave\Relay;

use Exception;
use GraphQL\GraphQL as GraphQLBase;
use GraphQL\Schema;
use GraphQL\Error;
use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\InterfaceType;
use Nuwave\Relay\Support\ValidationError;
use Illuminate\Http\JsonResponse;

class GraphQL
{
    /**
     * The mutations available in the schema.
     *
     * @var array
     */
    protected $mutations = [];

    /**
     * The queries available in the schema.
     *
     * @var array
     */
    protected $queries = [];

    /**
     * The types available in the schema.
     *
     * @var array
     */
    protected $types = [];

    /**
     * Registered type instances.
     *
     * @var array
     */
    protected $typesInstances = [];

    /**
     * Add a new type to the array.
     *
     * @param string $class
     * @param string $name
     */
    public function addType($class, $name)
    {
        $this->types[$name] = $class;
    }

    /**
     * Build the GraphQL schema.
     *
     * @return Schema
     * @throws Exception
     */
    public function schema()
    {
        $this->typesInstances = [];

        $schema = config('graphql.schema');

        foreach($this->types as $name => $type) {
            $this->type($name);
        }

        $queryType = $this->buildTypeFromFields(array_get($schema, 'query', []), [
            'name' => 'Query'
        ]);

        $mutationType = $this->buildTypeFromFields(array_get($schema, 'mutation', []), [
            'name' => 'Mutation'
        ]);

        return new Schema($queryType, $mutationType);
    }

    /**
     * Build the mutation and query type from the supplied fields.
     *
     * @param       $fields
     * @param array $opts
     * @return ObjectType
     */
    protected function buildTypeFromFields($fields, $opts = [])
    {
        $typeFields = [];

        foreach($fields as $key => $field) {
            if(is_string($field)) {
                $typeFields[$key] = app($field)->toArray();
            } else {
                $typeFields[$key] = $field;
            }
        }

        return new ObjectType(array_merge([
            'fields' => $typeFields
        ], $opts));
    }

    /**
     * Run the GraphQL query.
     *
     * @param       $query
     * @param array $params
     * @return array
     */
    public function runQuery($query, $params = [])
    {
        $result = GraphQLBase::executeAndReturnResult($this->schema(), $query, null, $params);

        if (!empty($result->errors)) {
            return new JsonResponse([
                'data' => $result->data,
                'errors' => array_map([$this, 'formatError'], $result->errors)
            ], 500);
        } else {
            return new JsonResponse(['data' => $result->data]);
        }
    }

    /**
     * Get the array version of a GraphQL type.
     *
     * @param      $name
     * @param bool $fresh
     * @return mixed
     * @throws Exception
     */
    public function type($name, $fresh = false)
    {
        if(!isset($this->types[$name])) {
            throw new Exception('Type '.$name.' not found.');
        }

        if(!$fresh && isset($this->typesInstances[$name])) {
            return $this->typesInstances[$name];
        }

        $type = $this->types[$name];

        if(!is_object($type)) {
            $type = app($type);
        }

        $instance = $type->toType();

        $this->typesInstances[$name] = $instance;

        if($type->interfaces) {
            InterfaceType::addImplementationToInterfaces($instance);
        }

        return $instance;
    }

    /**
     * Format errors thrown by GraphQL.
     *
     * @param Error $e
     * @return array
     */
    public function formatError(Error $e)
    {
        $error = [
            'message' => $e->getMessage()
        ];

        $locations = $e->getLocations();

        if(!empty($locations)) {
            $error['locations'] = array_map(function($loc) { return $loc->toArray();}, $locations);
        }

        $previous = $e->getPrevious();

        if ($previous && $previous instanceof ValidationError) {
            $error['validation'] = $previous->getValidatorMessages();
        }

        return $error;
    }
}
