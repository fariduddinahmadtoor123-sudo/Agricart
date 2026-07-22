<?php

namespace App\Livewire\Logistics;

use App\Models\OnlineSaleOrder;
use App\Models\User;
use App\Services\Sales\OnlineOrderDispatchService;
use App\Support\FriendlyError;
use Illuminate\Contracts\View\View;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Locked;
use Livewire\Component;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Livewire\WithFileUploads;

class ShipForm extends Component
{
    use WithFileUploads;

    #[Locked]
    public ?int $orderId = null;

    #[Locked]
    public string $mode = 'edit';

    /** @var array<int, array{carrier_name: string, tracking_number: string, shipping_reference: string, existing_proof: string|null}> */
    public array $supplierParcels = [];

    /** @var array<int, array{carrier_name: string, tracking_number: string, shipping_reference: string, existing_proof: string|null, label: string}> */
    public array $staffParcels = [];

    /** @var array<int, TemporaryUploadedFile|null> */
    public array $supplierProofUploads = [];

    /** @var array<int, TemporaryUploadedFile|null> */
    public array $staffProofUploads = [];

    public string $orderNumber = '';

    public string $customerLabel = '';

    /** @var list<string> */
    public array $itemLabels = [];

    public bool $saving = false;

    public ?string $statusMessage = null;

    public string $statusType = 'success';

    public function openForm(?int $orderId = null, string $mode = 'edit'): void
    {
        $this->authorizeAction($mode === 'view' ? 'view' : 'edit');
        $this->resetForm();
        $this->mode = in_array($mode, ['edit', 'view'], true) ? $mode : 'edit';
        $this->orderId = $orderId;

        if ($orderId) {
            $this->loadOrder($orderId);
        }
    }

    public function handlePanelAction(string $action): void
    {
        if ($this->mode === 'view' && $action === 'save_close') {
            return;
        }

        match ($action) {
            'save_close' => $this->saveAndClose(),
            default => null,
        };
    }

    public function saveAndClose(): void
    {
        if ($this->persist()) {
            $this->dispatch('pending-shipments-saved');
            $this->dispatch('shipped-orders-saved');
            $this->dispatch('close-form-panel');
        }
    }

    public function isReadonly(): bool
    {
        return $this->mode === 'view';
    }

    public function render(): View
    {
        return view('livewire.logistics.ship-form');
    }

    private function persist(): bool
    {
        if ($this->saving || $this->isReadonly() || $this->orderId === null) {
            return false;
        }

        $this->authorizeAction('edit');
        $this->saving = true;
        $this->statusMessage = null;

        try {
            $order = OnlineSaleOrder::query()->logisticsPending()->find($this->orderId)
                ?? OnlineSaleOrder::query()->logisticsShipped()->find($this->orderId);

            if ($order === null) {
                throw ValidationException::withMessages([
                    'order' => [__('logistics_ship.validation.order_not_found')],
                ]);
            }

            if ($this->supplierParcels === [] && $this->staffParcels === []) {
                throw ValidationException::withMessages([
                    'parcels' => [__('logistics_ship.validation.no_parcels')],
                ]);
            }

            app(OnlineOrderDispatchService::class)->shipLogisticsHandoff(
                $order,
                $this->supplierParcels,
                $this->staffParcels,
                $this->supplierProofUploads,
                $this->staffProofUploads,
            );

            $this->setStatus(__('logistics_ship.messages.shipped'), 'success');

            return true;
        } catch (ValidationException $exception) {
            $message = collect($exception->errors())->flatten()->first() ?: $exception->getMessage();
            $this->setStatus((string) $message, 'danger');

            return false;
        } catch (\Throwable $exception) {
            $this->setStatus(FriendlyError::message($exception), 'danger');

            return false;
        } finally {
            $this->saving = false;
        }
    }

    private function loadOrder(int $orderId): void
    {
        $order = OnlineSaleOrder::query()
            ->with([
                'customer',
                'items',
                'supplierAssignments.supplier',
                'staffAssignments.user',
            ])
            ->findOrFail($orderId);

        $this->orderNumber = $order->order_number;
        $this->customerLabel = $order->customer?->display_name
            ?? $order->ship_contact_name
            ?? '—';
        $this->itemLabels = $order->items
            ->map(fn ($item): string => trim(($item->displayName($order->locale) ?: 'Item').' × '.($item->quantity ?? '1')))
            ->values()
            ->all();

        $this->supplierParcels = [];
        $this->staffParcels = [];
        $this->supplierProofUploads = [];
        $this->staffProofUploads = [];

        foreach ($order->supplierAssignments as $assignment) {
            if ($assignment->released_at === null
                || $assignment->status === \App\Models\OnlineSaleOrderSupplierAssignment::STATUS_DECLINED) {
                continue;
            }

            if ($this->mode === 'edit' && $assignment->shipped_at !== null) {
                continue;
            }

            if ($this->mode === 'view' && $assignment->shipped_at === null) {
                continue;
            }

            $supplierId = (int) $assignment->supplier_id;
            $this->supplierParcels[$supplierId] = [
                'carrier_name' => (string) ($assignment->carrier_name ?? ''),
                'tracking_number' => (string) ($assignment->tracking_number ?? ''),
                'shipping_reference' => (string) ($assignment->shipping_reference ?? ''),
                'existing_proof' => $assignment->shipping_proof_path,
                'label' => $assignment->supplier?->business_name_en
                    ?? $assignment->supplier?->supplier_code
                    ?? __('logistics_ship.labels.supplier_parcel'),
            ];
        }

        foreach ($order->staffAssignments as $assignment) {
            if ($assignment->released_at === null) {
                continue;
            }

            if ($this->mode === 'edit' && $assignment->shipped_at !== null) {
                continue;
            }

            if ($this->mode === 'view' && $assignment->shipped_at === null) {
                continue;
            }

            $id = (int) $assignment->id;
            $this->staffParcels[$id] = [
                'carrier_name' => (string) ($assignment->carrier_name ?? ''),
                'tracking_number' => (string) ($assignment->tracking_number ?? ''),
                'shipping_reference' => (string) ($assignment->shipping_reference ?? ''),
                'existing_proof' => $assignment->shipping_proof_path,
                'label' => $assignment->user?->name
                    ?? __('logistics_ship.labels.staff_parcel'),
            ];
        }

        // Shipped page edit: allow updating already-shipped parcels.
        if ($this->mode === 'edit' && $this->supplierParcels === [] && $this->staffParcels === []) {
            foreach ($order->supplierAssignments as $assignment) {
                if ($assignment->released_at === null || $assignment->shipped_at === null) {
                    continue;
                }

                $supplierId = (int) $assignment->supplier_id;
                $this->supplierParcels[$supplierId] = [
                    'carrier_name' => (string) ($assignment->carrier_name ?? ''),
                    'tracking_number' => (string) ($assignment->tracking_number ?? ''),
                    'shipping_reference' => (string) ($assignment->shipping_reference ?? ''),
                    'existing_proof' => $assignment->shipping_proof_path,
                    'label' => $assignment->supplier?->business_name_en
                        ?? __('logistics_ship.labels.supplier_parcel'),
                ];
            }

            foreach ($order->staffAssignments as $assignment) {
                if ($assignment->released_at === null || $assignment->shipped_at === null) {
                    continue;
                }

                $id = (int) $assignment->id;
                $this->staffParcels[$id] = [
                    'carrier_name' => (string) ($assignment->carrier_name ?? ''),
                    'tracking_number' => (string) ($assignment->tracking_number ?? ''),
                    'shipping_reference' => (string) ($assignment->shipping_reference ?? ''),
                    'existing_proof' => $assignment->shipping_proof_path,
                    'label' => $assignment->user?->name
                        ?? __('logistics_ship.labels.staff_parcel'),
                ];
            }
        }
    }

    private function resetForm(): void
    {
        $this->orderId = null;
        $this->orderNumber = '';
        $this->customerLabel = '';
        $this->itemLabels = [];
        $this->supplierParcels = [];
        $this->staffParcels = [];
        $this->supplierProofUploads = [];
        $this->staffProofUploads = [];
        $this->statusMessage = null;
        $this->statusType = 'success';
        $this->saving = false;
    }

    private function authorizeAction(string $action): void
    {
        $user = auth()->user();

        if (! $user instanceof User) {
            abort(403);
        }

        if ($user->isSuperAdmin()) {
            return;
        }

        abort_unless(
            $user->hasPermission('logistics.'.$action) || $user->hasPermission('sales.'.$action),
            403,
        );
    }

    private function setStatus(string $message, string $type): void
    {
        $this->statusMessage = $message;
        $this->statusType = $type;
    }
}
