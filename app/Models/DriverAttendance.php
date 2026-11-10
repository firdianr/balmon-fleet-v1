<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DriverAttendance extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'status',
        'process_status',
        'attachment_file',
        'start_date',
        'end_date',
        'notes',
        'created_by',
        'action_by',
        'action_at',
        'rejection_note',
    ];

    protected $casts = [
        'start_date' => 'datetime',
        'end_date'   => 'datetime',
        'action_at'  => 'datetime',
    ];

    public function driver()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function actionBy()
    {
        return $this->belongsTo(User::class, 'action_by');
    }
}