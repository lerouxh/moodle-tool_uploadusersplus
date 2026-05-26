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
 * Importer for tool_uploadusersplus.
 *
 * @package    tool_uploadusersplus
 * @copyright  2026 eLearn Solutions
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace tool_uploadusersplus\local;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/user/lib.php');
require_once($CFG->dirroot . '/user/editlib.php');
require_once($CFG->dirroot . '/user/profile/lib.php');
require_once($CFG->dirroot . '/cohort/lib.php');
require_once($CFG->dirroot . '/group/lib.php');
require_once($CFG->libdir . '/enrollib.php');

/**
 * Applies validated changes in a transactional second pass.
 *
 * @package    tool_uploadusersplus
 * @copyright  2026 eLearn Solutions
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class importer {
    /**
     * Apply validated changes.
     *
     * @param array $validationresult
     * @param \stdClass $formdata
     * @return array
     */
    public function import(array $validationresult, \stdClass $formdata): array {
        global $DB;

        $result = [
            'rows' => $validationresult['rows'],
            'globalmessages' => [],
            'rolledback' => false,
            'passwordemailqueue' => [],
            'reportstats' => [
                'enrolmentscreated' => 0,
                'usersenrolleddistinct' => [],
                'courses' => [],
                'cohorts' => [],
            ],
        ];

        $transaction = $DB->start_delegated_transaction();
        try {
            foreach ($result['rows'] as $index => $row) {
                if (!empty($row['blocking']) || empty($row['prepared'])) {
                    continue;
                }

                if (!in_array($row['action'], ['create', 'update'], true)) {
                    continue;
                }

                $prepared = $row['prepared'];
                if ($row['action'] === 'create') {
                    $user = $this->create_user($prepared);
                    $row['existinguser'] = $user;
                    $row['messages'][] = $this->create_message('row', get_string('message_usercreateddone', 'tool_uploadusersplus'));
                } else {
                    $user = $this->update_user($row['existinguser'], $prepared);
                    if (!empty($prepared['hasusermodifications'])) {
                        $row['messages'][] = $this->create_message('row', get_string('message_userupdateddone', 'tool_uploadusersplus'));
                    } else {
                        $row['messages'][] = $this->create_message('row', get_string('message_useruptodate', 'tool_uploadusersplus'));
                    }
                }

                $this->apply_memberships($user, $prepared, $row['messages'], $result['reportstats']);
                if (!empty($prepared['sendpasswordemail'])) {
                    $result['passwordemailqueue'][] = $user;
                    $row['messages'][] = $this->create_message(
                        'password',
                        get_string('message_passwordemailqueued', 'tool_uploadusersplus')
                    );
                }

                $row['status'] = 'success';
                $row['statuslabel'] = get_string('status_success', 'tool_uploadusersplus');
                $result['rows'][$index] = $row;
            }

            $transaction->allow_commit();
        } catch (\Throwable $e) {
            $transaction->rollback($e);
            $result['rolledback'] = true;
            $result['globalmessages'][] = get_string('error_runtimerollback', 'tool_uploadusersplus');
            return $result;
        }

        if (!empty($result['passwordemailqueue'])) {
            $emailwarning = '';
            foreach ($result['passwordemailqueue'] as $user) {
                if (setnew_password_and_mail($user, true)) {
                    set_user_preference('auth_forcepasswordchange', 1, $user);
                } else {
                    $emailwarning = get_string('detailsemailfailed', 'tool_uploadusersplus');
                }
            }
            if ($emailwarning !== '') {
                $result['globalmessages'][] = $emailwarning;
            }
        }

        return $result;
    }

    /**
     * Email the detailed report after a successful run.
     *
     * @param array $report
     * @param string $emailaddress
     * @return array
     */
    public function email_detailed_report(array $report, string $emailaddress): array {
        global $CFG;

        if (!validate_email($emailaddress)) {
            return [
                'sent' => false,
                'error' => get_string('invalidemail'),
            ];
        }

        $builder = new report_builder();
        $recipient = new \stdClass();
        $recipient->id = -1;
        $recipient->username = 'uploadusersplusreport';
        $recipient->email = $emailaddress;
        $recipient->firstname = get_string('pluginname', 'tool_uploadusersplus');
        $recipient->lastname = get_string('reportrecipient', 'tool_uploadusersplus');
        $recipient->firstnamephonetic = '';
        $recipient->lastnamephonetic = '';
        $recipient->middlename = '';
        $recipient->alternatename = '';
        $recipient->maildisplay = 1;
        $recipient->deleted = 0;
        $recipient->suspended = 0;
        $recipient->auth = 'manual';
        $recipient->mnethostid = $CFG->mnet_localhost_id;
        $recipient->confirmed = 1;

        $from = \core_user::get_support_user();
        $subject = get_string('report_detailedemailsubject', 'tool_uploadusersplus');
        $text = $builder->build_email_text($report);

        $sent = email_to_user($recipient, $from, $subject, $text);

        return [
            'sent' => $sent,
            'error' => $sent ? '' : get_string('detailsemailfailed', 'tool_uploadusersplus'),
        ];
    }

    /**
     * Create a new user account.
     *
     * @param array $prepared
     * @return \stdClass
     */
    protected function create_user(array $prepared): \stdClass {
        global $CFG;

        $user = (object)$prepared['user'];
        $user->username = \core_text::strtolower($user->username);
        $user->mnethostid = $CFG->mnet_localhost_id;
        $user->confirmed = 1;
        $user->timecreated = time();
        $user->timemodified = $user->timecreated;
        $user->auth = $user->auth ?? 'manual';
        $user->suspended = isset($user->suspended) ? (int)$user->suspended : 0;

        if (is_internal_auth($user->auth)) {
            if (!empty($prepared['password'])) {
                $user->password = hash_internal_user_password($prepared['password'], true);
            } else {
                $user->password = hash_internal_user_password(generate_password(), true);
            }
        } else {
            $user->password = AUTH_PASSWORD_NOT_CACHED;
        }

        $user->id = user_create_user($user, false, false);
        $this->save_profile_fields($user->id, $prepared['profilefields']);
        $this->save_preferences($user->id, $prepared['preferences']);
        $this->save_interests($user->id, $prepared['interests']);
        \core\event\user_created::create_from_userid($user->id)->trigger();

        return get_complete_user_data('id', $user->id);
    }

    /**
     * Update an existing user account.
     *
     * @param \stdClass $existinguser
     * @param array $prepared
     * @return \stdClass
     */
    protected function update_user(\stdClass $existinguser, array $prepared): \stdClass {
        $changes = (object)['id' => $existinguser->id];
        $doupdate = false;

        foreach ($prepared['user'] as $field => $value) {
            if ($field === 'username') {
                continue;
            }

            if ($field === 'auth' && !is_internal_auth($value)) {
                $changes->password = AUTH_PASSWORD_NOT_CACHED;
            }

            if (!property_exists($existinguser, $field) || (string)$existinguser->{$field} !== (string)$value) {
                $changes->{$field} = $value;
                $doupdate = true;
            }
        }

        if (!empty($prepared['password'])) {
            $changes->password = hash_internal_user_password($prepared['password'], true);
            $doupdate = true;
        }

        if ($doupdate) {
            user_update_user($changes, false, false);
            \core\event\user_updated::create_from_userid($existinguser->id)->trigger();
        }

        if (!empty($prepared['profilefields'])) {
            $this->save_profile_fields($existinguser->id, $prepared['profilefields']);
        }
        if (array_key_exists('htmleditor', $prepared['preferences'])) {
            $this->save_preferences($existinguser->id, $prepared['preferences']);
        }
        if ($prepared['interests'] !== null) {
            $this->save_interests($existinguser->id, $prepared['interests']);
        }

        return get_complete_user_data('id', $existinguser->id);
    }

    /**
     * Save validated custom profile fields.
     *
     * @param int $userid
     * @param array $profilefields
     * @return void
     */
    protected function save_profile_fields(int $userid, array $profilefields): void {
        if (empty($profilefields)) {
            return;
        }

        $profiledata = (object)['id' => $userid];
        foreach ($profilefields as $header => $value) {
            $profiledata->{$header} = $value;
        }
        profile_save_data($profiledata);
    }

    /**
     * Save supported user preferences.
     *
     * @param int $userid
     * @param array $preferences
     * @return void
     */
    protected function save_preferences(int $userid, array $preferences): void {
        if (!array_key_exists('htmleditor', $preferences)) {
            return;
        }

        if ($preferences['htmleditor'] === '') {
            unset_user_preference('htmleditor', $userid);
        } else {
            set_user_preference('htmleditor', $preferences['htmleditor'], $userid);
        }
    }

    /**
     * Save user interests.
     *
     * @param int $userid
     * @param string|null $interests
     * @return void
     */
    protected function save_interests(int $userid, ?string $interests): void {
        if ($interests === null) {
            return;
        }

        $user = (object)['id' => $userid];
        $items = preg_split('/\s*,\s*/', $interests, -1, PREG_SPLIT_NO_EMPTY);
        useredit_update_interests($user, $items);
    }

    /**
     * Apply course and cohort memberships.
     *
     * @param \stdClass $user
     * @param array $prepared
     * @param array $messages
     * @param array $reportstats
     * @return void
     */
    protected function apply_memberships(\stdClass $user, array $prepared, array &$messages, array &$reportstats): void {
        foreach ($prepared['courseenrolments'] as $courseenrolment) {
            $course = $courseenrolment['course'];
            $group = $courseenrolment['group'];
            $context = \context_course::instance($course->id);
            if (!is_enrolled($context, $user->id)) {
                $instance = null;
                foreach (enrol_get_instances($course->id, true) as $enrolinstance) {
                    if ($enrolinstance->enrol === 'manual') {
                        $instance = $enrolinstance;
                        break;
                    }
                }
                if ($instance) {
                    $plugin = enrol_get_plugin('manual');
                    $plugin->enrol_user($instance, $user->id, $instance->roleid ?: null);
                    $reportstats['enrolmentscreated']++;
                    $reportstats['usersenrolleddistinct'][$user->id] = true;
                    if (!isset($reportstats['courses'][$course->id])) {
                        $reportstats['courses'][$course->id] = [
                            'courseshortname' => (string)($course->shortname ?? ''),
                            'coursefullname' => (string)($course->fullname ?? ''),
                            'userids' => [],
                        ];
                    }
                    $reportstats['courses'][$course->id]['userids'][$user->id] = true;
                    $messages[] = $this->create_message(
                        'course',
                        get_string('message_courseenrolmentdone', 'tool_uploadusersplus', s($course->shortname))
                    );
                }
            }

            if ($group && !groups_is_member($group->id, $user->id)) {
                groups_add_member($group->id, $user->id);
                $messages[] = $this->create_message(
                    'group',
                    get_string('message_groupmembershipdone', 'tool_uploadusersplus', s($group->name))
                );
            }
        }

        foreach ($prepared['cohorts'] as $cohort) {
            $reportstats['cohorts'][$cohort->id] = true;
            if (!cohort_is_member($cohort->id, $user->id)) {
                cohort_add_member($cohort->id, $user->id);
                $messages[] = $this->create_message(
                    'cohort',
                    get_string('message_cohortmembershipdone', 'tool_uploadusersplus', s($cohort->name))
                );
            }
        }
    }

    /**
     * Create a report message item.
     *
     * @param string $field
     * @param string $text
     * @return array
     */
    protected function create_message(string $field, string $text): array {
        return [
            'field' => $field,
            'fieldlabel' => $field,
            'text' => $text,
            'level' => 'info',
        ];
    }
}
