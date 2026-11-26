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
 * Live interactive studio controller for User Tour Styler settings.
 *
 * @module     local_tourstyler/studio
 * @copyright  2026 AbdelrhmanBoghdady support@learn-ix.com
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

export const init = () => {
    const defaults = {
        colormode: 'auto',
        preset: 'saas',
        primarycolor: '#6366f1',
        headericon: 'fa fa-star',
        headerbg: '#ffffff',
        titlecolor: '#0f172a',
        footerbg: '#f8fafc',
        textcolor: '#475569',
        borderradius: '20',
        nextbtnbg: '#6366f1',
        nextbtntext: '#ffffff',
        endbtnbg: '#ffffff',
        endbtntext: '#475569',
        btnradius: '10',
        accentstyle: 'gradient_animated',
        accentcolor1: '#6366f1',
        accentcolor2: '#8b5cf6',
        accentcolor3: '#0ea5e9',
        accentheight: '5',
        spotlightcolor: '#6366f1',
        backdropmode: 'blur',
        backdropcolor: '#0f172a',
        backdropopacity: '58',
        backdropblur: '4',
        titleanim: 'typing',
        typingspeed: '35',
        bodyanim: 'fade_up',
    };

    const fields = Object.keys(defaults);
    let prevTimer = null;
    const defaultSampleTitle = 'Welcome to Your Course';
    const defaultSampleBody =
        'Adjust colors, match platform branding, ' +
        'or customize individual tour steps live.';
    let toursData = [];
    let stepOverrides = {};
    let activeMode = 'global';
    let activeFilter = 'all';
    let selectedTourId = null;
    let selectedStep = null;
    let selectedStepIndex = 0;
    let selectedTourTotal = 0;

    /**
     * Get real input element by name.
     *
     * @param {string} name
     * @return {HTMLElement|null}
     */
    const mInput = (name) => {
        return document.getElementById('id_s_local_tourstyler_' + name);
    };

    /**
     * Get value from input or fallback.
     *
     * @param {string} name
     * @param {string} fallback
     * @return {string}
     */
    const getVal = (name, fallback) => {
        const el = mInput(name);
        return el && el.value !== '' ? el.value : fallback;
    };

    /**
     * Normalize hex color.
     *
     * @param {string} value
     * @param {string} fallback
     * @return {string}
     */
    const normHex = (value, fallback) => {
        return /^#[0-9a-fA-F]{6}$/.test(value) ? value : fallback;
    };

    /**
     * Convert rgb color string to hex.
     *
     * @param {string} rgb
     * @return {string|null}
     */
    const rgbToHex = (rgb) => {
        const match = (rgb || '').match(/rgba?\((\d+),\s*(\d+),\s*(\d+)/);
        if (!match) {
            return null;
        }
        const hex = (x) => {
            return ('0' + parseInt(x, 10).toString(16)).slice(-2);
        };
        return '#' + hex(match[1]) + hex(match[2]) + hex(match[3]);
    };

    /**
     * Detect platform brand color from DOM.
     *
     * @return {string}
     */
    const detectPlatformBrandColor = () => {
        const rootStyles = getComputedStyle(document.documentElement);
        const vars = [
            '--primary',
            '--bs-primary',
            '--brand-primary',
            '--theme-primary',
        ];
        for (let i = 0; i < vars.length; i++) {
            const v = rootStyles.getPropertyValue(vars[i]).trim();
            if (/^#[0-9a-fA-F]{6}$/.test(v)) {
                return v;
            }
            const parsed = rgbToHex(v);
            if (parsed) {
                return parsed;
            }
        }
        const probe = document.querySelector('.btn-primary, .nav-link.active');
        if (probe) {
            const bg = getComputedStyle(probe).backgroundColor;
            const hex = rgbToHex(bg);
            if (hex && hex !== '#000000' && hex !== '#ffffff') {
                return hex;
            }
        }
        return '#6366f1';
    };

    /**
     * Convert hex to rgba.
     *
     * @param {string} hex
     * @param {number} alpha
     * @return {string}
     */
    const hexToRgba = (hex, alpha) => {
        const clean = normHex(hex, '#0f172a').substring(1);
        const r = parseInt(clean.substring(0, 2), 16);
        const g = parseInt(clean.substring(2, 4), 16);
        const b = parseInt(clean.substring(4, 6), 16);
        return `rgba(${r},${g},${b},${alpha})`;
    };

    /**
     * Check if preview dark mode is active.
     *
     * @param {string} mode
     * @return {boolean}
     */
    const isPreviewDark = (mode) => {
        if (mode === 'dark') {
            return true;
        }
        if (mode === 'light') {
            return false;
        }
        return window.matchMedia &&
            window.matchMedia('(prefers-color-scheme: dark)').matches;
    };

    /**
     * Save step overrides to hidden input.
     */
    const saveStepOverridesToInput = () => {
        const real = mInput('stepoverrides');
        if (real) {
            real.value = JSON.stringify(stepOverrides);
            real.dispatchEvent(new Event('change', {bubbles: true}));
        }
    };

    /**
     * Get current step override object.
     *
     * @return {Object}
     */
    const getCurrentStepOverride = () => {
        if (activeMode !== 'tours' || !selectedStep) {
            return {};
        }
        return stepOverrides[String(selectedStep.id)] || {};
    };

    /**
     * Apply values map to controls.
     *
     * @param {Object} map
     */
    const applyValues = (map) => {
        Object.keys(map).forEach((name) => {
            const real = mInput(name);
            const bound = document.querySelector(`[data-bind="${name}"]`);
            const picker = document.querySelector(`[data-color="${name}"]`);
            if (real) {
                real.value = map[name];
                real.dispatchEvent(new Event('change', {bubbles: true}));
            }
            if (bound) {
                bound.value = map[name];
            }
            if (picker) {
                picker.value = normHex(map[name], '#6366f1');
            }
        });
        renderPreview();
        playPreviewAnim();
    };

    /**
     * Play preview title and body animations.
     */
    const playPreviewAnim = () => {
        const ov = getCurrentStepOverride();
        const tAnim = ov.titleanim || getVal('titleanim', 'typing');
        const bAnim = ov.bodyanim || getVal('bodyanim', 'fade_up');
        const speed = Math.max(
            15,
            Math.min(90, parseInt(getVal('typingspeed', '35'), 10) || 35)
        );
        const titleEl = document.getElementById('lx-prev-title');
        const bodyEl = document.getElementById('lx-prev-body');
        if (!titleEl || !bodyEl) {
            return;
        }

        const activeTitle = (activeMode === 'tours' && selectedStep) ?
            (selectedStep.rawtitle || 'Untitled Step') : defaultSampleTitle;

        if (prevTimer) {
            clearInterval(prevTimer);
            prevTimer = null;
        }

        titleEl.classList.remove('lx-prev-cursor');
        titleEl.style.animation = 'none';
        bodyEl.style.animation = 'none';
        void titleEl.offsetWidth;

        if (tAnim === 'typing') {
            titleEl.textContent = '';
            titleEl.classList.add('lx-prev-cursor');
            let idx = 0;
            prevTimer = setInterval(() => {
                idx++;
                titleEl.textContent = activeTitle.slice(0, idx);
                if (idx >= activeTitle.length) {
                    clearInterval(prevTimer);
                    prevTimer = null;
                    titleEl.classList.remove('lx-prev-cursor');
                }
            }, speed);
        } else {
            titleEl.textContent = activeTitle;
            if (tAnim === 'fade_up') {
                titleEl.style.animation = 'lxTourFadeUp 0.45s ease both';
            } else if (tAnim === 'blur_in') {
                titleEl.style.animation = 'lxTourBlurIn 0.5s ease both';
            }
        }

        if (bAnim === 'fade_up') {
            bodyEl.style.animation = 'lxTourFadeUp 0.5s ease 0.12s both';
        } else if (bAnim === 'blur_in') {
            bodyEl.style.animation = 'lxTourBlurIn 0.55s ease 0.12s both';
        } else if (bAnim === 'slide_in') {
            bodyEl.style.animation = 'lxTourSlideIn 0.45s ease 0.12s both';
        }
    };

    /**
     * Render live preview card and stage.
     */
    const renderPreview = () => {
        const card = document.getElementById('lx-prev-card');
        if (!card) {
            return;
        }

        const ov = getCurrentStepOverride();
        const colorMode = getVal('colormode', 'auto');
        const dark = isPreviewDark(colorMode);
        const preset = getVal('preset', 'saas');

        const primary = ov.primarycolor || getVal('primarycolor', '#6366f1');
        const headerbg = ov.headerbg || getVal('headerbg', '#ffffff');
        const titlecolor = ov.titlecolor || getVal('titlecolor', '#0f172a');
        const footerbg = getVal('footerbg', '#f8fafc');
        const textcolor = getVal('textcolor', '#475569');
        const radius = Math.max(
            0,
            Math.min(48, parseInt(getVal('borderradius', '20'), 10) || 0)
        );

        const accentstyle = getVal('accentstyle', 'gradient_animated');
        const c1 = getVal('accentcolor1', '#6366f1');
        const c2 = getVal('accentcolor2', '#8b5cf6');
        const c3 = getVal('accentcolor3', '#0ea5e9');
        const ah = Math.max(
            0,
            Math.min(20, parseInt(getVal('accentheight', '5'), 10) || 0)
        );
        const spotColor = ov.spotlightcolor ||
            getVal('spotlightcolor', '#6366f1');

        const iconcls = ov.icon || getVal('headericon', 'fa fa-star');
        const nextbg = getVal('nextbtnbg', '#6366f1');
        const nexttxt = getVal('nextbtntext', '#ffffff');
        const endbg = getVal('endbtnbg', '#ffffff');
        const endtxt = getVal('endbtntext', '#475569');
        const btnrad = Math.max(
            0,
            Math.min(30, parseInt(getVal('btnradius', '10'), 10) || 0)
        );

        const bdMode = getVal('backdropmode', 'blur');
        const bdColor = getVal('backdropcolor', '#0f172a');
        const bdOpacity = Math.max(
            0,
            Math.min(95, parseInt(getVal('backdropopacity', '58'), 10) || 0)
        );
        const bdBlur = Math.max(
            0,
            Math.min(20, parseInt(getVal('backdropblur', '4'), 10) || 0)
        );

        document.getElementById('lx-val-borderradius').textContent =
            radius + 'px';
        document.getElementById('lx-val-btnradius').textContent =
            btnrad + 'px';
        document.getElementById('lx-val-accentheight').textContent =
            ah + 'px';
        document.getElementById('lx-val-backdropopacity').textContent =
            bdOpacity + '%';
        document.getElementById('lx-val-backdropblur').textContent =
            bdBlur + 'px';

        const stageBadge = document.getElementById('lx-stage-badge');
        const prevBody = document.getElementById('lx-prev-body');
        const prevBadge = document.getElementById('lx-prev-badge');

        if (activeMode === 'tours' && selectedStep) {
            if (stageBadge) {
                stageBadge.textContent =
                    `Step ${selectedStepIndex + 1} of ${selectedTourTotal} Preview`;
            }
            prevBody.innerHTML = selectedStep.content ||
                '<em>Empty step content</em>';
            if (prevBadge) {
                prevBadge.textContent =
                    `${selectedStepIndex + 1} / ${selectedTourTotal}`;
            }
        } else {
            if (stageBadge) {
                stageBadge.textContent = 'Live Interactive Preview';
            }
            prevBody.textContent = defaultSampleBody;
            if (prevBadge) {
                prevBadge.textContent = '2 / 3';
            }
        }

        const targetMock = document.getElementById('lx-stage-target-mock');
        if (targetMock) {
            targetMock.style.background = dark ? '#1e293b' : '#ffffff';
            targetMock.style.color = dark ? '#f8fafc' : '#0f172a';
            targetMock.style.border = '2px solid ' + spotColor;
            targetMock.style.boxShadow =
                '0 0 0 4px ' + hexToRgba(spotColor, 0.28);
        }

        const stageBd = document.getElementById('lx-stage-backdrop');
        if (stageBd) {
            if (bdMode === 'none' || bdMode === 'transparent') {
                stageBd.style.background = 'transparent';
                stageBd.style.backdropFilter = 'none';
                stageBd.style.webkitBackdropFilter = 'none';
            } else if (bdMode === 'solid') {
                stageBd.style.background = hexToRgba(bdColor, bdOpacity / 100);
                stageBd.style.backdropFilter = 'none';
                stageBd.style.webkitBackdropFilter = 'none';
            } else {
                stageBd.style.background = hexToRgba(bdColor, bdOpacity / 100);
                stageBd.style.backdropFilter = `blur(${bdBlur}px)`;
                stageBd.style.webkitBackdropFilter = `blur(${bdBlur}px)`;
            }
        }

        card.style.borderRadius = radius + 'px';
        if (dark) {
            card.style.background = preset === 'glass' ?
                'rgba(15, 23, 42, 0.8)' : 'rgba(15, 23, 42, 0.95)';
            card.style.backdropFilter = preset === 'minimal' ?
                'none' : 'blur(18px)';
            card.style.borderColor = 'rgba(148, 163, 184, 0.2)';
            card.style.boxShadow = '0 25px 50px -12px rgba(0, 0, 0, 0.6)';
        } else if (preset === 'glass') {
            card.style.background = 'rgba(255, 255, 255, 0.78)';
            card.style.backdropFilter = 'blur(20px)';
            card.style.borderColor = 'rgba(15, 23, 42, 0.08)';
            card.style.boxShadow =
                '0 24px 48px -12px rgba(15, 23, 42, 0.22)';
        } else if (preset === 'minimal') {
            card.style.background = '#ffffff';
            card.style.backdropFilter = 'none';
            card.style.borderColor = 'rgba(15, 23, 42, 0.08)';
            card.style.boxShadow =
                '0 10px 25px rgba(15, 23, 42, 0.12)';
        } else {
            card.style.background = 'rgba(255, 255, 255, 0.96)';
            card.style.backdropFilter = 'blur(16px)';
            card.style.borderColor = 'rgba(15, 23, 42, 0.08)';
            card.style.boxShadow =
                '0 25px 50px -12px rgba(15, 23, 42, 0.25)';
        }

        const acc = document.getElementById('lx-prev-accent');
        if (accentstyle === 'hidden' || ah === 0) {
            acc.style.display = 'none';
        } else if (accentstyle === 'solid') {
            acc.style.display = 'block';
            acc.style.height = ah + 'px';
            acc.style.backgroundImage = 'none';
            acc.style.backgroundColor = c1;
            acc.style.animation = 'none';
        } else if (accentstyle === 'gradient_static') {
            acc.style.display = 'block';
            acc.style.height = ah + 'px';
            acc.style.backgroundImage =
                `linear-gradient(90deg,${c1},${c2},${c3})`;
            acc.style.backgroundSize = '100% 100%';
            acc.style.animation = 'none';
        } else {
            acc.style.display = 'block';
            acc.style.height = ah + 'px';
            acc.style.backgroundImage =
                `linear-gradient(90deg,${c1},${c2},${c3},${c1})`;
            acc.style.backgroundSize = '300% 100%';
            acc.style.animation = 'lxTourGradientFlow 3s ease infinite';
        }

        document.getElementById('lx-prev-header').style.background =
            (dark && !ov.headerbg) ? '#1e293b' : headerbg;
        document.getElementById('lx-prev-title').style.color =
            (dark && !ov.titlecolor) ? '#f8fafc' : titlecolor;
        document.getElementById('lx-prev-close').style.color =
            (dark && !ov.titlecolor) ? '#f8fafc' : titlecolor;

        const iconBox = document.getElementById('lx-prev-icon-box');
        iconBox.style.color = primary;
        iconBox.style.background =
            `color-mix(in srgb, ${primary} 15%, transparent)`;
        document.getElementById('lx-prev-icon').className = iconcls;

        prevBody.style.color = dark ? '#e2e8f0' : textcolor;
        document.getElementById('lx-prev-footer').style.background =
            dark ? '#0b1120' : footerbg;

        const btnNext = document.getElementById('lx-prev-btn-next');
        btnNext.style.background = nextbg;
        btnNext.style.color = nexttxt;
        btnNext.style.borderRadius = btnrad + 'px';

        const btnEnd = document.getElementById('lx-prev-btn-end');
        btnEnd.style.background = dark ? '#1e293b' : endbg;
        btnEnd.style.color = dark ? '#e2e8f0' : endtxt;
        btnEnd.style.borderRadius = btnrad + 'px';
        btnEnd.style.border = dark ?
            '1px solid rgba(148,163,184,0.24)' :
            '1px solid rgba(15,23,42,0.14)';
    };

    /**
     * Render list of tours in settings tab.
     */
    const renderToursList = () => {
        const container = document.getElementById('lx-tours-list');
        if (!container) {
            return;
        }
        container.innerHTML = '';

        const filtered = toursData.filter((t) => {
            if (activeFilter === 'active') {
                return t.enabled;
            }
            if (activeFilter === 'inactive') {
                return !t.enabled;
            }
            return true;
        });

        if (filtered.length === 0) {
            container.innerHTML =
                '<div class="lx-empty-state">No tours found.</div>';
            return;
        }

        filtered.forEach((tour) => {
            const item = document.createElement('button');
            item.type = 'button';
            item.className =
                'lx-tour-item' + (selectedTourId === tour.id ? ' active' : '');
            item.innerHTML =
                '<div class="lx-tour-item-main">' +
                '<span class="lx-tour-name"></span>' +
                `<span class="lx-tour-meta">${tour.steps.length} steps</span>` +
                '</div>' +
                `<span class="lx-status-pill ` +
                `${tour.enabled ? 'is-active' : 'is-inactive'}">` +
                `${tour.enabled ? 'Active' : 'Inactive'}</span>`;
            item.querySelector('.lx-tour-name').textContent = tour.name;
            item.addEventListener('click', () => {
                selectedTourId = tour.id;
                selectedStep = null;
                renderToursList();
                renderStepsList(tour);
            });
            container.appendChild(item);
        });
    };

    /**
     * Populate step editor fields.
     */
    const populateStepEditor = () => {
        const editor = document.getElementById('lx-step-editor');
        if (!editor || !selectedStep) {
            if (editor) {
                editor.style.display = 'none';
            }
            return;
        }

        editor.style.display = 'flex';
        const label = document.getElementById('lx-step-editor-label');
        if (label) {
            label.textContent =
                `Step ${selectedStepIndex + 1}: ` +
                `${selectedStep.rawtitle || 'Untitled'}`;
        }

        const ov = stepOverrides[String(selectedStep.id)] || {};
        const props = [
            'icon',
            'primarycolor',
            'headerbg',
            'titlecolor',
            'spotlightcolor',
            'titleanim',
            'bodyanim',
        ];
        props.forEach((key) => {
            const input = document.querySelector(`[data-step-bind="${key}"]`);
            const picker = document.querySelector(`[data-step-color="${key}"]`);
            if (input) {
                input.value = ov[key] || '';
            }
            if (picker) {
                picker.value = normHex(
                    ov[key] || getVal(key, '#6366f1'),
                    '#6366f1'
                );
            }
        });
    };

    /**
     * Render list of steps for selected tour.
     *
     * @param {Object} tour
     */
    const renderStepsList = (tour) => {
        const section = document.getElementById('lx-steps-section');
        const list = document.getElementById('lx-steps-list');
        if (!section || !list) {
            return;
        }

        section.style.display = 'flex';
        list.innerHTML = '';

        if (!tour.steps || tour.steps.length === 0) {
            list.innerHTML =
                '<div class="lx-empty-state">No steps in this tour.</div>';
            populateStepEditor();
            return;
        }

        selectedTourTotal = tour.steps.length;

        tour.steps.forEach((step, index) => {
            const hasCustom = !!stepOverrides[String(step.id)];
            const btn = document.createElement('button');
            btn.type = 'button';
            btn.className =
                'lx-step-item' +
                (selectedStep && selectedStep.id === step.id ? ' active' : '');
            btn.innerHTML =
                `<span class="lx-step-num">${index + 1}</span>` +
                '<span class="lx-step-title-txt"></span>' +
                (hasCustom ?
                    '<span class="lx-custom-dot" title="Customized"></span>' :
                    '');
            btn.querySelector('.lx-step-title-txt').textContent =
                step.rawtitle || 'Untitled Step';
            btn.addEventListener('click', () => {
                selectedStep = step;
                selectedStepIndex = index;
                renderStepsList(tour);
                populateStepEditor();
                renderPreview();
                playPreviewAnim();
            });
            list.appendChild(btn);
        });

        if (!selectedStep && tour.steps.length > 0) {
            selectedStep = tour.steps[0];
            selectedStepIndex = 0;
            renderStepsList(tour);
            populateStepEditor();
            renderPreview();
            playPreviewAnim();
        }
    };

    /**
     * Update specific step override field.
     *
     * @param {string} key
     * @param {string} value
     */
    const updateStepField = (key, value) => {
        if (!selectedStep) {
            return;
        }
        const sid = String(selectedStep.id);
        if (!stepOverrides[sid]) {
            stepOverrides[sid] = {title: selectedStep.rawtitle};
        }
        stepOverrides[sid].title = selectedStep.rawtitle;

        if (value === '') {
            delete stepOverrides[sid][key];
        } else {
            stepOverrides[sid][key] = value;
        }

        const keys = Object.keys(stepOverrides[sid]).filter((k) => {
            return k !== 'title';
        });
        if (keys.length === 0) {
            delete stepOverrides[sid];
        }

        saveStepOverridesToInput();
        renderPreview();
        if (key === 'titleanim' || key === 'bodyanim') {
            playPreviewAnim();
        }
    };

    const shell = document.querySelector('.lx-studio-shell');
    if (!shell) {
        return;
    }

    try {
        toursData = JSON.parse(shell.getAttribute('data-tours') || '[]');
    } catch (e) {
        toursData = [];
    }

    const realOverrides = mInput('stepoverrides');
    if (realOverrides) {
        const ovRow = realOverrides.closest('.form-item');
        if (ovRow) {
            ovRow.style.display = 'none';
        }
        try {
            stepOverrides = JSON.parse(realOverrides.value || '{}') || {};
        } catch (e) {
            stepOverrides = {};
        }
    }

    fields.forEach((name) => {
        const real = mInput(name);
        if (!real) {
            return;
        }
        const row = real.closest('.form-item');
        if (row) {
            row.style.display = 'none';
        }

        const bound = document.querySelector(`[data-bind="${name}"]`);
        const colorPicker = document.querySelector(`[data-color="${name}"]`);

        if (bound) {
            bound.value = real.value;
            bound.addEventListener('input', () => {
                real.value = bound.value;
                if (colorPicker) {
                    colorPicker.value = normHex(bound.value, colorPicker.value);
                }
                real.dispatchEvent(new Event('change', {bubbles: true}));
                renderPreview();
                if (
                    name === 'titleanim' ||
                    name === 'bodyanim' ||
                    name === 'typingspeed'
                ) {
                    playPreviewAnim();
                }
            });
            bound.addEventListener('change', () => {
                real.value = bound.value;
                renderPreview();
                if (
                    name === 'titleanim' ||
                    name === 'bodyanim' ||
                    name === 'typingspeed'
                ) {
                    playPreviewAnim();
                }
            });
        }

        if (colorPicker) {
            colorPicker.value = normHex(real.value, '#6366f1');
            colorPicker.addEventListener('input', () => {
                real.value = colorPicker.value;
                if (bound) {
                    bound.value = colorPicker.value;
                }
                real.dispatchEvent(new Event('change', {bubbles: true}));
                renderPreview();
            });
        }
    });

    document.querySelectorAll('.lx-header-tab').forEach((btn) => {
        btn.addEventListener('click', () => {
            activeMode = btn.dataset.mode;
            document.querySelectorAll('.lx-header-tab').forEach((b) => {
                b.classList.remove('active');
            });
            document.querySelectorAll('.lx-mode-pane').forEach((p) => {
                p.classList.remove('active');
            });
            btn.classList.add('active');
            const pane = document.querySelector(
                `.lx-mode-pane[data-mode-pane="${activeMode}"]`
            );
            if (pane) {
                pane.classList.add('active');
            }
            if (activeMode === 'tours') {
                renderToursList();
            }
            renderPreview();
            playPreviewAnim();
        });
    });

    document.querySelectorAll('.lx-filter-btn').forEach((btn) => {
        btn.addEventListener('click', () => {
            activeFilter = btn.dataset.tourFilter;
            document.querySelectorAll('.lx-filter-btn').forEach((b) => {
                b.classList.remove('active');
            });
            btn.classList.add('active');
            renderToursList();
        });
    });

    document.querySelectorAll('[data-step-bind]').forEach((input) => {
        const key = input.getAttribute('data-step-bind');
        const picker = document.querySelector(`[data-step-color="${key}"]`);
        input.addEventListener('input', () => {
            if (picker && /^#[0-9a-fA-F]{6}$/.test(input.value)) {
                picker.value = input.value;
            }
            updateStepField(key, input.value.trim());
        });
        input.addEventListener('change', () => {
            updateStepField(key, input.value.trim());
        });
    });

    document.querySelectorAll('[data-step-color]').forEach((picker) => {
        const key = picker.getAttribute('data-step-color');
        const input = document.querySelector(`[data-step-bind="${key}"]`);
        picker.addEventListener('input', () => {
            if (input) {
                input.value = picker.value;
            }
            updateStepField(key, picker.value);
        });
    });

    document.querySelectorAll('[data-step-icon]').forEach((chip) => {
        chip.addEventListener('click', () => {
            const ic = chip.getAttribute('data-step-icon');
            const input = document.querySelector('[data-step-bind="icon"]');
            if (input) {
                input.value = ic;
            }
            updateStepField('icon', ic);
        });
    });

    const stepResetBtn = document.getElementById('lx-step-reset');
    if (stepResetBtn) {
        stepResetBtn.addEventListener('click', () => {
            if (!selectedStep) {
                return;
            }
            delete stepOverrides[String(selectedStep.id)];
            saveStepOverridesToInput();
            populateStepEditor();
            renderPreview();
            playPreviewAnim();
        });
    }

    document.querySelectorAll('.lx-studio-tab').forEach((btn) => {
        btn.addEventListener('click', () => {
            document.querySelectorAll('.lx-studio-tab').forEach((b) => {
                b.classList.remove('active');
            });
            document.querySelectorAll('.lx-studio-pane').forEach((p) => {
                p.classList.remove('active');
            });
            btn.classList.add('active');
            const pane = document.querySelector(
                `.lx-studio-pane[data-pane="${btn.dataset.tab}"]`
            );
            if (pane) {
                pane.classList.add('active');
            }
        });
    });

    document.querySelectorAll('[data-pick-icon]').forEach((chip) => {
        chip.addEventListener('click', () => {
            const ic = chip.getAttribute('data-pick-icon');
            const bound = document.querySelector('[data-bind="headericon"]');
            const real = mInput('headericon');
            if (bound) {
                bound.value = ic;
            }
            if (real) {
                real.value = ic;
                real.dispatchEvent(new Event('change', {bubbles: true}));
            }
            renderPreview();
        });
    });

    const resetBtn = document.getElementById('lx-reset-defaults');
    if (resetBtn) {
        resetBtn.addEventListener('click', () => {
            applyValues(defaults);
        });
    }

    const matchBtn = document.getElementById('lx-match-platform');
    if (matchBtn) {
        matchBtn.addEventListener('click', () => {
            const brand = detectPlatformBrandColor();
            applyValues({
                primarycolor: brand,
                nextbtnbg: brand,
                accentcolor1: brand,
                spotlightcolor: brand,
            });
        });
    }

    const replayBtn = document.getElementById('lx-replay-anim');
    if (replayBtn) {
        replayBtn.addEventListener('click', playPreviewAnim);
    }

    renderPreview();
    playPreviewAnim();
};