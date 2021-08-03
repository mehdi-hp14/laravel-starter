<?php
$segments = [ 'admin', 'site','customer' ];
foreach ( $segments as $segment ) {
    foreach ( scandir( __DIR__ . '/' . $segment ) as $file ) {
        if ( $file != '.' && $file != '..' ) {
            require_once( __DIR__ . '/' . $segment . '/' . $file );
        }
    }
}

Auth::routes();

Route::get( '/x', function () {
    echo phpinfo();
} )->name( 'home2' );
Route::get( '/home', 'HomeController@index' )->name( 'home' );

//dummy
Route::get( '/roles', 'PermissionController@Permission' );


