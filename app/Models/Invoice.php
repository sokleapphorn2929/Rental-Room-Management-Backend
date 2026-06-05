<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Invoice extends Model
{
    protected $table = 'invoice';

    protected $primaryKey = 'invoice_id';

    protected $fillable = [
        'invoice_id',
        'contract_id',
        'billing_month',
        'room_charge',
        'electricity_charge',
        'water_charge',
        'total_amount',
        'status',
        'issue_date',
    ];

    public function contract()
    {
        return $this->belongsTo(Contract::class, 'contract_id', 'contract_id');
    }
}
