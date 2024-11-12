<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\Models\Activity;
use Spatie\Activitylog\LogOptions;

class Comment extends Model
{
    use LogsActivity;

    protected $fillable = ['text', 'user_id', 'bill_id'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function bill()
    {
        return $this->belongsTo(Bill::class);
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['text', 'user_id', 'bill_id'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->useLogName('comment')
            ->setDescriptionForEvent(function (string $eventName) {
                $action = match ($eventName) {
                    'created' => 'a trimis un nou mesaj',
                    'updated' => 'a actualizat un mesaj',
                    'deleted' => 'a șters un mesaj',
                    default => 'performed an action on',
                };
                return "{$this->user->name} {$action}";
            });
    }

    public function tapActivity(Activity $activity, string $eventName)
    {
        $activity->subject_id = $this->bill_id;
        $activity->subject_type = self::class;
    }
}
