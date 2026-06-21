export function initFormModal() {
    const modal = document.getElementById('form-modal');

    if (!modal) {
        return;
    }

    const titleEl = document.getElementById('form-modal-title');
    const form = document.getElementById('form-modal-form');

    if (!titleEl || !form) {
        return;
    }

    const open = () => modal.classList.remove('hidden');
    const close = () => modal.classList.add('hidden');

    const applyConfig = (config) => {
        titleEl.textContent = config.title || 'Form';

        if (config.action) {
            form.action = config.action;
        }

        form.querySelectorAll('[data-modal-field]').forEach((field) => {
            const key = field.dataset.modalField;
            const value = config.fields?.[key];

            if (field.type === 'checkbox') {
                if (Array.isArray(value)) {
                    field.checked = value.map(String).includes(String(field.value));
                } else if (value === undefined) {
                    field.checked = false;
                } else {
                    field.checked = value === true || value === '1' || value === 1;
                }
            } else if (value === undefined) {
                if (field.tagName === 'SELECT') {
                    field.selectedIndex = 0;
                } else {
                    field.value = '';
                }
            } else {
                field.value = value;
            }

            if (config.readonlyFields?.includes(key)) {
                if (field.tagName === 'SELECT' || field.type === 'checkbox') {
                    field.setAttribute('disabled', 'disabled');
                } else {
                    field.setAttribute('readonly', 'readonly');
                }
            } else {
                field.removeAttribute('readonly');
                field.removeAttribute('disabled');
            }
        });

        form.querySelectorAll('[data-modal-required]').forEach((field) => {
            const key = field.dataset.modalRequired;

            if (config.requiredFields?.includes(key)) {
                field.setAttribute('required', 'required');
            } else {
                field.removeAttribute('required');
            }
        });

        const methodField = form.querySelector('[name="_method"]');

        if (config.method === 'PUT') {
            if (methodField) {
                methodField.value = 'PUT';
            } else {
                const input = document.createElement('input');
                input.type = 'hidden';
                input.name = '_method';
                input.value = 'PUT';
                form.prepend(input);
            }
        } else {
            methodField?.remove();
        }
    };

    document.addEventListener('click', (event) => {
        const trigger = event.target.closest('[data-form-modal-open]');

        if (!trigger) {
            return;
        }

        event.preventDefault();

        const payload = trigger.getAttribute('data-form-modal-open');

        if (payload) {
            try {
                applyConfig(JSON.parse(payload));
            } catch (error) {
                console.error('Unable to open form modal:', error);

                return;
            }
        }

        open();
    });

    modal.querySelectorAll('[data-form-modal-dismiss]').forEach((element) => {
        element.addEventListener('click', close);
    });

    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape' && !modal.classList.contains('hidden')) {
            close();
        }
    });
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initFormModal);
} else {
    initFormModal();
}
