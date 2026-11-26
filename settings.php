<?php
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
 * Plugin administration pages are defined here.
 *
 * @package    local_tourstyler
 * @copyright  2026 AbdelrhmanBoghdady support@learn-ix.com
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

if ($hassiteconfig) {
    $settings = new admin_settingpage('local_tourstyler', get_string('pluginname', 'local_tourstyler'));
    $ADMIN->add('appearance', $settings);

    if (optional_param('section', '', PARAM_ALPHANUMEXT) === 'local_tourstyler') {
        $PAGE->requires->js_call_amd('local_tourstyler/studio', 'init');
    }

    $settings->add(new admin_setting_configcheckbox(
        'local_tourstyler/enabled',
        get_string('enabled', 'local_tourstyler'),
        get_string('enabled_desc', 'local_tourstyler'),
        1
    ));

    $activetheme = $CFG->theme;
    $themedir = core_component::get_plugin_directory('theme', $activetheme);
    $templatepath = $themedir . '/templates/tool_usertours/tourstep.mustache';
    $isdeployed = file_exists($templatepath);

    $deployurl = new moodle_url('/local/tourstyler/deploy.php', [
        'action' => 'deploy',
        'sesskey' => sesskey(),
    ]);
    $removeurl = new moodle_url('/local/tourstyler/deploy.php', [
        'action' => 'remove',
        'sesskey' => sesskey(),
    ]);

    if ($isdeployed) {
        $alertclass = 'alert alert-success';
        $statushtml = html_writer::tag('p', get_string('status_deployed', 'local_tourstyler', $activetheme));
        $redeploybtn = html_writer::link(
            $deployurl,
            get_string('redeploybtn', 'local_tourstyler'),
            ['class' => 'btn btn-secondary me-2']
        );
        $removebtn = html_writer::link(
            $removeurl,
            get_string('removebtn', 'local_tourstyler'),
            ['class' => 'btn btn-outline-danger']
        );
        $buttonshtml = $redeploybtn . $removebtn;
    } else {
        $alertclass = 'alert alert-warning';
        $statushtml = html_writer::tag('p', get_string('status_not_deployed', 'local_tourstyler', $activetheme));
        $buttonshtml = html_writer::link(
            $deployurl,
            get_string('deploybtn', 'local_tourstyler'),
            ['class' => 'btn btn-primary']
        );
    }

    $deployhtml = html_writer::div($statushtml . $buttonshtml, $alertclass);

    $settings->add(new admin_setting_heading(
        'local_tourstyler/deployheading',
        get_string('deployheading', 'local_tourstyler'),
        $deployhtml
    ));

    $resolvetourstr = static function(string $value): string {
        $trimmed = trim($value);
        if ($trimmed === '') {
            return '';
        }
        if (class_exists('\tool_usertours\helper')) {
            return \tool_usertours\helper::get_string_from_input($trimmed);
        }
        return $trimmed;
    };

    $toursdata = [];
    $dbman = $DB->get_manager();
    if ($dbman->table_exists('tool_usertours_tours') && $dbman->table_exists('tool_usertours_steps')) {
        $tours = $DB->get_records('tool_usertours_tours', null, 'sortorder ASC, id ASC');
        $allsteps = $DB->get_records('tool_usertours_steps', null, 'tourid ASC, sortorder ASC, id ASC');
        $stepsbytour = [];
        foreach ($allsteps as $step) {
            $resolvedtitle = format_string($resolvetourstr((string)$step->title));
            $cleantitle = html_entity_decode(trim(strip_tags($resolvedtitle)), ENT_QUOTES, 'UTF-8');
            $resolvedcontent = $resolvetourstr((string)$step->content);
            $stepsbytour[$step->tourid][] = [
                'id' => (int)$step->id,
                'title' => $resolvedtitle,
                'rawtitle' => $cleantitle,
                'content' => format_text($resolvedcontent, $step->contentformat ?? FORMAT_HTML),
                'sortorder' => (int)$step->sortorder,
            ];
        }
        foreach ($tours as $tour) {
            $resolvedname = format_string($resolvetourstr((string)$tour->name));
            $toursdata[] = [
                'id' => (int)$tour->id,
                'name' => html_entity_decode(trim(strip_tags($resolvedname)), ENT_QUOTES, 'UTF-8'),
                'enabled' => !empty($tour->enabled),
                'pathmatch' => (string)$tour->pathmatch,
                'steps' => $stepsbytour[$tour->id] ?? [],
            ];
        }
    }
    $toursjson = htmlspecialchars(json_encode($toursdata), ENT_QUOTES, 'UTF-8');
    $tourscount = count($toursdata);

    $studiohtml = <<<HTML
<div class="lx-studio-shell" data-tours="{$toursjson}">
    <div class="lx-studio-header-tabs">
        <button type="button" class="lx-header-tab active" data-mode="global">
            <i class="fa fa-sliders"></i> Global Studio
        </button>
        <button type="button" class="lx-header-tab" data-mode="tours">
            <i class="fa fa-map-signs"></i> Tours &amp; Steps
            <span class="lx-tab-count">{$tourscount}</span>
        </button>
    </div>
    <div class="lx-studio-wrap">
        <div class="lx-studio-controls">
            <div class="lx-mode-pane active" data-mode-pane="global">
                <div class="lx-studio-quickbar">
                    <button type="button" id="lx-match-platform" class="lx-quick-btn">
                        <i class="fa fa-paint-brush"></i> Match Platform Colors
                    </button>
                    <button type="button" id="lx-reset-defaults" class="lx-quick-btn lx-btn-reset">
                        <i class="fa fa-undo"></i> Reset to Default
                    </button>
                </div>
                <div class="lx-studio-tabs">
                    <button type="button" class="lx-studio-tab active" data-tab="card">Card</button>
                    <button type="button" class="lx-studio-tab" data-tab="buttons">Buttons</button>
                    <button type="button" class="lx-studio-tab" data-tab="accent">Accent</button>
                    <button type="button" class="lx-studio-tab" data-tab="backdrop">Backdrop &amp; Focus</button>
                    <button type="button" class="lx-studio-tab" data-tab="anim">Animations</button>
                </div>
                <div class="lx-studio-pane active" data-pane="card">
                    <div class="lx-ctrl-grid">
                        <div class="lx-ctrl-group">
                            <label class="lx-ctrl-label">Color Mode</label>
                            <select class="lx-select" data-bind="colormode">
                                <option value="auto">Auto (System / Theme)</option>
                                <option value="light">Light Mode</option>
                                <option value="dark">Dark Mode</option>
                            </select>
                        </div>
                        <div class="lx-ctrl-group">
                            <label class="lx-ctrl-label">Design Preset</label>
                            <select class="lx-select" data-bind="preset">
                                <option value="saas">Modern SaaS</option>
                                <option value="glass">Glassmorphism</option>
                                <option value="minimal">Minimal Clean</option>
                            </select>
                        </div>
                        <div class="lx-ctrl-group">
                            <label class="lx-ctrl-label">Primary &amp; Icon</label>
                            <div class="lx-color-row">
                                <input type="color" data-color="primarycolor">
                                <input type="text" data-bind="primarycolor">
                            </div>
                        </div>
                        <div class="lx-ctrl-group">
                            <label class="lx-ctrl-label">Header Background</label>
                            <div class="lx-color-row">
                                <input type="color" data-color="headerbg">
                                <input type="text" data-bind="headerbg">
                            </div>
                        </div>
                        <div class="lx-ctrl-group">
                            <label class="lx-ctrl-label">Title Text Color</label>
                            <div class="lx-color-row">
                                <input type="color" data-color="titlecolor">
                                <input type="text" data-bind="titlecolor">
                            </div>
                        </div>
                        <div class="lx-ctrl-group">
                            <label class="lx-ctrl-label">Body Text Color</label>
                            <div class="lx-color-row">
                                <input type="color" data-color="textcolor">
                                <input type="text" data-bind="textcolor">
                            </div>
                        </div>
                        <div class="lx-ctrl-group">
                            <label class="lx-ctrl-label">Footer Background</label>
                            <div class="lx-color-row">
                                <input type="color" data-color="footerbg">
                                <input type="text" data-bind="footerbg">
                            </div>
                        </div>
                        <div class="lx-ctrl-group">
                            <label class="lx-ctrl-label">
                                Card Radius
                                <span class="lx-ctrl-val" id="lx-val-borderradius">20px</span>
                            </label>
                            <input type="range" class="lx-range" min="0" max="48" data-bind="borderradius">
                        </div>
                        <div class="lx-ctrl-group full">
                            <label class="lx-ctrl-label">Header FontAwesome Icon</label>
                            <input type="text" class="lx-input-text" data-bind="headericon" placeholder="fa fa-star">
                            <div class="lx-icon-chips">
                                <button type="button" class="lx-icon-chip" data-pick-icon="fa fa-star">
                                    <i class="fa fa-star"></i>
                                </button>
                                <button type="button" class="lx-icon-chip" data-pick-icon="fa fa-rocket">
                                    <i class="fa fa-rocket"></i>
                                </button>
                                <button type="button" class="lx-icon-chip" data-pick-icon="fa fa-lightbulb-o">
                                    <i class="fa fa-lightbulb-o"></i>
                                </button>
                                <button type="button" class="lx-icon-chip" data-pick-icon="fa fa-graduation-cap">
                                    <i class="fa fa-graduation-cap"></i>
                                </button>
                                <button type="button" class="lx-icon-chip" data-pick-icon="fa fa-compass">
                                    <i class="fa fa-compass"></i>
                                </button>
                                <button type="button" class="lx-icon-chip" data-pick-icon="fa fa-bell">
                                    <i class="fa fa-bell"></i>
                                </button>
                                <button type="button" class="lx-icon-chip" data-pick-icon="fa fa-magic">
                                    <i class="fa fa-magic"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="lx-studio-pane" data-pane="buttons">
                    <div class="lx-ctrl-grid">
                        <div class="lx-ctrl-group">
                            <label class="lx-ctrl-label">Next Button BG</label>
                            <div class="lx-color-row">
                                <input type="color" data-color="nextbtnbg">
                                <input type="text" data-bind="nextbtnbg">
                            </div>
                        </div>
                        <div class="lx-ctrl-group">
                            <label class="lx-ctrl-label">Next Button Text</label>
                            <div class="lx-color-row">
                                <input type="color" data-color="nextbtntext">
                                <input type="text" data-bind="nextbtntext">
                            </div>
                        </div>
                        <div class="lx-ctrl-group">
                            <label class="lx-ctrl-label">Skip/End Button BG</label>
                            <div class="lx-color-row">
                                <input type="color" data-color="endbtnbg">
                                <input type="text" data-bind="endbtnbg">
                            </div>
                        </div>
                        <div class="lx-ctrl-group">
                            <label class="lx-ctrl-label">Skip/End Button Text</label>
                            <div class="lx-color-row">
                                <input type="color" data-color="endbtntext">
                                <input type="text" data-bind="endbtntext">
                            </div>
                        </div>
                        <div class="lx-ctrl-group full">
                            <label class="lx-ctrl-label">
                                Button Radius
                                <span class="lx-ctrl-val" id="lx-val-btnradius">10px</span>
                            </label>
                            <input type="range" class="lx-range" min="0" max="30" data-bind="btnradius">
                        </div>
                    </div>
                </div>
                <div class="lx-studio-pane" data-pane="accent">
                    <div class="lx-ctrl-grid">
                        <div class="lx-ctrl-group full">
                            <label class="lx-ctrl-label">Accent Bar Style</label>
                            <select class="lx-select" data-bind="accentstyle">
                                <option value="gradient_animated">Animated Gradient</option>
                                <option value="gradient_static">Static Gradient</option>
                                <option value="solid">Solid Color</option>
                                <option value="hidden">Hidden</option>
                            </select>
                        </div>
                        <div class="lx-ctrl-group">
                            <label class="lx-ctrl-label">Gradient Color 1</label>
                            <div class="lx-color-row">
                                <input type="color" data-color="accentcolor1">
                                <input type="text" data-bind="accentcolor1">
                            </div>
                        </div>
                        <div class="lx-ctrl-group">
                            <label class="lx-ctrl-label">Gradient Color 2</label>
                            <div class="lx-color-row">
                                <input type="color" data-color="accentcolor2">
                                <input type="text" data-bind="accentcolor2">
                            </div>
                        </div>
                        <div class="lx-ctrl-group">
                            <label class="lx-ctrl-label">Gradient Color 3</label>
                            <div class="lx-color-row">
                                <input type="color" data-color="accentcolor3">
                                <input type="text" data-bind="accentcolor3">
                            </div>
                        </div>
                        <div class="lx-ctrl-group">
                            <label class="lx-ctrl-label">
                                Bar Height
                                <span class="lx-ctrl-val" id="lx-val-accentheight">5px</span>
                            </label>
                            <input type="range" class="lx-range" min="0" max="20" data-bind="accentheight">
                        </div>
                    </div>
                </div>
                <div class="lx-studio-pane" data-pane="backdrop">
                    <div class="lx-ctrl-grid">
                        <div class="lx-ctrl-group">
                            <label class="lx-ctrl-label">Backdrop Mode</label>
                            <select class="lx-select" data-bind="backdropmode">
                                <option value="blur">Blurred Overlay</option>
                                <option value="solid">Solid Color Overlay</option>
                                <option value="transparent">Transparent (Page Visible)</option>
                                <option value="none">Hidden (No Backdrop)</option>
                            </select>
                        </div>
                        <div class="lx-ctrl-group">
                            <label class="lx-ctrl-label">Spotlight Ring Color</label>
                            <div class="lx-color-row">
                                <input type="color" data-color="spotlightcolor">
                                <input type="text" data-bind="spotlightcolor">
                            </div>
                        </div>
                        <div class="lx-ctrl-group full">
                            <label class="lx-ctrl-label">Backdrop Color</label>
                            <div class="lx-color-row">
                                <input type="color" data-color="backdropcolor">
                                <input type="text" data-bind="backdropcolor">
                            </div>
                        </div>
                        <div class="lx-ctrl-group">
                            <label class="lx-ctrl-label">
                                Opacity
                                <span class="lx-ctrl-val" id="lx-val-backdropopacity">58%</span>
                            </label>
                            <input type="range" class="lx-range" min="0" max="95" data-bind="backdropopacity">
                        </div>
                        <div class="lx-ctrl-group">
                            <label class="lx-ctrl-label">
                                Blur Amount
                                <span class="lx-ctrl-val" id="lx-val-backdropblur">4px</span>
                            </label>
                            <input type="range" class="lx-range" min="0" max="20" data-bind="backdropblur">
                        </div>
                    </div>
                </div>
                <div class="lx-studio-pane" data-pane="anim">
                    <div class="lx-ctrl-grid">
                        <div class="lx-ctrl-group full">
                            <label class="lx-ctrl-label">Header Title Animation</label>
                            <select class="lx-select" data-bind="titleanim">
                                <option value="typing">Typewriter (Typing Effect)</option>
                                <option value="fade_up">Fade &amp; Slide Up</option>
                                <option value="blur_in">Blur Reveal</option>
                                <option value="none">None</option>
                            </select>
                        </div>
                        <div class="lx-ctrl-group full">
                            <label class="lx-ctrl-label">
                                Typing Speed
                                <span class="lx-ctrl-val" id="lx-val-typingspeed">35ms</span>
                            </label>
                            <input type="range" class="lx-range" min="15" max="90" data-bind="typingspeed">
                        </div>
                        <div class="lx-ctrl-group full">
                            <label class="lx-ctrl-label">Body Text Animation</label>
                            <select class="lx-select" data-bind="bodyanim">
                                <option value="fade_up">Smooth Fade Up</option>
                                <option value="blur_in">Blur Reveal</option>
                                <option value="slide_in">Slide In</option>
                                <option value="none">None</option>
                            </select>
                        </div>
                    </div>
                </div>
            </div>
            <div class="lx-mode-pane" data-mode-pane="tours">
                <div class="lx-tours-filter">
                    <button type="button" class="lx-filter-btn active" data-tour-filter="all">All</button>
                    <button type="button" class="lx-filter-btn" data-tour-filter="active">Active</button>
                    <button type="button" class="lx-filter-btn" data-tour-filter="inactive">Inactive</button>
                </div>
                <div id="lx-tours-list" class="lx-tours-list"></div>
                <div id="lx-steps-section" class="lx-steps-section" style="display:none;">
                    <div class="lx-section-title">Tour Steps</div>
                    <div id="lx-steps-list" class="lx-steps-list"></div>
                </div>
                <div id="lx-step-editor" class="lx-step-editor" style="display:none;">
                    <div class="lx-step-editor-head">
                        <span id="lx-step-editor-label" class="lx-section-title">Customize Step</span>
                        <button type="button" id="lx-step-reset" class="lx-quick-btn lx-btn-reset">
                            <i class="fa fa-undo"></i> Reset Step
                        </button>
                    </div>
                    <div class="lx-ctrl-grid">
                        <div class="lx-ctrl-group full">
                            <label class="lx-ctrl-label">Step FontAwesome Icon</label>
                            <input type="text" class="lx-input-text" data-step-bind="icon"
                                placeholder="Inherit Global Icon">
                            <div class="lx-icon-chips">
                                <button type="button" class="lx-icon-chip" data-step-icon="fa fa-rocket">
                                    <i class="fa fa-rocket"></i>
                                </button>
                                <button type="button" class="lx-icon-chip" data-step-icon="fa fa-search">
                                    <i class="fa fa-search"></i>
                                </button>
                                <button type="button" class="lx-icon-chip" data-step-icon="fa fa-cog">
                                    <i class="fa fa-cog"></i>
                                </button>
                                <button type="button" class="lx-icon-chip" data-step-icon="fa fa-graduation-cap">
                                    <i class="fa fa-graduation-cap"></i>
                                </button>
                                <button type="button" class="lx-icon-chip" data-step-icon="fa fa-compass">
                                    <i class="fa fa-compass"></i>
                                </button>
                                <button type="button" class="lx-icon-chip" data-step-icon="fa fa-check-circle">
                                    <i class="fa fa-check-circle"></i>
                                </button>
                                <button type="button" class="lx-icon-chip" data-step-icon="fa fa-info-circle">
                                    <i class="fa fa-info-circle"></i>
                                </button>
                            </div>
                        </div>
                        <div class="lx-ctrl-group">
                            <label class="lx-ctrl-label">Primary &amp; Icon Color</label>
                            <div class="lx-color-row">
                                <input type="color" data-step-color="primarycolor">
                                <input type="text" data-step-bind="primarycolor" placeholder="Inherit">
                            </div>
                        </div>
                        <div class="lx-ctrl-group">
                            <label class="lx-ctrl-label">Header Background</label>
                            <div class="lx-color-row">
                                <input type="color" data-step-color="headerbg">
                                <input type="text" data-step-bind="headerbg" placeholder="Inherit">
                            </div>
                        </div>
                        <div class="lx-ctrl-group">
                            <label class="lx-ctrl-label">Title Text Color</label>
                            <div class="lx-color-row">
                                <input type="color" data-step-color="titlecolor">
                                <input type="text" data-step-bind="titlecolor" placeholder="Inherit">
                            </div>
                        </div>
                        <div class="lx-ctrl-group">
                            <label class="lx-ctrl-label">Spotlight Ring Color</label>
                            <div class="lx-color-row">
                                <input type="color" data-step-color="spotlightcolor">
                                <input type="text" data-step-bind="spotlightcolor" placeholder="Inherit">
                            </div>
                        </div>
                        <div class="lx-ctrl-group">
                            <label class="lx-ctrl-label">Title Animation</label>
                            <select class="lx-select" data-step-bind="titleanim">
                                <option value="">Inherit Global</option>
                                <option value="typing">Typewriter</option>
                                <option value="fade_up">Fade &amp; Slide Up</option>
                                <option value="blur_in">Blur Reveal</option>
                                <option value="none">None</option>
                            </select>
                        </div>
                        <div class="lx-ctrl-group">
                            <label class="lx-ctrl-label">Body Animation</label>
                            <select class="lx-select" data-step-bind="bodyanim">
                                <option value="">Inherit Global</option>
                                <option value="fade_up">Smooth Fade Up</option>
                                <option value="blur_in">Blur Reveal</option>
                                <option value="slide_in">Slide In</option>
                                <option value="none">None</option>
                            </select>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="lx-studio-stage">
            <div id="lx-stage-backdrop" class="lx-stage-backdrop"></div>
            <div class="lx-stage-bar">
                <span id="lx-stage-badge" class="lx-stage-badge">Live Interactive Preview</span>
                <button type="button" id="lx-replay-anim" class="lx-replay-btn">
                    <i class="fa fa-refresh"></i> Replay
                </button>
            </div>
            <div id="lx-stage-target-mock" class="lx-stage-target-mock">
                <i class="fa fa-crosshairs"></i> Highlighted Target Element
            </div>
            <div id="lx-prev-card" class="lx-prev-card">
                <div id="lx-prev-accent"></div>
                <div id="lx-prev-header" class="lx-prev-header">
                    <div class="lx-prev-title-wrap">
                        <span id="lx-prev-icon-box" class="lx-prev-icon">
                            <i id="lx-prev-icon" class="fa fa-star"></i>
                        </span>
                        <h5 id="lx-prev-title" class="lx-prev-title">Welcome to Your Course</h5>
                    </div>
                    <button type="button" id="lx-prev-close" class="lx-prev-close">
                        <i class="fa fa-times"></i>
                    </button>
                </div>
                <div id="lx-prev-body" class="lx-prev-body">
                    Adjust colors, match platform branding, or customize individual tour steps live.
                </div>
                <div id="lx-prev-footer" class="lx-prev-footer">
                    <button type="button" id="lx-prev-btn-end" class="lx-prev-btn">Skip tour</button>
                    <button type="button" id="lx-prev-btn-next" class="lx-prev-btn">
                        <span>Next</span>
                        <span id="lx-prev-badge" class="lx-prev-badge">2 / 3</span>
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>
HTML;

    $settings->add(new admin_setting_heading(
        'local_tourstyler/previewheading',
        get_string('previewheading', 'local_tourstyler'),
        $studiohtml
    ));

    $colormodes = [
        'auto' => get_string('colormode_auto', 'local_tourstyler'),
        'light' => get_string('colormode_light', 'local_tourstyler'),
        'dark' => get_string('colormode_dark', 'local_tourstyler'),
    ];

    $settings->add(new admin_setting_configselect(
        'local_tourstyler/colormode',
        get_string('colormode', 'local_tourstyler'),
        '',
        'auto',
        $colormodes
    ));

    $presets = [
        'saas' => get_string('preset_saas', 'local_tourstyler'),
        'glass' => get_string('preset_glass', 'local_tourstyler'),
        'minimal' => get_string('preset_minimal', 'local_tourstyler'),
    ];

    $settings->add(new admin_setting_configselect(
        'local_tourstyler/preset',
        get_string('preset', 'local_tourstyler'),
        '',
        'saas',
        $presets
    ));

    $settings->add(new admin_setting_configcolourpicker(
        'local_tourstyler/primarycolor',
        get_string('primarycolor', 'local_tourstyler'),
        '',
        '#6366f1'
    ));

    $settings->add(new admin_setting_configtext(
        'local_tourstyler/headericon',
        get_string('headericon', 'local_tourstyler'),
        get_string('headericon_desc', 'local_tourstyler'),
        'fa fa-star',
        PARAM_TEXT
    ));

    $settings->add(new admin_setting_configcolourpicker(
        'local_tourstyler/headerbg',
        get_string('headerbg', 'local_tourstyler'),
        '',
        '#ffffff'
    ));

    $settings->add(new admin_setting_configcolourpicker(
        'local_tourstyler/titlecolor',
        get_string('titlecolor', 'local_tourstyler'),
        '',
        '#0f172a'
    ));

    $settings->add(new admin_setting_configcolourpicker(
        'local_tourstyler/footerbg',
        get_string('footerbg', 'local_tourstyler'),
        '',
        '#f8fafc'
    ));

    $settings->add(new admin_setting_configcolourpicker(
        'local_tourstyler/textcolor',
        get_string('textcolor', 'local_tourstyler'),
        '',
        '#475569'
    ));

    $settings->add(new admin_setting_configtext(
        'local_tourstyler/borderradius',
        get_string('borderradius', 'local_tourstyler'),
        '',
        '20',
        PARAM_INT
    ));

    $settings->add(new admin_setting_configcolourpicker(
        'local_tourstyler/nextbtnbg',
        get_string('nextbtnbg', 'local_tourstyler'),
        '',
        '#6366f1'
    ));

    $settings->add(new admin_setting_configcolourpicker(
        'local_tourstyler/nextbtntext',
        get_string('nextbtntext', 'local_tourstyler'),
        '',
        '#ffffff'
    ));

    $settings->add(new admin_setting_configcolourpicker(
        'local_tourstyler/endbtnbg',
        get_string('endbtnbg', 'local_tourstyler'),
        '',
        '#ffffff'
    ));

    $settings->add(new admin_setting_configcolourpicker(
        'local_tourstyler/endbtntext',
        get_string('endbtntext', 'local_tourstyler'),
        '',
        '#475569'
    ));

    $settings->add(new admin_setting_configtext(
        'local_tourstyler/btnradius',
        get_string('btnradius', 'local_tourstyler'),
        '',
        '10',
        PARAM_INT
    ));

    $accentstyles = [
        'gradient_animated' => get_string('accentstyle_gradient_animated', 'local_tourstyler'),
        'gradient_static' => get_string('accentstyle_gradient_static', 'local_tourstyler'),
        'solid' => get_string('accentstyle_solid', 'local_tourstyler'),
        'hidden' => get_string('accentstyle_hidden', 'local_tourstyler'),
    ];

    $settings->add(new admin_setting_configselect(
        'local_tourstyler/accentstyle',
        get_string('accentstyle', 'local_tourstyler'),
        '',
        'gradient_animated',
        $accentstyles
    ));

    $settings->add(new admin_setting_configcolourpicker(
        'local_tourstyler/accentcolor1',
        get_string('accentcolor1', 'local_tourstyler'),
        '',
        '#6366f1'
    ));

    $settings->add(new admin_setting_configcolourpicker(
        'local_tourstyler/accentcolor2',
        get_string('accentcolor2', 'local_tourstyler'),
        '',
        '#8b5cf6'
    ));

    $settings->add(new admin_setting_configcolourpicker(
        'local_tourstyler/accentcolor3',
        get_string('accentcolor3', 'local_tourstyler'),
        '',
        '#0ea5e9'
    ));

    $settings->add(new admin_setting_configtext(
        'local_tourstyler/accentheight',
        get_string('accentheight', 'local_tourstyler'),
        get_string('accentheight_desc', 'local_tourstyler'),
        '5',
        PARAM_INT
    ));

    $settings->add(new admin_setting_configcolourpicker(
        'local_tourstyler/spotlightcolor',
        get_string('spotlightcolor', 'local_tourstyler'),
        '',
        '#6366f1'
    ));

    $backdropmodes = [
        'blur' => get_string('backdropmode_blur', 'local_tourstyler'),
        'solid' => get_string('backdropmode_solid', 'local_tourstyler'),
        'transparent' => get_string('backdropmode_transparent', 'local_tourstyler'),
        'none' => get_string('backdropmode_none', 'local_tourstyler'),
    ];

    $settings->add(new admin_setting_configselect(
        'local_tourstyler/backdropmode',
        get_string('backdropmode', 'local_tourstyler'),
        '',
        'blur',
        $backdropmodes
    ));

    $settings->add(new admin_setting_configcolourpicker(
        'local_tourstyler/backdropcolor',
        get_string('backdropcolor', 'local_tourstyler'),
        '',
        '#0f172a'
    ));

    $settings->add(new admin_setting_configtext(
        'local_tourstyler/backdropopacity',
        get_string('backdropopacity', 'local_tourstyler'),
        '',
        '58',
        PARAM_INT
    ));

    $settings->add(new admin_setting_configtext(
        'local_tourstyler/backdropblur',
        get_string('backdropblur', 'local_tourstyler'),
        '',
        '4',
        PARAM_INT
    ));

    $titleanims = [
        'typing' => get_string('anim_typing', 'local_tourstyler'),
        'fade_up' => get_string('anim_fade_up', 'local_tourstyler'),
        'blur_in' => get_string('anim_blur_in', 'local_tourstyler'),
        'none' => get_string('anim_none', 'local_tourstyler'),
    ];

    $settings->add(new admin_setting_configselect(
        'local_tourstyler/titleanim',
        get_string('titleanim', 'local_tourstyler'),
        '',
        'typing',
        $titleanims
    ));

    $settings->add(new admin_setting_configtext(
        'local_tourstyler/typingspeed',
        get_string('typingspeed', 'local_tourstyler'),
        '',
        '35',
        PARAM_INT
    ));

    $bodyanims = [
        'fade_up' => get_string('anim_fade_up', 'local_tourstyler'),
        'blur_in' => get_string('anim_blur_in', 'local_tourstyler'),
        'slide_in' => get_string('anim_slide_in', 'local_tourstyler'),
        'none' => get_string('anim_none', 'local_tourstyler'),
    ];

    $settings->add(new admin_setting_configselect(
        'local_tourstyler/bodyanim',
        get_string('bodyanim', 'local_tourstyler'),
        '',
        'fade_up',
        $bodyanims
    ));

    $settings->add(new admin_setting_configtextarea(
        'local_tourstyler/stepoverrides',
        get_string('stepoverrides', 'local_tourstyler'),
        '',
        '{}',
        PARAM_RAW
    ));

    $docstitle = get_string('cssdocs_title', 'local_tourstyler');
    $docsintro = get_string('cssdocs_intro', 'local_tourstyler');

    $docshtml = <<<HTML
<div class="card border-0 shadow-sm mt-2 mb-3">
    <div class="card-header bg-light fw-bold">{$docstitle}</div>
    <div class="card-body p-3">
        <p class="text-muted small mb-2">{$docsintro}</p>
        <div class="table-responsive">
            <table class="table table-sm table-bordered align-middle mb-3 small">
                <thead class="table-light">
                    <tr><th>Selector</th><th>Target Element</th></tr>
                </thead>
                <tbody>
                    <tr><td><code>.lx-tour-card</code></td><td>Main tour modal card container</td></tr>
                    <tr><td><code>.lx-tour-accent</code></td><td>Top accent bar (gradient / solid bar)</td></tr>
                    <tr><td><code>.lx-tour-header</code></td><td>Header wrapper</td></tr>
                    <tr><td><code>.lx-tour-icon</code></td><td>Header icon badge</td></tr>
                    <tr><td><code>.lx-tour-title</code></td><td>Tour step title text</td></tr>
                    <tr><td><code>.lx-tour-close</code></td><td>Top-right close button</td></tr>
                    <tr><td><code>.lx-tour-body</code></td><td>Step description content area</td></tr>
                    <tr><td><code>.lx-tour-footer</code></td><td>Footer buttons container</td></tr>
                    <tr><td><code>.lx-tour-btn-next</code></td><td>Next step primary button</td></tr>
                    <tr><td><code>.lx-tour-btn-end</code></td><td>End tour button</td></tr>
                    <tr><td><code>.lx-step-badge</code></td><td>Step counter pill badge (e.g. 2 / 3)</td></tr>
                    <tr><td><code>.lx-mode-dark</code></td><td>Applied to tour step when Dark Mode is active</td></tr>
                    <tr><td><code>.lx-tour-target-active</code></td><td>Highlighted target element on page</td></tr>
                    <tr><td><code>div[data-flexitour="backdrop"]</code></td><td>Page overlay backdrop</td></tr>
                    <tr><td><code>div[data-flexitour="step-background"]</code></td><td>Highlighted target spotlight</td></tr>
                </tbody>
            </table>
        </div>
        <div class="small fw-semibold mb-1">CSS Variables (<code>:root</code>)</div>
        <div class="bg-light border rounded p-2 small font-monospace">
            --lx-tour-primary | --lx-tour-header-bg | --lx-tour-title-color |
            --lx-tour-footer-bg | --lx-tour-text | --lx-tour-radius |
            --lx-tour-next-bg | --lx-tour-next-text | --lx-tour-end-bg |
            --lx-tour-end-text | --lx-tour-btn-radius | --lx-spotlight-color |
            --lx-backdrop-bg | --lx-backdrop-filter
        </div>
    </div>
</div>
HTML;

    $settings->add(new admin_setting_heading(
        'local_tourstyler/customcssheading',
        get_string('customcssheading', 'local_tourstyler'),
        $docshtml
    ));

    $settings->add(new admin_setting_configtextarea(
        'local_tourstyler/customcss',
        get_string('customcss', 'local_tourstyler'),
        get_string('customcss_desc', 'local_tourstyler'),
        '',
        PARAM_RAW
    ));
}