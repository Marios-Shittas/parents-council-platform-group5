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
        { name: 'date',         label: 'Ημερομηνία',           type: 'date',     required: true,  icon: 'fa-calendar-alt' },
        { name: 'comments',     label: 'Σχόλια (προαιρετικό)', type: 'textarea', required: false, icon: 'fa-comment-alt' }
    ],
    field_trip: [
        { name: 'student_name', label: 'Όνομα Μαθητή',            type: 'text',     required: true,  icon: 'fa-user-graduate' },
        { name: 'class',        label: 'Τάξη',                     type: 'select',   required: true,  icon: 'fa-chalkboard',        options: ['Α1','Α2','Β1','Β2','Γ1','Γ2'] },
        { name: 'parent_name',  label: 'Όνομα Γονέα/Κηδεμόνα',    type: 'text',     required: true,  icon: 'fa-user' },
        { name: 'trip_name',    label: 'Προορισμός Εκδρομής',      type: 'text',     required: true,  icon: 'fa-map-marker-alt' },
        { name: 'trip_date',    label: 'Ημερομηνία Εκδρομής',      type: 'date',     required: true,  icon: 'fa-calendar-alt' },
        { name: 'comments',     label: 'Ιατρικές Πληροφορίες / Σχόλια', type: 'textarea', required: false, icon: 'fa-comment-alt' }
    ],
    science_fair: [
        { name: 'student_name',  label: 'Όνομα Μαθητή',       type: 'text',     required: true,  icon: 'fa-user-graduate' },
        { name: 'class',         label: 'Τάξη',                type: 'select',   required: true,  icon: 'fa-chalkboard',  options: ['Α1','Α2','Β1','Β2','Γ1','Γ2'] },
        { name: 'parent_name',   label: 'Όνομα Γονέα',        type: 'text',     required: true,  icon: 'fa-user' },
        { name: 'project_title', label: 'Τίτλος Εργασίας',     type: 'text',     required: true,  icon: 'fa-flask' },
        { name: 'date',          label: 'Ημερομηνία',          type: 'date',     required: true,  icon: 'fa-calendar-alt' },
        { name: 'comments',      label: 'Σχόλια (προαιρετικό)',type: 'textarea', required: false, icon: 'fa-comment-alt' }
    ],
    after_school: [
        { name: 'student_name', label: 'Όνομα Μαθητή',       type: 'text',     required: true,  icon: 'fa-user-graduate' },
        { name: 'class',        label: 'Τάξη',                type: 'select',   required: true,  icon: 'fa-chalkboard',  options: ['Α1','Α2','Β1','Β2','Γ1','Γ2'] },
        { name: 'parent_name',  label: 'Όνομα Γονέα',        type: 'text',     required: true,  icon: 'fa-user' },
        { name: 'activity',     label: 'Δραστηριότητα',       type: 'select',   required: true,  icon: 'fa-running',     options: ['Ποδόσφαιρο','Μπάσκετ','Χορός','Θέατρο','Ζωγραφική'] },
        { name: 'date',         label: 'Ημερομηνία',          type: 'date',     required: true,  icon: 'fa-calendar-alt' },
        { name: 'comments',     label: 'Σχόλια (προαιρετικό)',type: 'textarea', required: false, icon: 'fa-comment-alt' }
    ],
    general: [
        { name: 'student_name', label: 'Όνομα Μαθητή',        type: 'text',     required: true,  icon: 'fa-user-graduate' },
        { name: 'class',        label: 'Τάξη',                 type: 'select',   required: true,  icon: 'fa-chalkboard',   options: ['Α1','Α2','Β1','Β2','Γ1','Γ2'] },
        { name: 'parent_name',  label: 'Όνομα Γονέα/Κηδεμόνα', type: 'text',   required: true,  icon: 'fa-user' },
        { name: 'date',         label: 'Ημερομηνία',           type: 'date',     required: true,  icon: 'fa-calendar-alt' },
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

/* ─────────────────────────────────────────────────────────────────────────────
   BADGE / TAG HTML BUILDERS
───────────────────────────────────────────────────────────────────────────── */
function cardStatusBadge(status) {
    var map = {
        open:    { cls: 'app-status-open',    icon: 'fa-unlock-alt', label: 'Ανοιχτή'      },
        closed:  { cls: 'app-status-closed',  icon: 'fa-lock',       label: 'Κλειστή'      },
        applied: { cls: 'app-status-applied', icon: 'fa-check',      label: 'Υποβλήθηκε'   }
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
        var applied    = dbApplied || isJsApplied(appId);

        // Status badge
        var badgeSlot = card.querySelector('.js-status-placeholder');
        if (badgeSlot) {
            badgeSlot.innerHTML = cardStatusBadge(applied ? 'applied' : meta.status);
        }

        // Category tag
        var catSlot = card.querySelector('.js-category-placeholder');
        if (catSlot) {
            catSlot.innerHTML =
                '<span class="category-tag">' +
                '<i class="fas fa-tag"></i>' + escHtml(meta.category) + '</span>';
        }

        // Dates
        var datesSlot = card.querySelector('.js-dates-placeholder');
        if (datesSlot) {
            datesSlot.innerHTML =
                '<div class="app-dates-row">' +
                '<span class="app-date-item"><i class="fas fa-calendar-plus text-success"></i>' +
                '<small>Άνοιγμα: <strong>' + escHtml(meta.openDate) + '</strong></small></span>' +
                '<span class="app-date-item"><i class="fas fa-calendar-times text-danger"></i>' +
                '<small>Λήξη: <strong>' + escHtml(meta.closeDate) + '</strong></small></span>' +
                '</div>';
        }

        // Submit button state
        var btn = card.querySelector('.submit-btn');
        if (!btn) return;

        if (applied) {
            btn.innerHTML = '<i class="fas fa-check mr-1"></i> Υποβλήθηκε';
            btn.classList.remove('btn-primary');
            btn.classList.add('btn-success');
            btn.disabled = true;
            btn.removeAttribute('data-toggle');
            btn.removeAttribute('data-target');
        } else if (meta.status === 'closed') {
            btn.innerHTML = '<i class="fas fa-lock mr-1"></i> Κλειστή';
            btn.classList.remove('btn-primary');
            btn.classList.add('btn-secondary');
            btn.disabled = true;
            btn.removeAttribute('data-toggle');
            btn.removeAttribute('data-target');
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
function showViewModalFromData(appTitle, submissionDataObj, statusKey) {
    var fields = FORM_FIELDS[submissionDataObj._formType] || FORM_FIELDS.general;
    var rows = fields.map(function (f) {
        var val = submissionDataObj[f.name] || '—';
        return '<tr><th class="text-muted font-weight-normal" style="width:45%">' +
               escHtml(f.label) + '</th><td><strong>' + escHtml(val) + '</strong></td></tr>';
    }).join('');

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

/* ─────────────────────────────────────────────────────────────────────────────
   DOM READY
───────────────────────────────────────────────────────────────────────────── */
document.addEventListener('DOMContentLoaded', function () {

    // 1. Augment cards on load
    augmentCards();

    // 2. Init submissions table visibility (table/noMsg already correct from PHP)

    /* ── Open modal: populate header + dynamic fields ─────────────────── */
    $('#submitModal').on('show.bs.modal', function (event) {
        var btn      = $(event.relatedTarget);
        var appId    = parseInt(btn.data('application-id'), 10);
        var appTitle = btn.data('application-title') || '';
        var appDesc  = btn.data('application-description') || '';
        var appIndex = parseInt(btn.data('app-index'), 10);
        var meta     = getMeta(appIndex);

        _modal = { appId: appId, appTitle: appTitle, appIndex: appIndex, meta: meta };

        // Header text
        document.getElementById('modal-title').textContent       = appTitle;
        document.getElementById('modal-description').textContent = appDesc;
        document.getElementById('modal-open-date').textContent   = meta.openDate;
        document.getElementById('modal-close-date').textContent  = meta.closeDate;

        var typeBadge = document.getElementById('modal-type-badge');
        if (typeBadge) {
            typeBadge.innerHTML =
                '<i class="fas fa-tag mr-1"></i>' + escHtml(meta.category);
        }

        // Dynamic form fields
        var fields    = FORM_FIELDS[meta.formType] || FORM_FIELDS.general;
        var container = document.getElementById('modal-dynamic-fields');
        container.innerHTML =
            '<form id="application-form">' +
            fields.map(buildField).join('') +
            '</form>';

        // Default today's date for all date inputs
        var today = new Date().toISOString().slice(0, 10);
        container.querySelectorAll('input[type="date"]').forEach(function (el) {
            el.value = today;
        });
    });

    /* ── Clear form on modal close ─────────────────────────────────────── */
    $('#submitModal').on('hidden.bs.modal', function () {
        var container = document.getElementById('modal-dynamic-fields');
        if (container) container.innerHTML = '';
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
        data._formType = _modal.meta.formType;
        data._category = _modal.meta.category;

        var btn = document.getElementById('modal-submit-btn');
        btn.disabled = true;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin mr-1"></i> Αποστολή...';

        var body = new URLSearchParams();
        body.append('ajax_submit',    '1');
        body.append('application_id', _modal.appId);
        body.append('submission_data', JSON.stringify(data));

        fetch('applications.php', {
            method:  'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body:    body.toString()
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
            $('#submitModal').modal('hide');
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
                var status = dbBtn.dataset.subStatus || 'waiting';
                try {
                    var parsed = JSON.parse(raw);
                    showViewModalFromData(title, parsed, status);
                } catch (err) {
                    alert('Δεν είναι δυνατή η εμφάνιση λεπτομερειών.');
                }
            }
        });
    }
});
