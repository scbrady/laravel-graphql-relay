<?php

namespace Nuwave\Relay\Support;

use GraphQL\Type\Definition\Type;
use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\ResolveInfo;
use GraphQL\Type\Definition\InputObjectType;
use Nuwave\Relay\Node\Node;

abstract class RelayMutation extends GraphQLMutation
{
    /**
     * Type being mutated is RelayType.
     *
     * @var boolean
     */
    protected $mutatesRelayType = true;

    /**
     * Generate Relay compliant arguments.
     *
     * @return array
     */
    public function args()
    {
        $inputType = new InputObjectType([
            'name' => ucfirst($this->name()) . 'Input',
            'fields' => array_merge($this->inputFields(), [
                'clientMutationId' => [
                    'type' => Type::nonNull(Type::string())
                ]
            ])
        ]);

        return [
            'input' => [
                'type' => Type::nonNull($inputType)
            ]
        ];
    }

    /**
     * List of available input fields.
     *
     * @return array
     */
    abstract protected function inputFields();

    /**
     * Perform mutation.
     *
     * @param  array       $input
     * @param  ResolveInfo $info
     * @return array
     */
    abstract protected function mutateAndGetPayload(array $input, ResolveInfo $info);

    /**
     * Get name of mutation.
     *
     * @return string
     */
    abstract protected function name();

    /**
     * List of output fields.
     *
     * @return array
     */
    abstract protected function outputFields();

    /**
     * Resolve mutation.
     *
     * @param  mixed       $_
     * @param  array       $args
     * @param  ResolveInfo $info
     * @return array
     */
    public function resolve($_, array $args, ResolveInfo $info)
    {
        if ($this->mutatesRelayType && isset($args['input']['id'])) {
            $args['input']['relay_id'] = $args['input']['id'];
            $args['input']['id'] = Node::idFromGlobalId($args['input']['id']);
        }

        $this->validate($args);

        $payload = $this->mutateAndGetPayload($args['input'], $info);

        return array_merge($payload, [
            'clientMutationId' => $args['input']['clientMutationId']
        ]);
    }

    /**
     * Generate Relay compliant output type.
     *
     * @return InputObjectType
     */
    public function type()
    {
        return new ObjectType([
            'name' => ucfirst($this->name()) . 'Payload',
            'fields' => array_merge($this->outputFields(), [
                'clientMutationId' => [
                    'type' => Type::nonNull(Type::string())
                ]
            ])
        ]);
    }
}
