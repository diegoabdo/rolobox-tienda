<?php

return [

    /*
    | Dónde viven las páginas de React. Con ensure_pages_exist en las pruebas,
    | assertInertia() falla si el componente no existe.
    */
    'pages' => [
        'ensure_pages_exist' => false,
        'paths' => [resource_path('js/pages')],
        'extensions' => ['tsx', 'ts', 'jsx', 'js'],
    ],

    'testing' => [
        'ensure_pages_exist' => true,
    ],

];
