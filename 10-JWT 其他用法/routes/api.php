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

Route::group(['prefix'=>'v1'], function (){
    Route::resource('lesson','LessonController');
});

$api = app('Dingo\Api\Routing\Router');

$api->version('v2', function ($api) {
    $api->group(['namespace' => 'App\Api\Controllers'], function ($api) {
        $api->post('user/login', 'AuthController@authenticate');
        $api->post('user/register', 'AuthController@register');
        $api->group(['middleware' => 'jwt.auth'], function ($api) {
            $api->get('lessons', 'LessonController@index');
            $api->get('lesson/{id}', 'LessonController@show');
        });
        $api->group(['middleware' => 'jwt.refresh'], function ($api) {
            $api->get('user/info', 'AuthController@getAuthenticatedUser');
        });
    });
});
