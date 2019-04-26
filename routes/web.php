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
Route::get('/123', function () {
    return 123;
});
// Auth::routes();

// Route::get('/home', 'HomeController@index')->name('home');

// Route::get('home/facebook', 'FacebookController@home')->name('fb.home');
// Route::get('login/facebook', 'Auth\LoginController@redirectToProvider');
// Route::get('login/facebook/callback', 'Auth\LoginController@handleProviderCallbackFacebook');


Route::group(['middleware' => 'auth:api'], function(){
		
});
Route::get('login/google', 'Auth\LoginController@redirectToGoogle');
Route::get('login/google/callback', 'Auth\LoginController@handleGoogleCallback');
Route::get('login/facebook/callback', 'Auth\LoginController@handleProviderCallbackFacebook');
Route::get('login/twitter', 'Auth\LoginController@redirectToTwitter');
Route::get('login/twitter/callback', 'Auth\LoginController@handleTwitterLoginCallback');
