/**
 * Upload users PLUS form interactions.
 *
 * @module     tool_uploadusersplus/form
 * @copyright  2026 eLearn Solutions
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

import Notification from 'core/notification';

const selectors = {
    templateButton: '[data-action="tool-uploadusersplus-template"]',
    templateFrame: '[data-region="tool-uploadusersplus-template-frame"]',
    uploadType: '[data-field="uploadtype"]',
    newPasswords: '[data-field="newpasswords"]',
    existingPasswords: '[data-field="existingpasswords"]',
    dryRun: '[data-field="dryrun"]',
    reportType: '[data-field="reporttype"]',
    emailRecipient: '[data-field="emailrecipient"]'
};

const templateFields = [
    'includecustomprofilefields',
    'includeoptionalfields',
    'courseenrolments',
    'numberofcourses',
    'includerolefields',
    'includeenroltimestart',
    'includeenrolperiod',
    'includeenrolstatus',
    'cohortenrolments',
    'numberofcohorts',
    'includedeletedfield',
    'includesuspendedfield'
];

const getFieldContainer = node => {
    if (!node) {
        return null;
    }

    return node.closest('.fitem') || node.closest('.form-group') || node;
};

const toggleField = (selector, visible) => {
    const node = document.querySelector(selector);
    const container = getFieldContainer(node);
    if (!container) {
        return;
    }

    container.hidden = !visible;
};

const buildTemplateUrl = config => {
    if (!config || !config.templateUrl) {
        throw new Error('Missing template URL configuration.');
    }

    const url = new URL(config.templateUrl, window.location.href);

    templateFields.forEach(name => {
        const element = document.querySelector(`[data-template-field="${name}"]`);
        if (!element) {
            return;
        }

        if (element.type === 'checkbox') {
            url.searchParams.set(name, element.checked ? '1' : '0');
            return;
        }

        url.searchParams.set(name, element.value);
    });

    return url.toString();
};

const getReportOptionValues = config => {
    if (!config || !config.reportOptions) {
        return {};
    }

    return {
        summary: String(config.reportOptions.summary.value),
        detailed: String(config.reportOptions.detailed.value),
        email: String(config.reportOptions.email.value)
    };
};

const isDisabledReportValue = (config, value) => {
    return Object.values(config.reportOptions).some(option => {
        return String(option.value) === value && !!option.disabled;
    });
};

const syncReportOptions = config => {
    const dryRun = document.querySelector(selectors.dryRun);
    const reportType = document.querySelector(selectors.reportType);
    if (!dryRun || !reportType || !config || !config.reportOptions) {
        return;
    }

    const values = getReportOptionValues(config);
    const selected = reportType.value;
    let nextValue = selected;

    Array.from(reportType.options).forEach(option => {
        const value = String(option.value);
        const isEmail = value === values.email;
        const isDisabled = isDisabledReportValue(config, value) || (!config.freeVersion && dryRun.checked && isEmail);

        option.disabled = isDisabled;
        option.hidden = !config.freeVersion && dryRun.checked && isEmail;

        if (value === selected && (option.disabled || option.hidden)) {
            nextValue = values.summary;
        }
    });

    if (nextValue !== selected) {
        reportType.value = nextValue;
    }
};

const syncVisibility = config => {
    const uploadType = document.querySelector(selectors.uploadType);
    const dryRun = document.querySelector(selectors.dryRun);
    const reportType = document.querySelector(selectors.reportType);

    if (!uploadType || !dryRun || !reportType) {
        return;
    }

    const values = getReportOptionValues(config);

    syncReportOptions(config);
    toggleField(selectors.newPasswords, uploadType.value !== '3');
    toggleField(selectors.existingPasswords, uploadType.value === '3');
    toggleField(
        selectors.emailRecipient,
        !config.freeVersion && !dryRun.checked && reportType.value === values.email
    );
};

const initTemplateButton = config => {
    const button = document.querySelector(selectors.templateButton);
    const frame = document.querySelector(selectors.templateFrame);
    if (!button) {
        return;
    }

    button.addEventListener('click', event => {
        event.preventDefault();
        event.stopPropagation();
        button.classList.add('disabled');
        button.setAttribute('aria-disabled', 'true');

        try {
            const downloadUrl = buildTemplateUrl(config);
            button.setAttribute('href', downloadUrl);

            if (frame) {
                frame.setAttribute('src', downloadUrl);
            } else {
                window.location.assign(downloadUrl);
            }
        } catch (error) {
            Notification.exception(error);
        } finally {
            button.classList.remove('disabled');
            button.setAttribute('aria-disabled', 'false');
        }
    });
};

const initVisibilityListeners = config => {
    [selectors.uploadType, selectors.dryRun, selectors.reportType].forEach(selector => {
        const node = document.querySelector(selector);
        if (node) {
            node.addEventListener('change', () => {
                syncVisibility(config);
            });
        }
    });
    syncVisibility(config);
};

export const init = config => {
    initTemplateButton(config);
    initVisibilityListeners(config);
};
