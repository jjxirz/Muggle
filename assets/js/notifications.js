(function () {
    function iconFor(type) {
        switch (type) {
            case 'success': return 'fas fa-check-circle';
            case 'error': return 'fas fa-times-circle';
            case 'warning': return 'fas fa-exclamation-triangle';
            default: return 'fas fa-info-circle';
        }
    }

    function getStack() {
        return document.querySelector('.app-notify-stack');
    }

    function show(message, options) {
        var stack = getStack();
        if (!stack || !message) {
            return;
        }

        var opts = options || {};
        var type = opts.type || 'info';
        var timeout = typeof opts.timeout === 'number' ? opts.timeout : 2600;

        var el = document.createElement('div');
        el.className = 'app-notify app-notify--' + type;
        el.setAttribute('role', 'status');
        el.setAttribute('aria-live', 'polite');
        el.innerHTML = '<i class="' + iconFor(type) + '" aria-hidden="true"></i>' +
            '<p class="app-notify__message"></p>';

        var msg = el.querySelector('.app-notify__message');
        if (msg) {
            msg.textContent = String(message);
        }

        stack.appendChild(el);
        requestAnimationFrame(function () {
            el.classList.add('is-visible');
        });

        window.setTimeout(function () {
            el.classList.remove('is-visible');
            window.setTimeout(function () {
                if (el.parentNode) {
                    el.parentNode.removeChild(el);
                }
            }, 220);
        }, Math.max(1200, timeout));
    }

    window.AppNotify = {
        show: show,
        success: function (message, options) {
            show(message, Object.assign({}, options || {}, { type: 'success' }));
        },
        error: function (message, options) {
            show(message, Object.assign({}, options || {}, { type: 'error' }));
        },
        warning: function (message, options) {
            show(message, Object.assign({}, options || {}, { type: 'warning' }));
        },
        info: function (message, options) {
            show(message, Object.assign({}, options || {}, { type: 'info' }));
        }
    };
})();
