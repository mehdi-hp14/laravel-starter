<?php
Route::group( [
    'middleware' => [ 'web', 'panel' ],
    'namespace'  => '\Kaban\Components\Customer\Words\Controllers'
], function () {
        Route::group( [ 'as' => 'customer.contents.' ], function () {

            Route::get('/words/index', 'WordsController@index')
                ->name('index');

            Route::post( '/words/store', [
                'as'   => 'words.store',
                'uses' => 'WordsController@store'
            ] );
            Route::post( '/words/search', [
                'as'   => 'words.search',
                'uses' => 'WordsController@search'
            ] );
            Route::post( '/words/search-tags', [
                'as'   => 'words.searchTags',
                'uses' => 'WordsController@searchTags'
            ] );
            Route::post( '/words/{id}/edit', [
                'as'   => 'words.edit',
                'uses' => 'WordsController@edit'
            ] );
            Route::post( '/words/{id}/update', [
                'as'   => 'words.update',
                'uses' => 'WordsController@update'
            ] );
            Route::post( '/words/{id}/destroy', [
                'as'   => 'words.delete',
                'uses' => 'WordsController@destroy'
            ] );
            Route::post( '/words/all', [
                'as'   => 'words.all',
                'uses' => 'WordsController@all'
            ] );
        } );
} );
//Route::get('/{link?}', 'AuthenticationBaseController@showForm')
//    ->where('link', '[^.]*')
//    ->name('auth.form')
//    ->middleware('guest');
