import hash from 'bcryptjs';

const msgContainer = document.querySelector('.message-container');
const passwordEntry = document.getElementById('password');
const submit = document.querySelector('input[type=submit]');

document.querySelector('form').addEventListener('submit', e => {
    e.preventDefault();

    const originalButtonCaption = submit.value;

    submit.disabled = true;
    submit.value = 'Importing…';

    hash(passwordEntry.value.trim(), 10).then(hashedPassword => {
        msgContainer.innerHTML = `<p class="error"><label>Generated Password (insert this into config.ini): <input type="text" value="${hashedPassword}"></label></p>`;
    }).catch(err => {
        msgContainer.innerHTML = `<p class="error">Unexpected happened. <details><pre>${JSON.stringify(err)}</pre></details></p>`;
    }).finally(() => {
        submit.disabled = false;
        submit.value = originalButtonCaption;
    });
});
