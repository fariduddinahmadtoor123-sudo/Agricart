<div class="agricart-store-admin">
    @include('livewire.store.partials.status')

    <section class="agricart-store-admin__card">
        <h3 class="agricart-store-admin__title">{{ __('agricart.store.admin.pages.title') }}</h3>
        <p class="agricart-store-admin__hint">{{ __('agricart.store.admin.pages.hint') }}</p>

        <form wire:submit="savePage" class="agricart-store-admin__form">
            <div class="agricart-store-admin__grid-2">
                <div class="agricart-store-admin__field">
                    <label>{{ __('agricart.store.admin.pages.title_en') }}</label>
                    <input type="text" wire:model.live="title_en" class="agricart-store-admin__input" required />
                    @error('title_en')
                        <p class="agricart-store-admin__error">{{ $message }}</p>
                    @enderror
                </div>
                <div class="agricart-store-admin__field">
                    <label>{{ __('agricart.store.admin.pages.title_ur') }}</label>
                    <input type="text" wire:model="title_ur" class="agricart-store-admin__input" dir="rtl" />
                </div>
            </div>
            <div class="agricart-store-admin__field">
                <label>{{ __('agricart.store.admin.pages.url_preview') }}</label>
                <p class="agricart-store-admin__url-preview"><code>/page/{{ $this->previewSlug }}</code></p>
                <p class="agricart-store-admin__hint">{{ __('agricart.store.admin.pages.slug_auto_hint') }}</p>
                @if ($showSlugField)
                    <div class="agricart-store-admin__field" style="margin-top: 0.75rem;">
                        <label>{{ __('agricart.store.admin.pages.slug') }}</label>
                        <input type="text" wire:model.live="slug" class="agricart-store-admin__input" placeholder="about-us" />
                        <p class="agricart-store-admin__hint">{{ __('agricart.store.admin.pages.slug_hint') }}</p>
                    </div>
                @else
                    <button type="button" wire:click="$set('showSlugField', true)" class="agricart-store-admin__link-btn">
                        {{ __('agricart.store.admin.pages.customize_url') }}
                    </button>
                @endif
            </div>
            <div class="agricart-store-admin__grid-2">
                <div class="agricart-store-admin__field">
                    <label>{{ __('agricart.store.admin.pages.content_en') }}</label>
                    <textarea wire:model="content_en" class="agricart-store-admin__input" rows="8"></textarea>
                </div>
                <div class="agricart-store-admin__field">
                    <label>{{ __('agricart.store.admin.pages.content_ur') }}</label>
                    <textarea wire:model="content_ur" class="agricart-store-admin__input" rows="8" dir="rtl"></textarea>
                </div>
            </div>
            <label class="agricart-store-admin__checkbox">
                <input type="checkbox" wire:model="is_active" />
                <span>{{ __('agricart.store.admin.active') }}</span>
            </label>
            <label class="agricart-store-admin__checkbox">
                <input type="checkbox" wire:model="show_in_menu" />
                <span>{{ __('agricart.store.admin.pages.show_in_menu') }}</span>
            </label>
            <label class="agricart-store-admin__checkbox">
                <input type="checkbox" wire:model="show_in_footer" />
                <span>{{ __('agricart.store.admin.pages.show_in_footer') }}</span>
            </label>
            <p class="agricart-store-admin__hint">{{ __('agricart.store.admin.pages.footer_hint') }}</p>
            <div class="agricart-store-admin__actions">
                <button type="submit" class="agricart-store-admin__btn">
                    {{ $editingId ? __('agricart.store.admin.update') : __('agricart.store.admin.add') }}
                </button>
                @if ($editingId)
                    <button type="button" wire:click="resetPageForm" class="agricart-store-admin__btn agricart-store-admin__btn--muted">{{ __('agricart.store.admin.cancel') }}</button>
                @endif
            </div>
        </form>

        <div class="agricart-store-admin__table-wrap">
            <table class="agricart-store-admin__table">
                <thead>
                    <tr>
                        <th>{{ __('agricart.store.admin.pages.title_en') }}</th>
                        <th>{{ __('agricart.store.admin.pages.url') }}</th>
                        <th>{{ __('agricart.store.admin.pages.placement') }}</th>
                        <th>{{ __('agricart.store.admin.fields.status') }}</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($pages as $page)
                        <tr wire:key="page-{{ $page->id }}">
                            <td>{{ $page->title_en }}</td>
                            <td><code>/page/{{ $page->slug }}</code></td>
                            <td>
                                @if ($page->show_in_menu) {{ __('agricart.store.admin.pages.menu') }} @endif
                                @if ($page->show_in_footer) {{ __('agricart.store.admin.pages.footer') }} @endif
                                @if (! $page->show_in_menu && ! $page->show_in_footer) — @endif
                            </td>
                            <td>{{ $page->is_active ? __('agricart.store.admin.active') : __('agricart.store.admin.inactive') }}</td>
                            <td class="agricart-store-admin__row-actions">
                                <a href="{{ $page->publicUrl() }}" target="_blank" rel="noopener" class="agricart-store-admin__link-btn">{{ __('agricart.store.admin.pages.view') }}</a>
                                <button type="button" wire:click="editPage({{ $page->id }})" class="agricart-store-admin__link-btn">{{ __('agricart.store.admin.edit') }}</button>
                                <button type="button" wire:click="togglePage({{ $page->id }})" class="agricart-store-admin__link-btn">{{ __('agricart.store.admin.toggle') }}</button>
                                <button type="button" wire:click="deletePage({{ $page->id }})" wire:confirm="{{ __('agricart.store.admin.delete_confirm') }}" class="agricart-store-admin__link-btn is-danger">{{ __('agricart.store.admin.delete') }}</button>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5">{{ __('agricart.store.admin.pages.empty') }}</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>
</div>
