"use strict";

/* ─────────────────────────────────────────────────────────────────────────────
   APPLICATION METADATA  (dummy / front-end only)
   Cycling array assigned by rendered card order (data-app-index).
   Provides status, category, open/close dates, and form type for the UI.
   Actual submission is saved to the database via AJAX.
───────────────────────────────────────────────────────────────────────────── */
const APP_META = [
    {
        status:    'open',
        category:  'Βιβλιοθήκη',
        openDate:  '01/03/2026',
        closeDate: '30/04/2026',
        formType:  'library'
    },
    {
        status:    'closed',
        category:  'Εκδρομές',
        openDate:  '10/01/2026',
        closeDate: '28/02/2026',
        formType:  'field_trip'
    },
    {
        status:    'open',
        category:  'Επιστήμη',
        openDate:  '01/03/2026',
        closeDate: '15/05/2026',
        formType:  'science_fair'
    },
    {
        status:    'open',
        category:  'Εξωσχολικές',
        openDate:  '10/03/2026',
        closeDate: '20/04/2026',
        formType:  'after_school'
    }
];

/* ─────────────────────────────────────────────────────────────────────────────
   FORM FIELD TEMPLATES  (keyed by formType)
───────────────────────────────────────────────────────────────────────────── */
const FORM_FIELDS = {
    library: [
        { name: 'student_name', label: 'Όνομα Μαθητή',        type: 'text',     required: true,  icon: 'fa-user-graduate' },
        { name: 'class',        label: 'Τάξη',                 type: 'select',   required: true,  icon: 'fa-chalkboard',   options: ['Α1','Α2','Β1','Β2','Γ1','Γ2'] },
        { name: 'parent_name',  label: 'Όνομα Γονέα/Κηδεμόνα', type: 'text',    required: true,  icon: 'fa-user' },
        { name: 'comments',     label: 'Σχόλια (προαιρετικό)', type: 'textarea', required: false, icon: 'fa-comment-alt' }
    ],
    field_trip: [
        { name: 'student_name', label: 'Όνομα Μαθητή',            type: 'text',     required: true,  icon: 'fa-user-graduate' },
        { name: 'class',        label: 'Τάξη',                     type: 'select',   required: true,  icon: 'fa-chalkboard',        options: ['Α1','Α2','Β1','Β2','Γ1','Γ2'] },
        { name: 'parent_name',  label: 'Όνομα Γονέα/Κηδεμόνα',    type: 'text',     required: true,  icon: 'fa-user' },
        { name: 'trip_name',    label: 'Προορισμός Εκδρομής',      type: 'text',     required: true,  icon: 'fa-map-marker-alt' },
        { name: 'comments',     label: 'Ιατρικές Πληροφορίες / Σχόλια', type: 'textarea', required: false, icon: 'fa-comment-alt' }
    ],
    science_fair: [
        { name: 'student_name',  label: 'Όνομα Μαθητή',       type: 'text',     required: true,  icon: 'fa-user-graduate' },
        { name: 'class',         label: 'Τάξη',                type: 'select',   required: true,  icon: 'fa-chalkboard',  options: ['Α1','Α2','Β1','Β2','Γ1','Γ2'] },
        { name: 'parent_name',   label: 'Όνομα Γονέα',        type: 'text',     required: true,  icon: 'fa-user' },
        { name: 'project_title', label: 'Τίτλος Εργασίας',     type: 'text',     required: true,  icon: 'fa-flask' },
        { name: 'comments',      label: 'Σχόλια (προαιρετικό)',type: 'textarea', required: false, icon: 'fa-comment-alt' }
    ],
    after_school: [
        { name: 'student_name', label: 'Όνομα Μαθητή',       type: 'text',     required: true,  icon: 'fa-user-graduate' },
        { name: 'class',        label: 'Τάξη',                type: 'select',   required: true,  icon: 'fa-chalkboard',  options: ['Α1','Α2','Β1','Β2','Γ1','Γ2'] },
        { name: 'parent_name',  label: 'Όνομα Γονέα',        type: 'text',     required: true,  icon: 'fa-user' },
        { name: 'activity',     label: 'Δραστηριότητα',       type: 'select',   required: true,  icon: 'fa-running',     options: ['Ποδόσφαιρο','Μπάσκετ','Χορός','Θέατρο','Ζωγραφική'] },
        { name: 'comments',     label: 'Σχόλια (προαιρετικό)',type: 'textarea', required: false, icon: 'fa-comment-alt' }
    ],
    general: [
        { name: 'student_name', label: 'Όνομα Μαθητή',        type: 'text',     required: true,  icon: 'fa-user-graduate' },
        { name: 'class',        label: 'Τάξη',                 type: 'select',   required: true,  icon: 'fa-chalkboard',   options: ['Α1','Α2','Β1','Β2','Γ1','Γ2'] },
        { name: 'parent_name',  label: 'Όνομα Γονέα/Κηδεμόνα', type: 'text',   required: true,  icon: 'fa-user' },
        { name: 'comments',     label: 'Σχόλια (προαιρετικό)', type: 'textarea', required: false, icon: 'fa-comment-alt' }
    ]
};

/* ─────────────────────────────────────────────────────────────────────────────
   IN-PAGE APPLIED TRACKING  (resets on page reload — DB is the source of truth)
───────────────────────────────────────────────────────────────────────────── */
const _justApplied = new Set(); // appIds submitted during this page session

function isJsApplied(appId) {
    return _justApplied.has(appId);
}

/* ─────────────────────────────────────────────────────────────────────────────
   HELPERS
───────────────────────────────────────────────────────────────────────────── */
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
    if (!card) return 'Αίτηση #' + String(appId);
    var titleEl = card.querySelector('.application-title');
    return titleEl ? titleEl.textContent.trim() : 'Αίτηση #' + String(appId);
}

function getDraftStudentInfo(draftData) {
    return {
        studentName: draftData && draftData.student_name ? draftData.student_name : '—',
        studentClass: draftData && draftData.class ? draftData.class : '—'
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
    var info = getDraftStudentInfo(draftData);

    row.innerHTML =
        '<td><strong>' + escHtml(appTitle) + '</strong></td>' +
        '<td>' + escHtml(info.studentName) + '</td>' +
        '<td>' + escHtml(info.studentClass) + '</td>' +
        '<td>' + escHtml(todayLabel()) + '</td>' +
        '<td>' +
            '<span class="sub-status-badge sub-submitted">Draft</span>' +
        '</td>' +
        '<td>' +
            '<button type="button" class="btn btn-sm btn-outline-primary continue-draft-btn" data-app-id="' + String(appId) + '">' +
                '<i class="fas fa-edit mr-1"></i>Συνέχεια αίτησης' +
            '</button>' +
        '</td>';

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

/* ─────────────────────────────────────────────────────────────────────────────
   BADGE / TAG HTML BUILDERS
───────────────────────────────────────────────────────────────────────────── */
function cardStatusBadge(status) {
    var map = {
        open:     { cls: 'app-status-open',    icon: 'fa-unlock-alt', label: 'Ανοιχτή'       },
        upcoming: { cls: 'app-status-closed',  icon: 'fa-hourglass-half', label: 'Δεν Άνοιξε Ακόμα' },
        closed:   { cls: 'app-status-closed',  icon: 'fa-lock',       label: 'Κλειστή'       },
        applied:  { cls: 'app-status-applied', icon: 'fa-check',      label: 'Υποβλήθηκε'    }
    };
    var s = map[status] || map.open;
    return '<span class="app-status-badge ' + s.cls + '">' +
           '<i class="fas ' + s.icon + '"></i>' + escHtml(s.label) + '</span>';
}

function submissionStatusBadge(status) {
    var map = {
        submitted: { cls: 'sub-submitted', label: 'Υποβλήθηκε'  },
        waiting:   { cls: 'sub-waiting',   label: 'Υπό Εξέταση' },
        approved:  { cls: 'sub-approved',  label: 'Εγκρίθηκε'   },
        rejected:  { cls: 'sub-rejected',  label: 'Απορρίφθηκε' }
    };
    var s = map[status] || map.submitted;
    return '<span class="sub-status-badge ' + s.cls + '">' + escHtml(s.label) + '</span>';
}

/* ─────────────────────────────────────────────────────────────────────────────
   AUGMENT CARDS  – inject status badge, category tag, and date row into every
   PHP-rendered application card using its data-app-index attribute.
───────────────────────────────────────────────────────────────────────────── */
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
                '<small>Άνοιγμα: <strong>' + escHtml(openDate) + '</strong></small></span>' +
                '<span class="app-date-item"><i class="fas fa-calendar-times text-danger"></i>' +
                '<small>Λήξη: <strong>' + escHtml(closeDate) + '</strong></small></span>' +
                '</div>';
        }

        // Submit button state
        var btn = card.querySelector('.submit-btn');
        if (!btn) return;

        btn.classList.remove('btn-primary', 'btn-success', 'btn-secondary', 'btn-warning');

        if (applied) {
            btn.innerHTML = '<i class="fas fa-check mr-1"></i> Υποβλήθηκε';
            btn.classList.add('btn-success');
            btn.disabled = true;
            btn.removeAttribute('data-toggle');
            btn.removeAttribute('data-target');
            btn.dataset.unavailableReason = 'Έχετε ήδη υποβάλει αυτή την αίτηση.';
        } else if (windowStatus === 'upcoming') {
            btn.innerHTML = '<i class="fas fa-hourglass-start mr-1"></i> Ακόμα δεν άνοιξε';
            btn.classList.add('btn-warning');
            btn.disabled = true;
            btn.removeAttribute('data-toggle');
            btn.removeAttribute('data-target');
            btn.dataset.unavailableReason = 'Η αίτηση δεν έχει ανοίξει ακόμα.';
        } else if (windowStatus === 'closed') {
            btn.innerHTML = '<i class="fas fa-lock mr-1"></i> Κλειστή';
            btn.classList.add('btn-secondary');
            btn.disabled = true;
            btn.removeAttribute('data-toggle');
            btn.removeAttribute('data-target');
            btn.dataset.unavailableReason = 'Η περίοδος υποβολής έχει λήξει.';
        } else {
            btn.innerHTML = '<i class="fas fa-paper-plane mr-1"></i> Υποβολή Αίτησης';
            btn.classList.add('btn-primary');
            btn.disabled = false;
            btn.setAttribute('data-toggle', 'modal');
            btn.setAttribute('data-target', '#submitModal');
            delete btn.dataset.unavailableReason;
        }
    });
}

/* ─────────────────────────────────────────────────────────────────────────────
   RENDER NEWLY-SUBMITTED ROWS  (rows added this page session, before reload)
   DB-rendered rows are already in the tbody from PHP.
───────────────────────────────────────────────────────────────────────────── */
function addSubmissionRow(sub) {
    var tbody = document.getElementById('submissions-tbody');
    var table = document.getElementById('submissions-table');
    var noMsg = document.getElementById('no-submissions-msg');

    if (!tbody) return;

    var tr = document.createElement('tr');
    tr.dataset.jsRow = sub.appId;
    tr.innerHTML =
        '<td><strong>' + escHtml(sub.appTitle)      + '</strong></td>' +
        '<td>'          + escHtml(sub.studentName)  + '</td>'          +
        '<td>'          + escHtml(sub.studentClass) + '</td>'          +
        '<td>'          + escHtml(sub.submittedDate)+ '</td>'          +
        '<td>'          + submissionStatusBadge('waiting') + '</td>'   +
        '<td><button class="btn btn-sm btn-outline-secondary" disabled>' +
            '<i class="fas fa-clock mr-1"></i>Αποθηκεύτηκε</button></td>';
    tbody.appendChild(tr);

    if (noMsg)  noMsg.style.display  = 'none';
    if (table)  table.style.display  = '';
}

/* ─────────────────────────────────────────────────────────────────────────────
   BUILD ONE FORM FIELD
───────────────────────────────────────────────────────────────────────────── */
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
                  '<option value="" disabled selected>Επιλέξτε...</option>' +
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

/* ─────────────────────────────────────────────────────────────────────────────
   SHOW VIEW MODAL  – handles both PHP-rendered DB rows and JS-submitted rows
───────────────────────────────────────────────────────────────────────────── */
function showViewModalFromData(appTitle, submissionDataObj, statusKey, submittedAt) {
    var fields = FORM_FIELDS[submissionDataObj._formType] || FORM_FIELDS.general;
    var rows = fields.map(function (f) {
        var val = submissionDataObj[f.name] || '—';
        return '<tr><th class="text-muted font-weight-normal" style="width:45%">' +
               escHtml(f.label) + '</th><td><strong>' + escHtml(val) + '</strong></td></tr>';
    }).join('');

    var submittedLabel = submittedAt || submissionDataObj.applied_at || '—';
    rows += '<tr><th class="text-muted font-weight-normal" style="width:45%">Ημερομηνία Υποβολής</th><td><strong>' +
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

/* ─────────────────────────────────────────────────────────────────────────────
   SHOW SUCCESS TOAST
───────────────────────────────────────────────────────────────────────────── */
function showToast() {
    var toast = document.getElementById('submission-toast');
    if (!toast) return;
    toast.style.display = 'block';
    setTimeout(function () { toast.style.display = 'none'; }, 4000);
}

/* ─────────────────────────────────────────────────────────────────────────────
   MODAL STATE  – shared between "show" & "submit" handlers
───────────────────────────────────────────────────────────────────────────── */
var _modal = {};
var _viewAppId = 0;

/* ─────────────────────────────────────────────────────────────────────────────
   DOM READY
───────────────────────────────────────────────────────────────────────────── */
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
    var viewModalContinueBtn = document.getElementById('application-view-continue-btn');

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

    document.querySelectorAll('.post-open-trigger').forEach(function (card) {
        card.addEventListener('click', function (e) {
            var submitButton = e.target.closest('.submit-btn');
            if (submitButton) return;

            var appId = parseInt(card.dataset.appId, 10);
            var title = card.dataset.applicationTitle || 'Λεπτομέρειες Αίτησης';
            var description = card.dataset.applicationDescription || 'Δεν υπάρχει διαθέσιμη περιγραφή.';
            var docsRaw = card.dataset.applicationDocuments || '[]';
            var docs = [];

            try {
                docs = JSON.parse(docsRaw);
                if (!Array.isArray(docs)) docs = [];
            } catch (err) {
                docs = [];
            }

            _viewAppId = appId;

            if (viewModalTitle) viewModalTitle.textContent = title;
            if (viewModalDesc) viewModalDesc.textContent = description;

            if (viewModalAttachments) {
                if (docs.length === 0) {
                    viewModalAttachments.innerHTML = '<li class="text-muted">Δεν υπάρχουν συνημμένα έγγραφα.</li>';
                } else {
                    viewModalAttachments.innerHTML = docs.map(function (doc) {
                        var rawPath = doc && doc.file_path ? doc.file_path : '';
                        var url = normalizeDocPath(rawPath);
                        var name = rawPath ? rawPath.split('/').pop() : 'Έγγραφο';
                        return '<li><a href="' + escHtml(url) + '" target="_blank"><i class="fas fa-file-alt mr-1 text-primary"></i>' + escHtml(name) + '</a></li>';
                    }).join('');
                }
            }

            $('#applicationViewModal').modal('show');
        });
    });

    if (viewModalContinueBtn) {
        viewModalContinueBtn.addEventListener('click', function () {
            if (!_viewAppId) return;

            var submitBtn = document.querySelector('#app-card-' + _viewAppId + ' .submit-btn');
            if (!submitBtn || submitBtn.disabled) {
                var reason = submitBtn ? (submitBtn.dataset.unavailableReason || '') : '';
                alert(reason || 'Η αίτηση δεν είναι διαθέσιμη για υποβολή.');
                return;
            }

            $('#applicationViewModal').modal('hide');
            submitBtn.click();
        });
    }

    /* ── Open modal: populate header + dynamic fields ─────────────────── */
    $('#submitModal').on('show.bs.modal', function (event) {
        var btn      = $(event.relatedTarget);
        var appId    = parseInt(btn.data('application-id'), 10);
        var appTitle = btn.data('application-title') || '';
        var appDesc  = btn.data('application-description') || '';
        var appIndex = parseInt(btn.data('app-index'), 10);
        var meta     = getMeta(appIndex);
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
        container.innerHTML =
            '<form id="application-form">' +
            fields.map(buildField).join('') +
            '</form>';

        // Restore saved draft for this application, if available.
        var form = document.getElementById('application-form');
        var draft = loadDraft(appId);
        if (form && draft) {
            restoreDraftToForm(form, draft);
        }
    });

    /* ── Clear form on modal close ─────────────────────────────────────── */
    $('#submitModal').on('hidden.bs.modal', function () {
        var container = document.getElementById('modal-dynamic-fields');
        if (container) container.innerHTML = '';
        var fileInput = document.getElementById('modal-submission-file');
        if (fileInput) fileInput.value = '';
        _modal = {};
    });

    /* ── Submit button: POST to PHP via fetch() ───────────────────────── */
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
        data._formType = _modal.meta.formType;
        data._category = _modal.meta.category;

        var btn = document.getElementById('modal-submit-btn');
        btn.disabled = true;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin mr-1"></i> Αποστολή...';

        var body = new FormData();
        body.append('ajax_submit', '1');
        body.append('application_id', String(_modal.appId));
        body.append('submission_data', JSON.stringify(data));

        var fileInput = document.getElementById('modal-submission-file');
        if (fileInput && fileInput.files && fileInput.files[0]) {
            body.append('submission_file', fileInput.files[0]);
        }

        fetch('applications.php', {
            method:  'POST',
            body:    body
        })
        .then(function (res) { return res.json(); })
        .then(function (json) {
            btn.disabled = false;
            btn.innerHTML = '<i class="fas fa-paper-plane mr-1"></i> Υποβολή';

            if (!json.success) {
                alert(json.message || 'Σφάλμα.');
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
                studentName:  data.student_name  || '—',
                studentClass: data.class         || '—',
                submittedDate: new Date().toLocaleDateString('el-GR')
            });
        })
        .catch(function (err) {
            btn.disabled = false;
            btn.innerHTML = '<i class="fas fa-paper-plane mr-1"></i> Υποβολή';
            alert('Σφάλμα δικτύου. Βεβαιωθείτε ότι ο διακομιστής τρέχει και δοκιμάστε ξανά.');
        });
    });

    var saveDraftBtn = document.getElementById('modal-save-draft-btn');
    if (saveDraftBtn) {
        saveDraftBtn.addEventListener('click', function () {
            var form = document.getElementById('application-form');
            if (!form || !_modal.appId) {
                alert('Δεν υπάρχει ενεργή αίτηση για αποθήκευση πρόχειρου.');
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
                alert('Το πρόχειρο αποθηκεύτηκε. Μπορείτε να συνεχίσετε αργότερα.');
            } else {
                alert('Δεν ήταν δυνατή η αποθήκευση πρόχειρου σε αυτή τη συσκευή.');
            }
        });
    }

    /* ── View submission details (event delegation on tbody) ──────────── */
    var tbody = document.getElementById('submissions-tbody');
    if (tbody) {
        tbody.addEventListener('click', function (e) {
            // JS-submitted rows (view-js-submission class — kept for compat)
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
                    alert('Δεν είναι δυνατή η εμφάνιση λεπτομερειών.');
                }
            }

            var continueBtn = e.target.closest('.continue-draft-btn');
            if (continueBtn) {
                var appId = parseInt(continueBtn.dataset.appId, 10);
                if (!appId) return;

                var submitBtn = document.querySelector('#app-card-' + appId + ' .submit-btn');
                if (!submitBtn || submitBtn.disabled) {
                    alert('Δεν είναι δυνατή η συνέχεια αυτής της αίτησης.');
                    return;
                }

                submitBtn.click();
            }
        });
    }
});
