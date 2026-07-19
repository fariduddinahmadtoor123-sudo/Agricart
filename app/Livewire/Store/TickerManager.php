<?php

namespace App\Livewire\Store;

use App\Models\StoreTickerMessage;
use App\Rules\SafeUrl;
use Illuminate\Contracts\View\View;

class TickerManager extends BaseStoreSettings
{
    public bool $ticker_enabled = true;

    public int $ticker_speed = 40;

    public string $text_en = '';

    public string $text_ur = '';

    public string $link = '';

    public ?int $editingId = null;

    public function mount(): void
    {
        if (! $this->canManageStoreSettings()) {
            return;
        }

        $settings = $this->settings();
        $this->ticker_enabled = $settings->ticker_enabled;
        $this->ticker_speed = $settings->ticker_speed;
    }

    public function saveSettings(): void
    {
        $this->authorizeStoreSettings();
        $this->validate([
            'ticker_speed' => ['required', 'integer', 'min:10', 'max:100'],
        ]);

        $settings = $this->settings();
        $settings->fill([
            'ticker_enabled' => $this->ticker_enabled,
            'ticker_speed' => $this->ticker_speed,
        ]);
        $settings->save();

        $this->flashSaved();
    }

    public function saveMessage(): void
    {
        $this->authorizeStoreSettings();
        $this->validate([
            'text_en' => ['required', 'string', 'max:500'],
            'text_ur' => ['nullable', 'string', 'max:500'],
            'link' => ['nullable', 'string', 'max:500', new SafeUrl],
        ]);

        $payload = [
            'text_en' => $this->text_en,
            'text_ur' => $this->text_ur,
            'link' => filled($this->link) ? $this->link : null,
        ];

        if ($this->editingId) {
            StoreTickerMessage::query()->whereKey($this->editingId)->update($payload);
        } else {
            $maxSort = (int) StoreTickerMessage::query()->max('sort_order');
            StoreTickerMessage::query()->create([
                ...$payload,
                'sort_order' => $maxSort + 1,
                'is_active' => true,
            ]);
        }

        $this->resetMessageForm();
        $this->flashSaved();
    }

    public function editMessage(int $id): void
    {
        $this->authorizeStoreSettings();
        $message = StoreTickerMessage::query()->findOrFail($id);

        $this->editingId = $message->id;
        $this->text_en = $message->text_en;
        $this->text_ur = $message->text_ur ?? '';
        $this->link = $message->link ?? '';
    }

    public function deleteMessage(int $id): void
    {
        $this->authorizeStoreSettings();
        StoreTickerMessage::query()->whereKey($id)->delete();

        if ($this->editingId === $id) {
            $this->resetMessageForm();
        }

        $this->flashSaved();
    }

    public function toggleMessage(int $id): void
    {
        $this->authorizeStoreSettings();
        $message = StoreTickerMessage::query()->findOrFail($id);
        $message->update(['is_active' => ! $message->is_active]);
        $this->flashSaved();
    }

    public function resetMessageForm(): void
    {
        $this->authorizeStoreSettings();
        $this->editingId = null;
        $this->text_en = '';
        $this->text_ur = '';
        $this->link = '';
    }

    public function render(): View
    {
        return view('livewire.store.ticker-manager', [
            'messages' => $this->canManageStoreSettings()
                ? StoreTickerMessage::query()->orderBy('sort_order')->orderBy('id')->get()
                : collect(),
        ]);
    }
}
