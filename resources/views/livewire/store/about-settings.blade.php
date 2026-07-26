<div class="agricart-store-admin">
    @include('livewire.store.partials.status')

    <form wire:submit="save" class="agricart-store-admin__card agricart-store-admin__form">
        <h3 class="agricart-store-admin__title">{{ __('agricart.store.admin.about.title') }}</h3>
        <p class="agricart-store-admin__hint">{{ __('agricart.store.admin.about.hint') }}</p>
        <p class="agricart-store-admin__hint">
            <a href="{{ route('store.about') }}" target="_blank" rel="noopener">{{ __('agricart.store.admin.about.view_page') }}</a>
        </p>

        <h4 class="agricart-store-admin__subtitle">{{ __('agricart.store.admin.about.section_hero') }}</h4>
        <div class="agricart-store-admin__field">
            <label>{{ __('agricart.store.admin.about.hero_years') }}</label>
            <input type="text" wire:model="hero_years_label" class="agricart-store-admin__input" maxlength="120" />
            <p class="agricart-store-admin__hint">{{ __('agricart.store.admin.about.hero_years_hint') }}</p>
            @error('hero_years_label') <p class="agricart-store-admin__error">{{ $message }}</p> @enderror
        </div>

        <h4 class="agricart-store-admin__subtitle">{{ __('agricart.store.admin.about.section_story') }}</h4>
        <div class="agricart-store-admin__grid-2">
            <div class="agricart-store-admin__field">
                <label>{{ __('agricart.store.admin.about.story_en') }}</label>
                <textarea wire:model="story_en" class="agricart-store-admin__input" rows="8" maxlength="20000"></textarea>
                @error('story_en') <p class="agricart-store-admin__error">{{ $message }}</p> @enderror
            </div>
            <div class="agricart-store-admin__field">
                <label>{{ __('agricart.store.admin.about.story_ur') }}</label>
                <textarea wire:model="story_ur" class="agricart-store-admin__input" rows="8" maxlength="20000" dir="rtl"></textarea>
                @error('story_ur') <p class="agricart-store-admin__error">{{ $message }}</p> @enderror
            </div>
        </div>

        <h4 class="agricart-store-admin__subtitle">{{ __('agricart.store.admin.about.section_ceo') }}</h4>
        <div class="agricart-store-admin__grid-2">
            <div class="agricart-store-admin__field">
                <label>{{ __('agricart.store.admin.about.ceo_name') }}</label>
                <input type="text" wire:model="ceo_name" class="agricart-store-admin__input" maxlength="120" />
                @error('ceo_name') <p class="agricart-store-admin__error">{{ $message }}</p> @enderror
            </div>
            <div class="agricart-store-admin__field">
                <label>{{ __('agricart.store.admin.about.ceo_title_en') }}</label>
                <input type="text" wire:model="ceo_title_en" class="agricart-store-admin__input" maxlength="120" />
            </div>
            <div class="agricart-store-admin__field">
                <label>{{ __('agricart.store.admin.about.ceo_title_ur') }}</label>
                <input type="text" wire:model="ceo_title_ur" class="agricart-store-admin__input" maxlength="120" dir="rtl" />
            </div>
        </div>
        <div class="agricart-store-admin__grid-2">
            <div class="agricart-store-admin__field">
                <label>{{ __('agricart.store.admin.about.ceo_bio_en') }}</label>
                <textarea wire:model="ceo_bio_en" class="agricart-store-admin__input" rows="5" maxlength="4000"></textarea>
            </div>
            <div class="agricart-store-admin__field">
                <label>{{ __('agricart.store.admin.about.ceo_bio_ur') }}</label>
                <textarea wire:model="ceo_bio_ur" class="agricart-store-admin__input" rows="5" maxlength="4000" dir="rtl"></textarea>
            </div>
        </div>
        <div class="agricart-store-admin__field">
            <label>{{ __('agricart.store.admin.about.ceo_photo') }}</label>
            <p class="agricart-store-admin__hint">{{ __('agricart.store.admin.about.ceo_photo_hint') }}</p>
            @if ($ceoPhotoPreviewUrl)
                <div class="agricart-store-admin__logo-preview">
                    <img src="{{ $ceoPhotoPreviewUrl }}" alt="" />
                    <button type="button" wire:click="removeCeoPhoto" class="agricart-store-admin__link-btn is-danger">
                        {{ __('agricart.store.admin.about.remove_photo') }}
                    </button>
                </div>
            @endif
            <input type="file" wire:model="ceoPhotoUpload" accept="image/jpeg,image/png,image/webp" class="agricart-store-admin__file" />
            <div wire:loading wire:target="ceoPhotoUpload" class="agricart-store-admin__hint">{{ __('agricart.store.admin.header.uploading') }}</div>
            @error('ceoPhotoUpload') <p class="agricart-store-admin__error">{{ $message }}</p> @enderror
            @error('file') <p class="agricart-store-admin__error">{{ $message }}</p> @enderror
        </div>

        <h4 class="agricart-store-admin__subtitle">{{ __('agricart.store.admin.about.section_office') }}</h4>
        <p class="agricart-store-admin__hint">{{ __('agricart.store.admin.about.office_hint') }}</p>
        @if ($officeRows !== [])
            <div class="agricart-store-about-admin__gallery">
                @foreach ($officeRows as $index => $row)
                    <div class="agricart-store-about-admin__gallery-item" wire:key="office-{{ $index }}-{{ $row['path'] }}">
                        <img src="{{ $row['url'] }}" alt="" />
                        <input type="text" wire:model="officeRows.{{ $index }}.caption_en" class="agricart-store-admin__input" placeholder="{{ __('agricart.store.admin.about.caption_en') }}" />
                        <input type="text" wire:model="officeRows.{{ $index }}.caption_ur" class="agricart-store-admin__input" placeholder="{{ __('agricart.store.admin.about.caption_ur') }}" dir="rtl" />
                        <button type="button" wire:click="removeOfficeImage({{ $index }})" class="agricart-store-admin__link-btn is-danger">
                            {{ __('agricart.store.admin.about.remove_photo') }}
                        </button>
                    </div>
                @endforeach
            </div>
        @endif
        <div class="agricart-store-admin__field">
            <label>{{ __('agricart.store.admin.about.add_office_photo') }}</label>
            <input type="file" wire:model="officeUpload" accept="image/jpeg,image/png,image/webp" class="agricart-store-admin__file" />
            <div wire:loading wire:target="officeUpload" class="agricart-store-admin__hint">{{ __('agricart.store.admin.header.uploading') }}</div>
            @error('officeUpload') <p class="agricart-store-admin__error">{{ $message }}</p> @enderror
        </div>

        <h4 class="agricart-store-admin__subtitle">{{ __('agricart.store.admin.about.section_team') }}</h4>
        <p class="agricart-store-admin__hint">{{ __('agricart.store.admin.about.team_hint') }}</p>
        @foreach ($teamRows as $index => $member)
            <div class="agricart-store-about-admin__team-row" wire:key="team-{{ $index }}">
                <div class="agricart-store-admin__grid-2">
                    <div class="agricart-store-admin__field">
                        <label>{{ __('agricart.store.admin.about.team_name') }}</label>
                        <input type="text" wire:model="teamRows.{{ $index }}.name" class="agricart-store-admin__input" maxlength="120" />
                    </div>
                    <div class="agricart-store-admin__field">
                        <label>{{ __('agricart.store.admin.about.team_role_en') }}</label>
                        <input type="text" wire:model="teamRows.{{ $index }}.role_en" class="agricart-store-admin__input" maxlength="120" />
                    </div>
                    <div class="agricart-store-admin__field">
                        <label>{{ __('agricart.store.admin.about.team_role_ur') }}</label>
                        <input type="text" wire:model="teamRows.{{ $index }}.role_ur" class="agricart-store-admin__input" maxlength="120" dir="rtl" />
                    </div>
                    <div class="agricart-store-admin__field">
                        <label>{{ __('agricart.store.admin.about.team_photo') }}</label>
                        @if (! empty($member['photo_url']))
                            <div class="agricart-store-admin__logo-preview">
                                <img src="{{ $member['photo_url'] }}" alt="" />
                            </div>
                        @endif
                        <input
                            type="file"
                            wire:model="teamPhotoUploads.{{ $index }}"
                            accept="image/jpeg,image/png,image/webp"
                            class="agricart-store-admin__file"
                        />
                        <div wire:loading wire:target="teamPhotoUploads.{{ $index }}" class="agricart-store-admin__hint">
                            {{ __('agricart.store.admin.header.uploading') }}
                        </div>
                        @error('teamPhotoUploads.'.$index) <p class="agricart-store-admin__error">{{ $message }}</p> @enderror
                    </div>
                </div>
                <button type="button" wire:click="removeTeamMember({{ $index }})" class="agricart-store-admin__link-btn is-danger">
                    {{ __('agricart.store.admin.about.remove_member') }}
                </button>
            </div>
        @endforeach
        <button type="button" wire:click="addTeamMember" class="agricart-store-admin__btn agricart-store-admin__btn--muted">
            {{ __('agricart.store.admin.about.add_member') }}
        </button>

        <div class="agricart-store-admin__actions" style="margin-top: 1.25rem;">
            <button type="submit" class="agricart-store-admin__btn">{{ __('agricart.store.admin.save') }}</button>
        </div>
    </form>
</div>
