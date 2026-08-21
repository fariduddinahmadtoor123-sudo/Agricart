<div class="agricart-store-admin">
    @if ($statusMessage)
        <div @class([
            'agricart-user-form__alert',
            'is-success' => $statusType === 'success',
            'is-danger' => $statusType === 'danger',
        ])>
            {{ $statusMessage }}
        </div>
    @endif

    <section class="agricart-store-admin__card">
        <h3 class="agricart-store-admin__title">
            {{ __('agricart.store.admin.blog.posts') }}
            <span class="agricart-store-admin__hint" style="font-weight:600;margin-inline-start:0.5rem;">
                {{ __('agricart.store.admin.blog.posts_count', ['count' => $posts->count()]) }}
            </span>
        </h3>
        <p class="agricart-store-admin__hint">{{ __('agricart.store.admin.blog.hint') }}</p>

        @if ($canCreate || ($canEdit && $editingId))
            <form
                wire:submit="savePost"
                wire:key="blog-post-form-{{ $editingId ?? 'new' }}"
                id="store-blog-post-form"
                class="agricart-store-admin__form"
            >
                <div class="agricart-store-admin__grid-2">
                    <div class="agricart-store-admin__field">
                        <label>{{ __('agricart.store.admin.blog.title_en') }}</label>
                        <input type="text" wire:model.live="title_en" class="agricart-store-admin__input" required />
                        @error('title_en') <span class="agricart-store-admin__hint" style="color:#b91c1c;">{{ $message }}</span> @enderror
                    </div>
                    <div class="agricart-store-admin__field">
                        <label>{{ __('agricart.store.admin.blog.title_ur') }}</label>
                        <input type="text" wire:model="title_ur" class="agricart-store-admin__input" dir="rtl" />
                    </div>
                </div>

                <div class="agricart-store-admin__field">
                    <label>{{ __('agricart.store.admin.blog.slug') }}</label>
                    <input type="text" wire:model="slug" class="agricart-store-admin__input" />
                    <p class="agricart-store-admin__hint">{{ __('agricart.store.admin.blog.slug_hint', ['slug' => $slug ?: '…']) }}</p>
                </div>

                <div class="agricart-store-admin__field">
                    <label>{{ __('agricart.store.admin.blog.type') }}</label>
                    <select wire:model.live="type" class="agricart-store-admin__input">
                        <option value="article">{{ __('agricart.store.admin.blog.type_article') }}</option>
                        <option value="video">{{ __('agricart.store.admin.blog.type_video') }}</option>
                    </select>
                </div>

                @if ($type === 'video')
                    <div class="agricart-store-admin__field">
                        <label>{{ __('agricart.store.admin.blog.youtube_url') }}</label>
                        <input type="url" wire:model="youtube_url" class="agricart-store-admin__input" placeholder="https://www.youtube.com/watch?v=…" required />
                        <p class="agricart-store-admin__hint">{{ __('agricart.store.admin.blog.youtube_hint') }}</p>
                        @error('youtube_url') <span class="agricart-store-admin__hint" style="color:#b91c1c;">{{ $message }}</span> @enderror
                    </div>
                @endif

                <div class="agricart-store-admin__grid-2">
                    <div class="agricart-store-admin__field">
                        <label>{{ __('agricart.store.admin.blog.excerpt_en') }}</label>
                        <textarea wire:model="excerpt_en" class="agricart-store-admin__input" rows="2"></textarea>
                    </div>
                    <div class="agricart-store-admin__field">
                        <label>{{ __('agricart.store.admin.blog.excerpt_ur') }}</label>
                        <textarea wire:model="excerpt_ur" class="agricart-store-admin__input" rows="2" dir="rtl"></textarea>
                    </div>
                </div>

                @if ($type === 'article')
                    <div class="agricart-store-admin__grid-2">
                        <div class="agricart-store-admin__field">
                            <label>{{ __('agricart.store.admin.blog.body_en') }}</label>
                            <textarea wire:model="body_en" class="agricart-store-admin__input" rows="6"></textarea>
                        </div>
                        <div class="agricart-store-admin__field">
                            <label>{{ __('agricart.store.admin.blog.body_ur') }}</label>
                            <textarea wire:model="body_ur" class="agricart-store-admin__input" rows="6" dir="rtl"></textarea>
                        </div>
                    </div>
                @endif

                <div class="agricart-store-admin__field">
                    <label>{{ __('agricart.store.admin.blog.cover') }}</label>
                    <input type="file" wire:model="coverUpload" accept="image/jpeg,image/png,image/webp" class="agricart-store-admin__input" />
                    <p class="agricart-store-admin__hint">{{ __('agricart.store.admin.blog.cover_hint') }}</p>
                    @if ($coverUpload)
                        <p class="agricart-store-admin__hint">{{ __('agricart.store.admin.header.uploading') }}</p>
                    @endif
                </div>

                <label class="agricart-store-admin__checkbox">
                    <input type="checkbox" wire:model="is_published" />
                    <span>{{ __('agricart.store.admin.blog.published') }}</span>
                </label>
                <p class="agricart-store-admin__hint">{{ __('agricart.store.admin.blog.published_hint') }}</p>

                <div class="agricart-store-admin__field">
                    <label>{{ __('agricart.store.admin.blog.published_at') }}</label>
                    <input type="datetime-local" wire:model="published_at" class="agricart-store-admin__input" />
                </div>

                <div class="agricart-store-admin__actions">
                    <button type="submit" class="agricart-store-admin__btn">
                        {{ $editingId ? __('agricart.store.admin.update') : __('agricart.store.admin.add') }}
                    </button>
                    @if ($editingId)
                        <button type="button" wire:click="resetForm" class="agricart-store-admin__btn agricart-store-admin__btn--muted">
                            {{ __('agricart.store.admin.cancel') }}
                        </button>
                    @endif
                </div>
            </form>
        @endif

        <div class="agricart-store-admin__table-wrap">
            <table class="agricart-store-admin__table">
                <thead>
                    <tr>
                        <th>{{ __('agricart.store.admin.blog.preview') }}</th>
                        <th>{{ __('agricart.store.admin.blog.col_title') }}</th>
                        <th>{{ __('agricart.store.admin.blog.col_type') }}</th>
                        <th>{{ __('agricart.store.admin.fields.status') }}</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($posts as $post)
                        @php
                            $thumb = $blog->cardImageUrl($post);
                        @endphp
                        <tr wire:key="blog-post-{{ $post->id }}">
                            <td>
                                @if ($thumb)
                                    <img src="{{ $thumb }}" alt="" style="max-width:7rem;max-height:4rem;object-fit:cover;" />
                                @else
                                    <span class="agricart-store-admin__hint">—</span>
                                @endif
                            </td>
                            <td>
                                <strong>{{ $post->title_en }}</strong>
                                <div class="agricart-store-admin__hint">/blog/{{ $post->slug }}</div>
                            </td>
                            <td>{{ $post->isVideo() ? __('agricart.store.admin.blog.type_video') : __('agricart.store.admin.blog.type_article') }}</td>
                            <td>{{ $post->is_published ? __('agricart.store.admin.blog.status_published') : __('agricart.store.admin.blog.status_draft') }}</td>
                            <td class="agricart-store-admin__row-actions">
                                @if ($canEdit)
                                    <button type="button" wire:click="editPost({{ $post->id }})" class="agricart-store-admin__link-btn">{{ __('agricart.store.admin.edit') }}</button>
                                    <button type="button" wire:click="togglePublished({{ $post->id }})" class="agricart-store-admin__link-btn">{{ __('agricart.store.admin.toggle') }}</button>
                                @endif
                                @if ($canDelete)
                                    <button type="button" wire:click="deletePost({{ $post->id }})" wire:confirm="{{ __('agricart.store.admin.delete_confirm') }}" class="agricart-store-admin__link-btn is-danger">{{ __('agricart.store.admin.delete') }}</button>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5">{{ __('agricart.store.admin.blog.empty') }}</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>
</div>
