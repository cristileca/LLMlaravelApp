<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Observers\BillObserver;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Models\Activity;

class Bill extends Model
{
    use HasFactory, LogsActivity;

    protected $fillable = [
        'files',
        'number',
        'date',
        'due_date',
        'fee',
        'contract_id',
        'status',
        'last_status_message',
        'acceptance_status',
        'acceptance_description',
        'analysis',
        'cost_center',
        'seller_name',
        'seller_cui',
        'seller_address',
        'seller_IBAN',
        'seller_bank',
        'seller_phone_number',
        'seller_email',
        'customer_name',
        'customer_cui',
        'customer_address',
        'customer_IBAN',
        'customer_bank',
        'customer_phone_number',
        'customer_email',
        'fee_tva',
        'details',
        'raw_text'
    ];

    protected $casts = [
        'files' => 'array',
    ];

    public function flow()
    {
        return $this->belongsTo(Flow::class);
    }

    public function contract()
    {
        return $this->belongsTo(Contract::class);
    }

    public function activities()
    {
        return $this->morphMany(Activity::class, 'subject');
    }

    public function comments()
    {
        return $this->hasMany(Comment::class);
    }

    public static function boot(): void
    {
        parent::boot();
        self::observe(BillObserver::class);
    }

    // Status constants
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

    public const STATUS_CHECK_IF_APPROVED = 'check_if_approved';
    public const STATUS_APPROVED = 'approved';
    public const STATUS_UNAPPROVED_ALERT = 'unapproved_alert';
    public const STATUS_UNAPPROVED = 'unapproved_check';

    public static array $acceptanceStatuses = [
        self::STATUS_APPROVED => 'Aprobata',
        self::STATUS_UNAPPROVED_ALERT => 'Neaprobata - Alerta Factura',
        self::STATUS_UNAPPROVED => 'Neaprobata - Verificare Manuala Necesara',
        self::STATUS_CHECK_IF_APPROVED => 'Verificare factura',
    ];

    public static array $acceptanceStatusColors = [
        self::STATUS_APPROVED => 'success',
        self::STATUS_UNAPPROVED_ALERT => 'danger',
        self::STATUS_UNAPPROVED => 'warning',
        self::STATUS_CHECK_IF_APPROVED => 'gray',
    ];

    // Configure the options for activity logging
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly([
                'files',
                'number',
                'date',
                'due_date',
                'fee',
                'contract_id',
                'acceptance_status',
                'analysis',
                'cost_center',
                'seller_name',
                'seller_cui',
                'seller_address',
                'seller_IBAN',
                'seller_bank',
                'seller_phone_number',
                'seller_email',
                'customer_name',
                'customer_cui',
                'customer_address',
                'customer_IBAN',
                'customer_bank',
                'customer_phone_number',
                'customer_email',
                'fee_tva',
                'details',
            ]) // Specify the attributes to log
            ->logOnlyDirty() // Log only when changes occur
            ->dontSubmitEmptyLogs() // Prevent logging empty changes
            ->useLogName('bill') // Use a custom log name
            ->setDescriptionForEvent(function (string $eventName) {
                return ucfirst($eventName) . ' Bill';
            });
    }
}

