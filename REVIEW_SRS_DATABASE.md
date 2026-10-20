# Review SRS dan rancangan database SIM Madrasah

Tanggal review awal: 14 Oktober 2026. Pembaruan terakhir: **20 Oktober 2026 — penyelarasan dengan implementasi JWT, Google OAuth, akun dan sidebar**.

Bagian status terbaru menilai kode/dokumen saat pembaruan ini. Temuan R-01–R-09 dalam bagian historis tetap merupakan rekaman sebelum revisi v1.2; pernyataan Inertia, keputusan terbuka, SQLite, migration belum tersedia, dan belum dijalankannya tes pada review awal tidak berlaku sebagai status aplikasi sekarang. Bukti pengujian lama dipisahkan dari audit dokumentasi ini.

## Status terbaru — 20 Oktober 2026

Acuan:

- [SRS revisi v1.2 dengan addendum foto/GPS dan autentikasi JWT/Google](SRS_SIM_Madrasah_MTs_Al_Karimiyyah_2026_2027_Revisi_v1_2.docx).
- [Kamus database kanonik](backend/database/database.MD), terutama §1.3 (kontrak auth), §4.1 (tabel JWT/OAuth), §8.1 (foto/GPS), §21 (migration), dan §22 (status implementasi).
- [API Laravel](backend/routes/api.php), [layanan token](backend/app/Services/JwtTokenService.php), [autentikasi](backend/app/Services/AuthenticationService.php), serta [BFF/cookie frontend](frontend/lib/auth/cookies.ts).
- [Design system](design-system.md) versi 1.3: shadcn sejak awal, penggunaan ulang komponen dan konsistensi seluruh halaman.

**Penilaian:** cakupan 15 modul dan fondasi data tetap konsisten. Kontrak autentikasi dokumen kini mengikuti kode JWT HS256 + Google OAuth opsional melalui BFF Next.js, sesuai permintaan pemilik proyek. DEC-08 diperjelas untuk pemulihan akun yang tersedia dan DEC-13 diperbarui untuk JWT/BFF; 17 keputusan lain, termasuk DEC-16 foto/GPS, dipertahankan. Struktur database, menu placeholder atau tes auth tidak menyatakan layanan bisnis seluruh modul maupun UAT telah selesai.

### Autentikasi yang sudah diimplementasikan

| Bagian | Kontrak aktual dan sumber |
| --- | --- |
| Login | Username/email + password; user/person aktif, siswa-only ditolak. Guard api JWT HS256 memeriksa signature, issuer, audience, subject, sid/jti, waktu dan sesi aktif. `AuthenticationService`, `JwtTokenService`, `config/auth.php`. |
| Durasi dan rotasi | Access default 15 menit; sesi/refresh absolut 120 menit sejak login. Refresh acak 32 byte hanya disimpan sebagai SHA-256, sekali pakai, rotasi JWT/jti+refresh. Rotasi tidak memperpanjang batas sesi dan membuat access lama tidak berlaku. Replay mencabut sesi. `config/jwt.php`, `JwtTokenService`. |
| Google | Socialite dengan state/PKCE, transaksi maksimal 600 detik. Hanya akun lokal aktif/email terverifikasi yang sama dan tidak wajib ganti sandi. Tautan subject dibuat pada login sah pertama; tidak ada registrasi otomatis atau penyimpanan token Google. Callback BFF harus cocok dengan APP_URL, GOOGLE_REDIRECT_URI dan Google Console. |
| Browser/BFF | madrasah_access/madrasah_refresh berupa cookie HttpOnly, host-only, SameSite=Lax dan Secure di production; Max-Age mengikuti sisa refresh, bukan memperpanjang JWT. BFF mengirim bearer ke Laravel; UI tidak menerima JSON token atau menyimpannya di localStorage/sessionStorage. Mutasi BFF memeriksa Origin+application/json dan menolak dengan 403. |
| OAuth/session Laravel | Middleware web/session Laravel hanya untuk transaksi Google. BFF menyimpan cookie transaksi dalam madrasah_google, Path=/api/auth/google, 600 detik, lalu menghapusnya setelah callback. Session tersebut bukan autentikasi API utama. |
| Pencabutan | Logout per sesi dan logout-all API; disable, reset/ganti sandi, perubahan email dan aksi administratif terkait mencabut sesi sesuai kode. Semua akses tetap memeriksa status akun/person. |
| Password sementara | Pembuatan/reset oleh petugas menghasilkan sandi 24 jam sekali login. Sesi hanya dapat profil me, ganti sandi dan logout; domain/refresh/Google diblokir sampai diganti. Ganti sandi memerlukan sandi lama, konfirmasi, minimal 12 karakter besar/kecil+angka dan maksimal 72 byte; berhasil mencabut sesi. |
| Rate limit dan data privat | Login 5/menit per login+IP dan 20/menit per IP; refresh 30/IP, Google 10/IP, password 5/akun. Laravel API dan BFF auth/account memakai private,no-store; data personal Next.js tidak di-cache lintas pengguna. Penerusan IP/trusted proxy pada deployment BFF perlu verifikasi tersendiri. |
| Waktu | config/app.php UTC; DATETIME(6)/claim/expiry internal UTC, penyajian Asia/Jakarta. Perbaikan ini mencegah JWT baru dianggap kedaluwarsa akibat selisih zona waktu. |

JWT_SECRET dan GOOGLE_CLIENT_SECRET tetap pada env backend; GOOGLE_CLIENT_ID adalah identitas publik, bukan private key JWT. HS256 memakai shared secret dan tidak memerlukan pasangan public/private key. Dokumentasi dan `.env.example` hanya menyebut nama variabel/default tanpa menyalin nilai rahasia. Nama variabel lain: JWT_ISSUER/AUDIENCE/ACCESS_TTL/REFRESH_TTL, GOOGLE_AUTH_ENABLED/REDIRECT_URI, API_BASE_URL dan APP_URL BFF.

### Akun awal, role dan navigasi

- `DatabaseSeeder` menjalankan referensi status/jenis soal dan `SuperAdminSeeder`; `AccountAccessSeeder` menyiapkan 10 role termasuk Super Admin serta 10 izin accounts.*.
- `backend/config/accounts.php` membaca INITIAL_ADMIN_EMAIL/NAME/USERNAME/PASSWORD. Akun awal dibuat aktif dengan email terverifikasi. Password env dihash; bila kosong, sandi acak tidak ditampilkan dan Google yang dikonfigurasi menjadi jalur login awal. Mengubah env/seeder tidak mengganti sandi akun lama. Seeder berhenti bila grant Super Admin sudah ada dan menolak benturan identitas; tidak mempromosikan/menimpa akun lama.
- API/UI pengelolaan akun tersedia: daftar/detail/create/update, role accounts.*, status, verifikasi email melalui konfirmasi identitas, reset password akun lain, pencabutan sesi, Google unlink, audit serta lock_version. Tidak ada hard delete atau signup publik. Self-reset melalui aksi admin ditolak; menu ganti sandi meminta sandi lama.
- Perubahan email menghapus tautan OAuth/verifikasi lokal; Google unlink mengosongkan email_verified_at dan mencabut sesi, sehingga tidak langsung tertaut ulang tanpa verifikasi.
- Role Super Admin dilindungi dan tidak memberi bypass BK internal/honor. `NavigationAccess` menggabungkan role aktif serta permission/mandat/grant/relasi yang dikenali; cookie ruang kerja bukan sumber izin. `AuthUserResource.navigation` menentukan menu dan SessionMonitor memuat ulang konteks bila akses berubah.
- Sidebar, navbar, shell, login, akun dan dashboard awal memakai komponen shadcn. Menu domain yang belum memiliki halaman disabled tanpa href. Perincian akses baca guru/wali, konfigurasi mandat/kategori melalui UI, serta otorisasi tindakan/objek domain tetap pekerjaan modul berikutnya; visibilitas menu bukan Policy data.

### Perubahan rancangan fisik auth

Migration [2026_10_20_020000_create_jwt_authentication_tables.php](backend/database/migrations/2026_10_20_020000_create_jwt_authentication_tables.php) menambah auth_sessions, auth_refresh_tokens dan oauth_accounts. Ada 3 FK RESTRICT, unique public_id/token_hash/provider-subject/user-provider dan indeks sesi/expiry; migration ini tidak menambah CHECK. Validasi token, rotasi dan status dijalankan guard/service.

Snapshot baseline+foto/GPS sebelumnya berisi 120 tabel domain, 373 FK, 320 CHECK. Dengan tambahan auth, inventaris migration menjadi **123 tabel domain + 8 infrastruktur = 131 tabel, 376 FK dan 320 CHECK**. Penjumlahan ini berdasarkan file migration; bukan pemeriksaan ulang skema produksi atau klaim bahwa semua tabel sudah terisi/setup operasional.

### Tindak lanjut temuan

| Temuan | Status sekarang |
| --- | --- |
| R-01: kategori pegawai historis | Rancangan/struktur employee_category_assignments dan snapshot tersedia; kalkulator honor historis tetap perlu implementasi/tes bisnis. |
| R-02: koreksi penerima honor | Struktur batch debit/kredit dan referensi asal ada; service approval/apply atomik serta pengajuan paralel belum menjadi bukti UAT. |
| R-03: snapshot laporan | report_dataset_rows dan consistent snapshot dirancang; worker dan uji koreksi selama ekstraksi belum selesai. |
| R-04: kelas historis | class_periods.grade_snapshot ada pada kamus/migration; penggunaan oleh laporan domain tetap perlu diuji. |
| R-05: pembulatan honor | Floor JP tiga desimal dan half-up rupiah per sumber ditetapkan; kalkulator/prorata/uji batas masih pekerjaan domain honor. |
| R-06/07: arsitektur dan stack | Laravel API + Next.js/React + MySQL sudah menjadi baseline; auth diselaraskan ke JWT/Google/BFF. Tidak diarahkan kembali ke Inertia atau autentikasi sesi SPA lama. |
| R-08: jobs.attempts | Kamus memakai U16 sesuai migration infrastruktur. |
| R-09: kamus kanonik | Sumber tunggal backend/database/database.MD; rujukan lokasi lama tidak menjadi salinan aktif. |
| R-10: dokumentasi auth tertinggal kode | Ditangani pada pembaruan ini: FR-01, NFR-04, arsitektur, DEC-08/13, AC-25/31/36–39, tabel JWT/OAuth, endpoint, cookie dan status implementasi. |
| R-11: izin domain dan cakupan data | UI role mengelola accounts.*; mandat kategori/objek/penugasan domain belum punya alur admin lengkap. Pemeriksaan objek API domain tetap wajib saat halaman diaktifkan. |
| R-12: pemulihan/aksi auth lanjutan | Reset email mandiri dan penetapan sandi pertama Google-only tanpa sandi lama belum tersedia. Logout-all hanya API Laravel, belum BFF/UI khusus. Deduplikasi refresh Next.js hanya satu proses; deployment multi-instance memerlukan peninjauan. |

### Addendum absensi pegawai

Konfirmasi pemilik proyek membatasi foto + GPS pada **masuk dan pulang pegawai**. Absensi siswa tetap harian oleh guru/wali kelas. Default lainnya tetap, termasuk pemisahan absensi dari realisasi mengajar dan honor, shift, toleransi terlambat, serta approval koreksi.

- Setiap aksi normal masuk/pulang memerlukan foto kamera baru dan lokasi perangkat. Biometrik/pengenalan wajah tetap nonaktif; geofence/radius tidak diberlakukan pada baseline ini.
- Default kompresi server: WebP, sisi terpanjang maksimal 1.280 px tanpa memperbesar, kualitas awal 80 sampai minimum 60, dan hasil maksimal 300 KiB. Orientasi diperbaiki dan EXIF dihapus; input maksimal 10 MiB/20 megapiksel. Kegagalan kompresi menolak aksi tanpa absensi parsial.
- Default GPS: akurasi maksimal 100 meter; usia foto/lokasi maksimal 60 detik saat diterima dan bukan dari masa depan. Jam absensi sah memakai waktu server.
- Foto hasil kompresi disimpan di storage privat. Database menyimpan referensi file dan metadata, bukan byte foto/BLOB/base64. Bukti asli dipertahankan saat koreksi; akses dibatasi ke pemilik dan petugas absensi bermandat.
- `employee_attendance_captures` menyimpan satu bukti per absensi–aksi, koordinat, akurasi, waktu pengambilan, metadata kompresi, actor, operation key, dan snapshot policy. Migration menambahkan 3 FK dan 7 CHECK.
- DEC-16 dan policy absensi memakai versi efektif baru. Flag SQL pada policy lama tetap 0/0 agar histori tidak diwajibkan bukti retroaktif; setup policy addendum baru wajib mengisi 1/1. Tanggal berlaku operasional diisi saat onboarding.

SRS memperbarui FR-05-01/02, NFR-05, DEC-16, AC-06/32 serta menambahkan FR-05-04–06, BR-24/25, dan AC-33–35. Pada tahap addendum foto/GPS, 18 DEC selain DEC-16 dipertahankan. Pembaruan auth berikutnya hanya menyelaraskan DEC-08/13; ketentuan foto/GPS, absensi siswa dan honor tetap sama.

### Bukti dan batas verifikasi

- **Bukti historis skema/addendum:** 46 tes pada MySQL 9.5.0 terisolasi, termasuk 23 tes metadata foto/GPS; lifecycle upgrade/rollback/migrate ulang mempertahankan absensi dan policy lama. Snapshot saat itu 120 tabel domain, 373 FK, 320 CHECK. Baseline sebelumnya diuji pada MySQL 8.4.11. Target produksi tetap MySQL 8.4.
- **Bukti sesi auth/akun/navigation sebelumnya:** 56 tes backend/511 assertions dan 47 tes browser lulus. Suite terkait: MysqlAuthApiTest, GoogleOAuthTest, MysqlAccountManagementTest, NavigationAccessTest, serta frontend/tests/e2e. Build/lint frontend dan format PHP juga tercatat lulus pada sesi implementasi.
- **Pembaruan ini:** membaca kode aktual dan migration, menyelaraskan ketiga dokumen, membandingkan 19 DEC, memeriksa XML/struktur DOCX dan render PDF SRS. Suite aplikasi tidak dijalankan ulang; hasil lama tidak diubah menjadi klaim eksekusi baru.
- **Belum dibuktikan selesai:** OAuth Google nyata/deployment production, TLS/trusted proxy/CORS produksi, pemulihan email, seluruh layanan domain, kamera/GPS browser, kompresi byte foto/cleanup storage, worker impor/ekspor/backup, uji beban, restore dan UAT. Nominal tarif, kalender nyata dan mandat operasional tetap membutuhkan onboarding.

Pembaruan dokumen tidak mengubah kode aplikasi, `.env`, kredensial, migration atau data akun operasional. Bagian historis berikut dipertahankan untuk jejak review dan harus dibaca sesuai tanggalnya.

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
