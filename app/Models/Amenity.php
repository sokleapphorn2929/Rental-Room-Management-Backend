<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Amenity extends Model
{
    protected $table = 'amenity';

    protected $primaryKey = 'amenity_id';

    protected $fillable = [
        'amenity_id',
        'room_id',
        'amenity_name',
        'note',
        'added_date',
    ];

    public function room()
    {
        return $this->belongsTo(Room::class, 'room_id', 'room_id');
    }
}
