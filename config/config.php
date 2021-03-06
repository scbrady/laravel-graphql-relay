<?php
/*
|--------------------------------------------------------------------------
| Schema File
|--------------------------------------------------------------------------
|
| You can utilize this file to register all of you GraphQL schma queries
| and mutations. You can group collections together by namespace or middlware.
|
*/

return [
    'schema' => function () {
        Relay::group(['namespace' => 'Nuwave\\Relay'], function () {
            Relay::group(['namespace' => 'Node'], function () {
                Relay::query('node', 'NodeQuery');
                Relay::type('node', 'NodeType');
            });

            Relay::type('pageInfo', 'Types\\PageInfoType');
        });

        // Additional Queries, Mutations and Types...
    }
];
