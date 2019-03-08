<?php

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('/', function () {
    return view('welcome');
});

Route::get('/try', function () {
    return "trying";
});

Auth::routes();

Route::get('/home', 'HomeController@index')->name('home');

Route::get('login/facebook', 'Auth\LoginController@redirectToProvider');
Route::get('login/facebook/callback', 'Auth\LoginController@handleProviderCallback');

Route::get('/fb/{pageId}/likes', 'FacebookController@getPageLikes')->name('fb.getPageLikes');
Route::get('/fb/{pageId}/views', 'FacebookController@getPageViews')->name('fb.getPageViews');
Route::get('/fb/{pageId}/engagements', 'FacebookController@getPagePostEngagements')->name('fb.getPagePostEngagements');
Route::get('/fb/{pageId}/impressions', 'FacebookController@getPagePostImpressions')->name('fb.getPagePostImpressions');
