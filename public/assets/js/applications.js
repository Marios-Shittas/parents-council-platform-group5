"use strict";

/* â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
   FORM FIELD TEMPLATES  (keyed by formType)
â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€ */
const FORM_FIELDS = {
    general: []
};

const SUBMISSION_FIELD_LABELS = {
    parent_name: '???ï¿½atep???ï¿½? G???a',
    parent_email: 'Email ?p????????a?',
    parent_phone: '????f??? ?p????????a?',
    student_name: '???ï¿½atep???ï¿½? ?a??t?/?a??t??a?',
    student_class: '?ï¿½?ï¿½a / ????',
    manual_application_text: '?e?ï¿½e?? ??t?s??',
    applied_at: '?ï¿½e??ï¿½???a ?p?ï¿½????',
    _submission_mode: '???p?? ?p?ï¿½????'
};

const APP_META = [
    {
        formType: 'general',
        category: '',
        openDate: '',
        closeDate: ''
    }
];

var MAX_SUBMISSION_FILES = 4;
var ALLOWED_SUBMISSION_EXTENSIONS = ['pdf', 'doc', 'docx', 'jpg', 'jpeg', 'png'];
var SUBMISSION_MODE_LABELS = {
    manual: 'Online S?ï¿½p????s?',
    upload: '???ï¿½asï¿½a ???e???'
};

/* â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
   IN-PAGE APPLIED TRACKING  (resets on page reload â€” DB is the source of truth)
â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€ */
const _justApplied = new Set(); // appIds submitted during this page session

// Perigrafei ti leitourgia tou antistoixou tmimatos me emfasi sti statherotita kai tin egkyrotita dedomenon.
function isJsApplied(appId) {
    return _justApplied.has(appId);
}

/* â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
   HELPERS
â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€ */
// Perigrafei ti leitourgia tou antistoixou tmimatos me emfasi sti statherotita kai tin egkyrotita dedomenon.
function getMeta(index) {
    return APP_META[((index % APP_META.length) + APP_META.length) % APP_META.length];
}

// Perigrafei ti leitourgia tou antistoixou tmimatos me emfasi sti statherotita kai tin egkyrotita dedomenon.
function todayLabel() {
    return new Date().toLocaleDateString('el-GR');
}

// Perigrafei ti leitourgia tou antistoixou tmimatos me emfasi sti statherotita kai tin egkyrotita dedomenon.
function escHtml(str) {
    return String(str)
        .replace(/&/g,  '&amp;')
        .replace(/</g,  '&lt;')
        .replace(/>/g,  '&gt;')
        .replace(/"/g,  '&quot;')
        .replace(/'/g,  '&#039;');
}

// Perigrafei ti leitourgia tou antistoixou tmimatos me emfasi sti statherotita kai tin egkyrotita dedomenon.
function normalizeSubmissionMode(mode) {
    return mode === 'manual' ? 'manual' : 'upload';
}

// Perigrafei ti leitourgia tou antistoixou tmimatos me emfasi sti statherotita kai tin egkyrotita dedomenon.
function getSubmissionModeLabel(mode) {
    var normalizedMode = normalizeSubmissionMode(mode);
    return SUBMISSION_MODE_LABELS[normalizedMode] || SUBMISSION_MODE_LABELS.upload;
}

// Perigrafei ti leitourgia tou antistoixou tmimatos me emfasi sti statherotita kai tin egkyrotita dedomenon.
function humanizeSubmissionFieldKey(key) {
    return SUBMISSION_FIELD_LABELS[key] || String(key || '').replace(/_/g, ' ');
}

// Perigrafei ti leitourgia tou antistoixou tmimatos me emfasi sti statherotita kai tin egkyrotita dedomenon.
function getDisplayableSubmissionEntries(submissionDataObj) {
    var data = submissionDataObj && typeof submissionDataObj === 'object' ? submissionDataObj : {};
    var entries = [];
    var preferredOrder = [
        '_submission_mode',
        'parent_name',
        'parent_email',
        'parent_phone',
        'student_name',
        'student_class',
        'manual_application_text',
        'applied_at'
    ];
    var seen = {};

    preferredOrder.forEach(function (key) {
        if (!Object.prototype.hasOwnProperty.call(data, key)) {
            return;
        }

        var rawValue = data[key];
        if (key.charAt(0) === '_' && key !== '_submission_mode') {
            return;
        }
        if (key === 'applied_at') {
            return;
        }
        if (Array.isArray(rawValue) || rawValue == null) {
            return;
        }

        var value = String(rawValue).trim();
        if (!value) {
            return;
        }

        if (key === '_submission_mode') {
            value = getSubmissionModeLabel(value);
        }

        entries.push({
            key: key,
            label: humanizeSubmissionFieldKey(key),
            value: value
        });
        seen[key] = true;
    });

    Object.keys(data).forEach(function (key) {
        if (seen[key] || key.charAt(0) === '_') {
            return;
        }
        if (key === 'applied_at') {
            return;
        }

        var rawValue = data[key];
        if (Array.isArray(rawValue) || rawValue == null) {
            return;
        }

        var value = String(rawValue).trim();
        if (!value) {
            return;
        }

        entries.push({
            key: key,
            label: humanizeSubmissionFieldKey(key),
            value: value
        });
    });

    return entries;
}

// Perigrafei ti leitourgia tou antistoixou tmimatos me emfasi sti statherotita kai tin egkyrotita dedomenon.
function draftStorageKey(appId) {
    return 'applications_draft_' + String(appId);
}

// Perigrafei ti leitourgia tou antistoixou tmimatos me emfasi sti statherotita kai tin egkyrotita dedomenon.
function saveDraft(appId, data) {
    try {
        localStorage.setItem(draftStorageKey(appId), JSON.stringify(data));
        return true;
    } catch (e) {
        return false;
    }
}

// Perigrafei ti leitourgia tou antistoixou tmimatos me emfasi sti statherotita kai tin egkyrotita dedomenon.
function loadDraft(appId) {
    try {
        var raw = localStorage.getItem(draftStorageKey(appId));
        if (!raw) return null;
        return JSON.parse(raw);
    } catch (e) {
        return null;
    }
}

// Perigrafei ti leitourgia tou antistoixou tmimatos me emfasi sti statherotita kai tin egkyrotita dedomenon.
function clearDraft(appId) {
    try {
        localStorage.removeItem(draftStorageKey(appId));
    } catch (e) {
        // Agnoei sfalmata localStorage gia na min diakoptei i roii tou UI.
    }
}

// Perigrafei ti leitourgia tou antistoixou tmimatos me emfasi sti statherotita kai tin egkyrotita dedomenon.
function getAllDraftAppIds() {
    var ids = [];
    var prefix = 'applications_draft_';

    try {
        for (var i = 0; i < localStorage.length; i++) {
            var key = localStorage.key(i);
            if (!key || key.indexOf(prefix) !== 0) continue;

            var appId = parseInt(key.slice(prefix.length), 10);
            if (!Number.isNaN(appId) && appId > 0) {
                ids.push(appId);
            }
        }
    } catch (e) {
        return [];
    }

    return ids;
}

// Perigrafei ti leitourgia tou antistoixou tmimatos me emfasi sti statherotita kai tin egkyrotita dedomenon.
function toggleSubmissionsVisibility() {
    var tbody = document.getElementById('submissions-tbody');
    var table = document.getElementById('submissions-table');
    var noMsg = document.getElementById('no-submissions-msg');
    if (!tbody || !table || !noMsg) return;

    var hasRows = tbody.querySelectorAll('tr').length > 0;
    noMsg.hidden = hasRows;
    table.hidden = !hasRows;
}

// Perigrafei ti leitourgia tou antistoixou tmimatos me emfasi sti statherotita kai tin egkyrotita dedomenon.
function getAppCardById(appId) {
    return document.getElementById('app-card-' + String(appId));
}

// Perigrafei ti leitourgia tou antistoixou tmimatos me emfasi sti statherotita kai tin egkyrotita dedomenon.
function getAppTitleById(appId) {
    var card = getAppCardById(appId);
    if (!card) return '\u0391\u03af\u03c4\u03b7\u03c3\u03b7 #' + String(appId);
    var titleEl = card.querySelector('.application-title');
    return titleEl ? titleEl.textContent.trim() : '\u0391\u03af\u03c4\u03b7\u03c3\u03b7 #' + String(appId);
}

// Perigrafei ti leitourgia tou antistoixou tmimatos me emfasi sti statherotita kai tin egkyrotita dedomenon.
function getDraftStudentInfo(draftData) {
    return {
        studentName: draftData && draftData.student_name ? draftData.student_name : 'â€”',
        studentClass: draftData && draftData.student_class ? draftData.student_class : 'â€”'
    };
}

// Perigrafei ti leitourgia tou antistoixou tmimatos me emfasi sti statherotita kai tin egkyrotita dedomenon.
function upsertDraftSubmissionRow(appId, draftData) {
    var tbody = document.getElementById('submissions-tbody');
    if (!tbody) return;

    var row = tbody.querySelector('tr[data-draft-row="' + String(appId) + '"]');
    if (!row) {
        row = document.createElement('tr');
        row.setAttribute('data-draft-row', String(appId));
        tbody.appendChild(row);
    }

    var appTitle = getAppTitleById(appId);
    row.innerHTML =
        '<td><strong>' + escHtml(appTitle) + '</strong><div class="small text-muted mt-1">\u03a0\u03c1\u03cc\u03c7\u03b5\u03b9\u03c1\u03bf</div></td>' +
        '<td>' + escHtml(todayLabel()) + '</td>';

    toggleSubmissionsVisibility();
}

// Perigrafei ti leitourgia tou antistoixou tmimatos me emfasi sti statherotita kai tin egkyrotita dedomenon.
function removeDraftSubmissionRow(appId) {
    var tbody = document.getElementById('submissions-tbody');
    if (!tbody) return;
    var row = tbody.querySelector('tr[data-draft-row="' + String(appId) + '"]');
    if (row) row.remove();
    toggleSubmissionsVisibility();
}

// Perigrafei ti leitourgia tou antistoixou tmimatos me emfasi sti statherotita kai tin egkyrotita dedomenon.
function renderDraftRowsFromStorage() {
    getAllDraftAppIds().forEach(function (appId) {
        var draft = loadDraft(appId);
        if (draft) {
            upsertDraftSubmissionRow(appId, draft);
        }
    });
}

// Perigrafei ti leitourgia tou antistoixou tmimatos me emfasi sti statherotita kai tin egkyrotita dedomenon.
function restoreDraftToForm(form, draftData) {
    if (!form || !draftData || typeof draftData !== 'object') return;

    Object.keys(draftData).forEach(function (key) {
        if (key === '_formType' || key === '_category') return;

        var field = form.elements.namedItem(key);
        if (!field) return;

        if (field instanceof RadioNodeList) {
            Array.prototype.forEach.call(field, function (el) {
                if (el.value === draftData[key]) {
                    el.checked = true;
                }
            });
            return;
        }

        if (field.type === 'file') {
            return;
        }

        field.value = draftData[key];
    });
}

// Perigrafei ti leitourgia tou antistoixou tmimatos me emfasi sti statherotita kai tin egkyrotita dedomenon.
function parseUiDate(value) {
    var v = String(value || '').trim();
    if (!v) return null;

    var isoMatch = v.match(/^(\d{4})-(\d{2})-(\d{2})$/);
    if (isoMatch) {
        return new Date(Number(isoMatch[1]), Number(isoMatch[2]) - 1, Number(isoMatch[3]));
    }

    var grMatch = v.match(/^(\d{2})\/(\d{2})\/(\d{4})$/);
    if (grMatch) {
        return new Date(Number(grMatch[3]), Number(grMatch[2]) - 1, Number(grMatch[1]));
    }

    return null;
}

// Perigrafei ti leitourgia tou antistoixou tmimatos me emfasi sti statherotita kai tin egkyrotita dedomenon.
function getApplicationWindowStatus(openDate, closeDate) {
    var now = new Date();
    var today = new Date(now.getFullYear(), now.getMonth(), now.getDate());
    var open = parseUiDate(openDate);
    var close = parseUiDate(closeDate);

    if (open && today < open) {
        return 'upcoming';
    }

    if (close && today > close) {
        return 'closed';
    }

    return 'open';
}

/* â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
   BADGE / TAG HTML BUILDERS
â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€ */
// Perigrafei ti leitourgia tou antistoixou tmimatos me emfasi sti statherotita kai tin egkyrotita dedomenon.
function cardStatusBadge(status) {
    var map = {
        open:     { cls: 'app-status-open',    icon: 'fa-unlock-alt', label: '\u0391\u03bd\u03bf\u03b9\u03c7\u03c4\u03ae' },
        upcoming: { cls: 'app-status-closed',  icon: 'fa-hourglass-half', label: '\u0394\u03b5\u03bd \u0386\u03bd\u03bf\u03b9\u03be\u03b5 \u0391\u03ba\u03cc\u03bc\u03b1' },
        closed:   { cls: 'app-status-closed',  icon: 'fa-lock',       label: '\u039a\u03bb\u03b5\u03b9\u03c3\u03c4\u03ae' },
        applied:  { cls: 'app-status-applied', icon: 'fa-check',      label: '\u03a5\u03c0\u03bf\u03b2\u03bb\u03ae\u03b8\u03b7\u03ba\u03b5' }
    };
    var s = map[status] || map.open;
    return '<span class="app-status-badge ' + s.cls + '">' +
           '<i class="fas ' + s.icon + '"></i>' + escHtml(s.label) + '</span>';
}

// Perigrafei ti leitourgia tou antistoixou tmimatos me emfasi sti statherotita kai tin egkyrotita dedomenon.
function submissionStatusBadge(status) {
    var map = {
        submitted: { cls: 'sub-submitted', label: '\u03a5\u03c0\u03bf\u03b2\u03bb\u03ae\u03b8\u03b7\u03ba\u03b5' },
        waiting:   { cls: 'sub-waiting',   label: '\u03a5\u03c0\u03cc \u0395\u03be\u03ad\u03c4\u03b1\u03c3\u03b7' },
        approved:  { cls: 'sub-approved',  label: '\u0395\u03b3\u03ba\u03c1\u03af\u03b8\u03b7\u03ba\u03b5' },
        rejected:  { cls: 'sub-rejected',  label: '\u0391\u03c0\u03bf\u03c1\u03c1\u03af\u03c6\u03b8\u03b7\u03ba\u03b5' }
    };
    var s = map[status] || map.submitted;
    return '<span class="sub-status-badge ' + s.cls + '">' + escHtml(s.label) + '</span>';
}

/* â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
   AUGMENT CARDS  â€“ inject status badge, category tag, and date row into every
   seira imerominias se kathe kartela aitisis pou erxetai apo PHP.
â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€ */
// Perigrafei ti leitourgia tou antistoixou tmimatos me emfasi sti statherotita kai tin egkyrotita dedomenon.
function augmentCards() {
    document.querySelectorAll('.app-card-wrapper').forEach(function (card) {
        var idx        = parseInt(card.dataset.appIndex, 10);
        var appId      = parseInt(card.dataset.appId,    10);
        var dbApplied  = card.dataset.dbApplied === 'true';
        var meta       = getMeta(idx);
        var openDate   = card.dataset.appOpenDate || meta.openDate;
        var closeDate  = card.dataset.appCloseDate || meta.closeDate;
        var applied    = dbApplied || isJsApplied(appId);
        var windowStatus = getApplicationWindowStatus(openDate, closeDate);
        var effectiveStatus = applied ? 'applied' : windowStatus;

        // Badge katastasis
        var badgeSlot = card.querySelector('.js-status-placeholder');
        if (badgeSlot) {
            badgeSlot.innerHTML = cardStatusBadge(effectiveStatus);
        }

        // Etiketa katigorias
        var catSlot = card.querySelector('.js-category-placeholder');
        if (catSlot) {
            catSlot.innerHTML = '';
        }

        // Imerominies
        var datesSlot = card.querySelector('.js-dates-placeholder');
        if (datesSlot) {
            datesSlot.innerHTML =
                '<div class="app-dates-row">' +
                '<span class="app-date-item"><i class="fas fa-calendar-plus text-success"></i>' +
                '<small>\u0386\u03bd\u03bf\u03b9\u03b3\u03bc\u03b1: <strong>' + escHtml(openDate) + '</strong></small></span>' +
                '<span class="app-date-item"><i class="fas fa-calendar-times text-danger"></i>' +
                '<small>\u039b\u03ae\u03be\u03b7: <strong>' + escHtml(closeDate) + '</strong></small></span>' +
                '</div>';
        }

        // Katastasi koumpiou ypovolis
        var btn = card.querySelector('.submit-btn');
        if (!btn) return;

        btn.classList.remove('btn-primary', 'btn-success', 'btn-secondary', 'btn-warning');

        if (applied) {
            btn.innerHTML = '<i class="fas fa-check mr-1"></i> \u03a5\u03c0\u03bf\u03b2\u03bb\u03ae\u03b8\u03b7\u03ba\u03b5';
            btn.classList.add('btn-success');
            btn.disabled = true;
            btn.removeAttribute('data-toggle');
            btn.removeAttribute('data-target');
            btn.dataset.unavailableReason = '\u0388\u03c7\u03b5\u03c4\u03b5 \u03ae\u03b4\u03b7 \u03c5\u03c0\u03bf\u03b2\u03ac\u03bb\u03b5\u03b9 \u03b1\u03c5\u03c4\u03ae \u03c4\u03b7\u03bd \u03b1\u03af\u03c4\u03b7\u03c3\u03b7.';
        } else if (windowStatus === 'upcoming') {
            btn.innerHTML = '<i class="fas fa-hourglass-start mr-1"></i> \u0391\u03ba\u03cc\u03bc\u03b1 \u03b4\u03b5\u03bd \u03ac\u03bd\u03bf\u03b9\u03be\u03b5';
            btn.classList.add('btn-warning');
            btn.disabled = true;
            btn.removeAttribute('data-toggle');
            btn.removeAttribute('data-target');
            btn.dataset.unavailableReason = '\u0397 \u03b1\u03af\u03c4\u03b7\u03c3\u03b7 \u03b4\u03b5\u03bd \u03ad\u03c7\u03b5\u03b9 \u03b1\u03bd\u03bf\u03af\u03be\u03b5\u03b9 \u03b1\u03ba\u03cc\u03bc\u03b1.';
        } else if (windowStatus === 'closed') {
            btn.innerHTML = '<i class="fas fa-lock mr-1"></i> \u039a\u03bb\u03b5\u03b9\u03c3\u03c4\u03ae';
            btn.classList.add('btn-secondary');
            btn.disabled = true;
            btn.removeAttribute('data-toggle');
            btn.removeAttribute('data-target');
            btn.dataset.unavailableReason = '\u0397 \u03c0\u03b5\u03c1\u03af\u03bf\u03b4\u03bf\u03c2 \u03c5\u03c0\u03bf\u03b2\u03bf\u03bb\u03ae\u03c2 \u03ad\u03c7\u03b5\u03b9 \u03bb\u03ae\u03be\u03b5\u03b9.';
        } else {
            btn.innerHTML = '<i class="fas fa-paper-plane mr-1"></i> \u03a5\u03c0\u03bf\u03b2\u03bf\u03bb\u03ae \u0391\u03af\u03c4\u03b7\u03c3\u03b7\u03c2';
            btn.classList.add('btn-primary');
            btn.disabled = false;
            btn.setAttribute('data-toggle', 'modal');
            btn.setAttribute('data-target', '#submitModal');
            delete btn.dataset.unavailableReason;
        }
    });
}

/* â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
   RENDER NEWLY-SUBMITTED ROWS  (rows added this page session, before reload)
   DB-rendered rows are already in the tbody from PHP.
â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€ */
// Perigrafei ti leitourgia tou antistoixou tmimatos me emfasi sti statherotita kai tin egkyrotita dedomenon.
function addSubmissionRow(sub) {
    var tbody = document.getElementById('submissions-tbody');
    var table = document.getElementById('submissions-table');
    var noMsg = document.getElementById('no-submissions-msg');

    if (!tbody) return;

    var uploadedFiles = Array.isArray(sub.uploadedFiles) ? sub.uploadedFiles : [];
    var submissionModeHtml = sub && sub.submissionMode
        ? '<div class="small text-muted mt-1">' + escHtml(getSubmissionModeLabel(sub.submissionMode)) + '</div>'
        : '';
    var filesHtml = uploadedFiles.map(function (fileItem) {
        if (fileItem && typeof fileItem === 'object' && fileItem.url) {
            var itemName = fileItem.name ? String(fileItem.name) : '???e??';
            return '<a href="' + escHtml(String(fileItem.url)) + '" target="_blank" rel="noopener noreferrer" class="submission-file-link d-block small mt-1"><i class="fas fa-paperclip mr-1"></i>' + escHtml(itemName) + '</a>';
        }

        return '<div class="submission-file-link d-block small mt-1"><i class="fas fa-paperclip mr-1"></i>' + escHtml(String(fileItem || '')) + '</div>';
    }).join('');

    var tr = document.createElement('tr');
    tr.dataset.jsRow = sub.appId;
    tr.innerHTML =
        '<td><strong>' + escHtml(sub.appTitle) + '</strong>' + submissionModeHtml + filesHtml + '</td>' +
        '<td>' + escHtml(sub.submittedDate) + '</td>';
    tbody.appendChild(tr);

    if (noMsg)  noMsg.hidden = true;
    if (table)  table.hidden = false;
}

/* â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
   BUILD ONE FORM FIELD
â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€ */
// Perigrafei ti leitourgia tou antistoixou tmimatos me emfasi sti statherotita kai tin egkyrotita dedomenon.
function buildField(field) {
    var req = field.required
        ? '<span class="text-danger ml-1" aria-hidden="true">*</span>'
        : '';
    var control = '';
    var showTopLabel = true;

    if (field.type === 'checkbox') {
        showTopLabel = false;
        control = '<label class="application-checkbox-row" for="field_' + escHtml(field.name) + '">' +
                  '<span class="application-checkbox-text">ï¿½ ' + escHtml(field.label) + req + '</span>' +
                  '<input type="checkbox" id="field_' + escHtml(field.name) + '" class="application-checkbox-input" name="' + field.name + '" value="1"' +
                  (field.required ? ' required' : '') + ' aria-label="' + escHtml(field.label) + '">' +
                  '</label>';
    } else if (field.type === 'file_upload') {
        control = '<input type="file" class="form-control" name="' + field.name + '" accept=".pdf,.doc,.docx,.jpg,.jpeg,.png"' +
                  (field.required ? ' required' : '') + '>';
    } else if (field.type === 'tel') {
        control = '<input type="tel" class="form-control js-phone-only" name="' + field.name + '" inputmode="numeric" pattern="[0-9]{6,15}" minlength="6" maxlength="15" autocomplete="tel" title="???? a???ï¿½?? (6-15 ??f?a)"' +
                  (field.required ? ' required' : '') + '>';
    } else if (field.type === 'select') {
        var opts = (field.options && Array.isArray(field.options)) 
            ? field.options.map(function (o) {
                  return '<option value="' + escHtml(o) + '">' + escHtml(o) + '</option>';
              }).join('')
            : '';
        control = '<select class="form-control" name="' + field.name + '"' +
                  (field.required ? ' required' : '') + '>' +
                  '<option value="" disabled selected>\u0395\u03c0\u03b9\u03bb\u03ad\u03be\u03c4\u03b5...</option>' +
                  opts + '</select>';
    } else if (field.type === 'radio') {
        // Gia checkbox kai radio emfanizei prosorina aplo text input.
        // Pliris ypostirixi apaitei options apo ti vasi dedomenon.
        control = '<input type="text" class="form-control" name="' +
                  field.name + '"' +
                  (field.required ? ' required' : '') + '>';
    } else if (field.type === 'textarea') {
        control = '<textarea class="form-control" name="' + field.name +
                  '" rows="3"' +
                  (field.required ? ' required' : '') + '></textarea>';
    } else {
        control = '<input type="' + field.type + '" class="form-control" name="' +
                  field.name + '"' +
                  (field.required ? ' required' : '') + '>';
    }

    return '<div class="form-group mb-3">' +
           (showTopLabel
               ? '<label class="form-label-custom">' + escHtml(field.label) + req + '</label>'
               : '') +
           control + '</div>';
}

/* â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
   SHOW VIEW MODAL  â€“ handles both PHP-rendered DB rows and JS-submitted rows
â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€ */
// Perigrafei ti leitourgia tou antistoixou tmimatos me emfasi sti statherotita kai tin egkyrotita dedomenon.
function showViewModalFromData(appTitle, submissionDataObj, statusKey, submittedAt) {
    var rows = getDisplayableSubmissionEntries(submissionDataObj).map(function (entry) {
        return '<tr><th class="text-muted font-weight-normal" style="width:45%">' +
               escHtml(entry.label) + '</th><td><strong>' + escHtml(entry.value) + '</strong></td></tr>';
    }).join('');

    var submittedLabel = submittedAt || submissionDataObj.applied_at || 'â€”';
    rows += '<tr><th class="text-muted font-weight-normal" style="width:45%">\u0397\u03bc\u03b5\u03c1\u03bf\u03bc\u03b7\u03bd\u03af\u03b1 \u03a5\u03c0\u03bf\u03b2\u03bf\u03bb\u03ae\u03c2</th><td><strong>' +
            escHtml(submittedLabel) + '</strong></td></tr>';

    var body = document.getElementById('view-modal-body');
    if (body) {
        body.innerHTML =
            '<div class="d-flex justify-content-between align-items-center mb-3">' +
            '<strong class="text-primary">' + escHtml(appTitle) + '</strong>' +
            submissionStatusBadge(statusKey || 'waiting') +
            '</div>' +
            '<table class="table table-sm table-bordered mb-2">' + rows + '</table>';
    }
    $('#viewModal').modal('show');
}

/* â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
   SHOW SUCCESS TOAST
â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€ */
// Perigrafei ti leitourgia tou antistoixou tmimatos me emfasi sti statherotita kai tin egkyrotita dedomenon.
function showToast() {
    var toast = document.getElementById('submission-toast');
    if (!toast) return;
    toast.hidden = false;
    setTimeout(function () { toast.hidden = true; }, 4000);
}

/* â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
   MODAL STATE  â€“ shared between "show" & "submit" handlers
â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€ */
var _modal = {};
var _viewAppId = 0;

/* â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
   DOM READY
â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€ */
document.addEventListener('DOMContentLoaded', function () {
    var canSubmitApplications = document.body && document.body.dataset
        ? document.body.dataset.applicationsCanSubmit === '1'
        : true;
    var applicationsLoginUrl = document.body && document.body.dataset
        ? String(document.body.dataset.applicationsLoginUrl || '')
        : '';
    var bodyScrollLockCount = 0;

    // Perigrafei ti leitourgia tou antistoixou tmimatos me emfasi sti statherotita kai tin egkyrotita dedomenon.
    function cleanupModalArtifacts() {
        var openModals = document.querySelectorAll('.modal.show').length;
        if (openModals > 0) {
            return;
        }

        document.querySelectorAll('.modal-backdrop').forEach(function (el) {
            el.parentNode && el.parentNode.removeChild(el);
        });

        document.body.classList.remove('modal-open');
        document.body.style.paddingRight = '';
    }

    // Perigrafei ti leitourgia tou antistoixou tmimatos me emfasi sti statherotita kai tin egkyrotita dedomenon.
    function lockBodyScroll() {
        if (bodyScrollLockCount === 0) {
            var currentY = window.pageYOffset || document.documentElement.scrollTop || 0;
            document.body.setAttribute('data-lock-scroll-y', String(currentY));
            document.body.style.position = 'fixed';
            document.body.style.top = '-' + currentY + 'px';
            document.body.style.left = '0';
            document.body.style.right = '0';
            document.body.style.width = '100%';
            document.body.style.overflow = 'hidden';
        }

        bodyScrollLockCount++;
    }

    // Perigrafei ti leitourgia tou antistoixou tmimatos me emfasi sti statherotita kai tin egkyrotita dedomenon.
    function unlockBodyScroll() {
        bodyScrollLockCount = Math.max(0, bodyScrollLockCount - 1);
        if (bodyScrollLockCount > 0) {
            return;
        }

        var savedY = parseInt(document.body.getAttribute('data-lock-scroll-y') || '0', 10) || 0;
        document.body.removeAttribute('data-lock-scroll-y');
        document.body.style.position = '';
        document.body.style.top = '';
        document.body.style.left = '';
        document.body.style.right = '';
        document.body.style.width = '';
        document.body.style.overflow = '';
        window.scrollTo(0, savedY);
        cleanupModalArtifacts();
    }

    $('#submitModal, #applicationViewModal, #viewModal').on('show.bs.modal', function () {
        lockBodyScroll();
    });

    $('#submitModal, #applicationViewModal, #viewModal').on('hidden.bs.modal', function () {
        unlockBodyScroll();
        setTimeout(cleanupModalArtifacts, 0);
    });

    // 1. Enisxyei tis karteles me to fortoma tis selidas
    augmentCards();

    // 2. Arxikopoiei tin emfanisi toy pinaka ypovolon (pinakas/noMsg einai idi sosta apo PHP)
    renderDraftRowsFromStorage();
    toggleSubmissionsVisibility();

    var viewModalTitle = document.getElementById('application-view-title');
    var viewModalDesc = document.getElementById('application-view-description');
    var viewModalAttachments = document.getElementById('application-view-attachments-list');
    var viewModalSubmitBtn = document.getElementById('application-view-submit-btn');
    var viewModalFileInput = document.getElementById('application-view-file-input');
    var viewModalSelectedFiles = document.getElementById('application-view-selected-files');
    var viewManualForm = document.getElementById('application-view-manual-form');
    var viewManualFields = document.getElementById('application-view-manual-fields');
    var viewModeCards = Array.from(document.querySelectorAll('#application-submit-methods [data-submit-mode]'));
    var viewManualPanel = document.getElementById('application-view-manual-panel');
    var viewUploadPanel = document.getElementById('application-view-upload-panel');
    var unavailableMessageEl = document.getElementById('application-unavailable-message');
    var applicationNoticeBox = document.getElementById('application-notice-box');
    var applicationNoticeBackdrop = document.getElementById('application-notice-backdrop');
    var applicationNoticeMessage = document.getElementById('application-notice-message');
    var applicationNoticeClose = document.getElementById('application-notice-close');

    document.addEventListener('input', function (event) {
        var target = event.target;
        if (!target || !target.classList || !target.classList.contains('js-phone-only')) {
            return;
        }

        var digitsOnly = String(target.value || '').replace(/[^0-9]/g, '');
        if (digitsOnly !== target.value) {
            target.value = digitsOnly;
        }
    });

    // Perigrafei ti leitourgia tou antistoixou tmimatos me emfasi sti statherotita kai tin egkyrotita dedomenon.
    function hideCenterNotice() {
        if (applicationNoticeBox) {
            applicationNoticeBox.classList.remove('is-visible');
        }
        if (applicationNoticeBackdrop) {
            applicationNoticeBackdrop.classList.remove('is-visible');
        }
    }

    // Perigrafei ti leitourgia tou antistoixou tmimatos me emfasi sti statherotita kai tin egkyrotita dedomenon.
    function showCenterNotice(messageText) {
        if (!applicationNoticeBox || !applicationNoticeMessage || !applicationNoticeBackdrop) {
            window.alert(messageText || 'Sf??ï¿½a.');
            return;
        }

        applicationNoticeMessage.textContent = messageText || 'Sf??ï¿½a.';
        applicationNoticeBackdrop.classList.add('is-visible');
        applicationNoticeBox.classList.add('is-visible');

        if (applicationNoticeClose) {
            applicationNoticeClose.focus();
        }
    }

    if (applicationNoticeClose) {
        applicationNoticeClose.addEventListener('click', hideCenterNotice);
    }

    if (applicationNoticeBackdrop) {
        applicationNoticeBackdrop.addEventListener('click', hideCenterNotice);
    }

    document.addEventListener('keydown', function (event) {
        if (event.key === 'Escape' && applicationNoticeBox && applicationNoticeBox.classList.contains('is-visible')) {
            hideCenterNotice();
        }
    });

    // Perigrafei ti leitourgia tou antistoixou tmimatos me emfasi sti statherotita kai tin egkyrotita dedomenon.
    function showUnavailableBox(messageText) {
        if (unavailableMessageEl) {
            unavailableMessageEl.textContent = messageText || '\u0397 \u03b1\u03af\u03c4\u03b7\u03c3\u03b7 \u03b4\u03b5\u03bd \u03ad\u03c7\u03b5\u03b9 \u03b1\u03bd\u03bf\u03af\u03be\u03b5\u03b9 \u03b1\u03ba\u03cc\u03bc\u03b1.';
        }
        $('#application-unavailable-modal').modal('show');
    }

    // Perigrafei ti leitourgia tou antistoixou tmimatos me emfasi sti statherotita kai tin egkyrotita dedomenon.
    function normalizeDocPath(rawPath) {
        var prefix = '/parents-council-platform-group5/public/assets/Applications_docs/';
        if (!rawPath) return '';
        if (String(rawPath).indexOf('storage/') === 0) {
            return '/parents-council-platform-group5/' + String(rawPath).replace(/^\/+/, '');
        }
        if (String(rawPath).indexOf('/storage/') === 0) {
            return '/parents-council-platform-group5' + String(rawPath);
        }
        var idx = String(rawPath).indexOf(prefix);
        if (idx !== -1) {
            return prefix + String(rawPath).slice(idx + prefix.length).split(prefix)[0];
        }
        return String(rawPath);
    }

    // Perigrafei ti leitourgia tou antistoixou tmimatos me emfasi sti statherotita kai tin egkyrotita dedomenon.
    function renderSelectedSubmissionFiles() {
        if (!viewModalSelectedFiles || !viewModalFileInput) return;

        var files = Array.from(viewModalFileInput.files || []);
        if (files.length === 0) {
            viewModalSelectedFiles.innerHTML = '';
            return;
        }

        viewModalSelectedFiles.innerHTML = files.map(function (file) {
            return '<li><i class="fas fa-file-alt"></i><span>' + escHtml(file.name) + '</span></li>';
        }).join('');
    }

    // Perigrafei ti leitourgia tou antistoixou tmimatos me emfasi sti statherotita kai tin egkyrotita dedomenon.
    function getFallbackManualFields(formType) {
        return FORM_FIELDS[formType] || FORM_FIELDS.general || [];
    }

    // Perigrafei ti leitourgia tou antistoixou tmimatos me emfasi sti statherotita kai tin egkyrotita dedomenon.
    function normalizeManualFieldType(type) {
        var normalizedType = String(type || 'text').toLowerCase();
        if (normalizedType === 'phone') {
            normalizedType = 'tel';
        }
        if (normalizedType === 'file') {
            normalizedType = 'file_upload';
        }

        var allowedTypes = ['text', 'email', 'number', 'date', 'textarea', 'select', 'checkbox', 'radio', 'tel', 'url', 'file_upload'];
        return allowedTypes.indexOf(normalizedType) !== -1 ? normalizedType : 'text';
    }

    // Perigrafei ti leitourgia tou antistoixou tmimatos me emfasi sti statherotita kai tin egkyrotita dedomenon.
    function collectFormUploadFiles(formElement) {
        if (!formElement) {
            return [];
        }

        var allFiles = [];
        formElement.querySelectorAll('input[type="file"]').forEach(function (input) {
            Array.from(input.files || []).forEach(function (file) {
                if (!file || !file.name) {
                    return;
                }
                allFiles.push(file);
            });
        });

        return allFiles;
    }

    // Perigrafei ti leitourgia tou antistoixou tmimatos me emfasi sti statherotita kai tin egkyrotita dedomenon.
    function applyFormDataValuesWithoutFiles(formElement, targetData) {
        if (!formElement || !targetData) {
            return;
        }

        new FormData(formElement).forEach(function (value, key) {
            var isFileValue = typeof File !== 'undefined' && value instanceof File;
            if (isFileValue) {
                if (value.name) {
                    targetData[key] = value.name;
                }
                return;
            }

            targetData[key] = value;
        });
    }

    // Perigrafei ti leitourgia tou antistoixou tmimatos me emfasi sti statherotita kai tin egkyrotita dedomenon.
    function getSubmissionFilesValidationMessage(files, requireAtLeastOne) {
        var selectedFiles = Array.isArray(files) ? files : [];
        if (requireAtLeastOne && selectedFiles.length === 0) {
            return '?a?a?a?? ep????te t??????st?? ??a a??e?? p??? t?? ?p?ï¿½???.';
        }

        if (selectedFiles.length > MAX_SUBMISSION_FILES) {
            return '?p??e?te ?a ep????ete ??? ' + String(MAX_SUBMISSION_FILES) + ' a??e?a.';
        }

        for (var i = 0; i < selectedFiles.length; i++) {
            var fileName = String(selectedFiles[i].name || '');
            var ext = fileName.indexOf('.') !== -1 ? fileName.split('.').pop().toLowerCase() : '';
            if (ALLOWED_SUBMISSION_EXTENSIONS.indexOf(ext) === -1) {
                return '?p?t?ep?ï¿½e??? t?p?? a??e???: pdf, doc, docx, jpg, jpeg, png.';
            }
        }

        return '';
    }

    // Perigrafei ti leitourgia tou antistoixou tmimatos me emfasi sti statherotita kai tin egkyrotita dedomenon.
    function normalizeManualField(field, index) {
        if (!field || typeof field !== 'object') {
            return null;
        }

        var rawName = String(field.name || '').trim();
        var safeName = rawName
            .replace(/\s+/g, '_')
            .replace(/[^a-zA-Z0-9_]/g, '_')
            .replace(/^_+|_+$/g, '');

        if (!safeName) {
            safeName = 'field_' + String((index || 0) + 1);
        }

        var rawLabel = String(field.label || rawName || ('?ed?? ' + String((index || 0) + 1))).trim();
        var rawIcon = String(field.icon || '').trim();

        return {
            name: safeName,
            label: rawLabel || ('?ed?? ' + String((index || 0) + 1)),
            type: normalizeManualFieldType(field.type),
            required: Boolean(field.required),
            icon: rawIcon || 'fa-keyboard',
            options: Array.isArray(field.options) ? field.options : []
        };
    }

    // Perigrafei ti leitourgia tou antistoixou tmimatos me emfasi sti statherotita kai tin egkyrotita dedomenon.
    function fetchManualFieldsForApplication(appId, formType) {
        var fallbackFields = getFallbackManualFields(formType);
        if (!appId) {
            return Promise.resolve(fallbackFields);
        }

        return fetch(window.location.pathname + '?ajax_get_form_fields=1&application_id=' + encodeURIComponent(String(appId)), {
            method: 'GET',
            headers: {
                'Accept': 'application/json'
            }
        })
        .then(function (response) {
            if (!response.ok) {
                throw new Error('Unable to load form fields');
            }
            return response.json();
        })
        .then(function (payload) {
            if (!payload || payload.success !== true || payload.hasCustomFields !== true || !Array.isArray(payload.fields) || payload.fields.length === 0) {
                return fallbackFields;
            }

            var normalizedFields = payload.fields
                .map(function (field, index) {
                    return normalizeManualField(field, index);
                })
                .filter(function (field) {
                    return Boolean(field);
                });

            return normalizedFields.length > 0 ? normalizedFields : fallbackFields;
        })
        .catch(function () {
            return fallbackFields;
        });
    }

    // Perigrafei ti leitourgia tou antistoixou tmimatos me emfasi sti statherotita kai tin egkyrotita dedomenon.
    function renderManualApplicationFields(prefill, fields) {
        if (!viewManualFields) {
            return;
        }

        var resolvedFields = Array.isArray(fields) && fields.length > 0
            ? fields
            : getFallbackManualFields((_modal.meta && _modal.meta.formType) ? _modal.meta.formType : 'general');

        if (!resolvedFields || resolvedFields.length === 0) {
            viewManualFields.innerHTML = '';
            return;
        }

        viewManualFields.innerHTML = resolvedFields.map(buildField).join('');

        if (!prefill) {
            return;
        }

        Object.keys(prefill).forEach(function (key) {
            if (!viewManualForm || !viewManualForm.elements) {
                return;
            }

            var field = viewManualForm.elements.namedItem(key);
            if (!field || field.type === 'file') {
                return;
            }

            if (!String(field.value || '').trim()) {
                field.value = prefill[key];
            }
        });
    }

    // Perigrafei ti leitourgia tou antistoixou tmimatos me emfasi sti statherotita kai tin egkyrotita dedomenon.
    function setApplicationSubmitButtonText(mode) {
        if (!viewModalSubmitBtn) {
            return;
        }

        if (!canSubmitApplications) {
            viewModalSubmitBtn.innerHTML = '<i class="fas fa-exclamation-circle mr-1"></i>???s????? ï¿½? d?a??s?ï¿½?';
            return;
        }

        var normalizedMode = normalizeSubmissionMode(mode);
        viewModalSubmitBtn.innerHTML = normalizedMode === 'manual'
            ? '<i class="fas fa-keyboard mr-1"></i>?p?ï¿½??? Online ??t?s??'
            : '<i class="fas fa-paper-plane mr-1"></i>?p?ï¿½??? ??t?s??';
    }

    // Perigrafei ti leitourgia tou antistoixou tmimatos me emfasi sti statherotita kai tin egkyrotita dedomenon.
    function setApplicationSubmitMode(mode) {
        var normalizedMode = normalizeSubmissionMode(mode);
        _modal.submitMode = normalizedMode;

        viewModeCards.forEach(function (card) {
            var isActive = card.getAttribute('data-submit-mode') === normalizedMode;
            card.classList.toggle('is-active', isActive);
            card.setAttribute('aria-pressed', isActive ? 'true' : 'false');
        });

        if (viewManualPanel) {
            viewManualPanel.classList.toggle('is-active', normalizedMode === 'manual');
        }
        if (viewUploadPanel) {
            viewUploadPanel.classList.toggle('is-active', normalizedMode === 'upload');
        }

        if (normalizedMode === 'manual' && viewModalFileInput) {
            viewModalFileInput.value = '';
            renderSelectedSubmissionFiles();
        }

        setApplicationSubmitButtonText(normalizedMode);
    }

    // Perigrafei ti leitourgia tou antistoixou tmimatos me emfasi sti statherotita kai tin egkyrotita dedomenon.
    function openApplicationViewModal(card) {
        if (!card) return;

        var submitBtn = card.querySelector('.submit-btn');
        if (!submitBtn || submitBtn.disabled) {
            var reason = submitBtn ? (submitBtn.dataset.unavailableReason || '') : '';
            showUnavailableBox(reason || '? a?t?s? de? ??e? a????e? a??ï¿½a.');
            return;
        }

        var appId = parseInt(card.dataset.appId, 10);
        var appIndex = parseInt(card.dataset.appIndex, 10);
        var title = card.dataset.applicationTitle || '\u039b\u03b5\u03c0\u03c4\u03bf\u03bc\u03ad\u03c1\u03b5\u03b9\u03b5\u03c2 \u0391\u03af\u03c4\u03b7\u03c3\u03b7\u03c2';
        var description = card.dataset.applicationDescription || '\u0394\u03b5\u03bd \u03c5\u03c0\u03ac\u03c1\u03c7\u03b5\u03b9 \u03b4\u03b9\u03b1\u03b8\u03ad\u03c3\u03b9\u03bc\u03b7 \u03c0\u03b5\u03c1\u03b9\u03b3\u03c1\u03b1\u03c6\u03ae.';
        var parentEmail = card.dataset.parentEmail || '';
        var docsRaw = card.dataset.applicationDocuments || '[]';
        var docs = [];

        try {
            docs = JSON.parse(docsRaw);
            if (!Array.isArray(docs)) docs = [];
        } catch (err) {
            docs = [];
        }

        var meta = getMeta(appIndex);
        var appOpenDate = card.dataset.appOpenDate || meta.openDate;
        var appCloseDate = card.dataset.appCloseDate || meta.closeDate;
        _viewAppId = appId;
        _modal = {
            appId: appId,
            appTitle: title,
            appIndex: appIndex,
            submitMode: 'upload',
            prefill: {
                parent_email: parentEmail
            },
            meta: {
                formType: meta.formType || 'general',
                category: meta.category || '',
                openDate: appOpenDate,
                closeDate: appCloseDate
            }
        };

        if (viewModalTitle) viewModalTitle.textContent = title;
        if (viewModalDesc) viewModalDesc.textContent = description;

        if (viewModalAttachments) {
            if (docs.length === 0) {
                viewModalAttachments.innerHTML = '<li class="text-muted">\u0394\u03b5\u03bd \u03c5\u03c0\u03ac\u03c1\u03c7\u03bf\u03c5\u03bd \u03c3\u03c5\u03bd\u03b7\u03bc\u03bc\u03ad\u03bd\u03b1 \u03ad\u03b3\u03b3\u03c1\u03b1\u03c6\u03b1.</li>';
            } else {
                viewModalAttachments.innerHTML = docs.map(function (doc) {
                    var rawPath = doc && doc.file_path ? doc.file_path : '';
                    var url = normalizeDocPath(rawPath);
                    var mappedName = doc && doc.display_name ? String(doc.display_name).trim() : '';
                    var name = mappedName || (rawPath ? rawPath.split('/').pop() : '\u0388\u03b3\u03b3\u03c1\u03b1\u03c6\u03bf');
                    return '<li><a href="' + escHtml(url) + '" target="_blank"><i class="fas fa-file-alt mr-1 text-primary"></i>' + escHtml(name) + '</a></li>';
                }).join('');
            }
        }

        if (viewModalFileInput) {
            viewModalFileInput.value = '';
        }
        renderSelectedSubmissionFiles();

        if (viewManualForm) {
            viewManualForm.reset();
        }

        if (viewManualFields) {
            viewManualFields.innerHTML = '<div class="text-muted small py-2">F??t?s? ped??? f??ï¿½a?...</div>';
        }

        _modal.manualFieldsLoading = true;
        _modal.manualFields = getFallbackManualFields(_modal.meta.formType);
        fetchManualFieldsForApplication(appId, _modal.meta.formType).then(function (manualFields) {
            if (!_modal || _modal.appId !== appId) {
                return;
            }

            _modal.manualFieldsLoading = false;
            _modal.manualFields = manualFields;
            renderManualApplicationFields(_modal.prefill, manualFields);
        });

        setApplicationSubmitMode('upload');

        var uploadTitleEl = document.querySelector('#application-view-upload .upload-title span');
        if (uploadTitleEl) {
            uploadTitleEl.textContent = 'Upload ??t?s?? (??? 4 a??e?a)';
        }

        var uploadNoteEl = document.querySelector('#application-view-upload .application-view-upload-note');
        if (uploadNoteEl) {
            uploadNoteEl.innerHTML = '?pa?te?ta? t??????st?? 1 a??e??. ?p?t?ep?ï¿½e??? t?p??: <strong>pdf, doc, docx, jpg, jpeg, png</strong>.';
        }

        if (viewModalSubmitBtn) {
            viewModalSubmitBtn.disabled = false;
            setApplicationSubmitButtonText(_modal.submitMode);
        }

        $('#applicationViewModal').modal('show');
    }

    document.querySelectorAll('.post-open-trigger').forEach(function (card) {
        card.addEventListener('click', function (e) {
            var submitButton = e.target.closest('.submit-btn');
            if (submitButton) {
                if (submitButton.disabled || submitButton.dataset.unavailableReason) {
                    e.preventDefault();
                    e.stopPropagation();
                    showUnavailableBox(submitButton.dataset.unavailableReason || '? a?t?s? de? e??a? d?a??s?ï¿½?.');
                }
                return;
            }
            if (e.target.closest('.application-instruction-link')) return;
            openApplicationViewModal(card);
        });
    });

    document.querySelectorAll('.js-open-submit-link').forEach(function (link) {
        link.addEventListener('click', function (event) {
            event.preventDefault();
            event.stopPropagation();

            var card = link.closest('.post-open-trigger');
            if (!card) return;
            openApplicationViewModal(card);
        });
    });

    viewModeCards.forEach(function (card) {
        card.addEventListener('click', function () {
            var selectedMode = card.getAttribute('data-submit-mode') || 'upload';
            setApplicationSubmitMode(selectedMode);
        });
    });

    if (viewModalFileInput) {
        viewModalFileInput.addEventListener('change', function () {
            var selectedFiles = Array.from(viewModalFileInput.files || []);
            if (selectedFiles.length > MAX_SUBMISSION_FILES) {
                showCenterNotice('?p??e?te ?a ep????ete ??? ' + String(MAX_SUBMISSION_FILES) + ' a??e?a.');
                viewModalFileInput.value = '';
            }

            renderSelectedSubmissionFiles();
        });
    }

    $('#applicationViewModal').on('hidden.bs.modal', function () {
        if (viewModalFileInput) {
            viewModalFileInput.value = '';
        }
        if (viewManualForm) {
            viewManualForm.reset();
        }
        if (viewManualFields) {
            viewManualFields.innerHTML = '';
        }
        if (viewManualPanel) {
            viewManualPanel.classList.remove('is-active');
        }
        if (viewUploadPanel) {
            viewUploadPanel.classList.remove('is-active');
        }
        viewModeCards.forEach(function (card) {
            card.classList.remove('is-active');
            card.setAttribute('aria-pressed', 'false');
        });
        renderSelectedSubmissionFiles();
        _viewAppId = 0;
        _modal = {};
    });

    if (viewModalSubmitBtn) {
        viewModalSubmitBtn.addEventListener('click', function () {
            if (!_modal.appId) return;

            if (!canSubmitApplications) {
                showCenterNotice('? ?p?ï¿½??? de? e??a? d?a??s?ï¿½? a?t? t? st??ï¿½?. ?a?a?a?? a?a?e?ste t? se??da ?a? d???ï¿½?ste ?a??.');
                return;
            }

            var selectedMode = normalizeSubmissionMode(_modal.submitMode);
            var files = viewModalFileInput ? Array.from(viewModalFileInput.files || []) : [];
            var data = {
                applied_at: todayLabel(),
                _formType: (_modal.meta && _modal.meta.formType) ? _modal.meta.formType : 'general',
                _category: (_modal.meta && _modal.meta.category) ? _modal.meta.category : '',
                _submission_mode: selectedMode
            };

            if (selectedMode === 'manual') {
                if (_modal.manualFieldsLoading) {
                    showCenterNotice('G??eta? f??t?s? t?? ped???. ?e??ï¿½??ete ???a de?te???epta ?a? d???ï¿½?ste ?a??.');
                    return;
                }

                if (!viewManualForm) {
                    showCenterNotice('?e? e??a? d?a??s?ï¿½? ? online f??ï¿½a a?t? t? st??ï¿½?.');
                    return;
                }

                if (!viewManualForm.checkValidity()) {
                    viewManualForm.reportValidity();
                    return;
                }

                applyFormDataValuesWithoutFiles(viewManualForm, data);

                files = collectFormUploadFiles(viewManualForm);
                var manualFilesValidationMessage = getSubmissionFilesValidationMessage(files, false);
                if (manualFilesValidationMessage !== '') {
                    showCenterNotice(manualFilesValidationMessage);
                    return;
                }
            } else {
                var uploadFilesValidationMessage = getSubmissionFilesValidationMessage(files, true);
                if (uploadFilesValidationMessage !== '') {
                    showCenterNotice(uploadFilesValidationMessage);
                    return;
                }
            }

            viewModalSubmitBtn.disabled = true;
            viewModalSubmitBtn.innerHTML = '<i class="fas fa-spinner fa-spin mr-1"></i>?p?st???...';

            var body = new FormData();
            body.append('ajax_submit_v2', '1');
            body.append('application_id', String(_modal.appId));
            body.append('submission_mode', selectedMode);
            body.append('submission_data', JSON.stringify(data));
            if (files.length > 0) {
                files.forEach(function (file) {
                    body.append('submission_files[]', file);
                });
            }

            fetch(window.location.pathname, {
                method: 'POST',
                body: body
            })
            .then(function (res) {
                return res.text().then(function (text) {
                    try {
                        return JSON.parse(text);
                    } catch (parseError) {
                        throw new Error(text ? text.slice(0, 260) : 'Empty response');
                    }
                });
            })
            .then(function (json) {
                viewModalSubmitBtn.disabled = false;
                setApplicationSubmitButtonText(selectedMode);

                if (!json.success) {
                    showCenterNotice(json.message || 'Sf??ï¿½a.');
                    return;
                }

                _justApplied.add(_modal.appId);
                clearDraft(_modal.appId);
                removeDraftSubmissionRow(_modal.appId);
                $('#applicationViewModal').modal('hide');
                setTimeout(cleanupModalArtifacts, 120);
                showToast();
                augmentCards();

                addSubmissionRow({
                    appId: _modal.appId,
                    appTitle: _modal.appTitle,
                    submittedDate: new Date().toLocaleDateString('el-GR'),
                    submissionMode: selectedMode,
                    uploadedFiles: Array.isArray(json.uploaded_file_links) ? json.uploaded_file_links : files.map(function (file) {
                        return {
                            name: String(file.name || ''),
                            url: ''
                        };
                    })
                });
            })
            .catch(function () {
                viewModalSubmitBtn.disabled = false;
                setApplicationSubmitButtonText(selectedMode);
                showCenterNotice('Sf??ï¿½a d??t???. ?eï¿½a???e?te ?t? ? d?a??ï¿½?st?? t???e? ?a? d???ï¿½?ste ?a??.');
            });
        });
    }

    /* â”€â”€ Anoigma modal: populate header + dynamic fields â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€ */
    $('#submitModal').on('show.bs.modal', function (event) {
        var btn      = $(event.relatedTarget);
        var appId    = parseInt(btn.data('application-id'), 10);
        var appTitle = btn.data('application-title') || '';
        var appDesc  = btn.data('application-description') || '';
        var appIndex = parseInt(btn.data('app-index'), 10);
        var meta     = getMeta(appIndex) || { formType: 'general', category: '', openDate: '', closeDate: '' };
        var appOpenDate = btn.data('app-open-date') || '';
        var appCloseDate = btn.data('app-close-date') || '';
        var parentEmail = btn.data('parent-email') || '';
        if (appOpenDate) {
            meta.openDate = appOpenDate;
        }
        if (appCloseDate) {
            meta.closeDate = appCloseDate;
        }

        _modal = {
            appId: appId,
            appTitle: appTitle,
            appIndex: appIndex,
            meta: meta,
            submitMode: 'manual',
            prefill: {
                parent_email: parentEmail
            }
        };

        // Keimeno kefalidas
        document.getElementById('modal-title').textContent       = appTitle;
        document.getElementById('modal-description').textContent = appDesc;

        // Anaktisi pediwn custom formas apo ton diakomisti
        fetch(window.location.pathname + '?ajax_get_form_fields=1&application_id=' + appId, {
            method: 'GET',
            headers: {
                'Accept': 'application/json'
            }
        })
        .then(function(response) { return response.json(); })
        .then(function(data) {
            // Xrisimopoiei custom pedia an yparxoun, allios epistrefei sta prokathorismena pedia.
            var fields = (data.hasCustomFields && Array.isArray(data.fields) && data.fields.length > 0)
                ? data.fields
                : (FORM_FIELDS[meta.formType] || FORM_FIELDS.general);

            var container = document.getElementById('modal-dynamic-fields');
            // Prosthetei to pedia arxeiou mesa sti forma.
            container.innerHTML =
                '<form id="application-form" enctype="multipart/form-data">' +
                fields.map(buildField).join('') +
                '<div class="form-group mt-3 mb-0">' +
                '<label class="form-label-custom mb-2">' +
                '<i class="fas fa-paperclip text-primary mr-1"></i>\u03a0\u03c1\u03bf\u03b1\u03b9\u03c1\u03b5\u03c4\u03b9\u03ba\u03cc \u03b1\u03c1\u03c7\u03b5\u03af\u03bf \u03c5\u03c0\u03bf\u03b2\u03bf\u03bb\u03ae\u03c2</label>' +
                '<input type="file" id="modal-submission-file" name="submission_file" class="form-control" accept=".pdf,.doc,.docx,.jpg,.jpeg,.png">' +
                '<small class="text-muted d-block mt-1">\u0395\u03c0\u03b9\u03c4\u03c1\u03b5\u03c0\u03cc\u03bc\u03b5\u03bd\u03bf\u03b9 \u03c4\u03cd\u03c0\u03bf\u03b9: pdf, doc, docx, jpg, jpeg, png.</small>' +
                '</div>' +
                '</form>';

            // Fortonei to apothikevmeno proxeiro gia tin aitisi, an yparxei.
            var form = document.getElementById('application-form');
            var draft = loadDraft(appId);
            if (form && draft) {
                restoreDraftToForm(form, draft);
            } else if (form && _modal.prefill) {
                Object.keys(_modal.prefill).forEach(function (key) {
                    var field = form.elements.namedItem(key);
                    if (field && field.type !== 'file' && !String(field.value || '').trim()) {
                        field.value = _modal.prefill[key];
                    }
                });
            }
        })
        .catch(function(err) {
            // An apotyxei i anaktisi, xrisimopoiei ta prokathorismena pedia.
            console.error('Error fetching custom fields:', err);
            var fields    = FORM_FIELDS[meta.formType] || FORM_FIELDS.general;
            var container = document.getElementById('modal-dynamic-fields');
            container.innerHTML =
                '<form id="application-form" enctype="multipart/form-data">' +
                fields.map(buildField).join('') +
                '<div class="form-group mt-3 mb-0">' +
                '<label class="form-label-custom mb-2">' +
                '<i class="fas fa-paperclip text-primary mr-1"></i>\u03a0\u03c1\u03bf\u03b1\u03b9\u03c1\u03b5\u03c4\u03b9\u03ba\u03cc \u03b1\u03c1\u03c7\u03b5\u03af\u03bf \u03c5\u03c0\u03bf\u03b2\u03bf\u03bb\u03ae\u03c2</label>' +
                '<input type="file" id="modal-submission-file" name="submission_file" class="form-control" accept=".pdf,.doc,.docx,.jpg,.jpeg,.png">' +
                '<small class="text-muted d-block mt-1">\u0395\u03c0\u03b9\u03c4\u03c1\u03b5\u03c0\u03cc\u03bc\u03b5\u03bd\u03bf\u03b9 \u03c4\u03cd\u03c0\u03bf\u03b9: pdf, doc, docx, jpg, jpeg, png.</small>' +
                '</div>' +
                '</form>';

            var form = document.getElementById('application-form');
            var draft = loadDraft(appId);
            if (form && draft) {
                restoreDraftToForm(form, draft);
            } else if (form && _modal.prefill) {
                Object.keys(_modal.prefill).forEach(function (key) {
                    var field = form.elements.namedItem(key);
                    if (field && field.type !== 'file' && !String(field.value || '').trim()) {
                        field.value = _modal.prefill[key];
                    }
                });
            }
        });
    });

    /* â”€â”€ Clear form on modal kleisimo â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€ */
    $('#submitModal').on('hidden.bs.modal', function () {
        var container = document.getElementById('modal-dynamic-fields');
        if (container) container.innerHTML = '';
        var fileInput = document.getElementById('modal-submission-file');
        if (fileInput) fileInput.value = '';
        _modal = {};
    });

    /* â”€â”€ Submit button: POST to PHP via anaktisi() â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€ */
    document.getElementById('modal-submit-btn').addEventListener('click', function () {
        if (!canSubmitApplications) {
            showCenterNotice('? ?p?ï¿½??? de? e??a? d?a??s?ï¿½? a?t? t? st??ï¿½?. ?a?a?a?? a?a?e?ste t? se??da ?a? d???ï¿½?ste ?a??.');
            return;
        }

        var form = document.getElementById('application-form');
        if (!form) return;

        if (!form.checkValidity()) {
            form.reportValidity();
            return;
        }

        var data = {};
        applyFormDataValuesWithoutFiles(form, data);
        data.applied_at = todayLabel();
        data._formType = (_modal.meta && _modal.meta.formType) ? _modal.meta.formType : 'general';
        data._category = (_modal.meta && _modal.meta.category) ? _modal.meta.category : '';
        data._submission_mode = 'manual';

        var files = collectFormUploadFiles(form);
        var manualFilesValidationMessage = getSubmissionFilesValidationMessage(files, false);
        if (manualFilesValidationMessage !== '') {
            showCenterNotice(manualFilesValidationMessage);
            return;
        }

        var btn = document.getElementById('modal-submit-btn');
        btn.disabled = true;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin mr-1"></i> ?p?st???...';

        var body = new FormData();
        body.append('ajax_submit_v2', '1');
        body.append('application_id', String(_modal.appId));
        body.append('submission_mode', 'manual');
        body.append('submission_data', JSON.stringify(data));
        files.forEach(function (file) {
            body.append('submission_files[]', file);
        });

        fetch(window.location.pathname, {
            method:  'POST',
            body:    body
        })
        .then(function (res) {
            return res.text().then(function (text) {
                try {
                    return JSON.parse(text);
                } catch (parseError) {
                    throw new Error(text ? text.slice(0, 260) : 'Empty response');
                }
            });
        })
        .then(function (json) {
            btn.disabled = false;
            btn.innerHTML = '<i class="fas fa-paper-plane mr-1"></i> ?p?ï¿½???';

            if (!json.success) {
                showCenterNotice(json.message || 'Sf??ï¿½a.');
                return;
            }

            _justApplied.add(_modal.appId);
            clearDraft(_modal.appId);
            removeDraftSubmissionRow(_modal.appId);
            $('#submitModal').modal('hide');
            setTimeout(cleanupModalArtifacts, 120);
            showToast();

            // Enimeronei amesa to koumpi kai to badge tis kartelas.
            augmentCards();

            // Prosthetei grammi se ekkremotita ston pinaka ypovolon.
            addSubmissionRow({
                appId:        _modal.appId,
                appTitle:     _modal.appTitle,
                studentName:  data.student_name  || 'â€”',
                studentClass: data.student_class || 'â€”',
                submissionMode: data._submission_mode || 'manual',
                submittedDate: new Date().toLocaleDateString('el-GR')
            });
        })
        .catch(function (err) {
            btn.disabled = false;
            btn.innerHTML = '<i class="fas fa-paper-plane mr-1"></i> ?p?ï¿½???';
            showCenterNotice('Sf??ï¿½a d??t???. ?eï¿½a???e?te ?t? ? d?a??ï¿½?st?? t???e? ?a? d???ï¿½?ste ?a??.');
        });
    });

    var saveDraftBtn = document.getElementById('modal-save-draft-btn');
    if (saveDraftBtn) {
        saveDraftBtn.addEventListener('click', function () {
            var form = document.getElementById('application-form');
            if (!form || !_modal.appId) {
                showCenterNotice('?e? ?p???e? e?e??? a?t?s? ??a ap????e?s? p???e????.');
                return;
            }

            var data = {};
            new FormData(form).forEach(function (value, key) {
                if (typeof File !== 'undefined' && value instanceof File) {
                    return;
                }
                data[key] = value;
            });

            data._formType = _modal.meta && _modal.meta.formType ? _modal.meta.formType : '';
            data._category = _modal.meta && _modal.meta.category ? _modal.meta.category : '';

            if (saveDraft(_modal.appId, data)) {
                upsertDraftSubmissionRow(_modal.appId, data);
                showCenterNotice('?? p???e??? ap????e?t??e. ?p??e?te ?a s??e??sete a???te?a.');
            } else {
                showCenterNotice('?e? ?ta? d??at? ? ap????e?s? p???e???? se a?t? t? s?s?e??.');
            }
        });
    }

    /* â”€â”€ Provoli submission details (event delegation on tbody) â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€ */
    var tbody = document.getElementById('submissions-tbody');
    if (tbody) {
        tbody.addEventListener('click', function (e) {
            // Perigrafei ti leitourgia tou antistoixou tmimatos me emfasi sti statherotita kai tin egkyrotita dedomenon.
            var jsBtn = e.target.closest('.view-js-submission');
            if (jsBtn) {
                var subId = parseInt(jsBtn.dataset.subId, 10);
                // Den einai diathesimo meta apo roii fetch()-based, opote den ginetai energeia.
                return;
            }

            // Grammes vasis dedomenon pou emfanistikan apo PHP
            var dbBtn = e.target.closest('.view-db-submission');
            if (dbBtn) {
                var raw    = dbBtn.dataset.subData  || '{}';
                var title  = dbBtn.dataset.subTitle || '';
                var submittedAt = dbBtn.dataset.submittedAt || '';
                var status = dbBtn.dataset.subStatus || 'waiting';
                try {
                    var parsed = JSON.parse(raw);
                    showViewModalFromData(title, parsed, status, submittedAt);
                } catch (err) {
                    showCenterNotice('?e? e??a? d??at? ? eï¿½f???s? ?ept?ï¿½e?e???.');
                }
            }

            var continueBtn = e.target.closest('.continue-draft-btn');
            if (continueBtn) {
                var appId = parseInt(continueBtn.dataset.appId, 10);
                if (!appId) return;

                var submitBtn = document.querySelector('#app-card-' + appId + ' .submit-btn');
                if (!submitBtn || submitBtn.disabled) {
                    showCenterNotice('?e? e??a? d??at? ? s????e?a a?t?? t?? a?t?s??.');
                    return;
                }

                submitBtn.click();
            }
        });
    }
});
