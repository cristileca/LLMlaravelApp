<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StageFlow extends Model
{
    use HasFactory;

    protected $fillable = [
        'flow_id', 
        'principal_person',
        'principal_person_email',
        'second_person_name',
        'second_person_email',
        'stage_number',
        'id_user',
        'status',
        'status_stage',
        'maximum_step_time'
    ];
    protected $table = 'stage_flow';

    public function flow()
    {
        return $this->belongsTo(Flow::class, 'flow_id');
    }

    public static array $acceptanceStatusColors = [
        'Acceptat' => 'success',
        'Refuzat'=> 'danger',
        'Ongoing' => 'warning',
        'InProgress' => 'warning',
    ];
}