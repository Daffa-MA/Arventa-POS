import * as Turbo from '@hotwired/turbo';
import Alpine from 'alpinejs';

Turbo.session.drive = true;

window.Alpine = Alpine;
Alpine.start();

const root = document.documentElement;
let loadingTimer;

const startLoading = () => {
    window.clearTimeout(loadingTimer);
    root.classList.add('turbo-loading');
};

const stopLoading = () => {
    loadingTimer = window.setTimeout(() => {
        root.classList.remove('turbo-loading');
    }, 180);
};

document.addEventListener('turbo:before-fetch-request', startLoading);
document.addEventListener('turbo:submit-start', startLoading);
document.addEventListener('turbo:before-render', () => {
    root.classList.add('turbo-rendering');
});
document.addEventListener('turbo:render', () => {
    root.classList.remove('turbo-rendering');
});
document.addEventListener('turbo:load', stopLoading);
document.addEventListener('turbo:submit-end', stopLoading);
document.addEventListener('turbo:fetch-request-error', stopLoading);
