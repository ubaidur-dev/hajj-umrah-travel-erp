import Chart from 'chart.js/auto';
window.Chart = Chart;

const csrf = () => document.querySelector('meta[name=csrf-token]')?.content;

/* ---------- Toast notifications (top-right, disappear by themselves) ---------- */
const ICONS = {
    success: 'M5 13l4 4L19 7',
    error: 'M6 18L18 6M6 6l12 12',
    info: 'M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z',
};
export function toast(message, type = 'success', ms = 4000) {
    let box = document.getElementById('toasts');
    if (!box) {
        box = document.createElement('div');
        box.id = 'toasts';
        box.className = 'fixed top-4 right-4 z-[100] flex flex-col gap-2 w-[calc(100%-2rem)] sm:w-96 pointer-events-none';
        document.body.appendChild(box);
    }
    const tone = { success: 'bg-brand-700', error: 'bg-red-600', info: 'bg-slate-800' }[type] || 'bg-brand-700';
    const el = document.createElement('div');
    el.className = `pointer-events-auto flex items-start gap-3 rounded-xl ${tone} text-white shadow-2xl px-4 py-3 text-sm translate-x-8 opacity-0 transition duration-300`;
    el.innerHTML = `<svg class="w-5 h-5 mt-0.5 shrink-0" fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="${ICONS[type] || ICONS.success}"/></svg>
        <div class="flex-1 font-medium"></div><button class="opacity-70 hover:opacity-100 leading-none text-lg">×</button>`;
    el.children[1].textContent = message;
    const close = () => { el.classList.add('opacity-0', 'translate-x-8'); setTimeout(() => el.remove(), 300); };
    el.querySelector('button').onclick = close;
    box.appendChild(el);
    requestAnimationFrame(() => el.classList.remove('opacity-0', 'translate-x-8'));
    setTimeout(close, ms);
}
window.toast = toast;

document.addEventListener('DOMContentLoaded', () => {
    const f = document.getElementById('flash');
    if (f?.dataset.ok) toast(f.dataset.ok, 'success');
    if (f?.dataset.err) toast(f.dataset.err, 'error', 7000);
    if (f?.dataset.invalid) toast('Please fix the highlighted fields.', 'error', 6000);
});

/* ---------- Confirm + double-submit protection ---------- */
document.addEventListener('submit', (e) => {
    const form = e.target;
    const msg = form.dataset.confirm;
    if (msg && !confirm(msg)) { e.preventDefault(); return; }
    if (form.hasAttribute('data-ajax')) { e.preventDefault(); ajaxSubmit(form); return; }
    if ((form.method || 'get').toLowerCase() !== 'get') {
        const btns = form.querySelectorAll('button:not([type=button])');
        btns.forEach((b) => b.classList.add('opacity-60', 'pointer-events-none'));
        setTimeout(() => btns.forEach((b) => b.classList.remove('opacity-60', 'pointer-events-none')), 4000);
    }
});

/* ---------- Forms that save without reloading: <form data-ajax> ---------- */
async function ajaxSubmit(form) {
    try {
        const res = await fetch(form.action, {
            method: 'POST', body: new FormData(form),
            headers: { 'X-Requested-With': 'XMLHttpRequest', Accept: 'application/json', 'X-CSRF-TOKEN': csrf() },
        });
        const data = await res.json().catch(() => ({}));
        if (!res.ok) throw new Error(data.message || 'Could not save');
        toast(data.message || 'Saved', 'success', 2500);
    } catch (err) { toast(err.message, 'error'); }
}
document.addEventListener('change', (e) => {
    if (e.target.matches('[data-autosave]')) e.target.closest('form').requestSubmit();
});

/* ---------- Live search & filters: <form data-auto> ----------
   Results update while you type (no page reload), the URL stays shareable, best matches come first. */
const SEP = '[\\s\\-+.\\/()_]*';
const esc = (t) => t.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');

/** Highlights what was typed inside the live results. Dashes/spaces in numbers do not matter. */
function highlight(root, term) {
    root.querySelectorAll('mark.hl').forEach((m) => m.replaceWith(document.createTextNode(m.textContent)));
    root.normalize();
    const tokens = term.trim().split(/\s+/).filter(Boolean);
    if (!tokens.length) return;
    const re = new RegExp(tokens.map((t) => [...t.replace(/[\s\-+.\/()_]/g, '')].map(esc).join(SEP)).filter(Boolean).join('|'), 'gi');
    const walker = document.createTreeWalker(root, NodeFilter.SHOW_TEXT, {
        acceptNode: (n) => (n.parentElement.closest('button, a.btn, form, script, style, mark, .badge') || !n.nodeValue.trim() ? NodeFilter.FILTER_REJECT : NodeFilter.FILTER_ACCEPT),
    });
    const nodes = []; while (walker.nextNode()) nodes.push(walker.currentNode);
    nodes.forEach((n) => {
        const text = n.nodeValue; re.lastIndex = 0;
        if (!re.test(text)) return;
        re.lastIndex = 0;
        const frag = document.createDocumentFragment(); let last = 0, m;
        while ((m = re.exec(text))) {
            if (!m[0]) { re.lastIndex++; continue; }
            frag.append(text.slice(last, m.index));
            const mk = document.createElement('mark'); mk.className = 'hl'; mk.textContent = m[0]; frag.append(mk);
            last = m.index + m[0].length;
        }
        frag.append(text.slice(last)); n.replaceWith(frag);
    });
}

let liveCtl;
async function liveLoad(url, term, push = true) {
    liveCtl?.abort(); liveCtl = new AbortController();
    const blocks = [...document.querySelectorAll('[data-live]')];
    blocks.forEach((b) => b.classList.add('opacity-50', 'transition-opacity'));
    try {
        const res = await fetch(url, { signal: liveCtl.signal, headers: { 'X-Requested-With': 'XMLHttpRequest' } });
        const doc = new DOMParser().parseFromString(await res.text(), 'text/html');
        const fresh = [...doc.querySelectorAll('[data-live]')];
        if (fresh.length !== blocks.length) { location.href = url; return; }
        blocks.forEach((b, i) => { b.innerHTML = fresh[i].innerHTML; b.classList.remove('opacity-50'); highlight(b, term); });
        if (push) history.replaceState(null, '', url);
    } catch (e) { if (e.name !== 'AbortError') blocks.forEach((b) => b.classList.remove('opacity-50')); }
}

document.querySelectorAll('form[data-auto]').forEach((form) => {
    const q = form.querySelector('input[name=q]');
    const urlFor = () => {
        const p = new URLSearchParams();
        new FormData(form).forEach((v, k) => { if (String(v).trim() !== '') p.append(k, v); });
        return (form.getAttribute('action') || location.pathname) + (p.toString() ? '?' + p : '');
    };
    const run = () => liveLoad(urlFor(), q?.value || '');
    let t;
    form.addEventListener('submit', (e) => { e.preventDefault(); run(); });
    form.addEventListener('change', run);
    form.addEventListener('input', (e) => {
        if (e.target === q) { clearTimeout(t); t = setTimeout(run, 120); }
    });
    // pagination inside the results also stays on the page
    document.addEventListener('click', (e) => {
        const a = e.target.closest('[data-live] nav a[href*="page="], [data-live] a[rel="next"], [data-live] a[rel="prev"]');
        if (!a) return;
        e.preventDefault(); liveLoad(a.href, q?.value || ''); form.scrollIntoView({ behavior: 'smooth', block: 'start' });
    });
    if (q) {
        if (q.value) { q.focus(); q.setSelectionRange(q.value.length, q.value.length); }
        document.querySelectorAll('[data-live]').forEach((b) => highlight(b, q.value));
    }
});

/* ---------- Search suggestions (type-ahead box under the search field) ---------- */
document.querySelectorAll('input[data-suggest]').forEach((input) => {
    const scope = input.dataset.suggest;
    const wrap = document.createElement('div'); wrap.className = 'relative'; input.replaceWith(wrap); wrap.append(input);
    const box = document.createElement('div');
    box.className = 'hidden absolute left-0 top-full mt-2 z-50 w-[min(34rem,92vw)] rounded-xl border border-slate-200 bg-white shadow-2xl overflow-hidden font-sans';
    box.setAttribute('role', 'listbox'); wrap.append(box);
    let items = [], active = -1, ctl, timer;

    const close = () => { box.classList.add('hidden'); active = -1; };
    const mark = (text, term) => {
        const out = document.createElement('span');
        const tokens = term.trim().split(/\s+/).filter(Boolean).map((t) => [...t.replace(/[\s\-+.\/()_]/g, '')].map(esc).join(SEP)).filter(Boolean);
        if (!tokens.length) { out.textContent = text; return out; }
        const re = new RegExp(tokens.join('|'), 'gi'); let last = 0, m;
        while ((m = re.exec(text))) {
            if (!m[0]) { re.lastIndex++; continue; }
            out.append(text.slice(last, m.index)); const mk = document.createElement('mark'); mk.className = 'hl'; mk.textContent = m[0]; out.append(mk); last = m.index + m[0].length;
        }
        out.append(text.slice(last)); return out;
    };
    const paint = () => {
        box.replaceChildren();
        if (!items.length) {
            const e = document.createElement('div'); e.className = 'px-4 py-3 text-sm text-slate-500'; e.textContent = 'No matches — try a different spelling or fewer letters.'; box.append(e); return;
        }
        const head = document.createElement('div'); head.className = 'px-4 pt-2.5 pb-1 text-[11px] font-semibold uppercase tracking-wider text-slate-400'; head.textContent = 'Best matches'; box.append(head);
        items.forEach((it, i) => {
            const a = document.createElement('a'); a.href = it.url; a.setAttribute('role', 'option');
            a.className = 'block px-4 py-2.5 border-t border-slate-100 first:border-0 ' + (i === active ? 'bg-brand-50' : 'hover:bg-slate-50');
            const t = document.createElement('div'); t.className = 'text-sm font-semibold text-slate-900 truncate'; t.append(mark(it.title, input.value));
            const s = document.createElement('div'); s.className = 'text-xs text-slate-500 truncate'; s.append(mark(it.sub || '', input.value));
            a.append(t, s); a.addEventListener('mouseenter', () => { active = i; box.querySelectorAll('a').forEach((x, j) => x.classList.toggle('bg-brand-50', j === i)); });
            box.append(a);
        });
    };
    const fetchHints = async () => {
        const term = input.value.trim();
        if (!term) { close(); return; }
        ctl?.abort(); ctl = new AbortController();
        try {
            const res = await fetch(`/search/suggest?scope=${encodeURIComponent(scope)}&q=${encodeURIComponent(term)}`, { signal: ctl.signal, headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' } });
            items = await res.json(); active = -1; paint(); box.classList.remove('hidden');
        } catch (e) { /* aborted */ }
    };
    input.addEventListener('input', () => { clearTimeout(timer); timer = setTimeout(fetchHints, 120); });
    input.addEventListener('focus', () => { if (input.value.trim()) fetchHints(); });
    input.addEventListener('keydown', (e) => {
        const links = [...box.querySelectorAll('a')];
        if (e.key === 'ArrowDown' && links.length) { e.preventDefault(); active = (active + 1) % links.length; }
        else if (e.key === 'ArrowUp' && links.length) { e.preventDefault(); active = (active - 1 + links.length) % links.length; }
        else if (e.key === 'Enter' && active >= 0 && links[active]) { e.preventDefault(); location.href = links[active].href; return; }
        else if (e.key === 'Escape') { close(); return; }
        else return;
        links.forEach((x, j) => x.classList.toggle('bg-brand-50', j === active)); links[active]?.scrollIntoView({ block: 'nearest' });
    });
    document.addEventListener('click', (e) => { if (!wrap.contains(e.target)) close(); });
});

/* ---------- Uploader (Spatie Media Library on the server) ---------- */
const PDF_ICON = '<svg class="w-10 h-10 text-slate-400" fill="none" stroke="currentColor" stroke-width="1.4" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M7 3h7l5 5v13H7zM14 3v5h5"/></svg>';

function tile(u, item) {
    const el = document.createElement('div');
    el.className = 'group relative rounded-xl border border-slate-200 bg-white overflow-hidden';
    if (item.id) el.dataset.mediaId = item.id;
    const round = u.dataset.shape === 'circle';
    el.innerHTML = `<a ${item.url ? `href="${item.url}" target="_blank"` : ''} class="block ${round ? 'aspect-square' : 'aspect-[4/3]'} bg-slate-100 flex items-center justify-center overflow-hidden">
        ${item.thumb ? `<img src="${item.thumb}" class="w-full h-full object-cover" alt="">` : PDF_ICON}</a>
        <div class="px-2 py-1.5 text-[11px] text-slate-600 truncate" data-name></div>
        <div data-progress class="hidden absolute inset-x-0 bottom-0 h-1 bg-brand-100"><div class="h-full bg-brand-600 w-0 transition-all"></div></div>
        <button type="button" title="Remove" class="absolute top-1.5 right-1.5 h-6 w-6 rounded-full bg-black/60 hover:bg-red-600 text-white text-sm leading-none opacity-100 sm:opacity-0 group-hover:opacity-100 transition">×</button>`;
    el.querySelector('[data-name]').textContent = item.name + (item.size ? ` · ${item.size}` : '');
    el.querySelector('button').onclick = () => removeTile(u, el, item);
    return el;
}

async function removeTile(u, el, item) {
    if (item.local) { item.local(); return; }                       // deferred file not yet uploaded
    if (!item.delete || !confirm('Remove this file?')) return;
    const res = await fetch(item.delete, { method: 'DELETE', headers: { 'X-CSRF-TOKEN': csrf(), Accept: 'application/json' } });
    if (!res.ok) return toast('Could not remove the file', 'error');
    el.remove();
    toast('File removed', 'success', 2500);
    if (u.dataset.reload === '1') setTimeout(() => location.reload(), 600);
}

function uploadFiles(u, files) {
    const tiles = u.querySelector('[data-tiles]');
    const single = u.dataset.multiple !== '1';
    if (single) { files = files.slice(0, 1); tiles.querySelectorAll('[data-media-id]').forEach((t) => t.remove()); }
    files.forEach((file) => {
        const el = tile(u, { name: file.name, size: (file.size / 1024 / 1024).toFixed(1) + ' MB', thumb: file.type.startsWith('image/') ? URL.createObjectURL(file) : null });
        tiles.appendChild(el);
        const bar = el.querySelector('[data-progress]'); bar.classList.remove('hidden');
        const fd = new FormData();
        fd.append('model', u.dataset.model); fd.append('id', u.dataset.id); fd.append('files[]', file);
        const xhr = new XMLHttpRequest();
        xhr.open('POST', u.dataset.url);
        xhr.setRequestHeader('X-CSRF-TOKEN', csrf()); xhr.setRequestHeader('Accept', 'application/json');
        xhr.upload.onprogress = (e) => { if (e.lengthComputable) bar.firstElementChild.style.width = (e.loaded / e.total * 100) + '%'; };
        xhr.onload = () => {
            let data = {}; try { data = JSON.parse(xhr.responseText); } catch (e) {}
            if (xhr.status === 200 && data.items?.length) {
                el.replaceWith(tile(u, data.items[0]));
                toast(single ? 'Saved' : 'File uploaded', 'success', 2500);
                if (u.dataset.reload === '1') setTimeout(() => location.reload(), 700);
            } else {
                el.remove();
                toast(data.errors ? Object.values(data.errors).flat()[0] : (data.message || 'Upload failed'), 'error', 6000);
            }
        };
        xhr.onerror = () => { el.remove(); toast('Upload failed. Check your connection.', 'error'); };
        xhr.send(fd);
    });
}

function renderDeferred(u, input) {
    // Files stay inside the <input>, so they are posted together with the surrounding form.
    const tiles = u.querySelector('[data-tiles]');
    tiles.querySelectorAll('[data-local]').forEach((t) => t.remove());
    const dt = new DataTransfer();
    u._files.forEach((file) => {
        dt.items.add(file);
        const item = { name: file.name, size: (file.size / 1024 / 1024).toFixed(1) + ' MB', thumb: file.type.startsWith('image/') ? URL.createObjectURL(file) : null };
        const el = tile(u, item);
        el.dataset.local = '1';
        el.querySelector('button').onclick = () => { u._files = u._files.filter((f) => f !== file); renderDeferred(u, input); };
        tiles.appendChild(el);
    });
    input.files = dt.files;
}

document.querySelectorAll('[data-uploader]').forEach((u) => {
    const input = u.querySelector('input[type=file]');
    const drop = u.querySelector('[data-drop]');
    const tiles = u.querySelector('[data-tiles]');
    const single = u.dataset.multiple !== '1';
    u._files = [];
    JSON.parse(u.querySelector('[data-initial]').textContent).forEach((m) => tiles.appendChild(tile(u, m)));

    const handle = (files) => {
        files = [...files];
        if (!files.length) return;
        if (u.dataset.mode === 'instant') return uploadFiles(u, files);
        u._files = single ? [files[0]] : [...u._files, ...files];
        renderDeferred(u, input);
    };
    input.addEventListener('change', () => { handle(input.files); if (u.dataset.mode === 'instant') input.value = ''; });
    ['dragenter', 'dragover'].forEach((ev) => drop.addEventListener(ev, (e) => { e.preventDefault(); drop.classList.add('border-brand-500', 'bg-brand-50'); }));
    ['dragleave', 'drop'].forEach((ev) => drop.addEventListener(ev, (e) => { e.preventDefault(); drop.classList.remove('border-brand-500', 'bg-brand-50'); }));
    drop.addEventListener('drop', (e) => handle(e.dataTransfer.files));
});

/* ---------- Pakistani CNIC mask: 12345-1234567-1 ---------- */
document.addEventListener('input', (e) => {
    if (!e.target.matches('[data-cnic]')) return;
    const d = e.target.value.replace(/\D/g, '').slice(0, 13);
    e.target.value = [d.slice(0, 5), d.slice(5, 12), d.slice(12)].filter(Boolean).join('-');
});

/* ---------- Pakistani input masks ---------- */
const phoneMask = (raw) => {
    let d = raw.replace(/\D/g, '');
    if (d && d[0] !== '0') d = '0' + d;                 // always starts with 0
    if (d.length > 1 && d[1] !== '3') d = d[0] + d.slice(2); // mobile numbers start with 03
    d = d.slice(0, 11);
    return d.length > 4 ? d.slice(0, 4) + '-' + d.slice(4) : d;
};
document.addEventListener('input', (e) => {
    const el = e.target;
    if (el.matches('[data-phone]')) el.value = phoneMask(el.value);
    else if (el.matches('[data-phones]')) {                 // up to two numbers, e.g. 0341-2185055, 0333-3002438
        const [first, second = ''] = el.value.split(',');
        const a = phoneMask(first), b = phoneMask(second);
        el.value = el.value.includes(',') ? a + ',' + (b ? ' ' + b : '') : a;
    } else if (el.matches('[data-passport]')) {             // 2 letters + 7 digits
        let out = '';
        for (const ch of el.value.toUpperCase()) {
            if (out.length < 2 ? /[A-Z]/.test(ch) : (out.length < 9 && /\d/.test(ch))) out += ch;
        }
        el.value = out;
    } else if (el.matches('[data-email]')) {                // nothing can be typed after .com
        let v = el.value.toLowerCase().replace(/\s/g, '');
        const i = v.indexOf('.com', v.indexOf('@') + 1);
        if (v.includes('@') && i > 0) v = v.slice(0, i + 4);
        el.value = v;
    }
});
