<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Supplier extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'TIN',
        'address',
        'trade_register_number',
        'phone',
        'IBAN',
        'email',
        'notes',
        'services',
    ];

    protected $casts = [
        'services' => 'array'
    ];

    public function contracts()
    {
        return $this->hasMany(Contract::class);
    }

    public function contractDetails()
    {
        return $this->hasMany(ContractDetails::class);
    }

    public function flows()
    {
        return $this->belongsToMany(Flow::class, 'flow_supplier');
    }
}
