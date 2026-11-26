// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Runtime tour enhancer for User Tour Styler.
 *
 * @module     local_tourstyler/tour
 * @copyright  2026 AbdelrhmanBoghdady support@learn-ix.com
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

let closeListenerBound = false;

export const init = (cfg = {}) => {
    const defaultIconClass = cfg.headericon || 'fa fa-star';
    const defaultTitleAnim = cfg.titleanim || 'typing';
    const typingSpeed = parseInt(cfg.typingspeed, 10) || 35;
    const colorMode = cfg.colormode || 'auto';
    const defaultSpotlight = cfg.spotlightcolor || '#6366f1';
    const stepOverrides = cfg.stepOverrides || {};

    /**
     * Normalize text for comparison.
     *
     * @param {string} str
     * @return {string}
     */
    const normalizeText = (str) => {
        const tmp = document.createElement('textarea');
        tmp.innerHTML = str || '';
        return tmp.value.replace(/\s+/g, ' ').trim().toLowerCase();
    };

    /**
     * Find override configuration by title.
     *
     * @param {string} titleText
     * @return {Object|null}
     */
    const findOverrideByTitle = (titleText) => {
        const clean = normalizeText(titleText);
        if (!clean) {
            return null;
        }
        for (const key of Object.keys(stepOverrides)) {
            const ov = stepOverrides[key];
            if (ov && ov.title && normalizeText(ov.title) === clean) {
                return ov;
            }
        }
        return null;
    };

    /**
     * Check if dark mode is active.
     *
     * @return {boolean}
     */
    const isDarkActive = () => {
        if (colorMode === 'dark') {
            return true;
        }
        if (colorMode === 'light') {
            return false;
        }
        const html = document.documentElement;
        const body = document.body;
        const hasDarkClass = html.getAttribute('data-bs-theme') === 'dark' ||
            html.classList.contains('dark') ||
            (body && (body.getAttribute('data-bs-theme') === 'dark' ||
                body.classList.contains('dark-mode')));
        if (hasDarkClass) {
            return true;
        }
        return window.matchMedia &&
            window.matchMedia('(prefers-color-scheme: dark)').matches;
    };

    /**
     * Run typewriter effect on title element.
     *
     * @param {HTMLElement} el
     */
    const runTypewriter = (el) => {
        const fullText = (el.dataset.lxOriginalTitle ||
            el.textContent || '').trim();
        if (!fullText || el.dataset.lxTypedText === fullText ||
            el.dataset.lxTyping === '1') {
            return;
        }
        el.dataset.lxOriginalTitle = fullText;
        el.dataset.lxTypedText = fullText;
        el.dataset.lxTyping = '1';
        el.textContent = '';
        el.classList.add('lx-typing-cursor');
        let i = 0;
        const timer = setInterval(() => {
            i++;
            el.textContent = fullText.slice(0, i);
            if (i >= fullText.length) {
                clearInterval(timer);
                el.classList.remove('lx-typing-cursor');
                el.dataset.lxTyping = '0';
            }
        }, typingSpeed);
    };

    /**
     * Clear active target highlights.
     */
    const clearTargetHighlights = () => {
        document.querySelectorAll('.lx-tour-target-active').forEach((el) => {
            el.classList.remove('lx-tour-target-active');
        });
    };

    /**
     * Highlight closest target element.
     */
    const highlightClosestTarget = () => {
        const bg = document.querySelector(
            'div[data-flexitour="step-background"]'
        );
        if (!bg || bg.style.display === 'none') {
            clearTargetHighlights();
            return;
        }
        const rect = bg.getBoundingClientRect();
        if (rect.width <= 0 || rect.height <= 0) {
            return;
        }
        const cx = rect.left + rect.width / 2;
        const cy = rect.top + rect.height / 2;
        const stack = document.elementsFromPoint(cx, cy);
        clearTargetHighlights();
        for (const candidate of stack) {
            const isIgnored = !candidate ||
                candidate === document.body ||
                candidate === document.documentElement ||
                candidate.getAttribute('data-flexitour') ||
                candidate.closest('[data-role="flexitour-step"]');
            if (isIgnored) {
                continue;
            }
            const cRect = candidate.getBoundingClientRect();
            if (
                Math.abs(cRect.width - rect.width) <= 45 &&
                Math.abs(cRect.height - rect.height) <= 45
            ) {
                candidate.classList.add('lx-tour-target-active');
                break;
            }
        }
    };

    /**
     * Apply custom CSS variables to step.
     *
     * @param {HTMLElement} step
     * @param {Object} ov
     */
    const applyStepVariables = (step, ov) => {
        const setOrRemove = (prop, val) => {
            if (val) {
                step.style.setProperty(prop, val);
            } else {
                step.style.removeProperty(prop);
            }
        };
        setOrRemove('--lx-tour-primary', ov.primarycolor);
        setOrRemove('--lx-tour-header-bg', ov.headerbg);
        setOrRemove('--lx-tour-dark-header-bg', ov.headerbg);
        setOrRemove('--lx-tour-title-color', ov.titlecolor);
        document.documentElement.style.setProperty(
            '--lx-spotlight-color',
            ov.spotlightcolor || defaultSpotlight
        );
    };

    /**
     * Apply title and body animations to step.
     *
     * @param {HTMLElement} step
     * @param {HTMLElement|null} titleEl
     * @param {Object} ov
     */
    const applyStepAnimations = (step, titleEl, ov) => {
        const effectiveAnim = ov.titleanim || defaultTitleAnim;
        const titleMap = {
            fade_up: 'lxTourFadeUp 0.45s ease both',
            blur_in: 'lxTourBlurIn 0.5s ease both',
            none: 'none',
        };
        if (titleEl) {
            if (effectiveAnim === 'typing') {
                runTypewriter(titleEl);
            } else if (titleMap[effectiveAnim]) {
                titleEl.style.animation = titleMap[effectiveAnim];
            }
        }

        const bodyEl = step.querySelector('.lx-tour-body');
        const bodyMap = {
            fade_up: 'lxTourFadeUp 0.45s ease 0.14s both',
            blur_in: 'lxTourBlurIn 0.52s ease 0.14s both',
            slide_in: 'lxTourSlideIn 0.45s ease 0.14s both',
            none: 'none',
        };
        if (bodyEl && ov.bodyanim && bodyMap[ov.bodyanim]) {
            bodyEl.style.animation = bodyMap[ov.bodyanim];
        }
    };

    /**
     * Enhance single tour step element.
     *
     * @param {HTMLElement} step
     * @param {boolean} dark
     */
    const enhanceSingleStep = (step, dark) => {
        step.classList.toggle('lx-mode-dark', dark);

        const titleEl = step.querySelector('.lx-tour-title');
        const rawTitle = titleEl ?
            (titleEl.dataset.lxOriginalTitle ||
                titleEl.textContent || '').trim() : '';
        const ov = findOverrideByTitle(rawTitle) || {};
        const effectiveIcon = ov.icon || defaultIconClass;

        applyStepVariables(step, ov);

        const iconWrap = step.querySelector('.lx-tour-icon');
        if (iconWrap && iconWrap.dataset.lxIconApplied !== effectiveIcon) {
            iconWrap.innerHTML =
                `<i class="${effectiveIcon}" aria-hidden="true"></i>`;
            iconWrap.dataset.lxIconApplied = effectiveIcon;
        }

        applyStepAnimations(step, titleEl, ov);

        const nextBtn = step.querySelector('.lx-tour-btn-next');
        if (nextBtn && !nextBtn.querySelector('.lx-step-badge')) {
            const txt = nextBtn.textContent || '';
            const match = txt.match(/\((\d+)\s*\/\s*(\d+)\)/);
            if (match) {
                const label = txt.replace(match[0], '').trim();
                nextBtn.innerHTML =
                    `<span>${label}</span>` +
                    `<span class="lx-step-badge">` +
                    `${match[1]} / ${match[2]}</span>`;
            }
        }

        highlightClosestTarget();
    };

    /**
     * Enhance all active tour steps.
     */
    const enhanceTourSteps = () => {
        const dark = isDarkActive();
        const steps = document.querySelectorAll(
            '[data-role="flexitour-step"].lx-tour-step'
        );

        if (document.body) {
            document.body.classList.toggle(
                'lx-tour-dark-active',
                dark && steps.length > 0
            );
        }

        if (steps.length === 0) {
            clearTargetHighlights();
            document.documentElement.style.setProperty(
                '--lx-spotlight-color',
                defaultSpotlight
            );
            return;
        }

        steps.forEach((step) => enhanceSingleStep(step, dark));
    };

    if (!closeListenerBound) {
        closeListenerBound = true;
        document.addEventListener('click', (e) => {
            const closeBtn = e.target.closest('[data-lx-action="close"]');
            if (closeBtn) {
                const step = closeBtn.closest(
                    '[data-role="flexitour-step"]'
                );
                const endBtn = step ? step.querySelector(
                    '.lx-tour-footer [data-role="end"]'
                ) : null;
                if (endBtn) {
                    endBtn.click();
                }
            }
        }, true);
    }

    const observer = new MutationObserver(enhanceTourSteps);
    observer.observe(document.body, {
        childList: true,
        subtree: true,
        attributes: true,
        attributeFilter: ['style', 'class', 'data-bs-theme'],
    });
    enhanceTourSteps();

    if (window.matchMedia) {
        window.matchMedia('(prefers-color-scheme: dark)').addEventListener(
            'change',
            enhanceTourSteps
        );
    }
};