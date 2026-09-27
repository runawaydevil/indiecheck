const buttons = document.querySelectorAll('form button[type="submit"]');

buttons.forEach((button) => {
    button.dataset.label = button.textContent;
    button.form.addEventListener('submit', (event) => {
        if (button.getAttribute('aria-busy') === 'true') {
            event.preventDefault();
            return;
        }
        button.setAttribute('aria-busy', 'true');
        button.textContent = 'Checking…';
    });
});

window.addEventListener('pageshow', () => {
    buttons.forEach((button) => {
        button.removeAttribute('aria-busy');
        button.textContent = button.dataset.label;
    });
});
