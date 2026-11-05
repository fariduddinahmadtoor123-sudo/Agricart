<x-filament-panels::page>
    <div class="agricart-app-doc" @if ($lang === 'ur') dir="rtl" @else dir="ltr" @endif>
        <div class="agricart-app-doc__toolbar">
            <p class="agricart-app-doc__eyebrow">{{ __('documentation.app_guide.eyebrow') }}</p>
            <div class="agricart-app-doc__lang">
                <button
                    type="button"
                    class="agricart-app-doc__lang-btn {{ $lang === 'ur' ? 'is-active' : '' }}"
                    wire:click="setLang('ur')"
                >اردو</button>
                <button
                    type="button"
                    class="agricart-app-doc__lang-btn {{ $lang === 'en' ? 'is-active' : '' }}"
                    wire:click="setLang('en')"
                >English</button>
                <button type="button" class="agricart-app-doc__print" onclick="window.print()">
                    {{ __('documentation.app_guide.print') }}
                </button>
            </div>
        </div>

        <article class="agricart-app-doc__paper">
            {!! $this->documentHtml() !!}
        </article>
    </div>
</x-filament-panels::page>
