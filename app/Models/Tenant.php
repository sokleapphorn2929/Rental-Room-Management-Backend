<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Tenant extends Model
{
    protected $table = 'tenant';

    protected $primaryKey = 'tenant_id';

    protected $fillable = [
        'tenant_id',
        'full_name',
        'phone',
        'email',
        'national_id',
        'gender',
        'current_address',
        'move_in_date',
    ];

    public function contracts()
    {
        return $this->hasMany(Contract::class, 'tenant_id', 'tenant_id');
    }
}
