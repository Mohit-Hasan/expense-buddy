export function initTransactionForm() {
    const typeSelect = document.querySelector('select[name="type"]');
    const contactWrap = document.getElementById('contact-field-wrap');
    const contactSelect = document.querySelector('select[name="contact_id"]');
    const contactHint = document.getElementById('contact-hint');
    const categoryWrap = document.getElementById('category-field-wrap');
    const paymentWrap = document.getElementById('payment-field-wrap');
    const paymentSelect = document.querySelector('select[name="payment_method_id"]');

    if (!typeSelect) {
        return;
    }

    const isLendingType = (value) => value.startsWith('lending');

    const sync = () => {
        const isLending = isLendingType(typeSelect.value);

        if (contactWrap) {
            contactWrap.classList.remove('hidden');
        }

        if (contactHint) {
            contactHint.textContent = isLending ? '(required)' : '(optional — for tracking)';
        }

        if (contactSelect) {
            contactSelect.required = isLending;
        }

        if (categoryWrap) {
            categoryWrap.classList.toggle('hidden', isLending);
        }

        if (paymentWrap) {
            paymentWrap.classList.toggle('hidden', isLending);
        }

        if (paymentSelect) {
            paymentSelect.required = !isLending;
            paymentSelect.disabled = isLending;
        }
    };

    typeSelect.addEventListener('change', sync);
    sync();
}

document.addEventListener('DOMContentLoaded', initTransactionForm);
