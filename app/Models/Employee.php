<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Employee extends Model
{
    protected $fillable = [
        'daidan_nik',
        'card_number',
        'first_name',
        'last_name',
        'gender',
        'national_id_number',
        'personal_email',
        'phone_number',
        'address',
        'date_of_birth',
        'religion',
        'marital_status',
        'emergency_contact_name',
        'emergency_contact_phone',
    ];

    protected function casts(): array
    {
        return [
            'date_of_birth' => 'date',
        ];
    }
}