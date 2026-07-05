@if ($contentBlocks !== [] || $faqs !== [] || filled($hsCode))
    <section class="ag-store-category-info" aria-label="{{ __('agricart.storefront.category.info_about', ['name' => $categoryService->localizedName($category)]) }}">
        <div class="ag-store-category-info__inner">
            @foreach ($contentBlocks as $block)
                <article @class(['ag-store-category-info__block', 'ag-store-category-info__block--' . $block['variant']])>
                    <h2 class="ag-store-category-info__heading">{{ $block['label'] }}</h2>
                    <div class="ag-store-category-info__body">{!! nl2br(e($block['content'])) !!}</div>
                </article>
            @endforeach

            @if (filled($hsCode))
                <article class="ag-store-category-info__block ag-store-category-info__block--meta">
                    <h2 class="ag-store-category-info__heading">{{ __('agricart.categories.fields.hs_code') }}</h2>
                    <div class="ag-store-category-info__body">{{ $hsCode }}</div>
                </article>
            @endif

            @if ($faqs !== [])
                <article class="ag-store-category-info__block ag-store-category-info__block--faqs">
                    <h2 class="ag-store-category-info__heading">{{ __('agricart.categories.sections.faqs') }}</h2>
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
