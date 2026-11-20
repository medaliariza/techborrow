(function () {
    const databaseName = 'techborrow-form-drafts';
    const databaseVersion = 1;
    let databasePromise;

    function openDatabase() {
        if (!('indexedDB' in window)) {
            return Promise.reject(new Error('This browser does not support saving attached files locally.'));
        }
        if (!databasePromise) {
            databasePromise = new Promise(function (resolve, reject) {
                const request = indexedDB.open(databaseName, databaseVersion);
                request.onupgradeneeded = function () {
                    const database = request.result;
                    if (!database.objectStoreNames.contains('drafts')) {
                        database.createObjectStore('drafts', { keyPath: 'key' });
                    }
                };
                request.onsuccess = function () {
                    resolve(request.result);
                };
                request.onerror = function () {
                    reject(request.error || new Error('Could not open local draft storage.'));
                };
            });
        }
        return databasePromise;
    }

    function runDatabaseRequest(mode, key, value) {
        return openDatabase().then(function (database) {
            return new Promise(function (resolve, reject) {
                const transaction = database.transaction('drafts', mode);
                const store = transaction.objectStore('drafts');
                const request = mode === 'readwrite'
                    ? (value ? store.put(value) : store.delete(key))
                    : store.get(key);
                let result;
                request.onsuccess = function () {
                    result = request.result;
                };
                request.onerror = function () {
                    reject(request.error || new Error('Could not access locally saved attachments.'));
                };
                transaction.oncomplete = function () {
                    resolve(result);
                };
                transaction.onerror = function () {
                    reject(transaction.error || new Error('Local attachment storage failed.'));
                };
                transaction.onabort = function () {
                    reject(transaction.error || new Error('Local attachment storage was interrupted.'));
                };
            });
        });
    }

    function storageKey(key) {
        return 'techborrow-form-draft:' + key;
    }

    function setStatus(form, message, isError) {
        const status = form.querySelector('[data-form-draft-status]');
        if (status) {
            status.textContent = message;
            status.classList.toggle('text-danger', Boolean(isError));
            status.classList.toggle('text-muted', !isError);
        }
    }

    function formFields(form) {
        const fields = {};
        form.querySelectorAll('[name]:not([type="file"]):not([type="submit"]):not([type="button"])').forEach(function (field) {
            const name = field.name;
            if (!fields[name]) {
                fields[name] = [];
            }
            fields[name].push({
                value: field.value,
                checked: 'checked' in field ? field.checked : null
            });
        });
        return fields;
    }

    function formFiles(form) {
        const files = {};
        form.querySelectorAll('input[type="file"][name]').forEach(function (input) {
            const file = input.files && input.files[0];
            if (file) {
                files[input.name] = {
                    blob: file,
                    name: file.name,
                    type: file.type,
                    lastModified: file.lastModified
                };
            }
        });
        return files;
    }

    async function saveDraft(form) {
        const key = form.dataset.formDraft;
        try {
            localStorage.setItem(storageKey(key), JSON.stringify(formFields(form)));
            await runDatabaseRequest('readwrite', key, {
                key: key,
                files: formFiles(form),
                savedAt: Date.now()
            });
            delete form.dataset.draftStorageError;
            setStatus(form, navigator.onLine
                ? 'Draft saved on this device. It will remain available if submission fails.'
                : 'You are offline. Your draft is saved on this device and will remain available when you reconnect.');
            return true;
        } catch (error) {
            console.error('TechBorrow could not save this form draft.', error);
            form.dataset.draftStorageError = 'true';
            setStatus(form, 'This browser could not save your draft. Keep this page open and do not reload until your request is submitted.', true);
            return false;
        }
    }

    function restoreRows(form, fields) {
        const container = form.querySelector('#request-items-container');
        const rows = container && container.querySelectorAll('[data-request-row]');
        if (!container || !rows || !rows.length) {
            return;
        }
        const equipmentCount = (fields['equipment_id[]'] || []).length;
        const quantityCount = (fields['quantity[]'] || []).length;
        const neededRows = Math.max(equipmentCount, quantityCount, 1);
        while (container.querySelectorAll('[data-request-row]').length < neededRows) {
            const row = container.querySelector('[data-request-row]').cloneNode(true);
            row.querySelectorAll('select').forEach(function (select) {
                select.selectedIndex = 0;
            });
            row.querySelectorAll('input').forEach(function (input) {
                input.value = '';
            });
            row.querySelectorAll('button').forEach(function (button) {
                button.disabled = false;
            });
            container.appendChild(row);
        }
    }

    function restoreFields(form, fields) {
        restoreRows(form, fields);
        Object.keys(fields).forEach(function (name) {
            const controls = Array.from(form.querySelectorAll('[name]')).filter(function (field) {
                return field.name === name && field.type !== 'file';
            });
            (fields[name] || []).forEach(function (saved, index) {
                const field = controls[index];
                if (!field) {
                    return;
                }
                if (field.type === 'checkbox' || field.type === 'radio') {
                    field.checked = Boolean(saved.checked);
                } else {
                    field.value = saved.value;
                }
            });
        });
    }

    async function restoreFiles(form, savedFiles) {
        const inputs = Array.from(form.querySelectorAll('input[type="file"][name]'));
        for (const input of inputs) {
            const saved = savedFiles[input.name];
            if (!saved || !saved.blob) {
                continue;
            }
            try {
                const transfer = new DataTransfer();
                transfer.items.add(new File([saved.blob], saved.name, {
                    type: saved.type || saved.blob.type,
                    lastModified: saved.lastModified || Date.now()
                }));
                input.files = transfer.files;
            } catch (error) {
                console.error('TechBorrow could not restore a saved attachment.', error);
                setStatus(form, 'Your text was restored, but the saved attachment could not be restored. Please attach it again.', true);
            }
        }
    }

    async function clearDraft(key) {
        localStorage.removeItem(storageKey(key));
        await runDatabaseRequest('readwrite', key, null);
    }

    function initializeForm(form) {
        const key = form.dataset.formDraft;
        if (form.hasAttribute('data-clear-form-draft')) {
            clearDraft(key).catch(function (error) {
                console.error('TechBorrow could not clear the completed form draft.', error);
            });
            return;
        }

        const submitButtons = Array.from(form.querySelectorAll('button[type="submit"], input[type="submit"]'));
        const fileInputs = Array.from(form.querySelectorAll('input[type="file"]'));
        submitButtons.forEach(function (button) {
            button.disabled = true;
        });
        fileInputs.forEach(function (input) {
            input.disabled = true;
        });

        let fields = {};
        let savedFields = null;
        try {
            savedFields = localStorage.getItem(storageKey(key));
            fields = savedFields ? JSON.parse(savedFields) : {};
            if (!fields || typeof fields !== 'object' || Array.isArray(fields)) {
                fields = {};
            }
            restoreFields(form, fields);
        } catch (error) {
            console.error('TechBorrow could not restore saved form fields.', error);
            setStatus(form, 'The saved text could not be restored. Keep this page open and check your entries before submitting.', true);
        }

        let restorationSucceeded = false;
        const restoration = runDatabaseRequest('readonly', key).then(async function (savedDraft) {
            if (savedDraft && savedDraft.files) {
                await restoreFiles(form, savedDraft.files);
            }
            restorationSucceeded = true;
            if (savedFields || (savedDraft && savedDraft.savedAt)) {
                setStatus(form, 'Your saved draft has been restored on this device. It will remain until submission succeeds.');
            }
        }).catch(function (error) {
            console.error('TechBorrow could not restore saved attachments.', error);
            form.dataset.draftStorageError = 'true';
            setStatus(form, 'Local draft storage is unavailable. Keep this page open and do not reload until your request is submitted.', true);
        }).finally(function () {
            submitButtons.forEach(function (button) {
                button.disabled = false;
            });
            fileInputs.forEach(function (input) {
                input.disabled = false;
            });
        });

        let saveTimer;
        function queueSave() {
            window.clearTimeout(saveTimer);
            saveTimer = window.setTimeout(function () {
                saveDraft(form);
            }, 250);
        }
        form.addEventListener('input', queueSave);
        form.addEventListener('change', queueSave);
        form.addEventListener('submit', function (event) {
            if (form.dataset.draftResubmitting === 'true') {
                form.dataset.draftResubmitting = 'false';
                return;
            }
            event.preventDefault();
            const submitter = event.submitter;
            window.clearTimeout(saveTimer);
            restoration.then(function () {
                if (!restorationSucceeded) {
                    return;
                }
                return saveDraft(form).then(function (saved) {
                    if (!saved) {
                        return;
                    }
                    form.dataset.draftResubmitting = 'true';
                    if (typeof form.requestSubmit === 'function') {
                        form.requestSubmit(submitter || undefined);
                    } else {
                        form.submit();
                    }
                });
            });
        });

        if (!navigator.onLine) {
            setStatus(form, 'You are offline. Your saved draft remains on this device; reconnect before submitting.');
        }
    }

    document.addEventListener('DOMContentLoaded', function () {
        document.querySelectorAll('[data-clear-form-draft]').forEach(function (marker) {
            const key = marker.dataset.clearFormDraft;
            if (key) {
                clearDraft(key).catch(function (error) {
                    console.error('TechBorrow could not clear the completed form draft.', error);
                });
            }
        });

        document.querySelectorAll('form[data-form-draft]').forEach(function (form) {
            initializeForm(form);
        });

        window.addEventListener('offline', function () {
            document.querySelectorAll('form[data-form-draft]').forEach(function (form) {
                if (form.dataset.draftStorageError !== 'true') {
                    setStatus(form, 'You are offline. Your draft is saved on this device and will remain available when you reconnect.');
                }
            });
        });
        window.addEventListener('online', function () {
            document.querySelectorAll('form[data-form-draft]').forEach(function (form) {
                if (form.dataset.draftStorageError !== 'true') {
                    setStatus(form, 'Connection restored. Your draft remains saved on this device; review it before submitting.');
                }
            });
        });
    });
})();
