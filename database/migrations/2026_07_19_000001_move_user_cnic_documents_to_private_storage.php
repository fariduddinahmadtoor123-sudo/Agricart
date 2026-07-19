<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;

return new class extends Migration
{
    public function up(): void
    {
        $this->moveDocuments('public', 'local');
    }

    public function down(): void
    {
        $this->moveDocuments('local', 'public');
    }

    private function moveDocuments(string $sourceDisk, string $targetDisk): void
    {
        if (! Schema::hasTable('users')) {
            return;
        }

        DB::table('users')
            ->select(['id', 'cnic_front_path', 'cnic_back_path'])
            ->orderBy('id')
            ->chunkById(100, function ($users) use ($sourceDisk, $targetDisk): void {
                foreach ($users as $user) {
                    foreach (['cnic_front_path', 'cnic_back_path'] as $field) {
                        $path = $user->{$field};

                        if (
                            ! is_string($path)
                            || ! str_starts_with($path, 'users/cnic/')
                            || ! Storage::disk($sourceDisk)->exists($path)
                        ) {
                            continue;
                        }

                        $contents = Storage::disk($sourceDisk)->get($path);

                        if (Storage::disk($targetDisk)->put($path, $contents)) {
                            Storage::disk($sourceDisk)->delete($path);
                        }
                    }
                }
            });
    }
};
