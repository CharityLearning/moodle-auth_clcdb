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
 * Strings for component 'auth_clcdb', language 'en'.
 *
 * @package   auth_clcdb
 * @copyright 1999 onwards Martin Dougiamas  {@link http://moodle.com}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

$string['auth_clcdbcantconnect'] = 'Could not connect to the specified authentication database...';
$string['auth_clcdbdebugauthdb'] = 'Debug ADOdb';
$string['auth_clcdbdebugauthdbhelp'] = 'Debug ADOdb connection to Database User Information Sync - use when getting empty page during login. Not suitable for production sites.';
$string['auth_clcdbdeleteuser'] = 'Deleted user {$a->name} id {$a->id}';
$string['auth_clcdbdeleteusererror'] = 'Error deleting user {$a}';
$string['auth_clcdbdescription'] = 'This method is used to sync information from an external table to any user.';
$string['auth_clcdbextencoding'] = 'External db encoding';
$string['auth_clcdbextencodinghelp'] = 'Encoding used in Database User Information Sync';
$string['auth_clcdbextrafields'] = 'These fields are optional.  You can choose to pre-fill some Moodle user fields with information from the <b>Database User Information Sync fields</b> that you specify here. <p>If you leave these blank, then defaults will be used.</p><p>In either case, the user will be able to edit all of these fields after they log in.</p>';
$string['auth_clcdbfieldpass'] = 'Name of the field containing passwords';
$string['auth_clcdbfieldpass_key'] = 'Password field';
$string['auth_clcdbfielduser'] = 'Name of the field containing usernames';
$string['auth_clcdbfielduser_key'] = 'Username field';
$string['auth_clcdbhost'] = 'The computer hosting the database server. Use a system DSN entry if using ODBC. Use a PDO DSN entry if using PDO.';
$string['auth_clcdbhost_key'] = 'Host';
$string['auth_clcdbchangepasswordurl_key'] = 'Password-change URL';
$string['auth_clcdbinsertuser'] = 'Inserted user {$a->name} id {$a->id}';
$string['auth_clcdbinsertuserduplicate'] = 'Error inserting user {$a->username} - user with this username was already created through \'{$a->auth}\' plugin.';
$string['auth_clcdbinsertusererror'] = 'Error inserting user {$a}';
$string['auth_clcdbname'] = 'Name of the database itself. Leave empty if using an ODBC DSN. Leave empty if your PDO DSN already contains the database name.';
$string['auth_clcdbname_key'] = 'DB name';
$string['auth_clcdbpass'] = 'Password matching the above username';
$string['auth_clcdbpass_key'] = 'Password';
$string['auth_clcdbpasstype'] = '<p>Specify the format that the password field is using.</p> <p>Use \'internal\' if you want the Database User Information Sync to manage usernames and email addresses, but Moodle to manage passwords. If you use \'internal\', you <i>must</i> provide a populated email address field in the Database User Information Sync, and you must execute both admin/cron.php and auth/db/cli/sync_users.php regularly. Moodle will send an email to new users with a temporary password.</p>';
$string['auth_clcdbpasstype_key'] = 'Password format';
$string['auth_clcdbreviveduser'] = 'Revived user {$a->name} id {$a->id}';
$string['auth_clcdbrevivedusererror'] = 'Error reviving user {$a}';
$string['auth_clcdbsaltedcrypt'] = 'Crypt one-way string hashing';
$string['auth_clcdbsetupsql'] = 'SQL setup command';
$string['auth_clcdbsetupsqlhelp'] = 'SQL command for special database setup, often used to setup communication encoding - example for MySQL and PostgreSQL: <em>SET NAMES \'utf8\'</em>';
$string['auth_clcdbsuspenduser'] = 'Suspended user {$a->name} id {$a->id}';
$string['auth_clcdbsuspendusererror'] = 'Error suspending user {$a}';
$string['auth_clcdbsybasequoting'] = 'Use sybase quotes';
$string['auth_clcdbsybasequotinghelp'] = 'Sybase style single quote escaping - needed for Oracle, MS SQL and some other databases. Do not use for MySQL!';
$string['auth_clcdbsyncuserstask'] = 'Synchronise users task';
$string['auth_clcdbtable'] = 'Name of the table in the database';
$string['auth_clcdbtable_key'] = 'Table';
$string['auth_clcdbtype'] = 'The database type (See the <a href="http://phplens.com/adodb/supported.databases.html" target="_blank">ADOdb documentation</a> for details)';
$string['auth_clcdbtype_key'] = 'Database';
$string['auth_clcdbupdateusers'] = 'Update users';
$string['auth_clcdbupdateusers_description'] = 'As well as inserting new users, update existing users.';
$string['auth_clcdbupdatinguser'] = 'Updating user {$a->name} id {$a->id}';
$string['auth_clcdbuser'] = 'Username with read access to the database';
$string['auth_clcdbuser_key'] = 'DB user';
$string['auth_clcdbuserstoadd'] = 'User entries to add: {$a}';
$string['auth_clcdbuserstoremove'] = 'User entries to remove: {$a}';
$string['pluginname'] = 'Database User Information Sync';
$string['privacy:metadata'] = 'The Database User Information Sync authentication plugin does not store any personal data.';
