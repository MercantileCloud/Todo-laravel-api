<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\api\AuthController;
use App\Http\Controllers\api\UserManageController;
use App\Http\Controllers\api\ManageTodoController;

// Route::get('/user', function (Request $request) {
//     return $request->user();
// })->middleware('auth:sanctum');



Route::post('login', [AuthController::class, 'login']);
Route::group(['prefix' => 'auth'], function () {
    Route::post('login', [AuthController::class, 'login']);
    Route::post('logout', [AuthController::class, 'logout'])->middleware('auth:sanctum');
    Route::get('get-details', [AuthController::class, 'getDetails'])->middleware('auth:sanctum');
});

Route::post('/create-password', [UserManageController::class, 'createPassword']);
Route::post('/forgot-password', [UserManageController::class, 'forgotPassword']);
Route::get('/verify-token', [UserManageController::class, 'verifyToken']);

Route::group(['middleware' => ['auth:sanctum']], function () {

    Route::post('send-reinvitation', [UserManageController::class, 'sendReinvitation']);

    Route::post('/manage-users/{id}', [UserManageController::class, 'update']);
    Route::apiResource('manage-users', UserManageController::class);

    Route::apiResource('manage-todo', ManageTodoController::class);
    Route::post('/manage-todo/{id}', [ManageTodoController::class, 'update']);
});
