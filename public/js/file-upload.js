(function () {
    'use strict';

    function formatSize(bytes) {
        if (bytes < 1024) return bytes + ' B';
        if (bytes < 1024 * 1024) return (bytes / 1024).toLocaleString('id-ID', { maximumFractionDigits: 1 }) + ' KB';
        return (bytes / (1024 * 1024)).toLocaleString('id-ID', { maximumFractionDigits: 1 }) + ' MB';
    }

    function enhance(input) {
        if (input.dataset.fileUploadReady || input.closest('.vp-file-picker')) return;
        input.dataset.fileUploadReady = 'true';

        var label = input.id ? Array.from(document.querySelectorAll('label[for]')).find(function (item) {
            return item.htmlFor === input.id;
        }) : null;
        var fieldName = label ? label.textContent.trim() : 'file';
        var wrapper = document.createElement('div');
        wrapper.className = 'nc-file-upload';
        var row = document.createElement('div');
        row.className = 'nc-file-upload-row';
        var choose = document.createElement('button');
        choose.type = 'button';
        choose.className = 'nc-file-upload-choose';
        choose.setAttribute('aria-label', 'Pilih file untuk ' + fieldName);
        if (input.id) choose.setAttribute('aria-controls', input.id);
        if (input.getAttribute('aria-describedby')) choose.setAttribute('aria-describedby', input.getAttribute('aria-describedby'));
        choose.innerHTML = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 16V4m0 0-4 4m4-4 4 4"/><path d="M4 16v3a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2v-3"/></svg><span>Pilih File</span>';
        var name = document.createElement('span');
        name.className = 'nc-file-upload-name';
        name.setAttribute('role', 'status');
        name.setAttribute('aria-live', 'polite');
        var size = document.createElement('span');
        size.className = 'nc-file-upload-size';
        size.setAttribute('aria-hidden', 'true');
        size.hidden = true;
        var remove = document.createElement('button');
        remove.type = 'button';
        remove.className = 'nc-file-upload-remove';
        remove.textContent = 'Hapus pilihan';
        remove.setAttribute('aria-label', 'Hapus file baru untuk ' + fieldName);
        remove.hidden = true;
        var preview = document.createElement('img');
        preview.className = 'nc-file-upload-preview';
        preview.alt = 'Pratinjau file baru untuk ' + fieldName;
        preview.hidden = true;

        input.insertAdjacentElement('beforebegin', wrapper);
        wrapper.appendChild(input);
        row.append(choose, name, size, remove);
        wrapper.append(row, preview);
        input.classList.add('nc-file-upload-native');
        input.tabIndex = -1;

        var previewUrl = null;
        function update() {
            if (previewUrl) URL.revokeObjectURL(previewUrl);
            previewUrl = null;
            var files = Array.from(input.files || []);
            var selected = files.length > 0;
            wrapper.classList.toggle('is-selected', selected);
            wrapper.classList.toggle('is-invalid', input.classList.contains('is-invalid') || input.getAttribute('aria-invalid') === 'true');
            wrapper.classList.toggle('is-disabled', input.disabled);
            choose.disabled = input.disabled;
            remove.disabled = input.disabled;
            remove.hidden = !selected;
            size.hidden = !selected;
            size.textContent = selected ? files.map(function (file) { return formatSize(file.size); }).join(', ') : '';
            name.textContent = selected ? files.map(function (file) { return file.name; }).join(', ') : 'Belum ada file dipilih';
            name.title = selected ? name.textContent : '';
            if (selected) {
                name.setAttribute('aria-label', name.textContent + ', ' + files.map(function (file) { return formatSize(file.size); }).join(', '));
            } else {
                name.removeAttribute('aria-label');
            }
            var image = files[0] && files[0].type.startsWith('image/');
            preview.hidden = !image;
            if (image) {
                previewUrl = URL.createObjectURL(files[0]);
                preview.src = previewUrl;
            } else {
                preview.removeAttribute('src');
            }
        }

        choose.addEventListener('click', function () { input.click(); });
        remove.addEventListener('click', function () {
            input.value = '';
            input.dispatchEvent(new Event('change', { bubbles: true }));
            choose.focus();
        });
        input.addEventListener('change', update);
        input.form && input.form.addEventListener('reset', function () { requestAnimationFrame(update); });
        update();
        wrapper.ncUpdateFile = update;
    }

    function scan(root) {
        if (root.matches && root.matches('input[type="file"]')) enhance(root);
        if (root.querySelectorAll) root.querySelectorAll('input[type="file"]').forEach(enhance);
    }

    function start() {
        scan(document);
        new MutationObserver(function (records) {
            records.forEach(function (record) {
                if (record.type === 'childList') {
                    record.addedNodes.forEach(function (node) { if (node.nodeType === 1) scan(node); });
                } else if (record.target.matches('input[type="file"]')) {
                    record.target.closest('.nc-file-upload')?.ncUpdateFile();
                } else if (record.target.matches('.nc-lexical-image-panel')) {
                    record.target.querySelector('.nc-file-upload')?.ncUpdateFile();
                }
            });
        }).observe(document.body, { childList: true, subtree: true, attributes: true, attributeFilter: ['disabled', 'hidden'] });
    }

    if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', start);
    else start();
})();
