<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Building extends Model
{
    protected $table = 'building';

    protected $primaryKey = 'building_id';

    protected $fillable = [
        'building_id',
        'admin_id',
        'building_name',
        'address',
        'total_floors',
        'status',
    ];

    public function admin()
    {
        return $this->belongsTo(Admin::class, 'admin_id', 'admin_id');
    }

    public function rooms()
    {
        return $this->hasMany(Room::class, 'building_id', 'building_id');
    }
}
