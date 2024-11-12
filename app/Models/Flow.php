<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Flow extends Model
{
    use HasFactory;
    protected $fillable = ['name', 'cost_center', 'min_price','max_price' ,'maximum_step_time', 'id_user', 'status', 'status_stage'];

    protected $casts = [
        'cost_center' => 'array'
    ];
    public function users()
    {
        return $this->belongsToMany(User::class, 'flow_user');
    }


    public function bills()
    {
        return $this->belongsToMany(Bill::class, 'flow_bill');
    }



    public function suppliers(){
        return $this->belongsToMany(Supplier::class, 'flow_supplier');
    }


    
    public function stages()
    {
        return $this->hasMany(StageFlow::class, 'flow_id');
    }

}
