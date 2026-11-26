(function() {
    var cfg = window.lxTourStylerConfig || {};
    var defaultIconClass = cfg.headericon || 'fa fa-star';
    var defaultTitleAnim = cfg.titleanim || 'typing';
    var typingSpeed = parseInt(cfg.typingspeed, 10) || 35;
    var colorMode = cfg.colormode || 'auto';
    var defaultSpotlight = cfg.spotlightcolor || '#6366f1';
    var stepOverrides = cfg.stepOverrides || {};

    function normalizeText(str) {
        var tmp = document.createElement('textarea');
        tmp.innerHTML = str || '';
        return tmp.value.replace(/\s+/g, ' ').trim().toLowerCase();
    }

    function findOverrideByTitle(titleText) {
        var clean = normalizeText(titleText);
        if (!clean) {
            return null;
        }
        var keys = Object.keys(stepOverrides);
        for (var i = 0; i < keys.length; i++) {
            var ov = stepOverrides[keys[i]];
            if (ov && ov.title && normalizeText(ov.title) === clean) {
                return ov;
            }
        }
        return null;
    }

    function isDarkActive() {
        if (colorMode === 'dark') {
            return true;
        }
        if (colorMode === 'light') {
            return false;
        }
        var html = document.documentElement;
        var body = document.body;
        if (html.getAttribute('data-bs-theme') === 'dark' ||
            html.classList.contains('dark') ||
            (body && (body.getAttribute('data-bs-theme') === 'dark' || body.classList.contains('dark-mode')))) {
            return true;
        }
        return window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches;
    }

    function runTypewriter(el) {
        var fullText = (el.dataset.lxOriginalTitle || el.textContent || '').trim();
        if (!fullText || el.dataset.lxTypedText === fullText || el.dataset.lxTyping === '1') {
            return;
        }
        el.dataset.lxOriginalTitle = fullText;
        el.dataset.lxTypedText = fullText;
        el.dataset.lxTyping = '1';
        el.textContent = '';
        el.classList.add('lx-typing-cursor');
        var i = 0;
        var timer = setInterval(function() {
            i++;
            el.textContent = fullText.slice(0, i);
            if (i >= fullText.length) {
                clearInterval(timer);
                el.classList.remove('lx-typing-cursor');
                el.dataset.lxTyping = '0';
            }
        }, typingSpeed);
    }

    function clearTargetHighlights() {
        document.querySelectorAll('.lx-tour-target-active').forEach(function(el) {
            el.classList.remove('lx-tour-target-active');
        });
    }

    function highlightClosestTarget() {
        var bg = document.querySelector('div[data-flexitour="step-background"]');
        if (!bg || bg.style.display === 'none') {
            clearTargetHighlights();
            return;
        }
        var rect = bg.getBoundingClientRect();
        if (rect.width <= 0 || rect.height <= 0) {
            return;
        }
        var cx = rect.left + rect.width / 2;
        var cy = rect.top + rect.height / 2;
        var stack = document.elementsFromPoint(cx, cy);
        clearTargetHighlights();
        for (var i = 0; i < stack.length; i++) {
            var candidate = stack[i];
            if (!candidate ||
                candidate === document.body ||
                candidate === document.documentElement ||
                candidate.getAttribute('data-flexitour') ||
                candidate.closest('[data-role="flexitour-step"]')) {
                continue;
            }
            var cRect = candidate.getBoundingClientRect();
            if (Math.abs(cRect.width - rect.width) <= 45 && Math.abs(cRect.height - rect.height) <= 45) {
                candidate.classList.add('lx-tour-target-active');
                break;
            }
        }
    }

    function enhanceTourSteps() {
        var dark = isDarkActive();
        var steps = document.querySelectorAll('[data-role="flexitour-step"].lx-tour-step');

        if (document.body) {
            document.body.classList.toggle('lx-tour-dark-active', dark && steps.length > 0);
        }

        if (steps.length === 0) {
            clearTargetHighlights();
            document.documentElement.style.setProperty('--lx-spotlight-color', defaultSpotlight);
            return;
        }

        steps.forEach(function(step) {
            step.classList.toggle('lx-mode-dark', dark);

            var titleEl = step.querySelector('.lx-tour-title');
            var rawTitle = titleEl ? (titleEl.dataset.lxOriginalTitle || titleEl.textContent || '').trim() : '';
            var ov = findOverrideByTitle(rawTitle) || {};

            var effectiveIcon = ov.icon || defaultIconClass;
            var effectiveAnim = ov.titleanim || defaultTitleAnim;

            if (ov.primarycolor) {
                step.style.setProperty('--lx-tour-primary', ov.primarycolor);
            } else {
                step.style.removeProperty('--lx-tour-primary');
            }

            if (ov.headerbg) {
                step.style.setProperty('--lx-tour-header-bg', ov.headerbg);
                step.style.setProperty('--lx-tour-dark-header-bg', ov.headerbg);
            } else {
                step.style.removeProperty('--lx-tour-header-bg');
                step.style.removeProperty('--lx-tour-dark-header-bg');
            }

            if (ov.titlecolor) {
                step.style.setProperty('--lx-tour-title-color', ov.titlecolor);
            } else {
                step.style.removeProperty('--lx-tour-title-color');
            }

            document.documentElement.style.setProperty('--lx-spotlight-color', ov.spotlightcolor || defaultSpotlight);

            var iconWrap = step.querySelector('.lx-tour-icon');
            if (iconWrap && iconWrap.dataset.lxIconApplied !== effectiveIcon) {
                iconWrap.innerHTML = '<i class="' + effectiveIcon + '" aria-hidden="true"></i>';
                iconWrap.dataset.lxIconApplied = effectiveIcon;
            }

            if (titleEl) {
                if (effectiveAnim === 'typing') {
                    runTypewriter(titleEl);
                } else if (effectiveAnim === 'fade_up') {
                    titleEl.style.animation = 'lxTourFadeUp 0.45s ease both';
                } else if (effectiveAnim === 'blur_in') {
                    titleEl.style.animation = 'lxTourBlurIn 0.5s ease both';
                } else if (effectiveAnim === 'none') {
                    titleEl.style.animation = 'none';
                }
            }

            var bodyEl = step.querySelector('.lx-tour-body');
            if (bodyEl && ov.bodyanim) {
                if (ov.bodyanim === 'fade_up') {
                    bodyEl.style.animation = 'lxTourFadeUp 0.45s ease 0.14s both';
                } else if (ov.bodyanim === 'blur_in') {
                    bodyEl.style.animation = 'lxTourBlurIn 0.52s ease 0.14s both';
                } else if (ov.bodyanim === 'slide_in') {
                    bodyEl.style.animation = 'lxTourSlideIn 0.45s ease 0.14s both';
                } else if (ov.bodyanim === 'none') {
                    bodyEl.style.animation = 'none';
                }
            }

            var nextBtn = step.querySelector('.lx-tour-btn-next');
            if (nextBtn && !nextBtn.querySelector('.lx-step-badge')) {
                var txt = nextBtn.textContent || '';
                var match = txt.match(/\((\d+)\s*\/\s*(\d+)\)/);
                if (match) {
                    var label = txt.replace(match[0], '').trim();
                    nextBtn.innerHTML = '<span>' + label + '</span><span class="lx-step-badge">' + match[1] + ' / ' + match[2] + '</span>';
                }
            }

            highlightClosestTarget();
        });
    }

    function start() {
        var observer = new MutationObserver(enhanceTourSteps);
        observer.observe(document.body, {childList: true, subtree: true, attributes: true, attributeFilter: ['style', 'class', 'data-bs-theme']});
        enhanceTourSteps();

        if (window.matchMedia) {
            window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', enhanceTourSteps);
        }
    }

    if (document.body) {
        start();
    } else {
        document.addEventListener('DOMContentLoaded', start);
    }
})();