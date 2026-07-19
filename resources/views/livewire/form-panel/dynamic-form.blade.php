<div data-form-panel-dynamic-host>
    @if ($component)
        @livewire($component, [], key('form-panel-'.$component.'-'.$instance))
    @endif
</div>
