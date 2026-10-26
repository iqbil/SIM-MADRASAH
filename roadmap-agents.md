# Roadmap Modul SIM Madrasah — MTs Al Karimiyyah (untuk AI Agent)

Peta runtutan modul yang **sudah** dan **harus** dikerjakan, mengikuti seksi menu
sidebar (`frontend/lib/navigation/sidebar.ts`) dan modul SRS v1.3
(`SRS_SIM_Madrasah_MTs_Al_Karimiyyah.txt`, FR-01…FR-17, Bab IX tabel tahap).
Dipelihara sebagai acuan kerja lintas sesi: setiap selesai satu modul, perbarui
status di file ini dan tambahkan catatan sesi di `log.txt`.

Terakhir diperbarui: **26 Oktober 2026** (modul Absensi Siswa tuntas penuh).

---

## A. Ringkasan status

| # | Modul (sidebar) | SRS | Status | Catatan |
|---|---|---|---|---|
| 1 | Autentikasi & pengguna (Beranda, Akun saya, Pengelolaan akun) | FR-01 | ✅ SELESAI | Login password/Google, JWT+BFF cookie HttpOnly, manajemen user/role/izin/audit |
| 2 | Data master (8 item) | FR-02 | ✅ SELESAI | Termasuk impor CSV + tab perbaikan (design-system v1.7) |
| 3 | Akademik & kelas (9 item aktif) | FR-03, FR-04 | ✅ SELESAI | Tahun ajaran s.d. jadwal pelajaran, guru pengganti, dan Kelas perwalian saya |
| 4 | Absensi pegawai | FR-05 | ✅ SELESAI | Foto+GPS mandiri, izin/sakit/cuti/dinas, pola kerja, koreksi, rekap |
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

### Urutan tahap awal pemakaian (MVP, diputakhirkan 26 Oktober 2026)
Berdasarkan SRS Bab IX dan §9.1 ("MVP tahap 1–2 dan laporan dasar; selesaikan
satu alur guru → jadwal → pertemuan → realisasi → verifikasi → honor locked"),
urutan pengerjaan modul sisa agar situs dapat dipakai nyata secepat mungkin:

**6 → 7 → 13 → 12 → 15\* → 11 → 16 → 8 → 9 → 10 → 14† → 15\*\***

- **#6 Absensi Siswa** — melengkapi kehadiran (fase 2); tanpa ini kesiswaan harian masih manual.
- **#7 Honor** — menutup irisan alur lengkap 9.1; setelah ini siklus operasional inti (guru→jadwal→realisasi→verifikasi→honor locked) berjalan end-to-end.
- **#13 Laporan & Ekspor** — "laporan dasar" MVP; rekap data #6+#7.
- **#12 Portal Wali** — butuh data #6; nilai adopsi tinggi.
- **#15\* PDP** — cukup bagian persetujuan publikasi foto siswa (prasyarat galeri website #11).
- **#11 Website → #16 Notifikasi** — publikasi + pemberitahuan kejadian yang sudah hidup.
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

4. **FR-05 Absensi pegawai** — foto+GPS mandiri, izin/sakit/cuti/dinas, pola kerja, koreksi, rekap.
5. **FR-06 Realisasi mengajar** — guru pelaksana (terjadwal/pengganti aktif)
   mencatat realisasi draf lalu mengajukan; isi immutable, revisi baru hanya
   sebelum diajukan/setelah ditolak; verifikasi oleh pemegang mandat
   realizations.verify yang berbeda dari pengaju, satu keputusan final per
   revisi; hanya held+approved yang masuk total JP. 8 rute /api/v1/teaching;
   halaman /realisasi/{pertemuan,riwayat,verifikasi}.
6. **FR-07 Absensi siswa** — mode harian per guru/wali kelas penugasan sebelum
   23.59 lokal (DEC-07, BR-08/09, AC-10); ketiadaan catatan ditampilkan sebagai
   belum dicatat (bukan alpa); rekap per tanggal & siswa; koreksi dengan alasan
   disetujui pemegang mandat `attendance.correct` yang berbeda dari pemohon;
   mengaktifkan item "Kelas perwalian saya" (`/akademik/perwalian`) dan tab
   "Koreksi siswa" pada persetujuan koreksi absensi (`/absensi/koreksi`).

Bukti: suite MySQL backend 154 test hijau (6953 asersi); frontend lint bersih + build bersih +
Playwright 82 test hijau; audit silang SRS/design-system/backend tercatat di
`log.txt`.

---

## C. Antrian modul berikutnya (urut kerja)

### 6) Absensi siswa — FR-07 ✅ SELESAI (26 Oktober 2026)
- **Item sidebar**: Absensi harian siswa (`/absensi-siswa/harian`); Rekap kehadiran siswa (`/absensi-siswa/rekap`); **mengaktifkan
  "Kelas perwalian saya"** (`/akademik/perwalian`) di seksi Akademik; tab "Koreksi siswa" di Persetujuan koreksi (`/absensi/koreksi`).
- **SRS**: FR-07-01…03, BR-08/09, DEC-07 (mode harian; 23.59 lokal; koreksi
  7 hari approval berbeda; "tidak ada baris ≠ alpa"; mode pertemuan/both
  nonaktif).
- **Tabel**: `attendance_policies` (baseline daily),
  `student_daily_attendances` (UK siswa+tanggal), `student_meeting_attendances`
  (nonaktif), `attendance_corrections`.

### 7) Honor & keuangan — FR-08 ⬜ BERIKUTNYA
- **Item sidebar**: Honor final saya; Tarif & komponen; Tugas tambahan &
  potongan; Periode & perhitungan; Pemeriksaan; Pengesahan & penguncian;
  Penyesuaian.
- **SRS**: FR-08-01…, BR-10…15, DEC-01…06/08; tiga akun berbeda
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
   database.MD` (tabel & aturan) + `design-system.md` (terkini v1.7) + sesi
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

- DB operasional `sim_madrasah` dan `.env` tidak disentuh; uji hanya pada
  `sim_madrasah_test` (`DB_PASSWORD="$(grep ^DB_PASSWORD= .env | cut -d= -f2-)"`
  di-export saat menjalankan phpunit.mysql.xml).
- Data baseline tidak dimasukkan lewat migrasi (pelajaran DEC: pakai seeder
  onboarding seperti `BusinessDecisionSeeder`).
- Otorisasi selalu diperiksa server di setiap baca/tulis; tombol disembunyikan
  hanya pelengkap (Bab II 2.3).
- Satu `h1` per halaman, kontrol `h-11`, komponen shadcn + token warna,
  tab memakai pola Bar tab v1.7 — tidak ada hex/komponen baru tanpa alasan.
- Fitur bertanda "nonaktif" di SRS (disposisi, mode absensi pertemuan/both,
  co-teaching, akun siswa, dsb.) tetap nonaktif; aktifkan hanya lewat revisi
  DEC yang disetujui.
