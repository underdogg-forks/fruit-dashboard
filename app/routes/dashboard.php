<?php

/**
 * Routes for dashboard pages
 * @see DashboardController
 */
Route::group([
        'prefix'    => 'dashboard',
    ], function() {

    Route::any('', [
        'before' => 'auth',
        'as'     => 'dashboard.dashboard',
        'uses'   => 'DashboardController@anyDashboard'
    ]);

    Route::get('manage', [
        'before' => 'auth',
        'as'     => 'dashboard.manage',
        'uses'   => 'DashboardController@getManageDashboards'
    ]);

    Route::any('delete/{dashboardId}', [
        'before' => 'auth',
        'as'     => 'dashboard.delete',
        'uses'   => 'DashboardController@anyDeleteDashboard'
    ]);

    Route::any('toggle-lock/{dashboardId}', [
        'before' => 'auth',
        'as'     => 'dashboard.toggle-lock',
        'uses'   => 'DashboardController@anyToggleDashboardLock'
    ]);

    Route::post('rename/{dashboardId}', [
        'before' => 'auth',
        'as'     => 'dashboard.rename',
        'uses'   => 'DashboardController@postRenameDashboard'
    ]);

    Route::post('create/', [
        'before' => 'auth',
        'as'     => 'dashboard.create',
        'uses'   => 'DashboardController@postCreateDashboard'
    ]);

    Route::any('get-dashboards', [
        'before' => 'auth',
        'as'     => 'dashboard.get-dashboards',
        'uses'   => 'DashboardController@anyGetDashboards'
    ]);
});
