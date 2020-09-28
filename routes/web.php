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

Route::get('/', 'Controller@index');

/**
 * 网页
 */
Route::view('/search', 'search');
Route::view('/library', 'library');
Route::view('/hotlist', 'hotlist');

/**
 * api接口
 */
Route::prefix('/novel')->group(
    function () {
        Route::get('/search', 'NovelController@search');
        Route::get('/library', 'NovelController@library');
        Route::get('/hotlist', 'NovelController@hotlist');
        Route::post('/collect', 'NovelController@collect');
        Route::post('/delete', 'NovelController@delete');
        Route::post('/sync', 'NovelController@sync');
        Route::get('/sync/process', 'NovelController@syncProcess');
        Route::get('/download/zip', 'NovelController@downloadZip');
        Route::get('/download/txt', 'NovelController@downloadTxt');
        Route::get('/collected', 'NovelController@isCollectedBatch');
    }
);


