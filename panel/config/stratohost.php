<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Source repository
    |--------------------------------------------------------------------------
    |
    | Used to build the copy-pasteable install command shown when creating a
    | node (`stratohost:node:create`) - the admin clones this repo on the
    | node and runs installer/agent-install.sh from it.
    |
    */

    'repo_url' => env('STRATOHOST_REPO_URL', 'https://github.com/Shisakoff/Stratohost-panel.git'),

];
