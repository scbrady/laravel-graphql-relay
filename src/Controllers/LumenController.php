<?php

namespace Nuwave\Relay\Controllers;

use Illuminate\Http\Request;
use Laravel\Lumen\Routing\Controller;
use Nuwave\Relay\GraphQL;

class LumenController extends Controller
{
    /**
     * Execute GraphQL query.
     *
     * @param  Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function query(Request $request)
    {
        $query = $request->get('query');

        $variables = $request->get('variables');

        if (is_string($variables)) {
            $variables = json_decode($variables, true);
        }

        return app(GraphQL::class)->runQuery($query, $variables);
    }
}
