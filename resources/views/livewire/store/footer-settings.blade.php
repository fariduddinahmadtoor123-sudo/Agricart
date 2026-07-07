<div class="agricart-store-admin">
    @include('livewire.store.partials.status')

    <form wire:submit="saveSettings" class="agricart-store-admin__card agricart-store-admin__form">
        <h3 class="agricart-store-admin__title">{{ __('agricart.store.admin.footer.title') }}</h3>
        <p class="agricart-store-admin__hint">{{ __('agricart.store.admin.footer.hint') }}</p>

        <label class="agricart-store-admin__checkbox">
            <input type="checkbox" wire:model="footer_enabled" />
            <span>{{ __('agricart.store.admin.footer.enabled') }}</span>
        </label>

        <label class="agricart-store-admin__checkbox">
            <input type="checkbox" wire:model="footer_show_logo" />
            <span>{{ __('agricart.store.admin.footer.show_logo') }}</span>
        </label>

        <div class="agricart-store-admin__field">
            <label>{{ __('agricart.store.admin.footer.logo') }}</label>
            @if ($footerLogoPreviewUrl)
                <div class="agricart-store-admin__logo-preview">
                    <img src="{{ $footerLogoPreviewUrl }}" alt="" />
                </div>
                <button type="button" wire:click="removeFooterLogo" class="agricart-store-admin__link-btn">{{ __('agricart.store.admin.footer.remove_logo') }}</button>
            @endif
            <input type="file" wire:model="footerLogoUpload" accept="image/*" class="agricart-store-admin__input" />
            <p class="agricart-store-admin__hint">{{ __('agricart.store.admin.footer.logo_hint') }}</p>
            <div wire:loading wire:target="footerLogoUpload" class="agricart-store-admin__hint">{{ __('agricart.store.admin.header.uploading') }}</div>
        </div>

        <div class="agricart-store-admin__grid-2">
            <div class="agricart-store-admin__field">
                <label>{{ __('agricart.store.admin.footer.tagline_en') }}</label>
                <textarea wire:model="footer_tagline_en" class="agricart-store-admin__input" rows="2"></textarea>
            </div>
            <div class="agricart-store-admin__field">
                <label>{{ __('agricart.store.admin.footer.tagline_ur') }}</label>
                <textarea wire:model="footer_tagline_ur" class="agricart-store-admin__input" rows="2" dir="rtl"></textarea>
            </div>
        </div>

        <h4 class="agricart-store-admin__subtitle">{{ __('agricart.store.admin.footer.column_titles') }}</h4>
        <div class="agricart-store-admin__grid-2">
            <div class="agricart-store-admin__field">
                <label>{{ __('agricart.store.admin.footer.quick_title_en') }}</label>
                <input type="text" wire:model="footer_quick_title_en" class="agricart-store-admin__input" />
            </div>
            <div class="agricart-store-admin__field">
                <label>{{ __('agricart.store.admin.footer.quick_title_ur') }}</label>
                <input type="text" wire:model="footer_quick_title_ur" class="agricart-store-admin__input" dir="rtl" />
            </div>
            <div class="agricart-store-admin__field">
                <label>{{ __('agricart.store.admin.footer.policies_title_en') }}</label>
                <input type="text" wire:model="footer_policies_title_en" class="agricart-store-admin__input" />
            </div>
            <div class="agricart-store-admin__field">
                <label>{{ __('agricart.store.admin.footer.policies_title_ur') }}</label>
                <input type="text" wire:model="footer_policies_title_ur" class="agricart-store-admin__input" dir="rtl" />
            </div>
            <div class="agricart-store-admin__field">
                <label>{{ __('agricart.store.admin.footer.registration_title_en') }}</label>
                <input type="text" wire:model="footer_registration_title_en" class="agricart-store-admin__input" />
            </div>
            <div class="agricart-store-admin__field">
                <label>{{ __('agricart.store.admin.footer.registration_title_ur') }}</label>
                <input type="text" wire:model="footer_registration_title_ur" class="agricart-store-admin__input" dir="rtl" />
            </div>
        </div>

        <div class="agricart-store-admin__field">
            <label>{{ __('agricart.store.admin.footer.map_embed') }}</label>
            <textarea wire:model="footer_map_embed" class="agricart-store-admin__input" rows="4" placeholder="<iframe src=&quot;https://www.google.com/maps/embed?...&quot; ...></iframe>"></textarea>
            <p class="agricart-store-admin__hint">{{ __('agricart.store.admin.footer.map_hint') }}</p>
        </div>

        <div class="agricart-store-admin__grid-2">
            <div class="agricart-store-admin__field">
                <label>{{ __('agricart.store.admin.footer.copyright_en') }}</label>
                <input type="text" wire:model="footer_copyright_en" class="agricart-store-admin__input" placeholder="© {year} Agricart. All rights reserved." />
            </div>
            <div class="agricart-store-admin__field">
                <label>{{ __('agricart.store.admin.footer.copyright_ur') }}</label>
                <input type="text" wire:model="footer_copyright_ur" class="agricart-store-admin__input" dir="rtl" />
            </div>
        </div>
        <p class="agricart-store-admin__hint">{{ __('agricart.store.admin.footer.copyright_hint') }}</p>

        <h4 class="agricart-store-admin__subtitle">{{ __('agricart.store.admin.footer.bottom_bar') }}</h4>
        <label class="agricart-store-admin__checkbox">
            <input type="checkbox" wire:model="footer_show_social" />
            <span>{{ __('agricart.store.admin.footer.show_social') }}</span>
        </label>
        <label class="agricart-store-admin__checkbox">
            <input type="checkbox" wire:model="footer_show_login_link" />
            <span>{{ __('agricart.store.admin.footer.show_login') }}</span>
        </label>
        <label class="agricart-store-admin__checkbox">
            <input type="checkbox" wire:model="footer_show_register_link" />
            <span>{{ __('agricart.store.admin.footer.show_register') }}</span>
        </label>
        <p class="agricart-store-admin__hint">{{ __('agricart.store.admin.footer.auth_hint') }}</p>

        <button type="submit" class="agricart-store-admin__btn">{{ __('agricart.store.admin.save') }}</button>
    </form>

    <section class="agricart-store-admin__card">
        <h3 class="agricart-store-admin__title">{{ __('agricart.store.admin.footer.policies_section') }}</h3>
        <p class="agricart-store-admin__hint">{{ __('agricart.store.admin.footer.policies_hint') }}</p>
        @if ($policyPages->isEmpty())
            <p class="agricart-store-admin__hint">{{ __('agricart.store.admin.footer.policies_empty') }}</p>
        @else
            <ul class="agricart-store-admin__simple-list">
                @foreach ($policyPages as $page)
                    <li>{{ $page->title_en }} — <code>/page/{{ $page->slug }}</code></li>
                @endforeach
            </ul>
        @endif
    </section>

    <section class="agricart-store-admin__card">
        <h3 class="agricart-store-admin__title">{{ __('agricart.store.admin.footer.manage_links') }}</h3>
        <p class="agricart-store-admin__hint">{{ __('agricart.store.admin.footer.manage_links_hint') }}</p>

        <form wire:submit="saveLink" class="agricart-store-admin__form">
            <div class="agricart-store-admin__field">
                <label>{{ __('agricart.store.admin.footer.link_group') }}</label>
                <select wire:model="link_group" class="agricart-store-admin__input">
                    <option value="{{ \App\Models\StoreFooterLink::GROUP_QUICK }}">{{ __('agricart.store.admin.footer.quick_links') }}</option>
                    <option value="{{ \App\Models\StoreFooterLink::GROUP_REGISTRATION }}">{{ __('agricart.store.admin.footer.registration_links') }}</option>
                </select>
            </div>
            <div class="agricart-store-admin__grid-2">
                <div class="agricart-store-admin__field">
                    <label>{{ __('agricart.store.admin.fields.label_en') }}</label>
                    <input type="text" wire:model="label_en" class="agricart-store-admin__input" required />
                </div>
                <div class="agricart-store-admin__field">
                    <label>{{ __('agricart.store.admin.fields.label_ur') }}</label>
                    <input type="text" wire:model="label_ur" class="agricart-store-admin__input" dir="rtl" />
                </div>
            </div>
            <div class="agricart-store-admin__grid-2">
                <div class="agricart-store-admin__field">
                    <label>{{ __('agricart.store.admin.menu.url') }}</label>
                    <input type="text" wire:model="url" class="agricart-store-admin__input" />
                    @error('url') <span class="agricart-store-admin__error">{{ $message }}</span> @enderror
                </div>
                <div class="agricart-store-admin__field">
                    <label>{{ __('agricart.store.admin.menu.category') }}</label>
                    <select wire:model="category_id" class="agricart-store-admin__input">
                        <option value="">{{ __('agricart.store.admin.menu.no_category') }}</option>
                        @foreach ($categories as $category)
                            <option value="{{ $category->id }}">{{ $category->name_en }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="agricart-store-admin__field">
                    <label>{{ __('agricart.store.admin.menu.page') }}</label>
                    <select wire:model="store_page_id" class="agricart-store-admin__input">
                        <option value="">{{ __('agricart.store.admin.menu.no_page') }}</option>
                        @foreach ($pages as $page)
                            <option value="{{ $page->id }}">{{ $page->title_en }}</option>
                        @endforeach
                    </select>
                    @error('store_page_id') <span class="agricart-store-admin__error">{{ $message }}</span> @enderror
                </div>
            </div>
            <label class="agricart-store-admin__checkbox">
                <input type="checkbox" wire:model="opens_in_new_tab" />
                <span>{{ __('agricart.store.admin.menu.new_tab') }}</span>
            </label>
            <div class="agricart-store-admin__actions">
                <button type="submit" class="agricart-store-admin__btn">
                    {{ $editingId ? __('agricart.store.admin.update') : __('agricart.store.admin.add') }}
                </button>
                @if ($editingId)
                    <button type="button" wire:click="resetLinkForm" class="agricart-store-admin__btn agricart-store-admin__btn--muted">{{ __('agricart.store.admin.cancel') }}</button>
                @endif
            </div>
        </form>

        <h4 class="agricart-store-admin__subtitle">{{ __('agricart.store.admin.footer.quick_links') }}</h4>
        <div class="agricart-store-admin__table-wrap">
            <table class="agricart-store-admin__table">
                <thead>
                    <tr>
                        <th>{{ __('agricart.store.admin.fields.label_en') }}</th>
                        <th>{{ __('agricart.store.admin.menu.link') }}</th>
                        <th>{{ __('agricart.store.admin.fields.status') }}</th>
                        <th>{{ __('agricart.store.admin.order') }}</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($quickLinks as $item)
                        <tr wire:key="footer-quick-{{ $item->id }}">
                            <td>{{ $item->label_en }}</td>
                            <td><code>{{ $item->resolvedUrl() }}</code></td>
                            <td>{{ $item->is_active ? __('agricart.store.admin.active') : __('agricart.store.admin.inactive') }}</td>
                            <td class="agricart-store-admin__row-actions">
                                <button type="button" wire:click="moveLink({{ $item->id }}, 'up')" class="agricart-store-admin__link-btn" @disabled($loop->first)>{{ __('agricart.store.admin.move_up') }}</button>
                                <button type="button" wire:click="moveLink({{ $item->id }}, 'down')" class="agricart-store-admin__link-btn" @disabled($loop->last)>{{ __('agricart.store.admin.move_down') }}</button>
                            </td>
                            <td class="agricart-store-admin__row-actions">
                                <button type="button" wire:click="editLink({{ $item->id }})" class="agricart-store-admin__link-btn">{{ __('agricart.store.admin.edit') }}</button>
                                <button type="button" wire:click="toggleLink({{ $item->id }})" class="agricart-store-admin__link-btn">{{ __('agricart.store.admin.toggle') }}</button>
                                <button type="button" wire:click="deleteLink({{ $item->id }})" wire:confirm="{{ __('agricart.store.admin.delete_confirm') }}" class="agricart-store-admin__link-btn is-danger">{{ __('agricart.store.admin.delete') }}</button>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5">{{ __('agricart.store.admin.footer.quick_links_empty') }}</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <h4 class="agricart-store-admin__subtitle">{{ __('agricart.store.admin.footer.registration_links') }}</h4>
        <div class="agricart-store-admin__table-wrap">
            <table class="agricart-store-admin__table">
                <thead>
                    <tr>
                        <th>{{ __('agricart.store.admin.fields.label_en') }}</th>
                        <th>{{ __('agricart.store.admin.menu.link') }}</th>
                        <th>{{ __('agricart.store.admin.fields.status') }}</th>
                        <th>{{ __('agricart.store.admin.order') }}</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($registrationLinks as $item)
                        <tr wire:key="footer-registration-{{ $item->id }}">
                            <td>{{ $item->label_en }}</td>
                            <td><code>{{ $item->resolvedUrl() }}</code></td>
                            <td>{{ $item->is_active ? __('agricart.store.admin.active') : __('agricart.store.admin.inactive') }}</td>
                            <td class="agricart-store-admin__row-actions">
                                <button type="button" wire:click="moveLink({{ $item->id }}, 'up')" class="agricart-store-admin__link-btn" @disabled($loop->first)>{{ __('agricart.store.admin.move_up') }}</button>
                                <button type="button" wire:click="moveLink({{ $item->id }}, 'down')" class="agricart-store-admin__link-btn" @disabled($loop->last)>{{ __('agricart.store.admin.move_down') }}</button>
                            </td>
                            <td class="agricart-store-admin__row-actions">
                                <button type="button" wire:click="editLink({{ $item->id }})" class="agricart-store-admin__link-btn">{{ __('agricart.store.admin.edit') }}</button>
                                <button type="button" wire:click="toggleLink({{ $item->id }})" class="agricart-store-admin__link-btn">{{ __('agricart.store.admin.toggle') }}</button>
                                <button type="button" wire:click="deleteLink({{ $item->id }})" wire:confirm="{{ __('agricart.store.admin.delete_confirm') }}" class="agricart-store-admin__link-btn is-danger">{{ __('agricart.store.admin.delete') }}</button>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5">{{ __('agricart.store.admin.footer.registration_links_empty') }}</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>
</div>
