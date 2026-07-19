<?php

namespace App\Observers;

use App\Services\Media\MediaLibraryService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Throwable;

class MediaUsageObserver
{
    public function saved(Model $model): void
    {
        $library = app(MediaLibraryService::class);
        $fields = array_values(array_filter(
            $library->mediaFieldsFor($model),
            fn (string $field): bool => $model->wasChanged($field),
        ));

        if ($fields === []) {
            return;
        }

        $ownerType = $model::class;
        $ownerId = (int) $model->getKey();
        $oldPaths = $this->pathsFrom($model, $fields, original: true);

        DB::afterCommit(function () use ($library, $ownerType, $ownerId, $fields, $oldPaths): void {
            try {
                $owner = $ownerType::query()->find($ownerId);

                if (! $owner instanceof Model) {
                    return;
                }

                $library->syncModel($owner, $fields);
                $library->cleanupUnusedPaths($oldPaths);
            } catch (Throwable $exception) {
                $library->recordSyncFailure($ownerType, $ownerId, 'sync', $fields, $oldPaths, $exception);
            }
        });
    }

    public function deleted(Model $model): void
    {
        $library = app(MediaLibraryService::class);
        $fields = $library->mediaFieldsFor($model);
        $oldPaths = $this->pathsFrom($model, $fields);
        $ownerType = $model::class;
        $ownerId = (int) $model->getKey();

        DB::afterCommit(function () use ($library, $model, $ownerType, $ownerId, $fields, $oldPaths): void {
            try {
                $library->detachModel($model);
                $library->cleanupUnusedPaths($oldPaths);
            } catch (Throwable $exception) {
                $library->recordSyncFailure($ownerType, $ownerId, 'delete', $fields, $oldPaths, $exception);
            }
        });
    }

    /**
     * @param  list<string>  $fields
     * @return list<string|null>
     */
    private function pathsFrom(Model $model, array $fields, bool $original = false): array
    {
        $paths = [];

        foreach ($fields as $field) {
            $value = $original ? $model->getOriginal($field) : $model->getAttribute($field);

            if (is_array($value)) {
                array_push($paths, ...array_values($value));
            } else {
                $paths[] = is_string($value) ? $value : null;
            }
        }

        return $paths;
    }
}
