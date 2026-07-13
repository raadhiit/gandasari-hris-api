<?php

namespace App\Http\Controllers;

use App\Models\AttendanceLog;
use App\Models\Device;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

class FingerprintPushController extends Controller
{

    public function getRequest(Request $request): Response
    {
        Log::info('Fingerprint getrequest', [
            'serial_number' => $request->query('SN'),
            'query' => $request->query(),
            'body' => $request->getContent(),
            'ip' => $request->ip(),
        ]);

        return response("OK\n", 200, [
            'Content-Type' => 'text/plain',
        ]);
    }

    public function deviceCommand(Request $request): Response
    {
        Log::info('Fingerprint devicecmd', [
            'serial_number' => $request->query('SN'),
            'query' => $request->query(),
            'body' => $request->getContent(),
            'ip' => $request->ip(),
        ]);

        return response("OK\n", 200, [
            'Content-Type' => 'text/plain',
        ]);
    }

    public function cdata(Request $request): Response
    {
        $serialNumber = trim((string) $request->query('SN'));
        $table = strtoupper(trim((string) $request->query('table')));
        $payload = trim($request->getContent());

        Log::info('Fingerprint cdata request', [
            'method' => $request->method(),
            'serial_number' => $serialNumber,
            'query' => $request->query(),
            'body' => $payload,
            'ip' => $request->ip(),
        ]);

        /*
         * GET biasanya dipakai mesin untuk mengambil konfigurasi.
         */
        if ($request->isMethod('get')) {
            return $this->configurationResponse($serialNumber);
        }

        $device = Device::query()
            ->where('serial_number', $serialNumber)
            ->first();

        if (! $device) {
            Log::warning('Fingerprint device not registered', [
                'serial_number' => $serialNumber,
                'ip' => $request->ip(),
            ]);

            return response(
                "ERROR: UNKNOWN DEVICE\n",
                404,
                ['Content-Type' => 'text/plain']
            );
        }

        $device->update([
            'last_seen_at' => now(),
        ]);

        if ($table !== 'ATTLOG' || $payload === '') {
            return response(
                "OK: 0\n",
                200,
                ['Content-Type' => 'text/plain']
            );
        }

        try {
            $insertedCount = $this->storeAttendanceLogs(
                deviceId: $device->id,
                payload: $payload,
            );

            /*
             * Memberi tahu mesin jumlah baris yang berhasil diproses.
             */
            return response(
                "OK: {$insertedCount}\n",
                200,
                ['Content-Type' => 'text/plain']
            );
        } catch (Throwable $exception) {
            Log::error('Failed storing fingerprint attendance', [
                'serial_number' => $serialNumber,
                'device_id' => $device->id,
                'payload' => $payload,
                'message' => $exception->getMessage(),
            ]);

            /*
             * Jangan balas OK ketika insert gagal.
             * Supaya mesin tidak menganggap data sudah diterima.
             */
            return response(
                "ERROR\n",
                500,
                ['Content-Type' => 'text/plain']
            );
        }
    }

    private function storeAttendanceLogs(
        int $deviceId,
        string $payload
    ): int {
        $lines = preg_split('/\r\n|\r|\n/', $payload);

        if ($lines === false) {
            return 0;
        }

        return DB::transaction(function () use (
            $deviceId,
            $lines
        ): int {
            $processedCount = 0;

            foreach ($lines as $line) {
                $line = trim($line);

                if ($line === '') {
                    continue;
                }

                /*
                 * Payload mesin menggunakan tab sebagai pemisah.
                 */
                $columns = explode("\t", $line);

                if (count($columns) < 2) {
                    Log::warning('Invalid ATTLOG row', [
                        'device_id' => $deviceId,
                        'line' => $line,
                    ]);

                    continue;
                }

                $userPin = trim((string) ($columns[0] ?? ''));
                $attendanceTime = trim(
                    (string) ($columns[1] ?? '')
                );
                $status = (int) ($columns[2] ?? 0);
                $verifyMode = (int) ($columns[3] ?? 0);
                $workCode = (int) ($columns[4] ?? 0);

                if (
                    $userPin === ''
                    || $attendanceTime === ''
                ) {
                    Log::warning('Incomplete ATTLOG row', [
                        'device_id' => $deviceId,
                        'line' => $line,
                    ]);

                    continue;
                }

                /*
                 * Hindari data ganda ketika mesin mengirim ulang payload.
                 */
                AttendanceLog::query()->firstOrCreate(
                    [
                        'device_id' => $deviceId,
                        'user_pin' => $userPin,
                        'timestamp' => $attendanceTime,
                    ],
                    [
                        'status' => $status,
                        'verify_mode' => $verifyMode,
                        'work_code' => $workCode,
                    ]
                );

                $processedCount++;
            }

            return $processedCount;
        });
    }

    private function configurationResponse(
        string $serialNumber
    ): Response {
        $response = implode("\n", [
            "GET OPTION FROM: {$serialNumber}",
            'Stamp=9999',
            'OpStamp=9999',
            'PhotoStamp=9999',
            'ErrorDelay=30',
            'Delay=5',
            'TransInterval=1',
            'TransFlag=1111000000',
            'Realtime=1',
            'Encrypt=0',
            '',
        ]);

        return response(
            $response,
            200,
            ['Content-Type' => 'text/plain']
        );
    }
}
