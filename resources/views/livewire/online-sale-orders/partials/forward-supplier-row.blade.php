<li wire:key="forward-supplier-{{ $supplier['id'] }}" class="agricart-order-forward__row">
    <div class="agricart-order-forward__identity">
        <strong>{{ $supplier['name'] }}</strong>
        <span>{{ $supplier['phone'] ?? __('online_sale_orders.forward.no_phone') }}</span>
        @if ($supplier['email'])
            <span>{{ $supplier['email'] }}</span>
        @endif
    </div>
    <div class="agricart-order-forward__channels">
        <button
            type="button"
            class="agricart-categories__action"
            wire:click="forwardWhatsApp('supplier', {{ $supplier['id'] }})"
            @disabled(! $supplier['can_whatsapp'])
            title="{{ $supplier['can_whatsapp'] ? '' : __('online_sale_orders.forward.no_phone') }}"
        >{{ __('order_dispatch.actions.whatsapp') }}</button>
        <button
            type="button"
            class="agricart-categories__action"
            wire:click="forwardEmail('supplier', {{ $supplier['id'] }})"
            @disabled(! $supplier['can_email'])
            title="{{ $supplier['can_email'] ? '' : __('online_sale_orders.forward.no_email') }}"
        >{{ __('order_dispatch.actions.email') }}</button>
        <button
            type="button"
            class="agricart-categories__action"
            wire:click="forwardOpen('supplier', {{ $supplier['id'] }})"
        >{{ __('online_sale_orders.forward.open') }}</button>
    </div>
</li>
