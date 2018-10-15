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
 * Admin settings and defaults.
 *
 * @package auth_clcdb
 * @copyright  2017 Stephen Bourget
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

if ($ADMIN->fulltree) {

    // We use a couple of custom admin settings since we need to massage the data before it is inserted into the DB.
    require_once($CFG->dirroot.'/auth/db/classes/admin_setting_special_auth_configtext.php');

    // Needed for constants.
    require_once($CFG->libdir.'/authlib.php');

    // Introductory explanation.
    $settings->add(new admin_setting_heading('auth_clcdb/pluginname', '', new lang_string('auth_clcdbdescription', 'auth_clcdb')));

    // Host.
    $settings->add(new admin_setting_configtext('auth_clcdb/host', get_string('auth_clcdbhost_key', 'auth_clcdb'),
            get_string('auth_clcdbhost', 'auth_clcdb') . ' ' .get_string('auth_multiplehosts', 'auth'),
            '127.0.0.1', PARAM_RAW));

    // Type.
    $dboptions = array();
    $dbtypes = array("access", "ado_access", "ado", "ado_mssql", "borland_ibase", "csv", "db2",
        "fbsql", "firebird", "ibase", "informix72", "informix", "mssql", "mssql_n", "mssqlnative",
        "mysql", "mysqli", "mysqlt", "oci805", "oci8", "oci8po", "odbc", "odbc_mssql", "odbc_oracle",
        "oracle", "pdo", "postgres64", "postgres7", "postgres", "proxy", "sqlanywhere", "sybase", "vfp");
    foreach ($dbtypes as $dbtype) {
        $dboptions[$dbtype] = $dbtype;
    }

    $settings->add(new admin_setting_configselect('auth_clcdb/type',
        new lang_string('auth_clcdbtype_key', 'auth_clcdb'),
        new lang_string('auth_clcdbtype', 'auth_clcdb'), 'mysqli', $dboptions));

    // Sybase quotes.
    $yesno = array(
        new lang_string('no'),
        new lang_string('yes'),
    );

    $settings->add(new admin_setting_configselect('auth_clcdb/sybasequoting',
        new lang_string('auth_clcdbsybasequoting', 'auth_clcdb'), new lang_string('auth_clcdbsybasequotinghelp', 'auth_clcdb'),
        0, $yesno));

    // DB Name.
    $settings->add(new admin_setting_configtext('auth_clcdb/name', get_string('auth_clcdbname_key', 'auth_clcdb'),
            get_string('auth_clcdbname', 'auth_clcdb'), '', PARAM_RAW_TRIMMED));

    // DB Username.
    $settings->add(new admin_setting_configtext('auth_clcdb/user', get_string('auth_clcdbuser_key', 'auth_clcdb'),
            get_string('auth_clcdbuser', 'auth_clcdb'), '', PARAM_RAW_TRIMMED));

    // Password.
    $settings->add(new admin_setting_configpasswordunmask('auth_clcdb/pass', get_string('auth_clcdbpass_key', 'auth_clcdb'),
            get_string('auth_clcdbpass', 'auth_clcdb'), ''));

    // DB Table.
    $settings->add(new admin_setting_configtext('auth_clcdb/table', get_string('auth_clcdbtable_key', 'auth_clcdb'),
            get_string('auth_clcdbtable', 'auth_clcdb'), '', PARAM_RAW_TRIMMED));

    // DB User field.
    $settings->add(new admin_setting_configtext('auth_clcdb/fielduser', get_string('auth_clcdbfielduser_key', 'auth_clcdb'),
            get_string('auth_clcdbfielduser', 'auth_clcdb'), '', PARAM_RAW_TRIMMED));

        // Encoding.
    $settings->add(new admin_setting_configtext('auth_clcdb/extencoding', get_string('auth_clcdbextencoding', 'auth_clcdb'),
            get_string('auth_clcdbextencodinghelp', 'auth_clcdb'), 'utf-8', PARAM_RAW_TRIMMED));

    // DB SQL SETUP.
    $settings->add(new admin_setting_configtext('auth_clcdb/setupsql', get_string('auth_clcdbsetupsql', 'auth_clcdb'),
            get_string('auth_clcdbsetupsqlhelp', 'auth_clcdb'), '', PARAM_RAW_TRIMMED));

    // Debug ADOOB.
    $settings->add(new admin_setting_configselect('auth_clcdb/debugauthdb',
        new lang_string('auth_clcdbdebugauthdb', 'auth_clcdb'), new lang_string('auth_clcdbdebugauthdbhelp', 'auth_clcdb'),
        0, $yesno));
        
    // Display locking / mapping of profile fields.
    $authplugin = get_auth_plugin('clcdb');
    display_auth_lock_options($settings, $authplugin->authtype, $authplugin->userfields,
            get_string('auth_clcdbextrafields', 'auth_clcdb'),
            true, true, $authplugin->get_custom_user_profile_fields());

}
