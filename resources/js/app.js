import './bootstrap';

// Override fetch global agar selalu kirim credentials (session cookie Sanctum)
const _fetch = window.fetch;
window.fetch = function (url, options = {}) {
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content ?? '';

    options.credentials = options.credentials ?? 'include';
    options.headers = {
        'Accept': 'application/json',
        'X-CSRF-TOKEN': csrfToken,
        'X-Requested-With': 'XMLHttpRequest',
        ...(options.headers ?? {}),
    };

    console.log('[fetch]', url, 'credentials:', options.credentials, 'csrf:', csrfToken ? 'ada' : 'TIDAK ADA');
    return _fetch(url, options);
};
