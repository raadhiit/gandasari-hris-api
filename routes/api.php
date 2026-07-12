<?php

use App\Http\Controllers\Api\V1\AttendanceSyncController;
use App\Http\Controllers\Api\V1\EmployeeProvisioningController;
use Illuminate\Support\Facades\Route;

Route::middleware('daidan.api.key')->group(function () {
    Route::get('/v1/attendance/sync',[AttendanceSyncController::class, 'index']);
    Route::put('/v1/employees/{daidanNik}',[EmployeeProvisioningController::class, 'upsert']);
});
