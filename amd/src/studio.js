(function() {
    var defaults = {
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
        bodyanim: 'fade_up'
    };

    var fields = Object.keys(defaults);
    var prevTimer = null;
    var defaultSampleTitle = 'Welcome to Your Course';
    var defaultSampleBody = 'Adjust colors, match platform branding, or customize individual tour steps live.';
    var toursData = [];
    var stepOverrides = {};
    var activeMode = 'global';
    var activeFilter = 'all';
    var selectedTourId = null;
    var selectedStep = null;
    var selectedStepIndex = 0;
    var selectedTourTotal = 0;

    function mInput(name) {
        return document.getElementById('id_s_local_tourstyler_' + name);
    }

    function getVal(name, fallback) {
        var el = mInput(name);
        return el && el.value !== '' ? el.value : fallback;
    }

    function normHex(value, fallback) {
        return /^#[0-9a-fA-F]{6}$/.test(value) ? value : fallback;
    }

    function rgbToHex(rgb) {
        var match = (rgb || '').match(/rgba?\((\d+),\s*(\d+),\s*(\d+)/);
        if (!match) {
            return null;
        }
        var hex = function(x) {
            return ('0' + parseInt(x, 10).toString(16)).slice(-2);
        };
        return '#' + hex(match[1]) + hex(match[2]) + hex(match[3]);
    }

    function detectPlatformBrandColor() {
        var rootStyles = getComputedStyle(document.documentElement);
        var vars = ['--primary', '--bs-primary', '--brand-primary', '--theme-primary'];
        for (var i = 0; i < vars.length; i++) {
            var v = rootStyles.getPropertyValue(vars[i]).trim();
            if (/^#[0-9a-fA-F]{6}$/.test(v)) {
                return v;
            }
            var parsed = rgbToHex(v);
            if (parsed) {
                return parsed;
            }
        }
        var probe = document.querySelector('.btn-primary, .nav-link.active');
        if (probe) {
            var bg = getComputedStyle(probe).backgroundColor;
            var hex = rgbToHex(bg);
            if (hex && hex !== '#000000' && hex !== '#ffffff') {
                return hex;
            }
        }
        return '#6366f1';
    }

    function hexToRgba(hex, alpha) {
        var clean = normHex(hex, '#0f172a').substring(1);
        var r = parseInt(clean.substring(0, 2), 16);
        var g = parseInt(clean.substring(2, 4), 16);
        var b = parseInt(clean.substring(4, 6), 16);
        return 'rgba(' + r + ',' + g + ',' + b + ',' + alpha + ')';
    }

    function isPreviewDark(mode) {
        if (mode === 'dark') {
            return true;
        }
        if (mode === 'light') {
            return false;
        }
        return window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches;
    }

    function saveStepOverridesToInput() {
        var real = mInput('stepoverrides');
        if (real) {
            real.value = JSON.stringify(stepOverrides);
            real.dispatchEvent(new Event('change', {bubbles: true}));
        }
    }

    function getCurrentStepOverride() {
        if (activeMode !== 'tours' || !selectedStep) {
            return {};
        }
        return stepOverrides[String(selectedStep.id)] || {};
    }

    function applyValues(map) {
        Object.keys(map).forEach(function(name) {
            var real = mInput(name);
            var bound = document.querySelector('[data-bind="' + name + '"]');
            var picker = document.querySelector('[data-color="' + name + '"]');
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
    }

    function playPreviewAnim() {
        var ov = getCurrentStepOverride();
        var tAnim = ov.titleanim || getVal('titleanim', 'typing');
        var bAnim = ov.bodyanim || getVal('bodyanim', 'fade_up');
        var speed = Math.max(15, Math.min(90, parseInt(getVal('typingspeed', '35'), 10) || 35));
        var titleEl = document.getElementById('lx-prev-title');
        var bodyEl = document.getElementById('lx-prev-body');
        if (!titleEl || !bodyEl) {
            return;
        }

        var activeTitle = (activeMode === 'tours' && selectedStep) ? (selectedStep.rawtitle || 'Untitled Step') : defaultSampleTitle;

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
            var idx = 0;
            prevTimer = setInterval(function() {
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
    }

    function renderPreview() {
        var card = document.getElementById('lx-prev-card');
        if (!card) {
            return;
        }

        var ov = getCurrentStepOverride();
        var colorMode = getVal('colormode', 'auto');
        var dark = isPreviewDark(colorMode);
        var preset = getVal('preset', 'saas');

        var primary = ov.primarycolor || getVal('primarycolor', '#6366f1');
        var headerbg = ov.headerbg || getVal('headerbg', '#ffffff');
        var titlecolor = ov.titlecolor || getVal('titlecolor', '#0f172a');
        var footerbg = getVal('footerbg', '#f8fafc');
        var textcolor = getVal('textcolor', '#475569');
        var radius = Math.max(0, Math.min(48, parseInt(getVal('borderradius', '20'), 10) || 0));

        var accentstyle = getVal('accentstyle', 'gradient_animated');
        var c1 = getVal('accentcolor1', '#6366f1');
        var c2 = getVal('accentcolor2', '#8b5cf6');
        var c3 = getVal('accentcolor3', '#0ea5e9');
        var ah = Math.max(0, Math.min(20, parseInt(getVal('accentheight', '5'), 10) || 0));
        var spotColor = ov.spotlightcolor || getVal('spotlightcolor', '#6366f1');

        var iconcls = ov.icon || getVal('headericon', 'fa fa-star');
        var nextbg = getVal('nextbtnbg', '#6366f1');
        var nexttxt = getVal('nextbtntext', '#ffffff');
        var endbg = getVal('endbtnbg', '#ffffff');
        var endtxt = getVal('endbtntext', '#475569');
        var btnrad = Math.max(0, Math.min(30, parseInt(getVal('btnradius', '10'), 10) || 0));

        var bdMode = getVal('backdropmode', 'blur');
        var bdColor = getVal('backdropcolor', '#0f172a');
        var bdOpacity = Math.max(0, Math.min(95, parseInt(getVal('backdropopacity', '58'), 10) || 0));
        var bdBlur = Math.max(0, Math.min(20, parseInt(getVal('backdropblur', '4'), 10) || 0));
        var speed = Math.max(15, Math.min(90, parseInt(getVal('typingspeed', '35'), 10) || 35));

        document.getElementById('lx-val-borderradius').textContent = radius + 'px';
        document.getElementById('lx-val-btnradius').textContent = btnrad + 'px';
        document.getElementById('lx-val-accentheight').textContent = ah + 'px';
        document.getElementById('lx-val-backdropopacity').textContent = bdOpacity + '%';
        document.getElementById('lx-val-backdropblur').textContent = bdBlur + 'px';
        document.getElementById('lx-val-typingspeed').textContent = speed + 'ms';

        var stageBadge = document.getElementById('lx-stage-badge');
        var prevBody = document.getElementById('lx-prev-body');
        var prevBadge = document.getElementById('lx-prev-badge');

        if (activeMode === 'tours' && selectedStep) {
            if (stageBadge) {
                stageBadge.textContent = 'Step ' + (selectedStepIndex + 1) + ' of ' + selectedTourTotal + ' Preview';
            }
            prevBody.innerHTML = selectedStep.content || '<em>Empty step content</em>';
            if (prevBadge) {
                prevBadge.textContent = (selectedStepIndex + 1) + ' / ' + selectedTourTotal;
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

        var targetMock = document.getElementById('lx-stage-target-mock');
        if (targetMock) {
            targetMock.style.background = dark ? '#1e293b' : '#ffffff';
            targetMock.style.color = dark ? '#f8fafc' : '#0f172a';
            targetMock.style.border = '2px solid ' + spotColor;
            targetMock.style.boxShadow = '0 0 0 4px ' + hexToRgba(spotColor, 0.28);
        }

        var stageBd = document.getElementById('lx-stage-backdrop');
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
                stageBd.style.backdropFilter = 'blur(' + bdBlur + 'px)';
                stageBd.style.webkitBackdropFilter = 'blur(' + bdBlur + 'px)';
            }
        }

        card.style.borderRadius = radius + 'px';
        if (dark) {
            card.style.background = preset === 'glass' ? 'rgba(15, 23, 42, 0.8)' : 'rgba(15, 23, 42, 0.95)';
            card.style.backdropFilter = preset === 'minimal' ? 'none' : 'blur(18px)';
            card.style.borderColor = 'rgba(148, 163, 184, 0.2)';
            card.style.boxShadow = '0 25px 50px -12px rgba(0, 0, 0, 0.6)';
        } else if (preset === 'glass') {
            card.style.background = 'rgba(255, 255, 255, 0.78)';
            card.style.backdropFilter = 'blur(20px)';
            card.style.borderColor = 'rgba(15, 23, 42, 0.08)';
            card.style.boxShadow = '0 24px 48px -12px rgba(15, 23, 42, 0.22)';
        } else if (preset === 'minimal') {
            card.style.background = '#ffffff';
            card.style.backdropFilter = 'none';
            card.style.borderColor = 'rgba(15, 23, 42, 0.08)';
            card.style.boxShadow = '0 10px 25px rgba(15, 23, 42, 0.12)';
        } else {
            card.style.background = 'rgba(255, 255, 255, 0.96)';
            card.style.backdropFilter = 'blur(16px)';
            card.style.borderColor = 'rgba(15, 23, 42, 0.08)';
            card.style.boxShadow = '0 25px 50px -12px rgba(15, 23, 42, 0.25)';
        }

        var acc = document.getElementById('lx-prev-accent');
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
            acc.style.backgroundImage = 'linear-gradient(90deg,' + c1 + ',' + c2 + ',' + c3 + ')';
            acc.style.backgroundSize = '100% 100%';
            acc.style.animation = 'none';
        } else {
            acc.style.display = 'block';
            acc.style.height = ah + 'px';
            acc.style.backgroundImage = 'linear-gradient(90deg,' + c1 + ',' + c2 + ',' + c3 + ',' + c1 + ')';
            acc.style.backgroundSize = '300% 100%';
            acc.style.animation = 'lxTourGradientFlow 3s ease infinite';
        }

        document.getElementById('lx-prev-header').style.background = (dark && !ov.headerbg) ? '#1e293b' : headerbg;
        document.getElementById('lx-prev-title').style.color = (dark && !ov.titlecolor) ? '#f8fafc' : titlecolor;
        document.getElementById('lx-prev-close').style.color = (dark && !ov.titlecolor) ? '#f8fafc' : titlecolor;

        var iconBox = document.getElementById('lx-prev-icon-box');
        iconBox.style.color = primary;
        iconBox.style.background = 'color-mix(in srgb, ' + primary + ' 15%, transparent)';
        document.getElementById('lx-prev-icon').className = iconcls;

        prevBody.style.color = dark ? '#e2e8f0' : textcolor;
        document.getElementById('lx-prev-footer').style.background = dark ? '#0b1120' : footerbg;

        var btnNext = document.getElementById('lx-prev-btn-next');
        btnNext.style.background = nextbg;
        btnNext.style.color = nexttxt;
        btnNext.style.borderRadius = btnrad + 'px';

        var btnEnd = document.getElementById('lx-prev-btn-end');
        btnEnd.style.background = dark ? '#1e293b' : endbg;
        btnEnd.style.color = dark ? '#e2e8f0' : endtxt;
        btnEnd.style.borderRadius = btnrad + 'px';
        btnEnd.style.border = dark ? '1px solid rgba(148,163,184,0.24)' : '1px solid rgba(15,23,42,0.14)';
    }

    function renderToursList() {
        var container = document.getElementById('lx-tours-list');
        if (!container) {
            return;
        }
        container.innerHTML = '';

        var filtered = toursData.filter(function(t) {
            if (activeFilter === 'active') {
                return t.enabled;
            }
            if (activeFilter === 'inactive') {
                return !t.enabled;
            }
            return true;
        });

        if (filtered.length === 0) {
            container.innerHTML = '<div class="lx-empty-state">No tours found in this filter.</div>';
            return;
        }

        filtered.forEach(function(tour) {
            var item = document.createElement('button');
            item.type = 'button';
            item.className = 'lx-tour-item' + (selectedTourId === tour.id ? ' active' : '');
            item.innerHTML = '<div class="lx-tour-item-main">'
                + '<span class="lx-tour-name"></span>'
                + '<span class="lx-tour-meta">' + tour.steps.length + ' steps</span>'
                + '</div>'
                + '<span class="lx-status-pill ' + (tour.enabled ? 'is-active' : 'is-inactive') + '">'
                + (tour.enabled ? 'Active' : 'Inactive')
                + '</span>';
            item.querySelector('.lx-tour-name').textContent = tour.name;
            item.addEventListener('click', function() {
                selectedTourId = tour.id;
                selectedStep = null;
                renderToursList();
                renderStepsList(tour);
            });
            container.appendChild(item);
        });
    }

    function populateStepEditor() {
        var editor = document.getElementById('lx-step-editor');
        if (!editor || !selectedStep) {
            if (editor) {
                editor.style.display = 'none';
            }
            return;
        }

        editor.style.display = 'flex';
        var label = document.getElementById('lx-step-editor-label');
        if (label) {
            label.textContent = 'Step ' + (selectedStepIndex + 1) + ': ' + (selectedStep.rawtitle || 'Untitled');
        }

        var ov = stepOverrides[String(selectedStep.id)] || {};
        ['icon', 'primarycolor', 'headerbg', 'titlecolor', 'spotlightcolor', 'titleanim', 'bodyanim'].forEach(function(key) {
            var input = document.querySelector('[data-step-bind="' + key + '"]');
            var picker = document.querySelector('[data-step-color="' + key + '"]');
            if (input) {
                input.value = ov[key] || '';
            }
            if (picker) {
                picker.value = normHex(ov[key] || getVal(key, '#6366f1'), '#6366f1');
            }
        });
    }

    function renderStepsList(tour) {
        var section = document.getElementById('lx-steps-section');
        var list = document.getElementById('lx-steps-list');
        if (!section || !list) {
            return;
        }

        section.style.display = 'flex';
        list.innerHTML = '';

        if (!tour.steps || tour.steps.length === 0) {
            list.innerHTML = '<div class="lx-empty-state">This tour has no steps yet.</div>';
            populateStepEditor();
            return;
        }

        selectedTourTotal = tour.steps.length;

        tour.steps.forEach(function(step, index) {
            var hasCustom = !!stepOverrides[String(step.id)];
            var btn = document.createElement('button');
            btn.type = 'button';
            btn.className = 'lx-step-item' + (selectedStep && selectedStep.id === step.id ? ' active' : '');
            btn.innerHTML = '<span class="lx-step-num">' + (index + 1) + '</span>'
                + '<span class="lx-step-title-txt"></span>'
                + (hasCustom ? '<span class="lx-custom-dot" title="Customized"></span>' : '');
            btn.querySelector('.lx-step-title-txt').textContent = step.rawtitle || 'Untitled Step';
            btn.addEventListener('click', function() {
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
    }

    function updateStepField(key, value) {
        if (!selectedStep) {
            return;
        }
        var sid = String(selectedStep.id);
        if (!stepOverrides[sid]) {
            stepOverrides[sid] = {title: selectedStep.rawtitle};
        }
        stepOverrides[sid].title = selectedStep.rawtitle;

        if (value === '') {
            delete stepOverrides[sid][key];
        } else {
            stepOverrides[sid][key] = value;
        }

        var keys = Object.keys(stepOverrides[sid]).filter(function(k) {
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
    }

    function initStudio() {
        var shell = document.querySelector('.lx-studio-shell');
        if (!shell) {
            return;
        }

        try {
            toursData = JSON.parse(shell.getAttribute('data-tours') || '[]');
        } catch (e) {
            toursData = [];
        }

        var realOverrides = mInput('stepoverrides');
        if (realOverrides) {
            var ovRow = realOverrides.closest('.form-item');
            if (ovRow) {
                ovRow.style.display = 'none';
            }
            try {
                stepOverrides = JSON.parse(realOverrides.value || '{}') || {};
            } catch (e) {
                stepOverrides = {};
            }
        }

        fields.forEach(function(name) {
            var real = mInput(name);
            if (!real) {
                return;
            }
            var row = real.closest('.form-item');
            if (row) {
                row.style.display = 'none';
            }

            var bound = document.querySelector('[data-bind="' + name + '"]');
            var colorPicker = document.querySelector('[data-color="' + name + '"]');

            if (bound) {
                bound.value = real.value;
                bound.addEventListener('input', function() {
                    real.value = bound.value;
                    if (colorPicker) {
                        colorPicker.value = normHex(bound.value, colorPicker.value);
                    }
                    real.dispatchEvent(new Event('change', {bubbles: true}));
                    renderPreview();
                    if (name === 'titleanim' || name === 'bodyanim' || name === 'typingspeed') {
                        playPreviewAnim();
                    }
                });
                bound.addEventListener('change', function() {
                    real.value = bound.value;
                    renderPreview();
                    if (name === 'titleanim' || name === 'bodyanim' || name === 'typingspeed') {
                        playPreviewAnim();
                    }
                });
            }

            if (colorPicker) {
                colorPicker.value = normHex(real.value, '#6366f1');
                colorPicker.addEventListener('input', function() {
                    real.value = colorPicker.value;
                    if (bound) {
                        bound.value = colorPicker.value;
                    }
                    real.dispatchEvent(new Event('change', {bubbles: true}));
                    renderPreview();
                });
            }
        });

        document.querySelectorAll('.lx-header-tab').forEach(function(btn) {
            btn.addEventListener('click', function() {
                activeMode = btn.dataset.mode;
                document.querySelectorAll('.lx-header-tab').forEach(function(b) {
                    b.classList.remove('active');
                });
                document.querySelectorAll('.lx-mode-pane').forEach(function(p) {
                    p.classList.remove('active');
                });
                btn.classList.add('active');
                var pane = document.querySelector('.lx-mode-pane[data-mode-pane="' + activeMode + '"]');
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

        document.querySelectorAll('.lx-filter-btn').forEach(function(btn) {
            btn.addEventListener('click', function() {
                activeFilter = btn.dataset.tourFilter;
                document.querySelectorAll('.lx-filter-btn').forEach(function(b) {
                    b.classList.remove('active');
                });
                btn.classList.add('active');
                renderToursList();
            });
        });

        document.querySelectorAll('[data-step-bind]').forEach(function(input) {
            var key = input.getAttribute('data-step-bind');
            var picker = document.querySelector('[data-step-color="' + key + '"]');
            input.addEventListener('input', function() {
                if (picker && /^#[0-9a-fA-F]{6}$/.test(input.value)) {
                    picker.value = input.value;
                }
                updateStepField(key, input.value.trim());
            });
            input.addEventListener('change', function() {
                updateStepField(key, input.value.trim());
            });
        });

        document.querySelectorAll('[data-step-color]').forEach(function(picker) {
            var key = picker.getAttribute('data-step-color');
            var input = document.querySelector('[data-step-bind="' + key + '"]');
            picker.addEventListener('input', function() {
                if (input) {
                    input.value = picker.value;
                }
                updateStepField(key, picker.value);
            });
        });

        document.querySelectorAll('[data-step-icon]').forEach(function(chip) {
            chip.addEventListener('click', function() {
                var ic = chip.getAttribute('data-step-icon');
                var input = document.querySelector('[data-step-bind="icon"]');
                if (input) {
                    input.value = ic;
                }
                updateStepField('icon', ic);
            });
        });

        var stepResetBtn = document.getElementById('lx-step-reset');
        if (stepResetBtn) {
            stepResetBtn.addEventListener('click', function() {
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

        document.querySelectorAll('.lx-studio-tab').forEach(function(btn) {
            btn.addEventListener('click', function() {
                document.querySelectorAll('.lx-studio-tab').forEach(function(b) {
                    b.classList.remove('active');
                });
                document.querySelectorAll('.lx-studio-pane').forEach(function(p) {
                    p.classList.remove('active');
                });
                btn.classList.add('active');
                var pane = document.querySelector('.lx-studio-pane[data-pane="' + btn.dataset.tab + '"]');
                if (pane) {
                    pane.classList.add('active');
                }
            });
        });

        document.querySelectorAll('[data-pick-icon]').forEach(function(chip) {
            chip.addEventListener('click', function() {
                var ic = chip.getAttribute('data-pick-icon');
                var bound = document.querySelector('[data-bind="headericon"]');
                var real = mInput('headericon');
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

        var resetBtn = document.getElementById('lx-reset-defaults');
        if (resetBtn) {
            resetBtn.addEventListener('click', function() {
                applyValues(defaults);
            });
        }

        var matchBtn = document.getElementById('lx-match-platform');
        if (matchBtn) {
            matchBtn.addEventListener('click', function() {
                var brand = detectPlatformBrandColor();
                applyValues({
                    primarycolor: brand,
                    nextbtnbg: brand,
                    accentcolor1: brand,
                    spotlightcolor: brand
                });
            });
        }

        var replayBtn = document.getElementById('lx-replay-anim');
        if (replayBtn) {
            replayBtn.addEventListener('click', playPreviewAnim);
        }

        renderPreview();
        playPreviewAnim();
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initStudio);
    } else {
        setTimeout(initStudio, 50);
    }
})();