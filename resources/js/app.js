import './bootstrap';
import regions from '../data/indonesia-regions.json';

const populateCities = (province, city, selected = '') => {
    city.replaceChildren(new Option('Pilih kota / kabupaten', ''));
    (regions[province.value] || []).forEach(name => city.add(new Option(name, name)));
    if ((regions[province.value] || []).includes(selected)) city.value = selected;
};

document.querySelectorAll('[data-profile-region]').forEach(section => {
    const province = section.querySelector('[data-region-province]');
    const city = section.querySelector('[data-region-city]');
    province.addEventListener('change', () => {
        populateCities(province, city);
        city.disabled = !province.value;
    });
    populateCities(province, city, city.value);
    city.disabled = !province.value;
});

document.querySelectorAll('[data-checkout-details]').forEach(form => {
    const address = form.querySelector('[data-delivery-address]');
    const manualAddress = form.querySelector('[data-manual-address]');
    const province = form.querySelector('[data-region-province]');
    const city = form.querySelector('[data-region-city]');
    const update = () => {
        const pickup = form.querySelector('[name="delivery_method"]:checked')?.value === 'pickup';
        const useProfile = form.querySelector('[name="address_source"]:checked')?.value === 'profile';
        const needsManualAddress = !pickup && !useProfile;
        address.hidden = pickup;
        manualAddress.hidden = !needsManualAddress;
        address.querySelectorAll('[name="address_source"]').forEach(input => { input.disabled = pickup; });
        manualAddress.querySelectorAll('input, textarea, select').forEach(input => {
            input.disabled = !needsManualAddress;
            input.required = needsManualAddress;
        });
        city.disabled = !needsManualAddress || !province.value;
    };
    province.addEventListener('change', () => { populateCities(province, city); update(); });
    form.querySelectorAll('[name="delivery_method"]').forEach(input => input.addEventListener('change', update));
    form.querySelectorAll('[name="address_source"]').forEach(input => input.addEventListener('change', update));
    populateCities(province, city, city.value);
    update();
});

const salesLive = document.querySelector('[data-sales-live]');
if (salesLive) {
    const count = salesLive.querySelector('[data-sales-pending-count]');
    const status = salesLive.querySelector('[data-sales-live-status]');
    const toast = document.querySelector('[data-sales-toast]');
    const toastMessage = toast?.querySelector('[data-sales-toast-message]');
    let lastRevision = null;
    let lastOrderId = null;
    let polling = false;
    let toastTimer = null;

    const showToast = message => {
        toastMessage.textContent = message;
        toast.hidden = false;
        clearTimeout(toastTimer);
        toastTimer = setTimeout(() => { toast.hidden = true; }, 7000);
    };
    toast?.querySelector('[data-sales-toast-close]')?.addEventListener('click', () => {
        clearTimeout(toastTimer);
        toast.hidden = true;
    });

    const refreshSalesList = async () => {
        const current = document.querySelector('[data-sales-results]');
        if (!current) return;
        const response = await fetch(window.location.href, {
            credentials: 'same-origin', cache: 'no-store',
            headers: { 'Accept': 'text/html', 'X-Requested-With': 'XMLHttpRequest' },
        });
        if (!response.ok) throw new Error('Daftar Sales belum dapat diperbarui.');
        const nextPage = new DOMParser().parseFromString(await response.text(), 'text/html');
        const updated = nextPage.querySelector('[data-sales-results]');
        if (!updated) throw new Error('Daftar Sales belum dapat diperbarui.');
        current.replaceWith(updated);
    };

    const pollSales = async () => {
        if (polling || document.hidden) return;
        polling = true;
        try {
            const response = await fetch(salesLive.dataset.updatesUrl, {
                credentials: 'same-origin', cache: 'no-store',
                headers: { 'Accept': 'application/json' },
            });
            if (!response.ok) throw new Error('Pembaruan belum tersedia.');
            const data = await response.json();
            count.textContent = data.pending_count;
            if (lastRevision !== null && data.revision !== lastRevision) {
                await refreshSalesList();
                if (data.latest_order_id > lastOrderId) showToast('Pesanan customer baru masuk. Daftar Sales diperbarui.');
            }
            lastRevision = data.revision;
            lastOrderId = data.latest_order_id;
            status.textContent = 'Diperbarui otomatis setiap 5 detik';
        } catch {
            status.textContent = 'Koneksi terputus. Mencoba lagi otomatis.';
        } finally {
            polling = false;
        }
    };

    pollSales();
    setInterval(pollSales, 5000);
    document.addEventListener('visibilitychange', () => { if (!document.hidden) pollSales(); });
}

document.querySelectorAll('[data-product-image-input]').forEach(input => {
    const form = input.form;
    const preview = form.querySelector('[data-product-image-preview]');
    const placeholder = form.querySelector('[data-product-image-placeholder]');
    const status = form.querySelector('[data-product-image-status]');
    const submit = form.querySelector('button:not([type]), button[type="submit"]');
    let objectUrl = null;
    let processedFile = null;
    let selection = 0;

    form.addEventListener('formdata', event => {
        if (processedFile) event.formData.set(input.name, processedFile);
    });

    input.addEventListener('change', async () => {
        const selected = input.files?.[0];
        const currentSelection = ++selection;
        processedFile = null;
        if (objectUrl) URL.revokeObjectURL(objectUrl);
        objectUrl = null;
        if (!selected) {
            status.textContent = '';
            return;
        }
        submit.disabled = true;
        status.textContent = 'Memotong dan menyiapkan foto…';

        let source;
        try {
            const loadImage = async () => {
                const image = new Image();
                const url = URL.createObjectURL(selected);
                image.src = url;
                try { await image.decode(); } finally { URL.revokeObjectURL(url); }
                return image;
            };
            if ('createImageBitmap' in window) {
                try { source = await createImageBitmap(selected); }
                catch { source = await loadImage(); }
            } else {
                source = await loadImage();
            }

            const width = source.width || source.naturalWidth;
            const height = source.height || source.naturalHeight;
            if (!width || !height) throw new Error('Foto tidak dapat dibaca.');
            const ratio = 4 / 5;
            const cropWidth = Math.min(width, height * ratio);
            const cropHeight = Math.min(height, width / ratio);
            const canvas = document.createElement('canvas');
            canvas.width = 800;
            canvas.height = 1000;
            canvas.getContext('2d').drawImage(source, (width - cropWidth) / 2, (height - cropHeight) / 2, cropWidth, cropHeight, 0, 0, 800, 1000);
            const blob = await new Promise(resolve => canvas.toBlob(resolve, 'image/webp', 0.88));
            if (!blob) throw new Error('Foto tidak dapat diproses.');
            if (currentSelection !== selection) return;

            const extension = blob.type === 'image/webp' ? 'webp' : 'png';
            processedFile = new File([blob], `${selected.name.replace(/\.[^.]+$/, '') || 'produk'}.${extension}`, { type: blob.type });
            try {
                const transfer = new DataTransfer();
                transfer.items.add(processedFile);
                input.files = transfer.files;
            } catch {
                // The formdata listener still sends the processed file on browsers that disallow assignment.
            }
            objectUrl = URL.createObjectURL(processedFile);
            preview.src = objectUrl;
            preview.classList.remove('hidden');
            placeholder?.classList.add('hidden');
            status.textContent = 'Foto siap: crop tengah 4:5, ukuran 800 × 1000 px.';
        } catch {
            if (currentSelection !== selection) return;
            input.value = '';
            status.textContent = 'Foto tidak dapat diproses. Coba JPG, PNG, atau WebP lain.';
        } finally {
            source?.close?.();
            if (currentSelection === selection) submit.disabled = false;
        }
    });
});

const logoutDialog = document.querySelector('#logout-dialog');
let logoutForm = null;
document.querySelectorAll('form[data-logout]').forEach(form => {
    form.addEventListener('submit', event => {
        if (!logoutDialog || typeof logoutDialog.showModal !== 'function') {
            if (!window.confirm('Yakin ingin keluar dari akun?')) event.preventDefault();
            return;
        }
        event.preventDefault();
        if (logoutDialog.open) return;
        logoutForm = form;
        logoutDialog.querySelector('[data-logout-accept]').disabled = false;
        logoutDialog.showModal();
    });
});
if (logoutDialog) {
    logoutDialog.querySelector('[data-logout-cancel]').addEventListener('click', () => logoutDialog.close());
    logoutDialog.addEventListener('close', () => { logoutForm = null; });
    logoutDialog.querySelector('[data-logout-accept]').addEventListener('click', event => {
        if (!logoutForm) return;
        event.currentTarget.disabled = true;
        HTMLFormElement.prototype.submit.call(logoutForm);
    });
}

document.querySelectorAll('[data-print]').forEach(button => {
    button.addEventListener('click', () => window.print());
});
document.querySelectorAll('[data-notice]').forEach(notice => {
    notice.querySelector('[data-notice-close]')?.addEventListener('click', () => notice.remove());
    if (notice.dataset.autodismiss) setTimeout(() => notice.remove(), Number(notice.dataset.autodismiss));
});

const confirmDialog = document.querySelector('#confirm-dialog');
let confirmForm = null;
document.querySelectorAll('form[data-confirm]').forEach(form => {
    form.addEventListener('submit', event => {
        if (!confirmDialog || typeof confirmDialog.showModal !== 'function') {
            if (!window.confirm(form.dataset.confirm)) event.preventDefault();
            return;
        }
        event.preventDefault();
        if (confirmDialog.open) return;
        confirmForm = form;
        confirmDialog.querySelector('[data-confirm-message]').textContent = form.dataset.confirm;
        confirmDialog.querySelector('[data-confirm-accept]').disabled = false;
        confirmDialog.showModal();
    });
});
if (confirmDialog) {
    confirmDialog.querySelector('[data-confirm-cancel]').addEventListener('click', () => confirmDialog.close());
    confirmDialog.addEventListener('close', () => { confirmForm = null; });
    confirmDialog.querySelector('[data-confirm-accept]').addEventListener('click', event => {
        if (!confirmForm) return;
        event.currentTarget.disabled = true;
        HTMLFormElement.prototype.submit.call(confirmForm);
    });
}

document.querySelectorAll('[data-transaction-form]').forEach(form => {
    const list = form.querySelector('[data-item-list]');
    const template = form.querySelector('[data-item-template]');
    const add = form.querySelector('[data-add-item]');
    const status = form.querySelector('[data-item-status]');
    const sync = () => {
        const rows = [...list.querySelectorAll('[data-item-row]')];
        rows.forEach((row, index) => {
            row.querySelector('[data-product]').name = `items[${index}][product_id]`;
            row.querySelector('[data-quantity]').name = `items[${index}][quantity]`;
            row.querySelector('[data-remove-item]').disabled = rows.length <= 1;
        });
        add.disabled = rows.length >= 100;
        status.textContent = `${rows.length} item. Maksimal 100 item; pilih setiap produk satu kali.`;
    };
    add.addEventListener('click', () => {
        if (list.children.length >= 100) return;
        list.append(template.content.cloneNode(true));
        sync();
        list.lastElementChild.querySelector('[data-product]').focus();
    });
    list.addEventListener('click', event => {
        const button = event.target.closest('[data-remove-item]');
        if (!button || list.children.length <= 1) return;
        button.closest('[data-item-row]').remove();
        sync();
        add.focus();
    });
    sync();
});
