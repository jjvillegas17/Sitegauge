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
// Route::post('login', array('middleware' => 'cors', 'uses' => 'API\RegisterController@login'));


Route::group(['middleware' => 'auth:api'], function(){
    Route::get('details', 'API\RegisterController@details')->name('register.details');
    Route::post('logout', 'API\RegisterController@logout');   

    // fb
});

Route::get('/{userId}/accounts', 'API\RegisterController@getAllAccounts')->name('getAllAccounts');
Route::get('/{userId}/info', 'API\RegisterController@getUser')->name('getUser');

Route::get('/fb/{userId}/pages', 'FacebookController@getPagesOfUser')->name('fb.getPages');
// ?pageToken={$token}
Route::post('/fb/add-page', 'FacebookController@addPage')->name('fb.addPage');
Route::get('/fb/{pageId}/dashboard-metrics', 'FacebookController@getDashboardMetrics')->name('fb.getDashboardMetrics');
Route::get('/fb/{pageId}/fetch-metrics', 'FacebookController@fetchMetrics')->name('fb.fetchMetrics');
Route::get('/fb/{pageId}/posts-details', 'FacebookController@getPagePostsDetails')->name('fb.getPagePostsDetails');
Route::get('/fb/{pageId}/min-date', 'FacebookController@getMinDate')->name('fb.getMinDate');

Route::get("/twitter/{userId}/accounts", 'TwitterController@getAccountsOfUser')->name('
	getAccountsOfUser');
Route::post("/twitter/add-account", 'TwitterController@addAccount')->name('addAccount');
Route::post("/twitter/{twitterId}/upload", 'TwitterController@uploadCSV')->name('upload');
Route::get("/twitter/{userId}/update-account", 'TwitterController@updateAccount')->name('updateAccount');
Route::get("/twitter/{twitterId}/tweet-metrics", 'TwitterController@getTweetMetrics')->name('getTweetMetrics');

Route::post("/google/add-account", 'GoogleController@addAccount');
Route::get("/google/get-accounts", 'GoogleController@getAccounts');
Route::get("/google/{userId}/accounts", 'GoogleController@getAccountsOfUser');
Route::get("/google/{profileId}/get-audience-metrics", 'GoogleController@getAudienceMetrics');
Route::get("/google/{profileId}/get-acquisition-metrics", 'GoogleController@getAcquisitionMetrics');
Route::get("/google/{profileId}/get-behavior-metrics", 'GoogleController@getBehaviorMetrics');
Route::get("/google/{profileId}/fetch-metrics", 'GoogleController@fetchMetrics');
// Route::get('login/{provider}', 'Auth\LoginController@redirectToProvider');
// Route::get('login/facebook/callback', 'Auth\LoginController@handleProviderCallbackFacebook');
// Route::get('login/twitter/callback', 'Auth\LoginController@handleProviderCallbackTwitter');


// Route::get('login/twitter', 'Auth\LoginController@redirectToTwitter');
// Route::get('login/twitter/callback', 'Auth\LoginController@handleProviderCallbackTwitter');




