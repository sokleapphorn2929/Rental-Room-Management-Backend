<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Room extends Model
{
    protected $table = 'room';

    protected $primaryKey = 'room_id';

    protected $fillable = [
        'room_id',
        'building_id',
        'room_number',
        'room_type',
        'floor_number',
        'monthly_price',
        'status',
        'area_sqm',
        'description',
    ];

    public function building()
    {
        return $this->belongsTo(Building::class, 'building_id', 'building_id');
    }

    public function maintenances()
    {
        return $this->hasMany(Maintenance::class, 'room_id', 'room_id');
    }

    public function amenities()
    {
        return $this->hasMany(Amenity::class, 'room_id', 'room_id');
    }

    public function contracts()
    {
        return $this->hasMany(Contract::class, 'room_id', 'room_id');
    }
}
