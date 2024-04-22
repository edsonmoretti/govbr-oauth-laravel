<?php

use App\Http\Controllers\GovBrOAuthController;
use Illuminate\Support\Facades\Route;

Route::get('/', [GovBrOAuthController::class, 'index'])->name('home');
Route::get('/oauth/authorize', [GovBrOAuthController::class, 'authorizeURL'])->name('oauth.authorize');
Route::get('/gestaoriscos/api/login/openid', [GovBrOAuthController::class, 'getToken'])->name('oauth.token');
