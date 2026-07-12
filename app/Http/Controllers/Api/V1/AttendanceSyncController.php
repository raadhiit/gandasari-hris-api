<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\AttendanceSyncRequest;
use App\Models\AttendanceLog;
use Illuminate\Http\JsonResponse;
use UnexpectedValueException;

class AttendanceSyncController extends Controller
{
    private const DEFAULT_LIMIT = 500;

    public function index(AttendanceSyncRequest $request): JsonResponse
    {
        $lastId = $request->integer('lastAttendanceId');
        $limit = $request->integer(
            'limit',
            self::DEFAULT_LIMIT
        );

        $rows = AttendanceLog::query()
            ->from('att_logs as attendance')
            ->leftJoin(
                'employees as employee',
                'employee.id',
                '=',
                'attendance.user_pin'
            )
            ->leftJoin('device_users as machine_user', function ($join) {
                $join->on(
                    'machine_user.device_id',
                    '=',
                    'attendance.device_id'
                )->on(
                    'machine_user.pin',
                    '=',
                    'attendance.user_pin'
                );
            })
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
                'attendance.user_pin as attendance_user_pin',
                'attendance.timestamp',
                'attendance.status',
                'attendance.device_id',
                'employee.id as employee_id',
                'employee.daidan_nik',
                'machine_user.id as device_user_id',
                'machine_user.pin as machine_user_id',
                'area.name as site_code',
            ])
            ->where('attendance.id', '>', $lastId)
            ->orderBy('attendance.id')
            ->limit($limit + 1)
            ->get();

        $hasMore = $rows->count() > $limit;

        $data = $rows
            ->take($limit)
            ->values();

        return response()->json([
            'success' => true,
            'lastAttendanceId' => (int) (
                $data->last()?->attendance_id ?? $lastId
            ),
            'hasMore' => $hasMore,
            'data' => $data->map(
                fn(AttendanceLog $attendance): array => [
                    'attendanceId' => (int) $attendance->attendance_id,
                    'daidanNik' => $attendance->daidan_nik !== null
                        ? (string) $attendance->daidan_nik
                        : null,
                    'employeeId' => (string) $attendance->employee_id,
                    'attendanceTime' => $attendance->timestamp
                        ->format('Y-m-d\TH:i:s'),
                    'attendanceType' => $this->resolveAttendanceType(
                        (int) $attendance->status
                    ),
                    'machineUserId' => (string) $attendance->machine_user_id,
                    'deviceId' => (string) $attendance->device_id,
                    'siteCode' => (string) $attendance->site_code,
                ]
            )->all(),
        ]);
    }

    private function resolveAttendanceType(int $status): string
    {
        return match ($status) {
            0 => 'IN',
            1 => 'OUT',

            default => throw new UnexpectedValueException(
                "Unsupported attendance status: {$status}."
            ),
        };
    }
}
