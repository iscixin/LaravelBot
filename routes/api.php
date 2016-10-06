<?php

use Illuminate\Http\Request;

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

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:api');

Route::get('/youtube/{videoid?}', [
	'uses' => 'APIController@youtube',
	'as' => 'api.youtube',
]);

Route::get('/youtube/dl/{videoid}.m4a', [
	'uses' => 'APIController@VideoToAudio',
	'as' => 'api.youtube.dl',
]);

Route::get('/railway/{date}', [
	'uses' => 'APIController@RailTime',
	'as' => 'api.railway.date',
]);
