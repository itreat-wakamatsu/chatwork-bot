<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SettingRevision extends Model
{
    protected $fillable = [
        'user_id',
        'target_type',
        'snapshot',
        'change_reason',
    ];

    protected function casts(): array
    {
        return [
            'snapshot' => 'array',
        ];
    }
}
