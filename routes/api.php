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

Route::middleware('auth:api')->get('/user', function (Request $request) {
    return $request->user();
});

Route::post('register', 'API\RegisterController@register');
Route::post('login', 'API\RegisterController@login');

Route::group(['middleware' => 'auth:api'], function(){
    Route::get('details', 'API\RegisterController@details')->name('register.details');
    Route::post('logout', 'API\RegisterController@logout');   

    // fb
	Route::post('/fb/add-page', 'FacebookController@addPage')->name('fb.addPage');
});

Route::get('/fb/{pageId}/dashboard-metrics', 'FacebookController@getDashboardMetrics')->name('fb.getDashboardMetrics');
Route::get('/fb/{pageId}/posts-details', 'FacebookController@getPagePostsDetails')->name('fb.getPagePostsDetails');
