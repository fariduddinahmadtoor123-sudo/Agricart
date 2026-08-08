<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Category codes must be unique (the edit form validates uniqueness), but imported
     * data left several categories sharing the same code. That made it impossible to
     * save an existing category — even for an unrelated change like a new image —
     * because the code collided with another row. Reassign the duplicates so each
     * category keeps a unique code; the earliest row keeps the original value.
     */
    public function up(): void
    {
        $rows = DB::table('categories')
            ->whereNotNull('code')
            ->where('code', '!=', '')
            ->orderBy('id')
            ->get(['id', 'code']);

        // Reserve every current code (case-insensitive) so new values never collide.
        $taken = [];
        foreach ($rows as $row) {
            $taken[mb_strtolower((string) $row->code)] = true;
        }

        $seen = [];

        foreach ($rows as $row) {
            $code = (string) $row->code;
            $key = mb_strtolower($code);

            if (! isset($seen[$key])) {
                $seen[$key] = true;

                continue;
            }

            $newCode = $this->makeUniqueCode($code, $taken);

            DB::table('categories')
                ->where('id', $row->id)
                ->update(['code' => $newCode]);
        }
    }

    public function down(): void
    {
        // Data repair — the original duplicate codes are intentionally not restored.
    }

    /**
     * @param  array<string, bool>  $taken
     */
    private function makeUniqueCode(string $code, array &$taken): string
    {
        // Prefer incrementing a trailing number (AC-SC-001 -> AC-SC-002) to keep the scheme.
        if (preg_match('/^(.*?)(\d+)$/', $code, $matches) === 1) {
            $prefix = $matches[1];
            $number = (int) $matches[2];
            $width = strlen($matches[2]);

            for ($i = $number + 1; $i <= $number + 100000; $i++) {
                $candidate = $prefix.str_pad((string) $i, $width, '0', STR_PAD_LEFT);

                if (! isset($taken[mb_strtolower($candidate)])) {
                    $taken[mb_strtolower($candidate)] = true;

                    return $candidate;
                }
            }
        }

        // Fallback: append -2, -3, … until a free value is found.
        $suffix = 2;

        do {
            $candidate = $code.'-'.$suffix;
            $suffix++;
        } while (isset($taken[mb_strtolower($candidate)]));

        $taken[mb_strtolower($candidate)] = true;

        return $candidate;
    }
};
