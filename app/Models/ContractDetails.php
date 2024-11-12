<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ContractDetails extends Model
{
    use HasFactory;

    protected $fillable = ['supplier','client','objective','price','delivery_conditions','penalties','payment_conditions','contract_term'];

    public function supplier(){
        return $this->belongsTo(Supplier::class);
    }
}
