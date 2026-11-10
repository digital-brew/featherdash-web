<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Docs Source
    |--------------------------------------------------------------------------
    |
    | Markdown synced into the `doc` post type by `wp acorn docs:sync`.
    | `index` is the docs home (shown at /docs/); its links set the docs order.
    | `changelog` becomes the Changelog page.
    |
    */

    'path' => env('DOCS_PATH', public_path('content/plugins/featherdash/docs')),

    'index' => 'README.md',

    'changelog' => env('DOCS_CHANGELOG', public_path('content/plugins/featherdash/CHANGELOG.md')),

];
