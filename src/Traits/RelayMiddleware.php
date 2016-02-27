<?php

namespace Nuwave\Relay\Traits;

use Illuminate\Http\Request;

trait RelayMiddleware
{
    /**
     * Generate middleware and connections from query.
     *
     * @param  Request $request
     * @return array
     */
    public function setupQuery(Request $request)
    {
        $relay = app('relay');
        $relay->setupRequest($request->get('query'));

        foreach ($relay->middleware() as $middleware) {
            $this->middleware($middleware);
        }
    }
}
