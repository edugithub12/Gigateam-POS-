<?php

namespace App\Traits;

use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

trait LogsUserActivity
{
    use LogsActivity;

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logAll()
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->useLogName(static::getActivityLogName())
            ->setDescriptionForEvent(
                fn(string $eventName) => ucfirst($eventName) . ' ' . class_basename(static::class)
            );
    }

    protected static function getActivityLogName(): string
    {
        return strtolower(class_basename(static::class));
    }
}