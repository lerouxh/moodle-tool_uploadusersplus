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
 * Upgrade steps for tool_uploadusersplus.
 *
 * @package    tool_uploadusersplus
 * @copyright  2026 eLearn Solutions
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Upgrade hook for tool_uploadusersplus.
 *
 * @param int $oldversion
 * @return bool
 */
function xmldb_tool_uploadusersplus_upgrade(int $oldversion): bool {
    global $DB;

    $dbman = $DB->get_manager();

    if ($oldversion < 2026050101) {
        $runtable = new xmldb_table('tool_uploadusersplus_run');
        $runtable->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $runtable->add_field('timecreated', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        $runtable->add_field('timemodified', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        $runtable->add_field('userid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        $runtable->add_field('filename', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL, null, null);
        $runtable->add_field('uploadtype', XMLDB_TYPE_CHAR, '20', null, XMLDB_NOTNULL, null, null);
        $runtable->add_field('csvseparator', XMLDB_TYPE_CHAR, '20', null, XMLDB_NOTNULL, null, null);
        $runtable->add_field('usersuploaded', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        $runtable->add_field('usersupdated', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        $runtable->add_field('usersenrolleddistinct', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        $runtable->add_field('enrolmentscreated', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        $runtable->add_field('coursescount', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        $runtable->add_field('cohortscount', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        $runtable->add_field('status', XMLDB_TYPE_CHAR, '20', null, XMLDB_NOTNULL, null, 'success');

        $runtable->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
        $runtable->add_key('userid', XMLDB_KEY_FOREIGN, ['userid'], 'user', ['id']);
        $runtable->add_index('status-timecreated', XMLDB_INDEX_NOTUNIQUE, ['status', 'timecreated']);

        if (!$dbman->table_exists($runtable)) {
            $dbman->create_table($runtable);
        }

        $coursetable = new xmldb_table('tool_uploadusersplus_run_course');
        $coursetable->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $coursetable->add_field('runid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        $coursetable->add_field('courseid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        $coursetable->add_field('courseshortname', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL, null, null);
        $coursetable->add_field('coursefullname', XMLDB_TYPE_TEXT, null, null, null, null, null);
        $coursetable->add_field('usersenrolled', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        $coursetable->add_field('timecreated', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');

        $coursetable->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
        $coursetable->add_key('runid', XMLDB_KEY_FOREIGN, ['runid'], 'tool_uploadusersplus_run', ['id']);
        $coursetable->add_key('courseid', XMLDB_KEY_FOREIGN, ['courseid'], 'course', ['id']);
        $coursetable->add_index('runid-courseid', XMLDB_INDEX_UNIQUE, ['runid', 'courseid']);

        if (!$dbman->table_exists($coursetable)) {
            $dbman->create_table($coursetable);
        }

        upgrade_plugin_savepoint(true, 2026050101, 'tool', 'uploadusersplus');
    }

    if ($oldversion < 2026050102) {
        upgrade_plugin_savepoint(true, 2026050102, 'tool', 'uploadusersplus');
    }

    if ($oldversion < 2026050103) {
        upgrade_plugin_savepoint(true, 2026050103, 'tool', 'uploadusersplus');
    }

    if ($oldversion < 2026050104) {
        upgrade_plugin_savepoint(true, 2026050104, 'tool', 'uploadusersplus');
    }

    if ($oldversion < 2026050105) {
        $runtable = new xmldb_table('tool_uploadusersplus_run');
        $oldfield = new xmldb_field('separator', XMLDB_TYPE_CHAR, '20', null, XMLDB_NOTNULL, null, null);
        $newfield = new xmldb_field('csvseparator', XMLDB_TYPE_CHAR, '20', null, XMLDB_NOTNULL, null, null);

        if ($dbman->table_exists($runtable)
                && $dbman->field_exists($runtable, $oldfield)
                && !$dbman->field_exists($runtable, $newfield)) {
            $dbman->rename_field($runtable, $oldfield, 'csvseparator');
        }

        upgrade_plugin_savepoint(true, 2026050105, 'tool', 'uploadusersplus');
    }

    return true;
}
