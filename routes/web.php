<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

Route::get('/', function () {
    return response()->json([
        'message' => 'Multi-Gateway Payment API',
        'version' => '1.0.0',
        'status' => 'running'
    ]);
});

Route::get('/up', function () {
    return response()->json(['status' => 'ok']);
});

Route::get('/login', function () {
    return response()->json([
        'message' => 'Please use POST /api/login to authenticate',
        'api_endpoint' => '/api/login',
        'method' => 'POST',
        'body' => [
            'email' => 'your@email.com',
            'password' => 'your_password'
        ]
    ], 401);
});
