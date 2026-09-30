# SYSTEM DESIGN - SIKMA
## Sistem Informasi Keluar Masuk BPMP Gorontalo

**Versi**: 2.0.0
**Status**: Draft
**Last Updated**: 2026
**Stack**: Laravel 11 + Blade + Vanilla JS + Laravel Breeze + Laravel Sanctum

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

| Role | Route | Fungsi |
|------|-------|--------|
| **Pegawai** | `/pegawai/*` | Scan QR keluar/masuk, ajukan izin dinas, lihat riwayat pribadi |
| **Atasan Langsung** | `/atasan/*` | Setujui atau tolak pengajuan izin dinas bawahan |
| **Akun Lobby** | `/lobby/*` | Hanya tampilkan QR Keluar yang berganti sendiri |
| **Akun Pos** | `/pos/*` | Tampilkan QR Masuk + daftar pindaian hari ini |
| **Admin Kepegawaian** | `/admin/*` | Kelola akun, jam kerja, rekap seluruh balai |
| **Pimpinan** | `/pimpinan/*` | Lihat rekap unit kerjanya (read-only) |

> Akun Lobby dan Akun Pos **tidak bisa** mengisi, mengubah, atau menghapus data. Keduanya hanya menampilkan QR dan hasil pindaian.

---

## 🏗️ ARSITEKTUR SISTEM

### Prinsip Arsitektur

1. **Laravel MVC** — Controller menangani logika, Blade untuk tampilan, Model untuk data
2. **API terpisah** — semua endpoint data ada di `Api\` controllers, dipanggil via `fetch()` JS
3. **Blade Layout** — layout header/navbar/sidebar/footer pakai Blade `@extends` dan `@section`
4. **Views = tampilan saja** — file Blade hanya mengatur struktur HTML, tidak ada query DB langsung
5. **Middleware per role** — proteksi akses menggunakan Laravel Middleware
6. **Sanctum** — proteksi semua API endpoint dengan token session

### Pembagian Tanggung Jawab

| Lapisan | Lokasi | Peran |
|---------|--------|-------|
| **Routes** | `routes/web.php`, `routes/api.php` | Daftarkan semua URL dan arahkan ke controller |
| **Web Controller** | `app/Http/Controllers/` | Render halaman Blade |
| **API Controller** | `app/Http/Controllers/Api/` | Return JSON untuk fetch() dari JS |
| **Model** | `app/Models/` | Eloquent ORM, relasi antar tabel |
| **Middleware** | `app/Http/Middleware/` | Cek auth + role sebelum masuk halaman/API |
| **Blade View** | `resources/views/` | Tampilan HTML per modul |
| **Migration** | `database/migrations/` | Definisi struktur tabel |

> **Aturan**: Blade view **tidak boleh** query database langsung. Semua data diambil via `fetch()` JS ke API controller.

---

## 📁 STRUKTUR FOLDER LENGKAP

```
SIKMA/
├── app/
│   ├── Http/
│   │   ├── Controllers/
│   │   │   ├── Api/                        # API Controllers (return JSON)
│   │   │   │   ├── QRController.php
│   │   │   │   ├── PindaianController.php
│   │   │   │   ├── IzinDinasController.php
│   │   │   │   ├── RiwayatController.php
│   │   │   │   ├── DashboardController.php
│   │   │   │   ├── PegawaiController.php
│   │   │   │   ├── RekapController.php
│   │   │   │   ├── PengaturanController.php
│   │   │   │   └── UserController.php
│   │   │   ├── Auth/                       # Laravel Breeze Auth Controllers
│   │   │   ├── PegawaiPageController.php   # Render halaman pegawai
│   │   │   ├── AtasanPageController.php    # Render halaman atasan
│   │   │   ├── LobbyPageController.php     # Render layar lobby
│   │   │   ├── PosPageController.php       # Render layar pos
│   │   │   ├── AdminPageController.php     # Render halaman admin
│   │   │   └── PimpinanPageController.php  # Render halaman pimpinan
│   │   └── Middleware/
│   │       └── RoleMiddleware.php          # Cek role user
│   └── Models/
│       ├── User.php
│       ├── Pegawai.php
│       ├── UnitKerja.php
│       ├── QrSesaat.php
│       ├── IzinDinas.php
│       ├── Pindaian.php
│       ├── PasanganKeluarMasuk.php
│       └── ActivityLog.php
├── database/
│   └── migrations/
│       ├── 001_create_users_table.php
│       ├── 002_create_pegawai_table.php
│       ├── 003_create_unit_kerja_table.php
│       ├── 004_create_qr_sesaat_table.php
│       ├── 005_create_izin_dinas_table.php
│       ├── 006_create_pindaian_table.php
│       ├── 007_create_pasangan_keluar_masuk_table.php
│       └── 008_create_activity_log_table.php
├── resources/
│   ├── views/
│   │   ├── layouts/
│   │   │   ├── app.blade.php               # Layout utama (header+navbar+sidebar+footer)
│   │   │   └── fullscreen.blade.php        # Layout fullscreen (lobby & pos)
│   │   ├── components/
│   │   │   ├── sidebar.blade.php
│   │   │   ├── navbar.blade.php
│   │   │   └── badge-status.blade.php
│   │   ├── auth/
│   │   │   └── login.blade.php
│   │   ├── pegawai/
│   │   │   ├── dashboard.blade.php
│   │   │   ├── scan.blade.php
│   │   │   ├── izin-dinas.blade.php
│   │   │   └── riwayat.blade.php
│   │   ├── atasan/
│   │   │   └── izin-dinas.blade.php
│   │   ├── lobby/
│   │   │   └── layar.blade.php
│   │   ├── pos/
│   │   │   └── layar.blade.php
│   │   ├── admin/
│   │   │   ├── dashboard.blade.php
│   │   │   ├── pegawai/
│   │   │   │   ├── index.blade.php
│   │   │   │   ├── create.blade.php
│   │   │   │   └── edit.blade.php
│   │   │   ├── catatan/
│   │   │   │   ├── index.blade.php
│   │   │   │   └── edit.blade.php
│   │   │   ├── rekap.blade.php
│   │   │   └── pengaturan.blade.php
│   │   └── pimpinan/
│   │       └── rekap.blade.php
│   ├── css/
│   │   └── app.css                     # Tailwind v4 + custom color tokens (@theme)
│   └── js/
│       ├── app.js                      # Global helpers: formatDate, showToast
│       └── qr-scanner.js               # Wrapper jsQR untuk decode QR via kamera
├── public/
│   └── img/
│       ├── logo-bpmp.png
│       └── foto-pegawai/               # Upload foto pegawai
├── routes/
│   ├── web.php                         # Route halaman Blade per role
│   └── api.php                         # Route API endpoint (dilindungi Sanctum)
└── vite.config.js                      # Bundler CSS & JS
```

---

## 🧩 LAYOUT SISTEM

Semua halaman (kecuali lobby & pos yang fullscreen) menggunakan layout utama `layouts/app.blade.php`:

```
┌─────────────────────────────────────────────┐
│                  HEADER                      │  ← layouts/app.blade.php
│  Logo BPMP | Nama Instansi                  │
├──────────┬──────────────────────────────────┤
│          │           NAVBAR                  │  ← components/navbar.blade.php
│          │  Nama User | Role | Logout        │
│ SIDEBAR  ├──────────────────────────────────┤
│          │                                   │
│ Menu     │        @yield('content')          │  ← konten dari tiap view
│ per role │                                   │
├──────────┴──────────────────────────────────┤
│                  FOOTER                      │
│  BPMP Gorontalo © 2026 | Versi 1.0          │
└─────────────────────────────────────────────┘
```

### Pola Blade di Setiap View

```blade
{{-- resources/views/pegawai/dashboard.blade.php --}}
@extends('layouts.app')

@section('title', 'Dashboard')

@section('content')
    <div class="pegawai-dashboard-status">
        {{-- konten halaman --}}
    </div>
@endsection

@push('scripts')
    <script src="{{ asset('js/pegawai/dashboard.js') }}"></script>
@endpush
```

### Layout Fullscreen (Lobby & Pos)

```blade
{{-- resources/views/lobby/layar.blade.php --}}
@extends('layouts.fullscreen')

@section('content')
    <div class="lobby-layar">
        {{-- QR fullscreen --}}
    </div>
@endsection
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
**Path**: `resources/views/auth/`
**Controller**: `app/Http/Controllers/Auth/` (Laravel Breeze)

- Login username + password via Laravel Breeze
- Setelah login, redirect per role ditangani di `App\Providers\AppServiceProvider` atau custom `AuthenticatedSessionController`:
  - `pegawai` → `/pegawai/dashboard`
  - `atasan` → `/atasan/izin-dinas`
  - `lobby` → `/lobby/layar`
  - `pos` → `/pos/layar`
  - `admin` → `/admin/dashboard`
  - `pimpinan` → `/pimpinan/rekap`
- Session timeout dikonfigurasi di `config/session.php`
- Akun lobby & pos dibiarkan login permanen selama jam kerja

---

### 2. 🏠 DASHBOARD PEGAWAI
**Route**: `GET /pegawai/dashboard`
**Controller**: `PegawaiPageController@dashboard`
**View**: `resources/views/pegawai/dashboard.blade.php`

- Controller hanya render view, tidak ada query DB
- Semua data diambil via `fetch()` JS ke API

**Konten tampilan**:
- Badge besar: **Di Kantor** (hijau) / **Sedang di Luar** (oranye) + durasi berjalan
- Tombol **Scan QR Keluar** atau **Scan QR Masuk** (kondisional sesuai status)
- Info izin dinas aktif hari ini (jika ada)
- Tabel riwayat 5 pindaian terakhir hari ini
- Auto-refresh status setiap 30 detik

**API yang dipanggil JS**:
- `GET /api/pindaian/status-saya`
- `GET /api/izin-dinas/aktif-hari-ini`

**CSS Classes**: `.pegawai-dashboard-status`, `.pegawai-dashboard-actions`, `.pegawai-dashboard-today`

---

### 3. 📷 SCAN QR (PEGAWAI)
**Route**: `GET /pegawai/scan`
**Controller**: `PegawaiPageController@scan`
**View**: `resources/views/pegawai/scan.blade.php`

- Controller hanya render view
- Semua logika scan ditangani di `resources/js/pegawai/scan.js`

**Alur di halaman ini**:
1. Kamera aktif via `getUserMedia`
2. jsQR decode token dari QR di layar lobby/pos
3. POST token ke `POST /api/qr/validate`
4. Jika QR Keluar: tampilkan pilihan keperluan (Dinas / Keperluan Lain)
5. Jika QR Masuk: langsung konfirmasi kembali
6. Tampilkan feedback sukses/gagal via toast

**API yang dipanggil JS**:
- `POST /api/qr/validate`
- `POST /api/pindaian/catat-keluar`
- `POST /api/pindaian/catat-masuk`

**CSS Classes**: `.scan-viewfinder`, `.scan-result`, `.scan-keperluan-form`

---

### 4. 📋 IZIN DINAS (PEGAWAI)
**Route**: `GET /pegawai/izin-dinas`
**Controller**: `PegawaiPageController@izinDinas`
**View**: `resources/views/pegawai/izin-dinas.blade.php`

- Controller hanya render view
- Semua data diambil dan dikirim via `fetch()` JS ke API

**Konten tampilan**:
- Form ajukan: tanggal, perkiraan jam pergi & kembali, tujuan, keperluan
- Tabel daftar izin: tanggal, tujuan, status badge, catatan atasan
- Validasi tanggal tidak boleh di masa lalu (client-side)

**API yang dipanggil JS**:
- `GET /api/izin-dinas`
- `POST /api/izin-dinas`

**CSS Classes**: `.izin-form`, `.izin-list`, `.izin-status-badge`

---

### 5. 📜 RIWAYAT (PEGAWAI)
**Route**: `GET /pegawai/riwayat`
**Controller**: `PegawaiPageController@riwayat`
**View**: `resources/views/pegawai/riwayat.blade.php`

- Controller hanya render view
- Semua data diambil via `fetch()` JS ke API

**Konten tampilan**:
- Filter: date range, jenis keperluan
- Tabel: Tanggal, Jam Keluar, Jam Kembali, Durasi, Keperluan, Status
- Pagination 15 per halaman

**API yang dipanggil JS**:
- `GET /api/riwayat`

**CSS Classes**: `.riwayat-table`, `.riwayat-filter`, `.riwayat-status`

---

### 6. ✅ IZIN DINAS (ATASAN)
**Route**: `GET /atasan/izin-dinas`
**Controller**: `AtasanPageController@izinDinas`
**View**: `resources/views/atasan/izin-dinas.blade.php`

- Controller hanya render view
- Semua data diambil dan dikirim via `fetch()` JS ke API

**Konten tampilan**:
- Tab: Menunggu / Disetujui / Ditolak
- Kartu pengajuan: nama pegawai, tanggal, tujuan, keperluan
- Tombol Setujui + input catatan opsional
- Tombol Tolak + input alasan wajib
- Badge counter pending di tab

**API yang dipanggil JS**:
- `GET /api/izin-dinas/bawahan`
- `POST /api/izin-dinas/{id}/putuskan`

**CSS Classes**: `.atasan-izin-list`, `.atasan-izin-card`, `.atasan-izin-actions`

---

### 7. 🖥️ LAYAR LOBBY
**Route**: `GET /lobby/layar`
**Controller**: `LobbyPageController@layar`
**View**: `resources/views/lobby/layar.blade.php`

> Halaman ini menggunakan `layouts/fullscreen.blade.php` — tanpa navbar/sidebar/footer.

- Controller hanya render view
- Semua logika polling QR ditangani di JS

**Konten tampilan**:
- QR Code besar di tengah layar
- Countdown "berganti dalam X detik"
- Jam digital real-time
- Label "QR KELUAR" + nama instansi

**API yang dipanggil JS**:
- `GET /api/qr/current?jenis=keluar`

**CSS Classes**: `.lobby-layar`, `.lobby-qr-container`, `.lobby-timer`, `.lobby-clock`

---

### 8. 🛡️ LAYAR POS SECURITY
**Route**: `GET /pos/layar`
**Controller**: `PosPageController@layar`
**View**: `resources/views/pos/layar.blade.php`

> Halaman ini menggunakan `layouts/fullscreen.blade.php` — tanpa navbar/sidebar/footer.

- Controller hanya render view
- Semua logika polling QR + daftar pindaian ditangani di JS

**Konten tampilan**:
- Layout split: kiri QR Masuk besar + countdown, kanan tabel pindaian hari ini
- Tabel: Nama, Unit, Jam, Tempat, Jenis, Keperluan
- Baris baru disorot otomatis beberapa detik
- Polling daftar pindaian setiap 10 detik

**API yang dipanggil JS**:
- `GET /api/qr/current?jenis=masuk`
- `GET /api/pindaian/hari-ini-pos`

**CSS Classes**: `.pos-layar`, `.pos-qr-side`, `.pos-daftar-side`, `.pos-highlight-new`

---

### 9. 📊 DASHBOARD ADMIN
**Route**: `GET /admin/dashboard`
**Controller**: `AdminPageController@dashboard`
**View**: `resources/views/admin/dashboard.blade.php`

- Controller hanya render view
- Semua data diambil via `fetch()` JS ke API

**Konten tampilan**:
- 4 stat cards: Sedang di Luar, Sudah Kembali, Belum Kembali, Total Keluar Hari Ini
- Tabel sedang di luar: nama, unit, jam keluar, keperluan, durasi berjalan
- Badge merah jika durasi melebihi estimasi
- Bar chart aktivitas keluar per jam 07.00–17.00 (Chart.js)
- Auto-refresh 60 detik

**API yang dipanggil JS**:
- `GET /api/dashboard/stats`
- `GET /api/dashboard/sedang-diluar`
- `GET /api/dashboard/chart`

**CSS Classes**: `.admin-dashboard-stats`, `.admin-dashboard-table`, `.admin-dashboard-chart`

---

### 10. 👥 MANAJEMEN PEGAWAI (ADMIN)
**Route**: `GET /admin/pegawai`
**Controller**: `AdminPageController@pegawai`, `pegawaiCreate`, `pegawaiEdit`
**View**: `resources/views/admin/pegawai/index.blade.php`, `create.blade.php`, `edit.blade.php`

- Controller hanya render view, data diambil via `fetch()` JS

**Konten tampilan**:
- Grid daftar pegawai: foto, nama, NIP, jabatan, unit kerja, status
- Search & filter: unit kerja, jabatan, status aktif/tidak
- CRUD: Tambah, Edit, Hapus + delete confirmation modal
- Upload foto pegawai dengan preview
- Assign atasan langsung
- Pagination 12 per halaman

**API yang dipanggil JS**:
- `GET /api/pegawai`
- `GET /api/pegawai/{id}`
- `POST /api/pegawai`
- `PUT /api/pegawai/{id}`
- `DELETE /api/pegawai/{id}`

**CSS Classes**: `.admin-pegawai-grid`, `.admin-pegawai-card`, `.admin-pegawai-form`

---

### 11. 📝 MANAJEMEN CATATAN (ADMIN)
**Route**: `GET /admin/catatan`, `GET /admin/catatan/{id}/edit`
**Controller**: `AdminPageController@catatan`, `catatanEdit`
**View**: `resources/views/admin/catatan/index.blade.php`, `edit.blade.php`

- Controller hanya render view, data diambil via `fetch()` JS

**Konten tampilan**:
- Tabel semua pindaian + filter: pegawai, tanggal, status
- Tutup manual catatan yang masih terbuka (belum kembali)
- Koreksi jam keluar/kembali dan keperluan
- Hapus catatan dengan konfirmasi modal
- Pagination 20 per halaman

**API yang dipanggil JS**:
- `GET /api/riwayat/semua`
- `POST /api/pindaian/catat-masuk` (tutup manual)
- `PUT /api/riwayat/{id}`
- `DELETE /api/riwayat/{id}`

**CSS Classes**: `.admin-catatan-table`, `.admin-catatan-filter`

---

### 12. 📈 REKAP (ADMIN)
**Route**: `GET /admin/rekap`
**Controller**: `AdminPageController@rekap`
**View**: `resources/views/admin/rekap.blade.php`

- Controller hanya render view, data diambil via `fetch()` JS

**Konten tampilan**:
- Tab Harian: pilih tanggal → tabel semua pegawai (keluar, kembali, durasi, keperluan)
- Tab Bulanan: pilih bulan → per pegawai (hari keluar, total menit, belum kembali)
- Filter unit kerja
- Tombol Unduh PDF & Unduh Excel

**API yang dipanggil JS**:
- `GET /api/rekap/harian?tanggal=YYYY-MM-DD`
- `GET /api/rekap/bulanan?bulan=YYYY-MM`
- `GET /api/rekap/export-pdf`
- `GET /api/rekap/export-excel`

**CSS Classes**: `.admin-rekap-filter`, `.admin-rekap-table`, `.admin-rekap-summary`

---

### 13. ⚙️ PENGATURAN (ADMIN)
**Route**: `GET /admin/pengaturan`
**Controller**: `AdminPageController@pengaturan`
**View**: `resources/views/admin/pengaturan.blade.php`

- Controller hanya render view, data diambil via `fetch()` JS

**Konten tampilan**:
- Jam kerja: jam mulai, jam selesai, jam istirahat mulai & selesai
- Toggle: hitung/tidak hitung jam istirahat
- Interval QR: input detik (default 30)
- Unit Kerja: tabel CRUD
- Profil instansi: nama, logo, alamat

**API yang dipanggil JS**:
- `GET /api/pengaturan/jam-kerja`
- `PUT /api/pengaturan/jam-kerja`
- `GET /api/pengaturan/unit-kerja`
- `POST /api/pengaturan/unit-kerja`
- `PUT /api/pengaturan/unit-kerja/{id}`
- `DELETE /api/pengaturan/unit-kerja/{id}`

**CSS Classes**: `.admin-pengaturan-section`, `.admin-pengaturan-form`

---

### 14. 📋 REKAP PIMPINAN
**Route**: `GET /pimpinan/rekap`
**Controller**: `PimpinanPageController@rekap`
**View**: `resources/views/pimpinan/rekap.blade.php`

- Controller hanya render view, unit kerja pimpinan dikirim ke view via `compact()`
- Data rekap diambil via `fetch()` JS ke API dengan filter unit otomatis

**Konten tampilan**:
- Sama seperti rekap admin tapi unit kerja otomatis dari session pimpinan
- Read-only: tidak ada tombol edit/hapus
- Tombol Unduh PDF & Unduh Excel tetap tersedia

**API yang dipanggil JS**:
- `GET /api/rekap/harian?tanggal=YYYY-MM-DD&unit_id={id}`
- `GET /api/rekap/bulanan?bulan=YYYY-MM&unit_id={id}`
- `GET /api/rekap/export-pdf`
- `GET /api/rekap/export-excel`

**CSS Classes**: `.pimpinan-rekap-filter`, `.pimpinan-rekap-table`

---

## 🗄️ DATABASE STRUCTURE

### `users`
```php
$table->id();
$table->string('username')->unique();
$table->string('password');
$table->string('full_name');
$table->enum('role', ['pegawai','atasan','lobby','pos','admin','pimpinan']);
$table->enum('status', ['aktif','tidak_aktif'])->default('aktif');
$table->foreignId('pegawai_id')->nullable()->constrained('pegawai');
$table->timestamps();
```

### `unit_kerja`
```php
$table->id();
$table->string('nama_unit');
$table->string('kode_unit')->unique();
$table->foreignId('pimpinan_id')->nullable()->constrained('users');
$table->timestamp('created_at')->useCurrent();
```

### `pegawai`
```php
$table->id();
$table->string('nip')->unique();
$table->string('nama_lengkap');
$table->string('jabatan');
$table->foreignId('unit_kerja_id')->constrained('unit_kerja');
$table->foreignId('atasan_id')->nullable()->constrained('pegawai');
$table->string('nomor_hp')->nullable();
$table->string('foto')->nullable();
$table->enum('status', ['aktif','tidak_aktif'])->default('aktif');
$table->timestamps();
```

### `qr_sesaat`
```php
$table->id();
$table->string('token')->unique();
$table->enum('jenis', ['keluar','masuk']);
$table->dateTime('expired_at');
$table->timestamp('created_at')->useCurrent();
```

### `izin_dinas`
```php
$table->id();
$table->foreignId('pegawai_id')->constrained('pegawai');
$table->foreignId('atasan_id')->constrained('users');
$table->date('tanggal');
$table->time('perkiraan_jam_pergi');
$table->time('perkiraan_jam_kembali');
$table->string('tujuan');
$table->text('keperluan');
$table->enum('status', ['menunggu','disetujui','ditolak'])->default('menunggu');
$table->text('catatan_atasan')->nullable();
$table->timestamps();
```

### `pindaian` ⭐
```php
$table->id();
$table->foreignId('pegawai_id')->constrained('pegawai');
$table->enum('jenis', ['keluar','masuk']);
$table->dateTime('jam');
$table->enum('tempat', ['lobby','pos']);
$table->enum('keperluan_jenis', ['dinas','keperluan_lain'])->nullable();
$table->foreignId('izin_dinas_id')->nullable()->constrained('izin_dinas');
$table->foreignId('pasangan_id')->nullable()->constrained('pasangan_keluar_masuk');
$table->timestamp('created_at')->useCurrent();
```

### `pasangan_keluar_masuk` ⭐
```php
$table->id();
$table->foreignId('pegawai_id')->constrained('pegawai');
$table->foreignId('pindaian_keluar_id')->constrained('pindaian');
$table->dateTime('jam_keluar');
$table->foreignId('pindaian_masuk_id')->nullable()->constrained('pindaian');
$table->dateTime('jam_kembali')->nullable();
$table->integer('durasi_menit')->nullable();
$table->enum('status', ['terbuka','kembali','belum_kembali'])->default('terbuka');
$table->timestamps();
```

### `activity_log`
```php
$table->id();
$table->foreignId('user_id')->constrained('users');
$table->string('action');
$table->string('target_table');
$table->unsignedBigInteger('target_id')->nullable();
$table->text('keterangan')->nullable();
$table->string('ip_address')->nullable();
$table->timestamp('created_at')->useCurrent();
```

---

## 🔄 ALUR UTAMA SISTEM

### Alur Pegawai Keluar:
```
Ponsel → Login (Laravel Breeze) → redirect /pegawai/dashboard
→ Klik "Scan QR Keluar" → GET /pegawai/scan
→ JS aktifkan kamera via getUserMedia
→ jsQR decode token dari layar lobby
→ POST /api/qr/validate (Sanctum)
→ Jika valid: tampilkan pilihan keperluan
→ POST /api/pindaian/catat-keluar
→ Nama muncul di layar pos (via polling)
```

### Alur Pegawai Kembali:
```
Ponsel → GET /pegawai/dashboard → Klik "Scan QR Masuk"
→ GET /pegawai/scan
→ jsQR decode token dari layar pos
→ POST /api/qr/validate (Sanctum)
→ Jika valid: POST /api/pindaian/catat-masuk
→ Durasi dihitung otomatis → pasangan ditutup
→ Nama & jam kembali muncul di layar pos
```

### Alur QR Berganti:
```
Laravel Scheduler generate token baru tiap N detik → simpan ke qr_sesaat
Laptop lobby/pos: JS polling GET /api/qr/current tiap N detik
→ Render QR baru dengan QRCode.js → reset countdown
Token lama: expired_at terlewati → server tolak dengan 422
```

### Alur Izin Dinas:
```
Pegawai POST /api/izin-dinas → status: menunggu
Atasan GET /api/izin-dinas/bawahan → klik Setujui/Tolak
→ POST /api/izin-dinas/{id}/putuskan → status update
Saat scan keluar: GET /api/izin-dinas/aktif-hari-ini → tampilkan opsi Dinas
```

---

## 🎨 DESIGN SYSTEM

**CSS Framework**: Tailwind CSS v4 via `@tailwindcss/vite`

Custom color tokens didefinisikan di `resources/css/app.css` menggunakan `@theme`:

```css
@theme {
    /* Primary */
    --color-primary:  #0a2e5c;   /* Biru Gelap — sidebar, heading, teks utama */
    --color-brand:    #0073e6;   /* Biru Cerah — tombol utama, link aktif */

    /* Accent */
    --color-accent:   #ff9f1c;   /* Kuning/Oranye — highlight, badge */
    --color-sky:      #5cc2f2;   /* Biru Muda — card border, grafis */

    /* Background */
    --color-soft:     #dbeeff;   /* Biru Soft — section bg, hero */
    --color-canvas:   #ffffff;   /* Putih — background utama */
    --color-bg:       #f0f7ff;   /* Page background — soft blue tint */

    /* Semantic (status) */
    --color-success:  #16a34a;   /* Hijau — Di Kantor / Disetujui */
    --color-danger:   #dc2626;   /* Merah — Belum Kembali / Ditolak */
    --color-warning:  #ff9f1c;   /* Oranye — Sedang di Luar / Menunggu */
    --color-info:     #0073e6;   /* Biru — Dinas */

    /* Text */
    --color-text:     #0a2e5c;
    --color-muted:    #64748b;
}
```

### Token Tailwind yang tersedia:
| Token | Hex | Penggunaan |
|-------|-----|------------|
| `bg-primary` / `text-primary` | `#0a2e5c` | Sidebar, header, heading |
| `bg-brand` / `text-brand` | `#0073e6` | Tombol utama, link aktif, badge role |
| `bg-accent` / `text-accent` | `#ff9f1c` | Highlight, badge warning |
| `bg-sky` / `text-sky` | `#5cc2f2` | Border card, elemen grafis |
| `bg-soft` | `#dbeeff` | Section background, hero |
| `bg-canvas` | `#ffffff` | Background card, navbar, footer |
| `bg-bg` | `#f0f7ff` | Background halaman |
| `text-muted` | `#64748b` | Teks sekunder |

### Status Badge (`components/badge-status.blade.php`):
- Di Kantor → `bg-green-100 text-success border-green-200`
- Sedang di Luar → `bg-orange-100 text-warning border-orange-200`
- Belum Kembali → `bg-red-100 text-danger border-red-200`
- Dinas → `bg-soft text-brand border-sky/40`
- Menunggu → `bg-orange-100 text-warning border-orange-200`
- Disetujui → `bg-green-100 text-success border-green-200`
- Ditolak → `bg-red-100 text-danger border-red-200`

### Styling Approach:
- Semua styling menggunakan **Tailwind utility classes** langsung di Blade
- Tidak ada custom CSS class naming (tidak pakai BEM)
- Sidebar menggunakan CSS gradient inline: `linear-gradient(180deg, #0a2e5c 0%, #0d3a73 100%)`
- Komponen reusable dibuat sebagai Blade component di `resources/views/components/`

### Responsive:
- Tailwind breakpoints: `sm` (640px), `md` (768px), `lg` (1024px), `xl` (1280px)
- Halaman scan pegawai: prioritas mobile (`sm:` prefix)
- Layar lobby & pos: fullscreen landscape laptop (`lg:` prefix)

---

## 🔐 SECURITY

### Middleware

```php
// routes/web.php
Route::middleware(['auth', 'role:pegawai'])->group(function () { ... });
Route::middleware(['auth', 'role:admin'])->group(function () { ... });
Route::middleware(['auth', 'role:atasan'])->group(function () { ... });
Route::middleware(['auth', 'role:lobby'])->group(function () { ... });
Route::middleware(['auth', 'role:pos'])->group(function () { ... });
Route::middleware(['auth', 'role:pimpinan'])->group(function () { ... });

// routes/api.php
Route::middleware(['auth:sanctum', 'role:pegawai'])->group(function () { ... });
Route::middleware(['auth:sanctum', 'role:admin'])->group(function () { ... });
```

### Checklist Keamanan

- Password: `Hash::make()` bcrypt via Laravel
- SQL injection: Eloquent ORM + query builder (prepared statements otomatis)
- XSS: Blade `{{ }}` auto-escape, `{!! !!}` hanya jika benar-benar perlu
- CSRF: `@csrf` di semua form Blade, otomatis divalidasi Laravel
- API auth: Laravel Sanctum (session-based untuk SPA)
- QR token: UUID v4 + `expired_at`, satu kali pakai, hapus setelah divalidasi
- HTTPS wajib di production (izin kamera browser)
- Rate limiting: `throttle:60,1` pada route API

---

## 🔌 API ENDPOINTS

Semua route API didaftarkan di `routes/api.php`, dilindungi `auth:sanctum`.

```
# Auth (routes/web.php — pakai Laravel Breeze)
POST   /login
POST   /logout

# QR
GET    /api/qr/current?jenis=keluar
GET    /api/qr/current?jenis=masuk
POST   /api/qr/validate

# Pindaian
POST   /api/pindaian/catat-keluar
POST   /api/pindaian/catat-masuk
GET    /api/pindaian/status-saya
GET    /api/pindaian/hari-ini-pos

# Izin Dinas
GET    /api/izin-dinas
POST   /api/izin-dinas
GET    /api/izin-dinas/bawahan
POST   /api/izin-dinas/{id}/putuskan
GET    /api/izin-dinas/aktif-hari-ini

# Riwayat
GET    /api/riwayat
GET    /api/riwayat/semua

# Dashboard Admin
GET    /api/dashboard/stats
GET    /api/dashboard/sedang-diluar
GET    /api/dashboard/chart

# Pegawai
GET    /api/pegawai
GET    /api/pegawai/{id}
POST   /api/pegawai
PUT    /api/pegawai/{id}
DELETE /api/pegawai/{id}

# Rekap
GET    /api/rekap/harian?tanggal=YYYY-MM-DD
GET    /api/rekap/bulanan?bulan=YYYY-MM
GET    /api/rekap/export-pdf
GET    /api/rekap/export-excel

# Pengaturan
GET    /api/pengaturan/jam-kerja
PUT    /api/pengaturan/jam-kerja
GET    /api/pengaturan/unit-kerja
POST   /api/pengaturan/unit-kerja
PUT    /api/pengaturan/unit-kerja/{id}
DELETE /api/pengaturan/unit-kerja/{id}

# User
GET    /api/users
POST   /api/users
PUT    /api/users/{id}
DELETE /api/users/{id}
POST   /api/users/{id}/reset-password
```

---

**Status**: 📝 Design Document
**Version**: 2.0.0
**Last Updated**: 2026
