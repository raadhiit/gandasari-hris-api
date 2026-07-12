<?php

use App\Http\Controllers\Api\V1\AttendanceSyncController;
use Illuminate\Support\Facades\Route;

Route::middleware('daidan.api.key')->group(function () {
    Route::get(
        '/v1/attendance/sync',
        [AttendanceSyncController::class, 'index']
    );
});
