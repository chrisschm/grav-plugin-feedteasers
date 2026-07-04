(function () {
    'use strict';

    function initFeedTeasers(root) {
        var tabs = root.querySelectorAll('.feedteasers__tab');
        if (!tabs.length) {
            return;
        }

        tabs.forEach(function (tab) {
            tab.addEventListener('click', function () {
                var targetId = tab.getAttribute('data-feedteasers-target');
                var panel = root.querySelector('#' + CSS.escape(targetId));
                if (!panel) {
                    return;
                }

                tabs.forEach(function (t) {
                    t.classList.remove('is-active');
                    t.setAttribute('aria-selected', 'false');
                });
                root.querySelectorAll('.feedteasers__panel').forEach(function (p) {
                    p.classList.add('is-hidden');
                });

                tab.classList.add('is-active');
                tab.setAttribute('aria-selected', 'true');
                panel.classList.remove('is-hidden');
            });
        });
    }

    document.addEventListener('DOMContentLoaded', function () {
        document.querySelectorAll('.feedteasers--tabbed').forEach(initFeedTeasers);
    });
})();
