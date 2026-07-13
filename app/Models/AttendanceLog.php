<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AttendanceLog extends Model
{
    protected $table = 'att_logs';

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'timestamp' => 'datetime',
            'status' => 'integer',
            'verify_mode' => 'integer',
            'work_code' => 'integer',
        ];
    }

    public function device(): BelongsTo
    {
        return $this->belongsTo(Device::class);
    }
}
