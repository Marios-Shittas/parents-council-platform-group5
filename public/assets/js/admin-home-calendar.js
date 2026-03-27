(function () {
    const root = document.getElementById('admin-calendar-app');
    if (!root) {
        return;
    }

    const data = window.adminCalendarData || {};
    const todayIso = typeof data.today === 'string' ? data.today : new Date().toISOString().slice(0, 10);
    const initialSelectedDate = isIsoDate(data.selectedDate) ? data.selectedDate : todayIso;
    const initialDate = parseIsoDate(initialSelectedDate);

    const state = {
        selectedDate: initialSelectedDate,
        month: initialDate.getMonth(),
        year: initialDate.getFullYear(),
    };

    const typeOrder = {
        holiday: 0,
        event: 1,
        announcement: 2,
    };

    const typeLabels = {
        holiday: 'Αργία',
        event: 'Εκδήλωση',
        announcement: 'Ανακοίνωση',
    };

    const typeIcons = {
        holiday: 'fas fa-umbrella-beach',
        event: 'fas fa-calendar-alt',
        announcement: 'fas fa-bullhorn',
    };

    const monthNames = [
        'January',
        'February',
        'March',
        'April',
        'May',
        'June',
        'July',
        'August',
        'September',
        'October',
        'November',
        'December',
    ];

    function isIsoDate(value) {
        return typeof value === 'string' && /^\d{4}-\d{2}-\d{2}$/.test(value);
    }

    function parseIsoDate(isoDate) {
        const parts = String(isoDate).split('-').map(Number);
        return new Date(parts[0], parts[1] - 1, parts[2]);
    }

    function toIsoDate(date) {
        const year = date.getFullYear();
        const month = String(date.getMonth() + 1).padStart(2, '0');
        const day = String(date.getDate()).padStart(2, '0');
        return `${year}-${month}-${day}`;
    }

    function escapeHtml(value) {
        return String(value ?? '')
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#39;');
    }

    function formatLongDate(isoDate) {
        return new Intl.DateTimeFormat('el-GR', {
            weekday: 'long',
            day: 'numeric',
            month: 'long',
            year: 'numeric',
        }).format(parseIsoDate(isoDate));
    }

    function formatShortDate(isoDate) {
        return new Intl.DateTimeFormat('el-GR', {
            day: '2-digit',
            month: '2-digit',
            year: 'numeric',
        }).format(parseIsoDate(isoDate));
    }

    function formatTime(value) {
        if (!value) {
            return 'Χωρίς συγκεκριμένη ώρα';
        }

        return value;
    }

    function getItemsForDate(isoDate) {
        return (Array.isArray(data.items) ? data.items : [])
            .filter((item) => item && item.date === isoDate)
            .slice()
            .sort((left, right) => {
                const leftType = typeOrder[left.type] ?? 99;
                const rightType = typeOrder[right.type] ?? 99;

                if (leftType !== rightType) {
                    return leftType - rightType;
                }

                const leftTime = left.time || '';
                const rightTime = right.time || '';
                if (leftTime !== rightTime) {
                    return leftTime.localeCompare(rightTime);
                }

                return String(left.title || '').localeCompare(String(right.title || ''), 'el');
            });
    }

    function getCounts(items) {
        return items.reduce((accumulator, item) => {
            if (accumulator[item.type] !== undefined) {
                accumulator[item.type] += 1;
            }
            return accumulator;
        }, { holiday: 0, event: 0, announcement: 0 });
    }

    function syncFormFields() {
        document.querySelectorAll('[data-calendar-date-field]').forEach((field) => {
            field.value = state.selectedDate;
        });

        document.querySelectorAll('[data-redirect-date-field]').forEach((field) => {
            field.value = state.selectedDate;
        });

        document.querySelectorAll('[data-calendar-selected-label]').forEach((node) => {
            node.textContent = formatLongDate(state.selectedDate);
        });
    }

    function openModal(modalSelector) {
        syncFormFields();

        if (window.jQuery) {
            window.jQuery(modalSelector).modal('show');
        }
    }

    function renderCalendarCells() {
        const firstDay = new Date(state.year, state.month, 1).getDay();
        const daysInMonth = new Date(state.year, state.month + 1, 0).getDate();
        const startOffset = firstDay === 0 ? 6 : firstDay - 1;
        const cells = [];

        for (let index = 0; index < startOffset; index += 1) {
            cells.push('<div class="admin-cal-empty"></div>');
        }

        for (let day = 1; day <= daysInMonth; day += 1) {
            const isoDate = toIsoDate(new Date(state.year, state.month, day));
            const dayItems = getItemsForDate(isoDate);
            const previewItem = dayItems[0] || null;
            const isToday = isoDate === todayIso;
            const isSelected = isoDate === state.selectedDate;

            let previewHtml = '<span class="day-preview-empty">Κενή μέρα</span>';
            if (previewItem) {
                const previewClass = previewItem.type === 'holiday'
                    ? 'holiday-preview'
                    : previewItem.type === 'announcement'
                        ? 'announcement-preview'
                        : '';

                previewHtml = `
                    <span class="event-title-preview ${previewClass}">
                        ${escapeHtml(previewItem.title)}
                    </span>
                    ${dayItems.length > 1 ? `<span class="day-preview-count">+${dayItems.length - 1}</span>` : ''}
                `;
            }

            cells.push(`
                <button
                    type="button"
                    class="admin-cal-cell ${dayItems.length > 0 ? 'has-items' : 'is-empty'} ${isToday ? 'current-day' : 'normal-day'} ${isSelected ? 'is-selected' : ''}"
                    data-date="${isoDate}"
                >
                    <span class="day-number">${day}</span>
                    <span class="day-preview-wrap">${previewHtml}</span>
                </button>
            `);
        }

        return cells.join('');
    }

    function renderSelectedDayPanel() {
        const items = getItemsForDate(state.selectedDate);
        const counts = getCounts(items);

        const itemsHtml = items.length > 0
            ? items.map((item) => `
                <article class="day-item-card day-item-${escapeHtml(item.type)}">
                    <div class="day-item-top">
                        <span class="day-item-badge badge-${escapeHtml(item.type)}">
                            <i class="${escapeHtml(typeIcons[item.type] || 'fas fa-circle')}"></i>
                            ${escapeHtml(typeLabels[item.type] || item.type)}
                        </span>
                        <a class="day-item-link" href="${escapeHtml(item.source_url || '#')}">${escapeHtml(item.source_label || 'Άνοιγμα πάνελ')}</a>
                    </div>
                    <h4>${escapeHtml(item.title)}</h4>
                    <p>${escapeHtml(item.description || 'Δεν υπάρχει περιγραφή.')}</p>
                    <div class="day-item-meta">
                        <span><i class="far fa-calendar mr-1"></i>${escapeHtml(formatShortDate(item.date))}</span>
                        <span><i class="far fa-clock mr-1"></i>${escapeHtml(formatTime(item.time))}</span>
                    </div>
                </article>
            `).join('')
            : `
                <div class="day-empty-state">
                    <i class="far fa-calendar-times"></i>
                    <h4>Δεν υπάρχει καταχώριση για αυτή τη μέρα</h4>
                    <p>Επίλεξε ένα από τα κουμπιά πιο κάτω για να προσθέσεις νέα εκδήλωση, ανακοίνωση ή αργία.</p>
                </div>
            `;

        return `
            <div class="selected-day-panel card-custom">
                <div class="selected-day-header">
                    <div>
                        <p class="selected-day-kicker">Επιλεγμένη ημέρα</p>
                        <h3>${escapeHtml(formatLongDate(state.selectedDate))}</h3>
                    </div>
                    <span class="selected-day-chip">${items.length} ${items.length === 1 ? 'εγγραφή' : 'εγγραφές'}</span>
                </div>

                <div class="selected-day-counts">
                    <span class="mini-chip holiday-chip">Αργίες: ${counts.holiday}</span>
                    <span class="mini-chip event-chip">Εκδηλώσεις: ${counts.event}</span>
                    <span class="mini-chip announcement-chip">Ανακοινώσεις: ${counts.announcement}</span>
                </div>

                <div class="selected-day-actions">
                    <button type="button" class="btn btn-primary-custom action-btn" data-open-modal="#createEventModal">
                        <i class="fas fa-calendar-plus mr-1"></i>Νέα Εκδήλωση
                    </button>
                    <button type="button" class="btn btn-primary-custom action-btn" data-open-modal="#createAnnouncementModal">
                        <i class="fas fa-bullhorn mr-1"></i>Νέα Ανακοίνωση
                    </button>
                    <button type="button" class="btn btn-primary-custom action-btn" data-open-modal="#createHolidayModal">
                        <i class="fas fa-umbrella-beach mr-1"></i>Νέα Αργία
                    </button>
                </div>

                <div class="selected-day-list">
                    ${itemsHtml}
                </div>
            </div>
        `;
    }

    function render() {
        root.innerHTML = `
            <div class="calendar-dashboard-grid">
                <section class="calendar-panel card-custom">
                    <div class="calendar-panel-header">
                        <div>
                            <p class="calendar-panel-kicker">Ίδιο ημερολόγιο με το public home</p>
                            <h2>Προβολή σχολικής δραστηριότητας ανά ημέρα</h2>
                        </div>
                        <div class="calendar-legend">
                            <span class="legend-item"><span class="legend-dot dot-event"></span>Εκδηλώσεις</span>
                            <span class="legend-item"><span class="legend-dot dot-announcement"></span>Ανακοινώσεις</span>
                            <span class="legend-item"><span class="legend-dot dot-holiday"></span>Αργίες</span>
                        </div>
                    </div>

                    <div class="admin-calendar-shell">
                        <div class="cal-header">
                            <span>
                                <span class="cal-month">${monthNames[state.month]}</span>
                                <span> </span>
                                <span class="cal-year">${state.year}</span>
                            </span>
                            <div class="calendar-nav-group">
                                <button type="button" class="calendar-nav" data-nav="-1">&lt;</button>
                                <button type="button" class="calendar-nav" data-nav="1">&gt;</button>
                            </div>
                        </div>

                        <div class="cal-row cal-days-row">
                            ${['MON', 'TUE', 'WED', 'THU', 'FRI', 'SAT', 'SUN']
                                .map((day) => `<div class="cal-cell day-name-cell">${day}</div>`)
                                .join('')}
                        </div>

                        <div class="admin-cal-grid">
                            ${renderCalendarCells()}
                        </div>
                    </div>
                </section>

                ${renderSelectedDayPanel()}
            </div>
        `;

        root.querySelectorAll('[data-date]').forEach((button) => {
            button.addEventListener('click', () => {
                state.selectedDate = button.getAttribute('data-date') || state.selectedDate;
                syncFormFields();
                render();
            });
        });

        root.querySelectorAll('[data-nav]').forEach((button) => {
            button.addEventListener('click', () => {
                const direction = Number(button.getAttribute('data-nav') || 0);
                let nextMonth = state.month + direction;
                let nextYear = state.year;

                if (nextMonth < 0) {
                    nextMonth = 11;
                    nextYear -= 1;
                }

                if (nextMonth > 11) {
                    nextMonth = 0;
                    nextYear += 1;
                }

                state.month = nextMonth;
                state.year = nextYear;
                render();
            });
        });

        root.querySelectorAll('[data-open-modal]').forEach((button) => {
            button.addEventListener('click', () => {
                const selector = button.getAttribute('data-open-modal');
                if (selector) {
                    openModal(selector);
                }
            });
        });

        syncFormFields();
    }

    render();
}());
