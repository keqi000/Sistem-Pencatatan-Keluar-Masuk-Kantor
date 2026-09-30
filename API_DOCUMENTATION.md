# 📚 DOKUMENTASI API BACKEND LARAVEL - SIKMA
### Sistem Informasi Pencatatan Keluar–Masuk Kantor
**Balai Penjaminan Mutu Pendidikan (BPMP) Provinsi Gorontalo**  
*Kementerian Pendidikan Dasar dan Menengah*

---

## 📌 Ringkasan Umum
- **Framework**: Laravel 12 (PHP 8.2+)
- **Base URL API**: `http://localhost:8000/api/` (ketika menjalankan `php artisan serve`)
- **Format Pertukaran Data**: JSON (`Content-Type: application/json`, `Accept: application/json`)
- **Zona Waktu**: Asia/Makassar (WITA, UTC+8)
- **Autentikasi**: Laravel Sanctum Bearer Token (`Authorization: Bearer <TOKEN>`) atau Session Cookie Laravel.
- **Cara Menjalankan Server Backend**:
  ```bash
  php artisan serve
  ```
- **Cara Migrasi & Seeder Ulang**:
  ```bash
  php artisan migrate:fresh --seed
  ```
- **Menjalankan Pengujian Otomatis**:
  ```bash
  php artisan test
  ```
- **Akun Bawaan (Password: `password123`)**:
  - `admin` (Role: `admin`)
  - `pimpinan` (Role: `pimpinan`)
  - `atasan` (Role: `atasan`)
  - `pegawai` (Role: `pegawai`)
  - `pegawai2` (Role: `pegawai`)
  - `lobby` (Role: `lobby`)
  - `pos` (Role: `pos`)

---

## 📑 Daftar Endpoint API

### 1. Autentikasi (`/api/auth/`)
| Method | Endpoint | Hak Akses | Deskripsi |
|---|---|---|---|
| `POST` | `/api/auth/login` | Publik | Login username & password, mengembalikan Bearer Token & data user |
| `POST` | `/api/auth/logout` | Terproteksi | Logout & revoke token |
| `GET` | `/api/auth/check-session` | Terproteksi | Cek apakah user sedang terautentikasi |
| `GET` | `/api/auth/me` | Terproteksi | Mendapatkan profil user & pegawai saat ini |

#### Contoh Request Login:
```json
POST /api/auth/login
{
  "username": "pegawai",
  "password": "password123"
}
```
#### Contoh Response Login (200 OK):
```json
{
  "success": true,
  "message": "Login berhasil",
  "token": "1|xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx",
  "user": {
    "id": 4,
    "username": "pegawai",
    "full_name": "Andi Saputra, S.Kom.",
    "role": "pegawai",
    "pegawai_id": 3,
    "nip": "198508202010011003",
    "jabatan": "Pranata Komputer Ahli Pertama",
    "unit_kerja_id": 1,
    "nama_unit": "Subbagian Umum"
  },
  "redirect_url": "/pages/pegawai/dashboard/"
}
```

---

### 2. QR Code Dinamis (`/api/qr/`)
| Method | Endpoint | Hak Akses | Deskripsi |
|---|---|---|---|
| `GET` | `/api/qr/current?jenis=keluar` | Publik | Ambil token QR aktif untuk Layar Lobby |
| `GET` | `/api/qr/current?jenis=masuk` | Publik | Ambil token QR aktif untuk Layar Pos Satpam |
| `POST` | `/api/qr/validate` | Publik | Validasi token QR apakah masih aktif dan benar |

#### Contoh Response `GET /api/qr/current?jenis=keluar`:
```json
{
  "success": true,
  "token": "9e5c4a52-9c3f-4229-873b-b6d859ebc210",
  "jenis": "keluar",
  "expired_at": "2026-09-30 11:15:30",
  "remaining_seconds": 29,
  "interval": 30,
  "server_time": "2026-09-30 11:15:01"
}
```
*Frontend polling endpoint ini setiap `interval` detik dan me-render ulang QR code dengan QRCode.js.*

---

### 3. Pindaian Keluar Masuk (`/api/pindaian/`)
| Method | Endpoint | Hak Akses | Deskripsi |
|---|---|---|---|
| `POST` | `/api/pindaian/catat-keluar` | Pegawai | Scan QR Keluar di Lobby |
| `POST` | `/api/pindaian/catat-masuk` | Pegawai | Scan QR Masuk di Pos Satpam |
| `GET` | `/api/pindaian/status-saya` | Pegawai | Status saat ini (`di_kantor` / `sedang_diluar`), durasi & riwayat hari ini |
| `GET` | `/api/pindaian/pos-hari-ini` | Publik / Pos | Feed realtime pindaian hari ini untuk layar pos security |
| `POST` | `/api/pindaian/tutup-manual` | Admin | Tutup manual catatan keluar yang lupa dipindai masuk |
| `PUT` | `/api/pindaian/{id}` | Admin | Koreksi jam keluar/kembali & keperluan catatan |
| `DELETE` | `/api/pindaian/{id}` | Admin | Hapus catatan |

#### Contoh Request `POST /api/pindaian/catat-keluar`:
```json
{
  "token": "9e5c4a52-9c3f-4229-873b-b6d859ebc210",
  "keperluan_jenis": "dinas", // atau "keperluan_lain"
  "izin_dinas_id": 1 // opsional jika dinas
}
```

#### Contoh Request `POST /api/pindaian/catat-masuk`:
```json
{
  "token": "b73a21fe-65aa-43d9-9528-766cf0e81112"
}
```

---

### 4. Izin Dinas (`/api/izin-dinas/`)
| Method | Endpoint | Hak Akses | Deskripsi |
|---|---|---|---|
| `GET` | `/api/izin-dinas/saya` | Pegawai | Daftar pengajuan izin dinas pribadi |
| `POST` | `/api/izin-dinas/ajukan` | Pegawai | Mengajukan izin dinas baru |
| `GET` | `/api/izin-dinas/bawahan` | Atasan, Admin | Daftar pengajuan bawahan untuk persetujuan (include `pending_count`) |
| `POST` | `/api/izin-dinas/putuskan` | Atasan, Admin | Setujui atau tolak izin dinas |
| `GET` | `/api/izin-dinas/aktif-hari-ini` | Pegawai | Izin dinas disetujui untuk hari ini |

#### Contoh Request `POST /api/izin-dinas/ajukan`:
```json
{
  "tanggal": "2026-10-01",
  "perkiraan_jam_pergi": "08:30",
  "perkiraan_jam_kembali": "12:00",
  "tujuan": "Dinas Pendidikan Kab. Bone Bolango",
  "keperluan": "Pendampingan Evaluasi PBD"
}
```

#### Contoh Request `POST /api/izin-dinas/putuskan`:
```json
{
  "izin_id": 1,
  "status": "disetujui", // atau "ditolak"
  "catatan_atasan": "Disetujui. Harap selesaikan laporan pasca dinas."
}
```

---

### 5. Riwayat (`/api/riwayat/`)
| Method | Endpoint | Hak Akses | Deskripsi |
|---|---|---|---|
| `GET` | `/api/riwayat/saya` | Pegawai | Riwayat keluar masuk pegawai login (`start_date`, `end_date`, `status`, `page`, `limit`) |
| `GET` | `/api/riwayat/all` | Admin, Pimpinan, Pos | Seluruh riwayat dengan filter unit kerja, tanggal, nama, dsb. |

---

### 6. Dashboard (`/api/dashboard/`)
| Method | Endpoint | Hak Akses | Deskripsi |
|---|---|---|---|
| `GET` | `/api/dashboard/stats` | Admin, Pimpinan, Atasan, Pos | Stat cards: total keluar, sedang di luar, sudah kembali, belum kembali |
| `GET` | `/api/dashboard/sedang-diluar` | Admin, Pimpinan, Atasan, Pos | Daftar pegawai di luar dengan flag `is_overdue` |
| `GET` | `/api/dashboard/chart` | Admin, Pimpinan, Atasan | Data aktivitas per jam (07:00–17:00) kompatibel dengan Chart.js |

---

### 7. Pegawai (`/api/pegawai/`)
| Method | Endpoint | Hak Akses | Deskripsi |
|---|---|---|---|
| `GET` | `/api/pegawai` | Semua | Daftar pegawai (`search`, `unit_kerja_id`, `status`, `all=1`) |
| `GET` | `/api/pegawai/{id}` | Semua | Detail satu pegawai |
| `POST` | `/api/pegawai` | Admin | Tambah pegawai baru (dukung upload foto `foto`) |
| `POST` | `/api/pegawai/{id}` | Admin | Update data pegawai / ganti foto |
| `DELETE` | `/api/pegawai/{id}` | Admin | Hapus / nonaktifkan pegawai |

---

### 8. Rekapitulasi (`/api/rekap/`)
| Method | Endpoint | Hak Akses | Deskripsi |
|---|---|---|---|
| `GET` | `/api/rekap/harian?tanggal=YYYY-MM-DD` | Admin, Pimpinan | Rekap keluar masuk harian |
| `GET` | `/api/rekap/bulanan?bulan=YYYY-MM` | Admin, Pimpinan | Rekap bulanan per pegawai |
| `GET` | `/api/rekap/export-pdf?tipe=harian` | Admin, Pimpinan | Cetak dokumen resmi kop BPMP Gorontalo |
| `GET` | `/api/rekap/export-excel?tipe=bulanan` | Admin, Pimpinan | Unduh file CSV Excel |

---

### 9. Pengaturan & Unit Kerja (`/api/pengaturan/`)
| Method | Endpoint | Hak Akses | Deskripsi |
|---|---|---|---|
| `GET` | `/api/pengaturan/jam-kerja` | Admin, Pimpinan | Ambil pengaturan jam kerja, toleransi, interval QR, instansi |
| `POST` | `/api/pengaturan/jam-kerja` | Admin | Simpan pengaturan sistem |
| `GET` | `/api/pengaturan/unit-kerja` | Semua | Daftar unit kerja balai |
| `POST` | `/api/pengaturan/unit-kerja` | Admin | CRUD Unit Kerja (`subaction`: `create`, `update`, `delete`) |

---

### 10. Pengguna Sistem (`/api/users/`)
| Method | Endpoint | Hak Akses | Deskripsi |
|---|---|---|---|
| `GET` | `/api/users` | Admin | Daftar akun pengguna sistem |
| `POST` | `/api/users` | Admin | Buat akun user baru |
| `PUT` | `/api/users/{id}` | Admin | Edit akun user |
| `DELETE` | `/api/users/{id}` | Admin | Nonaktifkan akun user |
| `POST` | `/api/users/reset-password` | Admin | Reset kata sandi pengguna |
