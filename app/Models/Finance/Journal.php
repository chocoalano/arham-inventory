<?php

namespace App\Models\Finance;

use App\Enums\JournalStatus;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Journal extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'journal_no','journal_date','period_id',
        'source_type','source_id','status','remarks',
        'created_by','posted_by','posted_at'
    ];

    protected $casts = [
        'journal_date' => 'date',
        'posted_at' => 'datetime',
        'status' => JournalStatus::class,
    ];

    public function period()
    {
        return $this->belongsTo(Period::class);
    }

    public function lines()
    {
        return $this->hasMany(JournalLine::class);
    }

    public function source(): MorphTo
    {
        return $this->morphTo(__FUNCTION__, 'source_type', 'source_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function poster()
    {
        return $this->belongsTo(User::class, 'posted_by');
    }

    public function scopePosted($q)
    {
        return $q->where('status', JournalStatus::Posted);
    }
}
