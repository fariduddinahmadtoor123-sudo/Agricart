<?php

namespace App\Services\PricingControls;

use App\Models\PriceControl;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class PriceControlService
{
    /** @var list<string> */
    public const STATUSES = ['active', 'inactive'];

    public function create(array $data): PriceControl
    {
        $validated = $this->validate($data);

        return DB::transaction(function () use ($validated): PriceControl {
            if (! filled($validated['control_number'] ?? null)) {
                $validated['control_number'] = $this->nextControlNumber();
            }

            $validated['created_by'] = $validated['created_by'] ?? auth()->id();

            return PriceControl::query()->create($validated);
        });
    }

    public function update(PriceControl $record, array $data): PriceControl
    {
        $validated = $this->validate($data, $record->id);
        $record->update($validated);

        return $record->fresh(['creator']);
    }

    public function delete(PriceControl $record): void
    {
        $record->delete();
    }

    public function nextControlNumber(): string
    {
        $next = (int) PriceControl::query()->max('id') + 1;

        do {
            $number = 'PRC-' . str_pad((string) $next, 5, '0', STR_PAD_LEFT);
            $next++;
        } while (PriceControl::query()->where('control_number', $number)->exists());

        return $number;
    }

    /**
     * @return array<string, mixed>
     */
    private function validate(array $data, ?int $ignoreId = null): array
    {
        return validator($data, [
            'control_number' => [
                'nullable',
                'string',
                'max:32',
                Rule::unique('price_controls', 'control_number')->ignore($ignoreId),
            ],
            'name_en' => ['required', 'string', 'max:255'],
            'name_ur' => ['nullable', 'string', 'max:255'],
            'status' => ['required', Rule::in(self::STATUSES)],
            'notes' => ['nullable', 'string', 'max:5000'],
            'settings' => ['nullable', 'array'],
            'created_by' => ['nullable', 'integer', 'exists:users,id'],
        ])->validate();
    }
}
