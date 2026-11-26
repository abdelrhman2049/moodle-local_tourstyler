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

    $jsstrings = [
        'previewheading' => get_string('previewheading', 'local_tourstyler'),
        'sample_title' => get_string('sample_title', 'local_tourstyler'),
        'sample_body' => get_string('sample_body', 'local_tourstyler'),
        'no_tours_found' => get_string('no_tours_found', 'local_tourstyler'),
        'no_steps_in_tour' => get_string('no_steps_in_tour', 'local_tourstyler'),
        'steps_label' => get_string('steps_label', 'local_tourstyler'),
        'status_active' => get_string('status_active', 'local_tourstyler'),
        'status_inactive' => get_string('status_inactive', 'local_tourstyler'),
        'untitled_step' => get_string('untitled_step', 'local_tourstyler'),
        'empty_step_content' => get_string('empty_step_content', 'local_tourstyler'),
        'step_customized' => get_string('step_customized', 'local_tourstyler'),
        'step_word' => get_string('step_word', 'local_tourstyler'),
        'of_word' => get_string('of_word', 'local_tourstyler'),
        'preview_word' => get_string('preview_word', 'local_tourstyler'),
    ];

    if (optional_param('section', '', PARAM_ALPHANUMEXT) === 'local_tourstyler') {
        $PAGE->requires->js_call_amd('local_tourstyler/studio', 'init', [$jsstrings]);
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

    $strkeys = [
        'studio_global', 'studio_tours', 'studio_matchplatform', 'studio_resetdefaults',
        'tab_card', 'tab_buttons', 'tab_accent', 'tab_backdrop', 'tab_anim',
        'colormode', 'colormode_auto', 'colormode_light', 'colormode_dark',
        'preset', 'preset_saas', 'preset_glass', 'preset_minimal',
        'primarycolor', 'headerbg', 'titlecolor', 'textcolor', 'footerbg',
        'borderradius', 'headericon', 'nextbtnbg', 'nextbtntext', 'endbtnbg',
        'endbtntext', 'btnradius', 'accentstyle', 'accentstyle_gradient_animated',
        'accentstyle_gradient_static', 'accentstyle_solid', 'accentstyle_hidden',
        'accentcolor1', 'accentcolor2', 'accentcolor3', 'accentheight',
        'backdropmode', 'backdropmode_blur', 'backdropmode_solid',
        'backdropmode_transparent', 'backdropmode_none', 'spotlightcolor',
        'backdropcolor', 'backdropopacity', 'backdropblur', 'titleanim',
        'anim_typing', 'anim_fade_up', 'anim_blur_in', 'anim_slide_in',
        'anim_none', 'typingspeed', 'bodyanim', 'filter_all', 'filter_active',
        'filter_inactive', 'tour_steps', 'customize_step', 'reset_step',
        'step_icon', 'inherit_global_icon', 'inherit_global', 'inherit_placeholder',
        'previewheading', 'replay', 'highlighted_target', 'sample_title',
        'sample_body', 'skip_tour', 'next', 'cssdocs_title', 'cssdocs_intro',
        'cssdocs_selector', 'cssdocs_target', 'cssdocs_vars', 'cssdocs_card',
        'cssdocs_accent', 'cssdocs_header', 'cssdocs_icon', 'cssdocs_title_el',
        'cssdocs_close', 'cssdocs_body', 'cssdocs_footer', 'cssdocs_btn_next',
        'cssdocs_btn_end', 'cssdocs_step_badge', 'cssdocs_mode_dark',
        'cssdocs_target_active', 'cssdocs_backdrop', 'cssdocs_spotlight',
    ];
    $s = [];
    foreach ($strkeys as $k) {
        $s[$k] = get_string($k, 'local_tourstyler');
    }

    $studiohtml = <<<HTML
<div class="lx-studio-shell" data-tours="{$toursjson}">
    <div class="lx-studio-header-tabs">
        <button type="button" class="lx-header-tab active" data-mode="global">
            <i class="fa fa-sliders"></i> {$s['studio_global']}
        </button>
        <button type="button" class="lx-header-tab" data-mode="tours">
            <i class="fa fa-map-signs"></i> {$s['studio_tours']}
            <span class="lx-tab-count">{$tourscount}</span>
        </button>
    </div>
    <div class="lx-studio-wrap">
        <div class="lx-studio-controls">
            <div class="lx-mode-pane active" data-mode-pane="global">
                <div class="lx-studio-quickbar">
                    <button type="button" id="lx-match-platform" class="lx-quick-btn">
                        <i class="fa fa-paint-brush"></i> {$s['studio_matchplatform']}
                    </button>
                    <button type="button" id="lx-reset-defaults" class="lx-quick-btn lx-btn-reset">
                        <i class="fa fa-undo"></i> {$s['studio_resetdefaults']}
                    </button>
                </div>
                <div class="lx-studio-tabs">
                    <button type="button" class="lx-studio-tab active" data-tab="card">{$s['tab_card']}</button>
                    <button type="button" class="lx-studio-tab" data-tab="buttons">{$s['tab_buttons']}</button>
                    <button type="button" class="lx-studio-tab" data-tab="accent">{$s['tab_accent']}</button>
                    <button type="button" class="lx-studio-tab" data-tab="backdrop">{$s['tab_backdrop']}</button>
                    <button type="button" class="lx-studio-tab" data-tab="anim">{$s['tab_anim']}</button>
                </div>
                <div class="lx-studio-pane active" data-pane="card">
                    <div class="lx-ctrl-grid">
                        <div class="lx-ctrl-group">
                            <label class="lx-ctrl-label">{$s['colormode']}</label>
                            <select class="lx-select" data-bind="colormode">
                                <option value="auto">{$s['colormode_auto']}</option>
                                <option value="light">{$s['colormode_light']}</option>
                                <option value="dark">{$s['colormode_dark']}</option>
                            </select>
                        </div>
                        <div class="lx-ctrl-group">
                            <label class="lx-ctrl-label">{$s['preset']}</label>
                            <select class="lx-select" data-bind="preset">
                                <option value="saas">{$s['preset_saas']}</option>
                                <option value="glass">{$s['preset_glass']}</option>
                                <option value="minimal">{$s['preset_minimal']}</option>
                            </select>
                        </div>
                        <div class="lx-ctrl-group">
                            <label class="lx-ctrl-label">{$s['primarycolor']}</label>
                            <div class="lx-color-row">
                                <input type="color" data-color="primarycolor">
                                <input type="text" data-bind="primarycolor">
                            </div>
                        </div>
                        <div class="lx-ctrl-group">
                            <label class="lx-ctrl-label">{$s['headerbg']}</label>
                            <div class="lx-color-row">
                                <input type="color" data-color="headerbg">
                                <input type="text" data-bind="headerbg">
                            </div>
                        </div>
                        <div class="lx-ctrl-group">
                            <label class="lx-ctrl-label">{$s['titlecolor']}</label>
                            <div class="lx-color-row">
                                <input type="color" data-color="titlecolor">
                                <input type="text" data-bind="titlecolor">
                            </div>
                        </div>
                        <div class="lx-ctrl-group">
                            <label class="lx-ctrl-label">{$s['textcolor']}</label>
                            <div class="lx-color-row">
                                <input type="color" data-color="textcolor">
                                <input type="text" data-bind="textcolor">
                            </div>
                        </div>
                        <div class="lx-ctrl-group">
                            <label class="lx-ctrl-label">{$s['footerbg']}</label>
                            <div class="lx-color-row">
                                <input type="color" data-color="footerbg">
                                <input type="text" data-bind="footerbg">
                            </div>
                        </div>
                        <div class="lx-ctrl-group">
                            <label class="lx-ctrl-label">
                                {$s['borderradius']}
                                <span class="lx-ctrl-val" id="lx-val-borderradius">20px</span>
                            </label>
                            <input type="range" class="lx-range" min="0" max="48" data-bind="borderradius">
                        </div>
                        <div class="lx-ctrl-group full">
                            <label class="lx-ctrl-label">{$s['headericon']}</label>
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
                            <label class="lx-ctrl-label">{$s['nextbtnbg']}</label>
                            <div class="lx-color-row">
                                <input type="color" data-color="nextbtnbg">
                                <input type="text" data-bind="nextbtnbg">
                            </div>
                        </div>
                        <div class="lx-ctrl-group">
                            <label class="lx-ctrl-label">{$s['nextbtntext']}</label>
                            <div class="lx-color-row">
                                <input type="color" data-color="nextbtntext">
                                <input type="text" data-bind="nextbtntext">
                            </div>
                        </div>
                        <div class="lx-ctrl-group">
                            <label class="lx-ctrl-label">{$s['endbtnbg']}</label>
                            <div class="lx-color-row">
                                <input type="color" data-color="endbtnbg">
                                <input type="text" data-bind="endbtnbg">
                            </div>
                        </div>
                        <div class="lx-ctrl-group">
                            <label class="lx-ctrl-label">{$s['endbtntext']}</label>
                            <div class="lx-color-row">
                                <input type="color" data-color="endbtntext">
                                <input type="text" data-bind="endbtntext">
                            </div>
                        </div>
                        <div class="lx-ctrl-group full">
                            <label class="lx-ctrl-label">
                                {$s['btnradius']}
                                <span class="lx-ctrl-val" id="lx-val-btnradius">10px</span>
                            </label>
                            <input type="range" class="lx-range" min="0" max="30" data-bind="btnradius">
                        </div>
                    </div>
                </div>
                <div class="lx-studio-pane" data-pane="accent">
                    <div class="lx-ctrl-grid">
                        <div class="lx-ctrl-group full">
                            <label class="lx-ctrl-label">{$s['accentstyle']}</label>
                            <select class="lx-select" data-bind="accentstyle">
                                <option value="gradient_animated">{$s['accentstyle_gradient_animated']}</option>
                                <option value="gradient_static">{$s['accentstyle_gradient_static']}</option>
                                <option value="solid">{$s['accentstyle_solid']}</option>
                                <option value="hidden">{$s['accentstyle_hidden']}</option>
                            </select>
                        </div>
                        <div class="lx-ctrl-group">
                            <label class="lx-ctrl-label">{$s['accentcolor1']}</label>
                            <div class="lx-color-row">
                                <input type="color" data-color="accentcolor1">
                                <input type="text" data-bind="accentcolor1">
                            </div>
                        </div>
                        <div class="lx-ctrl-group">
                            <label class="lx-ctrl-label">{$s['accentcolor2']}</label>
                            <div class="lx-color-row">
                                <input type="color" data-color="accentcolor2">
                                <input type="text" data-bind="accentcolor2">
                            </div>
                        </div>
                        <div class="lx-ctrl-group">
                            <label class="lx-ctrl-label">{$s['accentcolor3']}</label>
                            <div class="lx-color-row">
                                <input type="color" data-color="accentcolor3">
                                <input type="text" data-bind="accentcolor3">
                            </div>
                        </div>
                        <div class="lx-ctrl-group">
                            <label class="lx-ctrl-label">
                                {$s['accentheight']}
                                <span class="lx-ctrl-val" id="lx-val-accentheight">5px</span>
                            </label>
                            <input type="range" class="lx-range" min="0" max="20" data-bind="accentheight">
                        </div>
                    </div>
                </div>
                <div class="lx-studio-pane" data-pane="backdrop">
                    <div class="lx-ctrl-grid">
                        <div class="lx-ctrl-group">
                            <label class="lx-ctrl-label">{$s['backdropmode']}</label>
                            <select class="lx-select" data-bind="backdropmode">
                                <option value="blur">{$s['backdropmode_blur']}</option>
                                <option value="solid">{$s['backdropmode_solid']}</option>
                                <option value="transparent">{$s['backdropmode_transparent']}</option>
                                <option value="none">{$s['backdropmode_none']}</option>
                            </select>
                        </div>
                        <div class="lx-ctrl-group">
                            <label class="lx-ctrl-label">{$s['spotlightcolor']}</label>
                            <div class="lx-color-row">
                                <input type="color" data-color="spotlightcolor">
                                <input type="text" data-bind="spotlightcolor">
                            </div>
                        </div>
                        <div class="lx-ctrl-group full">
                            <label class="lx-ctrl-label">{$s['backdropcolor']}</label>
                            <div class="lx-color-row">
                                <input type="color" data-color="backdropcolor">
                                <input type="text" data-bind="backdropcolor">
                            </div>
                        </div>
                        <div class="lx-ctrl-group">
                            <label class="lx-ctrl-label">
                                {$s['backdropopacity']}
                                <span class="lx-ctrl-val" id="lx-val-backdropopacity">58%</span>
                            </label>
                            <input type="range" class="lx-range" min="0" max="95" data-bind="backdropopacity">
                        </div>
                        <div class="lx-ctrl-group">
                            <label class="lx-ctrl-label">
                                {$s['backdropblur']}
                                <span class="lx-ctrl-val" id="lx-val-backdropblur">4px</span>
                            </label>
                            <input type="range" class="lx-range" min="0" max="20" data-bind="backdropblur">
                        </div>
                    </div>
                </div>
                <div class="lx-studio-pane" data-pane="anim">
                    <div class="lx-ctrl-grid">
                        <div class="lx-ctrl-group full">
                            <label class="lx-ctrl-label">{$s['titleanim']}</label>
                            <select class="lx-select" data-bind="titleanim">
                                <option value="typing">{$s['anim_typing']}</option>
                                <option value="fade_up">{$s['anim_fade_up']}</option>
                                <option value="blur_in">{$s['anim_blur_in']}</option>
                                <option value="none">{$s['anim_none']}</option>
                            </select>
                        </div>
                        <div class="lx-ctrl-group full">
                            <label class="lx-ctrl-label">
                                {$s['typingspeed']}
                                <span class="lx-ctrl-val" id="lx-val-typingspeed">35ms</span>
                            </label>
                            <input type="range" class="lx-range" min="15" max="90" data-bind="typingspeed">
                        </div>
                        <div class="lx-ctrl-group full">
                            <label class="lx-ctrl-label">{$s['bodyanim']}</label>
                            <select class="lx-select" data-bind="bodyanim">
                                <option value="fade_up">{$s['anim_fade_up']}</option>
                                <option value="blur_in">{$s['anim_blur_in']}</option>
                                <option value="slide_in">{$s['anim_slide_in']}</option>
                                <option value="none">{$s['anim_none']}</option>
                            </select>
                        </div>
                    </div>
                </div>
            </div>
            <div class="lx-mode-pane" data-mode-pane="tours">
                <div class="lx-tours-filter">
                    <button type="button" class="lx-filter-btn active" data-tour-filter="all">
                        {$s['filter_all']}
                    </button>
                    <button type="button" class="lx-filter-btn" data-tour-filter="active">
                        {$s['filter_active']}
                    </button>
                    <button type="button" class="lx-filter-btn" data-tour-filter="inactive">
                        {$s['filter_inactive']}
                    </button>
                </div>
                <div id="lx-tours-list" class="lx-tours-list"></div>
                <div id="lx-steps-section" class="lx-steps-section" style="display:none;">
                    <div class="lx-section-title">{$s['tour_steps']}</div>
                    <div id="lx-steps-list" class="lx-steps-list"></div>
                </div>
                <div id="lx-step-editor" class="lx-step-editor" style="display:none;">
                    <div class="lx-step-editor-head">
                        <span id="lx-step-editor-label" class="lx-section-title">{$s['customize_step']}</span>
                        <button type="button" id="lx-step-reset" class="lx-quick-btn lx-btn-reset">
                            <i class="fa fa-undo"></i> {$s['reset_step']}
                        </button>
                    </div>
                    <div class="lx-ctrl-grid">
                        <div class="lx-ctrl-group full">
                            <label class="lx-ctrl-label">{$s['step_icon']}</label>
                            <input type="text" class="lx-input-text" data-step-bind="icon"
                                placeholder="{$s['inherit_global_icon']}">
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
                            <label class="lx-ctrl-label">{$s['primarycolor']}</label>
                            <div class="lx-color-row">
                                <input type="color" data-step-color="primarycolor">
                                <input type="text" data-step-bind="primarycolor"
                                    placeholder="{$s['inherit_placeholder']}">
                            </div>
                        </div>
                        <div class="lx-ctrl-group">
                            <label class="lx-ctrl-label">{$s['headerbg']}</label>
                            <div class="lx-color-row">
                                <input type="color" data-step-color="headerbg">
                                <input type="text" data-step-bind="headerbg"
                                    placeholder="{$s['inherit_placeholder']}">
                            </div>
                        </div>
                        <div class="lx-ctrl-group">
                            <label class="lx-ctrl-label">{$s['titlecolor']}</label>
                            <div class="lx-color-row">
                                <input type="color" data-step-color="titlecolor">
                                <input type="text" data-step-bind="titlecolor"
                                    placeholder="{$s['inherit_placeholder']}">
                            </div>
                        </div>
                        <div class="lx-ctrl-group">
                            <label class="lx-ctrl-label">{$s['spotlightcolor']}</label>
                            <div class="lx-color-row">
                                <input type="color" data-step-color="spotlightcolor">
                                <input type="text" data-step-bind="spotlightcolor"
                                    placeholder="{$s['inherit_placeholder']}">
                            </div>
                        </div>
                        <div class="lx-ctrl-group">
                            <label class="lx-ctrl-label">{$s['titleanim']}</label>
                            <select class="lx-select" data-step-bind="titleanim">
                                <option value="">{$s['inherit_global']}</option>
                                <option value="typing">{$s['anim_typing']}</option>
                                <option value="fade_up">{$s['anim_fade_up']}</option>
                                <option value="blur_in">{$s['anim_blur_in']}</option>
                                <option value="none">{$s['anim_none']}</option>
                            </select>
                        </div>
                        <div class="lx-ctrl-group">
                            <label class="lx-ctrl-label">{$s['bodyanim']}</label>
                            <select class="lx-select" data-step-bind="bodyanim">
                                <option value="">{$s['inherit_global']}</option>
                                <option value="fade_up">{$s['anim_fade_up']}</option>
                                <option value="blur_in">{$s['anim_blur_in']}</option>
                                <option value="slide_in">{$s['anim_slide_in']}</option>
                                <option value="none">{$s['anim_none']}</option>
                            </select>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="lx-studio-stage">
            <div id="lx-stage-backdrop" class="lx-stage-backdrop"></div>
            <div class="lx-stage-bar">
                <span id="lx-stage-badge" class="lx-stage-badge">{$s['previewheading']}</span>
                <button type="button" id="lx-replay-anim" class="lx-replay-btn">
                    <i class="fa fa-refresh"></i> {$s['replay']}
                </button>
            </div>
            <div id="lx-stage-target-mock" class="lx-stage-target-mock">
                <i class="fa fa-crosshairs"></i> {$s['highlighted_target']}
            </div>
            <div id="lx-prev-card" class="lx-prev-card">
                <div id="lx-prev-accent"></div>
                <div id="lx-prev-header" class="lx-prev-header">
                    <div class="lx-prev-title-wrap">
                        <span id="lx-prev-icon-box" class="lx-prev-icon">
                            <i id="lx-prev-icon" class="fa fa-star"></i>
                        </span>
                        <h5 id="lx-prev-title" class="lx-prev-title">{$s['sample_title']}</h5>
                    </div>
                    <button type="button" id="lx-prev-close" class="lx-prev-close">
                        <i class="fa fa-times"></i>
                    </button>
                </div>
                <div id="lx-prev-body" class="lx-prev-body">{$s['sample_body']}</div>
                <div id="lx-prev-footer" class="lx-prev-footer">
                    <button type="button" id="lx-prev-btn-end" class="lx-prev-btn">{$s['skip_tour']}</button>
                    <button type="button" id="lx-prev-btn-next" class="lx-prev-btn">
                        <span>{$s['next']}</span>
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

    $docshtml = <<<HTML
<div class="card border-0 shadow-sm mt-2 mb-3">
    <div class="card-header bg-light fw-bold">{$s['cssdocs_title']}</div>
    <div class="card-body p-3">
        <p class="text-muted small mb-2">{$s['cssdocs_intro']}</p>
        <div class="table-responsive">
            <table class="table table-sm table-bordered align-middle mb-3 small">
                <thead class="table-light">
                    <tr><th>{$s['cssdocs_selector']}</th><th>{$s['cssdocs_target']}</th></tr>
                </thead>
                <tbody>
                    <tr><td><code>.lx-tour-card</code></td><td>{$s['cssdocs_card']}</td></tr>
                    <tr><td><code>.lx-tour-accent</code></td><td>{$s['cssdocs_accent']}</td></tr>
                    <tr><td><code>.lx-tour-header</code></td><td>{$s['cssdocs_header']}</td></tr>
                    <tr><td><code>.lx-tour-icon</code></td><td>{$s['cssdocs_icon']}</td></tr>
                    <tr><td><code>.lx-tour-title</code></td><td>{$s['cssdocs_title_el']}</td></tr>
                    <tr><td><code>.lx-tour-close</code></td><td>{$s['cssdocs_close']}</td></tr>
                    <tr><td><code>.lx-tour-body</code></td><td>{$s['cssdocs_body']}</td></tr>
                    <tr><td><code>.lx-tour-footer</code></td><td>{$s['cssdocs_footer']}</td></tr>
                    <tr><td><code>.lx-tour-btn-next</code></td><td>{$s['cssdocs_btn_next']}</td></tr>
                    <tr><td><code>.lx-tour-btn-end</code></td><td>{$s['cssdocs_btn_end']}</td></tr>
                    <tr><td><code>.lx-step-badge</code></td><td>{$s['cssdocs_step_badge']}</td></tr>
                    <tr><td><code>.lx-mode-dark</code></td><td>{$s['cssdocs_mode_dark']}</td></tr>
                    <tr><td><code>.lx-tour-target-active</code></td><td>{$s['cssdocs_target_active']}</td></tr>
                    <tr><td><code>div[data-flexitour="backdrop"]</code></td><td>{$s['cssdocs_backdrop']}</td></tr>
                    <tr><td><code>div[data-flexitour="step-background"]</code></td><td>{$s['cssdocs_spotlight']}</td></tr>
                </tbody>
            </table>
        </div>
        <div class="small fw-semibold mb-1">{$s['cssdocs_vars']} (<code>:root</code>)</div>
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