# Penyesuaian Fungsional Sistem

Penyesuaian ini mengacu pada diagram proses bisnis dan class diagram proyek **Campus Facility Maintenance & Life-Cycle Cost System**.

## 1. Pembagian fungsi berdasarkan aktor

| Aktor | Fungsi yang tersedia di aplikasi |
|---|---|
| Pengguna Fasilitas | Membuat laporan kerusakan, merevisi laporan yang diminta Sarpras, dan melihat status perbaikan atas laporannya sendiri. |
| Sarpras | Memeriksa laporan, memverifikasi laporan atau meminta revisi, membuat work order dari tiket yang telah diverifikasi, serta menetapkan teknisi. |
| Teknisi | Melihat work order yang ditugaskan kepadanya, menerima work order, memulai perbaikan, mencatat progres, mencatat kebutuhan/pemakaian suku cadang dan biaya, lalu menyelesaikan pekerjaan. |
| Manajemen | Melihat dashboard, memantau indikator maintenance dan SLA, serta mengevaluasi kelayakan aset menggunakan informasi Life-Cycle Cost. |

## 2. Alur status laporan dan perbaikan

1. Pengguna mengirim laporan. Sistem membuat `DamageReport` dan `MaintenanceTicket` dengan status `Pending Verification`.
2. Sarpras memeriksa laporan. Laporan valid berubah menjadi `Verified`; laporan yang belum sesuai dikembalikan dengan status `Need Revision`.
3. Pengguna memperbaiki deskripsi dan mengirim ulang laporan dengan status `Revised`.
4. Sarpras membuat work order dari tiket yang telah diverifikasi dan menetapkan teknisi. Work order menunggu penerimaan teknisi (`Awaiting Acceptance`).
5. Teknisi menerima work order. Sistem mengubah status menjadi `Assigned`.
6. Teknisi memulai pekerjaan. Sistem menyimpan waktu mulai dan mengubah status menjadi `In Progress`.
7. Teknisi mencatat progres, biaya tenaga kerja, dan pemakaian suku cadang.
8. Setelah pekerjaan selesai, teknisi mengubah status menjadi `Completed`. Sistem menghitung downtime dan biaya, memeriksa SLA, menyimpan histori maintenance, dan memperbarui kondisi/kinerja aset.
9. Sistem memperbarui ringkasan dashboard dan perhitungan Life-Cycle Cost untuk Manajemen.

## 3. Menu berdasarkan role

| Role | Menu |
|---|---|
| Pengguna Fasilitas | Laporan Kerusakan |
| Sarpras | Laporan Kerusakan, Tiket Maintenance |
| Teknisi | Work Order |
| Manajemen | Dashboard |

## 4. Penyesuaian dari versi sebelumnya

- Dashboard tidak lagi ditampilkan kepada Pengguna Fasilitas, Sarpras, dan Teknisi. Dashboard hanya menjadi ruang kerja Manajemen.
- Pengguna Fasilitas tidak memiliki halaman data aset, harga pembelian, biaya maintenance, downtime, atau Life-Cycle Cost. Pengguna hanya memilih aset/lokasi saat melaporkan kerusakan dan memantau status laporannya.
- Sarpras tidak lagi mengubah status pekerjaan yang menjadi tanggung jawab Teknisi.
- Teknisi harus menerima work order sebelum memulai perbaikan.
- Biaya tenaga kerja dan pemakaian suku cadang dicatat pada proses pekerjaan oleh Teknisi, bukan saat Sarpras membuat work order.
- Halaman terpisah Life-Cycle Cost, master data, manajemen pengguna, dan pengelolaan katalog suku cadang dihilangkan dari menu karena bukan aktivitas aktor yang tercantum pada proses bisnis/class diagram. Data aset, kategori, lokasi, jenis maintenance, dan suku cadang tetap dipakai sebagai data pendukung proses.
- Perhitungan downtime, biaya maintenance, SLA, histori maintenance, dan Life-Cycle Cost tetap berjalan sebagai proses sistem. Hasil ringkasannya ditampilkan pada dashboard Manajemen.

## 5. Batasan perhitungan Life-Cycle Cost

Perhitungan yang digunakan pada aplikasi saat ini:

`Life-Cycle Cost = Harga Perolehan Aset + Total Biaya Maintenance Tercatat`

Downtime dan pelanggaran SLA ditampilkan sebagai indikator kinerja maintenance, bukan ditambahkan ke nominal Life-Cycle Cost.

## 6. Pemetaan operasi pada class diagram ke kode aplikasi

| Class pada diagram | Operasi yang diimplementasikan |
|---|---|
| `User` | `getRole()`; autentikasi `login()` dan `logout()` ditangani `AuthController`. |
| `AssetCategory` | `getCategoryName()`. |
| `Asset` | `getFailureFrequency()`, `getTotalMaintenanceCost()`, `getTotalDowntime()`, dan `calculateLifeCycleCost()`. |
| `DamageReport` | `submit()` dan `validasi()`. |
| `MaintenanceTicket` | `buatDariLaporan()` dan `cekSLA()`. |
| `WorkOrder` | `updateStatus()`, `hitungDowntime()`, dan `hitungTotalCost()`. |
| `SparePartUsage` | `hitungSubtotal()`. |
| `MaintenanceHistory` | `simpan()`. |
| `Dashboard` | `update()`, `tampilkanRingkasan()`, `getRekomendasiKelayakan()`, `filterByKategori()`, dan `filterByLokasi()` dijalankan melalui `DashboardController` untuk ruang kerja Manajemen. |

Catatan: status `Awaiting Acceptance` adalah status transisi sebelum Teknisi menekan **Terima Work Order**. Setelah diterima, sistem mengubah status menjadi `Assigned` sesuai urutan proses bisnis.
