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

Route::get('/login', 'API\LoginController@login');

Route::get('/category', 'API\CategoryController@index');
Route::get('/category/create', 'API\CategoryController@store');
Route::get('/category/{categoryID}/delete', 'API\CategoryController@delete');

Route::get('/link', 'API\LinkController@index');
Route::get('/link/search','API\LinkController@search');
Route::get('/link/create', 'API\LinkController@store');
Route::get('/link/{linkID}', 'API\LinkController@show');
Route::get('/link/{linkID}/modify', 'API\LinkController@update');
Route::get('/link/{linkID}/delete', 'API\LinkController@delete');
Route::get('/link/{linkID}/share', 'API\LinkController@share');