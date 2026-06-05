<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Maintenance extends Model
{
    protected $table = 'maintenance';

    protected $primaryKey = 'maintenance_id';

    protected $fillable = [
        'maintenance_id',
        'room_id',
        'issue_type',
        'description',
        'reported_date',
        'resolved_date',
        'status',
        'repair_cost',
    ];

    public function room()
    {
        return $this->belongsTo(Room::class, 'room_id', 'room_id');
    }
}
