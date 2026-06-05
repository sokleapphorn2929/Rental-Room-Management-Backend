<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Contract extends Model
{
    protected $table = 'contract';

    protected $primaryKey = 'contract_id';

    public $timestamps = false;

    protected $fillable = [
        'contract_id',
        'room_id',
        'tenant_id',
        'start_date',
        'end_date',
        'deposit_amount',
        'status',
        'notes',
        'created_at',
    ];

    public function room()
    {
        return $this->belongsTo(Room::class, 'room_id', 'room_id');
    }

    public function tenant()
    {
        return $this->belongsTo(Tenant::class, 'tenant_id', 'tenant_id');
    }

    public function invoices()
    {
        return $this->hasMany(Invoice::class, 'contract_id', 'contract_id');
    }
}
