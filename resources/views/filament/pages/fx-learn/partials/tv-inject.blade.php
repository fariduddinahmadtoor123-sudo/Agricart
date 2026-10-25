<script>
    window.agricartTvTheme = function () {
        return document.documentElement.classList.contains('dark') ? 'dark' : 'light';
    };

    window.agricartInjectTv = function (host, src, config) {
        if (!host) return;
        host.innerHTML = '';
        const wrap = document.createElement('div');
        wrap.className = 'tradingview-widget-container';
        wrap.style.width = '100%';
        wrap.style.height = '100%';
        const widget = document.createElement('div');
        widget.className = 'tradingview-widget-container__widget';
        widget.style.width = '100%';
        widget.style.height = '100%';
        wrap.appendChild(widget);
        const script = document.createElement('script');
        script.type = 'text/javascript';
        script.src = src;
        script.async = true;
        script.text = JSON.stringify(config);
        wrap.appendChild(script);
        host.appendChild(wrap);
    };

    window.agricartWatchTvTheme = function (onChange) {
        const obs = new MutationObserver(() => onChange());
        obs.observe(document.documentElement, {
            attributes: true,
            attributeFilter: ['class'],
        });
        return obs;
    };
</script>
