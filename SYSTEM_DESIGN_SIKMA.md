# SYSTEM DESIGN - SIKMA
## Sistem Informasi Keluar Masuk BPMP Gorontalo

**Versi**: 1.2.0
**Status**: Draft
**Last Updated**: 2026

---

## 📋 OVERVIEW

Aplikasi web untuk pencatatan aktivitas keluar masuk pegawai BPMP Gorontalo berbasis **scan QR Code**.

- Pegawai **keluar** → scan QR di **laptop lobby**
- Pegawai **kembali** → scan QR di **laptop satpam (pos security)**
- QR berganti otomatis setiap ~30 detik (tidak bisa difoto untuk dipakai nanti)
- Identitas diambil dari **akun pegawai yang sedang login di ponsel**, bukan dari QR

Sistem ini **tidak menggantikan** absen masuk/pulang yang sudah ada. Hanya mencatat aktivitas keluar di antara jam kerja.

---

## 👥 USER ROLES & ACCESS CONTROL

| Role | Akses | Fungsi |
|------|-------|--------|
| **Pegawai** | `/pages/pegawai/` | Scan QR keluar/masuk, ajukan izin dinas, lihat riwayat pribadi |
| **Atasan Langsung** | `/pages/atasan/` | Setujui atau tolak pengajuan izin dinas bawahan |
| **Akun Lobby** | `/pages/lobby/` | Hanya tampilkan QR Keluar yang berganti sendiri |
| **Akun Pos** | `/pages/pos/` | Tampilkan QR Masuk + daftar pindaian hari ini |
| **Admin Kepegawaian** | `/pages/admin/` | Kelola akun, jam kerja, rekap seluruh balai |
| **Pimpinan** | `/pages/pimpinan/` | Lihat rekap unit kerjanya (read-only) |

> Akun Lobby dan Akun Pos **tidak bisa** mengisi, mengubah, atau menghapus data. Keduanya hanya menampilkan QR dan hasil pindaian.

---

## 🏗️ ARSITEKTUR SISTEM

### Prinsip Arsitektur

1. **Modular per fitur** — setiap folder fitur di `pages/` berisi file sendiri (php tampilan, php fungsi, css, js)
2. **API terpisah** — semua logika data (ambil/kirim ke DB) hanya ada di `api/controllers/`, tidak di file pages
3. **Layout terpusat** — header, navbar, sidebar, footer ada di `layouts/` dan di-include oleh setiap halaman
4. **Pages = tampilan saja** — file PHP di pages hanya mengatur struktur HTML dan memanggil layout + fungsi halaman

### Pembagian Tanggung Jawab File di Setiap Modul

Setiap folder fitur di `pages/` memiliki **2 jenis file PHP**:

| File | Peran |
|------|-------|
| `{modul}.php` | **PHP Tampilan** — struktur HTML halaman, include layout, include fungsi |
| `{modul}.func.php` | **PHP Fungsi Halaman** — helper lokal halaman (format data, build query param, dsb) |
| `{modul}.css` | Style khusus modul ini |
| `{modul}.js` | Logic JS: fetch ke API controller, render DOM, event handler |

> **Aturan**: File `{modul}.php` (tampilan) **tidak boleh** query database langsung. Semua data diambil via `fetch()` JS ke `api/controllers/`.

---

## 📁 STRUKTUR FOLDER LENGKAP

```
SIKMA/
├── index.php                          # Entry point → redirect ke login
│
├── config/
│   ├── database.php                   # Koneksi PDO
│   ├── config.php                     # BASE_URL, APP_NAME, QR_INTERVAL, dll
│   └── auth.php                       # requireAuth(), requireRole(), getCurrentUser()
│
├── layouts/                           # Komponen layout global (di-include semua halaman)
│   ├── header.php                     # <head>, meta, CSS global, logo instansi
│   ├── navbar.php                     # Top navigation bar (nama user, role, logout)
│   ├── sidebar.php                    # Sidebar navigasi (menu per role)
│   ├── footer.php                     # Footer instansi, versi app
│   └── main.php                       # Wrapper <main> konten halaman
│
├── api/
│   └── controllers/                   # SERVER PHP — semua logika data ada di sini
│       ├── AuthController.php         # login, logout, cek session
│       ├── QRController.php           # generate & validasi token QR sesaat
│       ├── PindaianController.php     # catat_keluar, catat_masuk, get_status
│       ├── IzinDinasController.php    # ajukan, putuskan, get_list
│       ├── RiwayatController.php      # get_saya, get_all (admin)
│       ├── PegawaiController.php      # CRUD pegawai
│       ├── RekapController.php        # harian, bulanan, export PDF/Excel
│       ├── PengaturanController.php   # jam kerja, unit kerja, interval QR
│       └── UserController.php         # CRUD akun user sistem
│
├── pages/
│   ├── auth/
│   │   ├── login.php                  # Tampilan form login
│   │   ├── login.func.php             # Fungsi: proses redirect post-login per role
│   │   ├── login.css
│   │   └── logout.php                 # Destroy session → redirect login
│   │
│   ├── pegawai/
│   │   ├── dashboard/
│   │   │   ├── dashboard.php          # Tampilan: status, tombol scan, riwayat hari ini
│   │   │   ├── dashboard.func.php     # Fungsi: format durasi, tentukan tombol aktif
│   │   │   ├── dashboard.css
│   │   │   └── dashboard.js           # Fetch status saya, render badge, auto-refresh
│   │   ├── scan/
│   │   │   ├── scan.php               # Tampilan: viewfinder kamera, form keperluan
│   │   │   ├── scan.func.php          # Fungsi: parse hasil QR, tentukan jenis scan
│   │   │   ├── scan.css
│   │   │   └── scan.js                # Aktifkan kamera, decode QR, kirim ke API
│   │   ├── izin-dinas/
│   │   │   ├── izin-dinas.php         # Tampilan: form ajukan + daftar izin
│   │   │   ├── izin-dinas.func.php    # Fungsi: format status badge, validasi tanggal
│   │   │   ├── izin-dinas.css
│   │   │   └── izin-dinas.js          # Fetch list izin, submit form, render status
│   │   └── riwayat/
│   │       ├── riwayat.php            # Tampilan: tabel riwayat + filter
│   │       ├── riwayat.func.php       # Fungsi: format durasi, build filter query
│   │       ├── riwayat.css
│   │       └── riwayat.js             # Fetch riwayat, render tabel, pagination
│   │
│   ├── atasan/
│   │   └── izin-dinas/
│   │       ├── izin-dinas.php         # Tampilan: daftar pengajuan bawahan + tab
│   │       ├── izin-dinas.func.php    # Fungsi: format status, hitung pending
│   │       ├── izin-dinas.css
│   │       └── izin-dinas.js          # Fetch list bawahan, tombol setujui/tolak
│   │
│   ├── lobby/
│   │   └── layar/
│   │       ├── layar.php              # Tampilan: fullscreen QR Keluar (tanpa layout nav)
│   │       ├── layar.func.php         # Fungsi: hitung countdown, format jam
│   │       ├── layar.css              # Fullscreen styling
│   │       └── layar.js               # Polling token QR, render QR code, countdown
│   │
│   ├── pos/
│   │   └── layar/
│   │       ├── layar.php              # Tampilan: split — QR Masuk + daftar pindaian
│   │       ├── layar.func.php         # Fungsi: format daftar, highlight baru
│   │       ├── layar.css              # Split layout styling
│   │       └── layar.js               # Polling QR + polling daftar pindaian hari ini
│   │
│   ├── admin/
│   │   ├── dashboard/
│   │   │   ├── dashboard.php          # Tampilan: stat cards, tabel sedang diluar, chart
│   │   │   ├── dashboard.func.php     # Fungsi: format stat, warna badge status
│   │   │   ├── dashboard.css
│   │   │   └── dashboard.js           # Fetch stats, render chart, auto-refresh 60s
│   │   ├── pegawai/
│   │   │   ├── pegawai.php            # Tampilan: grid daftar pegawai + filter
│   │   │   ├── pegawai.func.php       # Fungsi: build filter, format status
│   │   │   ├── pegawai.css
│   │   │   ├── pegawai.js             # Fetch list, render grid, delete confirm
│   │   │   ├── create.php             # Tampilan: form tambah pegawai
│   │   │   ├── create.func.php        # Fungsi: validasi form, preview foto
│   │   │   ├── edit.php               # Tampilan: form edit pegawai
│   │   │   ├── edit.func.php          # Fungsi: pre-populate form, validasi
│   │   │   ├── form.css               # Style shared form create & edit
│   │   │   └── form.js                # Submit form, upload foto, validasi client
│   │   ├── catatan/
│   │   │   ├── catatan.php            # Tampilan: tabel semua catatan + filter
│   │   │   ├── catatan.func.php       # Fungsi: format status, build filter
│   │   │   ├── catatan.css
│   │   │   ├── catatan.js             # Fetch catatan, tutup manual, delete
│   │   │   ├── edit.php               # Tampilan: form koreksi catatan
│   │   │   ├── edit.func.php
│   │   │   └── edit.js
│   │   ├── rekap/
│   │   │   ├── rekap.php              # Tampilan: tab harian & bulanan + filter
│   │   │   ├── rekap.func.php         # Fungsi: format menit ke jam:menit
│   │   │   ├── rekap.css
│   │   │   └── rekap.js               # Fetch rekap, render tabel, tombol unduh
│   │   └── pengaturan/
│   │       ├── pengaturan.php         # Tampilan: form jam kerja, unit, interval QR
│   │       ├── pengaturan.func.php    # Fungsi: load setting saat ini
│   │       ├── pengaturan.css
│   │       └── pengaturan.js          # Fetch settings, submit update, CRUD unit kerja
│   │
│   └── pimpinan/
│       └── rekap/
│           ├── rekap.php              # Tampilan: rekap unit sendiri (read-only)
│           ├── rekap.func.php         # Fungsi: filter unit otomatis dari session
│           ├── rekap.css
│           └── rekap.js               # Fetch rekap unit, render, tombol unduh
│
├── assets/
│   ├── css/
│   │   ├── main.css                   # Reset, typography, global utility classes
│   │   └── components.css             # Shared components: card, badge, modal, tabel
│   ├── js/
│   │   ├── main.js                    # Global helpers: formatDate, showToast, dll
│   │   └── qr-scanner.js              # Library jsQR / ZXing untuk decode QR via kamera
│   └── img/
│       ├── logo-bpmp.png
│       └── foto-pegawai/              # Upload foto pegawai
│
└── database/
    └── migrations/
        ├── 001_create_users_table.sql
        ├── 002_create_pegawai_table.sql
        ├── 003_create_unit_kerja_table.sql
        ├── 004_create_qr_sesaat_table.sql
        ├── 005_create_izin_dinas_table.sql
        ├── 006_create_pindaian_table.sql
        ├── 007_create_pasangan_keluar_masuk_table.sql
        └── 008_create_activity_log_table.sql
```

---

## 🧩 LAYOUT SISTEM

Semua halaman (kecuali lobby & pos yang fullscreen) menggunakan layout standar:

```
┌─────────────────────────────────────────────┐
│                  HEADER                      │  ← layouts/header.php
│  Logo BPMP | Nama Instansi                  │
├──────────┬──────────────────────────────────┤
│          │           NAVBAR                  │  ← layouts/navbar.php
│          │  Nama User | Role | Logout        │
│ SIDEBAR  ├──────────────────────────────────┤
│          │                                   │
│ Menu     │           MAIN CONTENT            │  ← layouts/main.php
│ navigasi │   (konten dari tiap modul)        │
│ per role │                                   │
│          │                                   │
├──────────┴──────────────────────────────────┤
│                  FOOTER                      │  ← layouts/footer.php
│  BPMP Gorontalo © 2026 | Versi 1.0          │
└─────────────────────────────────────────────┘
```

### Cara Include Layout di Setiap Halaman

Setiap file `{modul}.php` di pages mengikuti pola ini:

```php
<?php
require_once '../../../config/auth.php';
requireRole('pegawai');                      // proteksi akses

require_once 'dashboard.func.php';           // fungsi lokal halaman ini
?>
<?php include '../../../layouts/header.php'; ?>
<?php include '../../../layouts/navbar.php'; ?>
<?php include '../../../layouts/sidebar.php'; ?>
<?php include '../../../layouts/main.php'; ?>  <!-- buka wrapper main -->

    <!-- KONTEN HALAMAN -->
    <div class="pegawai-dashboard-status"> ... </div>

<?php include '../../../layouts/main_end.php'; ?>  <!-- tutup wrapper main -->
<?php include '../../../layouts/footer.php'; ?>
```

### Sidebar Menu per Role

| Role | Menu Sidebar |
|------|-------------|
| Pegawai | Dashboard, Scan QR, Izin Dinas, Riwayat Saya |
| Atasan | Izin Dinas Bawahan |
| Admin | Dashboard, Pegawai, Catatan, Rekap, Pengaturan |
| Pimpinan | Rekap Unit |
| Lobby | *(tidak ada sidebar — fullscreen)* |
| Pos | *(tidak ada sidebar — fullscreen)* |

---

## 📂 MODUL APLIKASI

### 1. 🔐 AUTENTIKASI
**Path**: `/pages/auth/`
**Files**: `login.php`, `login.func.php`, `login.css`, `logout.php`

- Login username + password, semua role pakai halaman yang sama
- `login.func.php` menangani redirect post-login berdasarkan role:
  - `pegawai` → `/pages/pegawai/dashboard/`
  - `atasan` → `/pages/atasan/izin-dinas/`
  - `lobby` → `/pages/lobby/layar/`
  - `pos` → `/pages/pos/layar/`
  - `admin` → `/pages/admin/dashboard/`
  - `pimpinan` → `/pages/pimpinan/rekap/`
- Session timeout 8 jam idle
- Akun lobby & pos dibiarkan login permanen selama jam kerja

---

### 2. 🏠 DASHBOARD PEGAWAI
**Path**: `/pages/pegawai/dashboard/`
**Files**: `dashboard.php`, `dashboard.func.php`, `dashboard.css`, `dashboard.js`

- `dashboard.php` — struktur HTML: badge status, tombol scan, tabel riwayat hari ini
- `dashboard.func.php` — `formatDurasi()`, `tentukanTombolAktif()`, `getLabelStatus()`
- `dashboard.js` — fetch `PindaianController::get_status_saya`, render badge & tombol kondisional, auto-refresh 30 detik

**Konten tampilan**:
- Badge besar: **Di Kantor** (hijau) / **Sedang di Luar** (oranye) + durasi berjalan
- Tombol **Scan QR Keluar** atau **Scan QR Masuk** (kondisional sesuai status)
- Info izin dinas aktif hari ini (jika ada)
- Tabel riwayat 5 pindaian terakhir hari ini

**CSS Classes**: `.pegawai-dashboard-status`, `.pegawai-dashboard-actions`, `.pegawai-dashboard-today`

---

### 3. 📷 SCAN QR (PEGAWAI)
**Path**: `/pages/pegawai/scan/`
**Files**: `scan.php`, `scan.func.php`, `scan.css`, `scan.js`

- `scan.php` — struktur HTML: viewfinder kamera, area hasil scan, form pilih keperluan
- `scan.func.php` — `parseQRToken()`, `tentukanJenisScan()`, `validasiKonteks()`
- `scan.js` — aktifkan kamera via `getUserMedia`, decode QR dengan jsQR, kirim token ke `QRController::validate`, lalu ke `PindaianController::catat_keluar` atau `catat_masuk`

**Alur di halaman ini**:
1. Kamera aktif → pegawai arahkan ke QR di laptop lobby/pos
2. Token terbaca → kirim ke server untuk validasi
3. Jika QR Keluar: tampilkan pilihan keperluan (Dinas / Keperluan Lain)
4. Jika QR Masuk: langsung konfirmasi kembali
5. Tampilkan feedback sukses/gagal

**CSS Classes**: `.scan-viewfinder`, `.scan-result`, `.scan-keperluan-form`

---

### 4. 📋 IZIN DINAS (PEGAWAI)
**Path**: `/pages/pegawai/izin-dinas/`
**Files**: `izin-dinas.php`, `izin-dinas.func.php`, `izin-dinas.css`, `izin-dinas.js`

- `izin-dinas.php` — struktur HTML: form ajukan + tabel daftar izin
- `izin-dinas.func.php` — `formatStatusBadge()`, `validasiTanggalIzin()`
- `izin-dinas.js` — fetch `IzinDinasController::get_list_saya`, submit form ajukan, render status badge

**Konten tampilan**:
- Form: tanggal, perkiraan jam pergi, perkiraan jam kembali, tujuan, keperluan
- Tabel daftar izin: tanggal, tujuan, status (menunggu/disetujui/ditolak), catatan atasan

**CSS Classes**: `.izin-form`, `.izin-list`, `.izin-status-badge`

---

### 5. 📜 RIWAYAT (PEGAWAI)
**Path**: `/pages/pegawai/riwayat/`
**Files**: `riwayat.php`, `riwayat.func.php`, `riwayat.css`, `riwayat.js`

- `riwayat.php` — struktur HTML: filter + tabel riwayat
- `riwayat.func.php` — `formatDurasi()`, `buildFilterQuery()`, `getLabelKeperluan()`
- `riwayat.js` — fetch `RiwayatController::get_saya`, render tabel, pagination, filter

**Konten tampilan**:
- Filter: date range, jenis keperluan
- Tabel: Tanggal, Jam Keluar, Jam Kembali, Durasi, Keperluan, Status
- Pagination 15 per halaman

**CSS Classes**: `.riwayat-table`, `.riwayat-filter`, `.riwayat-status`

---

### 6. ✅ IZIN DINAS (ATASAN)
**Path**: `/pages/atasan/izin-dinas/`
**Files**: `izin-dinas.php`, `izin-dinas.func.php`, `izin-dinas.css`, `izin-dinas.js`

- `izin-dinas.php` — struktur HTML: tab Menunggu/Disetujui/Ditolak + kartu pengajuan
- `izin-dinas.func.php` — `hitungPending()`, `formatKartuIzin()`
- `izin-dinas.js` — fetch `IzinDinasController::get_list_bawahan`, tombol setujui/tolak kirim ke `putuskan`

**CSS Classes**: `.atasan-izin-list`, `.atasan-izin-card`, `.atasan-izin-actions`

---

### 7. 🖥️ LAYAR LOBBY
**Path**: `/pages/lobby/layar/`
**Files**: `layar.php`, `layar.func.php`, `layar.css`, `layar.js`

> Halaman ini **tidak menggunakan layout** (header/navbar/sidebar/footer). Fullscreen murni.

- `layar.php` — struktur HTML fullscreen: area QR besar, countdown, jam, nama instansi
- `layar.func.php` — `hitungCountdown()`, `formatJamSekarang()`
- `layar.js` — polling `QRController::get_current?jenis=keluar` setiap N detik, render QR baru dengan library QRCode.js, update countdown

**Konten tampilan**:
- QR Code besar di tengah
- Countdown "berganti dalam X detik"
- Jam digital real-time
- Nama instansi + label "QR KELUAR"

**CSS Classes**: `.lobby-layar`, `.lobby-qr-container`, `.lobby-timer`, `.lobby-clock`

---

### 8. 🛡️ LAYAR POS SECURITY
**Path**: `/pages/pos/layar/`
**Files**: `layar.php`, `layar.func.php`, `layar.css`, `layar.js`

> Halaman ini **tidak menggunakan layout**. Fullscreen split.

- `layar.php` — struktur HTML split: kiri QR, kanan daftar pindaian
- `layar.func.php` — `formatDaftarPindaian()`, `isEntryBaru()`
- `layar.js` — polling QR Masuk + polling `PindaianController::get_hari_ini_pos` setiap 10 detik, highlight baris baru

**Konten tampilan**:
- Kiri: QR Masuk besar + countdown
- Kanan: tabel pindaian hari ini (Nama, Unit, Jam, Tempat, Jenis, Keperluan), baris baru disorot

**CSS Classes**: `.pos-layar`, `.pos-qr-side`, `.pos-daftar-side`, `.pos-highlight-new`

---

### 9. 📊 DASHBOARD ADMIN
**Path**: `/pages/admin/dashboard/`
**Files**: `dashboard.php`, `dashboard.func.php`, `dashboard.css`, `dashboard.js`

- `dashboard.php` — struktur HTML: 4 stat cards, tabel sedang di luar, grafik bar
- `dashboard.func.php` — `warnaStatCard()`, `formatStatCard()`, `labelStatusBadge()`
- `dashboard.js` — fetch `DashboardController::get_stats_hari_ini` + `get_sedang_diluar` + `get_chart_data`, render Chart.js, auto-refresh 60 detik

**Konten tampilan**:
- Stat cards: Sedang di Luar, Sudah Kembali, Belum Kembali, Total Keluar Hari Ini
- Tabel: nama, unit, jam keluar, keperluan, estimasi kembali, durasi berjalan (badge merah jika terlambat)
- Bar chart: aktivitas keluar per jam 07.00–17.00

**CSS Classes**: `.admin-dashboard-stats`, `.admin-dashboard-table`, `.admin-dashboard-chart`

---

### 10. 👥 MANAJEMEN PEGAWAI (ADMIN)
**Path**: `/pages/admin/pegawai/`
**Files**: `pegawai.php`, `pegawai.func.php`, `pegawai.css`, `pegawai.js`, `create.php`, `create.func.php`, `edit.php`, `edit.func.php`, `form.css`, `form.js`

- `pegawai.php` — daftar pegawai grid + filter + search
- `pegawai.func.php` — `buildFilterQuery()`, `formatStatusBadge()`
- `pegawai.js` — fetch `PegawaiController::get_list`, render grid, delete confirm modal
- `create.php` / `edit.php` — form tambah/edit pegawai
- `create.func.php` / `edit.func.php` — validasi form, pre-populate data edit
- `form.js` — submit form, upload foto preview, validasi client-side

**Data**: NIP, Nama, Jabatan, Unit Kerja, Atasan Langsung, Nomor HP, Foto, Status

**CSS Classes**: `.admin-pegawai-grid`, `.admin-pegawai-card`, `.admin-pegawai-form`

---

### 11. 📝 MANAJEMEN CATATAN (ADMIN)
**Path**: `/pages/admin/catatan/`
**Files**: `catatan.php`, `catatan.func.php`, `catatan.css`, `catatan.js`, `edit.php`, `edit.func.php`, `edit.js`

- `catatan.php` — tabel semua catatan + filter (pegawai, tanggal, status)
- `catatan.func.php` — `formatStatusCatatan()`, `buildFilterCatatan()`
- `catatan.js` — fetch `RiwayatController::get_all`, tutup manual, hapus, render tabel
- `edit.php` — form koreksi jam/keperluan catatan
- `edit.func.php` — pre-populate data catatan yang akan diedit

**Fitur**: tutup manual catatan terbuka, koreksi jam, hapus, tambah manual

**CSS Classes**: `.admin-catatan-table`, `.admin-catatan-filter`

---

### 12. 📈 REKAP (ADMIN)
**Path**: `/pages/admin/rekap/`
**Files**: `rekap.php`, `rekap.func.php`, `rekap.css`, `rekap.js`

- `rekap.php` — struktur HTML: tab Harian/Bulanan + filter + tabel + tombol unduh
- `rekap.func.php` — `formatMenitKeJam()`, `hitungSummary()`, `buildFilterRekap()`
- `rekap.js` — fetch `RekapController::harian` atau `bulanan`, render tabel, trigger export

**Konten tampilan**:
- Tab Harian: pilih tanggal → tabel semua pegawai (keluar, kembali, durasi, keperluan)
- Tab Bulanan: pilih bulan → per pegawai (hari keluar, tanpa izin, total menit, belum kembali)
- Tombol Unduh PDF & Unduh Excel

**CSS Classes**: `.admin-rekap-filter`, `.admin-rekap-table`, `.admin-rekap-summary`

---

### 13. ⚙️ PENGATURAN (ADMIN)
**Path**: `/pages/admin/pengaturan/`
**Files**: `pengaturan.php`, `pengaturan.func.php`, `pengaturan.css`, `pengaturan.js`

- `pengaturan.php` — struktur HTML: section jam kerja, interval QR, unit kerja, profil instansi
- `pengaturan.func.php` — `loadSettingSekarang()`, `formatJamKerja()`
- `pengaturan.js` — fetch `PengaturanController::get_jam_kerja`, submit update, CRUD unit kerja

**Konten tampilan**:
- Jam kerja: jam mulai, jam selesai, jam istirahat mulai & selesai
- Aturan istirahat: toggle hitung/tidak
- Interval QR: input detik (default 30)
- Unit Kerja: tabel CRUD
- Profil instansi: nama, logo, alamat

**CSS Classes**: `.admin-pengaturan-section`, `.admin-pengaturan-form`

---

### 14. 📋 REKAP PIMPINAN
**Path**: `/pages/pimpinan/rekap/`
**Files**: `rekap.php`, `rekap.func.php`, `rekap.css`, `rekap.js`

- `rekap.php` — sama seperti rekap admin tapi unit kerja otomatis dari session pimpinan
- `rekap.func.php` — `getUnitDariSession()`, `formatMenitKeJam()`
- `rekap.js` — fetch rekap dengan filter unit otomatis, render tabel, tombol unduh

**CSS Classes**: `.pimpinan-rekap-filter`, `.pimpinan-rekap-table`

---

## 🗄️ DATABASE STRUCTURE

### `users`
```sql
id, username, password, full_name,
role ENUM('pegawai','atasan','lobby','pos','admin','pimpinan'),
status ENUM('aktif','tidak_aktif'),
pegawai_id INT FK nullable,
created_at, updated_at
```

### `unit_kerja`
```sql
id, nama_unit, kode_unit, pimpinan_id INT FK ke users nullable, created_at
```

### `pegawai`
```sql
id, nip, nama_lengkap, jabatan,
unit_kerja_id INT FK,
atasan_id INT FK ke pegawai nullable,
nomor_hp, foto,
status ENUM('aktif','tidak_aktif'),
created_at, updated_at
```

### `qr_sesaat`
```sql
id, token VARCHAR UNIQUE,
jenis ENUM('keluar','masuk'),
expired_at DATETIME,
created_at
```

### `izin_dinas`
```sql
id, pegawai_id FK, atasan_id FK ke users,
tanggal DATE, perkiraan_jam_pergi TIME, perkiraan_jam_kembali TIME,
tujuan VARCHAR, keperluan TEXT,
status ENUM('menunggu','disetujui','ditolak'),
catatan_atasan TEXT nullable,
created_at, updated_at
```

### `pindaian` ⭐
```sql
id, pegawai_id FK,
jenis ENUM('keluar','masuk'),
jam DATETIME, tempat ENUM('lobby','pos'),
keperluan_jenis ENUM('dinas','keperluan_lain') nullable,
izin_dinas_id FK nullable,
pasangan_id FK ke pasangan_keluar_masuk nullable,
created_at
```

### `pasangan_keluar_masuk` ⭐
```sql
id, pegawai_id FK,
pindaian_keluar_id FK, jam_keluar DATETIME,
pindaian_masuk_id FK nullable, jam_kembali DATETIME nullable,
durasi_menit INT nullable,
status ENUM('terbuka','kembali','belum_kembali'),
created_at, updated_at
```

### `activity_log`
```sql
id, user_id FK, action, target_table, target_id,
keterangan, ip_address, created_at
```

---

## 🔄 ALUR UTAMA SISTEM

### Alur Pegawai Keluar:
```
Ponsel → Login → Dashboard
→ Klik "Scan QR Keluar" → scan.php aktifkan kamera
→ Arahkan ke laptop lobby → jsQR decode token
→ scan.js POST token ke QRController::validate
→ Jika valid: tampilkan pilihan keperluan
→ Submit → PindaianController::catat_keluar
→ Nama muncul di layar pos (via polling)
```

### Alur Pegawai Kembali:
```
Ponsel → Dashboard → Klik "Scan QR Masuk"
→ Arahkan ke laptop pos → decode token
→ POST ke QRController::validate
→ Jika valid: PindaianController::catat_masuk
→ Durasi dihitung otomatis → pasangan ditutup
→ Nama & jam kembali muncul di layar pos
```

### Alur QR Berganti:
```
Server generate token baru tiap N detik → simpan qr_sesaat
Laptop lobby/pos: layar.js polling QRController::get_current tiap N detik
→ Render QR baru dengan QRCode.js → reset countdown
Token lama: expired_at terlewati → server tolak
```

### Alur Izin Dinas:
```
Pegawai ajukan izin → IzinDinasController::ajukan → status: menunggu
Atasan login → IzinDinasController::get_list_bawahan → klik Setujui/Tolak
→ IzinDinasController::putuskan → status update
Saat scan keluar: IzinDinasController::get_aktif_hari_ini → tampilkan opsi Dinas
```

---

## 🎨 DESIGN SYSTEM

```css
:root {
    --bg-primary: #FFFFFF;
    --bg-secondary: #F4F6F9;
    --color-primary: #1A5276;      /* Biru pemerintah */
    --color-secondary: #D4AC0D;    /* Emas */
    --color-success: #1E8449;      /* Hijau — Di Kantor / Sudah Kembali */
    --color-danger: #C0392B;       /* Merah — Belum Kembali / Terlambat */
    --color-warning: #D68910;      /* Oranye — Sedang di Luar */
    --color-info: #1A6FA8;         /* Biru muda — Dinas */
    --text-primary: #1A1A1A;
    --text-secondary: #555555;
    --shadow-light: rgba(26, 82, 118, 0.08);
    --shadow-medium: rgba(0, 0, 0, 0.1);
    --radius-sm: 6px;
    --radius-md: 10px;
    --radius-lg: 14px;
}
```

### Status Badge:
- Di Kantor → `--color-success`
- Sedang di Luar → `--color-warning`
- Belum Kembali (terlambat) → `--color-danger`
- Dinas → `--color-info`

### CSS Naming:
- `pegawai-{modul}-{komponen}`
- `atasan-{modul}-{komponen}`
- `admin-{modul}-{komponen}`
- `pimpinan-{modul}-{komponen}`
- `lobby-{komponen}`, `pos-{komponen}`
- Shared: `sikma-{komponen}`

### Responsive:
- Desktop 1200px+ | Laptop 992–1199px | Tablet 768–991px | Mobile <768px
- Halaman scan pegawai: prioritas mobile
- Layar lobby & pos: fullscreen landscape laptop

---

## 🔐 SECURITY

```php
requireAuth();                           // semua halaman
requireRole('admin');
requireRole(['admin','pimpinan']);
requireRole('lobby');
requireRole('pos');
requireRole(['pegawai','atasan','admin']);
```

- Password: `password_hash()` BCRYPT
- SQL injection: PDO prepared statements
- XSS: `htmlspecialchars()` semua output
- CSRF: token di semua form POST
- QR token: UUID v4 + `expired_at`, satu kali pakai
- HTTPS wajib (izin kamera browser)

---

## 🔌 API ENDPOINTS

```
# Auth
POST   AuthController.php?action=login
POST   AuthController.php?action=logout

# QR
GET    QRController.php?action=get_current&jenis=keluar
GET    QRController.php?action=get_current&jenis=masuk
POST   QRController.php?action=validate

# Pindaian
POST   PindaianController.php?action=catat_keluar
POST   PindaianController.php?action=catat_masuk
GET    PindaianController.php?action=get_status_saya
GET    PindaianController.php?action=get_hari_ini_pos

# Izin Dinas
GET    IzinDinasController.php?action=get_list_saya
POST   IzinDinasController.php?action=ajukan
GET    IzinDinasController.php?action=get_list_bawahan
POST   IzinDinasController.php?action=putuskan
GET    IzinDinasController.php?action=get_aktif_hari_ini

# Riwayat
GET    RiwayatController.php?action=get_saya
GET    RiwayatController.php?action=get_all

# Dashboard Admin
GET    DashboardController.php?action=get_stats_hari_ini
GET    DashboardController.php?action=get_sedang_diluar
GET    DashboardController.php?action=get_chart_data

# Pegawai
GET    PegawaiController.php?action=get_list
GET    PegawaiController.php?action=get_detail&id=X
POST   PegawaiController.php?action=create
POST   PegawaiController.php?action=update
DELETE PegawaiController.php?action=delete

# Rekap
GET    RekapController.php?action=harian&tanggal=YYYY-MM-DD
GET    RekapController.php?action=bulanan&bulan=YYYY-MM
GET    RekapController.php?action=export_pdf
GET    RekapController.php?action=export_excel

# Pengaturan
GET    PengaturanController.php?action=get_jam_kerja
POST   PengaturanController.php?action=update_jam_kerja
GET    PengaturanController.php?action=get_unit_kerja
POST   PengaturanController.php?action=crud_unit_kerja

# User
GET    UserController.php?action=get_list
POST   UserController.php?action=create
POST   UserController.php?action=update
DELETE UserController.php?action=delete
POST   UserController.php?action=reset_password
```

---

**Status**: 📝 Design Document
**Version**: 1.2.0
**Last Updated**: 2026
