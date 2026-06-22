export function initCopyButtons() {
    document.addEventListener('click', async (event) => {
        const button = event.target.closest('[data-copy]');

        if (!button || button.disabled) {
            return;
        }

        const text = button.getAttribute('data-copy');

        if (!text) {
            return;
        }

        const copied = await copyText(text);

        if (!copied) {
            return;
        }

        const icon = button.querySelector('[data-copy-icon]');
        const feedback = button.querySelector('[data-copy-feedback]');

        button.disabled = true;
        icon?.classList.add('hidden');
        feedback?.classList.remove('hidden');

        window.setTimeout(() => {
            button.disabled = false;
            icon?.classList.remove('hidden');
            feedback?.classList.add('hidden');
        }, 2000);
    });
}

async function copyText(text) {
    try {
        await navigator.clipboard.writeText(text);

        return true;
    } catch {
        const textarea = document.createElement('textarea');
        textarea.value = text;
        textarea.setAttribute('readonly', '');
        textarea.style.position = 'fixed';
        textarea.style.left = '-9999px';
        document.body.appendChild(textarea);
        textarea.select();

        try {
            document.execCommand('copy');

            return true;
        } catch {
            return false;
        } finally {
            document.body.removeChild(textarea);
        }
    }
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initCopyButtons);
} else {
    initCopyButtons();
}
