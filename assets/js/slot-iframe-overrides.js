(function ($) {
    $(function () {
        if (typeof SioIframeOverrides === 'undefined' || !SioIframeOverrides.iframeUrl) {
            return;
        }

        var url = SioIframeOverrides.iframeUrl;

        var tryReplace = function () {
            var $iframe = $('iframe.check-in').first();

            if (!$iframe.length) {
                $iframe = $('[class$="Iframe"] iframe, [class*="Iframe "] iframe').first();
            }

            if ($iframe.length) {
                $iframe.attr('src', url);
                return true;
            }

            return false;
        };

        if (tryReplace()) {
            return;
        }

        var attempts = 0;
        var maxAttempts = 20;
        var interval = setInterval(function () {
            attempts++;
            if (tryReplace() || attempts >= maxAttempts) {
                clearInterval(interval);
            }
        }, 500);
    });
})(jQuery);

