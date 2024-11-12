<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Observers\ContractObserver;

class Contract extends Model
{
    use HasFactory;

    protected $fillable = [
        'files',
        'contract_number',
        'issue_date',
        'starting_date',
        'supplier_id',
        'status',
        'last_status_message',
        'details_id',
        'client_id',
        'summary',
    ];

    protected $casts = [
        'files' => 'array'
    ];

    public function annexes()
    {
        return $this->hasMany(Annex::class)
            ->orderBy('annex_date', 'desc');
    }
    
    public function bills()
    {
        return $this->hasMany(Bill::class);
    }

    public function supplier()
    {
        return $this->belongsTo(Supplier::class, 'supplier_id');
    }

    public function client()
    {
        return $this->belongsTo(Supplier::class, 'client_id');
    }

    public function contractDetail()
    {
        return $this->belongsTo(ContractDetails::class, 'details_id', 'id');
    }

    public static function boot(): void
    {
        parent::boot();
        self::observe(ContractObserver::class);
    }

    public const STATUS_OCR_IN_PROGRESS = 'ocr_in_progress';
    public const STATUS_LLM_IN_PROGRESS = 'llm_in_progress';
    public const STATUS_FAILED = 'error';
    public const STATUS_IN_PROGRESS = 'in_progress';
    public const STATUS_SUCCESS = 'success';

    public static array $statuses = [
        self::STATUS_OCR_IN_PROGRESS => 'OCR în desfășurare',
        self::STATUS_LLM_IN_PROGRESS => 'LLM în desfășurare',
        self::STATUS_FAILED => 'Eroare',
        self::STATUS_IN_PROGRESS => 'În curs',
        self::STATUS_SUCCESS => 'Succes',
    ];
}
