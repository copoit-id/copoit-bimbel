import './bootstrap';

/**
 * Admin select enhancer.
 *
 * The native select remains in the form so existing validation, JavaScript,
 * Alpine bindings, and server-side request values keep working. The visible
 * control is only a styled representation of that native field.
 *
 * Add `data-native-select` to a select only when a future field needs to opt
 * out of this standard admin dropdown.
 */
const initializeAdminSelects = () => {
    if (!document.body.hasAttribute('data-admin-selects')) {
        return;
    }

    let activeInstance = null;
    let instanceCount = 0;

    const widthClasses = (select) => Array.from(select.classList)
        .filter((className) => /^(?:(?:sm|md|lg|xl|2xl):)?w-/.test(className));

    const close = (instance) => {
        if (!instance || !instance.open) {
            return;
        }

        instance.open = false;
        instance.trigger.setAttribute('aria-expanded', 'false');
        instance.menu.hidden = true;

        if (activeInstance === instance) {
            activeInstance = null;
        }
    };

    const closeActive = () => close(activeInstance);

    const createOptionButton = (instance, option) => {
        const button = document.createElement('button');
        const isSelected = option.selected;

        button.type = 'button';
        button.className = 'admin-select__option';
        button.setAttribute('role', 'option');
        button.setAttribute('aria-selected', String(isSelected));
        button.disabled = option.disabled;
        button.dataset.value = option.value;

        const label = document.createElement('span');
        label.className = 'admin-select__option-label';
        label.textContent = option.textContent.trim();
        button.appendChild(label);

        const check = document.createElement('i');
        check.className = 'ri-check-line admin-select__option-check';
        check.setAttribute('aria-hidden', 'true');
        button.appendChild(check);

        button.addEventListener('click', () => {
            if (option.disabled) {
                return;
            }

            if (instance.select.value !== option.value) {
                instance.select.value = option.value;
                instance.select.dispatchEvent(new Event('input', { bubbles: true }));
                instance.select.dispatchEvent(new Event('change', { bubbles: true }));
            }

            instance.sync();
            close(instance);
            instance.trigger.focus();
        });

        return button;
    };

    const enhance = (select) => {
        if (
            select.dataset.adminSelectInitialized === 'true'
            || select.matches('[data-native-select], [multiple]')
            || select.closest('[data-admin-select-menu]')
        ) {
            return;
        }

        instanceCount += 1;
        const instanceId = `admin-select-${instanceCount}`;
        const wrapper = document.createElement('div');
        const trigger = document.createElement('button');
        const menu = document.createElement('div');
        const menuId = `${instanceId}-menu`;
        const sourceId = select.id || instanceId;
        const escapedSourceId = window.CSS?.escape
            ? window.CSS.escape(sourceId)
            : sourceId.replace(/[^a-zA-Z0-9_-]/g, '\\$&');
        const sourceLabel = document.querySelector(`label[for="${escapedSourceId}"]`);

        select.dataset.adminSelectInitialized = 'true';
        select.classList.add('admin-select__native');
        select.tabIndex = -1;
        select.setAttribute('aria-hidden', 'true');

        wrapper.className = 'admin-select';
        wrapper.classList.add(...widthClasses(select));
        if (select.classList.contains('w-full')) {
            wrapper.classList.add('admin-select--full');
        }
        if (select.classList.contains('text-xs') || select.classList.contains('py-1')) {
            wrapper.classList.add('admin-select--compact');
        }
        wrapper.dataset.adminSelect = instanceId;

        trigger.type = 'button';
        trigger.className = 'admin-select__trigger';
        trigger.id = `${sourceId}-trigger`;
        trigger.setAttribute('aria-controls', menuId);
        trigger.setAttribute('aria-expanded', 'false');
        trigger.setAttribute('aria-haspopup', 'listbox');
        if (sourceLabel) {
            trigger.setAttribute('aria-labelledby', sourceLabel.id || `${sourceId}-label`);
            if (!sourceLabel.id) {
                sourceLabel.id = `${sourceId}-label`;
            }
        } else {
            trigger.setAttribute('aria-label', select.getAttribute('aria-label') || 'Pilih opsi');
        }

        const triggerLabel = document.createElement('span');
        triggerLabel.className = 'admin-select__value';
        const chevron = document.createElement('i');
        chevron.className = 'ri-arrow-down-s-line admin-select__chevron';
        chevron.setAttribute('aria-hidden', 'true');
        trigger.append(triggerLabel, chevron);

        menu.className = 'admin-select__menu';
        menu.id = menuId;
        menu.hidden = true;
        menu.dataset.adminSelectMenu = instanceId;
        menu.setAttribute('role', 'listbox');

        select.insertAdjacentElement('afterend', wrapper);
        wrapper.append(trigger, menu);

        const instance = {
            select,
            wrapper,
            trigger,
            triggerLabel,
            menu,
            open: false,
            sync() {
                const selectedOption = select.selectedOptions[0];
                triggerLabel.textContent = selectedOption?.textContent.trim() || 'Pilih opsi';
                triggerLabel.classList.toggle('admin-select__placeholder', !selectedOption || selectedOption.value === '');
                trigger.disabled = select.disabled;
                wrapper.classList.toggle('admin-select--disabled', select.disabled);

                menu.replaceChildren();
                Array.from(select.children).forEach((child) => {
                    if (child instanceof HTMLOptGroupElement) {
                        const group = document.createElement('div');
                        group.className = 'admin-select__group';

                        if (child.label) {
                            const label = document.createElement('p');
                            label.className = 'admin-select__group-label';
                            label.textContent = child.label;
                            group.appendChild(label);
                        }

                        Array.from(child.children).forEach((option) => {
                            if (option instanceof HTMLOptionElement && !option.hidden) {
                                group.appendChild(createOptionButton(instance, option));
                            }
                        });
                        menu.appendChild(group);
                    } else if (child instanceof HTMLOptionElement && !child.hidden) {
                        menu.appendChild(createOptionButton(instance, child));
                    }
                });
            },
        };

        trigger.addEventListener('click', () => {
            instance.sync();

            if (instance.open) {
                close(instance);
                return;
            }

            closeActive();
            instance.open = true;
            trigger.setAttribute('aria-expanded', 'true');
            menu.hidden = false;
            activeInstance = instance;
        });

        trigger.addEventListener('keydown', (event) => {
            if (event.key === 'Escape') {
                close(instance);
                return;
            }

            if (['ArrowDown', 'ArrowUp', 'Enter', ' '].includes(event.key)) {
                event.preventDefault();
                if (!instance.open) {
                    trigger.click();
                }
                const selected = menu.querySelector('[aria-selected="true"]:not(:disabled)');
                const first = menu.querySelector('.admin-select__option:not(:disabled)');
                (selected || first)?.focus();
            }
        });

        menu.addEventListener('keydown', (event) => {
            if (event.key === 'Escape') {
                event.preventDefault();
                close(instance);
                trigger.focus();
            }
        });

        select.addEventListener('change', () => instance.sync());
        select.addEventListener('input', () => instance.sync());

        const observer = new MutationObserver(() => instance.sync());
        observer.observe(select, {
            childList: true,
            subtree: true,
            attributes: true,
            attributeFilter: ['disabled', 'selected', 'hidden', 'label'],
        });

        select.form?.addEventListener('reset', () => window.setTimeout(() => instance.sync()));
        instance.sync();
    };

    const enhanceAll = (root = document) => {
        root.querySelectorAll?.('select').forEach(enhance);
    };

    document.addEventListener('pointerdown', (event) => {
        if (activeInstance && !activeInstance.wrapper.contains(event.target)) {
            closeActive();
        }
    });

    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape') {
            closeActive();
        }
    });

    const pageObserver = new MutationObserver((mutations) => {
        mutations.forEach((mutation) => {
            mutation.addedNodes.forEach((node) => {
                if (!(node instanceof HTMLElement)) {
                    return;
                }

                if (node.matches('select')) {
                    enhance(node);
                }
                enhanceAll(node);
            });
        });
    });

    pageObserver.observe(document.body, { childList: true, subtree: true });
    enhanceAll();
};

/**
 * Global date and time input enhancer.
 *
 * The native input remains responsible for validation and form submission,
 * while the visible field and picker use a consistent Indonesian presentation.
 * Add `data-native-date` when a field explicitly needs the browser presentation.
 */
const initializeDateInputs = () => {
    const supportedTypes = ['date', 'datetime-local', 'month', 'time'];
    const selector = supportedTypes.map((type) => `input[type="${type}"]`).join(', ');
    const locale = document.body.dataset.dateLocale || 'id-ID';
    const pad = (value) => String(value).padStart(2, '0');
    const toDateValue = (date) => `${date.getFullYear()}-${pad(date.getMonth() + 1)}-${pad(date.getDate())}`;
    const today = () => toDateValue(new Date());
    let activeInstance = null;
    let instanceCount = 0;

    const dateFormatter = new Intl.DateTimeFormat(locale, {
        day: '2-digit',
        month: 'long',
        year: 'numeric',
    });
    const dateTimeFormatter = new Intl.DateTimeFormat(locale, {
        day: '2-digit',
        month: 'long',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
        hourCycle: 'h23',
    });
    const monthFormatter = new Intl.DateTimeFormat(locale, {
        month: 'long',
        year: 'numeric',
    });
    const monthNames = Array.from({ length: 12 }, (_, month) => (
        new Intl.DateTimeFormat(locale, { month: 'long' }).format(new Date(2024, month, 1))
    ));
    const weekdayNames = ['Sen', 'Sel', 'Rab', 'Kam', 'Jum', 'Sab', 'Min'];

    const placeholders = {
        date: 'Pilih tanggal',
        'datetime-local': 'Pilih tanggal & waktu',
        month: 'Pilih bulan',
        time: 'Pilih waktu',
    };

    const layoutClasses = (input) => Array.from(input.classList)
        .filter((className) => /^(?:(?:sm|md|lg|xl|2xl):)?(?:w|min-w|max-w|m|mt|mr|mb|ml|mx|my|self|flex)-/.test(className));

    const parseDate = (value) => {
        const match = /^(\d{4})-(\d{2})-(\d{2})$/.exec(value);
        if (!match) {
            return null;
        }

        const [, year, month, day] = match;
        const parsed = new Date(Number(year), Number(month) - 1, Number(day));

        return Number.isNaN(parsed.getTime()) ? null : parsed;
    };

    const parseDateTime = (value) => {
        const match = /^(\d{4})-(\d{2})-(\d{2})T(\d{2}):(\d{2})/.exec(value);
        if (!match) {
            return null;
        }

        const [, year, month, day, hour, minute] = match;
        const parsed = new Date(
            Number(year),
            Number(month) - 1,
            Number(day),
            Number(hour),
            Number(minute),
        );

        return Number.isNaN(parsed.getTime()) ? null : parsed;
    };

    const formatValue = (input) => {
        if (!input.value) {
            return placeholders[input.type] || 'Pilih tanggal';
        }

        if (input.type === 'time') {
            const match = /^(\d{2}):(\d{2})/.exec(input.value);
            return match ? `${match[1]}.${match[2]}` : input.value;
        }

        if (input.type === 'month') {
            const parsed = parseDate(`${input.value}-01`);
            return parsed ? monthFormatter.format(parsed) : input.value;
        }

        if (input.type === 'datetime-local') {
            const parsed = parseDateTime(input.value);
            return parsed ? dateTimeFormatter.format(parsed).replace(' pukul ', ', ') : input.value;
        }

        const parsed = parseDate(input.value);
        return parsed ? dateFormatter.format(parsed) : input.value;
    };

    const createElement = (tag, className = '', text = '') => {
        const element = document.createElement(tag);
        element.className = className;
        element.textContent = text;
        return element;
    };

    const setButtonIcon = (button, iconClass, label) => {
        button.type = 'button';
        button.setAttribute('aria-label', label);
        const icon = createElement('i', iconClass);
        icon.setAttribute('aria-hidden', 'true');
        button.appendChild(icon);
    };

    const dispatchValue = (instance, value) => {
        const changed = instance.input.value !== value;
        instance.input.value = value;
        instance.sync();

        if (changed) {
            instance.input.dispatchEvent(new Event('input', { bubbles: true }));
            instance.input.dispatchEvent(new Event('change', { bubbles: true }));
        }
    };

    const close = (instance, restoreFocus = false) => {
        if (!instance || !instance.open) {
            return;
        }

        instance.open = false;
        instance.popover.hidden = true;
        instance.input.setAttribute('aria-expanded', 'false');

        if (activeInstance === instance) {
            activeInstance = null;
        }

        if (restoreFocus) {
            instance.input.focus({ preventScroll: true });
        }
    };

    const positionPopover = (instance) => {
        if (!instance.open) {
            return;
        }

        const viewportGap = 12;
        const fieldRect = instance.wrapper.getBoundingClientRect();
        const popover = instance.popover;
        const preferredWidth = instance.input.type === 'time' ? 288 : 352;
        const width = Math.min(preferredWidth, window.innerWidth - (viewportGap * 2));

        popover.style.width = `${width}px`;
        popover.style.maxHeight = `calc(100vh - ${viewportGap * 2}px)`;
        const popoverHeight = popover.offsetHeight;
        const availableBelow = window.innerHeight - fieldRect.bottom - viewportGap - 6;
        const availableAbove = fieldRect.top - viewportGap - 6;
        const openAbove = availableBelow < popoverHeight && availableAbove > availableBelow;
        const availableHeight = Math.max(80, openAbove ? availableAbove : availableBelow);

        popover.style.maxHeight = `${availableHeight}px`;
        const renderedHeight = Math.min(popoverHeight, availableHeight);
        const top = openAbove
            ? fieldRect.top - renderedHeight - 6
            : fieldRect.bottom + 6;
        const left = Math.min(
            Math.max(viewportGap, fieldRect.left),
            window.innerWidth - width - viewportGap,
        );

        popover.style.top = `${Math.max(viewportGap, top)}px`;
        popover.style.left = `${left}px`;
    };

    const isDateAllowed = (input, dateValue) => {
        const min = input.min?.slice(0, 10);
        const max = input.max?.slice(0, 10);
        return (!min || dateValue >= min) && (!max || dateValue <= max);
    };

    const isMonthAllowed = (input, monthValue) => {
        const min = input.min?.slice(0, 7);
        const max = input.max?.slice(0, 7);
        return (!min || monthValue >= min) && (!max || monthValue <= max);
    };

    const selectedDateTimeValue = (instance) => (
        `${instance.state.selectedDate}T${pad(instance.state.hour)}:${pad(instance.state.minute)}`
    );

    const selectedTimeValue = (instance) => (
        `${pad(instance.state.hour)}:${pad(instance.state.minute)}`
    );

    const isFinalValueAllowed = (instance, value) => {
        const { min, max } = instance.input;
        return (!min || value >= min) && (!max || value <= max);
    };

    const syncState = (instance) => {
        const now = new Date();
        const value = instance.input.value;
        let selectedDate = today();
        let hour = now.getHours();
        let minute = now.getMinutes();

        if (instance.input.type === 'date' && value) {
            selectedDate = value;
        } else if (instance.input.type === 'datetime-local' && value) {
            const [datePart, timePart = ''] = value.split('T');
            selectedDate = datePart || selectedDate;
            const [valueHour, valueMinute] = timePart.split(':').map(Number);
            hour = Number.isInteger(valueHour) ? valueHour : hour;
            minute = Number.isInteger(valueMinute) ? valueMinute : minute;
        } else if (instance.input.type === 'month' && value) {
            selectedDate = `${value}-01`;
        } else if (instance.input.type === 'time' && value) {
            const [valueHour, valueMinute] = value.split(':').map(Number);
            hour = Number.isInteger(valueHour) ? valueHour : hour;
            minute = Number.isInteger(valueMinute) ? valueMinute : minute;
        }

        const parsed = parseDate(selectedDate) || new Date();
        instance.state = {
            selectedDate,
            hour,
            minute,
            viewYear: parsed.getFullYear(),
            viewMonth: parsed.getMonth(),
            viewMode: 'calendar',
            yearPickerReturn: 'calendar',
        };
    };

    const renderHeader = (instance, title, onPrevious, onNext, onTitleClick = null) => {
        const header = createElement('div', 'app-date-picker__header');
        const previous = createElement('button', 'app-date-picker__nav');
        const heading = createElement(
            onTitleClick ? 'button' : 'p',
            onTitleClick ? 'app-date-picker__title app-date-picker__title-button' : 'app-date-picker__title',
            title,
        );
        const next = createElement('button', 'app-date-picker__nav');

        setButtonIcon(previous, 'ri-arrow-left-s-line', 'Sebelumnya');
        setButtonIcon(next, 'ri-arrow-right-s-line', 'Berikutnya');
        previous.addEventListener('click', onPrevious);
        next.addEventListener('click', onNext);
        if (onTitleClick) {
            heading.type = 'button';
            heading.setAttribute('aria-label', 'Pilih tahun');
            heading.addEventListener('click', onTitleClick);
        }
        header.append(previous, heading, next);

        return header;
    };

    const yearFromValue = (value) => {
        const year = Number(value?.slice(0, 4));
        return Number.isInteger(year) && year > 0 ? year : null;
    };

    const yearOptions = (instance) => {
        const currentYear = new Date().getFullYear();
        const selectedYear = yearFromValue(instance.state.selectedDate);
        const minYear = yearFromValue(instance.input.min);
        const maxYear = yearFromValue(instance.input.max);
        const startYear = minYear || Math.min(1900, instance.state.viewYear, selectedYear || Infinity);
        const endYear = maxYear || Math.max(currentYear + 100, instance.state.viewYear, selectedYear || 0);

        return Array.from({ length: endYear - startYear + 1 }, (_, index) => startYear + index);
    };

    const renderYearPicker = (instance) => {
        const container = createElement('div', 'app-date-picker__calendar');
        const header = createElement('div', 'app-date-picker__header');
        const back = createElement('button', 'app-date-picker__nav');
        const title = createElement('p', 'app-date-picker__title', 'Pilih tahun');
        const spacer = createElement('span', 'app-date-picker__header-spacer');
        const select = createElement('select', 'app-date-picker__year-select');

        setButtonIcon(back, 'ri-arrow-left-s-line', 'Kembali ke kalender');
        back.addEventListener('click', () => {
            instance.state.viewMode = instance.state.yearPickerReturn || 'calendar';
            instance.render();
        });

        yearOptions(instance).forEach((year) => {
            const option = document.createElement('option');
            option.value = String(year);
            option.textContent = String(year);
            option.selected = year === instance.state.viewYear;
            select.appendChild(option);
        });
        select.setAttribute('aria-label', 'Pilih tahun');
        select.addEventListener('change', () => {
            instance.state.viewYear = Number(select.value);
            instance.state.viewMode = instance.state.yearPickerReturn || 'calendar';
            instance.render();
        });

        header.append(back, title, spacer);
        container.append(header, select);
        return container;
    };

    const renderCalendar = (instance) => {
        const calendar = createElement('div', 'app-date-picker__calendar');
        const titleDate = new Date(instance.state.viewYear, instance.state.viewMonth, 1);
        const header = renderHeader(
            instance,
            monthFormatter.format(titleDate),
            () => {
                instance.state.viewMonth -= 1;
                if (instance.state.viewMonth < 0) {
                    instance.state.viewMonth = 11;
                    instance.state.viewYear -= 1;
                }
                instance.render();
            },
            () => {
                instance.state.viewMonth += 1;
                if (instance.state.viewMonth > 11) {
                    instance.state.viewMonth = 0;
                    instance.state.viewYear += 1;
                }
                instance.render();
            },
            () => {
                instance.state.yearPickerReturn = 'calendar';
                instance.state.viewMode = 'year';
                instance.render();
            },
        );
        const weekdays = createElement('div', 'app-date-picker__weekdays');
        weekdayNames.forEach((weekday) => {
            weekdays.appendChild(createElement('span', '', weekday));
        });

        const days = createElement('div', 'app-date-picker__days');
        const firstDay = new Date(instance.state.viewYear, instance.state.viewMonth, 1);
        const mondayOffset = (firstDay.getDay() + 6) % 7;
        const gridStart = new Date(
            instance.state.viewYear,
            instance.state.viewMonth,
            1 - mondayOffset,
        );

        for (let index = 0; index < 42; index += 1) {
            const date = new Date(
                gridStart.getFullYear(),
                gridStart.getMonth(),
                gridStart.getDate() + index,
            );
            const dateValue = toDateValue(date);
            const day = createElement('button', 'app-date-picker__day', String(date.getDate()));
            const outsideMonth = date.getMonth() !== instance.state.viewMonth;

            day.type = 'button';
            day.dataset.date = dateValue;
            day.disabled = !isDateAllowed(instance.input, dateValue);
            day.classList.toggle('app-date-picker__day--outside', outsideMonth);
            day.classList.toggle('app-date-picker__day--today', dateValue === today());
            day.classList.toggle('app-date-picker__day--selected', dateValue === instance.state.selectedDate);
            day.setAttribute('aria-label', dateFormatter.format(date));
            day.setAttribute('aria-pressed', String(dateValue === instance.state.selectedDate));

            day.addEventListener('click', () => {
                instance.state.selectedDate = dateValue;
                instance.state.viewYear = date.getFullYear();
                instance.state.viewMonth = date.getMonth();

                if (instance.input.type === 'date') {
                    dispatchValue(instance, dateValue);
                    close(instance, true);
                    return;
                }

                instance.render();
            });
            days.appendChild(day);
        }

        calendar.append(header, weekdays, days);
        return calendar;
    };

    const renderMonthPicker = (instance) => {
        const container = createElement('div', 'app-date-picker__calendar');
        const header = renderHeader(
            instance,
            String(instance.state.viewYear),
            () => {
                instance.state.viewYear -= 1;
                instance.render();
            },
            () => {
                instance.state.viewYear += 1;
                instance.render();
            },
            () => {
                instance.state.yearPickerReturn = 'month';
                instance.state.viewMode = 'year';
                instance.render();
            },
        );
        const months = createElement('div', 'app-date-picker__months');
        const selectedMonth = instance.input.value || instance.state.selectedDate.slice(0, 7);

        monthNames.forEach((monthName, monthIndex) => {
            const monthValue = `${instance.state.viewYear}-${pad(monthIndex + 1)}`;
            const button = createElement('button', 'app-date-picker__month', monthName);
            button.type = 'button';
            button.disabled = !isMonthAllowed(instance.input, monthValue);
            button.classList.toggle('app-date-picker__month--selected', monthValue === selectedMonth);
            button.addEventListener('click', () => {
                dispatchValue(instance, monthValue);
                close(instance, true);
            });
            months.appendChild(button);
        });

        container.append(header, months);
        return container;
    };

    const updateTimeSelection = (instance, kind, value, panel) => {
        instance.state[kind] = value;
        panel.querySelectorAll(`[data-time-kind="${kind}"]`).forEach((button) => {
            const selected = Number(button.dataset.timeValue) === value;
            button.classList.toggle('app-date-picker__time-option--selected', selected);
            button.setAttribute('aria-pressed', String(selected));
        });

        const apply = instance.popover.querySelector('[data-date-picker-apply]');
        if (apply) {
            const finalValue = instance.input.type === 'time'
                ? selectedTimeValue(instance)
                : selectedDateTimeValue(instance);
            apply.disabled = !isFinalValueAllowed(instance, finalValue);
        }
    };

    const renderTimePanel = (instance) => {
        const panel = createElement('div', 'app-date-picker__time');
        const label = createElement(
            'p',
            'app-date-picker__section-title',
            instance.input.type === 'time' ? 'Pilih jam' : 'Jam',
        );
        const columns = createElement('div', 'app-date-picker__time-columns');

        const createColumn = (kind, count, columnLabel) => {
            const column = createElement('div', 'app-date-picker__time-column');
            const heading = createElement('span', 'app-date-picker__time-label', columnLabel);
            const list = createElement('div', 'app-date-picker__time-list');

            list.setAttribute('role', 'listbox');
            for (let value = 0; value < count; value += 1) {
                const selected = instance.state[kind] === value;
                const button = createElement(
                    'button',
                    'app-date-picker__time-option',
                    pad(value),
                );
                button.type = 'button';
                button.dataset.timeKind = kind;
                button.dataset.timeValue = String(value);
                button.classList.toggle('app-date-picker__time-option--selected', selected);
                button.setAttribute('aria-pressed', String(selected));
                button.addEventListener('click', () => {
                    updateTimeSelection(instance, kind, value, panel);
                });
                list.appendChild(button);
            }

            window.requestAnimationFrame(() => {
                const selected = list.querySelector('.app-date-picker__time-option--selected');
                if (selected) {
                    list.scrollTop = selected.offsetTop - (list.clientHeight / 2) + (selected.clientHeight / 2);
                }
            });

            column.append(heading, list);
            return column;
        };

        columns.append(
            createColumn('hour', 24, 'Jam'),
            createColumn('minute', 60, 'Menit'),
        );
        panel.append(label, columns);
        return panel;
    };

    const renderFooter = (instance) => {
        const footer = createElement('div', 'app-date-picker__footer');
        const secondary = createElement('div', 'app-date-picker__footer-secondary');
        const nowButton = createElement(
            'button',
            'app-date-picker__text-button',
            instance.input.type === 'time' ? 'Sekarang' : 'Hari ini',
        );
        nowButton.type = 'button';
        nowButton.addEventListener('click', () => {
            const now = new Date();
            instance.state.selectedDate = toDateValue(now);
            instance.state.viewYear = now.getFullYear();
            instance.state.viewMonth = now.getMonth();
            instance.state.hour = now.getHours();
            instance.state.minute = now.getMinutes();

            if (instance.input.type === 'date' && isDateAllowed(instance.input, instance.state.selectedDate)) {
                dispatchValue(instance, instance.state.selectedDate);
                close(instance, true);
                return;
            }

            instance.render();
        });
        secondary.appendChild(nowButton);

        if (!instance.input.required) {
            const clear = createElement('button', 'app-date-picker__text-button', 'Bersihkan');
            clear.type = 'button';
            clear.addEventListener('click', () => {
                dispatchValue(instance, '');
                close(instance, true);
            });
            secondary.appendChild(clear);
        }

        footer.appendChild(secondary);

        if (['time', 'datetime-local'].includes(instance.input.type)) {
            const apply = createElement('button', 'app-date-picker__apply', 'Terapkan');
            const finalValue = instance.input.type === 'time'
                ? selectedTimeValue(instance)
                : selectedDateTimeValue(instance);
            apply.type = 'button';
            apply.dataset.datePickerApply = '';
            apply.disabled = !isFinalValueAllowed(instance, finalValue);
            apply.addEventListener('click', () => {
                const value = instance.input.type === 'time'
                    ? selectedTimeValue(instance)
                    : selectedDateTimeValue(instance);
                if (!isFinalValueAllowed(instance, value)) {
                    return;
                }
                dispatchValue(instance, value);
                close(instance, true);
            });
            footer.appendChild(apply);
        }

        return footer;
    };

    const enhance = (input) => {
        if (
            input.dataset.dateInputInitialized === 'true'
            || input.hasAttribute('data-native-date')
            || !supportedTypes.includes(input.type)
        ) {
            return;
        }

        const wrapper = document.createElement('span');
        const display = document.createElement('span');
        const value = document.createElement('span');
        const icon = document.createElement('i');
        const popover = document.createElement('div');

        instanceCount += 1;
        input.dataset.dateInputInitialized = 'true';
        input.classList.add('app-date__native');
        input.setAttribute('aria-expanded', 'false');
        input.setAttribute('aria-haspopup', 'dialog');

        wrapper.className = 'app-date';
        wrapper.classList.add(...layoutClasses(input));
        if (input.classList.contains('w-full')) {
            wrapper.classList.add('app-date--full');
        }
        if (input.classList.contains('text-xs') || input.classList.contains('py-1')) {
            wrapper.classList.add('app-date--compact');
        }

        display.className = 'app-date__display';
        display.setAttribute('aria-hidden', 'true');

        value.className = 'app-date__value';
        icon.className = input.type === 'time'
            ? 'ri-time-line app-date__icon'
            : 'ri-calendar-line app-date__icon';
        icon.setAttribute('aria-hidden', 'true');
        display.append(value, icon);

        input.insertAdjacentElement('afterend', wrapper);
        wrapper.append(input, display);

        popover.className = 'app-date-picker';
        popover.id = `app-date-picker-${instanceCount}`;
        popover.hidden = true;
        popover.setAttribute('role', 'dialog');
        popover.setAttribute('aria-modal', 'false');
        popover.setAttribute('aria-label', placeholders[input.type]);
        input.setAttribute('aria-controls', popover.id);
        document.body.appendChild(popover);

        const instance = {
            input,
            wrapper,
            value,
            popover,
            state: {},
            open: false,
            sync() {
                value.textContent = formatValue(input);
                value.classList.toggle('app-date__placeholder', !input.value);
                wrapper.classList.toggle('app-date--disabled', input.disabled || input.readOnly);
            },
            render() {
                popover.replaceChildren();
                popover.classList.toggle(
                    'app-date-picker--year-picker',
                    instance.state.viewMode === 'year',
                );

                if (instance.state.viewMode === 'year') {
                    popover.appendChild(renderYearPicker(instance));
                } else if (input.type === 'month') {
                    popover.appendChild(renderMonthPicker(instance));
                } else {
                    if (input.type !== 'time') {
                        popover.appendChild(renderCalendar(instance));
                    }
                    if (['time', 'datetime-local'].includes(input.type)) {
                        popover.appendChild(renderTimePanel(instance));
                    }
                }

                popover.appendChild(renderFooter(instance));
                window.requestAnimationFrame(() => positionPopover(instance));
            },
        };

        const openPicker = () => {
            if (input.disabled || input.readOnly) {
                return;
            }

            if (instance.open) {
                close(instance, true);
                return;
            }

            close(activeInstance);
            syncState(instance);
            instance.open = true;
            input.setAttribute('aria-expanded', 'true');
            popover.hidden = false;
            activeInstance = instance;
            instance.render();
        };

        input.addEventListener('pointerdown', (event) => {
            event.preventDefault();
            input.focus({ preventScroll: true });
            openPicker();
        });
        input.addEventListener('click', (event) => event.preventDefault());
        input.addEventListener('keydown', (event) => {
            if (['Enter', ' ', 'ArrowDown'].includes(event.key)) {
                event.preventDefault();
                openPicker();
            } else if (event.key === 'Escape') {
                close(instance, true);
            }
        });
        input.addEventListener('input', instance.sync);
        input.addEventListener('change', instance.sync);

        const observer = new MutationObserver(instance.sync);
        observer.observe(input, {
            attributes: true,
            attributeFilter: ['disabled', 'readonly', 'value', 'min', 'max'],
        });

        input.form?.addEventListener('reset', () => window.setTimeout(instance.sync));
        instance.sync();
    };

    const enhanceAll = (root = document) => {
        if (root instanceof HTMLInputElement && root.matches(selector)) {
            enhance(root);
        }
        root.querySelectorAll?.(selector).forEach(enhance);
    };

    const pageObserver = new MutationObserver((mutations) => {
        mutations.forEach((mutation) => {
            mutation.addedNodes.forEach((node) => {
                if (node instanceof HTMLElement) {
                    enhanceAll(node);
                }
            });
        });
    });

    pageObserver.observe(document.body, { childList: true, subtree: true });

    document.addEventListener('pointerdown', (event) => {
        if (
            activeInstance
            && !activeInstance.wrapper.contains(event.target)
            && !activeInstance.popover.contains(event.target)
        ) {
            close(activeInstance);
        }
    });
    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape') {
            close(activeInstance, true);
        }
    });
    document.addEventListener('focusin', (event) => {
        if (
            activeInstance
            && !activeInstance.wrapper.contains(event.target)
            && !activeInstance.popover.contains(event.target)
        ) {
            close(activeInstance);
        }
    });
    window.addEventListener('resize', () => positionPopover(activeInstance));
    window.addEventListener('scroll', () => positionPopover(activeInstance), true);

    enhanceAll();
};

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initializeAdminSelects, { once: true });
    document.addEventListener('DOMContentLoaded', initializeDateInputs, { once: true });
} else {
    initializeAdminSelects();
    initializeDateInputs();
}

/**
 * Shared application dialog for JavaScript alerts and confirmations.
 *
 * Use `window.appAlert(message)` for notifications and
 * `await window.appConfirm(message)` before a destructive or important action.
 * Native `confirm()` remains available while legacy synchronous handlers are
 * migrated one at a time.
 */
const initializeAppDialog = () => {
    if (window.appDialog) {
        return;
    }

    const queue = [];
    let active = null;
    let bodyWasLocked = false;

    const variantStyles = {
        info: {
            icon: 'ri-information-line',
            iconClass: ['bg-blue-100', 'text-blue-600'],
            confirmClass: ['bg-primary', 'text-white', 'hover:bg-primary/90'],
        },
        success: {
            icon: 'ri-checkbox-circle-line',
            iconClass: ['bg-green-100', 'text-green-600'],
            confirmClass: ['bg-green-600', 'text-white', 'hover:bg-green-700'],
        },
        warning: {
            icon: 'ri-alert-line',
            iconClass: ['bg-amber-100', 'text-amber-700'],
            confirmClass: ['bg-amber-500', 'text-white', 'hover:bg-amber-600'],
        },
        danger: {
            icon: 'ri-error-warning-line',
            iconClass: ['bg-red-100', 'text-red-600'],
            confirmClass: ['bg-red', 'text-white', 'hover:bg-red/90'],
        },
    };

    const modal = document.createElement('div');
    modal.className = 'fixed inset-0 z-[200] hidden items-center justify-center bg-slate-950/45 px-4 py-6';
    modal.setAttribute('aria-hidden', 'true');
    modal.innerHTML = `
        <div class="w-full max-w-md rounded-2xl bg-white shadow-2xl" role="dialog" aria-modal="true" aria-labelledby="app-dialog-title" aria-describedby="app-dialog-message">
            <div class="p-6 text-center">
                <div data-app-dialog-icon class="mx-auto mb-4 flex h-12 w-12 items-center justify-center rounded-full">
                    <i data-app-dialog-icon-symbol class="text-2xl" aria-hidden="true"></i>
                </div>
                <h2 id="app-dialog-title" data-app-dialog-title class="text-lg font-semibold text-gray-900"></h2>
                <p id="app-dialog-message" data-app-dialog-message class="mt-2 whitespace-pre-line text-sm leading-6 text-gray-600"></p>
            </div>
            <div class="flex gap-3 px-6 pb-6">
                <button type="button" data-app-dialog-cancel class="hidden flex-1 rounded-lg border border-gray-300 px-4 py-2.5 text-sm font-semibold text-gray-700 transition hover:bg-gray-50">Batal</button>
                <button type="button" data-app-dialog-confirm class="flex-1 rounded-lg px-4 py-2.5 text-sm font-semibold transition">OK</button>
            </div>
        </div>
    `;
    document.body.appendChild(modal);

    const title = modal.querySelector('[data-app-dialog-title]');
    const message = modal.querySelector('[data-app-dialog-message]');
    const icon = modal.querySelector('[data-app-dialog-icon]');
    const iconSymbol = modal.querySelector('[data-app-dialog-icon-symbol]');
    const cancelButton = modal.querySelector('[data-app-dialog-cancel]');
    const confirmButton = modal.querySelector('[data-app-dialog-confirm]');

    const close = (result) => {
        if (!active) {
            return;
        }

        const { resolve } = active;
        active = null;
        modal.classList.add('hidden');
        modal.classList.remove('flex');
        modal.setAttribute('aria-hidden', 'true');
        if (!bodyWasLocked) {
            document.body.classList.remove('overflow-hidden');
        }
        resolve(result);
        window.setTimeout(showNext, 0);
    };

    const showNext = () => {
        if (active || queue.length === 0) {
            return;
        }

        active = queue.shift();
        const variant = variantStyles[active.options.variant] || variantStyles.info;
        title.textContent = active.options.title || (active.type === 'confirm' ? 'Konfirmasi' : 'Informasi');
        message.textContent = active.message;
        icon.className = `mx-auto mb-4 flex h-12 w-12 items-center justify-center rounded-full ${variant.iconClass.join(' ')}`;
        iconSymbol.className = `${variant.icon} text-2xl`;
        confirmButton.textContent = active.options.confirmText || (active.type === 'confirm' ? 'Ya, lanjutkan' : 'OK');
        confirmButton.className = `flex-1 rounded-lg px-4 py-2.5 text-sm font-semibold transition ${variant.confirmClass.join(' ')}`;
        cancelButton.textContent = active.options.cancelText || 'Batal';
        cancelButton.classList.toggle('hidden', active.type !== 'confirm');
        cancelButton.classList.toggle('flex-1', active.type === 'confirm');

        bodyWasLocked = document.body.classList.contains('overflow-hidden');
        document.body.classList.add('overflow-hidden');
        modal.classList.remove('hidden');
        modal.classList.add('flex');
        modal.setAttribute('aria-hidden', 'false');
        window.setTimeout(() => (active?.type === 'confirm' ? cancelButton : confirmButton).focus(), 0);
    };

    const open = (type, dialogMessage, options = {}) => new Promise((resolve) => {
        queue.push({ type, message: String(dialogMessage ?? ''), options, resolve });
        showNext();
    });

    confirmButton.addEventListener('click', () => close(true));
    cancelButton.addEventListener('click', () => close(false));
    modal.addEventListener('click', (event) => {
        if (event.target === modal) {
            close(false);
        }
    });
    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape' && active) {
            close(false);
        }
    });

    window.appDialog = {
        alert: (dialogMessage, options = {}) => open('alert', dialogMessage, options),
        confirm: (dialogMessage, options = {}) => open('confirm', dialogMessage, options),
    };
    window.appAlert = window.appDialog.alert;
    window.appConfirm = window.appDialog.confirm;

    window.appConfirmSubmit = (form, dialogMessage, options = {}) => {
        if (form.dataset.appDialogApproved === 'true') {
            delete form.dataset.appDialogApproved;
            return true;
        }

        window.appConfirm(dialogMessage, options).then((confirmed) => {
            if (!confirmed) {
                return;
            }

            form.dataset.appDialogApproved = 'true';
            form.requestSubmit();
        });

        return false;
    };

    // Legacy alert() calls now use the application dialog.
    window.alert = (dialogMessage) => {
        window.appAlert(dialogMessage);
    };

    // Most legacy confirmations are called from an inline form/button handler.
    // Native confirm() is synchronous, so cancel the first interaction, then
    // replay that exact interaction after the asynchronous dialog is approved.
    const nativeConfirm = window.confirm.bind(window);
    let legacyConfirmationContext = null;
    let bypassLegacyConfirmation = false;

    const rememberLegacyConfirmationContext = (context) => {
        legacyConfirmationContext = context;

        queueMicrotask(() => {
            if (legacyConfirmationContext === context) {
                legacyConfirmationContext = null;
            }
        });
    };

    document.addEventListener('click', (event) => {
        const target = event.target instanceof Element ? event.target : null;
        const trigger = target?.closest('button, a, input[type="submit"], input[type="button"]');
        if (trigger) {
            rememberLegacyConfirmationContext({ type: 'click', trigger });
        }
    }, true);

    document.addEventListener('submit', (event) => {
        rememberLegacyConfirmationContext({
            type: 'submit',
            form: event.target,
            submitter: event.submitter || null,
        });
    }, true);

    window.confirm = (dialogMessage) => {
        if (bypassLegacyConfirmation) {
            bypassLegacyConfirmation = false;
            return true;
        }

        const context = legacyConfirmationContext;
        if (!context) {
            return nativeConfirm(dialogMessage);
        }

        window.appConfirm(dialogMessage, {
            title: 'Konfirmasi',
            confirmText: 'Ya, lanjutkan',
            variant: 'warning',
        }).then((confirmed) => {
            if (!confirmed) {
                return;
            }

            bypassLegacyConfirmation = true;
            if (context.type === 'submit') {
                context.form.requestSubmit(context.submitter || undefined);
                return;
            }

            context.trigger.click();
        });

        return false;
    };
};

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initializeAppDialog, { once: true });
} else {
    initializeAppDialog();
}
