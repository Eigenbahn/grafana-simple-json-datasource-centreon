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

// ------------------------------------------------------------------------
// GRAFANA SIMPLE JSON DATASOURCE

Route::get('grafana','GrafanaBackend\GrafanaBackendController@testConnection');
Route::post('grafana/search','GrafanaBackend\GrafanaBackendController@search');
Route::post('grafana/query','GrafanaBackend\GrafanaBackendController@query');

// non-standard
Route::delete('grafana','GrafanaBackend\GrafanaBackendController@reloadCache');
