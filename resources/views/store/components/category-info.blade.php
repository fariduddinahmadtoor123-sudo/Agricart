@if ($contentBlocks !== [] || $faqs !== [] || filled($hsCode))
    <section class="ag-store-category-info" aria-label="{{ __('agricart.storefront.category.info_about', ['name' => $categoryService->localizedName($category)]) }}">
        @php
            $firstTabKey = $contentBlocks !== []
                ? 'category-content-0'
                : (filled($hsCode) ? 'category-hs-code' : 'category-faqs');
        @endphp

        <div class="ag-store-category-info__inner ag-store-pdp__tabs" data-store-pdp-tabs>
            <div class="ag-store-pdp__tablist" role="tablist" aria-label="{{ __('agricart.storefront.category.info_about', ['name' => $categoryService->localizedName($category)]) }}">
                @foreach ($contentBlocks as $block)
                    @php($tabKey = 'category-content-' . $loop->index)
                    <button
                        type="button"
                        @class(['ag-store-pdp__tab', 'is-active' => $tabKey === $firstTabKey])
                        id="store-category-tab-{{ $tabKey }}"
                        role="tab"
                        aria-selected="{{ $tabKey === $firstTabKey ? 'true' : 'false' }}"
                        aria-controls="store-category-panel-{{ $tabKey }}"
                        tabindex="{{ $tabKey === $firstTabKey ? '0' : '-1' }}"
                        data-store-pdp-tab="{{ $tabKey }}"
                    >
                        {{ __('agricart.storefront.category.tabs.' . $block['key']) }}
                    </button>
                @endforeach

                @if (filled($hsCode))
                    <button
                        type="button"
                        @class(['ag-store-pdp__tab', 'is-active' => $firstTabKey === 'category-hs-code'])
                        id="store-category-tab-category-hs-code"
                        role="tab"
                        aria-selected="{{ $firstTabKey === 'category-hs-code' ? 'true' : 'false' }}"
                        aria-controls="store-category-panel-category-hs-code"
                        tabindex="{{ $firstTabKey === 'category-hs-code' ? '0' : '-1' }}"
                        data-store-pdp-tab="category-hs-code"
                    >
                        {{ __('agricart.categories.fields.hs_code') }}
                    </button>
                @endif

                @if ($faqs !== [])
                    <button
                        type="button"
                        @class(['ag-store-pdp__tab', 'is-active' => $firstTabKey === 'category-faqs'])
                        id="store-category-tab-category-faqs"
                        role="tab"
                        aria-selected="{{ $firstTabKey === 'category-faqs' ? 'true' : 'false' }}"
                        aria-controls="store-category-panel-category-faqs"
                        tabindex="{{ $firstTabKey === 'category-faqs' ? '0' : '-1' }}"
                        data-store-pdp-tab="category-faqs"
                    >
                        {{ __('agricart.categories.sections.faqs') }}
                    </button>
                @endif
            </div>

            @foreach ($contentBlocks as $block)
                @php($panelKey = 'category-content-' . $loop->index)
                <article
                    @class(['ag-store-pdp__panel', 'is-active' => $panelKey === $firstTabKey])
                    id="store-category-panel-{{ $panelKey }}"
                    role="tabpanel"
                    aria-labelledby="store-category-tab-{{ $panelKey }}"
                    tabindex="0"
                    data-store-pdp-panel="{{ $panelKey }}"
                    @if ($panelKey !== $firstTabKey) hidden @endif
                >
                    <div class="ag-store-category-info__body">{!! nl2br(e($block['content'])) !!}</div>
                </article>
            @endforeach

            @if (filled($hsCode))
                <article
                    @class(['ag-store-pdp__panel', 'is-active' => $firstTabKey === 'category-hs-code'])
                    id="store-category-panel-category-hs-code"
                    role="tabpanel"
                    aria-labelledby="store-category-tab-category-hs-code"
                    tabindex="0"
                    data-store-pdp-panel="category-hs-code"
                    @if ($firstTabKey !== 'category-hs-code') hidden @endif
                >
                    <div class="ag-store-category-info__body">{{ $hsCode }}</div>
                </article>
            @endif

            @if ($faqs !== [])
                <article
                    @class(['ag-store-pdp__panel', 'is-active' => $firstTabKey === 'category-faqs'])
                    id="store-category-panel-category-faqs"
                    role="tabpanel"
                    aria-labelledby="store-category-tab-category-faqs"
                    tabindex="0"
                    data-store-pdp-panel="category-faqs"
                    @if ($firstTabKey !== 'category-faqs') hidden @endif
                >
                    <dl class="ag-store-category-info__faqs">
                        @foreach ($faqs as $faq)
                            <div class="ag-store-category-info__faq">
                                <dt class="ag-store-category-info__faq-q">{{ $faq['question'] }}</dt>
                                <dd class="ag-store-category-info__faq-a">{!! nl2br(e($faq['answer'])) !!}</dd>
                            </div>
                        @endforeach
                    </dl>
                </article>
            @endif
        </div>
    </section>
@endif
