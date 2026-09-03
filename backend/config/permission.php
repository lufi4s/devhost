<?php

return [

    'models' => [

        'permission' => Spatie\Permission\Models\Permission::class,

        'role' => Spatie\Permission\Models\Role::class,

    ],

    'table_name' => 'permissions',

    'cache' => [
        'key' => 'spatie.permission.cache',
        'driver' => 'array',
    ],

];
