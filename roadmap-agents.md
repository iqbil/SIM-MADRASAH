# Roadmap Modul SIM Madrasah — MTs Al Karimiyyah (untuk AI Agent)

Peta runtutan modul yang **sudah** dan **harus** dikerjakan, mengikuti seksi menu
sidebar (`frontend/lib/navigation/sidebar.ts`) dan modul SRS v1.4
(`SRS_SIM_Madrasah_MTs_Al_Karimiyyah.txt`, FR-01…FR-17, Bab IX tabel tahap).
Dipelihara sebagai acuan kerja lintas sesi: setiap selesai satu modul, perbarui
status di file ini dan tambahkan catatan sesi di `log.txt`.

Terakhir diperbarui: **27 Oktober 2026** (audit dokumentasi terhadap routes/api.php, SRS v1.4, skema aktual dan hasil implementasi).

---

## A. Ringkasan status

**Arti selesai:** API backend, BFF/UI dan pengujian otomatis modul telah tersedia. Status ini bukan pengesahan UAT, kesiapan produksi, atau penyelesaian FR lintas modul seperti notifikasi/laporan. Roadmap #1–#6 mencakup **FR-01–FR-07**, karena #3 memuat FR-03 dan FR-04.

| # | Modul (sidebar) | SRS | Status | Catatan |
|---|---|---|---|---|
| 1 | Autentikasi & pengguna (Beranda, Akun saya, Pengelolaan akun) | FR-01 | ✅ SELESAI | Login password/Google, JWT+BFF cookie HttpOnly, manajemen user/role/izin/audit |
| 2 | Data master (8 item) | FR-02 | ✅ SELESAI | Termasuk impor CSV + tab perbaikan (design-system v1.11) |
| 3 | Akademik & kelas (9 item aktif) | FR-03, FR-04 | ✅ SELESAI | Tahun ajaran s.d. jadwal pelajaran, guru pengganti, dan Kelas perwalian saya |
| 4 | Absensi pegawai | FR-05 | ✅ SELESAI | Foto+GPS mandiri, titik/radius berversi via peta, izin/sakit/cuti/dinas, pola kerja, koreksi, rekap |
| 5 | Realisasi mengajar | FR-06 | ✅ SELESAI | Guru pelaksana (terjadwal/pengganti) mengajukan revisi realisasi; verifikasi mandat, pemohon ≠ penyetuju |
| 6 | Absensi siswa | FR-07 | ✅ SELESAI | Harian per wali kelas/guru; mengaktifkan "Kelas perwalian saya", koreksi approval petugas berbeda |
| 7 | Honor & keuangan | FR-08 | ⬜ BERIKUTNYA | Tarif → periode → pemeriksaan → pengesahan/lock → penyesuaian |
| 8 | Bank soal & paket | FR-09 | ⬜ | Soal berversi, paket, cetak naskah/kunci PDF |
| 9 | Bimbingan & konseling | FR-10 | ⬜ | Kasus internal, tindak lanjut, pembagian ringkasan |
| 10 | Tata usaha & arsip | FR-11 | ⬜ | Surat masuk/keluar, agenda, arsip dokumen (disposisi nonaktif) |
| 11 | Portal orang tua/wali | FR-12 | ⬜ | Verifikasi hubungan, data & kehadiran anak, pengumuman |
| 12 | Website madrasah | FR-13 | ⬜ | Profil/berita/galeri + alur draf → tinjau → terbit |
| 13 | Laporan & ekspor | FR-14 | ⬜ | Dashboard/laporan lintas sumber + permintaan ekspor |
| 14 | Pengaturan sistem (audit, backup, monitoring, retensi) | FR-15 | ⬜ | Sebagian primitif sudah ada (audit trail service, BusinessDecisionSeeder) |
| 15 | Privasi & kepatuhan UU PDP | FR-16 | ⬜ | Tabel sudah ada (register, persetujuan publikasi, DSR) — alur/UI belum |
| 16 | Notifikasi in-app | FR-17 | ⬜ | Tabel `notifications` sudah ada — dispatch & pusat notifikasi belum |

Urutan mengikuti penomoran FR dan fase SRS Bab IX (tahap 1 fondasi → tahap 2
akademik/kehadiran → tahap 3 honor → tahap 4 soal/BK/TU → tahap 5
portal/website/dashboard). Modul #15–#16 bersifat lintas-modul: boleh dikerjakan
menempel pada modul terkait (mis. persetujuan publikasi foto saat website/#12,
notifikasi verifikasi saat realisasi/#5) asal izin & alurnya mengikuti SRS.

Item sidebar yang masih bergambar jam (nonaktif) = modul belum dibangun; item
mendapat `href` saat modulnya dikerjakan.

---

### A.1 Jejak API modul selesai — 27 Oktober 2026

Sumber: [routes/api.php](backend/routes/api.php), hasil `php artisan route:list --path=api/v1 --json`, controller/service/DataAccess terkait dan BFF `frontend/app/api`.

| Roadmap / FR | Kelompok `/api/v1` | Definisi rute | Alur tersedia |
| --- | --- | ---: | --- |
| #1 / FR-01 | `auth` + `accounts` | 9 + 10 | JWT/refresh/Google, profil/sandi/logout; akun, role/izin, status/reset/sesi/Google unlink dan audit. |
| #2 / FR-02 | `master` | 22 | Identitas madrasah, delapan resource, relasi wali/kategori/jabatan, impor CSV/template/pratinjau/error/konfirmasi. |
| #3 / FR-03–04 | `academic` | 60 | Periode/konteks, rombel/enrollment/mutasi, kalender, penugasan, slot/jadwal berversi, pertemuan/pembatalan dan pengganti. |
| #4 / FR-05 | `attendance` | 21 | Foto/GPS, policy/geofence berversi, bukti privat, status petugas, pola/penetapan/hari kerja, koreksi dan rekap. |
| #5 / FR-06 | `teaching` | 8 | Pertemuan pelaksana, revisi/submit, detail, antrean dan keputusan verifikasi. |
| #6 / FR-07 | `student-attendance` | 7 | Kelas/roster, input harian, rekap, pengajuan dan keputusan koreksi. |

Total **137 definisi rute**; GET|HEAD dihitung satu, placeholder resource/action tidak diperluas. FR-08–FR-17 belum mempunyai kelompok API bisnis. Tabel/model, navigasi role, audit, impor dan rekap yang sudah ada tidak berarti modul honor/portal/publikasi/laporan/sistem/privasi/notifikasi selesai.

Batas akses yang sudah diterapkan:

- Akun/person/role harus aktif. API role menyediakan katalog 45 izin (10 accounts.*, 31 master.*, 3 attendance.*, realizations.verify), grantable_permissions dan proteksi role sistem; delegasi scope institusi tidak boleh melampaui pemberi. UI memfilter permissions auth/me (akun/master/akademik), sehingga attendance.* dan realizations.verify belum menjadi pilihan UI. Katalog academic.* terpisah; UI administrasi mandat lengkap belum tersedia.
- Guru/wali membaca akademik dan mencatat siswa sesuai kelas/penugasan aktif; operator/Super Admin mengelola akademik, kepala membaca institusi. Preview/confirm mutasi perlu academic.enrollments.manage; perwalian memakai filter kelas wali aktif.
- Pengelolaan policy/geofence hanya kewenangan institusi: Operator/Super Admin, role eksplisit attendance.manage atau mandat institusi. Mandat kelas/semester/pegawai tidak cukup. GET policy mengembalikan policy berlaku pada `data`, daftar `scheduled` dan `can_manage`; POST versi baru memerlukan alasan dan tanggal paling cepat besok/setelah versi terjadwal terakhir. UI mengirim expected_policy_id untuk konflik 409; field ini masih opsional di API demi kompatibilitas client lama.
- Koreksi perlu mandat attendance.correct sesuai objek dan pemutus berbeda; verifikasi realisasi perlu mandat realizations.verify aktif yang mencakup pelaksana/pertemuan. Role kepala/Super Admin atau permission pada role saja tidak menggantikan mandat tersebut. Antrean/detail/keputusan dibatasi konsisten.
- Geofence memblokir posisi luar/meragukan di UI dan server; jarak + akurasi harus <= radius 25–5.000 m. Foto/lokasi tetap mengikuti usia/akurasi policy. Riwayat dan fallback administratif dipertahankan; tidak ada pelacakan latar belakang atau jaminan anti-Fake GPS.
- Absensi siswa tetap daily; tidak ada baris berarti belum dicatat, input sampai 23.59 WIB dan koreksi 7 hari/mandat sesudah tenggat. API koreksi siswa terpisah dari pegawai. Total JP realisasi hanya held+approved sah, dibatasi rencana/durasi dan filter.

Rincian kontrak, lima kolom geofence dan batas pekerjaan tersedia di [database.MD §22](backend/database/database.MD#22-status-implementasi-dan-batas-dokumentasi--27-oktober-2026) dan [review aktif](REVIEW_SRS_DATABASE.md). Bukti pengujian pada bagian B berasal dari sesi implementasi; audit dokumentasi ini tidak menjalankan ulang suite aplikasi.

### Urutan tahap awal pemakaian (MVP, diputakhirkan 26 Oktober 2026)
Berdasarkan SRS Bab IX dan §9.1 ("MVP tahap 1–2 dan laporan dasar; selesaikan
satu alur guru → jadwal → pertemuan → realisasi → verifikasi → honor locked"),
urutan pengerjaan modul sisa agar situs dapat dipakai nyata secepat mungkin:

**7 → 13 → 11 → 15\* → 12 → 16 → 8 → 9 → 10 → 14† → 15\*\***

- **#6 Absensi Siswa** telah selesai dan masuk cakupan regresi; pekerjaan sisa dimulai pada #7.
- **#7 Honor** — menutup irisan alur lengkap 9.1; setelah ini siklus operasional inti (guru→jadwal→realisasi→verifikasi→honor locked) berjalan end-to-end.
- **#13 Laporan & Ekspor** — "laporan dasar" MVP; rekap data #6+#7.
- **#11 Portal Wali** — butuh data #6; nilai adopsi tinggi.
- **#15\* PDP** — cukup bagian persetujuan publikasi foto siswa (prasyarat galeri website #12).
- **#12 Website → #16 Notifikasi** — publikasi + pemberitahuan kejadian yang sudah hidup.
- **#8/#9/#10** — fase 4 SRS, mandiri; kerjakan urut nomor.
- **#14† Sistem** — UI audit/backup sebagai pengerasan pra-pilot (tahap 6 UAT); sisanya #15 (alur DSR) bertahap.

## B. Modul selesai (riwayat singkat)

1. **FR-01 Autentikasi & pengguna** — backend JWT HS256 + Google OAuth, rotasi
   refresh + replay detection, BFF Next.js (allowlist per area, Origin check),
   halaman login/akun/dashboard, manajemen akun+role+izin+audit (operator).
2. **FR-02 Data master** — identitas madrasah, siswa, wali, pegawai, jabatan &
   kategori, mapel, referensi status, impor CSV berpratinjau, relasi
   kategori/jabatan/wali, versi optimistik (409) + alasan tiap mutasi.
3. **FR-03/FR-04 Akademik & kelas** — tahun ajaran/semester + konteks aktif,
   rombel + periode, keanggotaan & mutasi (pratinjau→konfirmasi), kalender
   akademik, penugasan mengajar & wali kelas, **jadwal pelajaran berversi**
   (draf→terbit, benturan guru/rombel, generator pertemuan ikut kalender,
   pembatalan), **guru pengganti** (satu aktif per pertemuan, penunjukan baru
   menutup yang lama). Cakupan guru/wali dibatasi penugasan aktif (Bab II).

4. **FR-05 Absensi pegawai** — foto+GPS mandiri, geofence opsional berversi (SRS v1.4 FR-05-08), peta dan input manual, alert/validasi server termasuk ketelitian GPS; izin/sakit/cuti/dinas, pola kerja, koreksi, rekap. Policy lama tetap tanpa geofence sampai pengelola mengaktifkan versi baru.
5. **FR-06 Realisasi mengajar** — guru pelaksana (terjadwal/pengganti aktif)
   mencatat realisasi draf lalu mengajukan; isi immutable, revisi baru hanya
   sebelum diajukan/setelah ditolak; verifikasi oleh pemegang mandat
   realizations.verify yang berbeda dari pengaju, satu keputusan final per
   revisi; hanya held+approved yang valid dan tidak batal/terhalang kalender
   masuk total JP sesuai filter; JP dibatasi rencana dan durasi aktual (40 menit/JP). 8 rute /api/v1/teaching;
   halaman /realisasi/{pertemuan,riwayat,verifikasi}.
6. **FR-07 Absensi siswa** — mode harian per guru/wali kelas penugasan sebelum
   23.59 lokal (DEC-07, BR-08/09, AC-10); ketiadaan catatan ditampilkan sebagai
   belum dicatat (bukan alpa); rekap per tanggal & siswa; koreksi dengan alasan
   disetujui pemegang mandat `attendance.correct` yang berbeda dari pemohon;
   mengaktifkan item "Kelas perwalian saya" (`/akademik/perwalian`) dan tab
   "Koreksi siswa" pada persetujuan koreksi absensi (`/absensi/koreksi`).

Bukti per 27 Oktober 2026: backend suite penuh 170/170 lulus (7264 asersi),
regresi akhir 11/11 lulus (217 asersi), termasuk tes migrasi geofence baru pada
policy lama. Jumlah kasus backend pada sesi geofence 171; satu kasus migrasi ditambahkan
setelah suite penuh dan dijalankan pada regresi akhir. Frontend E2E lengkap
94/94 lulus; hydration development 6/6 lulus; Biome 150 file dan build 38/38 halaman.
Perbaikan akses, snapshot, radius/ketelitian, versioning prospektif, dan bukti
pengujian ada di `log.txt`. Design system terkini v1.11. Migration tambahan lima
kolom diuji pada database test dan telah diterapkan ke database latihan lokal
saat pemulihan di bawah. Aktivasi lokasi nyata tetap melalui policy prospektif,
bukan reset database. Modul berikutnya tetap #7.

Pemulihan rollback database latihan (27 Oktober 2026): seluruh 22 migration
sudah Ran, termasuk geofence; 2 akun dan hak akses asli dipertahankan/dipulihkan.
GuardedMigrator kini memeriksa downgrade identitas sebelum tabel batch dihapus;
seeder mengisi created_by yang guarded dengan benar. Regresi akhir pemulihan
17/17 lulus (178 asersi); jumlah kasus backend sekarang 173. Detail kehilangan
data latihan lain, snapshot privat, dan langkah login ulang dicatat di log.

---

## C. Antrian modul berikutnya (urut kerja)

### 7) Honor & keuangan — FR-08 ⬜ BERIKUTNYA
- **Item sidebar**: Honor final saya; Tarif & komponen; Tugas tambahan &
  potongan; Periode & perhitungan; Pemeriksaan; Pengesahan & penguncian;
  Penyesuaian.
- **SRS**: FR-08-01…, BR-11…18, DEC-01…06/08; tiga akun berbeda
  pembuat/pemeriksa/pengesah; setelah `locked` hanya adjustment; snapshot
  berisi versi kebijakan.
- **Tabel**: `honor_rates`, `honor_periods`, `honor_policy_versions`,
  `employee_additional_duties`, `honor_deductions`, `payrolls`/`payroll_versions`
  /`payroll_lines`/`payroll_line_sources`/`payroll_source_claims`,
  `payroll_adjustment_batches`/`payroll_adjustments`.
- **Sumber JP**: realisasi terverifikasi modul #5 (alur guru lengkap — MVP 9.1).

### 8) Bank soal & paket — FR-09
- Soal berversi milik guru/penugasan; paket dirakit pengelola; akses kunci
  terpisah dari naskah; cetak PDF naskah & kunci. Tabel `questions`,
  `question_versions`, `exam_packages`, `exam_package_items`,
  `exam_package_options`, `exam_package_keys`, `exam_package_files`.

### 9) Bimbingan & konseling — FR-10
- Kasus & catatan internal (akses khusus guru BK, tanpa bypass admin), tindak
  lanjut/pembinaan, ringkasan yang dibagikan ke wali kelas/wali siswa.
  Privasi: jangan bocorkan detail internal (DEC-20 berlaku juga ke notifikasi).

### 10) Tata usaha & arsip — FR-11
- Surat masuk/keluar + penomoran, agenda, arsip dokumen dengan kategori &
  izin unduh; publik hanya dokumen terbit; disposisi nonaktif (backlog).

### 11) Portal orang tua/wali — FR-12
- Verifikasi hubungan wali–siswa, data & kehadiran anak (sumber #6),
  pengumuman. Uji kebocoran data siswa lain (AC-25) wajib.

### 12) Website madrasah — FR-13
- Profil/halaman, berita, kegiatan, galeri (dengan **persetujuan publikasi foto
  siswa** FR-16 → aktifkan tabel `student_publication_consents` di sini),
  unduhan/kontak; alur draf → ditinjau penerbit berbeda → terbit.
  Tabel `contents`/`content_revisions`, `content_student_links`.

### 13) Laporan & ekspor — FR-14
- Dashboard/laporan operasional lintas sumber + permintaan & hasil ekspor;
  cakupan ekspor tidak melampaui izin sumber data.

### 14) Pengaturan sistem — FR-15
- Jejak audit (UI atas `audit_logs` yang sudah terisi semua modul), backup &
  pemulihan, retensi/arsip, monitoring, konfigurasi & kebijakan
  (`business_decisions` register — seeder sudah ada, UI belum).

### 15) Privasi & kepatuhan UU PDP — FR-16 (lintas)
- Register pemrosesan data, template & pencatatan persetujuan publikasi,
  alur permintaan hak subjek data (akses/koreksi/hapus) dengan tenggat.
  Semua tabel migrasi sudah tersedia (sesi 2026-10-23).

### 16) Notifikasi in-app — FR-17 (lintas)
- Pusat notifikasi per pengguna + dispatch dari kejadian (verifikasi realisasi,
  approval honor, koreksi absensi, pengumuman). Batasi isi payload sesuai
  DEC-20 (tanpa BK internal/rincian honor orang lain/koordinat).

---

## D. Alur kerja per modul (pola yang sudah terbukti)

1. **Baca acuan**: seksi SRS modul + Bab II (hak akses) + `backend/database/
   database.MD` (tabel & aturan) + `design-system.md` (terkini v1.11) + sesi
   terakhir `log.txt`.
2. **Backend dulu**: `*DataAccess` (katalog izin + scope) → `*Service`
   (`DB::transaction` retry 3, `lockForUpdate` parent, validasi `Validator`,
   `reason` min 10, `expected_updated_at` → 409, audit via
   `AccountManagementService`) → controller tipis → rute `routes/api.php` →
   test MySQL (`phpunit.mysql.xml`).
3. **Audit backend** terhadap SRS/Bab II/best-practice sebelum lanjut.
4. **Frontend**: perluas BFF area terkait (allowlist + query param) →
   `lib/<modul>.ts` → komponen di `components/<modul>/` memakai komponen
   bersama → aktifkan href sidebar + ekspansi token navigasi + guard halaman →
   mock e2e (`tests/fixtures/*-api.mjs`) → spec Playwright.
5. **Verifikasi**: `pnpm format && pnpm lint && pnpm build` lalu
   `pnpm exec playwright test` (WAJIB build ulang sebelum e2e — server
   produksi `next start`); backend: suite MySQL penuh hijau.
6. **Tutup sesi**: perbarui status di file ini + entri `log.txt` + memori
   agent bila ada gotcha baru.

## E. Aturan tetap

- Pengujian memakai `sim_madrasah_test` melalui phpunit.mysql.xml; kredensial
  disuplai environment lokal tanpa ditulis ke dokumentasi/log. `.env` tidak
  diubah oleh audit ini. Database operasional tidak menjadi sasaran tes.
  Pengecualian tercatat: pemulihan database latihan `sim_madrasah` pada
  27 Oktober diotorisasi pengguna setelah rollback parsial; kini 22 migration
  Ran, 137 tabel, 393 FK dan 331 CHECK. Data domain yang sudah terhapus tidak
  otomatis kembali. Audit dokumentasi ini hanya membaca metadata.
- Menambah fitur/migration memakai `php artisan migrate`. `migrate:rollback`
  membatalkan batch terakhir, bukan file yang sedang dibuka. GuardedMigrator
  mencegah batch downgrade identitas saat email NULL sebelum tabel dihapus;
  ini pengaman kasus spesifik, bukan pemulihan data atau jaminan semua rollback.
- Data baseline tidak dimasukkan lewat migrasi (pelajaran DEC: pakai seeder
  onboarding seperti `BusinessDecisionSeeder`).
- Otorisasi selalu diperiksa server di setiap baca/tulis; tombol disembunyikan
  hanya pelengkap (Bab II 2.3).
- Satu `h1` per halaman, kontrol `h-11`, komponen shadcn + token warna,
  tab memakai pola Bar tab pada design-system terkini v1.11 — tidak ada hex/komponen baru tanpa alasan.
- Fitur bertanda "nonaktif" di SRS (disposisi, mode absensi pertemuan/both,
  co-teaching, akun siswa, dsb.) tetap nonaktif; aktifkan hanya lewat revisi
  DEC yang disetujui.
