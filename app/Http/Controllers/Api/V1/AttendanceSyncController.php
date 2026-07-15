<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\AttendanceSyncRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use UnexpectedValueException;

class AttendanceSyncController extends Controller
{
    private const DEFAULT_LIMIT = 1000;

    public function index(AttendanceSyncRequest $request): JsonResponse
    {
        $lastId = $request->integer('lastAttendanceId');
        $limit = $request->integer(
            'limit',
            self::DEFAULT_LIMIT
        );

        $rows = DB::table('att_logs as attendance')
            ->leftJoin(
                'employees as employee',
                'employee.id',
                '=',
                'attendance.user_pin'
            )
            ->leftJoin(
                'devices as device',
                'device.id',
                '=',
                'attendance.device_id'
            )
            ->leftJoin(
                'areas as area',
                'area.id',
                '=',
                'device.area_id'
            )
            ->select([
                'attendance.id as attendance_id',
                DB::raw("DATE_FORMAT(attendance.timestamp, '%Y-%m-%dT%H:%i:%s') as attendance_time"),
                'attendance.status',
                'attendance.device_id',
                'employee.id as employee_id',
                'employee.card_number as daidan_nik',
                'attendance.user_pin as machine_user_id',
                'area.name as site_code',
            ])
            ->where('attendance.id', '>', $lastId)
            ->orderBy('attendance.id')
            ->limit($limit + 1)
            ->get();

        $hasMore = $rows->count() > $limit;
        $data = [];
        $lastAttendanceId = $lastId;

        foreach ($rows as $index => $attendance) {
            if ($index >= $limit) {
                break;
            }

            $lastAttendanceId = (int) $attendance->attendance_id;

            $data[] = [
                'attendanceId' => $lastAttendanceId,
                'daidanNik' => $attendance->daidan_nik !== null
                    ? (string) $attendance->daidan_nik
                    : null,
                'employeeId' => (string) $attendance->employee_id,
                'attendanceTime' => $attendance->attendance_time,
                'attendanceType' => $this->resolveAttendanceType(
                    (int) $attendance->status
                ),
                'machineUserId' => (string) $attendance->machine_user_id,
                'deviceId' => (string) $attendance->device_id,
                'siteCode' => (string) $attendance->site_code,
            ];
        }

        return response()->json([
            'success' => true,
            'lastAttendanceId' => $lastAttendanceId,
            'hasMore' => $hasMore,
            'data' => $data,
        ]);
    }


    private function resolveAttendanceType(int $status): string
    {
        return match ($status) {
            0 => 'IN',
            1 => 'OUT',
            2 => 'Break-In',
            3 => 'Break-Out',
            4 => 'Overtime-In',
            5 => 'Overtime-Out',
            255 => 'raw/unknown',

            default => throw new UnexpectedValueException(
                "Unsupported attendance status: {$status}."
            ),
        };
    }
}
