<?php

return [
    'server' => env('TATTLER_SERVER'), // domain name of Tattler backend
    'ssl'    => env('TATTLER_SSL', false), // false == ws://, true == wss://
    'root'   => env('TATTLER_ROOT', null) // use it for sharing namespaces between different web sites. Leave NULL for automatic generation of unique value.
];
