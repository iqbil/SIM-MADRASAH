# Review SRS dan rancangan database SIM Madrasah

Tanggal review awal: 14 Oktober 2026. Pembaruan terakhir: **27 Oktober 2026 — audit kesesuaian SRS v1.4, database, roadmap dan implementasi API/BFF/UI**.

Bagian 27 Oktober adalah status aktif. Bagian 24/20/14 Oktober dipertahankan sebagai riwayat sesuai tanggalnya; hitungan tes/tabel dan pernyataan “belum tersedia” pada bagian lama tidak menggantikan status terbaru. Keberadaan rute ditelusuri ke controller/service, pembatas akses, frontend dan tes, bukan dianggap bukti UAT.

## Status aktif — 27 Oktober 2026

Acuan: [SRS v1.4](SRS_SIM_Madrasah_MTs_Al_Karimiyyah.txt) §1.5/Bab II/VIII, [kamus database](backend/database/database.MD) §17/21/22, [roadmap](roadmap-agents.md), [API Laravel](backend/routes/api.php), service/DataAccess terkait, `frontend/app/api` dan tes implementasi yang tercatat di [log](log.txt).

### Kesesuaian modul selesai dan rute

| Roadmap | SRS | Kelompok `/api/v1` | Definisi rute | Status aktual |
| --- | --- | --- | ---: | --- |
| #1 Autentikasi/pengguna | FR-01 | `auth`, `accounts` | 9 + 10 | JWT/refresh/Google, profil/sandi/logout; akun, role/izin, audit; BFF/UI tersedia. |
| #2 Data master | FR-02 | `master` | 22 | Identitas madrasah, delapan resource master, relasi wali/kategori/jabatan, impor CSV dengan pratinjau/error/konfirmasi; BFF/UI tersedia. |
| #3 Akademik/kelas | FR-03–04 | `academic` | 60 | Periode/konteks, rombel/enrollment/mutasi, kalender, penugasan, slot/jadwal berversi, generator pertemuan, pembatalan/pengganti; BFF/UI termasuk perwalian tersedia. |
| #4 Absensi pegawai | FR-05 | `attendance` | 21 | Foto/GPS, status petugas, pola/penetapan/hari kerja, policy/geofence berversi dengan peta, bukti privat, koreksi/rekap; BFF/UI tersedia. |
| #5 Realisasi mengajar | FR-06 | `teaching` | 8 | Pertemuan pelaksana, revisi/submit dan verifikasi bermandat; BFF/UI tersedia. |
| #6 Absensi siswa | FR-07 | `student-attendance` | 7 | Kelas/roster, record harian, rekap dan koreksi/keputusan; BFF/UI tersedia. |
| #7–16 | FR-08–17 | Belum ada kelompok API bisnis | 0 | Honor, soal, BK, TU, portal wali, website, laporan/ekspor, sistem, privasi dan notifikasi masih backlog. Struktur tabel/model tersedia. |

**137 definisi rute** dari `php artisan route:list --path=api/v1 --json`; GET|HEAD dihitung satu, placeholder `{resource}`/`{action}` tidak diperluas. Angka ini bukan jumlah layar atau izin untuk semua role. Dashboard role dan rekap yang sudah tersedia bukan penyelesaian FR-14; audit per modul bukan penyelesaian FR-15. Hubungan wali yang dapat diverifikasi di master belum berarti portal wali selesai. Modul berikutnya tetap #7/FR-08.

### Ketidaksesuaian dokumentasi yang diperbaiki

| Bagian tertinggal | Penyelarasan |
| --- | --- |
| Status auth/shell saja dan menu domain nonaktif | Status aktif FR-01–FR-07 pada keempat dokumen; matriks tujuh kelompok API dan pemisahan backlog FR-08–FR-17. |
| Akademik masih disebut 31 rute, jadwal/pertemuan dan BFF/UI belum ada | Akademik kini 60 rute, termasuk jadwal/slot/pertemuan/pengganti dan halaman perwalian. |
| API/BFF serta daftar permission masih auth/accounts saja | BFF tujuh area; katalog role 45 izin, academic.* terpisah; izin efektif tetap diperiksa per objek/mandat. |
| Absensi foto/GPS hanya struktur dan geofence tidak tersedia | Kamera/kompresi/API/UI sudah tersedia; geofence opsional berversi FR-05-08 dengan editor peta dan validasi server. |
| Inventaris database 330 CHECK dan migration geofence belum tercantum | 22 migration, 137 tabel (128 domain + 9 infrastruktur), 393 FK, 331 CHECK; lima atribut geofence pada policy dan snapshot JSON bukti. |
| Default absensi disebut seluruhnya pekerjaan setup | Bootstrap baseline pegawai dan policy siswa sudah ada; lokasi nyata, penugasan, kalender dan mandat tetap onboarding. |
| Pernyataan database operasional tidak pernah disentuh | Riwayat pengujian lama tetap terisolasi; pemulihan database latihan 27 Oktober adalah pengecualian yang diotorisasi dan dicatat. Audit dokumen ini hanya baca metadata. |
| Tautan SRS historis tidak ada di root dan bagian geofence memutus tabel Markdown | Link arsip diarahkan ke docs/archive; tabel kehadiran disatukan kembali, status historis diberi label tanggal. |

### Hak akses dan batas implementasi

- [AccountAccess](backend/app/Services/AccountAccess.php) dan endpoint roles menyediakan 45 izin terkelola: 10 accounts.*, 31 master.*, 3 attendance.*, realizations.verify. Pemberian dibatasi `grantable_permissions` dan `can_assign`, kode role sistem dilindungi; pemberi tidak boleh menaikkan scope institusi melampaui miliknya. UI memfilter katalog berdasarkan permissions auth/me (akun/master/akademik), sehingga attendance.* dan realizations.verify belum dapat dipilih di UI. Katalog 14 academic.* dikelola `AcademicDataAccess`, belum menjadi pilihan editor role. UI administrasi mandat belum tersedia.
- [AcademicDataAccess](backend/app/Services/AcademicDataAccess.php) dan layanan membatasi guru/wali menurut penugasan aktif serta kelas/periode; operator/Super Admin dapat mengelola, kepala membaca institusi. Mutasi membutuhkan `academic.enrollments.manage`; filter perwalian hanya kelas wali aktif.
- [AttendanceDataAccess](backend/app/Services/AttendanceDataAccess.php) memeriksa pegawai aktif milik akun, kewenangan petugas, scope koreksi dan mandat aktif. Pengaturan policy/geofence memerlukan kewenangan institusi; mandat kelas/semester/pegawai saja tidak cukup. Koreksi harus diputus orang berbeda dengan versi/tenggat yang sah.
- [TeachingDataAccess](backend/app/Services/TeachingDataAccess.php) membatasi pengajuan pada pelaksana pertemuan; verifikasi memerlukan mandat aktif realizations.verify sesuai institusi/semester/kelas/pegawai pelaksana, termasuk guru pengganti. Kepala/Super Admin tidak otomatis menjadi verifikator. Queue/detail/keputusan dibatasi konsisten. Hanya held+approved, tidak batal/terhalang kalender, yang masuk total JP sesuai filter; JP dibatasi rencana dan durasi aktual 40 menit/JP.
- [StudentAttendanceService](backend/app/Services/StudentAttendanceService.php) memakai scope kelas penugasan/institusi, mode daily, batas input 23.59 WIB dan koreksi 7 hari dengan persetujuan berbeda; sesudah tenggat memerlukan mandat. Tidak ada baris berarti belum dicatat. API koreksi siswa dan pegawai terpisah. Mode pertemuan/both tetap nonaktif.
- BFF menggunakan cookie HttpOnly, Origin dan allowlist rute/query/metode serta no-store. Mutasi biasa JSON; unggah CSV master/foto clock memakai multipart khusus. Bukti foto/GPS privat tidak diberikan ke publik/wali atau log umum.

Akun awal kini dibaca langsung di `backend/database/seeders/SuperAdminSeeder.php`; file konfigurasi akun dan pemanggilan `config()` akun dihapus sesuai arahan pengguna. Nilai awal dan perlindungan akun lama tetap sama; keterangan konfigurasi pada riwayat 20 Oktober dibaca sesuai tanggalnya.

### Geofence, database dan rollback

Lima atribut sudah tersedia melalui [migration geofence](backend/database/migrations/2026_10_27_090758_add_geofence_to_employee_attendance_policies.php): `geofence_enabled` default false; `geofence_name`, `geofence_latitude`, `geofence_longitude`, `geofence_radius_meters` nullable. Radius aktif 25–5.000 m; CHECK `ck_eap_geofence` mewajibkan konfigurasi lengkap dan foto/GPS. Tidak perlu database/tabel lokasi baru; konfigurasi dan jarak disimpan di `employee_attendance_captures.policy_snapshot`.

Editor pada `/absensi/pola-kerja` → Kebijakan foto/GPS memakai Leaflet/OpenStreetMap, klik/geser pin dan input manual. Rute tetap `GET attendance/policy` dan `POST attendance/policy/versions`, bukan endpoint geofence baru. Versi prospektif paling cepat besok dan sesudah versi terjadwal terakhir; alasan wajib. UI mengirim `expected_policy_id`, API menolak ID usang dengan 409 tetapi mengizinkan field ini dihilangkan untuk kompatibilitas client lama. Policy berlaku dan terjadwal dibedakan. Frontend menampilkan alert di luar radius/posisi belum pasti, memblokir submit dan mengambil GPS ulang. Server menerima hanya jarak Haversine + akurasi <= radius; penolakan 422 terjadi sebelum penulisan foto/absensi. Histori dan fallback petugas dipertahankan; tidak ada pelacakan latar belakang atau jaminan anti-Fake GPS.

Metadata lokal dibaca pada MySQL **9.7.4**: **137 tabel, 393 FK, 331 CHECK, 22 migration**. Target produksi tetap **MySQL 8.4**. Database latihan telah dipulihkan setelah rollback parsial atas konfirmasi pengguna tanpa backup: schema, akun serta hak akses asli yang dapat dipulihkan tersedia; data latihan domain lain yang terhapus harus diisi ulang dan sesi memerlukan login ulang. Snapshot keadaan tersisa bukan backup sebelum rollback. [GuardedMigrator](backend/app/Services/GuardedMigrator.php) menghentikan batch yang mencakup downgrade identitas ketika email NULL sebelum `down()` pertama; guard NOT NULL asli tetap ada. `migrate` memasang fitur baru; `migrate:rollback` membatalkan batch dan bukan cara memasang migration.

### Bukti dan pekerjaan tersisa

Hasil sesi implementasi yang dicatat di log, diperiksa kembali sebagai bukti historis:

| Cakupan | Hasil |
| --- | --- |
| Backend MySQL penuh | 170/170 tes, 7.264 asersi. |
| Regresi geofence berikutnya | 11/11 tes, 217 asersi. |
| Regresi rollback/seeder/akun berikutnya | 17/17 tes, 178 asersi. |
| Inventaris backend sekarang | 173 kasus; bukan klaim suite penuh 173 sudah dijalankan. |
| Frontend E2E lengkap | 94/94; API/kamera/GPS menggunakan fixture. |
| Hydration development dan regresi layout geofence | 6/6 dan 9/9. |
| Lint/build frontend | Biome 150 file bersih, build 38/38 halaman. |

Audit dokumentasi ini memeriksa route list, source/tes, metadata database dan konsistensi dokumen; tidak menjalankan ulang suite aplikasi atau mengubah kode/data. Belum dibuktikan: OAuth Google nyata, GPS/kamera lapangan, versi MySQL deployment, TLS/proxy produksi, uji beban/pentest, restore produksi dan UAT. FR-08–FR-17, UI mandat lengkap, reset mandiri email dan sandi pertama Google-only, BFF/UI logout-all, worker ekspor/backup, pemeriksaan publish dengan konsen, serta dispatch/pusat notifikasi tetap pekerjaan berikutnya. Keberadaan tabel dan requirement DEC-20 in-app bukan bukti dispatch telah aktif.

## Riwayat — 24 Oktober 2026 (bukan status implementasi terkini)

### Pembaruan 24 Oktober 2026 — SRS v1.3 dan addendum database

Acuan:

- [SRS v1.3](SRS_SIM_Madrasah_MTs_Al_Karimiyyah.txt) — addendum 24 Oktober 2026: FR-16 (privasi/UU PDP No. 27/2022), FR-17 (notifikasi), BR-26–BR-28, NFR-15 (performa frontend), NFR-16 (uji keamanan independen), DEC-20/DEC-21, AC-40–AC-42, FR-05-07 (SOP absensi), penguatan FR-13-03.
- [Kamus database kanonik](backend/database/database.MD) §2, §13.6 (baru), §14, §15.2, §17, §19, §21, §22.
- Migration baru: `2026_10_24_000001_create_sim_privacy_consent_and_notifications_tables.php`; register DEC via `backend/database/seeders/BusinessDecisionSeeder.php` (onboarding).

**Penilaian:** cakupan kini 17 kelompok kebutuhan (FR-01–FR-17) dan 21 DEC. Dampak skema v1.3 terbatas dan telah diturunkan: empat tabel privasi/persetujuan, satu tabel notifikasi, serta seed register keputusan. Modul inti (master, akademik, absensi, honor, soal, BK, TU) tidak berubah — FR-05-07 memakai mekanisme petugas/koreksi yang sudah ada, NFR-15/16 adalah kebutuhan uji/proses tanpa tabel baru.

### Perubahan struktur dan data

| SRS v1.3 | Penurunan database |
| --- | --- |
| FR-16-01 register dasar pemrosesan | `data_processing_registers` (domain, dasar hukum, kategori data, retensi, peran penanggung jawab). |
| FR-16-04/DEC-21/FR-13-03 persetujuan publikasi siswa | `student_publication_consents` (per siswa–scope, berversi, marker aktif generated column, penarikan tercatat) + `content_student_links` (siswa yang tampak pada revisi konten; snapshot konsen saat publish). |
| FR-16-03/BR-27 hak subjek data | `data_subject_requests` (akses/perbaikan/pembatasan/keberatan/penghapusan, verifikasi identitas, status, alasan penolakan wajib). |
| FR-17/DEC-20 notifikasi | `notifications` in-app per penerima dengan `read_at`; payload hanya referensi entitas; tanpa tabel kanal eksternal (backlog). |
| Register DEC-01–DEC-21 | `BusinessDecisionSeeder` (onboarding, idempoten): 22 baris `business_decisions` baseline; DEC-16 dua versi (v1 superseded 14 Okt, v2 baseline addendum 20 Okt dengan `supersedes_id`); DEC-20/21 efektif 24 Okt. Menutup poin "seed DEC" yang sebelumnya berstatus setup tertunda. Ditetapkan sebagai seeder, bukan migration: percobaan migration-seed membuat 24 tes error karena tes migration/absensi memigrasi skema dengan asumsi register kosong (dan rollback berbenturan FK `supersedes_id` self-reference). |

Rancangan alur publish konten mewajibkan validasi konsen aktif untuk seluruh `content_student_links` (§13.6, §15.2); penarikan konsen memicu peninjauan konten terbit pada siklus publikasi berikutnya (BR-28).

### Verifikasi yang dijalankan

- Suite MySQL penuh (`phpunit.mysql.xml`) lulus **132 tes / 6358 assertions** pada MySQL 9.7.2 lokal terisolasi (database uji dibuat/dihapus khusus; database operasional `sim_madrasah` tidak disentuh). Target produksi tetap MySQL 8.4 — uji ulang versi produksi tetap perlu.
- Uji negatif lulus: konsen aktif ganda ditolak `uq_spc_student_scope_active`; `consent_scope`/`link_type`/kategori notifikasi/`legal_basis` invalid ditolak CHECK; `data_subject_requests` status `rejected` tanpa alasan ditolak; tautan konten–siswa ganda ditolak `uq_csl_revision_student`. Penarikan konsen lalu pemberian konsen aktif baru berhasil (riwayat dipertahankan).
- `BusinessDecisionSeeder` idempoten: dua kali eksekusi tetap 22 baris DEC dengan tanggal efektif benar.
- Model Eloquent: 5 model baru (`DataProcessingRegister`, `StudentPublicationConsent`, `ContentStudentLink`, `DataSubjectRequest`, `UserNotification` → tabel `notifications`) + relasi balik pada User/Person/Student/StudentGuardian/ContentRevision/RetentionPolicy/File; `MysqlModelTest` memvalidasi seluruh tabel domain memiliki model dengan cast/relasi sesuai skema nyata — hitungan diperbarui 123 → 128.
- Perbaikan tes yang tertinggal kode (bukan akibat v1.3): `NavigationAccessTest` kini mengharapkan token granular `master.*.view` + `master.imports` untuk super_admin/operator (drift sejak perubahan NavigationAccess 22–23 Oktober yang hanya diverifikasi tes Unit saat itu).
- Pint + `php -l` lulus untuk seluruh berkas yang diubah.
- Inventaris rancangan kini **128 tabel domain + 9 infrastruktur = 137 tabel, 393 FK, 330 CHECK** (menambah `personal_access_tokens` 20 Okt, seed izin master data 22 Okt, dan addendum v1.3 24 Okt ke inventaris §21 yang sebelumnya tertinggal).

### Tindak lanjut temuan (v1.3)

| Temuan | Status |
| --- | --- |
| Persetujuan publikasi foto siswa belum dimodelkan | Struktur tersedia (`student_publication_consents` + `content_student_links`); layanan publish yang memeriksa konsen, UI pendaftaran konsen, dan AC-40 tetap pekerjaan modul publikasi. |
| Notifikasi tanpa keputusan eksplisit | DEC-20 menetapkan in-app baseline + kanal eksternal backlog; tabel `notifications` tersedia; layanan emisian/pembacaan dan UI menyusul modul terkait. |
| UU PDP tanpa register/hak subjek data | Register pemrosesan, konsen, dan permintaan subjek data tersedia sebagai struktur; pengisian register data nyata, template persetujuan, dan petugas layanan adalah pekerjaan onboarding (§17.1). |
| Uji keamanan independen (NFR-16) & performa frontend (NFR-15) | Non-skema; masuk Tahap 6/checklist rilis Bab IX SRS dan pemantauan §19/AC-42. Tidak ada tabel baru. |
| SOP absensi foto/GPS (FR-05-07) | Tanpa perubahan skema; memakai `entry_mode=officer` + `attendance_corrections` yang sudah ada. SOP/petugas fallback adalah data onboarding. |

**Modul manajemen akun dan data master:** SRS v1.3 tidak mengubah FR-01/FR-02, sehingga tidak ada revisi fungsional diperlukan pada `AuthenticationService`/`AccountManagementService`/`MasterData*`. Verifikasi: suite MySQL auth/akun/navigasi/master lulus penuh setelah perbaikan ekspektasi tes NavigationAccess; model `User` kini memiliki relasi `notifications()` untuk FR-17.

**Tindak lanjut tahap pengembangan (24 Oktober 2026, malam):** API domain pertama sudah diimplementasikan — modul Akademik & Kelas (`/api/v1/academic`, 31 endpoint termasuk show) dengan otorisasi `academic.*` berbasis role, transaksi + penguncian parent, snapshot grade, mutasi pratinjau/konfirmasi, dan cakupan guru/wali kelas; suite MySQL 140 tes lulus (bertambah `MysqlAcademicApiTest`). BFF frontend dan UI menyusul; jadwal/slot/pertemuan adalah modul berikutnya.

Belum dibuktikan oleh pembaruan ini: layanan domain privasi/notifikasi, kompresi foto nyata, kamera/GPS browser, worker laporan/backup, uji beban, restore, pentest, dan UAT.

## Riwayat — 20 Oktober 2026 (bukan status implementasi terkini)

Acuan:

- [SRS revisi v1.2 dengan addendum foto/GPS dan autentikasi JWT/Google](docs/archive/SRS_SIM_Madrasah_MTs_Al_Karimiyyah_2026_2027_Revisi_v1_2.docx).
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

- `docs/archive/SRS_SIM_Madrasah_MTs_Al_Karimiyyah_2026_2027_Revisi_v1_1.docx`.
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
