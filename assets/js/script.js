document.addEventListener('DOMContentLoaded', function () {
    document.addEventListener('click', function (event) {
        const addButton = event.target.closest('#add-request-row');
        if (addButton) {
            const container = document.querySelector('#request-items-container');
            const firstRow = container && container.querySelector('[data-request-row]');
            if (!container || !firstRow) {
                return;
            }

            const row = firstRow.cloneNode(true);
            row.querySelectorAll('select').forEach(function (select) {
                select.selectedIndex = 0;
            });
            row.querySelectorAll('input').forEach(function (input) {
                if (input.type === 'checkbox' || input.type === 'radio') {
                    input.checked = false;
                } else {
                    input.value = '';
                }
            });
            row.querySelectorAll('button').forEach(function (button) {
                button.disabled = false;
            });
            container.appendChild(row);
            const firstControl = row.querySelector('select, input');
            if (firstControl) {
                firstControl.focus();
            }
            return;
        }

        const removeButton = event.target.closest('[data-request-row] button');
        if (removeButton) {
            const container = removeButton.closest('#request-items-container');
            const row = removeButton.closest('[data-request-row]');
            if (!container || !row) {
                return;
            }
            if (container.querySelectorAll('[data-request-row]').length > 1) {
                row.remove();
            } else {
                row.querySelectorAll('select').forEach(function (select) {
                    select.selectedIndex = 0;
                });
                row.querySelectorAll('input').forEach(function (input) {
                    input.value = '';
                });
            }
            container.dispatchEvent(new Event('input', { bubbles: true }));
            return;
        }
    });
});
