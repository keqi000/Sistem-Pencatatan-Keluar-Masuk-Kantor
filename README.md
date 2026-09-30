# Sistem Pencatatan Keluar Masuk Kantor (SIKMA)
### BPMP Provinsi Gorontalo

Sistem informasi berbasis web untuk pencatatan presensi keluar dan masuk kantor bagi pegawai BPMP Provinsi Gorontalo secara terintegrasi dengan validasi **QR Code Sesaat (Dynamic QR)** dan alur persetujuan **Izin Dinas**.

---

## 🚀 Fitur Utama

1. **Dynamic QR Code (QR Sesaat)**:
   - QR Keluar di Lobby & QR Masuk di Pos Satpam yang berganti otomatis setiap $N$ detik untuk mencegah manipulasi / screenshot QR.
2. **Pencatatan Presensi Terintegrasi**:
   - Perekaman waktu keluar dan kembali pegawai secara otomatis dengan kalkulasi durasi.
   - Peringatan durasi melebihi batas waktu maksimal.
3. **Pengajuan & Persetujuan Izin Dinas**:
   - Pengajuan izin dinas luar oleh pegawai.
   - Validasi dan persetujuan bertingkat oleh Atasan Langsung.
4. **Dashboard & Monitoring**:
   - Dashboard Admin Kepegawaian (status pegawai di luar, statistik harian, chart).
   - Layar monitor Pos Satpam (live feed pindaian hari ini).
5. **Rekapitulasi & Pelaporan**:
   - Laporan harian & bulanan per unit kerja.
   - Fitur ekspor data.

---

## 🛠️ Tech Stack

- **Framework**: Laravel 11 (PHP 8.2+)
- **Database**: SQLite (Development) / MySQL (Production)
- **Authentication**: Laravel Sanctum / Session Auth
- **Testing**: PHPUnit / Pest (`php artisan test`)
- **Documentation**: [API_DOCUMENTATION.md](API_DOCUMENTATION.md) & [SYSTEM_DESIGN_SIKMA.md](SYSTEM_DESIGN_SIKMA.md)

---

## ⚙️ Panduan Menjalankan Proyek

### 1. Clone & Dependencies
```bash
git clone https://github.com/keqi000/Sistem-Pencatatan-Keluar-Masuk-Kantor.git
cd Sistem-Pencatatan-Keluar-Masuk-Kantor
composer install
npm install
```

### 2. Konfigurasi Environment
```bash
cp .env.example .env
php artisan key:generate
```

### 3. Migrasi & Seeder Database
```bash
php artisan migrate --seed
```

Data user default bawaan seeder:
- **Admin**: `admin@bpmp.go.id` / `password`
- **Atasan**: `atasan@bpmp.go.id` / `password`
- **Pegawai**: `pegawai@bpmp.go.id` / `password`
- **Layar Lobby**: `lobby@bpmp.go.id` / `password`
- **Layar Pos**: `pos@bpmp.go.id` / `password`
- **Pimpinan**: `pimpinan@bpmp.go.id` / `password`

### 4. Menjalankan Server & Unit Test
```bash
# Menjalankan Dev Server
php artisan serve

# Menjalankan Test Suite (Backend Validation)
php artisan test
```

---

## 📂 Struktur Proyek
- `app/Http/Controllers/Api/`: Seluruh API Controllers (QR, Pindaian, Izin Dinas, Pegawai, Rekap, dll.)
- `app/Models/`: Model Eloquent relasi database
- `database/migrations/`: Skema tabel database SIKMA
- `database/seeders/`: Data awal seeder unit kerja, pegawai, pengguna & pengaturan
- `routes/api.php`: Route endpoint RESTful API terproteksi Sanctum
- `tests/Feature/SikmaBackendTest.php`: Feature & Integration tests (38 assertions)
