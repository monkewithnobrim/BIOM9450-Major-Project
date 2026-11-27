async function requestPage(url, options = {}) {
    const response = await fetch(url, {
        ...options,
        credentials: 'same-origin',
        cache: 'no-store'
    });

    if (new URL(response.url).pathname.endsWith('/login.php')) {
        window.location.assign(response.url);
        throw new Error('Session expired.');
    }

    if (!response.ok) {
        throw new Error('Request failed (' + response.status + ').');
    }

    return new DOMParser().parseFromString(await response.text(), 'text/html');
}

let saving = false;

document.addEventListener('submit', async event => {
    const form = event.target;

    if (!form.matches('.dispense-form')) {
        return;
    }

    const card = form.closest('[data-admin-id]');
    const residents = document.querySelector('.round-layout > section');
    const feedback = document.getElementById('round-feedback');
    const status = form.elements.status.value;

    if (!card || !residents || !feedback) {
        if (!window.confirm('Record this medication as ' + status + '?')) {
            event.preventDefault();
        }
        return;
    }

    event.preventDefault();

    if (saving || !window.confirm(
        'Record this medication as ' + status + '? This cannot be changed on this page.'
    )) {
        return;
    }

    const data = new FormData(form);
    const buttons = [...document.querySelectorAll('.dispense-form button[type="submit"]')];
    const button = form.querySelector('button[type="submit"]');
    saving = true;
    buttons.forEach(b => b.disabled = true);

    if (button) {
        button.textContent = 'Saving...';
    }

    feedback.textContent = 'Saving medication outcome...';

    try {
        const page = await requestPage(form.action, {
            method: 'POST',
            body: data
        });
        const updatedCard = [...page.querySelectorAll('[data-admin-id]')]
            .find(item => item.dataset.adminId === card.dataset.adminId);
        const updatedResidents = page.querySelector('.round-layout > section');
        const updatedFeedback = page.getElementById('round-feedback');

        if (!updatedCard || !updatedResidents || !updatedFeedback || !updatedFeedback.textContent.trim()) {
            throw new Error('Could not verify the saved outcome.');
        }

        if (!updatedCard.querySelector('.dispense-form')) {
            card.replaceWith(updatedCard);
        }

        residents.replaceWith(updatedResidents);
        feedback.replaceChildren(...updatedFeedback.childNodes);
    } catch (error) {
        feedback.textContent = 'Could not confirm the outcome. Reload the round and check the record before retrying.';
    } finally {
        saving = false;
        buttons.forEach(b => b.disabled = false);

        if (button) {
            button.textContent = 'Save outcome';
        }
    }
});