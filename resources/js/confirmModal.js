let pendingForm = null;

const modal = () => document.getElementById('confirm-modal');
const messageEl = () => document.getElementById('confirm-modal-message');
const submitBtn = () => document.getElementById('confirm-modal-submit');

function openConfirm(message, form) {
    pendingForm = form;
    messageEl().textContent = message;
    modal().classList.remove('hidden');
}

function closeConfirm() {
    pendingForm = null;
    modal().classList.add('hidden');
}

export function initConfirmModal() {
    document.querySelectorAll('[data-confirm]').forEach((element) => {
        element.addEventListener('click', (event) => {
            event.preventDefault();

            const message = element.dataset.confirm || 'Are you sure?';
            const formId = element.getAttribute('form');
            const form = formId ? document.getElementById(formId) : element.closest('form');

            if (!form) {
                return;
            }

            openConfirm(message, form);
        });
    });

    submitBtn()?.addEventListener('click', () => {
        if (pendingForm) {
            pendingForm.submit();
        }

        closeConfirm();
    });

    modal()?.querySelectorAll('[data-confirm-dismiss]').forEach((element) => {
        element.addEventListener('click', closeConfirm);
    });

    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape' && !modal()?.classList.contains('hidden')) {
            closeConfirm();
        }
    });
}

document.addEventListener('DOMContentLoaded', initConfirmModal);
