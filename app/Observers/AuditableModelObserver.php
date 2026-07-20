<?php

namespace App\Observers;

use App\Services\Activity\ActivityLogger;
use Illuminate\Database\Eloquent\Model;

class AuditableModelObserver
{
    public function created(Model $model): void
    {
        app(ActivityLogger::class)->modelCreated($model);
    }

    public function updated(Model $model): void
    {
        app(ActivityLogger::class)->modelUpdated($model);
    }

    public function deleted(Model $model): void
    {
        app(ActivityLogger::class)->modelDeleted($model);
    }
}
