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
                                    <strong>{{ $review->product?->name_en ?? '—' }}</strong>
                                    @if (($review->open_reports_count ?? 0) > 0)
                                        <p class="agricart-store-reviews__reports-badge">
                                            {{ trans_choice('agricart.store.admin.reviews.reports_open', $review->open_reports_count, ['count' => $review->open_reports_count]) }}
                                        </p>
                                    @endif
                                </td>
                            @endif
                            @if (in_array('customer', $visibleColumns, true))
                                <td>
                                    {{ $review->customer?->displayName() ?? '—' }}
                                    <p class="agricart-store-reviews__path">
                                        @if ($review->isProofSubmission())
                                            {{ __('agricart.store.admin.reviews.path_proof') }}
                                        @else
                                            {{ __('agricart.store.admin.reviews.path_verified') }}
                                        @endif
                                    </p>
                                </td>
                            @endif
                            @if (in_array('rating', $visibleColumns, true))
                                <td>{{ $review->rating }} / 5</td>
                            @endif
                            @if (in_array('body', $visibleColumns, true))
                                <td class="agricart-store-messages__preview">
                                    {{ \Illuminate\Support\Str::limit($review->body, 120) }}
                                    @if (filled($review->proof_note))
                                        <p class="agricart-store-reviews__proof-note">
                                            <strong>{{ __('agricart.store.admin.reviews.proof_note_label') }}:</strong>
                                            {{ \Illuminate\Support\Str::limit($review->proof_note, 120) }}
                                        </p>
                                    @endif
                                    @php
                                        $galleryPhotos = $review->photos->where('kind', \App\Models\ProductReviewPhoto::KIND_GALLERY);
                                        $proofPhotos = $review->photos->where('kind', \App\Models\ProductReviewPhoto::KIND_PROOF);
                                    @endphp
                                    @if ($proofPhotos->isNotEmpty())
                                        <p class="agricart-store-reviews__media-label">{{ __('agricart.store.admin.reviews.proof_label') }}</p>
                                        <div class="agricart-store-reviews__thumbs">
                                            @foreach ($proofPhotos as $photo)
                                                <span class="agricart-store-reviews__thumb">
                                                    @if ($photo->isPdf())
                                                        <a href="{{ $photo->url() }}" target="_blank" rel="noopener">{{ __('agricart.store.admin.reviews.proof_pdf') }}</a>
                                                    @else
                                                        <a href="{{ $photo->url() }}" target="_blank" rel="noopener">
                                                            <img src="{{ $photo->url() }}" alt="" width="48" height="48" loading="lazy" />
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
                                    @endif
                                    @if ($galleryPhotos->isNotEmpty())
                                        <p class="agricart-store-reviews__media-label">{{ __('agricart.store.admin.reviews.gallery_label') }}</p>
                                        <div class="agricart-store-reviews__thumbs">
                                            @foreach ($galleryPhotos as $photo)
                                                <span class="agricart-store-reviews__thumb">
                                                    <a href="{{ $photo->url() }}" target="_blank" rel="noopener">
                                                        <img src="{{ $photo->url() }}" alt="" width="48" height="48" loading="lazy" />
                                                    </a>
                                                    @if ($canEdit)
                                                        <button type="button" class="agricart-categories__action is-danger" wire:click="deletePhoto({{ $photo->id }})">
                                                            {{ __('agricart.store.admin.reviews.delete_photo') }}
                                                        </button>
                                                    @endif
                                                </span>
                                            @endforeach
                                        </div>
                                    @endif
                                    @if (filled($review->admin_reply))
                                        <p class="agricart-store-reviews__reply-preview">
                                            <strong>{{ __('agricart.storefront.reviews.admin_reply') }}:</strong>
                                            {{ \Illuminate\Support\Str::limit($review->admin_reply, 80) }}
                                        </p>
                                    @endif
                                    @if ($replyReviewId === $review->id)
                                        <div class="agricart-store-reviews__reply-editor">
                                            <textarea rows="3" wire:model="replyText" class="fi-input" placeholder="{{ __('agricart.store.admin.reviews.reply_placeholder') }}"></textarea>
                                            <button type="button" class="agricart-categories__action is-primary" wire:click="saveReply">
                                                {{ __('agricart.store.admin.reviews.reply_save') }}
                                            </button>
                                        </div>
                                    @endif
                                    @foreach ($review->reports as $report)
                                        <div class="agricart-store-reviews__report-row">
                                            <span>{{ __('agricart.storefront.reviews.report_reasons.'.$report->reason) }}</span>
                                            @if ($canEdit)
                                                <button type="button" class="agricart-categories__action" wire:click="resolveReport({{ $report->id }})">
                                                    {{ __('agricart.store.admin.reviews.resolve_report') }}
                                                </button>
                                                <button type="button" class="agricart-categories__action" wire:click="dismissReport({{ $report->id }})">
                                                    {{ __('agricart.store.admin.reviews.dismiss_report') }}
                                                </button>
                                                <button type="button" class="agricart-categories__action is-warning" wire:click="resolveReportAndHide({{ $report->id }})">
                                                    {{ __('agricart.store.admin.reviews.resolve_and_hide') }}
                                                </button>
                                            @endif
                                        </div>
                                    @endforeach
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
                                <td>{{ $review->created_at?->format('Y-m-d H:i') }}</td>
                            @endif
                            @if (in_array('actions', $visibleColumns, true))
                                <td class="agricart-categories__actions agricart-store-messages__row-actions">
                                    @if ($canEdit)
                                        @if ($review->status !== \App\Models\ProductReview::STATUS_APPROVED)
                                            <button type="button" class="agricart-categories__action is-primary" wire:click="approve({{ $review->id }})">
                                                {{ __('agricart.store.admin.reviews.approve') }}
                                            </button>
                                        @endif
                                        @if ($review->status !== \App\Models\ProductReview::STATUS_HIDDEN)
                                            <button type="button" class="agricart-categories__action" wire:click="hide({{ $review->id }})">
                                                {{ __('agricart.store.admin.reviews.hide') }}
                                            </button>
                                        @endif
                                        @if ($review->status !== \App\Models\ProductReview::STATUS_REJECTED)
                                            <button type="button" class="agricart-categories__action" wire:click="reject({{ $review->id }})">
                                                {{ __('agricart.store.admin.reviews.reject') }}
                                            </button>
                                        @endif
                                        @if ($review->status !== \App\Models\ProductReview::STATUS_PENDING)
                                            <button type="button" class="agricart-categories__action" wire:click="pending({{ $review->id }})">
                                                {{ __('agricart.store.admin.reviews.pending') }}
                                            </button>
                                        @endif
                                        <button type="button" class="agricart-categories__action" wire:click="openReply({{ $review->id }})">
                                            {{ __('agricart.store.admin.reviews.reply') }}
                                        </button>
                                        @if (filled($review->admin_reply))
                                            <button type="button" class="agricart-categories__action" wire:click="clearReply({{ $review->id }})">
                                                {{ __('agricart.store.admin.reviews.reply_clear') }}
                                            </button>
                                        @endif
                                        <button
                                            type="button"
                                            class="agricart-categories__action is-danger"
                                            wire:click="delete({{ $review->id }})"
                                            wire:confirm="{{ __('agricart.store.admin.reviews.delete_confirm') }}"
                                        >
                                            {{ __('agricart.store.admin.reviews.delete') }}
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
</div>
