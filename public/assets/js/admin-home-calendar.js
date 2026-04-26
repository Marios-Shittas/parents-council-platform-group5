// Arxeio: public\assets\js\admin-home-calendar.js
// Rolos: Xeirizetai frontend symperifora sto admin panel, opos formaes, modals, filters i React components.
// Simeiosi: Prosoxi: einai gia admin, opote kratame elegxous rolou kai feedback kathara gia ton diaxeiristi.
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
        holiday: 'Î‘ÏÎ³Î¯Î±',
        event: 'Î•ÎºÎ´Î®Î»Ï‰ÏƒÎ·',
        announcement: 'Î‘Î½Î±ÎºÎ¿Î¯Î½Ï‰ÏƒÎ·',
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

    // Perigrafei ti leitourgia tou antistoixou tmimatos me emfasi sti statherotita kai tin egkyrotita dedomenon.
    function isIsoDate(value) {
        return typeof value === 'string' && /^\d{4}-\d{2}-\d{2}$/.test(value);
    }

    // Perigrafei ti leitourgia tou antistoixou tmimatos me emfasi sti statherotita kai tin egkyrotita dedomenon.
    function parseIsoDate(isoDate) {
        const parts = String(isoDate).split('-').map(Number);
        return new Date(parts[0], parts[1] - 1, parts[2]);
    }

    // Perigrafei ti leitourgia tou antistoixou tmimatos me emfasi sti statherotita kai tin egkyrotita dedomenon.
    function toIsoDate(date) {
        const year = date.getFullYear();
        const month = String(date.getMonth() + 1).padStart(2, '0');
        const day = String(date.getDate()).padStart(2, '0');
        return `${year}-${month}-${day}`;
    }

    // Perigrafei ti leitourgia tou antistoixou tmimatos me emfasi sti statherotita kai tin egkyrotita dedomenon.
    function escapeHtml(value) {
        return String(value ?? '')
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#39;');
    }

    // Perigrafei ti leitourgia tou antistoixou tmimatos me emfasi sti statherotita kai tin egkyrotita dedomenon.
    function formatLongDate(isoDate) {
        return new Intl.DateTimeFormat('el-GR', {
            weekday: 'long',
            day: 'numeric',
            month: 'long',
            year: 'numeric',
        }).format(parseIsoDate(isoDate));
    }

    // Perigrafei ti leitourgia tou antistoixou tmimatos me emfasi sti statherotita kai tin egkyrotita dedomenon.
    function formatShortDate(isoDate) {
        return new Intl.DateTimeFormat('el-GR', {
            day: '2-digit',
            month: '2-digit',
            year: 'numeric',
        }).format(parseIsoDate(isoDate));
    }

    // Perigrafei ti leitourgia tou antistoixou tmimatos me emfasi sti statherotita kai tin egkyrotita dedomenon.
    function formatTime(value) {
        if (!value) {
            return 'Î§Ï‰ÏÎ¯Ï‚ ÏƒÏ…Î³ÎºÎµÎºÏÎ¹Î¼Î­Î½Î· ÏŽÏÎ±';
        }

        return value;
    }

    // Perigrafei ti leitourgia tou antistoixou tmimatos me emfasi sti statherotita kai tin egkyrotita dedomenon.
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

    // Perigrafei ti leitourgia tou antistoixou tmimatos me emfasi sti statherotita kai tin egkyrotita dedomenon.
    function getCounts(items) {
        return items.reduce((accumulator, item) => {
            if (accumulator[item.type] !== undefined) {
                accumulator[item.type] += 1;
            }
            return accumulator;
        }, { holiday: 0, event: 0, announcement: 0 });
    }

    // Perigrafei ti leitourgia tou antistoixou tmimatos me emfasi sti statherotita kai tin egkyrotita dedomenon.
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

    // Perigrafei ti leitourgia tou antistoixou tmimatos me emfasi sti statherotita kai tin egkyrotita dedomenon.
    function openModal(modalSelector) {
        syncFormFields();

        if (window.jQuery) {
            window.jQuery(modalSelector).modal('show');
        }
    }

    // Perigrafei ti leitourgia tou antistoixou tmimatos me emfasi sti statherotita kai tin egkyrotita dedomenon.
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

            let previewHtml = '<span class="day-preview-empty">ÎšÎµÎ½Î® Î¼Î­ÏÎ±</span>';
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

    // Perigrafei ti leitourgia tou antistoixou tmimatos me emfasi sti statherotita kai tin egkyrotita dedomenon.
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
                        <a class="day-item-link" href="${escapeHtml(item.source_url || '#')}">${escapeHtml(item.source_label || 'Î†Î½Î¿Î¹Î³Î¼Î± Ï€Î¬Î½ÎµÎ»')}</a>
                    </div>
                    <h4>${escapeHtml(item.title)}</h4>
                    <p>${escapeHtml(item.description || 'Î”ÎµÎ½ Ï…Ï€Î¬ÏÏ‡ÎµÎ¹ Ï€ÎµÏÎ¹Î³ÏÎ±Ï†Î®.')}</p>
                    <div class="day-item-meta">
                        <span><i class="far fa-calendar mr-1"></i>${escapeHtml(formatShortDate(item.date))}</span>
                        <span><i class="far fa-clock mr-1"></i>${escapeHtml(formatTime(item.time))}</span>
                    </div>
                </article>
            `).join('')
            : `
                <div class="day-empty-state">
                    <i class="far fa-calendar-times"></i>
                    <h4>Î”ÎµÎ½ Ï…Ï€Î¬ÏÏ‡ÎµÎ¹ ÎºÎ±Ï„Î±Ï‡ÏŽÏÎ¹ÏƒÎ· Î³Î¹Î± Î±Ï…Ï„Î® Ï„Î· Î¼Î­ÏÎ±</h4>
                    <p>Î•Ï€Î¯Î»ÎµÎ¾Îµ Î­Î½Î± Î±Ï€ÏŒ Ï„Î± ÎºÎ¿Ï…Î¼Ï€Î¹Î¬ Ï€Î¹Î¿ ÎºÎ¬Ï„Ï‰ Î³Î¹Î± Î½Î± Ï€ÏÎ¿ÏƒÎ¸Î­ÏƒÎµÎ¹Ï‚ Î½Î­Î± ÎµÎºÎ´Î®Î»Ï‰ÏƒÎ·, Î±Î½Î±ÎºÎ¿Î¯Î½Ï‰ÏƒÎ· Î® Î±ÏÎ³Î¯Î±.</p>
                </div>
            `;

        return `
            <div class="selected-day-panel card-custom">
                <div class="selected-day-header">
                    <div>
                        <p class="selected-day-kicker">Î•Ï€Î¹Î»ÎµÎ³Î¼Î­Î½Î· Î·Î¼Î­ÏÎ±</p>
                        <h3>${escapeHtml(formatLongDate(state.selectedDate))}</h3>
                    </div>
                    <span class="selected-day-chip">${items.length} ${items.length === 1 ? 'ÎµÎ³Î³ÏÎ±Ï†Î®' : 'ÎµÎ³Î³ÏÎ±Ï†Î­Ï‚'}</span>
                </div>

                <div class="selected-day-counts">
                    <span class="mini-chip holiday-chip">Î‘ÏÎ³Î¯ÎµÏ‚: ${counts.holiday}</span>
                    <span class="mini-chip event-chip">Î•ÎºÎ´Î·Î»ÏŽÏƒÎµÎ¹Ï‚: ${counts.event}</span>
                    <span class="mini-chip announcement-chip">Î‘Î½Î±ÎºÎ¿Î¹Î½ÏŽÏƒÎµÎ¹Ï‚: ${counts.announcement}</span>
                </div>

                <div class="selected-day-actions">
                    <button type="button" class="btn btn-primary-custom action-btn" data-open-modal="#createEventModal">
                        <i class="fas fa-calendar-plus mr-1"></i>ÎÎ­Î± Î•ÎºÎ´Î®Î»Ï‰ÏƒÎ·
                    </button>
                    <button type="button" class="btn btn-primary-custom action-btn" data-open-modal="#createAnnouncementModal">
                        <i class="fas fa-bullhorn mr-1"></i>ÎÎ­Î± Î‘Î½Î±ÎºÎ¿Î¯Î½Ï‰ÏƒÎ·
                    </button>
                    <button type="button" class="btn btn-primary-custom action-btn" data-open-modal="#createHolidayModal">
                        <i class="fas fa-umbrella-beach mr-1"></i>ÎÎ­Î± Î‘ÏÎ³Î¯Î±
                    </button>
                </div>

                <div class="selected-day-list">
                    ${itemsHtml}
                </div>
            </div>
        `;
    }

    // Perigrafei ti leitourgia tou antistoixou tmimatos me emfasi sti statherotita kai tin egkyrotita dedomenon.
    function render() {
        root.innerHTML = `
            <div class="calendar-dashboard-grid">
                <section class="calendar-panel card-custom">
                    <div class="calendar-panel-header">
                        <div>
                            <p class="calendar-panel-kicker">ÎŠÎ´Î¹Î¿ Î·Î¼ÎµÏÎ¿Î»ÏŒÎ³Î¹Î¿ Î¼Îµ Ï„Î¿ public home</p>
                            <h2>Î ÏÎ¿Î²Î¿Î»Î® ÏƒÏ‡Î¿Î»Î¹ÎºÎ®Ï‚ Î´ÏÎ±ÏƒÏ„Î·ÏÎ¹ÏŒÏ„Î·Ï„Î±Ï‚ Î±Î½Î¬ Î·Î¼Î­ÏÎ±</h2>
                        </div>
                        <div class="calendar-legend">
                            <span class="legend-item"><span class="legend-dot dot-event"></span>Î•ÎºÎ´Î·Î»ÏŽÏƒÎµÎ¹Ï‚</span>
                            <span class="legend-item"><span class="legend-dot dot-announcement"></span>Î‘Î½Î±ÎºÎ¿Î¹Î½ÏŽÏƒÎµÎ¹Ï‚</span>
                            <span class="legend-item"><span class="legend-dot dot-holiday"></span>Î‘ÏÎ³Î¯ÎµÏ‚</span>
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
