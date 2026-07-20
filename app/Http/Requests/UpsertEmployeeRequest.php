<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpsertEmployeeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'daidanNik' => $this->route('daidanNik'),
        ]);
    }

    public function rules(): array
    {
        return [
            'daidanNik' => [
                'nullable',
                'string',
                'max:50',
            ],

            'card_number' => [
                'nullable',
                'string',
                'max:20',
            ],

            'gandasariHrisId' => [
                'nullable',
                'integer',
                'min:1',
            ],

            'firstName' => [
                'required',
                'string',
                'max:255',
            ],

            'lastName' => [
                'sometimes',
                'nullable',
                'string',
                'max:255',
            ],

            'gender' => [
                'required',
                Rule::in([
                    'male',
                    'female',
                ]),
            ],

            'nationalIdNumber' => [
                'sometimes',
                'nullable',
                'string',
                'max:255',
            ],

            'personalEmail' => [
                'sometimes',
                'nullable',
                'email',
                'max:255',
            ],

            'phoneNumber' => [
                'sometimes',
                'nullable',
                'string',
                'max:255',
            ],

            'address' => [
                'sometimes',
                'nullable',
                'string',
            ],

            'dateOfBirth' => [
                'sometimes',
                'nullable',
                'date_format:Y-m-d',
            ],

            'religion' => [
                'sometimes',
                'nullable',
                Rule::in([
                    'islam',
                    'kristen',
                    'katolik',
                    'hindu',
                    'buddha',
                    'konghucu',
                ]),
            ],

            'maritalStatus' => [
                'sometimes',
                'nullable',
                Rule::in([
                    'single',
                    'married',
                    'divorced',
                    'widowed',
                ]),
            ],

            'emergencyContactName' => [
                'sometimes',
                'nullable',
                'string',
                'max:255',
            ],

            'emergencyContactPhone' => [
                'sometimes',
                'nullable',
                'string',
                'max:255',
            ],
        ];
    }

    public function gandasariHrisId(): ?int
    {
        $id = $this->validated('gandasariHrisId');

        return $id !== null
            ? (int) $id
            : null;
    }

    public function daidanNik(): string
    {
        return (string) $this->validated('daidanNik');
    }

    public function employeeAttributes(): array
    {
        $validated = $this->validated();

        $fieldMapping = [
            'firstName' => 'first_name',
            'lastName' => 'last_name',
            'gender' => 'gender',
            'nationalIdNumber' => 'national_id_number',
            'personalEmail' => 'personal_email',
            'phoneNumber' => 'phone_number',
            'address' => 'address',
            'dateOfBirth' => 'date_of_birth',
            'religion' => 'religion',
            'maritalStatus' => 'marital_status',
            'emergencyContactName' => 'emergency_contact_name',
            'emergencyContactPhone' => 'emergency_contact_phone',
        ];

        $attributes = [];

        foreach ($fieldMapping as $requestField => $databaseColumn) {
            if (array_key_exists($requestField, $validated)) {
                $attributes[$databaseColumn] =
                    $validated[$requestField];
            }
        }

        return $attributes;
    }
}
