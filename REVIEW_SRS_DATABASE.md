# Review SRS dan rancangan database SIM Madrasah

Tanggal review awal: 14 Oktober 2026. Pembaruan status: **20 Oktober 2026**.

Catatan: temuan R-01–R-09 di bagian historis menilai dokumen sebelum revisi v1.2. Status terbaru ada di bagian berikut; pernyataan mengenai keputusan terbuka, stack Inertia, SQLite, atau migration yang belum tersedia pada review awal tidak menggambarkan baseline saat ini. Rujukan nomor baris lama hanya berlaku pada dokumen yang ditinjau saat itu. Nama file laporan dipertahankan untuk menjaga rujukan.

## Status terbaru — 20 Oktober 2026

Dokumen acuan saat ini:

- [SRS revisi v1.2 dengan addendum absensi 20 Oktober 2026](SRS_SIM_Madrasah_MTs_Al_Karimiyyah_2026_2027_Revisi_v1_2.docx).
- [Kamus database kanonik](backend/database/database.MD), termasuk §8.1 untuk foto/GPS dan §21 untuk migration serta bukti pengujian.
- [Migration tambahan foto dan GPS](backend/database/migrations/2026_10_20_000001_add_photo_and_gps_to_employee_attendance.php).

**Penilaian terkini:** cakupan dan struktur utama SRS, kamus database, serta migration sudah selaras. Baseline menggunakan Laravel API + Next.js/React + MySQL 8.4; DEC-01–DEC-19 telah ditetapkan sebagai default implementasi berversi. Kelengkapan struktur database tidak berarti seluruh layanan bisnis, keamanan akses, atau kriteria UAT telah diimplementasikan dan lulus.

### Addendum absensi pegawai

Konfirmasi pemilik proyek membatasi foto + GPS pada **masuk dan pulang pegawai**. Absensi siswa tetap harian oleh guru/wali kelas. Default lainnya tetap, termasuk pemisahan absensi dari realisasi mengajar dan honor, shift, toleransi terlambat, serta approval koreksi.

- Setiap aksi normal masuk/pulang memerlukan foto kamera baru dan lokasi perangkat. Biometrik/pengenalan wajah tetap nonaktif; geofence/radius tidak diberlakukan pada baseline ini.
- Default kompresi server: WebP, sisi terpanjang maksimal 1.280 px tanpa memperbesar, kualitas awal 80 sampai minimum 60, dan hasil maksimal 300 KiB. Orientasi diperbaiki dan EXIF dihapus; input maksimal 10 MiB/20 megapiksel. Kegagalan kompresi menolak aksi tanpa absensi parsial.
- Default GPS: akurasi maksimal 100 meter; usia foto/lokasi maksimal 60 detik saat diterima dan bukan dari masa depan. Jam absensi sah memakai waktu server.
- Foto hasil kompresi disimpan di storage privat. Database menyimpan referensi file dan metadata, bukan byte foto/BLOB/base64. Bukti asli dipertahankan saat koreksi; akses dibatasi ke pemilik dan petugas absensi bermandat.
- `employee_attendance_captures` menyimpan satu bukti per absensi–aksi, koordinat, akurasi, waktu pengambilan, metadata kompresi, actor, operation key, dan snapshot policy. Migration menambahkan 3 FK dan 7 CHECK.
- DEC-16 dan policy absensi memakai versi efektif baru. Flag SQL pada policy lama tetap 0/0 agar histori tidak diwajibkan bukti retroaktif; setup policy addendum baru wajib mengisi 1/1. Tanggal berlaku operasional diisi saat onboarding.

SRS memperbarui FR-05-01/02, NFR-05, DEC-16, AC-06/32 serta menambahkan FR-05-04–06, BR-24/25, dan AC-33–35. Perbandingan isi memastikan 18 DEC lainnya, kebutuhan absensi siswa, dan kebutuhan honor tetap sama.

### Tindak lanjut temuan review awal

| Temuan | Status pada baseline saat ini |
| --- | --- |
| R-01: kategori pegawai historis | Ditangani dalam desain dan struktur `employee_category_assignments` serta snapshot sumber honor. Pemilihan kategori sesuai tanggal tetap perlu layanan dan tes bisnis. |
| R-02: koreksi penerima honor | Alur batch debit/kredit, referensi asal, dan struktur adjustment sudah ditetapkan. Approval/apply atomik serta pengajuan paralel belum dibuktikan pada layanan bisnis. |
| R-03: snapshot laporan | `report_dataset_rows` dan ekstraksi melalui consistent snapshot pada satu koneksi sudah dirancang. Worker dan uji perubahan data selama ekstraksi masih diperlukan. |
| R-04: tingkat kelas historis | `class_periods.grade_snapshot` tersedia dalam kamus dan migration. Laporan harus memakai snapshot periode. |
| R-05: pembulatan honor | Floor JP tiga desimal dan half-up rupiah per sumber sudah ditetapkan; total menjumlah hasil sumber. Implementasi kalkulator dan uji batas tetap diperlukan. |
| R-06: status arsitektur SRS | Baseline ditetapkan sebagai Laravel API + Next.js/React + MySQL, dengan kontrak autentikasi dan API dalam dokumen. |
| R-07: perbedaan stack repository | Pilihan Next.js telah diselaraskan dengan SRS; tidak perlu mengalihkan frontend ke Inertia. Implementasi autentikasi/API sesuai baseline masih merupakan pekerjaan berikutnya. |
| R-08: tipe `jobs.attempts` | Kamus saat ini sudah memakai U16 sesuai migration infrastruktur. |
| R-09: lokasi kamus database | Sumber kanonik ada di `backend/database/database.MD`. Path lama `backend/datatabase/database.MD` dan catatan `docs/REVISI_SRS_DATABASE_v1_2.md` tidak tersedia pada pemeriksaan ini; gunakan tautan acuan yang ada di bagian atas laporan. |

### Bukti dan batas verifikasi

Hasil sesi pengembangan 20 Oktober 2026: 46 tes lulus pada MySQL 9.5.0 terisolasi, termasuk 23 tes baru metadata bukti/policy foto-GPS. Uji lifecycle tambahan juga lulus untuk upgrade dengan policy/absensi lama, rollback addendum, dan migrasi ulang; baris absensi dan flag policy lama dipertahankan. Skema akhir terverifikasi memiliki 120 tabel domain, 373 FK, dan 320 CHECK. Pint serta pemeriksaan sintaks PHP lulus. SRS berhasil dirender dan bagian revisi ditinjau.

Target produksi tetap MySQL 8.4; hasil pada 9.5.0 tidak menggantikan verifikasi versi produksi. Kamera/GPS browser, kompresi byte gambar, otorisasi API, cleanup storage, alur bisnis lintas modul, dan UAT belum dibuktikan oleh tes migration. Seeder/factory akun masih perlu disesuaikan dengan skema identitas baru dan seed baseline operasional belum tersedia. Database operasional tidak dimigrasikan dalam pekerjaan tersebut. Pembaruan laporan ini memeriksa dokumen/kode dan mencatat hasil sesi sebelumnya, tanpa menjalankan ulang suite pengujian.

## Review historis — 14 Oktober 2026

Bagian berikut dipertahankan sebagai rekaman penilaian awal; status tindak lanjutnya mengikuti tabel terbaru di atas.

Tanggal: 14 Oktober 2026.

Dokumen yang ditinjau:

- `SRS_SIM_Madrasah_MTs_Al_Karimiyyah_2026_2027_Revisi_v1_1.docx`.
- `backend/datatabase/database.MD` (636 baris).

Pemeriksaan tambahan: manifest dependensi, konfigurasi database default, dan migration infrastruktur repository. Isi dokumen diperlakukan sebagai bahan review. Pemeriksaan ini tidak mengesahkan kebijakan bisnis, tidak menerapkan skema, dan tidak menjalankan pengujian migration domain. Dokumen sumber tidak diubah.

## Penilaian

Kedua dokumen secara umum selaras pada cakupan 15 modul, batas fitur, riwayat akademik, pemisahan absensi dan realisasi, snapshot honor, paket soal final, serta akses BK/wali. Rancangan sudah menggunakan banyak praktik desain yang tepat. Status yang layak adalah **draf desain yang dapat dilanjutkan setelah klarifikasi**, belum skema final yang sudah terbukti berjalan.

Register DEC-01–DEC-19 dan lembar validasi SRS masih terbuka. Hal tersebut memang dinyatakan oleh dokumen; bukan kesalahan teknis. Keputusan yang menjadi prasyarat suatu modul harus selesai sebelum aturan operasional modul itu diaktifkan. Tidak harus menunggu semua keputusan untuk mulai fondasi akun dan master.

## Temuan prioritas

### R-01 — Histori kategori pegawai untuk pemilihan tarif belum dimodelkan

Prioritas: tinggi sebelum honor. Jenis: celah desain historis.

Rujukan: SRS FR-08-01/03, BR-02/11, AC-12; database baris 170, 241–242, 256–258.

`employees.category_id` menyimpan kategori terkini. Tarif dapat berlaku untuk `employee_category_id`, sementara perhitungan memilih tarif berdasarkan tanggal sumber. Belum ada relasi kategori pegawai bertanggal yang memungkinkan sistem mengetahui kategori yang berlaku pada tanggal tersebut.

Contoh: kategori guru berubah pada November, lalu sumber Oktober dihitung pertama kali atau direvisi pada November. Menggunakan kategori terkini berpotensi memilih tarif berbeda. Snapshot payroll melindungi hasil lama yang sudah disimpan, tetapi tidak menyelesaikan pemilihan kategori pada kalkulasi historis baru.

Perbaikan: tambahkan riwayat kategori efektif jika kategori dapat berubah, atau tetapkan kategori sebagai atribut immutable dengan prosedur perubahan khusus. Simpan kategori/scope tarif terpilih dalam snapshot sumber. Tambahkan uji perubahan kategori di tengah periode dan kalkulasi tanggal sebelumnya.

### R-02 — Koreksi penerima honor lintas pegawai belum mempunyai alur eksplisit

Prioritas: tinggi jika koreksi penerima diperbolehkan. Jenis: keputusan dan alur yang belum lengkap.

Rujukan: SRS DEC-03/06, BR-15, FR-08-05; database baris 246, 250, 252, 271, 439.

Identitas payroll tetap terikat ke satu pegawai. Klaim pertemuan unik secara global, dan revisi biasa mengganti versi dalam parent payroll yang sama. Desain belum menjelaskan apa yang dilakukan apabila pertemuan terlanjur disahkan untuk A, sedangkan penerima yang benar adalah B.

Ini bukan alasan menghapus `UK(meeting_id)`: constraint tersebut tepat untuk baseline satu penerima. Yang perlu ditentukan adalah koreksi lintas payroll: apakah dilarang, diselesaikan dengan adjustment debit/kredit yang berpasangan, atau melalui pengalihan klaim formal. Jelaskan referensi asal, saldo bersih, persetujuan, dan perlakuan histori final kedua pegawai. Tambahkan UAT A → B dengan kegagalan di tengah transaksi dan pengajuan paralel.

### R-03 — Snapshot laporan belum memiliki mekanisme implementasi yang dipilih

Prioritas: sedang, sebelum laporan besar. Jenis: rincian implementasi yang belum ditetapkan.

Rujukan: SRS FR-14, AC-19/21; database baris 364, 563.

Dokumen sudah mewajibkan dataset konsisten, tetapi `dataset_cutoff_at` sendiri tidak membekukan nilai baris yang dapat diperbarui. Filter `created_at <= cutoff` pun tidak mencegah perubahan absensi yang dibuat sebelum cutoff. Pagination/chunk yang membaca pada waktu berbeda dapat menghasilkan laporan campuran.

Perbaikan: pilih mekanisme snapshot, misalnya membaca data dalam consistent snapshot transaction pada koneksi yang sama, lalu membuat berkas setelah data diekstrak, atau menyimpan dataset laporan yang telah dibekukan. Tentukan batas volume/waktu dan pembersihannya. Tidak selalu perlu tabel tambahan. Tambahkan uji koreksi absensi ketika job sedang membaca beberapa bagian laporan.

### R-04 — Atribut kelas historis belum sepenuhnya dilindungi

Prioritas: sedang. Jenis: celah desain bersyarat pada perubahan master.

Rujukan: SRS BR-02 dan AC-03; database baris 184–185.

`class_periods.display_name` sudah melindungi nama historis, tetapi tingkat kelas hanya berada pada `classes.grade`. Jika master kelas boleh berganti tingkat, laporan lama yang join ke `classes` akan mengikuti tingkat terbaru.

Perbaikan: tetapkan grade identitas kelas tidak dapat diubah setelah digunakan, atau simpan `grade_snapshot` pada `class_periods`. Tidak perlu menduplikasi semua atribut master; lindungi atribut yang benar-benar digunakan laporan historis.

### R-05 — Presisi uang sudah tepat, tetapi tahap pembulatan perlu ditetapkan

Prioritas: sedang sebelum honor. Jenis: keputusan perhitungan, terkait DEC-02/05.

Rujukan: SRS BR-16; database baris 239, 248–249, 260.

`DECIMAL(19,2)` tepat untuk nominal dan JP menggunakan DECIMAL. Dokumen mengharuskan jumlah amount sumber sama dengan amount line. Jika pembulatan dilakukan per sumber dan per line dengan aturan berbeda, hasil bisa berbeda. Dua nilai mentah 0,005 dapat menjadi 0,02 jika masing-masing dibulatkan ke dua desimal, atau 0,01 jika dijumlahkan terlebih dahulu.

Perbaikan: tetapkan presisi hitung antara, tahap pembulatan, aturan pembulatan ke rupiah bila dipakai, serta pembagian selisih pembulatan ke sumber. Tambahkan contoh prorata dan banyak sumber kecil. Jangan menjadikan angka contoh tersebut kebijakan resmi.

### R-06 — SRS menyebut status arsitektur secara tidak konsisten

Prioritas: sedang untuk konsistensi dokumen. Jenis: ketidakkonsistenan redaksional.

Rujukan: SRS §1.3 dibandingkan Bab VIII dan DEC-13.

§1.3 menyebut Bab VIII sebagai rekomendasi desain, sementara Bab VIII dan DEC-13 menyatakan Laravel + Inertia + React/TypeScript + MySQL telah ditetapkan.

Perbaikan: ubah §1.3 agar menyebut stack sudah ditetapkan; versi, SSR, dan detail implementasi tetap mengikuti DEC-13. Ini memperjelas mana yang dapat dipilih ulang dan mana yang sudah menjadi baseline.

### R-07 — Repository belum mengikuti baseline stack SRS

Prioritas: tinggi sebelum melanjutkan UI. Jenis: perbedaan implementasi tambahan, bukan ketidaksesuaian dua dokumen.

Rujukan: SRS §8.1–8.3; `frontend/package.json`, `backend/composer.json`, `backend/package.json`, `backend/config/database.php:20`, `backend/.env.example:23`.

Frontend saat ini merupakan aplikasi Next.js. Manifest backend yang diperiksa belum mencantumkan dependensi Inertia. Default koneksi masih SQLite. Database.MD sendiri sudah mengakui SQLite dan menyatakan desain MySQL belum diterapkan, sehingga bagian database ini transparan, bukan klaim keliru.

Perbaikan: jika SRS tetap menjadi baseline, siapkan integrasi Laravel–Inertia–React dan lingkungan MySQL sesuai versi yang dipilih. Jika Next.js memang merupakan keputusan baru, ubah SRS/ADR dan jelaskan autentikasi, API, deployment, serta kontrak data yang berubah. Jangan melanjutkan dua arsitektur tanpa keputusan yang konsisten.

### R-08 — Dokumentasi tipe attempts antrean berbeda dari migration aktual

Prioritas: rendah. Jenis: kesalahan faktual kamus data.

Rujukan: database baris 398; `backend/database/migrations/0001_01_01_000002_create_jobs_table.php`.

Kamus menulis `jobs.attempts U8`. Migration memakai `unsignedSmallInteger('attempts')`, yaitu U16 menurut notasi dokumen. Perbaiki dokumentasinya menjadi U16; tidak perlu mengubah migration framework.

### R-09 — Lokasi dokumentasi database berpotensi membingungkan

Prioritas: rendah. Jenis: pengelolaan dokumen.

`backend/datatabase/database.MD` berisi rancangan, sedangkan `backend/database/database.MD` berisi satu baris kosong. Lokasi pertama memang mengikuti permintaan sebelumnya yang tercatat dalam dokumen; ini bukan kerusakan skema.

Perbaikan: pilih satu sumber kanonik. Jika lokasi sekarang dipertahankan, isi lokasi kedua dengan tautan yang jelas. Jika dipindahkan, perbarui seluruh rujukan. Jangan memelihara dua salinan desain yang dapat berbeda.

## Praktik yang sudah tepat

- Identitas orang dipisahkan dari akun dan profil siswa/pegawai/wali; multi-peran tidak menggandakan pegawai.
- Enrollment bertanggal, jadwal berversi, dan pertemuan aktual menjaga sejarah.
- Absensi pegawai, pelaksanaan mengajar, dan verifikasi dipisahkan.
- Unique key, parent locking, transaksi, dan idempotensi dibahas untuk operasi paralel.
- Honor menyimpan sumber, versi aturan, tarif, dan hasil; pengesahan/penguncian atomik serta versi pengganti menjaga audit.
- Paket soal final memisahkan naskah dan kunci serta memetakan ulang opsi final.
- BK internal dipisahkan dari ringkasan berbagi; hubungan wali terverifikasi dan pencabutan diperiksa saat akses.
- File privat, publikasi derivatif, audit minimum, backup data+berkas, dan latihan restore dinyatakan eksplisit.
- Pilihan di luar cakupan seperti SPP, nilai/rapor dan pembayaran tidak ditambahkan diam-diam.

Otorisasi objek pada setiap permintaan dan prinsip menolak akses tanpa izin sejalan dengan [OWASP Authorization Cheat Sheet](https://cheatsheetseries.owasp.org/cheatsheets/Authorization_Cheat_Sheet.html). Pemakaian `FOR UPDATE` dalam transaksi sesuai [dokumentasi locking reads MySQL 8.4](https://dev.mysql.com/doc/refman/8.4/en/innodb-locking-reads.html). Pemisahan validasi overlap/agregat dari CHECK per baris tepat karena CHECK tidak mendukung subquery atau pembacaan tabel lain; perhatikan pula bahwa hasil UNKNOWN akibat NULL dapat lolos CHECK. Rujukan: [MySQL 8.4 CHECK Constraints](https://dev.mysql.com/doc/refman/8.4/en/create-table-check-constraints.html).

## Penyempurnaan sebelum baseline final

1. Turunkan pemetaan FR kelompok menjadi sub-FR → tabel/constraint/layanan → AC/tes. Pemetaan FR-01–FR-15 sudah tersedia, tetapi belum membuktikan semua perilaku sub-FR terimplementasi.
2. Untuk setiap invariansi, tulis penegaknya: FK/UK/CHECK, transaksi layanan, otorisasi, atau prosedur operasional. Rincikan FK komposit yang masih berupa arahan umum di §15.1 ketika migration ditulis.
3. Tentukan prosedur disposal dan legal hold per domain. Desain sudah menyebut keduanya; jangan menganggap FK RESTRICT atau soft delete sebagai kebijakan retensi lengkap.
4. Lengkapi target NFR yang masih subjektif dengan perangkat/profil uji dan ukuran penerimaan. Misalnya pemakaian keyboard/fokus/kontras, kegagalan job, dan interval pemantauan ketersediaan. Target usulan perlu divalidasi pemilik proses.
5. Implementasikan bertahap sesuai dependensi MVP. Banyak tabel versi/riwayat memiliki alasan bisnis; pengurangan tabel harus tetap mempertahankan invariansi, bukan sekadar mengejar jumlah tabel kecil.

## Bukti yang diperlukan untuk menyatakan siap implementasi/operasional

- Keputusan kritis per modul, contoh transaksi nyata yang diizinkan, serta pemilik proses yang memvalidasi.
- Migration berjalan pada MySQL terpilih dengan FK/UK/CHECK benar, termasuk relasi pointer yang siklik.
- Uji paralel untuk mutasi siswa, penerbitan jadwal, realisasi, pengesahan honor, dan penomoran surat.
- Uji akses negatif pada props, pencarian, ekspor dan unduhan; pengujian pencabutan izin.
- Rekonsiliasi honor manual, pergantian tarif/kategori, revisi, dan aturan pembulatan.
- Uji snapshot laporan selama perubahan data, serta restore database dan lampiran.

Tes aplikasi tidak dijalankan dalam review ini karena tidak ada perubahan aplikasi dan migration domain belum tersedia. Penilaian atas konkurensi, integritas, performa dan keamanan adalah penilaian rancangan; hasil eksekusinya masih perlu dibuktikan.
