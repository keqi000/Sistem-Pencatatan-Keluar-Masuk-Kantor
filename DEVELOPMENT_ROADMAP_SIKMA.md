# DEVELOPMENT ROADMAP - SIKMA
## Tahapan Pengembangan Sistem Informasi Keluar Masuk BPMP Gorontalo

**KONSEP PENGEMBANGAN**:

### 🖥️ BACKEND (PHP + Database)
- **Phase 0**: DATABASE — Migrations semua tabel sesuai system design
- **Phase 0B**: CONFIG & AUTH — Koneksi DB, helper auth, login/logout
- **Phase D**: CONTROLLERS & API — Semua endpoint API controller

### 🎨 FRONTEND (HTML + CSS + JS)
- **Phase A**: PAGES PEGAWAI — Dashboard, Scan QR, Izin Dinas, Riwayat
- **Phase B**: PAGES ATASAN — Approval izin dinas bawahan
- **Phase C**: PAGES LOBBY & POS — Layar QR fullscreen
- **Phase E**: PAGES ADMIN — Dashboard, Pegawai, Catatan, Rekap, Pengaturan
- **Phase F**: PAGES PIMPINAN — Rekap unit (read-only)
- **Phase G**: OPTIMIZATION — Testing, security, polish, export PDF/Excel

---

# 🖥️ BACKEND

---

## 📊 PHASE 0: DATABASE MIGRATIONS
**Priority**: CRITICAL — Execute sebelum semua phase lain
**Status**: ✅ Selesai (9/9 created & migrated)

| Migration | Tabel | Status |
|-----------|-------|--------|
| 001 | users | ✅ Selesai |
| 002 | pegawai | ✅ Selesai |
| 003 | unit_kerja | ✅ Selesai |
| 004 | qr_sesaat | ✅ Selesai |
| 005 | izin_dinas | ✅ Selesai |
| 006 | pindaian | ✅ Selesai |
| 007 | pasangan_keluar_masuk | ✅ Selesai |
| 008 | activity_log | ✅ Selesai |
| 009 | pengaturan | ✅ Selesai |

**Execution Order**:
1. `001_create_users_table.sql` — role ENUM: pegawai, atasan, lobby, pos, admin, pimpinan
2. `002_create_pegawai_table.sql` — NIP, nama, jabatan, unit_kerja_id, atasan_id, foto
3. `003_create_unit_kerja_table.sql` — nama_unit, kode_unit, pimpinan_id
4. `004_create_qr_sesaat_table.sql` — token UUID, jenis ENUM(keluar/masuk), expired_at
5. `005_create_izin_dinas_table.sql` — pegawai_id, atasan_id, tanggal, status ENUM
6. `006_create_pindaian_table.sql` — pegawai_id, jenis, jam, tempat, keperluan_jenis
7. `007_create_pasangan_keluar_masuk_table.sql` — pindaian_keluar_id, pindaian_masuk_id, durasi_menit, status
8. `008_create_activity_log_table.sql` — user_id, action, target_table, ip_address

---

## 🔐 PHASE 0B: CONFIG & AUTENTIKASI
**Priority**: CRITICAL
**Status**: ✅ Selesai (5/5 files created)

```
config/
├── database.php     ✅  koneksi PDO singleton
├── config.php       ✅  BASE_URL, APP_NAME, QR_INTERVAL, SESSION_TIMEOUT
└── auth.php         ✅  requireAuth(), requireRole(), getCurrentUser(), logActivity()

pages/auth/
├── login.func.php   ✅  redirect post-login per role
└── logout.php       ✅  destroy session & activity logging
```

- [ ] Koneksi database PDO + error handling
- [ ] Konstanta global: BASE_URL, APP_NAME, QR_INTERVAL (default 30 detik), SESSION_TIMEOUT (8 jam)
- [ ] `requireAuth()` — redirect ke login jika belum login
- [ ] `requireRole($role)` — support single role dan array role
- [ ] `getCurrentUser()` — return data user dari session
- [ ] `login.func.php`: redirect per role setelah login berhasil:
  - pegawai → `/pages/pegawai/dashboard/`
  - atasan → `/pages/atasan/izin-dinas/`
  - lobby → `/pages/lobby/layar/`
  - pos → `/pages/pos/layar/`
  - admin → `/pages/admin/dashboard/`
  - pimpinan → `/pages/pimpinan/rekap/`
- [ ] Session timeout 8 jam idle
- [ ] Logout: destroy session + redirect login

---

## ⚙️ PHASE D: CONTROLLERS & API
**Priority**: HIGH — Backend API Engine
**Status**: ✅ Selesai (10/10 controllers)

```
api/controllers/
├── AuthController.php          ✅  login, logout, cek session, me
├── QRController.php            ✅  generate & validasi token QR sesaat
├── PindaianController.php      ✅  catat_keluar, catat_masuk, get_status, get_hari_ini_pos, tutup_manual
├── IzinDinasController.php     ✅  ajukan, putuskan, get_list_saya, get_list_bawahan, get_aktif_hari_ini
├── RiwayatController.php       ✅  get_saya, get_all
├── DashboardController.php     ✅  get_stats_hari_ini, get_sedang_diluar, get_chart_data
├── PegawaiController.php       ✅  CRUD pegawai + upload foto
├── RekapController.php         ✅  harian, bulanan, export_pdf, export_excel
├── PengaturanController.php    ✅  jam kerja, unit kerja, interval QR
└── UserController.php          ✅  CRUD akun user sistem, reset password
```

**Priority Build Order**:
1. `AuthController.php` — login (bcrypt verify), logout, session check
2. `QRController.php` — generate token UUID + expired_at, get_current per jenis, validate (cek expired + hapus)
3. `PindaianController.php` — catat_keluar (buat pindaian + pasangan baru), catat_masuk (tutup pasangan + hitung durasi), get_status_saya, get_hari_ini_pos
4. `IzinDinasController.php` — ajukan, putuskan (setujui/tolak), get_list_saya, get_list_bawahan, get_aktif_hari_ini
5. `RiwayatController.php` — get_saya (filter + pagination), get_all (admin)
6. `DashboardController.php` — get_stats_hari_ini, get_sedang_diluar, get_chart_data per jam
7. `PegawaiController.php` — get_list, get_detail, create, update, delete + upload foto
8. `RekapController.php` — harian, bulanan, export_pdf (DomPDF), export_excel (PhpSpreadsheet)
9. `PengaturanController.php` — get/update jam kerja, get/crud unit_kerja, get/update interval QR
10. `UserController.php` — get_list, create, update, delete, reset_password

**Catatan keamanan tiap controller**:
- PDO prepared statements untuk semua query
- `requireAuth()` + `requireRole()` di awal setiap action
- CSRF token divalidasi untuk semua POST/DELETE
- Output di-encode dengan `htmlspecialchars()`

---

# 🎨 FRONTEND

---

## 📱 PHASE A: PAGES PEGAWAI
**Priority**: CRITICAL — Build setelah Phase D (1-3) siap
**Status**: 📋 Planned (0/4 modules)

---

### A0. Shared Assets & Layout
**Status**: ⏳ Planned
```
assets/css/
├── main.css           ⏳  reset, typography, utility classes
└── components.css     ⏳  card, badge, modal, tabel, toast

assets/js/
├── main.js            ⏳  formatDate(), showToast(), global helpers
└── qr-scanner.js      ⏳  wrapper jsQR / ZXing decode via kamera

layouts/
├── header.php         ⏳  <head>, CSS global, logo instansi
├── navbar.php         ⏳  nama user, role, logout
├── sidebar.php        ⏳  menu per role
├── footer.php         ⏳  BPMP Gorontalo © 2026
└── main.php           ⏳  wrapper <main> buka & tutup
```
- [ ] CSS variables design system (warna, radius, shadow)
- [ ] Komponen shared: badge status, modal konfirmasi, toast notifikasi
- [ ] Sidebar menu kondisional per role
- [ ] Responsive grid dasar (desktop/laptop/tablet/mobile)

---

### A1. Dashboard Pegawai
**Status**: ⏳ Planned
```
pages/pegawai/dashboard/
├── dashboard.php       ⏳
├── dashboard.func.php  ⏳  formatDurasi(), tentukanTombolAktif(), getLabelStatus()
├── dashboard.css       ⏳
└── dashboard.js        ⏳  fetch get_status_saya, render badge, auto-refresh 30s
```
- [ ] Badge besar: **Di Kantor** (hijau) / **Sedang di Luar** (oranye) + durasi berjalan
- [ ] Tombol kondisional: **Scan QR Keluar** atau **Scan QR Masuk**
- [ ] Info izin dinas aktif hari ini (jika ada)
- [ ] Tabel 5 pindaian terakhir hari ini
- [ ] Auto-refresh status setiap 30 detik

**API**: `PindaianController::get_status_saya`, `IzinDinasController::get_aktif_hari_ini`

**CSS Classes**: `.pegawai-dashboard-status`, `.pegawai-dashboard-actions`, `.pegawai-dashboard-today`

---

### A2. Scan QR (Pegawai)
**Status**: ⏳ Planned
```
pages/pegawai/scan/
├── scan.php            ⏳
├── scan.func.php       ⏳  parseQRToken(), tentukanJenisScan()
├── scan.css            ⏳
└── scan.js             ⏳  getUserMedia, jsQR decode, POST ke API
```
- [ ] Viewfinder kamera aktif via `getUserMedia`
- [ ] Decode QR realtime dengan jsQR
- [ ] POST token ke `QRController::validate`
- [ ] Jika QR Keluar: tampilkan pilihan keperluan (Dinas / Keperluan Lain)
- [ ] Jika QR Masuk: langsung konfirmasi kembali
- [ ] Feedback sukses/gagal dengan toast

**API**: `QRController::validate`, `PindaianController::catat_keluar`, `PindaianController::catat_masuk`

**CSS Classes**: `.scan-viewfinder`, `.scan-result`, `.scan-keperluan-form`

---

### A3. Izin Dinas (Pegawai)
**Status**: ⏳ Planned
```
pages/pegawai/izin-dinas/
├── izin-dinas.php       ⏳
├── izin-dinas.func.php  ⏳  formatStatusBadge(), validasiTanggalIzin()
├── izin-dinas.css       ⏳
└── izin-dinas.js        ⏳  fetch get_list_saya, submit form ajukan
```
- [ ] Form ajukan: tanggal, perkiraan jam pergi & kembali, tujuan, keperluan
- [ ] Tabel daftar izin: tanggal, tujuan, status badge, catatan atasan
- [ ] Validasi tanggal tidak boleh di masa lalu

**API**: `IzinDinasController::get_list_saya`, `IzinDinasController::ajukan`

**CSS Classes**: `.izin-form`, `.izin-list`, `.izin-status-badge`

---

### A4. Riwayat (Pegawai)
**Status**: ⏳ Planned
```
pages/pegawai/riwayat/
├── riwayat.php          ⏳
├── riwayat.func.php     ⏳  formatDurasi(), buildFilterQuery(), getLabelKeperluan()
├── riwayat.css          ⏳
└── riwayat.js           ⏳  fetch get_saya, render tabel, pagination, filter
```
- [ ] Filter: date range, jenis keperluan
- [ ] Tabel: Tanggal, Jam Keluar, Jam Kembali, Durasi, Keperluan, Status
- [ ] Pagination 15 per halaman

**API**: `RiwayatController::get_saya`

**CSS Classes**: `.riwayat-table`, `.riwayat-filter`, `.riwayat-status`

---

## ✅ PHASE B: PAGES ATASAN
**Priority**: HIGH — Build setelah Phase A selesai
**Status**: 📋 Planned (0/1 module)

---

### B1. Izin Dinas (Atasan)
**Status**: ⏳ Planned
```
pages/atasan/izin-dinas/
├── izin-dinas.php       ⏳
├── izin-dinas.func.php  ⏳  hitungPending(), formatKartuIzin()
├── izin-dinas.css       ⏳
└── izin-dinas.js        ⏳  fetch get_list_bawahan, tombol setujui/tolak
```
- [ ] Tab: Menunggu / Disetujui / Ditolak
- [ ] Kartu pengajuan: nama pegawai, tanggal, tujuan, keperluan
- [ ] Tombol Setujui + input catatan opsional
- [ ] Tombol Tolak + input alasan wajib
- [ ] Badge counter pending di tab

**API**: `IzinDinasController::get_list_bawahan`, `IzinDinasController::putuskan`

**CSS Classes**: `.atasan-izin-list`, `.atasan-izin-card`, `.atasan-izin-actions`

---

## 🖥️ PHASE C: PAGES LOBBY & POS
**Priority**: HIGH — Build setelah Phase D (2) QRController siap
**Status**: 📋 Planned (0/2 modules)

> Kedua halaman ini **fullscreen**, tidak menggunakan layout header/navbar/sidebar/footer.

---

### C1. Layar Lobby
**Status**: ⏳ Planned
```
pages/lobby/layar/
├── layar.php            ⏳
├── layar.func.php       ⏳  hitungCountdown(), formatJamSekarang()
├── layar.css            ⏳  fullscreen styling
└── layar.js             ⏳  polling get_current?jenis=keluar, render QRCode.js, countdown
```
- [ ] QR Code besar di tengah layar
- [ ] Countdown "berganti dalam X detik"
- [ ] Jam digital real-time
- [ ] Label "QR KELUAR" + nama instansi
- [ ] Polling token baru setiap N detik (sesuai QR_INTERVAL)
- [ ] Auto-render QR baru dengan QRCode.js saat token berganti

**API**: `QRController::get_current?jenis=keluar`

**CSS Classes**: `.lobby-layar`, `.lobby-qr-container`, `.lobby-timer`, `.lobby-clock`

---

### C2. Layar Pos Security
**Status**: ⏳ Planned
```
pages/pos/layar/
├── layar.php            ⏳
├── layar.func.php       ⏳  formatDaftarPindaian(), isEntryBaru()
├── layar.css            ⏳  split layout styling
└── layar.js             ⏳  polling QR masuk + polling daftar pindaian, highlight baru
```
- [ ] Layout split: kiri QR Masuk, kanan daftar pindaian hari ini
- [ ] Kiri: QR besar + countdown
- [ ] Kanan: tabel (Nama, Unit, Jam, Tempat, Jenis, Keperluan)
- [ ] Baris baru disorot otomatis selama beberapa detik
- [ ] Polling daftar pindaian setiap 10 detik

**API**: `QRController::get_current?jenis=masuk`, `PindaianController::get_hari_ini_pos`

**CSS Classes**: `.pos-layar`, `.pos-qr-side`, `.pos-daftar-side`, `.pos-highlight-new`

---

## 🔧 PHASE E: PAGES ADMIN
**Priority**: HIGH — Build setelah Phase D (6-7) siap
**Status**: 📋 Planned (0/5 modules)

---

### E1. Dashboard Admin
**Status**: ⏳ Planned
```
pages/admin/dashboard/
├── dashboard.php        ⏳
├── dashboard.func.php   ⏳  warnaStatCard(), formatStatCard(), labelStatusBadge()
├── dashboard.css        ⏳
└── dashboard.js         ⏳  fetch stats + sedang_diluar + chart_data, Chart.js, auto-refresh 60s
```
- [ ] 4 stat cards: Sedang di Luar, Sudah Kembali, Belum Kembali, Total Keluar Hari Ini
- [ ] Tabel sedang di luar: nama, unit, jam keluar, keperluan, durasi berjalan
- [ ] Badge merah jika durasi melebihi estimasi
- [ ] Bar chart aktivitas keluar per jam 07.00–17.00 (Chart.js)
- [ ] Auto-refresh 60 detik

**API**: `DashboardController::get_stats_hari_ini`, `get_sedang_diluar`, `get_chart_data`

**CSS Classes**: `.admin-dashboard-stats`, `.admin-dashboard-table`, `.admin-dashboard-chart`

---

### E2. Manajemen Pegawai (Admin)
**Status**: ⏳ Planned
```
pages/admin/pegawai/
├── pegawai.php          ⏳
├── pegawai.func.php     ⏳  buildFilterQuery(), formatStatusBadge()
├── pegawai.css          ⏳
├── pegawai.js           ⏳  fetch get_list, render grid, delete confirm modal
├── create.php           ⏳
├── create.func.php      ⏳  validasi form, preview foto
├── edit.php             ⏳
├── edit.func.php        ⏳  pre-populate form
├── form.css             ⏳  shared style create & edit
└── form.js              ⏳  submit form, upload foto preview, validasi client
```
- [ ] Grid daftar pegawai: foto, nama, NIP, jabatan, unit kerja, status
- [ ] Search & filter (unit kerja, jabatan, status aktif/tidak)
- [ ] CRUD: Tambah, Edit, Hapus + delete confirmation modal
- [ ] Upload foto pegawai dengan preview
- [ ] Assign atasan langsung
- [ ] Pagination 12 per halaman

**API**: `PegawaiController::get_list`, `get_detail`, `create`, `update`, `delete`

**CSS Classes**: `.admin-pegawai-grid`, `.admin-pegawai-card`, `.admin-pegawai-form`

---

### E3. Manajemen Catatan (Admin)
**Status**: ⏳ Planned
```
pages/admin/catatan/
├── catatan.php          ⏳
├── catatan.func.php     ⏳  formatStatusCatatan(), buildFilterCatatan()
├── catatan.css          ⏳
├── catatan.js           ⏳  fetch get_all, tutup manual, hapus
├── edit.php             ⏳
├── edit.func.php        ⏳  pre-populate data catatan
└── edit.js              ⏳
```
- [ ] Tabel semua pindaian + filter (pegawai, tanggal, status)
- [ ] Tutup manual catatan yang masih terbuka (belum kembali)
- [ ] Koreksi jam keluar/kembali dan keperluan
- [ ] Hapus catatan dengan konfirmasi modal
- [ ] Pagination 20 per halaman

**API**: `RiwayatController::get_all`, `PindaianController::tutup_manual`, `update`, `delete`

**CSS Classes**: `.admin-catatan-table`, `.admin-catatan-filter`

---

### E4. Rekap (Admin)
**Status**: ⏳ Planned
```
pages/admin/rekap/
├── rekap.php            ⏳
├── rekap.func.php       ⏳  formatMenitKeJam(), hitungSummary(), buildFilterRekap()
├── rekap.css            ⏳
└── rekap.js             ⏳  fetch harian/bulanan, render tabel, trigger export
```
- [ ] Tab Harian: pilih tanggal → tabel semua pegawai (keluar, kembali, durasi, keperluan)
- [ ] Tab Bulanan: pilih bulan → per pegawai (hari keluar, total menit, belum kembali)
- [ ] Filter unit kerja
- [ ] Tombol Unduh PDF & Unduh Excel

**API**: `RekapController::harian`, `bulanan`, `export_pdf`, `export_excel`

**CSS Classes**: `.admin-rekap-filter`, `.admin-rekap-table`, `.admin-rekap-summary`

---

### E5. Pengaturan (Admin)
**Status**: ⏳ Planned
```
pages/admin/pengaturan/
├── pengaturan.php       ⏳
├── pengaturan.func.php  ⏳  loadSettingSekarang(), formatJamKerja()
├── pengaturan.css       ⏳
└── pengaturan.js        ⏳  fetch settings, submit update, CRUD unit kerja
```
- [ ] Jam kerja: jam mulai, jam selesai, jam istirahat mulai & selesai
- [ ] Toggle: hitung/tidak hitung jam istirahat
- [ ] Interval QR: input detik (default 30)
- [ ] Unit Kerja: tabel CRUD
- [ ] Profil instansi: nama, logo, alamat

**API**: `PengaturanController::get_jam_kerja`, `update_jam_kerja`, `get_unit_kerja`, `crud_unit_kerja`

**CSS Classes**: `.admin-pengaturan-section`, `.admin-pengaturan-form`

---

## 👔 PHASE F: PAGES PIMPINAN
**Priority**: MEDIUM — Build setelah Phase E selesai
**Status**: 📋 Planned (0/1 module)

---

### F1. Rekap Pimpinan
**Status**: ⏳ Planned
```
pages/pimpinan/rekap/
├── rekap.php            ⏳
├── rekap.func.php       ⏳  getUnitDariSession(), formatMenitKeJam()
├── rekap.css            ⏳
└── rekap.js             ⏳  fetch rekap dengan filter unit otomatis, render, unduh
```
- [ ] Sama seperti rekap admin tapi unit kerja otomatis dari session pimpinan
- [ ] Read-only: tidak ada tombol edit/hapus
- [ ] Tombol Unduh PDF & Unduh Excel tetap tersedia

**API**: `RekapController::harian`, `bulanan`, `export_pdf`, `export_excel`

**CSS Classes**: `.pimpinan-rekap-filter`, `.pimpinan-rekap-table`

---

## 🚀 PHASE G: OPTIMIZATION & POLISH
**Priority**: LOW — BUILD LAST
**Status**: 📋 Planned

- [ ] Export PDF: DomPDF
- [ ] Export Excel: PhpSpreadsheet
- [ ] Database indexing (pegawai_id, jam, status pada tabel pindaian & pasangan)
- [ ] Security audit: SQL injection, XSS, CSRF
- [ ] Input validation & sanitization menyeluruh
- [ ] Error handling & logging ke activity_log
- [ ] Responsive testing: mobile (scan pegawai), landscape laptop (lobby & pos)
- [ ] Browser compatibility: Chrome, Firefox, Edge
- [ ] UI/UX polish & loading states
- [ ] HTTPS enforcement (wajib untuk akses kamera)
- [ ] Dokumentasi kode

---

## 📊 PROGRESS TRACKING

### 🖥️ BACKEND

#### Phase 0: Database Migrations
- [x] 001 - users
- [x] 002 - pegawai
- [x] 003 - unit_kerja
- [x] 004 - qr_sesaat
- [x] 005 - izin_dinas
- [x] 006 - pindaian
- [x] 007 - pasangan_keluar_masuk
- [x] 008 - activity_log
- [x] 009 - pengaturan

**Status**: 100% (9/9)

#### Phase 0B: Config & Autentikasi
- [x] database.php
- [x] config.php
- [x] auth.php
- [x] login.func.php
- [x] logout.php

**Status**: 100% (5/5)

#### Phase D: Controllers & API
- [x] AuthController
- [x] QRController
- [x] PindaianController
- [x] IzinDinasController
- [x] RiwayatController
- [x] DashboardController
- [x] PegawaiController
- [x] RekapController
- [x] PengaturanController
- [x] UserController

**Status**: 100% (10/10)

### 🎨 FRONTEND

#### Phase A: Pages Pegawai
- [ ] A0 - Shared assets & layout
- [ ] A1 - Dashboard Pegawai
- [ ] A2 - Scan QR
- [ ] A3 - Izin Dinas (Pegawai)
- [ ] A4 - Riwayat (Pegawai)

**Status**: 0% (0/5)

#### Phase B: Pages Atasan
- [ ] B1 - Izin Dinas (Atasan)

**Status**: 0% (0/1)

#### Phase C: Pages Lobby & Pos
- [ ] C1 - Layar Lobby
- [ ] C2 - Layar Pos Security

**Status**: 0% (0/2)

#### Phase E: Pages Admin
- [ ] E1 - Dashboard Admin
- [ ] E2 - Manajemen Pegawai
- [ ] E3 - Manajemen Catatan
- [ ] E4 - Rekap
- [ ] E5 - Pengaturan

**Status**: 0% (0/5)

#### Phase F: Pages Pimpinan
- [ ] F1 - Rekap Pimpinan

**Status**: 0% (0/1)

#### Phase G: Optimization
**Status**: Not Started

---

## 🎯 STATUS & NEXT STEPS

### 🖥️ BACKEND:
✅ **Phase 0**: Database Migrations (9 migration files + Seeder data awal) — **SELESAI**
✅ **Phase 0B**: Config (PDO singleton, timezone WITA, Session 8 jam, BASE_URL) & Auth helpers — **SELESAI**
✅ **Phase D**: Seluruh 10 API Controller — **SELESAI & LULUS 29 UNIT TESTS**

### 🎨 FRONTEND — Siap dikerjakan:
1. **Phase A0**: Shared assets, CSS variables, layout components
2. **Phase C**: Layar Lobby (QR Keluar) & Pos Satpam (QR Masuk + Real-time Feed)
3. **Phase A1–A2**: Dashboard Pegawai + Scan QR (kamera via jsQR)
4. **Phase A3–A4**: Izin Dinas + Riwayat Pegawai
5. **Phase B1**: Izin Dinas Atasan (Approval)
6. **Phase E1–E5**: Pages Admin (Dashboard, Pegawai, Catatan, Rekap, Pengaturan)
7. **Phase F1**: Rekap Pimpinan
8. **Phase G**: Optimization & Polish

---

**Last Updated**: 2026
**Backend Progress**: 100% — Siap Terhubung ke Frontend
**Backend Test Suite**: `php tests/test_backend.php` (29 Passed / 0 Failed)
