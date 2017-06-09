<?php

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/
Route::get('/login', 'APIController@login');

Route::get('/category', 'APIController@categoryIndex');
Route::get('/category/create', 'APIController@categoryCreate');
Route::get('/category/{id}/delete', 'APIController@categoryDelete');

Route::get('/link', 'APIController@linkIndex');
Route::get('/link/search', 'APIController@linkSearch');
Route::get('/link/create', 'APIController@linkCreate');
Route::get('/link/{id}', 'APIController@linkShow');
Route::get('/link/{id}/modify', 'APIController@linkModify');
Route::get('/link/{id}/delete', 'APIController@linkDelete');
Route::get('/link/{id}/share', 'APIController@linkShare');