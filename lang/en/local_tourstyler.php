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
 * English language strings for User Tour Styler.
 *
 * @package    local_tourstyler
 * @copyright  2026 AbdelrhmanBoghdady support@learn-ix.com
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$string['pluginname'] = 'User Tour Styler';$string['enabled'] = 'Enable User Tour Styler';
$string['enabled_desc'] = 'When disabled, Moodle default user tour styling will be used.';$string['deployheading'] = 'Deploy tour template';
$string['activethemeinfo'] = 'Current active theme: {$a}. Click below to create the Mustache override inside this theme.';
$string['deploybtn'] = 'Deploy template';
$string['redeploybtn'] = 'Redeploy template';$string['removebtn'] = 'Remove override (restore default)';
$string['status_deployed'] = 'Template override is active in theme: {$a}.';
$string['status_not_deployed'] = 'Template override is not deployed yet in theme: {$a}.';
$string['previewheading'] = 'Live interactive preview';$string['colormode'] = 'Color mode';
$string['colormode_auto'] = 'Auto (system / theme)';$string['colormode_light'] = 'Light mode';
$string['colormode_dark'] = 'Dark mode';$string['preset'] = 'Design preset';
$string['preset_saas'] = 'Modern SaaS';$string['preset_glass'] = 'Glassmorphism';
$string['preset_minimal'] = 'Minimal clean';$string['primarycolor'] = 'Primary & icon color';
$string['headericon'] = 'Header FontAwesome icon';$string['headericon_desc'] = 'Enter FontAwesome icon classes (e.g. fa fa-star, fa fa-rocket).';
$string['headerbg'] = 'Header background color';
$string['titlecolor'] = 'Header title text color';$string['footerbg'] = 'Footer background color';
$string['textcolor'] = 'Body text color';$string['borderradius'] = 'Card border radius (px)';
$string['buttonsheading'] = 'Buttons styling';$string['buttonsheading_desc'] = 'Customize the next and skip/end tour buttons appearance.';
$string['nextbtnbg'] = 'Next button background';$string['nextbtntext'] = 'Next button text color';
$string['endbtnbg'] = 'Skip/end button background';$string['endbtntext'] = 'Skip/end button text color';
$string['btnradius'] = 'Button border radius (px)';$string['accentheading'] = 'Top accent bar';
$string['accentheading_desc'] = 'Customize the top decorative bar above the tour card header.';$string['accentstyle'] = 'Accent bar style';
$string['accentstyle_gradient_animated'] = 'Animated gradient';$string['accentstyle_gradient_static'] = 'Static gradient';
$string['accentstyle_solid'] = 'Solid color';$string['accentstyle_hidden'] = 'Hidden';
$string['accentcolor1'] = 'Gradient color 1';$string['accentcolor2'] = 'Gradient color 2';
$string['accentcolor3'] = 'Gradient color 3';$string['accentheight'] = 'Accent bar height (px)';
$string['accentheight_desc'] = 'Height of the top accent bar in pixels (0 to 20).';$string['spotlightcolor'] = 'Spotlight ring color';
$string['backdropmode'] = 'Backdrop mode';$string['backdropmode_blur'] = 'Blurred overlay';
$string['backdropmode_solid'] = 'Solid color overlay';$string['backdropmode_transparent'] = 'Transparent (page visible)';
$string['backdropmode_none'] = 'Hidden (no backdrop)';$string['backdropcolor'] = 'Backdrop color';
$string['backdropopacity'] = 'Backdrop opacity (\%)';$string['backdropblur'] = 'Backdrop blur (px)';
$string['titleanim'] = 'Header title animation';$string['typingspeed'] = 'Typing speed (ms)';
$string['bodyanim'] = 'Body text animation';$string['anim_typing'] = 'Typewriter (typing effect)';
$string['anim_fade_up'] = 'Smooth fade up';$string['anim_blur_in'] = 'Blur reveal';
$string['anim_slide_in'] = 'Slide in';$string['anim_none'] = 'None';
$string['customcssheading'] = 'Custom CSS & reference';$string['cssdocs_title'] = 'Available CSS selectors & variables';
$string['cssdocs_intro'] = 'Use the selectors and variables below in Custom CSS to override any part of the tour card.';$string['customcss'] = 'Custom CSS';
$string['customcss_desc'] = 'Additional CSS rules injected after the plugin styles.';$string['stepoverrides'] = 'Per-step style overrides (JSON)';
$string['privacy:metadata'] = 'The User Tour Styler plugin does not store any personal data.';$string['err_themedirnotfound'] = 'Active theme directory not found: {$a}';$string['err_permissioncreate'] = 'Permission denied creating: {$a}';$string['err_permissionwrite'] = 'Permission denied writing: {$a}';$string['err_permissionremove'] = 'Permission denied removing: {$a}';$string['msg_deployed'] = 'Deployed successfully to theme: {$a}';$string['msg_nooverride'] = 'No override found in theme: {$a}';$string['msg_removed'] = 'Override removed. Restored Moodle default for theme: {$a}';
$string['studio_global'] = 'Global studio';$string['studio_tours'] = 'Tours & steps';
$string['studio_matchplatform'] = 'Match platform colors';$string['studio_resetdefaults'] = 'Reset to default';
$string['tab_card'] = 'Card';$string['tab_buttons'] = 'Buttons';
$string['tab_accent'] = 'Accent';$string['tab_backdrop'] = 'Backdrop & focus';
$string['tab_anim'] = 'Animations';$string['filter_all'] = 'All';
$string['filter_active'] = 'Active';$string['filter_inactive'] = 'Inactive';
$string['tour_steps'] = 'Tour steps';$string['customize_step'] = 'Customize step';
$string['reset_step'] = 'Reset step';$string['step_icon'] = 'Step FontAwesome icon';
$string['inherit_global_icon'] = 'Inherit global icon';$string['inherit_global'] = 'Inherit global';
$string['inherit_placeholder'] = 'Inherit';$string['replay'] = 'Replay';
$string['highlighted_target'] = 'Highlighted target element';$string['sample_title'] = 'Welcome to your course';
$string['sample_body'] = 'Adjust colors, match platform branding, or customize individual tour steps live.';$string['skip_tour'] = 'Skip tour';
$string['next'] = 'Next';$string['no_tours_found'] = 'No tours found.';
$string['no_steps_in_tour'] = 'No steps in this tour.';$string['steps_label'] = 'steps';
$string['status_active'] = 'Active';$string['status_inactive'] = 'Inactive';
$string['untitled_step'] = 'Untitled step';$string['empty_step_content'] = 'Empty step content';
$string['step_customized'] = 'Customized';$string['step_word'] = 'Step';
$string['of_word'] = 'of';$string['preview_word'] = 'preview';
$string['cssdocs_selector'] = 'Selector';$string['cssdocs_target'] = 'Target element';
$string['cssdocs_vars'] = 'CSS variables';$string['cssdocs_card'] = 'Main tour modal card container';
$string['cssdocs_accent'] = 'Top accent bar (gradient / solid bar)';$string['cssdocs_header'] = 'Header wrapper';
$string['cssdocs_icon'] = 'Header icon badge';$string['cssdocs_title_el'] = 'Tour step title text';
$string['cssdocs_close'] = 'Top-right close button';$string['cssdocs_body'] = 'Step description content area';
$string['cssdocs_footer'] = 'Footer buttons container';$string['cssdocs_btn_next'] = 'Next step primary button';
$string['cssdocs_btn_end'] = 'End tour button';$string['cssdocs_step_badge'] = 'Step counter pill badge (e.g. 2 / 3)';
$string['cssdocs_mode_dark'] = 'Applied to tour step when dark mode is active';$string['cssdocs_target_active'] = 'Highlighted target element on page';
$string['cssdocs_backdrop'] = 'Page overlay backdrop';$string['cssdocs_spotlight'] = 'Highlighted target spotlight';