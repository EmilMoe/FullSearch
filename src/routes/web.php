<?php

Route::resource('api/full-search', 'EmilMoe\FullSearch\Http\Controllers\FullSearchAPIController')
    ->middleware(config('full-search.middleware'))
    ->only(['index']);