<?php

namespace Nuwave\Relay\Support;

use Nuwave\Relay\GraphQL;

class GraphQLMutation extends GraphQLField
{
    /**
     * The validator instance.
     *
     * @var \Illuminate\Validation\Factory
     */
    protected $validator;

    /**
     * The container instance of GraphQL.
     *
     * @var \Laravel\Lumen\Application|mixed
     */
    protected $graphQL;

    /**
     * GraphQLMutation constructor.
     *
     */
    public function __construct()
    {
        parent::__construct();

        $this->validator = app('validator');

        $this->graphQL = app(GraphQL::class);
    }

    /**
     * Get the validation rules.
     *
     * @return array
     */
    public function getRules()
    {
        $arguments = func_get_args();

        $rules = call_user_func_array([$this, 'rules'], $arguments);
        $argsRules = [];
        foreach($this->args() as $name => $arg)
        {
            if(isset($arg['rules']))
            {
                if(is_callable($arg['rules']))
                {
                    $argsRules[$name] = call_user_func_array($arg['rules'], $arguments);
                }
                else
                {
                    $argsRules[$name] = $arg['rules'];
                }
            }
        }

        return array_merge($argsRules, $rules);
    }

    /**
     * Get the field resolver.
     *
     * @return \Closure|null
     */
    protected function getResolver()
    {
        if (!method_exists($this, 'resolve')) {
            return null;
        }

        $resolver = array($this, 'resolve');

        return function () use ($resolver) {
            $arguments = func_get_args();

            $this->validate($arguments);

            return call_user_func_array($resolver, $arguments);
        };
    }

    /**
     * The validation rules for this mutation.
     *
     * @return array
     */
    protected function rules()
    {
        return [];
    }

    /**
     * Validate relay mutation.
     *
     * @param  array $args
     * @throws ValidationError
     * @return void
     */
    protected function validate(array $args)
    {
        $rules = call_user_func_array([$this, 'getRules'], $args);

        if (sizeof($rules)) {
            $validator = $this->validator->make($args['input'], $rules);

            if ($validator->fails()) {
                throw with(new ValidationError('Validation failed', $validator));
            }
        }
    }
}
