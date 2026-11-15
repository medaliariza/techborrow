document.addEventListener('DOMContentLoaded', function () {
    const requestRows = document.querySelectorAll('[data-request-row]');
    requestRows.forEach(function (row) {
        const select = row.querySelector('select');
        const quantity = row.querySelector('input[type="number"]');
        if (select && quantity) {
            select.addEventListener('change', function () {
                if (this.value) {
                    quantity.required = true;
                    quantity.min = 1;
                }
            });
        }
    });

    const addRowButton = document.querySelector('#add-request-row');
    if (addRowButton) {
        addRowButton.addEventListener('click', function () {
            const row = document.querySelector('[data-request-row]').cloneNode(true);
            const selects = row.querySelectorAll('select');
            const inputs = row.querySelectorAll('input');
            selects.forEach(function (select) {
                select.value = '';
            });
            inputs.forEach(function (input) {
                input.value = '';
            });
            document.querySelector('#request-items-container').appendChild(row);
        });
    }
});
