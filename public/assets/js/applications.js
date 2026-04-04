"use strict";

/* â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
   FORM FIELD TEMPLATES  (keyed by formType)
â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€ */
const FORM_FIELDS = {
    general: []
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

/* â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
   IN-PAGE APPLIED TRACKING  (resets on page reload â€” DB is the source of truth)
â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€ */
const _justApplied = new Set(); // appIds submitted during this page session

function isJsApplied(appId) {
    return _justApplied.has(appId);
}

/* â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
   HELPERS
â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€ */
function getMeta(index) {
    return APP_META[((index % APP_META.length) + APP_META.length) % APP_META.length];
}

function todayLabel() {
    return new Date().toLocaleDateString('el-GR');
}

function escHtml(str) {
    return String(str)
        .replace(/&/g,  '&amp;')
        .replace(/</g,  '&lt;')
        .replace(/>/g,  '&gt;')
        .replace(/"/g,  '&quot;')
        .replace(/'/g,  '&#039;');
}

function draftStorageKey(appId) {
    return 'applications_draft_' + String(appId);
}

function saveDraft(appId, data) {
    try {
        localStorage.setItem(draftStorageKey(appId), JSON.stringify(data));
        return true;
    } catch (e) {
        return false;
    }
}

function loadDraft(appId) {
    try {
        var raw = localStorage.getItem(draftStorageKey(appId));
        if (!raw) return null;
        return JSON.parse(raw);
    } catch (e) {
        return null;
    }
}

function clearDraft(appId) {
    try {
        localStorage.removeItem(draftStorageKey(appId));
    } catch (e) {
        // ignore localStorage errors silently
    }
}

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

function toggleSubmissionsVisibility() {
    var tbody = document.getElementById('submissions-tbody');
    var table = document.getElementById('submissions-table');
    var noMsg = document.getElementById('no-submissions-msg');
    if (!tbody || !table || !noMsg) return;

    var hasRows = tbody.querySelectorAll('tr').length > 0;
    noMsg.style.display = hasRows ? 'none' : '';
    table.style.display = hasRows ? '' : 'none';
}

function getAppCardById(appId) {
    return document.getElementById('app-card-' + String(appId));
}

function getAppTitleById(appId) {
    var card = getAppCardById(appId);
    if (!card) return '\u0391\u03af\u03c4\u03b7\u03c3\u03b7 #' + String(appId);
    var titleEl = card.querySelector('.application-title');
    return titleEl ? titleEl.textContent.trim() : '\u0391\u03af\u03c4\u03b7\u03c3\u03b7 #' + String(appId);
}

function getDraftStudentInfo(draftData) {
    return {
        studentName: draftData && draftData.student_name ? draftData.student_name : 'â€”',
        studentClass: draftData && draftData.class ? draftData.class : 'â€”'
    };
}

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

function removeDraftSubmissionRow(appId) {
    var tbody = document.getElementById('submissions-tbody');
    if (!tbody) return;
    var row = tbody.querySelector('tr[data-draft-row="' + String(appId) + '"]');
    if (row) row.remove();
    toggleSubmissionsVisibility();
}

function renderDraftRowsFromStorage() {
    getAllDraftAppIds().forEach(function (appId) {
        var draft = loadDraft(appId);
        if (draft) {
            upsertDraftSubmissionRow(appId, draft);
        }
    });
}

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
   PHP-rendered application card using its data-app-index attribute.
â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€ */
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

        // Status badge
        var badgeSlot = card.querySelector('.js-status-placeholder');
        if (badgeSlot) {
            badgeSlot.innerHTML = cardStatusBadge(effectiveStatus);
        }

        // Category tag
        var catSlot = card.querySelector('.js-category-placeholder');
        if (catSlot) {
            catSlot.innerHTML = '';
        }

        // Dates
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

        // Submit button state
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
function addSubmissionRow(sub) {
    var tbody = document.getElementById('submissions-tbody');
    var table = document.getElementById('submissions-table');
    var noMsg = document.getElementById('no-submissions-msg');

    if (!tbody) return;

    var uploadedFiles = Array.isArray(sub.uploadedFiles) ? sub.uploadedFiles : [];
    var filesHtml = uploadedFiles.map(function (fileItem) {
        if (fileItem && typeof fileItem === 'object' && fileItem.url) {
            var itemName = fileItem.name ? String(fileItem.name) : 'Αρχείο';
            return '<a href="' + escHtml(String(fileItem.url)) + '" target="_blank" rel="noopener noreferrer" class="submission-file-link d-block small mt-1"><i class="fas fa-paperclip mr-1"></i>' + escHtml(itemName) + '</a>';
        }

        return '<div class="submission-file-link d-block small mt-1"><i class="fas fa-paperclip mr-1"></i>' + escHtml(String(fileItem || '')) + '</div>';
    }).join('');

    var tr = document.createElement('tr');
    tr.dataset.jsRow = sub.appId;
    tr.innerHTML =
        '<td><strong>' + escHtml(sub.appTitle) + '</strong>' + filesHtml + '</td>' +
        '<td>' + escHtml(sub.submittedDate) + '</td>';
    tbody.appendChild(tr);

    if (noMsg)  noMsg.style.display  = 'none';
    if (table)  table.style.display  = '';
}

/* â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
   BUILD ONE FORM FIELD
â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€ */
function buildField(field) {
    var req = field.required
        ? '<span class="text-danger ml-1" aria-hidden="true">*</span>'
        : '';
    var control = '';

    if (field.type === 'select') {
        var opts = field.options.map(function (o) {
            return '<option value="' + escHtml(o) + '">' + escHtml(o) + '</option>';
        }).join('');
        control = '<select class="form-control" name="' + field.name + '"' +
                  (field.required ? ' required' : '') + '>' +
                  '<option value="" disabled selected>\u0395\u03c0\u03b9\u03bb\u03ad\u03be\u03c4\u03b5...</option>' +
                  opts + '</select>';
    } else if (field.type === 'textarea') {
        control = '<textarea class="form-control" name="' + field.name +
                  '" rows="3" placeholder="' + escHtml(field.label) + '..."' +
                  (field.required ? ' required' : '') + '></textarea>';
    } else {
        control = '<input type="' + field.type + '" class="form-control" name="' +
                  field.name + '" placeholder="' + escHtml(field.label) + '..."' +
                  (field.required ? ' required' : '') + '>';
    }

    return '<div class="form-group mb-3">' +
           '<label class="form-label-custom">' +
           '<i class="fas ' + field.icon + ' text-primary mr-1"></i>' +
           escHtml(field.label) + req + '</label>' +
           control + '</div>';
}

/* â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
   SHOW VIEW MODAL  â€“ handles both PHP-rendered DB rows and JS-submitted rows
â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€ */
function showViewModalFromData(appTitle, submissionDataObj, statusKey, submittedAt) {
    var fields = FORM_FIELDS[submissionDataObj._formType] || FORM_FIELDS.general;
    var rows = fields.map(function (f) {
        var val = submissionDataObj[f.name] || 'â€”';
        return '<tr><th class="text-muted font-weight-normal" style="width:45%">' +
               escHtml(f.label) + '</th><td><strong>' + escHtml(val) + '</strong></td></tr>';
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
function showToast() {
    var toast = document.getElementById('submission-toast');
    if (!toast) return;
    toast.style.display = 'block';
    setTimeout(function () { toast.style.display = 'none'; }, 4000);
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
    var bodyScrollLockCount = 0;

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

    // 1. Augment cards on load
    augmentCards();

    // 2. Init submissions table visibility (table/noMsg already correct from PHP)
    renderDraftRowsFromStorage();
    toggleSubmissionsVisibility();

    var viewModalTitle = document.getElementById('application-view-title');
    var viewModalDesc = document.getElementById('application-view-description');
    var viewModalAttachments = document.getElementById('application-view-attachments-list');
    var viewModalSubmitBtn = document.getElementById('application-view-submit-btn');
    var viewModalFileInput = document.getElementById('application-view-file-input');
    var viewModalSelectedFiles = document.getElementById('application-view-selected-files');
    var unavailableMessageEl = document.getElementById('application-unavailable-message');
    var applicationNoticeBox = document.getElementById('application-notice-box');
    var applicationNoticeBackdrop = document.getElementById('application-notice-backdrop');
    var applicationNoticeMessage = document.getElementById('application-notice-message');
    var applicationNoticeClose = document.getElementById('application-notice-close');

    function hideCenterNotice() {
        if (applicationNoticeBox) {
            applicationNoticeBox.classList.remove('is-visible');
        }
        if (applicationNoticeBackdrop) {
            applicationNoticeBackdrop.classList.remove('is-visible');
        }
    }

    function showCenterNotice(messageText) {
        if (!applicationNoticeBox || !applicationNoticeMessage || !applicationNoticeBackdrop) {
            console.error(messageText || 'Σφάλμα.');
            return;
        }

        applicationNoticeMessage.textContent = messageText || 'Σφάλμα.';
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

    function showUnavailableBox(messageText) {
        if (unavailableMessageEl) {
            unavailableMessageEl.textContent = messageText || '\u0397 \u03b1\u03af\u03c4\u03b7\u03c3\u03b7 \u03b4\u03b5\u03bd \u03ad\u03c7\u03b5\u03b9 \u03b1\u03bd\u03bf\u03af\u03be\u03b5\u03b9 \u03b1\u03ba\u03cc\u03bc\u03b1.';
        }
        $('#application-unavailable-modal').modal('show');
    }

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

    function openApplicationViewModal(card) {
        if (!card) return;

        var submitBtn = card.querySelector('.submit-btn');
        if (!submitBtn || submitBtn.disabled) {
            var reason = submitBtn ? (submitBtn.dataset.unavailableReason || '') : '';
            showUnavailableBox(reason || 'Η αίτηση δεν έχει ανοίξει ακόμα.');
            return;
        }

        var appId = parseInt(card.dataset.appId, 10);
        var appIndex = parseInt(card.dataset.appIndex, 10);
        var title = card.dataset.applicationTitle || '\u039b\u03b5\u03c0\u03c4\u03bf\u03bc\u03ad\u03c1\u03b5\u03b9\u03b5\u03c2 \u0391\u03af\u03c4\u03b7\u03c3\u03b7\u03c2';
        var description = card.dataset.applicationDescription || '\u0394\u03b5\u03bd \u03c5\u03c0\u03ac\u03c1\u03c7\u03b5\u03b9 \u03b4\u03b9\u03b1\u03b8\u03ad\u03c3\u03b9\u03bc\u03b7 \u03c0\u03b5\u03c1\u03b9\u03b3\u03c1\u03b1\u03c6\u03ae.';
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
                    var name = rawPath ? rawPath.split('/').pop() : '\u0388\u03b3\u03b3\u03c1\u03b1\u03c6\u03bf';
                    return '<li><a href="' + escHtml(url) + '" target="_blank"><i class="fas fa-file-alt mr-1 text-primary"></i>' + escHtml(name) + '</a></li>';
                }).join('');
            }
        }

        if (viewModalFileInput) {
            viewModalFileInput.value = '';
        }
        renderSelectedSubmissionFiles();

        if (viewModalSubmitBtn) {
            viewModalSubmitBtn.disabled = false;
            viewModalSubmitBtn.innerHTML = '<i class="fas fa-paper-plane mr-1"></i>Υποβολή Αίτησης';
        }

        $('#applicationViewModal').modal('show');
    }

    document.querySelectorAll('.post-open-trigger').forEach(function (card) {
        card.addEventListener('click', function (e) {
            var submitButton = e.target.closest('.submit-btn');
            if (submitButton) return;
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

    if (viewModalFileInput) {
        viewModalFileInput.addEventListener('change', function () {
            var selectedFiles = Array.from(viewModalFileInput.files || []);
            if (selectedFiles.length > MAX_SUBMISSION_FILES) {
                showCenterNotice('Μπορείτε να επιλέξετε έως ' + String(MAX_SUBMISSION_FILES) + ' αρχεία.');
                viewModalFileInput.value = '';
            }

            renderSelectedSubmissionFiles();
        });
    }

    $('#applicationViewModal').on('hidden.bs.modal', function () {
        if (viewModalFileInput) {
            viewModalFileInput.value = '';
        }
        renderSelectedSubmissionFiles();
        _viewAppId = 0;
        _modal = {};
    });

    if (viewModalSubmitBtn) {
        viewModalSubmitBtn.addEventListener('click', function () {
            if (!_modal.appId) return;

            var files = viewModalFileInput ? Array.from(viewModalFileInput.files || []) : [];
            if (files.length === 0) {
                showCenterNotice('Παρακαλώ επιλέξτε τουλάχιστον ένα αρχείο πριν την υποβολή.');
                return;
            }
            if (files.length > MAX_SUBMISSION_FILES) {
                showCenterNotice('Μπορείτε να επιλέξετε έως ' + String(MAX_SUBMISSION_FILES) + ' αρχεία.');
                return;
            }

            for (var i = 0; i < files.length; i++) {
                var fileName = String(files[i].name || '');
                var ext = fileName.indexOf('.') !== -1 ? fileName.split('.').pop().toLowerCase() : '';
                if (ALLOWED_SUBMISSION_EXTENSIONS.indexOf(ext) === -1) {
                    showCenterNotice('Επιτρεπόμενοι τύποι αρχείων: pdf, doc, docx, jpg, jpeg, png.');
                    return;
                }
            }

            var data = {
                applied_at: todayLabel(),
                _formType: (_modal.meta && _modal.meta.formType) ? _modal.meta.formType : 'general',
                _category: (_modal.meta && _modal.meta.category) ? _modal.meta.category : ''
            };

            viewModalSubmitBtn.disabled = true;
            viewModalSubmitBtn.innerHTML = '<i class="fas fa-spinner fa-spin mr-1"></i>Αποστολή...';

            var body = new FormData();
            body.append('ajax_submit_v2', '1');
            body.append('application_id', String(_modal.appId));
            body.append('submission_data', JSON.stringify(data));
            files.forEach(function (file) {
                body.append('submission_files[]', file);
            });

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
                viewModalSubmitBtn.innerHTML = '<i class="fas fa-paper-plane mr-1"></i>Υποβολή Αίτησης';

                if (!json.success) {
                    showCenterNotice(json.message || 'Σφάλμα.');
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
                viewModalSubmitBtn.innerHTML = '<i class="fas fa-paper-plane mr-1"></i>Υποβολή Αίτησης';
                showCenterNotice('Σφάλμα δικτύου. Βεβαιωθείτε ότι ο διακομιστής τρέχει και δοκιμάστε ξανά.');
            });
        });
    }

    /* â”€â”€ Open modal: populate header + dynamic fields â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€ */
    $('#submitModal').on('show.bs.modal', function (event) {
        var btn      = $(event.relatedTarget);
        var appId    = parseInt(btn.data('application-id'), 10);
        var appTitle = btn.data('application-title') || '';
        var appDesc  = btn.data('application-description') || '';
        var appIndex = parseInt(btn.data('app-index'), 10);
        var meta     = getMeta(appIndex) || { formType: 'general', category: '', openDate: '', closeDate: '' };
        var appOpenDate = btn.data('app-open-date') || '';
        var appCloseDate = btn.data('app-close-date') || '';
        if (appOpenDate) {
            meta.openDate = appOpenDate;
        }
        if (appCloseDate) {
            meta.closeDate = appCloseDate;
        }

        _modal = { appId: appId, appTitle: appTitle, appIndex: appIndex, meta: meta };

        // Header text
        document.getElementById('modal-title').textContent       = appTitle;
        document.getElementById('modal-description').textContent = appDesc;

        // Dynamic form fields
        var fields    = FORM_FIELDS[meta.formType] || FORM_FIELDS.general;
        var container = document.getElementById('modal-dynamic-fields');
        // Add the file input inside the form.
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

        // Restore saved draft for this application, if available.
        var form = document.getElementById('application-form');
        var draft = loadDraft(appId);
        if (form && draft) {
            restoreDraftToForm(form, draft);
        }
    });

    /* â”€â”€ Clear form on modal close â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€ */
    $('#submitModal').on('hidden.bs.modal', function () {
        var container = document.getElementById('modal-dynamic-fields');
        if (container) container.innerHTML = '';
        var fileInput = document.getElementById('modal-submission-file');
        if (fileInput) fileInput.value = '';
        _modal = {};
    });

    /* â”€â”€ Submit button: POST to PHP via fetch() â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€ */
    document.getElementById('modal-submit-btn').addEventListener('click', function () {
        var form = document.getElementById('application-form');
        if (!form) return;

        if (!form.checkValidity()) {
            form.reportValidity();
            return;
        }

        var data = {};
        new FormData(form).forEach(function (value, key) { data[key] = value; });
        data.applied_at = todayLabel();
        data._formType = (_modal.meta && _modal.meta.formType) ? _modal.meta.formType : 'general';
        data._category = (_modal.meta && _modal.meta.category) ? _modal.meta.category : '';

        var btn = document.getElementById('modal-submit-btn');
        btn.disabled = true;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin mr-1"></i> Αποστολή...';

        var body = new FormData();
        body.append('ajax_submit_v2', '1');
        body.append('application_id', String(_modal.appId));
        body.append('submission_data', JSON.stringify(data));

        var fileInput = document.getElementById('modal-submission-file');
        if (fileInput && fileInput.files && fileInput.files[0]) {
            body.append('submission_file', fileInput.files[0]);
        }

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
            btn.innerHTML = '<i class="fas fa-paper-plane mr-1"></i> Υποβολή';

            if (!json.success) {
                showCenterNotice(json.message || 'Σφάλμα.');
                return;
            }

            _justApplied.add(_modal.appId);
            clearDraft(_modal.appId);
            removeDraftSubmissionRow(_modal.appId);
            $('#submitModal').modal('hide');
            setTimeout(cleanupModalArtifacts, 120);
            showToast();

            // Update the card button/badge immediately
            augmentCards();

            // Add a pending row to the submissions table
            addSubmissionRow({
                appId:        _modal.appId,
                appTitle:     _modal.appTitle,
                studentName:  data.student_name  || 'â€”',
                studentClass: data.class         || 'â€”',
                submittedDate: new Date().toLocaleDateString('el-GR')
            });
        })
        .catch(function (err) {
            btn.disabled = false;
            btn.innerHTML = '<i class="fas fa-paper-plane mr-1"></i> Υποβολή';
            showCenterNotice('Σφάλμα δικτύου. Βεβαιωθείτε ότι ο διακομιστής τρέχει και δοκιμάστε ξανά.');
        });
    });

    var saveDraftBtn = document.getElementById('modal-save-draft-btn');
    if (saveDraftBtn) {
        saveDraftBtn.addEventListener('click', function () {
            var form = document.getElementById('application-form');
            if (!form || !_modal.appId) {
                showCenterNotice('Δεν υπάρχει ενεργή αίτηση για αποθήκευση πρόχειρου.');
                return;
            }

            var data = {};
            new FormData(form).forEach(function (value, key) {
                data[key] = value;
            });

            data._formType = _modal.meta && _modal.meta.formType ? _modal.meta.formType : '';
            data._category = _modal.meta && _modal.meta.category ? _modal.meta.category : '';

            if (saveDraft(_modal.appId, data)) {
                upsertDraftSubmissionRow(_modal.appId, data);
                showCenterNotice('Το πρόχειρο αποθηκεύτηκε. Μπορείτε να συνεχίσετε αργότερα.');
            } else {
                showCenterNotice('Δεν ήταν δυνατή η αποθήκευση πρόχειρου σε αυτή τη συσκευή.');
            }
        });
    }

    /* â”€â”€ View submission details (event delegation on tbody) â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€ */
    var tbody = document.getElementById('submissions-tbody');
    if (tbody) {
        tbody.addEventListener('click', function (e) {
            // JS-submitted rows (view-js-submission class â€” kept for compat)
            var jsBtn = e.target.closest('.view-js-submission');
            if (jsBtn) {
                var subId = parseInt(jsBtn.dataset.subId, 10);
                // Not available after fetch()-based flow; no-op
                return;
            }

            // DB rows rendered by PHP
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
                    showCenterNotice('Δεν είναι δυνατή η εμφάνιση λεπτομερειών.');
                }
            }

            var continueBtn = e.target.closest('.continue-draft-btn');
            if (continueBtn) {
                var appId = parseInt(continueBtn.dataset.appId, 10);
                if (!appId) return;

                var submitBtn = document.querySelector('#app-card-' + appId + ' .submit-btn');
                if (!submitBtn || submitBtn.disabled) {
                    showCenterNotice('Δεν είναι δυνατή η συνέχεια αυτής της αίτησης.');
                    return;
                }

                submitBtn.click();
            }
        });
    }
});

