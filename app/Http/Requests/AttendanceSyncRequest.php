<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class AttendanceSyncRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'lastAttendanceId' => [
                'required',
                'integer',
                'min:0'
            ],
            'limit' => [
                'nullable',
                'integer',
                'min:1',
                'max:500',
            ]
        ];
    }

    public function messages(): array
    {
        return [
            'lastAttendanceId.required' => 'lastAttendanceId is required.',
            'lastAttendanceId.integer' => 'lastAttendanceId must be an integer.',
            'lastAttendanceId.min' => 'lastAttendanceId must be at least 0.',

            'limit.integer' => 'limit must be an integer.',
            'limit.min' => 'limit must be at least 1.',
            'limit.max' => 'limit may not be greater than 500.',
        ];
    }
}
