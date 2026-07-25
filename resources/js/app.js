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

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initializeAdminSelects, { once: true });
} else {
    initializeAdminSelects();
}
