/* LaporSarana — skrip bersama. Tanpa library; semua fitur bersifat tambahan,
   halaman tetap berfungsi bila JavaScript mati. */
(function () {
    'use strict';

    var $ = function (sel, root) { return (root || document).querySelector(sel); };
    var $$ = function (sel, root) { return Array.prototype.slice.call((root || document).querySelectorAll(sel)); };
    var csrf = (document.querySelector('meta[name="csrf-token"]') || {}).content || '';

    /* ---------- Tema terang / gelap ---------- */
    $$('[data-theme-toggle]').forEach(function (btn) {
        btn.addEventListener('click', function () {
            var gelap = document.documentElement.getAttribute('data-theme') === 'dark';
            var baru = gelap ? 'light' : 'dark';
            document.documentElement.setAttribute('data-theme', baru);
            try { localStorage.setItem('tema', baru); } catch (e) { /* abaikan */ }
        });
    });

    /* ---------- Sidebar pada layar kecil ---------- */
    var menuBtn = $('#menu-toggle');
    if (menuBtn) {
        menuBtn.addEventListener('click', function () { document.body.classList.toggle('nav-open'); });
        document.addEventListener('click', function (e) {
            if (document.body.classList.contains('nav-open') && !e.target.closest('.sidebar') && !e.target.closest('#menu-toggle')) {
                document.body.classList.remove('nav-open');
            }
        });
    }

    /* ---------- Toast ---------- */
    function tutupToast(el) {
        el.classList.add('out');
        setTimeout(function () { if (el.parentNode) { el.parentNode.removeChild(el); } }, 260);
    }
    $$('.toast').forEach(function (el) {
        var x = $('.toast-x', el);
        if (x) { x.addEventListener('click', function () { tutupToast(el); }); }
        setTimeout(function () { tutupToast(el); }, el.classList.contains('toast-error') ? 6000 : 4000);
    });

    /* ---------- Konfirmasi sebelum aksi penting ---------- */
    $$('form[data-confirm]').forEach(function (form) {
        form.addEventListener('submit', function (e) {
            if (!window.confirm(form.getAttribute('data-confirm'))) { e.preventDefault(); }
        });
    });

    /* ---------- Tombol berputar saat kirim (cegah klik ganda) ---------- */
    $$('form[data-loading]').forEach(function (form) {
        form.addEventListener('submit', function (e) {
            if (e.defaultPrevented) { return; }
            setTimeout(function () {
                $$('button[type="submit"], button:not([type])', form).forEach(function (b) { b.classList.add('is-loading'); });
            }, 0);
        });
    });

    /* ---------- Validasi per isian (langsung saat diketik / ditinggalkan) ---------- */
    function pesanValidasi(el) {
        var v = el.validity;
        if (v.valueMissing) { return 'Wajib diisi.'; }
        if (v.typeMismatch) { return 'Format tidak valid.'; }
        if (v.tooShort) { return 'Minimal ' + el.getAttribute('minlength') + ' karakter.'; }
        if (v.tooLong) { return 'Maksimal ' + el.getAttribute('maxlength') + ' karakter.'; }
        if (v.patternMismatch) { return 'Format tidak sesuai.'; }
        return el.validationMessage;
    }
    function periksa(el) {
        var wadah = el.closest('.field');
        if (!wadah) { return; }
        var box = $('.field-error[data-js]', wadah);
        if (el.checkValidity()) {
            el.classList.remove('is-invalid');
            if (box) { box.remove(); }
            return;
        }
        el.classList.add('is-invalid');
        if (!box) {
            box = document.createElement('div');
            box.className = 'field-error';
            box.setAttribute('data-js', '1');
            wadah.appendChild(box);
        }
        box.textContent = pesanValidasi(el);
    }
    $$('form[data-validate]').forEach(function (form) {
        form.setAttribute('novalidate', 'novalidate');
        $$('input, select, textarea', form).forEach(function (el) {
            if (el.type === 'hidden' || el.type === 'file' || el.type === 'checkbox' || el.type === 'radio') { return; }
            el.addEventListener('blur', function () { periksa(el); });
            el.addEventListener('input', function () { if (el.classList.contains('is-invalid')) { periksa(el); } });
        });
        form.addEventListener('submit', function (e) {
            var pertama = null;
            $$('input, select, textarea', form).forEach(function (el) {
                if (el.type === 'hidden' || el.type === 'file' || el.type === 'checkbox' || el.type === 'radio') { return; }
                periksa(el);
                if (!el.checkValidity() && !pertama) { pertama = el; }
            });
            if (pertama) {
                e.preventDefault();
                e.stopImmediatePropagation();
                pertama.focus();
            }
        }, true);
    });

    /* ---------- Unggah foto: seret-lepas + pratinjau ---------- */
    $$('[data-dropzone]').forEach(function (zona) {
        var input = $('input[type="file"]', zona);
        var img = $('.dz-preview img', zona);
        var nama = $('.dz-name', zona);
        var err = zona.parentNode.querySelector('.dz-error');
        var batas = parseInt(zona.getAttribute('data-max-mb') || '5', 10) * 1024 * 1024;
        if (!input) { return; }

        function tampil(msg) {
            if (!err) { return; }
            err.textContent = msg || '';
            err.style.display = msg ? 'block' : 'none';
        }
        function atur(file) {
            if (!file) { return; }
            if (!/^image\/(jpeg|png|webp)$/.test(file.type)) {
                input.value = '';
                tampil('Format harus JPG, PNG, atau WEBP.');
                return;
            }
            if (file.size > batas) {
                input.value = '';
                tampil('Ukuran foto maksimal ' + (batas / 1048576) + ' MB.');
                return;
            }
            tampil('');
            var reader = new FileReader();
            reader.onload = function (e) {
                img.src = e.target.result;
                nama.textContent = file.name + ' · ' + Math.round(file.size / 1024) + ' KB';
                zona.classList.add('has-file');
            };
            reader.readAsDataURL(file);
        }
        input.addEventListener('change', function () { atur(input.files[0]); });
        ['dragenter', 'dragover'].forEach(function (n) {
            zona.addEventListener(n, function (e) { e.preventDefault(); zona.classList.add('drag'); });
        });
        ['dragleave', 'drop'].forEach(function (n) {
            zona.addEventListener(n, function (e) { e.preventDefault(); zona.classList.remove('drag'); });
        });
        zona.addEventListener('drop', function (e) {
            if (e.dataTransfer && e.dataTransfer.files.length) {
                input.files = e.dataTransfer.files;
                atur(input.files[0]);
            }
        });
        var hapus = $('.dz-clear', zona);
        if (hapus) {
            hapus.addEventListener('click', function () {
                input.value = '';
                img.removeAttribute('src');
                zona.classList.remove('has-file');
                tampil('');
            });
        }
    });

    /* ---------- Pendaftaran: nama muncul otomatis dari NIS / NIP ---------- */
    var formDaftar = $('#form-daftar');
    if (formDaftar) {
        var urlCari = formDaftar.getAttribute('data-lookup');
        var inputNomor = $('#nomor_identitas', formDaftar);
        var kotakNama = $('#nama-otomatis', formDaftar);
        var info = $('#info-nomor', formDaftar);
        var label = $('#label-nomor', formDaftar);
        var hint = $('#hint-nomor', formDaftar);
        var timer = null;

        var teks = {
            Siswa: { label: 'NIS', hint: 'Nomor Induk Siswa, contoh: 2526001', ph: 'Masukkan NIS' },
            Guru: { label: 'NIP', hint: 'Nomor Induk Pegawai (18 digit), contoh: 198503122010012005', ph: 'Masukkan NIP' }
        };
        function jenis() {
            var r = $('input[name="jenis_pengguna"]:checked', formDaftar);
            return r ? r.value : 'Siswa';
        }
        function perbaruiLabel() {
            var t = teks[jenis()];
            label.textContent = t.label;
            hint.textContent = t.hint;
            inputNomor.placeholder = t.ph;
        }
        function setNama(text, kosong) {
            kotakNama.textContent = text;
            kotakNama.classList.toggle('empty-name', !!kosong);
        }
        function cari() {
            var nomor = inputNomor.value.trim();
            info.className = 'field-ok';
            info.textContent = '';
            if (nomor.length < 5) { setNama('Nama akan muncul otomatis setelah nomor diisi', true); return; }
            fetch(urlCari + '?jenis_pengguna=' + encodeURIComponent(jenis()) + '&nomor_identitas=' + encodeURIComponent(nomor), {
                headers: { 'Accept': 'application/json' }
            }).then(function (r) { return r.json().then(function (d) { return { ok: r.ok, d: d }; }); })
              .then(function (res) {
                  if (res.ok && res.d.success) {
                      setNama(res.d.nama_lengkap + (res.d.kelas ? ' · ' + res.d.kelas : ''), false);
                      info.className = 'field-ok';
                      info.textContent = 'Data ditemukan.';
                  } else {
                      setNama('Nama akan muncul otomatis setelah nomor diisi', true);
                      info.className = 'field-error';
                      info.textContent = (res.d && res.d.message) || 'Data tidak ditemukan.';
                  }
              }).catch(function () { /* jaringan gagal: biarkan server yang memvalidasi saat kirim */ });
        }
        $$('input[name="jenis_pengguna"]', formDaftar).forEach(function (r) {
            r.addEventListener('change', function () { perbaruiLabel(); cari(); });
        });
        inputNomor.addEventListener('input', function () {
            clearTimeout(timer);
            timer = setTimeout(cari, 450);
        });
        perbaruiLabel();
        if (inputNomor.value) { cari(); }
    }

    /* ---------- Chat (polling sederhana) ---------- */
    var chat = $('#chat');
    if (chat) {
        var log = $('#chat-log', chat);
        var form = $('#chat-form', chat);
        var area = $('textarea', form);
        var tombol = $('button[type="submit"]', form);
        var urlBaru = chat.getAttribute('data-url-baru');
        var urlKirim = chat.getAttribute('data-url-kirim');
        var terakhir = parseInt(chat.getAttribute('data-terakhir') || '0', 10);
        var sudahAda = {};

        $$('.msg', log).forEach(function (m) { sudahAda[m.getAttribute('data-id')] = true; });

        function ke_bawah() { log.scrollTop = log.scrollHeight; }
        function tambah(p) {
            if (sudahAda[p.id]) { return; }
            sudahAda[p.id] = true;
            var kosong = $('.chat-empty', log);
            if (kosong) { kosong.remove(); }
            var wrap = document.createElement('div');
            wrap.className = 'msg' + (p.milikku ? ' mine' : '');
            wrap.setAttribute('data-id', p.id);
            var who = document.createElement('div');
            who.className = 'who';
            who.textContent = p.milikku ? 'Kamu' : p.nama + (p.peran === 'Admin' ? ' · Admin' : '');
            var b = document.createElement('div');
            b.className = 'bubble';
            b.textContent = p.isi;
            var t = document.createElement('div');
            t.className = 'time';
            t.textContent = p.waktu;
            wrap.appendChild(who);
            wrap.appendChild(b);
            wrap.appendChild(t);
            log.appendChild(wrap);
            if (p.id > terakhir) { terakhir = p.id; }
        }
        function tarik() {
            if (document.hidden) { return; }
            fetch(urlBaru + '?setelah=' + terakhir, { headers: { 'Accept': 'application/json' } })
                .then(function (r) { return r.ok ? r.json() : null; })
                .then(function (d) {
                    if (d && d.pesan && d.pesan.length) {
                        var dekat = log.scrollHeight - log.scrollTop - log.clientHeight < 120;
                        d.pesan.forEach(tambah);
                        if (dekat) { ke_bawah(); }
                    }
                }).catch(function () { /* coba lagi pada putaran berikutnya */ });
        }
        function kirim() {
            var isi = area.value.trim();
            if (!isi) { return; }
            tombol.disabled = true;
            fetch(urlKirim, {
                method: 'POST',
                headers: { 'Accept': 'application/json', 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf },
                body: JSON.stringify({ isi: isi })
            }).then(function (r) { return r.ok ? r.json() : Promise.reject(); })
              .then(function (d) {
                  tambah(d.pesan);
                  area.value = '';
                  area.style.height = '';
                  ke_bawah();
              })
              .catch(function () { window.alert('Pesan gagal dikirim. Periksa koneksi lalu coba lagi.'); })
              .then(function () { tombol.disabled = false; area.focus(); });
        }
        form.addEventListener('submit', function (e) {
            e.preventDefault();
            kirim();
        });
        area.addEventListener('keydown', function (e) {
            if (e.key === 'Enter' && !e.shiftKey) { e.preventDefault(); kirim(); }
        });
        area.addEventListener('input', function () {
            area.style.height = 'auto';
            area.style.height = Math.min(area.scrollHeight, 130) + 'px';
        });
        ke_bawah();
        setInterval(tarik, 5000);
    }

    /* ---------- Angka ringkasan & lencana diperbarui otomatis ---------- */
    var urlRingkasan = document.body.getAttribute('data-ringkasan');
    if (urlRingkasan) {
        var setBadge = function (nama, n) {
            $$('[data-badge="' + nama + '"]').forEach(function (el) {
                el.textContent = n > 99 ? '99+' : n;
                el.hidden = !(n > 0);
            });
        };
        var segarkan = function () {
            if (document.hidden) { return; }
            fetch(urlRingkasan, { headers: { 'Accept': 'application/json' } })
                .then(function (r) { return r.ok ? r.json() : null; })
                .then(function (d) {
                    if (!d) { return; }
                    setBadge('notif', d.notif);
                    setBadge('pesan', d.pesan);
                    Object.keys(d.angka || {}).forEach(function (k) {
                        $$('[data-stat="' + k + '"]').forEach(function (el) { el.textContent = d.angka[k]; });
                    });
                }).catch(function () { /* diam saja */ });
        };
        setInterval(segarkan, 20000);
    }
})();
