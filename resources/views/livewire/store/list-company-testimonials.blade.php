<div class="agricart-categories agricart-store-reviews">
    <p class="agricart-store-reviews__policy">{{ __('agricart.store.admin.testimonials.hint') }}</p>

    @if ($canEdit)
        <form wire:submit="createManual" class="agricart-store-admin__card agricart-store-admin__form" style="margin-bottom: 1.25rem;">
            <h3 class="agricart-store-admin__title">{{ __('agricart.store.admin.testimonials.manual_title') }}</h3>
            <p class="agricart-store-admin__hint">{{ __('agricart.store.admin.testimonials.manual_hint') }}</p>
            <div class="agricart-store-admin__grid-2">
                <div class="agricart-store-admin__field">
                    <label>{{ __('agricart.store.admin.testimonials.col_customer') }}</label>
                    <input type="text" wire:model="manual_display_name" class="agricart-store-admin__input" required maxlength="120" />
                    @error('manual_display_name') <p class="agricart-store-admin__error">{{ $message }}</p> @enderror
                </div>
                <div class="agricart-store-admin__field">
                    <label>{{ __('agricart.store.admin.testimonials.col_rating') }}</label>
                    <select wire:model="manual_rating" class="agricart-store-admin__input">
                        @for ($star = 5; $star >= 1; $star--)
                            <option value="{{ $star }}">{{ $star }} / 5</option>
                        @endfor
                    </select>
                </div>
            </div>
            <div class="agricart-store-admin__field">
                <label>{{ __('agricart.store.admin.testimonials.field_title') }}</label>
                <input type="text" wire:model="manual_title" class="agricart-store-admin__input" maxlength="160" />
            </div>
            <div class="agricart-store-admin__field">
                <label>{{ __('agricart.store.admin.testimonials.col_body') }}</label>
                <textarea wire:model="manual_body" class="agricart-store-admin__input" rows="3" required maxlength="2000"></textarea>
                @error('manual_body') <p class="agricart-store-admin__error">{{ $message }}</p> @enderror
            </div>
            <label class="agricart-store-admin__checkbox">
                <input type="checkbox" wire:model="manual_featured" />
                <span>{{ __('agricart.store.admin.testimonials.feature') }}</span>
            </label>
            <button type="submit" class="agricart-store-admin__btn">{{ __('agricart.store.admin.testimonials.add_manual') }}</button>
        </form>
    @endif

    <div class="agricart-store-messages__summary" aria-label="{{ __('agricart.store.admin.testimonials.workflow_summary') }}">
        @foreach (\App\Models\CompanyTestimonial::STATUSES as $summaryStatus)
            <button
                type="button"
                class="agricart-store-messages__summary-card is-{{ $summaryStatus }} {{ $statusFilter === $summaryStatus ? 'is-active' : '' }}"
                wire:click="applyFilter('status:{{ $summaryStatus }}')"
            >
                <span>{{ __('agricart.store.admin.testimonials.statuses.'.$summaryStatus) }}</span>
                <strong>{{ $statusCounts[$summaryStatus] ?? 0 }}</strong>
            </button>
        @endforeach
    </div>

    @if ($items->isEmpty())
        <div class="agricart-categories__empty">
            <p>{{ __('agricart.store.admin.testimonials.empty') }}</p>
        </div>
    @else
        <div class="agricart-categories__table-wrap">
            <table class="agricart-categories__table">
                <thead>
                    <tr>
                        @if (in_array('customer', $visibleColumns, true))
                            <th>{{ __('agricart.store.admin.testimonials.col_customer') }}</th>
                        @endif
                        @if (in_array('rating', $visibleColumns, true))
                            <th>{{ __('agricart.store.admin.testimonials.col_rating') }}</th>
                        @endif
                        @if (in_array('body', $visibleColumns, true))
                            <th>{{ __('agricart.store.admin.testimonials.col_body') }}</th>
                        @endif
                        @if (in_array('featured', $visibleColumns, true))
                            <th>{{ __('agricart.store.admin.testimonials.col_featured') }}</th>
                        @endif
                        @if (in_array('status', $visibleColumns, true))
                            <th>{{ __('agricart.store.admin.testimonials.col_status') }}</th>
                        @endif
                        @if (in_array('received', $visibleColumns, true))
                            <th>{{ __('agricart.store.admin.testimonials.col_received') }}</th>
                        @endif
                        @if (in_array('actions', $visibleColumns, true))
                            <th>{{ __('agricart.store.admin.testimonials.col_actions') }}</th>
                        @endif
                    </tr>
                </thead>
                <tbody>
                    @foreach ($items as $item)
                        <tr wire:key="store-testimonial-{{ $item->id }}">
                            @if (in_array('customer', $visibleColumns, true))
                                <td>
                                    <strong>{{ $item->display_name }}</strong>
                                    @if ($item->source === \App\Models\CompanyTestimonial::SOURCE_ADMIN)
                                        <div class="agricart-store-reviews__path">{{ __('agricart.store.admin.testimonials.source_admin') }}</div>
                                    @endif
                                </td>
                            @endif
                            @if (in_array('rating', $visibleColumns, true))
                                <td>{{ $item->rating }} / 5</td>
                            @endif
                            @if (in_array('body', $visibleColumns, true))
                                <td class="agricart-store-messages__preview">
                                    @if (filled($item->title))
                                        <strong>{{ $item->title }}</strong><br>
                                    @endif
                                    {{ \Illuminate\Support\Str::limit($item->body, 100) }}
                                </td>
                            @endif
                            @if (in_array('featured', $visibleColumns, true))
                                <td>{{ $item->is_featured ? __('agricart.store.admin.testimonials.yes') : __('agricart.store.admin.testimonials.no') }}</td>
                            @endif
                            @if (in_array('status', $visibleColumns, true))
                                <td>
                                    <span class="agricart-store-messages__status is-{{ $item->status }}">
                                        {{ __('agricart.store.admin.testimonials.statuses.'.$item->status) }}
                                    </span>
                                </td>
                            @endif
                            @if (in_array('received', $visibleColumns, true))
                                <td>{{ agricart_datetime($item->created_at) }}</td>
                            @endif
                            @if (in_array('actions', $visibleColumns, true))
                                <td class="agricart-categories__actions">
                                    <button type="button" class="agricart-categories__link-btn" wire:click="openView({{ $item->id }})">
                                        {{ __('agricart.store.admin.testimonials.view') }}
                                    </button>
                                    @if ($canEdit)
                                        @if ($item->status !== \App\Models\CompanyTestimonial::STATUS_APPROVED)
                                            <button type="button" class="agricart-categories__link-btn" wire:click="approve({{ $item->id }})">
                                                {{ __('agricart.store.admin.testimonials.approve') }}
                                            </button>
                                        @endif
                                        @if ($item->isApproved())
                                            <button type="button" class="agricart-categories__link-btn" wire:click="toggleFeatured({{ $item->id }})">
                                                {{ $item->is_featured ? __('agricart.store.admin.testimonials.unfeature') : __('agricart.store.admin.testimonials.feature') }}
                                            </button>
                                            <button type="button" class="agricart-categories__link-btn" wire:click="hide({{ $item->id }})">
                                                {{ __('agricart.store.admin.testimonials.hide') }}
                                            </button>
                                        @endif
                                        @if ($item->status !== \App\Models\CompanyTestimonial::STATUS_REJECTED)
                                            <button type="button" class="agricart-categories__link-btn is-danger" wire:click="reject({{ $item->id }})">
                                                {{ __('agricart.store.admin.testimonials.reject') }}
                                            </button>
                                        @endif
                                        <button type="button" class="agricart-categories__link-btn is-danger" wire:click="delete({{ $item->id }})" wire:confirm="{{ __('agricart.store.admin.testimonials.delete_confirm') }}">
                                            {{ __('agricart.store.admin.testimonials.delete') }}
                                        </button>
                                    @endif
                                </td>
                            @endif
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <div class="agricart-categories__pagination">
            {{ $items->links() }}
        </div>
    @endif

    @if ($selected)
        <div class="agricart-store-messages__drawer" role="dialog" aria-modal="true">
            <div class="agricart-store-messages__drawer-panel">
                <div class="agricart-store-messages__drawer-head">
                    <h3>{{ __('agricart.store.admin.testimonials.detail_label') }}</h3>
                    <button type="button" class="agricart-categories__link-btn" wire:click="closeView">
                        {{ __('agricart.store.admin.testimonials.close_view') }}
                    </button>
                </div>
                <p><strong>{{ $selected->display_name }}</strong> — {{ $selected->rating }} / 5</p>
                @if (filled($selected->title))
                    <p><strong>{{ $selected->title }}</strong></p>
                @endif
                <p>{{ $selected->body }}</p>
                @if (filled($selected->admin_reply))
                    <p class="agricart-store-reviews__path">
                        <strong>{{ __('agricart.store.admin.testimonials.reply') }}:</strong> {{ $selected->admin_reply }}
                    </p>
                @endif
                @if ($canEdit)
                    <div class="agricart-store-admin__field" style="margin-top: 1rem;">
                        <label>{{ __('agricart.store.admin.testimonials.reply') }}</label>
                        <textarea wire:model="replyText" class="agricart-store-admin__input" rows="3" maxlength="2000" placeholder="{{ __('agricart.store.admin.testimonials.reply_placeholder') }}"></textarea>
                        <div class="agricart-store-admin__actions" style="margin-top: 0.5rem;">
                            <button type="button" class="agricart-store-admin__btn" wire:click="saveReply">{{ __('agricart.store.admin.testimonials.reply_save') }}</button>
                            <button type="button" class="agricart-store-admin__btn agricart-store-admin__btn--muted" wire:click="clearReply">{{ __('agricart.store.admin.testimonials.reply_clear') }}</button>
                        </div>
                    </div>
                @endif
            </div>
        </div>
    @endif
</div>
