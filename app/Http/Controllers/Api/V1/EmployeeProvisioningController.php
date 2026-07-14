<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\UpsertEmployeeRequest;
use App\Models\Employee;
use Illuminate\Database\QueryException;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class EmployeeProvisioningController extends Controller
{
    public function upsert( UpsertEmployeeRequest $request): JsonResponse 
    {
        try {
            $result = DB::transaction( function () use ($request): array {
                    $daidanNik = $request->daidanNik();

                    $gandasariHrisId = $request->gandasariHrisId();

                    $employeeByDaidanNik = Employee::query()
                        ->where('daidan_nik', $daidanNik)
                        ->lockForUpdate()
                        ->first();

                    $employeeByGandasariId =$gandasariHrisId !== null
                                            ? Employee::query()
                                                ->whereKey($gandasariHrisId)
                                                ->lockForUpdate()
                                                ->first()
                                            : null;

                    if ( $gandasariHrisId !== null && $employeeByGandasariId === null) {
                        return [
                            'error' => [
                                'status' => 404,
                                'code' =>
                                    'GANDASARI_EMPLOYEE_NOT_FOUND',
                                'message' =>
                                    'Gandasari HRIS employee was not found.',
                            ],
                        ];
                    }

                    if ($employeeByDaidanNik !== null && $employeeByGandasariId !== null && ! $employeeByDaidanNik->is($employeeByGandasariId)) 
                    {
                        return [
                            'error' => [
                                'status' => 409,
                                'code' =>
                                    'EMPLOYEE_IDENTITY_CONFLICT',
                                'message' =>
                                    'Daidan NIK and Gandasari HRIS ID refer to different employees.',
                            ],
                        ];
                    }

                    if ($employeeByGandasariId !== null && $employeeByGandasariId->daidan_nik !== null && $employeeByGandasariId->daidan_nik !== $daidanNik) 
                    {
                        return [
                            'error' => [
                                'status' => 409,
                                'code' =>
                                    'EMPLOYEE_IDENTITY_CONFLICT',
                                'message' =>
                                    'The Gandasari HRIS employee is already linked to another Daidan NIK.',
                            ],
                        ];
                    }

                    $employee = $employeeByDaidanNik ?? $employeeByGandasariId;
                    $created = $employee === null;

                    if ($created) {
                        $employee = new Employee();
                    }

                    $employee->daidan_nik = $daidanNik;
                    $employee->fill($request->employeeAttributes());
                    $changed = $employee->isDirty();
                    $employee->save();

                    $action = match (true) {
                        $created => 'created',
                        $changed => 'updated',
                        default => 'unchanged',
                    };

                    return [
                        'employee' => $employee,
                        'action' => $action,
                    ];
                }
            );
        } catch (QueryException $exception) {
            if ((string) $exception->getCode() === '23000') {
                return response()->json([
                    'success' => false,
                    'code' => 'EMPLOYEE_DATA_CONFLICT',
                    'message' =>
                        'One or more unique employee fields are already used by another employee.',
                ], 409);
            }

            throw $exception;
        }

        if (isset($result['error'])) {
            return response()->json([
                'success' => false,
                'code' => $result['error']['code'],
                'message' => $result['error']['message'],
            ], $result['error']['status']);
        }

        /** @var Employee $employee */
        $employee = $result['employee'];

        return response()->json([
            'success' => true,
            'action' => $result['action'],
            'data' => [
                'gandasariHrisId' => (int) $employee->id,
                'daidanNik' => $employee->daidan_nik,
                'updatedAt' => $employee->updated_at
                    ?->format('Y-m-d\TH:i:s'),
            ],
        ], $result['action'] === 'created' ? 201 : 200);
    }
}