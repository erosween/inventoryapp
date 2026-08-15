<script>
(function () {
    const recentAjaxKeys = new Map();

    function uuid() {
        if (window.crypto && typeof window.crypto.randomUUID === 'function') {
            return window.crypto.randomUUID();
        }
        return 'xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx'.replace(/[xy]/g, function (c) {
            const r = Math.random() * 16 | 0;
            return (c === 'x' ? r : (r & 3 | 8)).toString(16);
        });
    }

    function protectForms(root) {
        const forms = Array.from((root || document).querySelectorAll('form'));
        if (root && root.matches && root.matches('form')) forms.unshift(root);
        forms.forEach(function (form) {
            if ((form.getAttribute('method') || 'GET').toUpperCase() === 'GET') return;
            if (form.querySelector('input[name="_idempotency_key"]')) return;

            const input = document.createElement('input');
            input.type = 'hidden';
            input.name = '_idempotency_key';
            input.value = uuid();
            form.appendChild(input);
        });
    }

    document.addEventListener('DOMContentLoaded', function () {
        protectForms(document);
        new MutationObserver(function (mutations) {
            mutations.forEach(function (mutation) {
                mutation.addedNodes.forEach(function (node) {
                    if (node.nodeType === 1) protectForms(node);
                });
            });
        }).observe(document.body, { childList: true, subtree: true });

        if (window.jQuery) {
            window.jQuery.ajaxPrefilter(function (options, originalOptions, xhr) {
                const method = (options.type || 'GET').toUpperCase();
                if (method === 'GET' || method === 'HEAD') return;

                const serializedData = typeof originalOptions.data === 'string'
                    ? originalOptions.data
                    : window.jQuery.param(originalOptions.data || {});
                const signature = method + '|' + options.url + '|' + serializedData;
                const now = Date.now();
                const previous = recentAjaxKeys.get(signature);
                const key = previous && now - previous.time < 10000 ? previous.key : uuid();
                recentAjaxKeys.set(signature, { key: key, time: now });
                xhr.setRequestHeader('Idempotency-Key', key);
            });
        }
    });
})();
</script>
