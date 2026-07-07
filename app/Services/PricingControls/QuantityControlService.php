<?php

namespace App\Services\PricingControls;

use App\Models\QuantityControl;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class QuantityControlService
{
    /** @var list<string> */
    public const STATUSES = ['active', 'inactive'];

    public function create(array $data): QuantityControl
    {
        $validated = $this->validate($data);

        return DB::transaction(function () use ($validated): QuantityControl {
            if (! filled($validated['control_number'] ?? null)) {
                $validated['control_number'] = $this->nextControlNumber();
            }

            $validated['created_by'] = $validated['created_by'] ?? auth()->id();

            return QuantityControl::query()->create($validated);
        });
    }

    public function update(QuantityControl $record, array $data): QuantityControl
    {
        $validated = $this->validate($data, $record->id);
        $record->update($validated);

        return $record->fresh(['creator']);
    }

    public function delete(QuantityControl $record): void
    {
        $record->delete();
    }

    public function nextControlNumber(): string
    {
        $next = (int) QuantityControl::query()->max('id') + 1;

        do {
            $number = 'QTC-' . str_pad((string) $next, 5, '0', STR_PAD_LEFT);
            $next++;
        } while (QuantityControl::query()->where('control_number', $number)->exists());

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
                Rule::unique('quantity_controls', 'control_number')->ignore($ignoreId),
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
