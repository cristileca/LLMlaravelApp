<?php

namespace App\Models;

use App\Observers\AnnexObserver;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Annex extends Model
{
    use HasFactory;
    protected $fillable = ['contract_id', 'files', 'contract_number', 'annex_date', 'status', 'summary', 'last_status_message'];

    protected $casts = [
        'files' => 'array'
    ];

    public function contract()
    {
        return $this->belongsTo(Contract::class);
    }

    public function getTitleAttribute(): string
    {
        return "Annex";
    }
    public static function boot(): void
    {
        parent::boot();
        self::observe(AnnexObserver::class);
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
