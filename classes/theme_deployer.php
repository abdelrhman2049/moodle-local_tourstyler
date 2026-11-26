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
 * Theme Mustache template deployer for User Tour Styler.
 *
 * @package    local_tourstyler
 * @copyright  2026 AbdelrhmanBoghdady support@learn-ix.com
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_tourstyler;

use core_component;

/**
 * Theme deployer helper class.
 *
 * @package    local_tourstyler
 * @copyright  2026 AbdelrhmanBoghdady support@learn-ix.com
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class theme_deployer {

    /**
     * Get target file path for active theme mustache override.
     *
     * @return string|null
     */
    public static function get_target_file(): ?string {
        global $CFG;

        $theme = clean_param($CFG->theme, PARAM_PLUGIN);
        $themedir = core_component::get_plugin_directory('theme', $theme);
        if (empty($themedir) || !is_dir($themedir)) {
            return null;
        }

        return $themedir . '/templates/tool_usertours/tourstep.mustache';
    }

    /**
     * Check if override is deployed.
     *
     * @return bool
     */
    public static function is_deployed(): bool {
        $targetfile = self::get_target_file();
        return $targetfile !== null && file_exists($targetfile);
    }

    /**
     * Deploy template to active theme.
     *
     * @return array
     */
    public static function deploy_to_active_theme(): array {
        global $CFG;

        $theme = clean_param($CFG->theme, PARAM_PLUGIN);
        $targetfile = self::get_target_file();

        if ($targetfile === null) {
            return [
                'status' => false,
                'message' => get_string('err_themedirnotfound', 'local_tourstyler', $theme),
            ];
        }

        $targetdir = dirname($targetfile);

        if (!is_dir($targetdir)) {
            if (!@mkdir($targetdir, 0775, true)) {
                return [
                    'status' => false,
                    'message' => get_string('err_permissioncreate', 'local_tourstyler', $targetdir),
                ];
            }
        }

        $mustache = self::get_mustache_template();
        if (@file_put_contents($targetfile, $mustache) === false) {
            return [
                'status' => false,
                'message' => get_string('err_permissionwrite', 'local_tourstyler', $targetfile),
            ];
        }

        purge_all_caches();

        return [
            'status' => true,
            'message' => get_string('msg_deployed', 'local_tourstyler', $theme),
        ];
    }

    /**
     * Remove template override from active theme.
     *
     * @return array
     */
    public static function remove_from_active_theme(): array {
        global $CFG;

        $theme = clean_param($CFG->theme, PARAM_PLUGIN);
        $targetfile = self::get_target_file();

        if ($targetfile === null || !file_exists($targetfile)) {
            return [
                'status' => true,
                'message' => get_string('msg_nooverride', 'local_tourstyler', $theme),
            ];
        }

        if (!@unlink($targetfile)) {
            return [
                'status' => false,
                'message' => get_string('err_permissionremove', 'local_tourstyler', $targetfile),
            ];
        }

        purge_all_caches();

        return [
            'status' => true,
            'message' => get_string('msg_removed', 'local_tourstyler', $theme),
        ];
    }

    /**
     * Get mustache template content string.
     *
     * @return string
     */
    private static function get_mustache_template(): string {
        return <<<'MUSTACHE'
<div data-role="flexitour-step" class="lx-tour-step">
    <div data-role="arrow" class="lx-tour-arrow"></div>
    <div class="modal-dialog lx-tour-dialog" role="document">
        <div class="modal-content lx-tour-card">
            <div class="lx-tour-accent" aria-hidden="true"></div>
            <div class="modal-header lx-tour-header">
                <div class="lx-tour-title-wrap">
                    <span class="lx-tour-icon" aria-hidden="true">
                        <i class="fa fa-star"></i>
                    </span>
                    <h5 class="modal-title lx-tour-title" data-placeholder="title"></h5>
                </div>
                <button type="button" class="lx-tour-close" data-lx-action="close"
                        aria-label="{{#str}} close, core {{/str}}">
                    <i class="fa fa-times" aria-hidden="true"></i>
                </button>
            </div>
            <div class="modal-body lx-tour-body" data-placeholder="body"></div>
            <div class="modal-footer lx-tour-footer">
                <button type="button" class="btn lx-tour-btn lx-tour-btn-end" data-role="end">
                    {{#str}} endtour, tool_usertours {{/str}}
                </button>
                <button type="button" class="btn lx-tour-btn lx-tour-btn-primary lx-tour-btn-next" data-role="next">
                    {{#str}} nextstep, tool_usertours {{/str}}
                </button>
            </div>
        </div>
    </div>
</div>
MUSTACHE;
    }
}
