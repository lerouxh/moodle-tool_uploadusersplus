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
 * Validation pipeline for tool_uploadusersplus.
 *
 * @package    tool_uploadusersplus
 * @copyright  2026 eLearn Solutions
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace tool_uploadusersplus\local;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/user/lib.php');
require_once($CFG->dirroot . '/user/profile/lib.php');
require_once($CFG->libdir . '/enrollib.php');
require_once($CFG->libdir . '/editorlib.php');
require_once($CFG->dirroot . '/cohort/lib.php');
require_once($CFG->dirroot . '/group/lib.php');

/**
 * Validates uploaded CSV content in a strict first pass.
 *
 * @package    tool_uploadusersplus
 * @copyright  2026 eLearn Solutions
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class validator {
    /** @var csv_parser */
    protected csv_parser $parser;
    /** @var resolver */
    protected resolver $resolver;
    /** @var \stdClass */
    protected \stdClass $settings;

    /**
     * Constructor.
     */
    public function __construct() {
        $this->parser = new csv_parser();
        $this->resolver = new resolver();
        $this->settings = helper::get_admin_settings();
    }

    /**
     * Validate CSV content and prepare data for a second-pass import.
     *
     * @param string $content
     * @param \stdClass $formdata
     * @return array
     */
    public function validate(string $content, \stdClass $formdata): array {
        $rowlimit = helper::is_free_version() ? helper::get_free_row_processing_limit() : null;
        $parsed = $this->parser->parse($content, $rowlimit);
        $result = [
            'delimitername' => $parsed['delimitername'],
            'hasblockingerrors' => false,
            'globalerrors' => $parsed['errors'],
            'globalmessages' => [],
            'processinglimited' => !empty($parsed['rowlimitexceeded']),
            'processinglimit' => $parsed['rowlimit'],
            'rows' => [],
            'summary' => [
                'rowsread' => count($parsed['rows']),
                'validrows' => 0,
                'invalidrows' => 0,
                'newusersdetected' => 0,
                'existingusersdetected' => 0,
            ],
        ];

        if (!empty($parsed['errors'])) {
            $result['hasblockingerrors'] = true;
            return $result;
        }

        [$normalisedheaders, $headererrors] = $this->validate_headers($parsed['headers'], $formdata);
        if (!empty($headererrors)) {
            $result['globalerrors'] = array_merge($result['globalerrors'], $headererrors);
            $result['hasblockingerrors'] = true;
            foreach ($parsed['rows'] as $row) {
                $result['rows'][] = $this->create_row_result(
                    $row['line'],
                    '',
                    'invalid',
                    'none',
                    array_map(function(string $message): array {
                        return $this->create_message('file', $message);
                    }, $headererrors)
                );
            }
            $result['summary']['invalidrows'] = count($parsed['rows']);
            return $result;
        }

        $usernamecounts = [];
        foreach ($parsed['rows'] as $row) {
            $assoc = $this->row_to_assoc($row['values'], $normalisedheaders);
            $username = trim($assoc['username'] ?? '');
            if ($username !== '') {
                $username = \core_text::strtolower($username);
                $usernamecounts[$username] = ($usernamecounts[$username] ?? 0) + 1;
            }
        }

        foreach ($parsed['rows'] as $row) {
            $result['rows'][] = $this->validate_row($row, $normalisedheaders, $formdata, $usernamecounts);
        }

        if ($this->has_skipped_missing_required_custom_profile_fields($result['rows'])) {
            $result['globalmessages'][] = get_string('missingrequiredcustomprofilefieldsnotice', 'tool_uploadusersplus');
        }

        $result['globalmessages'] = array_merge(
            $result['globalmessages'],
            $this->get_unknown_profile_datatype_skip_messages($result['rows'])
        );

        foreach ($result['rows'] as $rowresult) {
            if (!empty($rowresult['blocking'])) {
                $result['summary']['invalidrows']++;
                $result['hasblockingerrors'] = true;
                continue;
            }

            $result['summary']['validrows']++;
            if (!empty($rowresult['existinguser'])) {
                $result['summary']['existingusersdetected']++;
            } else if (!empty($rowresult['username'])) {
                $result['summary']['newusersdetected']++;
            }
        }

        return $result;
    }

    /**
     * Validate headers and return their normalised form.
     *
     * @param array $headers
     * @param \stdClass $formdata
     * @return array
     */
    protected function validate_headers(array $headers, \stdClass $formdata): array {
        $errors = [];
        $seen = [];
        $normalised = [];
        $customfields = helper::get_custom_profile_fields();
        $acceptedstandard = array_merge(helper::get_supported_standard_headers(), helper::get_optional_user_fields());
        $requiredheaders = ['username'];

        if (in_array((int)$formdata->uploadtype, [helper::UPLOADTYPE_ADDNEW, helper::UPLOADTYPE_ADDUPDATE], true)) {
            $requiredheaders = array_merge($requiredheaders, ['firstname', 'lastname', 'email']);
        }

        if (helper::upload_requires_password_column($formdata)) {
            $requiredheaders[] = 'password';
        }

        foreach ($headers as $header) {
            $originalheader = trim($header);
            $header = helper::normalise_header($originalheader);

            if ($header === '') {
                $errors[] = get_string('error_unexpectedheader', 'tool_uploadusersplus', $originalheader);
                continue;
            }

            if (array_key_exists($header, $seen)) {
                $errors[] = get_string('error_duplicateheader', 'tool_uploadusersplus', $originalheader);
                continue;
            }

            $valid = in_array($header, $acceptedstandard, true)
                || array_key_exists($header, $customfields)
                || helper::is_course_header($header)
                || helper::is_group_header($header)
                || helper::is_cohort_header($header);

            if (!$valid) {
                $errors[] = get_string('error_unexpectedheader', 'tool_uploadusersplus', $originalheader);
                continue;
            }

            $seen[$header] = true;
            $normalised[] = $header;
        }

        foreach (array_unique($requiredheaders) as $requiredheader) {
            if (!in_array($requiredheader, $normalised, true)) {
                if ($requiredheader === 'password' && helper::upload_requires_password_column($formdata)) {
                    $errors[] = get_string('error_missingpasswordheaderrequiredmode', 'tool_uploadusersplus');
                } else {
                    $errors[] = get_string('error_missingrequiredheader', 'tool_uploadusersplus', $requiredheader);
                }
            }
        }

        foreach ($normalised as $header) {
            if (helper::is_group_header($header)) {
                $index = helper::get_index_from_header($header);
                if (!in_array('course' . $index, $normalised, true)) {
                    $errors[] = get_string('error_missingrequiredheader', 'tool_uploadusersplus', 'course' . $index);
                }
            }
        }

        return [$normalised, $errors];
    }

    /**
     * Validate a single row.
     *
     * @param array $row
     * @param array $headers
     * @param \stdClass $formdata
     * @param array $usernamecounts
     * @return array
     */
    protected function validate_row(array $row, array $headers, \stdClass $formdata, array $usernamecounts): array {
        global $CFG, $DB;

        if (count($row['values']) !== count($headers)) {
            return $this->create_row_result(
                $row['line'],
                '',
                'invalid',
                'none',
                [$this->create_message('row', get_string('error_malformedrow', 'tool_uploadusersplus'))],
                true
            );
        }

        $assoc = $this->row_to_assoc($row['values'], $headers);
        if ($this->is_empty_assoc_row($assoc)) {
            return $this->create_row_result(
                $row['line'],
                '',
                'invalid',
                'none',
                [$this->create_message('row', get_string('error_emptyrow', 'tool_uploadusersplus'))],
                true
            );
        }

        $messages = [];
        $deferredmessages = [];
        $prepared = [
            'user' => [],
            'profilefields' => [],
            'preferences' => [],
            'interests' => null,
            'courseenrolments' => [],
            'cohorts' => [],
            'sendpasswordemail' => false,
            'hasusermodifications' => false,
        ];
        $prepared['user']['username'] = '';

        $username = trim(\core_text::strtolower($assoc['username'] ?? ''));
        if ($username === '') {
            $messages[] = $this->create_message('username', get_string('error_missingvalue', 'tool_uploadusersplus', 'username'));
        } else if ($username !== \core_user::clean_field($username, 'username')) {
            $messages[] = $this->create_message('username', get_string('error_invalidusername', 'tool_uploadusersplus'));
        } else if (($usernamecounts[$username] ?? 0) > 1) {
            $messages[] = $this->create_message(
                'username',
                get_string('error_duplicateusernameinfile', 'tool_uploadusersplus', s($username))
            );
        }

        $existinguser = $username === '' ? null : $this->resolver->resolve_user($username);
        $rowstatus = 'valid';
        $rowaction = 'none';

        if ($existinguser) {
            if (!empty($existinguser->deleted)) {
                $messages[] = $this->create_message('username', get_string('error_deleteduser', 'tool_uploadusersplus'));
            }
        }

        if (empty($messages)) {
            $prepared['user']['username'] = $username;

            if ($existinguser) {
                if (is_siteadmin($existinguser->id) && (int)$formdata->uploadtype !== helper::UPLOADTYPE_ADDNEW) {
                    return $this->create_row_result(
                        $row['line'],
                        $username,
                        'skipped',
                        'none',
                        [$this->create_warning('username', get_string('error_adminupdate', 'tool_uploadusersplus'))],
                        false,
                        null,
                        $existinguser
                    );
                }

                switch ((int)$formdata->uploadtype) {
                    case helper::UPLOADTYPE_ADDNEW:
                        return $this->create_row_result(
                            $row['line'],
                            $username,
                            'skipped',
                            'skipexisting',
                            [$this->create_warning('username', get_string('warning_skipexisting', 'tool_uploadusersplus'))],
                            false,
                            null,
                            $existinguser
                        );
                    case helper::UPLOADTYPE_ADDUPDATE:
                    case helper::UPLOADTYPE_UPDATEONLY:
                        $rowaction = 'update';
                        break;
                }
            } else {
                switch ((int)$formdata->uploadtype) {
                    case helper::UPLOADTYPE_UPDATEONLY:
                        return $this->create_row_result(
                            $row['line'],
                            $username,
                            'skipped',
                            'skipmissing',
                            [$this->create_warning('username', get_string('warning_skipmissing', 'tool_uploadusersplus'))]
                        );
                    case helper::UPLOADTYPE_ADDNEW:
                    case helper::UPLOADTYPE_ADDUPDATE:
                        $rowaction = 'create';
                        break;
                }
            }
        }

        $providedauth = trim($assoc['auth'] ?? '');
        if ($providedauth !== '') {
            if (!in_array($providedauth, get_enabled_auth_plugins(), true)) {
                $messages[] = $this->create_message('auth', get_string('error_invalidauth', 'tool_uploadusersplus'));
            }
            $effectiveauth = $providedauth;
        } else {
            $effectiveauth = $existinguser->auth ?? 'manual';
            if (!exists_auth_plugin($effectiveauth)) {
                $messages[] = $this->create_message('auth', get_string('error_invalidauth', 'tool_uploadusersplus'));
            }
        }

        $isinternalauth = empty($messages) ? is_internal_auth($effectiveauth) : true;
        $prepared['user']['auth'] = $effectiveauth;

        if ($rowaction === 'create') {
            foreach (['firstname', 'lastname', 'email'] as $requiredfield) {
                if (!array_key_exists($requiredfield, $assoc) || trim($assoc[$requiredfield]) === '') {
                    $messages[] = $this->create_message(
                        $requiredfield,
                        get_string('error_missingvalue', 'tool_uploadusersplus', $requiredfield)
                    );
                }
            }
        } else if ($rowaction === 'update') {
            foreach (['firstname', 'lastname', 'email'] as $requiredfield) {
                if (array_key_exists($requiredfield, $assoc) && trim($assoc[$requiredfield]) === '') {
                    $messages[] = $this->create_message(
                        $requiredfield,
                        get_string('error_missingvalue', 'tool_uploadusersplus', $requiredfield)
                    );
                }
            }
        }

        foreach (['username', 'firstname', 'lastname', 'email', 'institution', 'department', 'city', 'country', 'lang', 'auth',
                'timezone', 'idnumber', 'icq', 'phone1', 'phone2', 'address', 'url', 'description', 'mailformat',
                'maildisplay', 'maildigest', 'autosubscribe', 'theme'] as $field) {
            if ($field === 'username' || !array_key_exists($field, $assoc)) {
                continue;
            }
            $value = $assoc[$field];
            if ($value === '' && !in_array($field, ['auth', 'country', 'lang', 'timezone', 'idnumber', 'icq', 'phone1', 'phone2',
                    'address', 'url', 'description', 'mailformat', 'maildisplay', 'maildigest', 'autosubscribe', 'theme',
                    'institution', 'department', 'city'], true)) {
                continue;
            }

            $validatedvalue = $this->validate_user_field($field, $value, $messages, $existinguser);
            if ($validatedvalue !== null) {
                $prepared['user'][$field] = $validatedvalue;
                if ($field !== 'auth' || $validatedvalue !== ($existinguser->auth ?? 'manual')) {
                    $prepared['hasusermodifications'] = true;
                }
            }
        }

        if (isset($prepared['user']['email']) && $prepared['user']['email'] !== '') {
            $emailowner = $DB->get_record('user', ['email' => $prepared['user']['email'], 'deleted' => 0], 'id, email');
            if ($emailowner && (!$existinguser || (int)$emailowner->id !== (int)$existinguser->id) && empty($CFG->allowaccountssameemail)) {
                $messages[] = $this->create_message('email', get_string('error_duplicateemail', 'tool_uploadusersplus'));
            }
        }

        if (($rowaction === 'create' && (int)$formdata->newpasswords === helper::NEWPASSWORDS_FILE && $isinternalauth)
                || ($rowaction === 'update'
                    && (int)$formdata->uploadtype === helper::UPLOADTYPE_UPDATEONLY
                    && (int)$formdata->existingpasswords === helper::EXISTINGPASSWORDS_UPDATE
                    && $isinternalauth)) {
            $password = trim($assoc['password'] ?? '');
            if ($rowaction === 'create' && $password === '') {
                $messages[] = $this->create_message('password', get_string('error_passwordrequired', 'tool_uploadusersplus'));
            } else if ($password !== '') {
                $passworduser = (object)[
                    'username' => $username,
                    'firstname' => $prepared['user']['firstname'] ?? ($existinguser->firstname ?? ''),
                    'lastname' => $prepared['user']['lastname'] ?? ($existinguser->lastname ?? ''),
                    'email' => $prepared['user']['email'] ?? ($existinguser->email ?? ''),
                ];
                if (!check_password_policy($password, $unused, $passworduser)) {
                    $messages[] = $this->create_message('password', get_string('error_passwordpolicy', 'tool_uploadusersplus'));
                } else {
                    $prepared['password'] = $password;
                    $prepared['hasusermodifications'] = true;
                }
            }
        } else if ($rowaction === 'create'
                && (int)$formdata->newpasswords === helper::NEWPASSWORDS_CREATE
                && $isinternalauth) {
            $prepared['sendpasswordemail'] = true;
            $prepared['hasusermodifications'] = true;
        }

        if (array_key_exists('htmleditor', $assoc)) {
            $htmleditorvalue = $this->validate_htmleditor_value($assoc['htmleditor'], $messages);
            if ($htmleditorvalue !== null) {
                $prepared['preferences']['htmleditor'] = $htmleditorvalue;
                $prepared['hasusermodifications'] = true;
            }
        }

        if (array_key_exists('interests', $assoc)) {
            $prepared['interests'] = trim($assoc['interests']);
            $prepared['hasusermodifications'] = true;
        }

        $customfields = helper::get_custom_profile_fields();
        $skippedrequiredcustomprofilefieldsmissing = false;
        foreach ($customfields as $header => $fieldobject) {
            if ($rowaction === 'create' && $fieldobject->is_required() && !array_key_exists($header, $assoc)) {
                if (!empty($this->settings->ignorerequiredcustomprofilefieldsmissing)) {
                    $skippedrequiredcustomprofilefieldsmissing = true;
                } else {
                    $messages[] = $this->create_message(
                        $header,
                        get_string('error_profilefieldrequired', 'tool_uploadusersplus', $header)
                    );
                }
                continue;
            }

            if (!array_key_exists($header, $assoc)) {
                continue;
            }

            $value = $this->validate_profile_field($fieldobject, $assoc[$header], $messages, $deferredmessages, $existinguser);
            if ($value !== null) {
                $prepared['profilefields'][$header] = $value;
                $prepared['hasusermodifications'] = true;
            }
        }

        foreach ($headers as $header) {
            if (!helper::is_course_header($header)) {
                continue;
            }

            $index = helper::get_index_from_header($header);
            $coursevalue = trim($assoc['course' . $index] ?? '');
            $groupvalue = trim($assoc['group' . $index] ?? '');

            if ($coursevalue === '' && $groupvalue === '') {
                continue;
            }

            if ($coursevalue === '') {
                $messages[] = $this->create_message('group' . $index, get_string('error_groupwithoutcourse', 'tool_uploadusersplus'));
                continue;
            }

            $resolvedcourse = $this->resolver->resolve_course($coursevalue);
            if (!empty($resolvedcourse['error'])) {
                $messages[] = $this->create_message('course' . $index, $resolvedcourse['error']);
                continue;
            }

            $course = $resolvedcourse['record'];

            if (!$this->validate_enrolment_restriction($course, $messages, 'course' . $index)) {
                continue;
            }

            $group = null;
            if ($groupvalue !== '') {
                $resolvedgroup = $this->resolver->resolve_group((int)$course->id, $groupvalue);
                if (!empty($resolvedgroup['error'])) {
                    $messages[] = $this->create_message('group' . $index, $resolvedgroup['error']);
                    continue;
                }
                $group = $resolvedgroup['record'];
            }

            if (!$existinguser || !is_enrolled(\context_course::instance($course->id), $existinguser->id)) {
                if (!$this->resolver->get_manual_enrol_instance((int)$course->id)) {
                    $messages[] = $this->create_message(
                        'course' . $index,
                        get_string('error_manualenrolmissing', 'tool_uploadusersplus', s($course->shortname))
                    );
                    continue;
                }
            }

            $prepared['courseenrolments'][] = [
                'course' => $course,
                'group' => $group,
            ];
        }

        foreach ($headers as $header) {
            if (!helper::is_cohort_header($header)) {
                continue;
            }

            $cohortvalue = trim($assoc[$header] ?? '');
            if ($cohortvalue === '') {
                continue;
            }

            $resolvedcohort = $this->resolver->resolve_cohort($cohortvalue);
            if (!empty($resolvedcohort['error'])) {
                $messages[] = $this->create_message($header, $resolvedcohort['error']);
                continue;
            }

            $cohort = $resolvedcohort['record'];
            if (!empty($cohort->component)) {
                $messages[] = $this->create_message(
                    $header,
                    get_string('error_externalcohort', 'tool_uploadusersplus', s($cohort->name))
                );
                continue;
            }

            $prepared['cohorts'][] = $cohort;
        }

        $blocking = !empty($messages);
        if (!$blocking && !empty($deferredmessages)) {
            $messages = array_merge($messages, $deferredmessages);
        }
        if (!$blocking && $skippedrequiredcustomprofilefieldsmissing) {
            $messages[] = $this->create_info(
                'profilefields',
                get_string('missingrequiredcustomprofilefieldsnotice', 'tool_uploadusersplus')
            );
        }

        if (!$blocking && empty($prepared['hasusermodifications'])
                && empty($prepared['courseenrolments']) && empty($prepared['cohorts'])
                && $rowaction === 'update') {
            $messages[] = $this->create_info('row', get_string('warning_nochanges', 'tool_uploadusersplus'));
        } else if (!$blocking && $rowaction === 'create') {
            $messages[] = $this->create_info('row', get_string('message_usercreated', 'tool_uploadusersplus'));
        } else if (!$blocking && $rowaction === 'update') {
            $messages[] = $this->create_info('row', get_string('message_userupdated', 'tool_uploadusersplus'));
        }

        return $this->create_row_result(
            $row['line'],
            $username,
            $blocking ? 'invalid' : $rowstatus,
            $rowaction,
            $messages,
            $blocking,
            $blocking ? null : $prepared,
            $existinguser
        );
    }

    /**
     * Convert row values into an associative array using normalised headers.
     *
     * @param array $values
     * @param array $headers
     * @return array
     */
    protected function row_to_assoc(array $values, array $headers): array {
        $assoc = [];
        foreach ($headers as $index => $header) {
            $value = $values[$index] ?? '';
            $assoc[$header] = is_string($value) ? trim($value) : '';
        }
        return $assoc;
    }

    /**
     * Determine whether a row is effectively empty.
     *
     * @param array $assoc
     * @return bool
     */
    protected function is_empty_assoc_row(array $assoc): bool {
        foreach ($assoc as $value) {
            if ($value !== '') {
                return false;
            }
        }
        return true;
    }

    /**
     * Validate a user table field.
     *
     * @param string $field
     * @param string $value
     * @param array $messages
     * @param \stdClass|null $existinguser
     * @return mixed
     */
    protected function validate_user_field(string $field, string $value, array &$messages, ?\stdClass $existinguser) {
        global $CFG;

        switch ($field) {
            case 'email':
                if ($value === '' || !validate_email($value)) {
                    $messages[] = $this->create_message($field, get_string('error_invalidemailfield', 'tool_uploadusersplus'));
                    return null;
                }
                return $value;

            case 'country':
                if ($value === '') {
                    return '';
                }
                $choices = \core_user::get_property_choices('country');
                if (!array_key_exists($value, $choices)) {
                    $messages[] = $this->create_message($field, get_string('error_invalidcountry', 'tool_uploadusersplus'));
                    return null;
                }
                return $value;

            case 'lang':
                if ($value === '') {
                    return '';
                }
                if (!get_string_manager()->translation_exists($value)) {
                    $messages[] = $this->create_message($field, get_string('error_invalidlang', 'tool_uploadusersplus'));
                    return null;
                }
                return $value;

            case 'auth':
                if ($value === '') {
                    return $existinguser->auth ?? 'manual';
                }
                if (!in_array($value, get_enabled_auth_plugins(), true)) {
                    $messages[] = $this->create_message($field, get_string('error_invalidauth', 'tool_uploadusersplus'));
                    return null;
                }
                return $value;

            case 'timezone':
                if ($value === '') {
                    return '';
                }
                if (isset($CFG->forcetimezone) && (string)$CFG->forcetimezone !== '99' && (string)$value !== (string)$CFG->forcetimezone) {
                    $messages[] = $this->create_message($field, get_string('error_invalidtimezone', 'tool_uploadusersplus'));
                    return null;
                }
                $choices = \core_date::get_list_of_timezones(null, true);
                if (!array_key_exists($value, $choices)) {
                    $messages[] = $this->create_message($field, get_string('error_invalidtimezone', 'tool_uploadusersplus'));
                    return null;
                }
                return $value;

            case 'theme':
                if ($value === '') {
                    return '';
                }
                if (empty($CFG->allowuserthemes)) {
                    $messages[] = $this->create_message($field, get_string('error_invalidtheme', 'tool_uploadusersplus'));
                    return null;
                }
                $themes = get_list_of_themes();
                if (!array_key_exists($value, $themes)) {
                    $messages[] = $this->create_message($field, get_string('error_invalidtheme', 'tool_uploadusersplus'));
                    return null;
                }
                return $value;

            case 'mailformat':
                return $this->validate_choice_field($field, $value, ['0', '1'], $messages, 'error_invalidmailformat');

            case 'maildisplay':
                return $this->validate_choice_field($field, $value, ['0', '1', '2'], $messages, 'error_invalidmaildisplay');

            case 'maildigest':
                return $this->validate_choice_field($field, $value, ['0', '1', '2'], $messages, 'error_invalidmaildigest');

            case 'autosubscribe':
                return $this->validate_choice_field($field, $value, ['0', '1'], $messages, 'error_invalidautosubscribe');

            default:
                if ($value === '') {
                    return '';
                }

                $cleaned = \core_user::clean_field($value, $field);
                if ((string)$cleaned !== (string)$value) {
                    $messages[] = $this->create_message(
                        $field,
                        get_string('error_invalidfieldvalue', 'tool_uploadusersplus', $field)
                    );
                    return null;
                }
                return $cleaned;
        }
    }

    /**
     * Validate a simple enumerated field.
     *
     * @param string $field
     * @param string $value
     * @param array $allowedvalues
     * @param array $messages
     * @param string $stringkey
     * @return string|null
     */
    protected function validate_choice_field(
        string $field,
        string $value,
        array $allowedvalues,
        array &$messages,
        string $stringkey
    ): ?string {
        if ($value === '') {
            return '';
        }

        if (!in_array($value, $allowedvalues, true)) {
            $messages[] = $this->create_message($field, get_string($stringkey, 'tool_uploadusersplus'));
            return null;
        }

        return $value;
    }

    /**
     * Determine whether any row skipped required custom profile fields because the setting allows it.
     *
     * @param array $rows
     * @return bool
     */
    protected function has_skipped_missing_required_custom_profile_fields(array $rows): bool {
        $notice = get_string('missingrequiredcustomprofilefieldsnotice', 'tool_uploadusersplus');

        foreach ($rows as $row) {
            foreach ($row['messages'] as $message) {
                if (($message['text'] ?? '') === $notice) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Validate configured enrolment timing restrictions for a course.
     *
     * @param \stdClass $course
     * @param array $messages
     * @param string $field
     * @return bool
     */
    protected function validate_enrolment_restriction(\stdClass $course, array &$messages, string $field): bool {
        if (empty($this->settings->enrolmentrestrictions)) {
            return true;
        }

        if (empty($course->startdate) || !is_numeric($course->startdate)) {
            return true;
        }

        $cutoff = (int)$course->startdate + ((int)$this->settings->enrolmentrestrictiondays * DAYSECS);
        if (time() <= $cutoff) {
            return true;
        }

        $message = (object)[
            'course' => helper::get_course_display_name($course),
            'days' => (int)$this->settings->enrolmentrestrictiondays,
        ];
        $messages[] = $this->create_message(
            $field,
            get_string('error_enrolmentrestriction', 'tool_uploadusersplus', $message)
        );

        return false;
    }

    /**
     * Validate the HTML editor preference.
     *
     * @param string $value
     * @param array $messages
     * @return string|null
     */
    protected function validate_htmleditor_value(string $value, array &$messages): ?string {
        $editors = array_keys(editors_get_enabled());
        if ($value === '') {
            return '';
        }

        if (!in_array($value, $editors, true)) {
            $messages[] = $this->create_message('htmleditor', get_string('error_invalidhtmleditor', 'tool_uploadusersplus'));
            return null;
        }

        return $value;
    }

    /**
     * Validate a custom profile field value.
     *
     * @param \profile_field_base $fieldobject
     * @param string $value
     * @param array $messages
     * @param array $deferredmessages
     * @param \stdClass|null $existinguser
     * @return mixed
     */
    protected function validate_profile_field(
        \profile_field_base $fieldobject,
        string $value,
        array &$messages,
        array &$deferredmessages,
        ?\stdClass $existinguser
    ) {
        $datatype = $fieldobject->field->datatype;
        $header = 'profile_field_' . $fieldobject->get_shortname();
        if (!in_array($datatype, helper::get_supported_profile_datatypes(), true)) {
            if ((int)$this->settings->unknownprofilefielddatatypes === helper::UNKNOWN_PROFILE_DATATYPE_ALLOWRAW) {
                $deferredmessages[] = $this->create_warning(
                    $header,
                    get_string('warning_unknownprofiledatatypevalidationskipped', 'tool_uploadusersplus', (object)[
                        'fieldname' => $this->get_profile_field_display_name($fieldobject),
                        'datatype' => $datatype,
                    ])
                );
                return $value;
            }

            $messages[] = $this->create_message(
                $header,
                get_string('error_unsupportedprofilefielddatatype', 'tool_uploadusersplus', (object)[
                    'fieldname' => $this->get_profile_field_display_name($fieldobject),
                    'datatype' => $datatype,
                ])
            );
            return null;
        }

        if ($value === '') {
            if ($fieldobject->is_required()) {
                $messages[] = $this->create_message(
                    $header,
                    get_string('error_profilefieldrequired', 'tool_uploadusersplus', $header)
                );
            }
            return '';
        }

        switch ($datatype) {
            case 'menu':
            case 'text':
            case 'textonce':
                if (method_exists($fieldobject, 'convert_external_data')) {
                    $value = $fieldobject->convert_external_data($value);
                }
                if ($value === null) {
                    $messages[] = $this->create_message(
                        $header,
                        get_string('error_profilefieldinvalid', 'tool_uploadusersplus', $header)
                    );
                    return null;
                }
                break;

            case 'conditional':
                $options = preg_split('/\R/u', (string)($fieldobject->field->param1 ?? ''), -1, PREG_SPLIT_NO_EMPTY);
                $options = array_map('trim', $options);
                $options = array_values(array_filter($options, static function(string $option): bool {
                    return $option !== '';
                }));
                if (!in_array($value, $options, true)) {
                    $messages[] = $this->create_message(
                        $header,
                        get_string('error_profilefieldinvalid', 'tool_uploadusersplus', $header)
                    );
                    return null;
                }
                break;

            case 'checkbox':
                $boolvalue = $this->parse_boolean_value($value);
                if ($boolvalue === null) {
                    $messages[] = $this->create_message($header, get_string('error_invalidcheckbox', 'tool_uploadusersplus'));
                    return null;
                }
                $value = $boolvalue;
                break;

            case 'datetime':
                if (!preg_match('/^\d{4}-\d{2}-\d{2}(?:-\d{2}-\d{2}-\d{2})?$/', $value) && !ctype_digit($value)) {
                    $messages[] = $this->create_message($header, get_string('error_invaliddatetime', 'tool_uploadusersplus'));
                    return null;
                }
                if (preg_match('/^(?<year>\d{4})-\d{2}-\d{2}/', $value, $matches)) {
                    $year = (int)$matches['year'];
                    if ($year < (int)$fieldobject->field->param1 || $year > (int)$fieldobject->field->param2) {
                        $messages[] = $this->create_message($header, get_string('error_invaliddatetime', 'tool_uploadusersplus'));
                        return null;
                    }
                }
                break;

            case 'textarea':
                $value = ['text' => $value, 'format' => FORMAT_PLAIN];
                break;

            case 'social':
                if ($fieldobject->field->param1 === 'url' && clean_param($value, PARAM_URL) !== $value) {
                    $messages[] = $this->create_message(
                        $header,
                        get_string('error_profilefieldinvalid', 'tool_uploadusersplus', $header)
                    );
                    return null;
                }
                break;
        }

        $userid = $existinguser->id ?? 0;
        $fakeuser = (object)[
            'id' => $userid,
            $fieldobject->inputname => $value,
        ];
        $errors = $fieldobject->edit_validate_field($fakeuser);
        if (!empty($errors)) {
            $messages[] = $this->create_message(
                $header,
                get_string('error_profilefieldinvalid', 'tool_uploadusersplus', $header)
            );
            return null;
        }

        return $value;
    }

    /**
     * Get unique summary messages for unknown datatype validation skips.
     *
     * @param array $rows
     * @return array
     */
    protected function get_unknown_profile_datatype_skip_messages(array $rows): array {
        $prefix = get_string('warning_unknownprofiledatatypevalidationskipped_prefix', 'tool_uploadusersplus');
        $messages = [];

        foreach ($rows as $row) {
            foreach ($row['messages'] as $message) {
                if (($message['level'] ?? '') !== 'warning') {
                    continue;
                }

                if (strpos($message['text'] ?? '', $prefix) !== 0) {
                    continue;
                }

                $messages[$message['text']] = $message['text'];
            }
        }

        return array_values($messages);
    }

    /**
     * Get a readable custom profile field label.
     *
     * @param \profile_field_base $fieldobject
     * @return string
     */
    protected function get_profile_field_display_name(\profile_field_base $fieldobject): string {
        $name = trim((string)($fieldobject->field->name ?? ''));
        if ($name !== '') {
            return $name;
        }

        return 'profile_field_' . $fieldobject->get_shortname();
    }

    /**
     * Parse a user-friendly boolean value.
     *
     * @param string $value
     * @return int|null
     */
    protected function parse_boolean_value(string $value): ?int {
        $normalised = \core_text::strtolower(trim($value));
        if (in_array($normalised, ['1', 'yes', 'true'], true)) {
            return 1;
        }
        if (in_array($normalised, ['0', 'no', 'false'], true)) {
            return 0;
        }
        return null;
    }

    /**
     * Create a standard row result structure.
     *
     * @param int $line
     * @param string $username
     * @param string $status
     * @param string $action
     * @param array $messages
     * @param bool $blocking
     * @param array|null $prepared
     * @param \stdClass|null $existinguser
     * @return array
     */
    protected function create_row_result(
        int $line,
        string $username,
        string $status,
        string $action,
        array $messages,
        bool $blocking = false,
        ?array $prepared = null,
        ?\stdClass $existinguser = null
    ): array {
        return [
            'line' => $line,
            'username' => $username,
            'status' => $status,
            'statuslabel' => get_string('status_' . $status, 'tool_uploadusersplus'),
            'action' => $action,
            'actionlabel' => get_string('action_' . $action, 'tool_uploadusersplus'),
            'messages' => $messages,
            'blocking' => $blocking,
            'prepared' => $prepared,
            'existinguser' => $existinguser,
        ];
    }

    /**
     * Create an error message entry.
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
            'level' => 'error',
        ];
    }

    /**
     * Create a warning message entry.
     *
     * @param string $field
     * @param string $text
     * @return array
     */
    protected function create_warning(string $field, string $text): array {
        return [
            'field' => $field,
            'fieldlabel' => $field,
            'text' => $text,
            'level' => 'warning',
        ];
    }

    /**
     * Create an informational message entry.
     *
     * @param string $field
     * @param string $text
     * @return array
     */
    protected function create_info(string $field, string $text): array {
        return [
            'field' => $field,
            'fieldlabel' => $field,
            'text' => $text,
            'level' => 'info',
        ];
    }
}
