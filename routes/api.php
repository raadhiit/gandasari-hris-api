<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\V1\AttendanceSyncController;
use App\Http\Controllers\Api\V1\EmployeeProvisioningController;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::get('/v1/attendance/sync', [AttendanceSyncController::class, 'index'])
    ->middleware([
        'auth:sanctum',
        'abilities:attendance:read',
    ]);

Route::put('/v1/employees/{daidanNik}', [EmployeeProvisioningController::class, 'update'])
    ->middleware([
        'auth:sanctum',
        'abilities:employees:write',
    ]);
