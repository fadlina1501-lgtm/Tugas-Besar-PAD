# Revisi Hak Akses Tubes PAD

Paket ini berisi penyesuaian alur dan hak akses sesuai revisi terbaru.

## Ringkasan perubahan

- Login tidak lagi meminta pengguna memilih role. Sistem menentukan role berdasarkan akun yang berhasil login.
- Pengguna Fasilitas: menu Data Aset disembunyikan dan akses URL `/assets` dibatasi. Informasi dan grafik biaya maintenance di dashboard disembunyikan.
- Sarpras: menu Suku Cadang dan akses URL-nya dihapus. Sarpras tetap dapat membuat work order dari tiket, memilih jenis maintenance dan teknisi, serta memberikan instruksi. Sarpras tidak mengisi biaya, status pelaksanaan, atau komponen yang digunakan.
- Teknisi: menu Data Aset dan akses URL-nya dihapus. Menu Suku Cadang tersedia untuk teknisi. Teknisi yang menerima penugasan dapat memperbarui status, memasukkan biaya tenaga kerja aktual, serta mencatat komponen, jumlah, dan harga aktual per unit. Dashboard teknisi tidak menampilkan grafik Biaya Maintenance dan Kondisi Aset.
- Manajemen: menu dan tampilan utama Manajemen dipertahankan.

## Perubahan database

Tidak ada migration baru. Kolom `stock` lama pada tabel `spare_parts` tetap dipertahankan untuk kompatibilitas database, tetapi tidak lagi ditampilkan atau digunakan sebagai persediaan kampus. Jumlah komponen dan harga aktual dicatat pada relasi work order.

Seeder demo mengatur nilai stock komponen menjadi 0 untuk mencerminkan bahwa kampus tidak menyediakan stok suku cadang.

## Berkas keamanan

Paket ini sengaja tidak menyertakan `.env`, `.git`, `vendor`, `node_modules`, database SQLite lokal, maupun cache build. Jangan mengganti `.env` lokal atau konfigurasi rahasia Laravel Cloud saat menyalin revisi.

## Menerapkan revisi ke repository GitHub

1. Ekstrak ZIP.
2. Salin isi folder `tubes-pad-final` ke folder proyek lokal yang sudah terhubung ke GitHub, lalu izinkan file yang sama ditimpa.
3. Pertahankan file `.env` lokal dan folder `.git` yang sudah ada.
4. Jalankan `git status` dan pastikan hanya perubahan source code yang akan dikomit.
5. Jalankan:

   ```bash
   git add .
   git commit -m "Adjust role permissions and maintenance workflow"
   git push origin main
   ```

6. Laravel Cloud akan menjalankan deployment baru dari branch `main`. Pastikan deployment berhasil, kemudian uji akun untuk setiap role.

## Pengujian yang disarankan

- Login per role tanpa dropdown role.
- Pengguna Fasilitas dan Teknisi tidak dapat membuka `/assets` secara langsung.
- Sarpras tidak dapat membuka `/spare-parts` dan tidak dapat mengubah biaya/status work order.
- Teknisi hanya dapat mengelola work order yang ditugaskan kepadanya.
- Teknisi dapat mengelola data komponen, memasukkan biaya tenaga kerja, jumlah komponen, dan harga aktual.
- Dashboard Pengguna Fasilitas tidak menampilkan biaya maintenance.
- Dashboard Teknisi tidak menampilkan biaya maintenance dan kondisi aset.
- Dashboard dan menu Manajemen tetap tersedia seperti sebelumnya.
