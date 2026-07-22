<script>
(function () {
    function escapeHtml(value) {
        return String(value)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;');
    }

    function initStoreSearch() {
        document.querySelectorAll('[data-store-search]').forEach(function (root) {
            if (root.dataset.storeSearchReady === '1') {
                return;
            }
            root.dataset.storeSearchReady = '1';

            var input = root.querySelector('[data-store-search-input]');
            var form = root.querySelector('[data-store-search-form]');
            var list = root.querySelector('[data-store-search-list]');
            var status = root.querySelector('[data-store-search-status]');
            var suggestUrl = root.dataset.storeSearchSuggestUrl || '/search/suggest';
            var minChars = parseInt(root.dataset.storeSearchMinChars || '2', 10);

            if (!input || !form || !list || !status) {
                return;
            }

            var debounceTimer = null;
            var fetchController = null;
            var options = [];
            var activeIndex = -1;

            function announce(message) {
                status.textContent = '';
                window.setTimeout(function () { status.textContent = message; }, 20);
            }

            function closeList() {
                list.hidden = true;
                list.innerHTML = '';
                options = [];
                activeIndex = -1;
                input.setAttribute('aria-expanded', 'false');
                input.removeAttribute('aria-activedescendant');
            }

            function setActive(index) {
                if (options.length === 0) {
                    return;
                }
                activeIndex = (index + options.length) % options.length;
                options.forEach(function (option, optionIndex) {
                    option.setAttribute('aria-selected', optionIndex === activeIndex ? 'true' : 'false');
                    option.classList.toggle('is-active', optionIndex === activeIndex);
                });
                input.setAttribute('aria-activedescendant', options[activeIndex].id);
                options[activeIndex].scrollIntoView({ block: 'nearest' });
            }

            function renderResults(results, searchUrl) {
                if (!Array.isArray(results) || results.length === 0) {
                    closeList();
                    announce(root.dataset.storeSearchEmpty || '');
                    return;
                }

                list.innerHTML = results.map(function (result, index) {
                    var meta = [result.type_label, result.code, result.brand].filter(Boolean).join(' · ');
                    var letter = String(result.name || '?').trim().charAt(0).toUpperCase() || '?';
                    var thumb = result.image
                        ? '<img class="ag-store-search-suggestions__thumb-img" src="' + escapeHtml(result.image) + '" alt="" width="40" height="40" loading="lazy" decoding="async" data-thumb-letter="' + escapeHtml(letter) + '" onerror="var letter=this.getAttribute(\'data-thumb-letter\')||\'?\';var span=document.createElement(\'span\');span.className=\'ag-store-search-suggestions__thumb-letter\';span.setAttribute(\'aria-hidden\',\'true\');span.textContent=letter;this.replaceWith(span);" />'
                        : '<span class="ag-store-search-suggestions__thumb-letter" aria-hidden="true">' + escapeHtml(letter) + '</span>';

                    return '<a id="' + list.id + '-option-' + index + '" role="option" aria-selected="false" class="ag-store-search-suggestions__option" href="' +
                        escapeHtml(result.url) + '" data-store-search-option>' +
                        '<span class="ag-store-search-suggestions__thumb">' + thumb + '</span>' +
                        '<span class="ag-store-search-suggestions__text">' +
                        '<span class="ag-store-search-suggestions__name">' + escapeHtml(result.name) + '</span>' +
                        (meta ? '<span class="ag-store-search-suggestions__meta">' + escapeHtml(meta) + '</span>' : '') +
                        '</span></a>';
                }).join('') + '<a role="option" aria-selected="false" class="ag-store-search-suggestions__option ag-store-search-suggestions__all" href="' +
                    escapeHtml(searchUrl) + '" data-store-search-option>{{ e(__('agricart.storefront.search.view_all')) }}</a>';
                list.hidden = false;
                input.setAttribute('aria-expanded', 'true');
                options = Array.prototype.slice.call(list.querySelectorAll('[data-store-search-option]'));
                activeIndex = -1;
                announce((root.dataset.storeSearchCount || ':count results').replace(':count', String(results.length)));
            }

            function fetchSuggestions() {
                var query = input.value.trim();
                if (query.length < minChars) {
                    if (fetchController) {
                        fetchController.abort();
                    }
                    closeList();
                    return;
                }

                if (fetchController) {
                    fetchController.abort();
                }
                fetchController = new AbortController();
                announce(root.dataset.storeSearchLoading || '');

                var requestUrl = new URL(suggestUrl, window.location.origin);
                requestUrl.searchParams.set('q', query);
                fetch(requestUrl.toString(), {
                    headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                    credentials: 'same-origin',
                    signal: fetchController.signal,
                }).then(function (response) {
                    if (!response.ok) {
                        throw new Error('Suggestion request failed');
                    }
                    return response.json();
                }).then(function (payload) {
                    if (input.value.trim() === payload.query) {
                        renderResults(payload.results, payload.search_url);
                    }
                }).catch(function (error) {
                    if (error.name !== 'AbortError') {
                        closeList();
                        announce(root.dataset.storeSearchError || '');
                    }
                });
            }

            input.addEventListener('input', function () {
                window.clearTimeout(debounceTimer);
                debounceTimer = window.setTimeout(fetchSuggestions, 250);
            });
            input.addEventListener('keydown', function (event) {
                if (event.key === 'Escape') {
                    closeList();
                    return;
                }
                if (list.hidden || options.length === 0) {
                    return;
                }
                if (event.key === 'ArrowDown') {
                    event.preventDefault();
                    setActive(activeIndex + 1);
                } else if (event.key === 'ArrowUp') {
                    event.preventDefault();
                    setActive(activeIndex - 1);
                } else if (event.key === 'Home') {
                    event.preventDefault();
                    setActive(0);
                } else if (event.key === 'End') {
                    event.preventDefault();
                    setActive(options.length - 1);
                } else if (event.key === 'Enter' && activeIndex >= 0) {
                    event.preventDefault();
                    window.location.assign(options[activeIndex].href);
                }
            });
            input.addEventListener('focus', function () {
                if (options.length > 0) {
                    list.hidden = false;
                    input.setAttribute('aria-expanded', 'true');
                }
            });
            document.addEventListener('pointerdown', function (event) {
                if (!root.contains(event.target)) {
                    closeList();
                }
            });
        });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initStoreSearch);
    } else {
        initStoreSearch();
    }
})();
</script>
