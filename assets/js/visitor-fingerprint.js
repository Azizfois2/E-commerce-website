/**
 * Maroc PC — Browser Fingerprint Collector
 *
 * Generates a lightweight browser fingerprint hash from:
 *  canvas rendering, WebGL, screen, timezone, language, fonts,
 *  touch support, platform, hardware concurrency.
 *
 * POSTs result to api/visitor-fingerprint.php.
 * No external dependencies. ~2KB minified.
 */
(function () {
    'use strict';

    // Simple FNV-1a 32-bit hash → hex string
    function fnv1a(str) {
        var h = 0x811c9dc5;
        for (var i = 0; i < str.length; i++) {
            h ^= str.charCodeAt(i);
            h = (h + ((h << 1) + (h << 4) + (h << 7) + (h << 8) + (h << 24))) >>> 0;
        }
        // Convert to 8-char hex, pad
        return ('00000000' + h.toString(16)).slice(-8);
    }

    function canvasFingerprint() {
        try {
            var c = document.createElement('canvas');
            c.width = 220;
            c.height = 30;
            var ctx = c.getContext('2d');
            if (!ctx) return '';
            ctx.textBaseline = 'top';
            ctx.font = '14px Arial';
            ctx.fillStyle = '#f60';
            ctx.fillRect(0, 0, 220, 30);
            ctx.fillStyle = '#069';
            ctx.fillText('MarocPC 🖥️ fp!', 2, 8);
            ctx.strokeStyle = 'rgba(102,204,0,0.7)';
            ctx.arc(50, 15, 12, 0, Math.PI * 2);
            ctx.stroke();
            return c.toDataURL().slice(-80);
        } catch (e) {
            return '';
        }
    }

    function webglFingerprint() {
        try {
            var c = document.createElement('canvas');
            var gl = c.getContext('webgl') || c.getContext('experimental-webgl');
            if (!gl) return '';
            var dbg = gl.getExtension('WEBGL_debug_renderer_info');
            if (!dbg) return '';
            return gl.getParameter(dbg.UNMASKED_RENDERER_WEBGL) + '|' +
                   gl.getParameter(dbg.UNMASKED_VENDOR_WEBGL);
        } catch (e) {
            return '';
        }
    }

    function probeFonts() {
        var baseFonts = ['monospace', 'sans-serif', 'serif'];
        var testFonts = [
            'Arial', 'Courier New', 'Georgia', 'Times New Roman', 'Verdana',
            'Trebuchet MS', 'Tahoma', 'Comic Sans MS', 'Impact', 'Lucida Console',
            'Consolas', 'Palatino Linotype', 'Book Antiqua', 'Segoe UI',
            'Calibri', 'Cambria', 'Candara', 'Helvetica', 'Futura'
        ];
        var s = document.createElement('span');
        s.textContent = 'mmmmmmmmmmlli';
        s.style.fontSize = '72px';
        s.style.position = 'absolute';
        s.style.left = '-9999px';
        document.body.appendChild(s);

        var baseWidths = {};
        baseFonts.forEach(function (f) {
            s.style.fontFamily = f;
            baseWidths[f] = s.offsetWidth;
        });

        var detected = [];
        testFonts.forEach(function (tf) {
            var found = false;
            baseFonts.forEach(function (bf) {
                s.style.fontFamily = tf + ',' + bf;
                if (s.offsetWidth !== baseWidths[bf]) found = true;
            });
            if (found) detected.push(tf);
        });

        document.body.removeChild(s);
        return detected.join(',');
    }

    function collect() {
        var parts = [
            canvasFingerprint(),
            webglFingerprint(),
            screen.width + 'x' + screen.height,
            screen.colorDepth || '',
            Intl.DateTimeFormat().resolvedOptions().timeZone || '',
            navigator.language || '',
            (navigator.languages || []).join(','),
            navigator.platform || '',
            navigator.hardwareConcurrency || '',
            navigator.maxTouchPoints || 0,
            probeFonts(),
            navigator.vendor || '',
        ];
        return parts.join('|||');
    }

    function send() {
        var raw = collect();
        var hash = fnv1a(raw);
        var screenRes = screen.width + 'x' + screen.height;

        var payload = {
            hash: hash,
            screen: screenRes,
            language: navigator.language || '',
            pageUrl: window.location.pathname + window.location.search,
            referrer: document.referrer || '',
        };

        // Use sendBeacon for reliability, fallback to fetch
        var url = (window.__marocPcBaseUrl || '') + 'api/visitor-fingerprint.php';
        var body = JSON.stringify(payload);

        if (navigator.sendBeacon) {
            var blob = new Blob([body], { type: 'application/json' });
            if (navigator.sendBeacon(url, blob)) return;
        }

        fetch(url, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: body,
            keepalive: true,
        }).catch(function () {});
    }

    // Wait for DOM to be ready so font probing works
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', function () {
            setTimeout(send, 200);
        });
    } else {
        setTimeout(send, 200);
    }
})();
