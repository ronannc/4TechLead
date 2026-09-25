<?php

use App\Http\Controllers\Web\OAuthLoginController;
use Illuminate\Support\Facades\Route;

Route::get('/login', [OAuthLoginController::class, 'create'])->name('login');
Route::post('/login', [OAuthLoginController::class, 'store'])->name('login.store');

Route::get('/', function () {
    return view('welcome');
});

Route::get('/docs', function () {
    return response()->file(resource_path('docs/index.html'));
})->name('docs');

Route::get('/docs/openapi', function () {
    $document = json_decode((string) file_get_contents(resource_path('docs/openapi.json')), true, flags: JSON_THROW_ON_ERROR);
    $document['servers'] = [
        [
            'url' => rtrim((string) config('app.url'), '/').'/api/v1',
            'description' => 'Current application environment',
        ],
    ];

    return response()->json(
        $document
    );
})->name('docs.openapi');
