@if (config('analytics.enabled'))
    <script>
        (() => {
            const endpoint = @js(route('store.analytics.presence'));
            const token = document.querySelector('meta[name="csrf-token"]')?.content;

            if (!token || navigator.globalPrivacyControl === true || navigator.doNotTrack === '1') {
                return;
            }

            const heartbeat = () => {
                if (document.visibilityState !== 'visible') {
                    return;
                }

                fetch(endpoint, {
                    method: 'POST',
                    credentials: 'same-origin',
                    keepalive: true,
                    headers: {
                        'X-CSRF-TOKEN': token,
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json',
                    },
                }).catch(() => {});
            };

            window.setInterval(heartbeat, 60_000);
            document.addEventListener('visibilitychange', () => {
                if (document.visibilityState === 'visible') {
                    heartbeat();
                }
            });
        })();
    </script>
@endif
