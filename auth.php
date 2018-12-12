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
 * Authentication Plugin: External Database Authentication
 *
 * Checks against an external database.
 *
 * @package    auth_clcdb
 * @author     Martin Dougiamas
 * @license    http://www.gnu.org/copyleft/gpl.html GNU Public License
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir.'/authlib.php');

/**
 * External database authentication plugin.
 */
class auth_plugin_clcdb extends auth_plugin_base {

    /**
     * Constructor.
     */
    public function __construct() {
        global $CFG;
        require_once($CFG->libdir.'/adodb/adodb.inc.php');

        $this->authtype = 'clcdb';
        $this->config = get_config('auth_clcdb');
        $this->errorlogtag = '[AUTH DB] ';
        if (empty($this->config->extencoding)) {
            $this->config->extencoding = 'utf-8';
        }
    }

    /**
     * Returns true if the username and password work and false if they are
     * wrong or don't exist.
     *
     * @param string $username The username
     * @param string $password The password
     * @return bool Authentication success or failure.
     */
    public function user_login($username, $password) {
        return false;
    }

    /**
     * Connect to external database.
     *
     * @return ADOConnection
     * @throws moodle_exception
     */
    public function db_init() {
        if ($this->is_configured() === false) {
            throw new moodle_exception('auth_clcdbcantconnect', 'auth_clcdb');
        }

        // Connect to the external database (forcing new connection).
        $authdb = ADONewConnection($this->config->type);
        if (!empty($this->config->debugauthdb)) {
            $authdb->debug = true;
            ob_start(); // Start output buffer to allow later use of the page headers.
        }
        $authdb->Connect($this->config->host, $this->config->user, $this->config->pass, $this->config->name, true);
        $authdb->SetFetchMode(ADODB_FETCH_ASSOC);
        if (!empty($this->config->setupsql)) {
            $authdb->Execute($this->config->setupsql);
        }

        return $authdb;
    }

    /**
     * Returns user attribute mappings between moodle and the external database.
     *
     * @return array
     */
    public function db_attributes() {
        $moodleattributes = array();
        // If we have custom fields then merge them with user fields.
        $customfields = $this->get_custom_user_profile_fields();
        if (!empty($customfields) && !empty($this->userfields)) {
            $userfields = array_merge($this->userfields, $customfields);
        } else {
            $userfields = $this->userfields;
        }

        foreach ($userfields as $field) {
            if (!empty($this->config->{"field_map_$field"})) {
                $moodleattributes[$field] = $this->config->{"field_map_$field"};
            }
        }
        $moodleattributes['username'] = $this->config->fielduser;
        return $moodleattributes;
    }

    /**
     * Reads any other information for a user from external database,
     * then returns it in an array.
     *
     * @param string $username
     * @return array
     */
    public function get_userinfo($username) {
        global $CFG;

        $extusername = core_text::convert($username, 'utf-8', $this->config->extencoding);

        $authdb = $this->db_init();

        // Array to map local fieldnames we want, to external fieldnames.
        $selectfields = $this->db_attributes();

        $result = array();
        // If at least one field is mapped from external db, get that mapped data.
        if ($selectfields) {
            $select = array();
            $fieldcount = 0;
            foreach ($selectfields as $localname => $externalname) {
                // Without aliasing, multiple occurrences of the same external
                // name can coalesce in only occurrence in the result.
                $select[] = "$externalname AS F".$fieldcount;
                $fieldcount++;
            }
            $select = implode(', ', $select);
            $sql = "SELECT $select
                      FROM {$this->config->table}
                     WHERE {$this->config->fielduser} = '".$this->ext_addslashes($extusername)."'";

            if ($rs = $authdb->Execute($sql)) {
                if (!$rs->EOF) {
                    $fields = $rs->FetchRow();
                    // Convert the associative array to an array of its values so we don't have to worry about the case of its keys.
                    $fields = array_values($fields);
                    foreach (array_keys($selectfields) as $index => $localname) {
                        $value = $fields[$index];
                        $result[$localname] = core_text::convert($value, $this->config->extencoding, 'utf-8');
                    }
                }
                $rs->Close();
            }
        }
        $authdb->Close();
        return $result;
    }

    /**
     * Change a user's password.
     *
     * @param  stdClass  $user      User table object
     * @param  string  $newpassword Plaintext password
     * @return bool                 True on success
     */
    public function user_update_password($user, $newpassword) {
            return false;
    }

    /**
     * Synchronizes user from external db to moodle user table.
     *
     * Sync should be done by using idnumber attribute, not username.
     * You need to pass firstsync parameter to function to fill in
     * idnumbers if they don't exists in moodle user table.
     *
     * Syncing users removes (disables) users that don't exists anymore in external db.
     * Creates new users and updates coursecreator status of users.
     *
     * This implementation is simpler but less scalable than the one found in the LDAP module.
     *
     * @param progress_trace $trace
     * @param bool $do_updates  Optional: set to true to force an update of existing accounts
     * @return int 0 means success, 1 means failure
     */
    public function sync_users(progress_trace $trace) {
        global $CFG, $DB;

        require_once($CFG->dirroot . '/user/lib.php');

        // List external users.
        $userlist = $this->get_userlist();
        if (!count($userlist)) {
            // Exit right here, nothing else to do.
            $trace->finished();
            return true;
        }
        // Narrow down what fields we need to update.
        $allkeys = array_keys(get_object_vars($this->config));
        $updatekeys = array();
        foreach ($allkeys as $key) {
            if (preg_match('/^field_updatelocal_(.+)$/', $key, $match)) {
                if ($this->config->{$key} === 'onlogin') {
                    array_push($updatekeys, $match[1]); // The actual key name.
                }
            }
        }
        unset($allkeys); unset($key);

        // Only go ahead if we actually have fields to update locally.
        if (!empty($updatekeys)) {
            $updateusers = array();
            // All the drivers can cope with chunks of 10,000. See line 4491 of lib/dml/tests/dml_est.php.
            $userlistchunks = array_chunk($userlist , 10000);
            foreach ($userlistchunks as $userlistchunk) {
                list($insql, $params) = $DB->get_in_or_equal($userlistchunk, SQL_PARAMS_NAMED, 'u', true);
                $params['mnethostid'] = $CFG->mnet_localhost_id;
                $sql = "SELECT u.id, u.username, u.suspended
                      FROM {user} u
                     WHERE u.deleted = 0 AND u.mnethostid = :mnethostid AND u.username {$insql}";
                $updateusers = $updateusers + $DB->get_records_sql($sql, $params);
            }

            if ($updateusers) {
                $trace->output("User entries to update: ".count($updateusers));

                foreach ($updateusers as $user) {
                    if ($this->update_user_record($user->username, $updatekeys, false, (bool) $user->suspended)) {
                        $trace->output(get_string('auth_clcdbupdatinguser', 'auth_clcdb',
                        array('name' => $user->username, 'id' => $user->id)), 1);
                    } else {
                        $trace->output(get_string('auth_clcdbupdatinguser', 'auth_clcdb',
                        array('name' => $user->username, 'id' => $user->id))." - ".get_string('skipped'), 1);
                    }
                }
                unset($updateusers);
            }
        }

        $trace->finished();
        return true;
    }

    public function user_exists($username) {

        // Init result value.
        $result = false;

        $extusername = core_text::convert($username, 'utf-8', $this->config->extencoding);

        $authdb = $this->db_init();

        $rs = $authdb->Execute("SELECT *
                                  FROM {$this->config->table}
                                 WHERE {$this->config->fielduser} = '".$this->ext_addslashes($extusername)."' ");

        if (!$rs) {
            print_error('auth_clcdbcantconnect', 'auth_clcdb');
        } else if (!$rs->EOF) {
            // User exists externally.
            $result = true;
        }

        $authdb->Close();
        return $result;
    }


    public function get_userlist() {

        // Init result value.
        $result = array();

        $authdb = $this->db_init();

        // Fetch userlist.
        $rs = $authdb->Execute("SELECT {$this->config->fielduser}
                                  FROM {$this->config->table} ");

        if (!$rs) {
            print_error('auth_clcdbcantconnect', 'auth_clcdb');
        } else if (!$rs->EOF) {
            while ($rec = $rs->FetchRow()) {
                $rec = array_change_key_case((array)$rec, CASE_LOWER);
                array_push($result, $rec[strtolower($this->config->fielduser)]);
            }
        }

        $authdb->Close();
        return $result;
    }

    /**
     * Reads user information from DB and return it in an object.
     *
     * @param string $username username
     * @return array
     */
    public function get_userinfo_asobj($username) {
        $userarray = truncate_userinfo($this->get_userinfo($username));
        $user = new stdClass();
        foreach ($userarray as $key => $value) {
            $user->{$key} = $value;
        }
        return $user;
    }
    /**
     * Called when the user record is updated.
     * Modifies user in external database. It takes olduser (before changes) and newuser (after changes)
     * compares information saved modified information to external db.
     *
     * @param stdClass $olduser     Userobject before modifications
     * @param stdClass $newuser     Userobject new modified userobject
     * @return boolean result
     *
     */
    public function user_update($olduser, $newuser) {
        if (isset($olduser->username) and isset($newuser->username) and $olduser->username != $newuser->username) {
            return false;
        }

        $curruser = $this->get_userinfo($olduser->username);
        if (empty($curruser)) {
            return false;
        }

        $extusername = core_text::convert($olduser->username, 'utf-8', $this->config->extencoding);

        $authdb = $this->db_init();

        $update = array();
        foreach ($curruser as $key => $value) {
            if ($key == 'username') {
                continue; // Skip this.
            }
            if (empty($this->config->{"field_updateremote_$key"})) {
                continue; // Remote update not requested.
            }
            if (!isset($newuser->$key)) {
                continue;
            }
            $nuvalue = $newuser->$key;
            // Support for textarea fields.
            if (isset($nuvalue['text'])) {
                $nuvalue = $nuvalue['text'];
            }
            if ($nuvalue != $value) {
                $update[] = $this->config->{"field_map_$key"}."='".
                $this->ext_addslashes(core_text::convert($nuvalue, 'utf-8', $this->config->extencoding))."'";
            }
        }
        if (!empty($update)) {
            $authdb->Execute("UPDATE {$this->config->table}
                                 SET ".implode(',', $update)."
                               WHERE {$this->config->fielduser}='".$this->ext_addslashes($extusername)."'");
        }
        $authdb->Close();
        return true;
    }

    public function prevent_local_passwords() {
        return !$this->is_internal();
    }

    /**
     * Returns true if this authentication plugin is "internal".
     *
     * Internal plugins use password hashes from Moodle user table for authentication.
     *
     * @return bool
     */
    public function is_internal() {
        if (!isset($this->config->passtype)) {
            return true;
        }
        return ($this->config->passtype === 'internal');
    }

    /**
     * Returns false if this plugin is enabled but not configured.
     *
     * @return bool
     */
    public function is_configured() {
        if (!empty($this->config->type)) {
            return true;
        }
        return false;
    }

    /**
     * Indicates if moodle should automatically update internal user
     * records with data from external sources using the information
     * from auth_plugin_base::get_userinfo().
     *
     * @return bool true means automatically copy data from ext to user table
     */
    public function is_synchronised_with_external() {
        return true;
    }

    /**
     * Returns true if this authentication plugin can change the user's
     * password.
     *
     * @return bool
     */
    public function can_change_password() {
        return false;
    }

    /**
     * Returns the URL for changing the user's pw, or empty if the default can
     * be used.
     *
     * @return moodle_url
     */
    public function change_password_url() {
        return null;
    }

    /**
     * Returns true if plugin allows resetting of internal password.
     *
     * @return bool
     */
    public function can_reset_password() {
        return false;
    }

    /**
     * Add slashes, we can not use placeholders or system functions.
     *
     * @param string $text
     * @return string
     */
    public function ext_addslashes($text) {
        if (empty($this->config->sybasequoting)) {
            $text = str_replace('\\', '\\\\', $text);
            $text = str_replace(array('\'', '"', "\0"), array('\\\'', '\\"', '\\0'), $text);
        } else {
            $text = str_replace("'", "''", $text);
        }
        return $text;
    }

    /**
     * Test if settings are ok, print info to output.
     * @private
     */
    public function test_settings() {
        global $CFG, $OUTPUT;

        // NOTE: this is not localised intentionally, admins are supposed to understand English at least a bit...

        raise_memory_limit(MEMORY_HUGE);

        if (empty($this->config->table)) {
            echo $OUTPUT->notification('External table not specified.', 'notifyproblem');
            return;
        }

        if (empty($this->config->fielduser)) {
            echo $OUTPUT->notification('External user field not specified.', 'notifyproblem');
            return;
        }

        $olddebug = $CFG->debug;
        $olddisplay = ini_get('display_errors');
        ini_set('display_errors', '1');
        $CFG->debug = DEBUG_DEVELOPER;
        $olddebugauthdb = $this->config->debugauthdb;
        $this->config->debugauthdb = 1;
        error_reporting($CFG->debug);

        $adodb = $this->db_init();

        if (!$adodb or !$adodb->IsConnected()) {
            $this->config->debugauthdb = $olddebugauthdb;
            $CFG->debug = $olddebug;
            ini_set('display_errors', $olddisplay);
            error_reporting($CFG->debug);
            ob_end_flush();

            echo $OUTPUT->notification('Cannot connect the database.', 'notifyproblem');
            return;
        }

        $rs = $adodb->Execute("SELECT *
                                 FROM {$this->config->table}
                                WHERE {$this->config->fielduser} <> 'random_unlikely_username'"); // Any unlikely name is ok here.

        if (!$rs) {
            echo $OUTPUT->notification('Can not read external table.', 'notifyproblem');

        } else if ($rs->EOF) {
            echo $OUTPUT->notification('External table is empty.', 'notifyproblem');
            $rs->close();

        } else {
            $fieldsobj = $rs->FetchObj();
            $columns = array_keys((array)$fieldsobj);

            echo $OUTPUT->notification('External table contains following columns:<br />'.implode(', ', $columns), 'notifysuccess');
            $rs->close();
        }

        $adodb->Close();

        $this->config->debugauthdb = $olddebugauthdb;
        $CFG->debug = $olddebug;
        ini_set('display_errors', $olddisplay);
        error_reporting($CFG->debug);
        ob_end_flush();
    }

    /**
     * Clean the user data that comes from an external database.
     * @deprecated since 3.1, please use core_user::clean_data() instead.
     * @param array $user the user data to be validated against properties definition.
     * @return stdClass $user the cleaned user data.
     */
    public function clean_data($user) {
        debugging('The method clean_data() has been deprecated, please use core_user::clean_data() instead.',
            DEBUG_DEVELOPER);
        return core_user::clean_data($user);
    }
}
