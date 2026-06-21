/**
 * Lightweight searchable select — enhances native <select> for forms.
 * Usage: <select class="input" data-search-select> or data-search-select="off" to skip.
 */

function shouldEnhance(select) {
    if (select.dataset.searchEnhanced === 'true') {
        return false;
    }

    if (select.dataset.searchSelect === 'off') {
        return false;
    }

    if (select.hasAttribute('data-search-select')) {
        return true;
    }

    const optionCount = Array.from(select.options).filter((option) => option.value !== '').length;

    return optionCount >= 4;
}

function enhance(select) {
    select.dataset.searchEnhanced = 'true';

    const placeholder = select.dataset.placeholder || 'Select an option';
    const searchPlaceholder = select.dataset.searchPlaceholder || 'Type to search…';

    const wrapper = document.createElement('div');
    wrapper.className = 'search-select';

    const trigger = document.createElement('button');
    trigger.type = 'button';
    trigger.className = 'search-select-trigger';
    trigger.setAttribute('aria-haspopup', 'listbox');
    trigger.setAttribute('aria-expanded', 'false');

    const panel = document.createElement('div');
    panel.className = 'search-select-panel hidden';
    panel.setAttribute('role', 'listbox');

    const search = document.createElement('input');
    search.type = 'search';
    search.className = 'search-select-search';
    search.placeholder = searchPlaceholder;
    search.autocomplete = 'off';

    const list = document.createElement('ul');
    list.className = 'search-select-list';

    const empty = document.createElement('li');
    empty.className = 'search-select-empty hidden';
    empty.textContent = 'No matches found';

    const open = () => {
        panel.classList.remove('hidden');
        trigger.setAttribute('aria-expanded', 'true');
        search.value = '';
        renderList('');
        search.focus();
    };

    const close = () => {
        panel.classList.add('hidden');
        trigger.setAttribute('aria-expanded', 'false');
    };

    const updateTrigger = () => {
        const selected = select.selectedOptions[0];

        if (selected && selected.value !== '') {
            trigger.textContent = selected.text;
            trigger.classList.remove('is-placeholder');
        } else {
            trigger.textContent = placeholder;
            trigger.classList.add('is-placeholder');
        }
    };

    const selectValue = (value) => {
        select.value = value;
        select.dispatchEvent(new Event('change', { bubbles: true }));
        updateTrigger();
        close();
    };

    const renderList = (query) => {
        const normalized = query.trim().toLowerCase();
        list.innerHTML = '';
        let visible = 0;

        Array.from(select.options).forEach((option) => {
            if (option.value === '') {
                return;
            }

            if (normalized && !option.text.toLowerCase().includes(normalized)) {
                return;
            }

            const item = document.createElement('li');
            item.className = 'search-select-option';
            item.textContent = option.text;
            item.dataset.value = option.value;

            if (option.value === select.value) {
                item.classList.add('is-selected');
            }

            item.addEventListener('click', () => selectValue(option.value));
            list.appendChild(item);
            visible++;
        });

        if (visible === 0) {
            empty.classList.remove('hidden');
            list.appendChild(empty);
        } else {
            empty.classList.add('hidden');
        }
    };

    trigger.addEventListener('click', (event) => {
        event.preventDefault();
        event.stopPropagation();

        if (panel.classList.contains('hidden')) {
            open();
        } else {
            close();
        }
    });

    search.addEventListener('input', () => renderList(search.value));

    search.addEventListener('keydown', (event) => {
        if (event.key === 'Escape') {
            close();
            trigger.focus();
        }

        if (event.key === 'Enter') {
            event.preventDefault();
            const first = list.querySelector('.search-select-option:not(.search-select-empty)');

            if (first) {
                selectValue(first.dataset.value);
            }
        }
    });

    document.addEventListener('click', (event) => {
        if (!wrapper.contains(event.target)) {
            close();
        }
    });

    select.classList.add('search-select-native');
    select.tabIndex = -1;

    select.parentNode.insertBefore(wrapper, select);
    wrapper.appendChild(select);
    wrapper.appendChild(trigger);
    wrapper.appendChild(panel);
    panel.appendChild(search);
    panel.appendChild(list);

    select.addEventListener('change', updateTrigger);
    updateTrigger();
}

export function initSearchSelects(root = document) {
    root.querySelectorAll('select').forEach((select) => {
        if (shouldEnhance(select)) {
            enhance(select);
        }
    });
}

document.addEventListener('DOMContentLoaded', () => initSearchSelects());
