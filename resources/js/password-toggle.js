document.addEventListener('click', (event) => {
    const button = event.target.closest('[data-ag-password-toggle]');

    if (! button) {
        return;
    }

    const control = button.closest('.ag-password-field__control');

    if (! control) {
        return;
    }

    const input = control.querySelector('[data-ag-password-input]');

    if (! input) {
        return;
    }

    const show = input.type === 'password';
    input.type = show ? 'text' : 'password';

    const showLabel = button.dataset.labelShow ?? 'Show password';
    const hideLabel = button.dataset.labelHide ?? 'Hide password';
    button.setAttribute('aria-label', show ? hideLabel : showLabel);
    button.setAttribute('aria-pressed', show ? 'true' : 'false');

    const iconShow = button.querySelector('[data-ag-password-icon-show]');
    const iconHide = button.querySelector('[data-ag-password-icon-hide]');

    if (iconShow) {
        iconShow.hidden = show;
    }

    if (iconHide) {
        iconHide.hidden = ! show;
    }
});
