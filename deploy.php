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
 * Deploy or remove the user tour Mustache override in the active theme.
 *
 * @package    local_tourstyler
 * @copyright  2026 AbdelrhmanBoghdady support@learn-ix.com
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');

use core\output\notification;
use local_tourstyler\theme_deployer;

require_login();
require_capability('moodle/site:config', context_system::instance());
require_sesskey();

$action = optional_param('action', 'deploy', PARAM_ALPHA);

if ($action === 'remove') {$result = theme_deployer::remove_from_active_theme();
} else {
    $result = theme_deployer::deploy_to_active_theme();
}

$redirecturl = new moodle_url('/admin/settings.php', ['section' => 'local_tourstyler']);

redirect(
    $redirecturl,$result['message'],
    null,
    $result['status'] ? notification::NOTIFY_SUCCESS : notification::NOTIFY_ERROR
);
