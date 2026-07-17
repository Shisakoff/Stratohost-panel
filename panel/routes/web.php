<?php

use Illuminate\Support\Facades\Route;

// Vue Router uses history mode, so every non-API, non-Sanctum GET route
// serves the same SPA shell and lets the client-side router take over.
Route::get('/{any}', function () {
    return view('app');
})->where('any', '^(?!api|sanctum).*$');
