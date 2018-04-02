<?php


/*
|---------------------------------------------------------------
| Admin related routes
|---------------------------------------------------------------
|
| Define routes that starts with /admin/ here
| All routes are already protected with 'role:admin' middleware
| Don't prefix your routes with /admin/
|
*/

Route::group(['prefix' => 'users', 'as' => 'users.'], function () {
    Route::patch('/{user}/update-roles', 'UserController@updateRoles')->name('update-roles');
    Route::patch('/{user}/toggle-block', 'UserController@toggleBlockedStatus')->name('toggle-block');
});

Route::resource(
    'users', 'UserController', ['only' => [
        'index', 'edit', 'update', 'destroy'
    ]]
);