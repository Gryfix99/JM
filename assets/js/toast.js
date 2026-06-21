/* PJM Toast — lekkie powiadomienia (zamiast alert()). window.pjmToast(msg, type) */
(function () {
    if (window.pjmToast) return;

    var wrap = null;
    function ensureWrap() {
        if (wrap) return wrap;
        wrap = document.createElement('div');
        wrap.id = 'pjm-toast-wrap';
        wrap.setAttribute('aria-live', 'polite');
        wrap.style.cssText = 'position:fixed;z-index:2147483647;bottom:20px;right:20px;display:flex;flex-direction:column;gap:10px;max-width:360px;';
        document.body.appendChild(wrap);
        return wrap;
    }

    var COLORS = {
        success: { bg: '#1B5E4B', icon: '✓' },
        error:   { bg: '#b91c1c', icon: '!' },
        info:    { bg: '#14352A', icon: 'i' },
        warning: { bg: '#A8472B', icon: '!' }
    };

    window.pjmToast = function (message, type) {
        try {
            var c = COLORS[type] || COLORS.success;
            var w = ensureWrap();
            var t = document.createElement('div');
            t.setAttribute('role', 'status');
            t.style.cssText = 'display:flex;align-items:flex-start;gap:10px;background:' + c.bg + ';color:#fff;padding:13px 16px;border-radius:10px;box-shadow:0 8px 24px rgba(0,0,0,.18);font-family:Inter,system-ui,sans-serif;font-size:14px;line-height:1.4;opacity:0;transform:translateY(8px);transition:opacity .2s,transform .2s;';
            var badge = document.createElement('span');
            badge.textContent = c.icon;
            badge.style.cssText = 'flex-shrink:0;width:20px;height:20px;border-radius:50%;background:rgba(255,255,255,.2);display:inline-flex;align-items:center;justify-content:center;font-weight:700;font-size:12px;';
            var txt = document.createElement('span');
            txt.textContent = String(message);
            t.appendChild(badge);
            t.appendChild(txt);
            w.appendChild(t);
            requestAnimationFrame(function () { t.style.opacity = '1'; t.style.transform = 'translateY(0)'; });
            var ttl = (type === 'error') ? 6000 : 3500;
            setTimeout(function () {
                t.style.opacity = '0'; t.style.transform = 'translateY(8px)';
                setTimeout(function () { if (t.parentNode) t.parentNode.removeChild(t); }, 250);
            }, ttl);
        } catch (e) {
            alert(message); // awaryjnie
        }
    };
})();
