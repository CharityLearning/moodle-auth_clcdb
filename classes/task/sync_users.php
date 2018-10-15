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
 * Sync users task
 * @package   auth_clcdb
 * @author    Guy Thomas <gthomas@moodlerooms.com>
 * @copyright Copyright (c) 2017 Blackboard Inc.
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace auth_clcdb\task;

defined('MOODLE_INTERNAL') || die();

/**
 * Sync users task class
 * @package   auth_clcdb
 * @author    Guy Thomas <gthomas@moodlerooms.com>
 * @copyright Copyright (c) 2017 Blackboard Inc.
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class sync_users extends \core\task\scheduled_task {

    /**
     * Name for this task.
     *
     * @return string
     */
    public function get_name() {
        return get_string('auth_clcdbsyncuserstask', 'auth_clcdb');
    }

    /**
     * Run task for synchronising users.
     */
    public function execute() {
        if (!is_enabled_auth('clcdb')) {
            mtrace('auth_clcdb plugin is disabled, synchronisation stopped', 2);
            return;
        }

        $dbauth = get_auth_plugin('clcdb');
        $config = get_config('auth_clcdb');
        $trace = new \text_progress_trace();
        $dbauth->sync_users($trace);
    }

}
