/**
 * Transaction form: lending requires contact; category hidden for lending.
 */
export function initTransactionForm() {
    const typeSelect = document.querySelector('select[name="type"]');
    const contactWrap = document.getElementById('contact-field-wrap');
    const contactSelect = document.querySelector('select[name="contact_id"]');
    const categoryWrap = document.getElementById('category-field-wrap');

    if (!typeSelect) {
        return;
    }

    const sync = () => {
        const isLending = typeSelect.value === 'lending';

        if (contactWrap) {
            contactWrap.classList.toggle('ring-2', isLending);
            contactWrap.classList.toggle('ring-amber-300', isLending);
            contactWrap.classList.toggle('rounded-xl', isLending);
        }

        if (contactSelect) {
            contactSelect.required = isLending;
        }

        if (categoryWrap) {
            categoryWrap.classList.toggle('hidden', isLending);
        }
    };

    typeSelect.addEventListener('change', sync);
    sync();
}

document.addEventListener('DOMContentLoaded', initTransactionForm);
