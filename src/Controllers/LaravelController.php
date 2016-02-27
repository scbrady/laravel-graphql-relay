<?php

namespace Nuwave\Relay\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Nuwave\Relay\GraphQL;

class LaravelController extends Controller
{
    /**
     * The controller's GraphQL instance.
     *
     * @var GraphQL
     */
    protected $graphQL;

    /**
     * RelayController constructor.
     *
     * @param GraphQL $graphQL
     */
    public function __construct(GraphQL $graphQL)
    {
        $this->graphQL = $graphQL;
    }

    /**
     * Execute GraphQL query.
     *
     * @param  Request $request
     * @return Response
     */
    public function query(Request $request)
    {
        $query = $request->get('query');

        $variables = $request->get('variables');

        if (is_string($variables)) {
            $variables = json_decode($variables, true);
        }

        return $this->graphQL->runQuery($query, $variables);
    }
}
