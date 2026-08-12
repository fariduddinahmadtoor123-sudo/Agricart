<div class="agricart-categories agricart-store-reviews">
    <p class="agricart-store-reviews__policy">{{ __('agricart.store.admin.brand_reviews.hint') }}</p>

    <div class="agricart-store-messages__summary" aria-label="{{ __('agricart.store.admin.brand_reviews.workflow_summary') }}">
        @foreach (\App\Models\BrandCustomerReview::STATUSES as $summaryStatus)
            <button
                type="button"
                class="agricart-store-messages__summary-card is-{{ $summaryStatus }} {{ $statusFilter === $summaryStatus ? 'is-active' : '' }}"
                wire:click="applyFilter('status:{{ $summaryStatus }}')"
            >
                <span>{{ __('agricart.store.admin.brand_reviews.statuses.'.$summaryStatus) }}</span>
                <strong>{{ $statusCounts[$summaryStatus] ?? 0 }}</strong>
            </button>
        @endforeach
    </div>

    @if ($items->isEmpty())
        <div class="agricart-categories__empty">
            <p>{{ __('agricart.store.admin.brand_reviews.empty') }}</p>
        </div>
    @else
        <div class="agricart-categories__table-wrap">
            <table class="agricart-categories__table">
                <thead>
                    <tr>
                        @if (in_array('brand', $visibleColumns, true))
                            <th>{{ __('agricart.store.admin.brand_reviews.col_brand') }}</th>
                        @endif
                        @if (in_array('customer', $visibleColumns, true))
                            <th>{{ __('agricart.store.admin.brand_reviews.col_customer') }}</th>
                        @endif
                        @if (in_array('rating', $visibleColumns, true))
                            <th>{{ __('agricart.store.admin.brand_reviews.col_rating') }}</th>
                        @endif
                        @if (in_array('body', $visibleColumns, true))
                            <th>{{ __('agricart.store.admin.brand_reviews.col_body') }}</th>
                        @endif
                        @if (in_array('status', $visibleColumns, true))
                            <th>{{ __('agricart.store.admin.brand_reviews.col_status') }}</th>
                        @endif
                        @if (in_array('received', $visibleColumns, true))
                            <th>{{ __('agricart.store.admin.brand_reviews.col_received') }}</th>
                        @endif
                        @if (in_array('actions', $visibleColumns, true))
                            <th>{{ __('agricart.store.admin.brand_reviews.col_actions') }}</th>
                        @endif
                    </tr>
                </thead>
                <tbody>
                    @foreach ($items as $item)
                        <tr wire:key="store-brand-review-{{ $item->id }}">
                            @if (in_array('brand', $visibleColumns, true))
                                <td><strong>{{ $item->brand?->name_en }}</strong></td>
                            @endif
                            @if (in_array('customer', $visibleColumns, true))
                                <td>{{ $item->customer?->displayName() ?? '—' }}</td>
                            @endif
                            @if (in_array('rating', $visibleColumns, true))
                                <td>{{ $item->rating }} / 5</td>
                            @endif
                            @if (in_array('body', $visibleColumns, true))
                                <td class="agricart-store-messages__preview">{{ \Illuminate\Support\Str::limit($item->body, 90) }}</td>
                            @endif
                            @if (in_array('status', $visibleColumns, true))
                                <td>
                                    <span class="agricart-store-reviews__status is-{{ $item->status }}">
                                        {{ __('agricart.store.admin.brand_reviews.statuses.'.$item->status) }}
                                    </span>
                                </td>
                            @endif
                            @if (in_array('received', $visibleColumns, true))
                                <td>{{ agricart_datetime($item->created_at) }}</td>
                            @endif
                            @if (in_array('actions', $visibleColumns, true))
                                <td class="agricart-categories__actions">
                                    <button type="button" class="agricart-categories__link-btn" wire:click="openView({{ $item->id }})">
                                        {{ __('agricart.store.admin.brand_reviews.view') }}
                                    </button>
                                    @if ($canEdit)
                                        @if ($item->status !== \App\Models\BrandCustomerReview::STATUS_APPROVED)
                                            <button type="button" class="agricart-categories__link-btn" wire:click="approve({{ $item->id }})">
                                                {{ __('agricart.store.admin.brand_reviews.approve') }}
                                            </button>
                                        @endif
                                        @if ($item->status === \App\Models\BrandCustomerReview::STATUS_APPROVED)
                                            <button type="button" class="agricart-categories__link-btn" wire:click="hide({{ $item->id }})">
                                                {{ __('agricart.store.admin.brand_reviews.hide') }}
                                            </button>
                                        @endif
                                        @if ($item->status !== \App\Models\BrandCustomerReview::STATUS_REJECTED)
                                            <button type="button" class="agricart-categories__link-btn is-danger" wire:click="reject({{ $item->id }})">
                                                {{ __('agricart.store.admin.brand_reviews.reject') }}
                                            </button>
                                        @endif
                                        <button type="button" class="agricart-categories__link-btn is-danger" wire:click="delete({{ $item->id }})" wire:confirm="{{ __('agricart.store.admin.brand_reviews.delete_confirm') }}">
                                            {{ __('agricart.store.admin.brand_reviews.delete') }}
                                        </button>
                                    @endif
                                </td>
                            @endif
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <div class="agricart-categories__pagination">{{ $items->links() }}</div>
    @endif

    @if ($selected)
        <div class="agricart-store-reviews__drawer" wire:key="brand-review-drawer-{{ $selected->id }}">
            <div class="agricart-store-reviews__drawer-head">
                <h3>{{ __('agricart.store.admin.brand_reviews.detail_label') }}</h3>
                <button type="button" class="agricart-categories__link-btn" wire:click="closeView">
                    {{ __('agricart.store.admin.brand_reviews.close_view') }}
                </button>
            </div>
            <p><strong>{{ __('agricart.store.admin.brand_reviews.col_brand') }}:</strong> {{ $selected->brand?->name_en }}</p>
            <p><strong>{{ __('agricart.store.admin.brand_reviews.col_customer') }}:</strong> {{ $selected->customer?->displayName() }}</p>
            <p><strong>{{ __('agricart.store.admin.brand_reviews.col_rating') }}:</strong> {{ $selected->rating }} / 5</p>
            <p>{{ $selected->body }}</p>
            @if (filled($selected->admin_reply))
                <p><strong>{{ __('agricart.store.admin.brand_reviews.reply') }}:</strong> {{ $selected->admin_reply }}</p>
            @endif
            @if ($canEdit)
                <div class="agricart-store-admin__field">
                    <label>{{ __('agricart.store.admin.brand_reviews.reply') }}</label>
                    <textarea wire:model="replyText" class="agricart-store-admin__input" rows="3" maxlength="2000" placeholder="{{ __('agricart.store.admin.brand_reviews.reply_placeholder') }}"></textarea>
                    <div class="agricart-store-admin__actions">
                        <button type="button" class="agricart-store-admin__btn" wire:click="saveReply">{{ __('agricart.store.admin.brand_reviews.reply_save') }}</button>
                        <button type="button" class="agricart-store-admin__btn agricart-store-admin__btn--muted" wire:click="clearReply">{{ __('agricart.store.admin.brand_reviews.reply_clear') }}</button>
                    </div>
                </div>
            @endif
        </div>
    @endif
</div>
