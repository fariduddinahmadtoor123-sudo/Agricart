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
        var searchPage = document.querySelector('[data-store-search-page]');
        var resultsHost = searchPage ? searchPage.querySelector('[data-store-search-results]') : null;
        var titleEl = searchPage ? searchPage.querySelector('[data-store-search-title]') : null;
        var titleForTemplate = searchPage ? (searchPage.dataset.storeSearchTitleFor || '') : '';
        var titleDefault = searchPage ? (searchPage.dataset.storeSearchTitleDefault || '') : '';
        var emptyQueryMessage = searchPage ? (searchPage.dataset.storeSearchEmptyQuery || '') : '';

        document.querySelectorAll('[data-store-search]').forEach(function (root) {
            if (root.dataset.storeSearchReady === '1') {
                return;
            }
            root.dataset.storeSearchReady = '1';

            var input = root.querySelector('[data-store-search-input]');
            var liveUrl = root.dataset.storeSearchLiveUrl || '/search';
            var fragmentUrl = root.dataset.storeSearchFragmentUrl || '';
            var minChars = parseInt(root.dataset.storeSearchMinChars || '2', 10);

            if (!input) {
                return;
            }

            var debounceTimer = null;
            var fetchController = null;
            var lastQuery = null;

            function updateTitle(query) {
                if (!titleEl) {
                    return;
                }
                if (query.length >= minChars) {
                    titleEl.textContent = titleForTemplate.replace('__QUERY__', query);
                } else {
                    titleEl.textContent = titleDefault;
                }
            }

            function runLiveSearch() {
                var query = input.value.trim();

                if (query === lastQuery) {
                    return;
                }
                lastQuery = query;

                if (searchPage && resultsHost && fragmentUrl) {
                    updateTitle(query);

                    if (query.length < minChars) {
                        var clearUrl = new URL(liveUrl, window.location.origin);
                        window.history.replaceState(null, '', clearUrl.pathname);
                        resultsHost.innerHTML = '<div class="ag-store-empty">' + escapeHtml(emptyQueryMessage || titleDefault) + '</div>';
                        return;
                    }

                    if (fetchController) {
                        fetchController.abort();
                    }
                    fetchController = new AbortController();

                    var fragmentRequest = new URL(fragmentUrl, window.location.origin);
                    fragmentRequest.searchParams.set('q', query);

                    var pageUrl = new URL(liveUrl, window.location.origin);
                    pageUrl.searchParams.set('q', query);
                    window.history.replaceState(null, '', pageUrl.toString());

                    fetch(fragmentRequest.toString(), {
                        headers: {
                            Accept: 'text/html',
                            'X-Requested-With': 'XMLHttpRequest',
                        },
                        credentials: 'same-origin',
                        signal: fetchController.signal,
                    })
                        .then(function (response) {
                            if (!response.ok) {
                                throw new Error('Search fragment failed');
                            }
                            return response.text();
                        })
                        .then(function (html) {
                            resultsHost.innerHTML = html;
                        })
                        .catch(function (error) {
                            if (error.name !== 'AbortError') {
                                window.location.assign(pageUrl.toString());
                            }
                        });

                    return;
                }

                if (query.length < minChars) {
                    return;
                }

                var destination = new URL(liveUrl, window.location.origin);
                destination.searchParams.set('q', query);

                if (window.location.href !== destination.href) {
                    window.location.assign(destination.toString());
                }
            }

            function scheduleLiveSearch() {
                window.clearTimeout(debounceTimer);
                debounceTimer = window.setTimeout(runLiveSearch, 380);
            }

            input.addEventListener('input', scheduleLiveSearch);

            lastQuery = input.value.trim();

            if (searchPage && input.value.trim().length >= minChars) {
                scheduleLiveSearch();
            }
        });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initStoreSearch);
    } else {
        initStoreSearch();
    }
})();
</script>
