<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Log extends Model
{
    protected $table = "activity_log";
    protected $fillable = [
        'log_name',
        'description',
        'subject',
        'causer_id',
        'properties',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'causer_id');
    }
}
