@php use Illuminate\Support\Facades\Storage; @endphp
<div class="agricart-categories agricart-store-reviews">
    <p class="agricart-store-reviews__policy">{{ __('agricart.store.admin.reviews.policy_checklist') }}</p>

    <div class="agricart-store-messages__summary" aria-label="{{ __('agricart.store.admin.reviews.workflow_summary') }}">
        @foreach (\App\Models\ProductReview::STATUSES as $summaryStatus)
            <button
                type="button"
                class="agricart-store-messages__summary-card is-{{ $summaryStatus }} {{ $statusFilter === $summaryStatus ? 'is-active' : '' }}"
                wire:click="applyFilter('status:{{ $summaryStatus }}')"
            >
                <span>{{ __('agricart.store.admin.reviews.statuses.'.$summaryStatus) }}</span>
                <strong>{{ $statusCounts[$summaryStatus] ?? 0 }}</strong>
            </button>
        @endforeach
    </div>

    @if ($reviews->isEmpty())
        <div class="agricart-categories__empty">
            <p>{{ __('agricart.store.admin.reviews.empty') }}</p>
        </div>
    @else
        <div class="agricart-categories__table-wrap">
            <table class="agricart-categories__table">
                <thead>
                    <tr>
                        @if (in_array('product', $visibleColumns, true))
                            <th>{{ __('agricart.store.admin.reviews.col_product') }}</th>
                        @endif
                        @if (in_array('customer', $visibleColumns, true))
                            <th>{{ __('agricart.store.admin.reviews.col_customer') }}</th>
                        @endif
                        @if (in_array('rating', $visibleColumns, true))
                            <th>{{ __('agricart.store.admin.reviews.col_rating') }}</th>
                        @endif
                        @if (in_array('body', $visibleColumns, true))
                            <th>{{ __('agricart.store.admin.reviews.col_body') }}</th>
                        @endif
                        @if (in_array('status', $visibleColumns, true))
                            <th>{{ __('agricart.store.admin.reviews.col_status') }}</th>
                        @endif
                        @if (in_array('received', $visibleColumns, true))
                            <th>{{ __('agricart.store.admin.reviews.col_received') }}</th>
                        @endif
                        @if (in_array('actions', $visibleColumns, true))
                            <th>{{ __('agricart.store.admin.reviews.col_actions') }}</th>
                        @endif
                    </tr>
                </thead>
                <tbody>
                    @foreach ($reviews as $review)
                        <tr wire:key="store-product-review-{{ $review->id }}">
                            @if (in_array('product', $visibleColumns, true))
                                <td>
                                    <div class="agricart-store-reviews__product-cell">
                                        @if ($review->product?->image_path)
                                            <img
                                                src="{{ Storage::disk('public')->url($review->product->image_path) }}"
                                                alt=""
                                                class="agricart-categories__thumb"
                                                loading="lazy"
                                            />
                                        @else
                                            <span class="agricart-categories__thumb-placeholder">—</span>
                                        @endif
                                        <div class="agricart-store-reviews__product-text">
                                            <strong>{{ $review->product?->name_en ?? '—' }}</strong>
                                            <span class="agricart-store-reviews__path">
                                                @if ($review->isProofSubmission())
                                                    {{ __('agricart.store.admin.reviews.path_proof') }}
                                                @else
                                                    {{ __('agricart.store.admin.reviews.path_verified') }}
                                                @endif
                                            </span>
                                        </div>
                                    </div>
                                </td>
                            @endif
                            @if (in_array('customer', $visibleColumns, true))
                                <td>{{ $review->customer?->displayName() ?? '—' }}</td>
                            @endif
                            @if (in_array('rating', $visibleColumns, true))
                                <td>{{ $review->rating }} / 5</td>
                            @endif
                            @if (in_array('body', $visibleColumns, true))
                                <td class="agricart-store-messages__preview">
                                    <span class="agricart-store-reviews__review-label">{{ __('agricart.store.admin.reviews.review_label') }}</span>
                                    {{ \Illuminate\Support\Str::limit($review->body, 100) }}
                                </td>
                            @endif
                            @if (in_array('status', $visibleColumns, true))
                                <td>
                                    <span class="agricart-store-messages__status is-{{ $review->status }}">
                                        {{ __('agricart.store.admin.reviews.statuses.'.$review->status) }}
                                    </span>
                                </td>
                            @endif
                            @if (in_array('received', $visibleColumns, true))
                                <td>{{ agricart_datetime($review->created_at) }}</td>
                            @endif
                            @if (in_array('actions', $visibleColumns, true))
                                <td class="agricart-categories__actions agricart-store-messages__row-actions">
                                    <button type="button" class="agricart-categories__action is-primary" wire:click="openView({{ $review->id }})">
                                        {{ __('agricart.store.admin.reviews.view') }}
                                    </button>
                                    @if ($canEdit && $review->status !== \App\Models\ProductReview::STATUS_APPROVED)
                                        <button type="button" class="agricart-categories__action" wire:click="approve({{ $review->id }})">
                                            {{ __('agricart.store.admin.reviews.approve') }}
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
            {{ $reviews->links() }}
        </div>
    @endif

    @if ($selectedReview)
        @php
            $galleryPhotos = $selectedReview->photos->where('kind', \App\Models\ProductReviewPhoto::KIND_GALLERY);
            $proofPhotos = $selectedReview->photos->where('kind', \App\Models\ProductReviewPhoto::KIND_PROOF);
        @endphp
        <div class="agricart-store-messages__overlay" wire:click.self="closeView" x-data @keydown.escape.window="$wire.closeView()">
            <section
                class="agricart-store-messages__panel agricart-store-reviews__panel"
                role="dialog"
                aria-modal="true"
                aria-labelledby="store-review-detail-title"
            >
                <header class="agricart-store-messages__panel-head">
                    <div>
                        <span class="agricart-store-messages__panel-eyebrow">{{ __('agricart.store.admin.reviews.detail_label') }}</span>
                        <h2 id="store-review-detail-title">{{ $selectedReview->product?->name_en ?? __('agricart.store.admin.reviews.col_product') }}</h2>
                    </div>
                    <button type="button" class="agricart-store-messages__panel-close" wire:click="closeView" aria-label="{{ __('agricart.store.admin.reviews.close_view') }}">×</button>
                </header>

                <div class="agricart-store-reviews__detail-product">
                    @if ($selectedReview->product?->image_path)
                        <img
                            src="{{ Storage::disk('public')->url($selectedReview->product->image_path) }}"
                            alt=""
                            class="agricart-store-reviews__detail-product-image"
                            loading="lazy"
                        />
                    @else
                        <span class="agricart-store-reviews__detail-product-placeholder">—</span>
                    @endif
                    <div>
                        <p class="agricart-store-reviews__detail-product-name">{{ $selectedReview->product?->name_en ?? '—' }}</p>
                        <p class="agricart-store-reviews__path">
                            @if ($selectedReview->isProofSubmission())
                                {{ __('agricart.store.admin.reviews.path_proof') }}
                            @else
                                {{ __('agricart.store.admin.reviews.path_verified') }}
                                @if ($selectedReview->order?->order_number)
                                    · {{ $selectedReview->order->order_number }}
                                @endif
                            @endif
                        </p>
                    </div>
                </div>

                <div class="agricart-store-messages__panel-status">
                    <span class="agricart-store-messages__status is-{{ $selectedReview->status }}">
                        {{ __('agricart.store.admin.reviews.statuses.'.$selectedReview->status) }}
                    </span>
                    <span>{{ $selectedReview->rating }} / 5</span>
                    <span>{{ __('agricart.store.admin.reviews.received_at', ['date' => agricart_datetime($selectedReview->created_at)]) }}</span>
                </div>

                <dl class="agricart-store-messages__meta">
                    <div>
                        <dt>{{ __('agricart.store.admin.reviews.col_customer') }}</dt>
                        <dd>{{ $selectedReview->customer?->displayName() ?? '—' }}</dd>
                    </div>
                    <div>
                        <dt>{{ __('agricart.users.fields.email') }}</dt>
                        <dd>{{ $selectedReview->customer?->email ?: '—' }}</dd>
                    </div>
                    <div>
                        <dt>{{ __('agricart.store.admin.reviews.col_phone') }}</dt>
                        <dd>
                            @if (filled($selectedReview->customer?->phone))
                                <a href="tel:{{ $selectedReview->customer->phone }}">{{ $selectedReview->customer->phone }}</a>
                            @else
                                —
                            @endif
                        </dd>
                    </div>
                </dl>

                <div class="agricart-store-messages__body">
                    <h3>{{ __('agricart.store.admin.reviews.review_label') }}</h3>
                    <p>{{ $selectedReview->body }}</p>
                </div>

                @if (filled($selectedReview->proof_note))
                    <div class="agricart-store-messages__body">
                        <h3>{{ __('agricart.store.admin.reviews.proof_note_label') }}</h3>
                        <p>{{ $selectedReview->proof_note }}</p>
                    </div>
                @endif

                @if ($proofPhotos->isNotEmpty())
                    <div class="agricart-store-reviews__detail-media">
                        <h3>{{ __('agricart.store.admin.reviews.proof_label') }}</h3>
                        <div class="agricart-store-reviews__thumbs">
                            @foreach ($proofPhotos as $photo)
                                <span class="agricart-store-reviews__thumb">
                                    @if ($photo->isPdf())
                                        <a href="{{ $photo->url() }}" target="_blank" rel="noopener">{{ __('agricart.store.admin.reviews.proof_pdf') }}</a>
                                    @else
                                        <a href="{{ $photo->url() }}" target="_blank" rel="noopener">
                                            <img src="{{ $photo->url() }}" alt="" width="72" height="72" loading="lazy" />
                                        </a>
                                    @endif
                                    @if ($canEdit)
                                        <button type="button" class="agricart-categories__action is-danger" wire:click="deletePhoto({{ $photo->id }})">
                                            {{ __('agricart.store.admin.reviews.delete_photo') }}
                                        </button>
                                    @endif
                                </span>
                            @endforeach
                        </div>
                    </div>
                @endif

                @if ($galleryPhotos->isNotEmpty())
                    <div class="agricart-store-reviews__detail-media">
                        <h3>{{ __('agricart.store.admin.reviews.gallery_label') }}</h3>
                        <div class="agricart-store-reviews__thumbs">
                            @foreach ($galleryPhotos as $photo)
                                <span class="agricart-store-reviews__thumb">
                                    <a href="{{ $photo->url() }}" target="_blank" rel="noopener">
                                        <img src="{{ $photo->url() }}" alt="" width="72" height="72" loading="lazy" />
                                    </a>
                                    @if ($canEdit)
                                        <button type="button" class="agricart-categories__action is-danger" wire:click="deletePhoto({{ $photo->id }})">
                                            {{ __('agricart.store.admin.reviews.delete_photo') }}
                                        </button>
                                    @endif
                                </span>
                            @endforeach
                        </div>
                    </div>
                @endif

                @if ($canEdit)
                    <div class="agricart-store-reviews__reply-editor">
                        <h3>{{ __('agricart.storefront.reviews.admin_reply') }}</h3>
                        <textarea rows="3" wire:model="replyText" class="fi-input" placeholder="{{ __('agricart.store.admin.reviews.reply_placeholder') }}"></textarea>
                        <div class="agricart-store-reviews__reply-actions">
                            <button type="button" class="agricart-categories__action is-primary" wire:click="saveReply">
                                {{ __('agricart.store.admin.reviews.reply_save') }}
                            </button>
                            @if (filled($selectedReview->admin_reply))
                                <button type="button" class="agricart-categories__action" wire:click="clearReply">
                                    {{ __('agricart.store.admin.reviews.reply_clear') }}
                                </button>
                            @endif
                        </div>
                    </div>
                @elseif (filled($selectedReview->admin_reply))
                    <div class="agricart-store-messages__body">
                        <h3>{{ __('agricart.storefront.reviews.admin_reply') }}</h3>
                        <p>{{ $selectedReview->admin_reply }}</p>
                    </div>
                @endif

                <x-communication.record-panels
                    linkable-type="product_review"
                    :linkable-id="$selectedReview->id"
                />

                @if ($canEdit)
                    <footer class="agricart-store-messages__panel-actions">
                        @if ($selectedReview->status !== \App\Models\ProductReview::STATUS_APPROVED)
                            <button type="button" class="agricart-categories__action is-primary" wire:click="approve({{ $selectedReview->id }})">
                                {{ __('agricart.store.admin.reviews.approve') }}
                            </button>
                        @endif
                        @if ($selectedReview->status !== \App\Models\ProductReview::STATUS_HIDDEN)
                            <button type="button" class="agricart-categories__action" wire:click="hide({{ $selectedReview->id }})">
                                {{ __('agricart.store.admin.reviews.hide') }}
                            </button>
                        @endif
                        @if ($selectedReview->status !== \App\Models\ProductReview::STATUS_REJECTED)
                            <button type="button" class="agricart-categories__action" wire:click="reject({{ $selectedReview->id }})">
                                {{ __('agricart.store.admin.reviews.reject') }}
                            </button>
                        @endif
                        @if ($selectedReview->status !== \App\Models\ProductReview::STATUS_PENDING)
                            <button type="button" class="agricart-categories__action" wire:click="pending({{ $selectedReview->id }})">
                                {{ __('agricart.store.admin.reviews.pending') }}
                            </button>
                        @endif
                        <button
                            type="button"
                            class="agricart-categories__action is-danger"
                            wire:click="delete({{ $selectedReview->id }})"
                            wire:confirm="{{ __('agricart.store.admin.reviews.delete_confirm') }}"
                        >
                            {{ __('agricart.store.admin.reviews.delete') }}
                        </button>
                    </footer>
                @endif
            </section>
        </div>
    @endif
</div>
