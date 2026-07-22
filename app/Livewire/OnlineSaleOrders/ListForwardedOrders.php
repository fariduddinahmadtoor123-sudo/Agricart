<?php

namespace App\Livewire\OnlineSaleOrders;

use App\Models\OnlineSaleOrderStaffAssignment;
use App\Models\OnlineSaleOrderSupplierAssignment;
use App\Models\User;
use App\Services\Sales\OnlineOrderDispatchService;
use Filament\Notifications\Notification;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\On;
use Livewire\Component;

class ListForwardedOrders extends Component
{
    public string $search = '';

    public ?string $expandedKey = null;

    public string $statusMessage = '';

    #[On('forwarded-orders-saved')]
    #[On('order-dispatch-saved')]
    #[On('online-sale-orders-saved')]
    public function refreshList(): void
    {
        $this->statusMessage = '';
    }

    #[On('forwarded-orders-search')]
    public function updateSearch(string $query): void
    {
        $this->search = $query;
    }

    public function toggleGroup(string $key): void
    {
        $this->expandedKey = $this->expandedKey === $key ? null : $key;
    }

    public function markReleased(string $assignmentType, int $assignmentId): void
    {
        if (! $this->userCanEdit()) {
            Notification::make()
                ->title(__('forwarded_orders.messages.release_denied'))
                ->danger()
                ->send();

            return;
        }

        $dispatch = app(OnlineOrderDispatchService::class);

        try {
            if ($assignmentType === 'supplier') {
                $assignment = OnlineSaleOrderSupplierAssignment::query()->find($assignmentId);

                if ($assignment === null) {
                    return;
                }

                $dispatch->markSupplierReleased($assignment);
            } elseif ($assignmentType === 'staff') {
                $assignment = OnlineSaleOrderStaffAssignment::query()->find($assignmentId);

                if ($assignment === null) {
                    return;
                }

                $dispatch->markStaffReleased($assignment);
            } else {
                return;
            }
        } catch (\Illuminate\Validation\ValidationException $exception) {
            Notification::make()
                ->title((string) (collect($exception->errors())->flatten()->first() ?? __('forwarded_orders.messages.release_failed')))
                ->danger()
                ->send();

            return;
        }

        $this->dispatch('forwarded-orders-saved');
        $this->dispatch('pending-shipments-saved');
        $this->dispatch('order-dispatch-saved');

        Notification::make()
            ->title(__('forwarded_orders.messages.released'))
            ->success()
            ->send();
    }

    public function render(): View
    {
        $groups = app(OnlineOrderDispatchService::class)->forwardedRecipientGroups($this->search);

        return view('livewire.online-sale-orders.list-forwarded-orders', [
            'groups' => $groups,
            'canEdit' => $this->userCanEdit(),
        ]);
    }

    private function userCanEdit(): bool
    {
        $user = auth()->user();

        if (! $user instanceof User) {
            return false;
        }

        return $user->isSuperAdmin() || $user->hasPermission('sales.edit');
    }
}
