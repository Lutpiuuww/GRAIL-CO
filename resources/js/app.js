import './bootstrap';
import regions from '../data/indonesia-regions.json';

document.querySelectorAll('[data-checkout-details]').forEach(form => {
    const address = form.querySelector('[data-delivery-address]');
    const province = form.querySelector('[data-region-province]');
    const city = form.querySelector('[data-region-city]');
    const syncCities = (selected = '') => {
        city.replaceChildren(new Option('Pilih kota / kabupaten', ''));
        (regions[province.value] || []).forEach(name => city.add(new Option(name, name)));
        if ((regions[province.value] || []).includes(selected)) city.value = selected;
    };
    const update = () => {
        const pickup = form.querySelector('[name="delivery_method"]:checked')?.value === 'pickup';
        address.hidden = pickup;
        address.querySelectorAll('input, textarea, select').forEach(input => {
            input.disabled = pickup;
            input.required = !pickup;
        });
        city.disabled = pickup || !province.value;
    };
    province.addEventListener('change', () => { syncCities(); update(); });
    form.querySelectorAll('[name="delivery_method"]').forEach(input => input.addEventListener('change', update));
    syncCities(city.value);
    update();
});

document.querySelectorAll('[data-product-image-input]').forEach(input => {
    const preview = document.querySelector('[data-product-image-preview]');
    const placeholder = document.querySelector('[data-product-image-placeholder]');
    let objectUrl = null;
    input.addEventListener('change', () => {
        if (objectUrl) URL.revokeObjectURL(objectUrl);
        objectUrl = input.files?.[0] ? URL.createObjectURL(input.files[0]) : null;
        if (objectUrl) {
            preview.src = objectUrl;
            preview.classList.remove('hidden');
            placeholder?.classList.add('hidden');
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
document.querySelectorAll('form[data-confirm]').forEach(form => {
    form.addEventListener('submit', event => {
        if (!window.confirm(form.dataset.confirm)) event.preventDefault();
    });
});

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
