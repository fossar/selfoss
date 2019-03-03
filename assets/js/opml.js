const form = document.querySelector('form');
const msgContainer = document.querySelector('.message-container');
const submit = document.querySelector('input[type=submit]');

form.addEventListener('submit', e => {
    e.preventDefault();

    const originalButtonCaption = submit.value;

    submit.disabled = true;
    submit.value = 'Importing…';
    msgContainer.innerHTML = '';

    const request = new Request(form.action, {
        method: 'POST',
        body: new FormData(form)
    });

    fetch(request).then(response => {
        return response.json().then(({messages}) => {
            if (response.status === 200) {
                msgContainer.innerHTML = `<p class="msg success">${messages.join('<br>')} You might want to <a href="update">update now</a> or <a href="./">view your feeds</a>.</p>`;
            } else if (response.status === 202) {
                msgContainer.innerHTML = `<p class="msg error">The following feeds could not be imported:<br>${messages.join('<br>')}</p>`;
            } else if (response.status === 400) {
                msgContainer.innerHTML = `<p class="msg error">There was a problem importing your OPML file:<br>${messages.join('<br>')}</p>`;
            } else {
                msgContainer.innerHTML = `<p class="msg error">Unexpected happened. <details><pre>${JSON.stringify(messages)}</pre></details></p>`;
            }
        });
    }).catch(err => {
        msgContainer.innerHTML = `<p class="msg error">Unexpected happened. <details><pre>${JSON.stringify(err)}</pre></details></p>`;
    }).finally(() => {
        submit.disabled = false;
        submit.value = originalButtonCaption;
    });
});
