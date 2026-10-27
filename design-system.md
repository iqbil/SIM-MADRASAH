# Design System — SIM Madrasah MTs Al Karimiyyah

Versi 1.11 · 27 Oktober 2026 · Berlaku untuk seluruh `frontend/`.

Dokumen ini menjadi pedoman implementasi UI/UX dari autentikasi hingga seluruh modul madrasah. Perubahan berikutnya wajib mengacu ke pedoman ini. Ketentuan bisnis, otorisasi, dan data mengikuti [SRS v1.4](SRS_SIM_Madrasah_MTs_Al_Karimiyyah.txt) serta kontrak API; design system mengatur cara pengguna berinteraksi dengan ketentuan tersebut. Status modul mengikuti [roadmap](roadmap-agents.md). Revisi 1.11 mencocokkan pedoman dengan source UI yang sudah tersedia, tanpa mengubah kode aplikasi.

## 1. Prinsip dan aturan wajib

1. **Mobile menjadi prioritas.** Semua alur utama dapat diselesaikan pada lebar 320 px, menggunakan sentuhan dan keyboard, tanpa menggulir seluruh halaman secara horizontal.
2. **Komponen interaktif wajib shadcn/ui.** Gunakan komponen lokal `frontend/components/ui/`. Komponen baru ditambahkan melalui registry shadcn dengan style `radix-nova`, lalu disesuaikan dengan token proyek. Elemen semantik seperti `main`, `section`, `form`, `fieldset`, dan `dl` tetap menggunakan HTML. Leaflet merupakan pengecualian untuk interaksi peta/native zoom; bungkus dengan komponen fitur dan ikuti ukuran, fokus, token serta alternatif input manual pada §10.2.
3. **Satu identitas visual.** Warna hijau madrasah, latar netral hangat, kartu putih, tipografi Geist, ikon Lucide, dan ruang kosong yang cukup. Warna tidak berubah menurut role.
4. **Pola interaksi seragam.** Aksi yang sama memakai label, urutan, indikator proses, modal, dan pesan hasil yang sama di semua modul.
5. **Tampilkan keadaan yang sebenarnya.** Angka berasal dari API; bedakan memuat, kosong, gagal, dan tidak berizin. Loading tidak ditampilkan sebagai angka nol.
6. **Akses mengikuti izin.** Sidebar dan tindakan mengikuti role/permission yang diberikan. Penyembunyian tombol bukan pengganti pemeriksaan izin di backend.
7. **Perbaiki penyebab error.** Peringatan hydration/prerender harus diperiksa, bukan disembunyikan secara global.
8. **Gunakan ulang sebelum membuat.** Jangan membuat komponen baru jika komponen yang ada dapat memenuhi kebutuhan melalui komposisi, props, atau variant yang wajar. Aturan ini berlaku untuk komponen dasar, layout, form, modal, notifikasi, dan komponen fitur, sejak halaman pertama sampai pengembangan sistem selesai.
9. **Halaman baru mengikuti halaman yang sudah ada.** Modul dan role dapat berbeda isi serta hak aksesnya, tetapi memakai shell, token, ukuran kontrol, pola form, dialog, tabel, dan keadaan UI yang sama. Jangan membuat identitas visual atau kumpulan komponen dasar tersendiri per modul/role.

**Cara membaca:** tabel sumber dan bagian “implementasi saat ini” mendeskripsikan source yang ada. Aturan wajib/checklist adalah target untuk halaman baru dan komponen yang diubah, bukan klaim seluruh halaman telah lulus semua pemeriksaan. Status tes adalah bukti sesuai sesi, bukan sertifikasi aksesibilitas/UAT.

Pedoman berlaku langsung untuk halaman baru dan komponen yang diubah. Bagian lama yang belum memenuhi ukuran teks atau kontrol di bawah ini harus diselaraskan saat dikerjakan; dokumen ini tidak menyatakan seluruh tampilan lama sudah memenuhi semua kriteria aksesibilitas.

## 2. Sumber implementasi

| Kebutuhan | Sumber utama |
| --- | --- |
| Warna, radius, font, animasi | `frontend/app/globals.css` |
| Konfigurasi shadcn | `frontend/components.json` |
| Komponen dasar | `frontend/components/ui/` |
| Font, tooltip, notifikasi global | `frontend/app/layout.tsx` |
| Layout portal dan navigasi | `frontend/components/layout/app-shell.tsx` |
| Navbar dan menu akun | `frontend/components/layout/app-header.tsx` |
| Kelompok/menu sidebar | `frontend/components/layout/sidebar-navigation.tsx`, `frontend/lib/navigation/sidebar.ts` |
| Akses navigasi dari backend | `backend/app/Services/NavigationAccess.php` → `AuthUserResource.navigation` |
| Layout login/pemulihan/password | `frontend/components/auth/auth-frame.tsx` |
| State kosong dashboard | `frontend/components/dashboard/empty-dashboard.tsx` |
| Loading halaman | `frontend/components/layout/page-skeleton.tsx` |
| Titik/radius absensi | `frontend/components/attendance/attendance-policy-dialog.tsx`, `frontend/components/attendance/attendance-geofence-map.tsx`, `frontend/lib/geofence.ts` |
| Loading/error data lintas fitur | `frontend/components/layout/data-feedback.tsx` (`DataLoading`, `DataFailure`); alias master dipertahankan |
| Breakpoint sidebar | `frontend/hooks/use-mobile.ts` |
| Referensi form akun, role, konfirmasi | `frontend/components/accounts/account-workspace.tsx` |
| Dialog form lintas fitur | `frontend/components/layout/form-dialog.tsx` |
| Konfirmasi tindakan lintas fitur | `frontend/components/layout/confirmation-dialog.tsx` |
| Pagination dan alasan perubahan | `frontend/components/layout/pager.tsx`, `frontend/components/layout/reason-field.tsx` |
| Data master, form domain, relasi dan impor | `frontend/components/master/`, `frontend/lib/master-data.ts` |
| Modul Akademik & Kelas (periode, rombel, keanggotaan/mutasi, kalender, penugasan, jadwal/pertemuan, pengganti, perwalian) | `frontend/components/academic/`, `frontend/lib/academic.ts`, BFF `frontend/app/api/academic/` |
| Request sesi dan loading data bersama | `frontend/lib/api-client.ts`, `frontend/hooks/use-async-data.ts`, `frontend/hooks/use-dialog-focus.ts` |
| Heading/daftar/form/filter lintas modul | `frontend/components/academic/academic-shared.tsx` — dipakai akademik, absensi, realisasi dan siswa |
| Masuk/pulang, riwayat, status petugas, pola, rekap dan koreksi | `frontend/components/attendance/`, `frontend/lib/attendance.ts`, BFF `frontend/app/api/attendance/[...path]/route.ts` |
| Realisasi/pengajuan dan verifikasi | `frontend/components/teaching/`, `frontend/lib/teaching.ts`, BFF `frontend/app/api/teaching/[...path]/route.ts` |
| Absensi harian dan rekap siswa | `frontend/components/student-attendance/`, `frontend/lib/student-attendance.ts`, BFF `frontend/app/api/student-attendance/[...path]/route.ts` |
| Regresi UI/responsif/hydration | `frontend/tests/e2e/`, `frontend/playwright.config.ts` |
| Nama role dan tujuan ruang kerja | `frontend/lib/auth/roles.ts` |

Alur pemilihan komponen: periksa komponen lokal → cari komponen shadcn yang sesuai → tambahkan jika belum tersedia → susun menjadi komponen fitur. Jangan membuat dialog, dropdown, checkbox, atau toast sendiri dengan `div` dan event handler.

Kustomisasi umum dilakukan pada komponen bersama; halaman hanya mengatur isi dan susunan. Jangan menyalin satu komponen dasar ke setiap modul. Saat menambahkan komponen melalui CLI, jangan menimpa komponen lokal yang sudah disesuaikan tanpa meninjau perbedaannya.

### 2.1 Urutan wajib sebelum membuat komponen

1. Baca dokumen ini dan buka halaman/komponen terdekat yang sudah menerapkan alur serupa. Periksa source serta props-nya, bukan hanya nama file atau screenshot.
2. Cari implementasi yang tersedia di `components/ui`, `components/layout`, `components/auth`, dan direktori fitur terkait. Gunakan pencarian file dan ekspor sebelum membuat file baru, misalnya dari direktori `frontend`:

   ```bash
   rg --files components
   rg -n 'export (function|const)|function (Pager|ActionDialog|UserEditor|RoleEditor)' components
   ```

3. **Gunakan langsung** jika komponen sudah sesuai. Untuk perbedaan isi, susun komponen yang ada melalui `children`, props, atau slot; jangan menyalin source-nya.
4. **Perluas komponen yang ada** jika perbedaannya merupakan variasi dari fungsi yang sama. Pertahankan API yang jelas dan kompatibilitas pemanggil sebelumnya; periksa seluruh pemanggil yang terdampak. Perbaikan ukuran, fokus, atau state bersama dilakukan pada sumber bersama, bukan dengan override berbeda-beda di setiap halaman.
5. **Ekstrak pola yang berulang** jika kebutuhan kedua berada dalam fungsi privat halaman/fitur lama. Pindahkan bagian tampilan yang sama ke komponen bersama dan gunakan kembali pada kedua tempat. Validasi, endpoint, mandat, dan aturan bisnis tetap pada domain masing-masing. Jangan membuat salinan `Pager`, dialog konfirmasi, atau field berlabel hanya karena implementasi sebelumnya belum diekspor.
6. **Buat komponen baru hanya bila langkah sebelumnya tidak cocok.** Untuk primitive interaktif yang belum ada, ambil komponen shadcn yang sesuai dan sesuaikan dengan token proyek. Untuk komponen fitur, susun dari primitive lokal. Jelaskan kebutuhan yang belum tertampung dan komponen yang telah diperiksa dalam ringkasan perubahan; tidak perlu membuat laporan baru untuk setiap komponen.
7. Catat komponen bersama baru pada tabel sumber/inventaris ini, lalu periksa lint, tipe/build, dan interaksi yang relevan bila kode aplikasi berubah. Jika kebutuhan hanya dokumentasi, cukup verifikasi isi, tautan, dan path yang dirujuk.

Hindari abstraksi yang membutuhkan banyak flag untuk perilaku yang tidak berkaitan. Komponen khusus tetap boleh dibuat untuk kebutuhan domain yang berbeda; bagian tampilannya tetap memakai primitive dan pola bersama. Jangan menambah dependensi atau mengganti primitive shadcn yang sudah dipakai hanya untuk menghasilkan variasi tampilan lain.

### 2.2 Inventaris yang harus diperiksa lebih dahulu

| Kebutuhan | Komponen/pola yang tersedia | Cara penggunaan ulang |
| --- | --- | --- |
| Shell portal, sidebar, navbar | `AppShell`, `AppHeader`, `SidebarNavigation` | Gunakan layout portal yang sudah membungkus halaman; jangan memasang shell/navbar kedua di setiap page. |
| Kerangka autentikasi | `AuthFrame` | Isi form berbeda melalui `children`; pertahankan latar dan susunan bersama. |
| Identitas madrasah | `Brand` | Gunakan variant/props yang tersedia; jangan membuat logo dan nama sekolah ulang pada halaman. |
| Input password dan tombol keluar | `PasswordInput`, `LogoutButton` | Gunakan perilaku tampil/sembunyi password serta alur keluar yang sudah ada. |
| Memuat shell/halaman | `ShellSkeleton`, `PageSkeleton`, primitive `Skeleton` | Gunakan fallback yang sesuai. Bila bentuk halaman berbeda, susun skeleton dari primitive yang ada tanpa membuat primitive loading kedua. |
| Dashboard awal kosong | `EmptyDashboard` dan primitive `Empty` | `EmptyDashboard` khusus konteks dashboard role; daftar kosong modul lain memakai Empty atau pesan inline sesuai pola source pada §9/§10; bedakan kosong, loading dan gagal. |
| Dialog, konfirmasi, detail | `Dialog`, `AlertDialog`, `Sheet`, `ScrollArea` | Pakai struktur pada §8. `UserEditor`, `RoleEditor`, dan `ActionDialog` tetap memuat aturan akun; kerangka tampilannya memakai `FormDialog`/`ConfirmationDialog` bersama. `ReasonField` menjadi kolom alasan lintas fitur. Jangan mengimpor workspace akun untuk menggunakan pola umum. |
| Daftar, status, pagination | `Table`, `Badge`, pola kartu mobile, dan `Pager` bersama di `components/layout/pager.tsx` | Pakai metadata halaman/jumlah hasil yang nyata dan nonaktifkan pagination saat pending. Jangan menyalin fungsi pagination ke modul lain. |
| Form dan notifikasi | `Label`, `Input`, `Textarea`, `Select`, `Checkbox`, `Button`, `Alert`, `Toaster` | Satu pola label/error/pending. `Toaster` hanya pada root layout, fitur memakai `toast` yang sama. |

Komponen fitur yang sudah dipakai lintas domain tetap berada pada lokasi berikut; jangan membuat salinan hanya untuk mengganti nama domain:

| Ekspor | Lokasi | Kontrak penggunaan |
| --- | --- | --- |
| `AcademicHeading` | `components/academic/academic-shared.tsx` | `category`, `title`, `description`, `action`; category harus sesuai pekerjaan (Akademik & kelas, Absensi pegawai, Realisasi mengajar, Absensi siswa). |
| `AcademicList` | File yang sama | `load(page)`, columns, judul/metadata/aksi baris, opsi create dan reloadKey; menggabungkan loading/error, refresh, tabel `md`, kartu mobile serta Pager. Data halaman dinormalisasi oleh `normalizeAcademicPage`. |
| `AcademicFormDialog` | File yang sama | FieldDefinition, alasan, submit async, current/expected_updated_at dan konflik; `extra` menerima ReactNode atau fungsi `(busy) => ReactNode` untuk field/peta tambahan. |
| `ConfirmAction` | File yang sama | Membungkus ConfirmationDialog dengan alasan dan aksi async; label/dampak mengikuti proses domain. |
| `FilterSelect`, `DateFilter` | File yang sama | Filter berlabel; Select shadcn mengubah opsi “Semua” menjadi nilai kosong. Jangan tampilkan sentinel `__all` kepada pengguna. |
| `MasterField`, `RecordPicker` | `components/master/master-fields.tsx` | Kontrol berdasarkan FieldDefinition dan pencarian referensi server; tetap gunakan input/label/error yang sama. |
| `AttendancePolicyDialog`, `AttendanceGeofenceMap` | `components/attendance/` | Editor policy melalui AcademicFormDialog; peta adalah field pendamping koordinat/radius, bukan shell/dialog baru. |

Path pada tabel inventaris komponen relatif terhadap `frontend/`; ini lokasi aktual, bukan instruksi memindahkan seluruh helper akademik ke folder baru.

Inventaris ini menyatakan komponen yang benar-benar ada saat revisi ini. Jangan menganggap nama pola sebagai komponen yang sudah diekspor, dan jangan mengimpor seluruh workspace akun untuk memakai satu bagian tampilan. Lokasi fitur tetap mengikuti domain; komponen lintas fitur ditempatkan di direktori bersama yang sudah tersedia dan sesuai fungsinya.

### 2.3 Halaman yang sudah mempunyai UI

| Area / SRS | Halaman atau pola aktif | Acuan UI |
| --- | --- | --- |
| Autentikasi & akun / FR-01 | `/login`, `/ganti-password`, `/sesi/perbarui`, `/akun`, `/pengguna`, `/dashboard/[role]` | AuthFrame, AccountWorkspace, AppShell dan EmptyDashboard. Dashboard role masih tampilan awal/navigasi. |
| Data master / FR-02 | `/data-master/[section]` | MasterWorkspace: delapan bagian, relasi, pencarian, form/detail dan impor. |
| Akademik & kelas / FR-03–04 | `/akademik/[section]`, termasuk jadwal, pengganti dan `/akademik/perwalian` | AcademicWorkspace, AcademicList/FormDialog dan HomeroomClass. |
| Absensi pegawai / FR-05 | `/absensi/{masuk-pulang,riwayat,pegawai,pola-kerja,rekap,koreksi}` | AttendanceClock/History/Records/Patterns/Recap/CorrectionRequests; editor policy/geofence pada pola-kerja. |
| Realisasi / FR-06 | `/realisasi/{pertemuan,riwayat,verifikasi}` | MyMeetings, MyRealizations, Verifications; daftar dan dialog bersama. |
| Absensi siswa / FR-07 | `/absensi-siswa/{harian,rekap}`, tab Koreksi siswa pada `/absensi/koreksi` | DailyAttendance, StudentRecap dan alur koreksi bersama. |

Halaman privat memakai requireUser/Suspense serta pemeriksaan navigation/permissions. URL langsung tanpa akses mengikuti guard halaman/backend, bukan hanya menyembunyikan menu. Kehadiran entri pada katalog sidebar tidak berarti halaman tersedia; FR-08–FR-17 tetap backlog sebagaimana roadmap. Kebutuhan tampilan honor, BK, publikasi, portal wali, laporan, privasi dan notifikasi in-app berikutnya adalah pedoman, bukan UI selesai. Toast Sonner adalah umpan balik aksi; belum merupakan pusat notifikasi FR-17.

## 3. Token visual

### Warna

Gunakan kelas semantik seperti `bg-background`, `bg-card`, `text-foreground`, `text-muted-foreground`, `border-border`, dan `text-primary`. Nilai berikut mencerminkan token yang sudah terpasang.

| Token CSS | Nilai saat ini | Pemakaian |
| --- | --- | --- |
| `--background` | `#f7f8f5` | Latar aplikasi |
| `--foreground` | `#22332d` | Teks utama |
| `--card`, `--popover` | Putih `oklch(1 0 0)` | Kartu, dialog, dropdown |
| `--primary` | `#1b5945` | Aksi utama, menu aktif, identitas madrasah |
| `--primary-foreground` | `#ffffff` | Teks pada hijau utama |
| `--secondary` | `#eaf0e7` | Badge dan permukaan sekunder |
| `--secondary-foreground` | `#224d3c` | Teks pada permukaan sekunder |
| `--muted` | `#eef1eb` | Area bantu dan input lembut |
| `--muted-foreground` | `#58645d` | Deskripsi dan metadata; kontras minimal 4,5:1 pada latar proyek |
| `--accent` | `#edf3eb` | Hover dan pilihan ringan |
| `--accent-foreground` | `#1b5945` | Teks pada accent |
| `--border` | `#e2e7df` | Pemisah dan batas kartu |
| `--input` | `#dce3d9` | Batas kontrol |
| `--ring` | `#59937a` | Fokus keyboard |
| `--destructive` | `oklch(0.577 0.245 27.325)` | Error dan tindakan berisiko |
| `--sidebar` | `#ffffff` | Permukaan navigasi |
| `--sidebar-foreground` | `#32463b` | Teks navigasi |
| `--card-foreground`, `--popover-foreground` | `var(--foreground)` | Teks kartu/dialog/menu |
| `--sidebar-primary`, `--sidebar-primary-foreground` | `#1b5945`, `#ffffff` | Aksi/identitas sidebar |
| `--sidebar-accent`, `--sidebar-accent-foreground` | `#eef4eb`, `#1b5945` | Pilihan/hover sidebar |
| `--sidebar-border`, `--sidebar-ring` | `#e2e7df`, `#59937a` | Batas dan fokus sidebar |

Hijau ringan menggunakan turunan token, misalnya `bg-primary/5` dan `border-primary/10`. Hindari warna hex baru di halaman. Pengecualian terbatas untuk identitas resmi pihak ketiga, gambar, dan overlay foto autentikasi. Jika dibutuhkan warna semantik sukses/peringatan/informasi pada modul baru, definisikan token bersama dan dokumentasikan sebelum dipakai; jangan menambahkan palet berbeda di setiap modul.

Status selalu memiliki label teks. Contoh: `Aktif`, `Nonaktif`, `Terverifikasi`, `Belum terverifikasi`; warna atau ikon saja tidak cukup. Grafik mendatang harus memakai seri yang dapat dibedakan, label/legenda, serta alternatif tekstual; token grafik netral `--chart-1`…`--chart-5` saat ini belum menjadi palet final untuk grafik data; belum ada dashboard grafik lintas modul.

Tema yang aktif saat ini **light**. Sonner juga menggunakan tema light. Dark mode baru boleh ditambahkan bersama token lengkap, provider, dan pengujian; jangan mengikuti preferensi OS hanya pada sebagian komponen.

### Tipografi

- Geist untuk seluruh UI; Geist Mono untuk kode/identitas teknis pendek yang memang perlu dibaca pengguna.
- Satu `h1` per halaman. Gunakan `h2` untuk bagian dan `h3` untuk subbagian, meskipun teks dibungkus `CardTitle` atau `EmptyTitle`.
- Judul halaman: 24–28 px pada mobile, 30–32 px pada layar lebar, `font-semibold`, `tracking-tight`. AcademicHeading saat ini memakai `text-[27px] sm:text-3xl`, category `text-xs`, deskripsi `text-sm leading-6 max-w-2xl`. Gunakan category prop agar konteks domain benar.
- Judul kartu/dialog: 14–16 px, `font-medium` atau `font-semibold`.
- Isi utama dan label: 14–16 px, tinggi baris 1,5–1,75. Deskripsi panjang tidak dipaksa menjadi huruf kecil.
- Metadata sekunder: minimal 12 px untuk pengembangan baru. Teks 10–11 px yang masih ada merupakan bagian lama yang perlu diperbaiki bertahap.
- Input mobile: minimal 16 px untuk mencegah zoom saat fokus. Jangan mengecilkan input agar form muat.
- Nama, email, alasan, serta label role yang panjang harus membungkus (`break-words`, `break-all` untuk email/kode, `min-w-0`). Pemotongan teks tidak boleh menghilangkan informasi penting tanpa akses ke detail lengkap.

### Spacing, radius, dan elevasi

Gunakan satuan kelipatan 4 px: 4, 8, 12, 16, 20, 24, 32, 40, 48. Jarak antarkelompok umumnya 24 px; label–input 8 px; antarfield 16 px; isi kartu 16–24 px.

`--radius` bernilai 12 px. Turunannya pada tema proyek:

| Kelas | Radius |
| --- | --- |
| `rounded-sm` | 7,2 px |
| `rounded-md` | 9,6 px |
| `rounded-lg` | 12 px |
| `rounded-xl` | 16,8 px |
| `rounded-2xl` | 21,6 px |
| `rounded-3xl` | 26,4 px |
| `rounded-4xl` | 31,2 px |

Input/tombol memakai `rounded-lg`; kartu, dialog, dan bagian dashboard memakai `rounded-xl`/`rounded-2xl`; avatar dan badge dapat membulat penuh. Lengkung 32 px pada panel login mobile adalah pengecualian identitas autentikasi.

Kartu daftar/ringkasan domain saat ini umumnya memakai `rounded-2xl border border-border/70 py-0 ring-0` dengan isi `p-4 sm:p-5` (kamera dapat `sm:p-6`). Kartu utama cukup memakai border lembut, tanpa bayangan berat. Dropdown memakai bayangan ringan; dialog/sheet memakai bayangan lebih kuat dan overlay gelap. Hindari border ganda akibat menambahkan ring dan border tanpa meninjau komponen dasar.

## 4. Layout dan responsivitas

| Ukuran | Perilaku |
| --- | --- |
| 320–639 px | Satu kolom; padding utama 16 px; form dan tombol mudah dijangkau |
| 640–767 px (`sm`) | Padding 24 px; dua kolom hanya jika isi tetap terbaca |
| 768–1023 px (`md`) | Tablet; menu tetap drawer; tabel boleh muncul jika muat atau punya area gulir sendiri |
| ≥1024 px (`lg`) | Sidebar desktop; padding utama 32 px |
| ≥1280 px (`xl`) | Padding 40 px; bagian dashboard dapat berdampingan |
| ≥1536 px (`2xl`) | Pertahankan batas lebar konten, jangan memperlebar teks tanpa batas |

Breakpoint drawer sidebar adalah **1024 px**, sama pada CSS `lg:` dan `use-mobile.ts`. Jangan mengubah hanya salah satunya. Breakpoint tabel dapat berbeda karena mengikuti kebutuhan isi, bukan perangkat.

Layout portal menggunakan `SidebarProvider`, `Sidebar`, `SidebarInset`, dan satu elemen `main` untuk isi. Lebar sidebar desktop 256 px, mode ikon 72 px, drawer 288 px; drawer tidak boleh melampaui lebar viewport. Header sticky setinggi 64 px, menjadi 72 px di desktop. Konten maksimum 1440 px dan terpusat.

Aturan layout:

- Gunakan `min-w-0` pada anak flex/grid; kolom fleksibel memakai `minmax(0, 1fr)`.
- Overflow horizontal hanya boleh berada di komponen yang memerlukannya, seperti tabel. Jangan menutupi masalah layout dengan `overflow-x-hidden` pada seluruh halaman.
- Aksi utama dapat selebar kontainer pada mobile dan kembali selebar isinya pada desktop.
- Filter membungkus menjadi beberapa baris; pencarian mendapat satu baris penuh pada layar sempit.
- Navigasi aktif ditandai melalui style dan label yang jelas. Drawer menutup setelah memilih tujuan.
- Ukuran layar dibaca melalui hook yang aman untuk SSR. Kontrol navigasi tidak boleh aktif sebelum state viewport siap.
- Ukuran sentuh kontrol utama minimal **44 × 44 px**; gunakan `h-11`, `min-h-11`, atau `size-11`. Tombol autentikasi memakai tinggi 48 px.

### Navbar portal

Gunakan `AppHeader` pada seluruh halaman portal. Konten navbar mengikuti batas lebar 1440 px dan padding konten utama. Susunan kiri adalah tombol navigasi, nama madrasah, dan konteks halaman; kanan adalah role aktif dan menu akun. Gunakan landmark `nav` berlabel `Navigasi portal`.

- Pada mobile, tampilkan hamburger 44 px dan avatar akun 44 px. Nama sekolah/konteks tetap tersedia dengan truncation dalam area fleksibel.
- Mulai `md`, role aktif tampil sebagai badge setinggi 32 px dengan ikon dan label terpusat. Jangan menambah padding vertikal yang melampaui tinggi tetap badge.
- Mulai `lg`, tombol akun menampilkan nama pengguna dan keterangan singkat; nama panjang dipotong di navbar dan tersedia lengkap di dropdown.
- Menu akun memakai `DropdownMenu` shadcn, maksimum 288 px dengan margin viewport minimal 16 px. Nama/email/role panjang dapat membungkus. Fokus kembali ke pemicu saat menu ditutup.
- Tombol navigasi menyatakan `aria-expanded` sesuai drawer/sidebar aktif dan baru dapat digunakan setelah state viewport siap.
- Header tetap terjangkau saat halaman digulir. Jangan menambahkan kontrol pencarian/notifikasi yang belum memiliki fungsi.

### Menu sidebar dan modul yang belum tersedia

- Kelompok modul memakai `Collapsible` shadcn bersama komponen `Sidebar`. Kelompok dapat dibuka dengan keyboard dan pada mode ikon membuka sidebar desktop kembali; drawer tetap dapat digulir pada mobile/tablet.
- Menu selesai mencakup Data Master, Akademik/jadwal/pengganti/perwalian, Absensi pegawai, Realisasi dan Absensi siswa sesuai §2.3. Data Master aktif pada `/data-master/{section}` untuk delapan bagian katalog; `href` didefinisikan pada katalog sidebar yang sama. Menu operasional yang sudah tersedia tetap berupa `Link` dengan status aktif dan `aria-current`. Menu yang belum memiliki halaman berupa tombol **disabled**, tanpa `href`, dengan ikon jam dan nama aksesibel `Nama menu — Segera hadir`. Keterangan status tersedia di awal kelompok modul; jangan membuat tautan `#`, halaman kosong, atau toast saat menu diklik.
- Katalog tunggal berada di `lib/navigation/sidebar.ts`; hanya mencakup fitur baseline SRS. PPDB, akun siswa, ujian online, rapor, SPP, pembayaran digital, DOCX, disposisi, dan fitur lain yang nonaktif tidak ditampilkan.
- Visibilitas modul berasal dari `navigation` pada respons pengguna Laravel. Jika field tidak tersedia/kosong, sembunyikan modul. Menu akun memakai `accounts.view` dari `permissions`. Jangan menambahkan fallback yang memberikan akses berdasarkan nama role di frontend.
- Perubahan `navigation` juga diperiksa oleh `SessionMonitor` pada pemeriksaan sesi berkala (60 detik saat tab terlihat), `pageshow`, dan saat tab kembali terlihat; jika berubah, muat ulang konteks dashboard.
- Backend menggabungkan **semua role aktif**. Ruang kerja aktif dan cookie preferensi hanya mengubah konteks navigasi. Nama role tidak memberikan bypass untuk menu sensitif; role nonaktif, penugasan mendatang/kedaluwarsa/dicabut tidak dihitung.
- Absensi/honor pribadi memerlukan identitas pegawai aktif. Portal wali memerlukan role wali serta hubungan wali–siswa yang terverifikasi dan masih berlaku; tanggal hubungan mengikuti kalender `Asia/Jakarta`.
- BK internal/berbagi memerlukan role BK serta grant kategori aktif dengan tindakan yang sesuai. Arsip dokumen memerlukan grant kategori aktif. Verifikasi realisasi, pengelolaan/pemeriksaan/pengesahan honor, dan penerbitan mengikuti mandat eksplisit; kepala/Super Admin tidak mendapatkannya otomatis.
- `NavigationAccess` hanya mengatur visibilitas menu, **bukan otorisasi data**. Saat halaman/API domain dibuat, wajib memeriksa tindakan, kategori, penugasan, cakupan mandat, periode, dan objek pada setiap request, termasuk unduhan/ekspor. Ringkasan BK untuk penerima tidak membuka catatan internal.
- Izin role eksplisit `attendance.manage`, `attendance.correct`, `payroll.rates`, `questions.packages`, `questions.keys`, `reports.view`, dan `reports.export` juga dapat membuka menu terkait. Izin role biasa tidak menggantikan mandat verifikasi realisasi, perhitungan/pemeriksaan/pengesahan honor, atau penerbitan.
- Kunci mandat yang dikenali saat ini: `realizations.verify`, `attendance.manage`, `attendance.correct`, `payroll.calculate`, `payroll.review`, `payroll.approve`, `payroll.rates`, `payroll.adjust`, `questions.packages`, `questions.keys`, `publication.publish`, `reports.view`, `reports.export`. Kunci ini memetakan menu; tidak membuat grant/mandat baru secara otomatis. Pengelolaan mandat domain menjadi bagian implementasi modul berikutnya.

## 5. Peta komponen shadcn wajib

Ketentuan ini berlaku pada pembuatan awal, penambahan modul, perbaikan, dan refactor sampai sistem selesai. Semua kontrol interaktif aplikasi disusun dari komponen shadcn lokal, termasuk popup notifikasi, modal form, konfirmasi, dropdown, navigasi, dan kontrol filter. Jangan membuat versi native/custom di halaman lalu menjadwalkan penggantian ke shadcn belakangan. HTML semantik dan elemen media seperti `video` untuk kamera tetap boleh digunakan sesuai fungsinya; elemen tersebut bukan pengganti kontrol UI bersama.

| Kebutuhan | Komponen/pola |
| --- | --- |
| Aksi | `Button`; tautan aksi memakai `Button asChild` + `Link` |
| Form | `Input`, `Textarea`, `Label`, `Checkbox`, `Select` |
| Permukaan/ringkasan | `Card`, `CardHeader`, `CardContent`, `CardFooter` |
| Navigasi portal | `Sidebar`, `Sheet`, `DropdownMenu`, `Tabs` |
| Form/edit terfokus | `Dialog` |
| Konfirmasi tindakan sensitif | `AlertDialog` |
| Detail dari daftar | `Sheet` |
| Pesan di dalam halaman/form | `Alert`, `AlertDescription` |
| Notifikasi sementara | `Toaster` shadcn + `toast` dari `sonner` |
| Status dan identitas | `Badge`, `Avatar`, `AvatarFallback` |
| Memuat/kosong | `Skeleton`, `Empty` dan turunannya |
| Data tabular | `Table` dan turunannya; kartu/ringkasan daftar pada mobile |
| Katalog panjang | `ScrollArea` |
| Keterangan tambahan | `Tooltip`, dengan teks/label utama tetap tersedia |

Jangan memakai `window.alert`, `window.confirm`, toast buatan sendiri, atau modal `fixed` buatan halaman. `fixed` diperbolehkan dalam implementasi komponen dasar shadcn dan layout yang memerlukannya.

Ukuran bawaan beberapa komponen registry masih compact. Halaman aplikasi wajib memberi ukuran sentuh yang sesuai; jangan menganggap `size="default"` otomatis memiliki tinggi 44 px.

```tsx
import Link from "next/link";
import { Button } from "@/components/ui/button";

<Button type="submit" className="h-11" disabled={busy}>
  {busy ? "Menyimpan…" : "Simpan perubahan"}
</Button>
<Button asChild variant="outline" className="h-11">
  <Link href="/akun">Lihat akun saya</Link>
</Button>
```

Variant Button yang tersedia pada primitive lokal:

| Variant | Penggunaan dan tampilan aktual |
| --- | --- |
| `default` | Aksi utama; primary dengan teks primary-foreground. |
| `outline` | Aksi sekunder, filter/refresh/kelola, batal; border dan background aplikasi. |
| `secondary` | Aksi pendamping dengan permukaan secondary. |
| `ghost` | Aksi toolbar/menu yang tidak perlu border tetap. |
| `destructive` | Aksi berisiko; latar destructive/10 dan teks destructive, bukan merah penuh. Tetap jelaskan dampak dan gunakan konfirmasi bila diperlukan. |
| `link` | Aksi berwujud tautan dengan warna primary dan underline saat hover. Navigasi memakai Link/asChild. |

Ukuran primitive saat ini: Button default/icon 32 px, lg/icon-lg 36 px; Input default 32 px dengan teks 16 px mobile/14 px mulai md. Tinggi aplikasi berasal dari `h-11`/`size-11`, tombol auth/clock dari `h-12`; SelectTrigger dan Tabs pada pola tertentu memakai `h-11!`/`h-14!` untuk mengatasi tinggi registry. Jangan mengubah seluruh primitive hanya agar satu pemanggil muat. Warna richColors toast berasal dari Sonner; status domain tetap memakai Badge/token semantik, bukan menyalin warna toast sebagai palet baru.

Gunakan ikon Lucide berukuran 16–20 px, gaya stroke konsisten. Ikon tombol selalu mempunyai label teks atau `aria-label`; tooltip bukan pengganti nama aksesibel. Ikon dekoratif diberi `aria-hidden`.

## 6. Pola halaman dan navigasi

Susunan halaman modul: konteks/judul → deskripsi singkat → aksi utama → ringkasan relevan → filter/tabs → daftar atau state kosong → pagination. Tidak semua halaman memerlukan semua bagian.

- Satu aksi primer yang paling menonjol pada setiap konteks; tindakan sekunder memakai outline/ghost.
- Nama menu menjelaskan pekerjaan, misalnya `Pengelolaan akun`, bukan nama tabel database.
- Pergantian role hanya menawarkan role milik pengguna. Role aktif adalah preferensi navigasi, bukan pemberian izin.
- Dashboard awal memakai `EmptyDashboard`/`Empty`; ini bukan dashboard statistik lintas domain meskipun modul operasional sudah aktif. Tautan cepat hanya menuju fitur yang sudah tersedia dan boleh diakses.
- Halaman akun pribadi memakai informasi berlabel (`dl`) dan bagian keamanan terpisah.
- Login/password/pembaruan sesi memakai `AuthFrame`; panel identitasnya tidak disalin ke tiap halaman.
- Latar `AuthFrame` memakai ilustrasi lingkungan sekolah dengan overlay hijau tua; teks panel kiri bersifat faktual dan institusional (overline, garis emas `#e9d6a3`, judul skala sedang, daftar kemampuan portal) — tanpa tagline pemasaran, pill kaca, atau tipografi hero besar. Penggantian dengan foto asli madrasah dilakukan melalui aset bersama dengan pertimbangan beban muat (NFR-15).

Komponen fitur baru ditempatkan menurut domain yang sudah ada (`components/attendance`, `components/teaching`, `components/student-attendance`, dan seterusnya); komponen dasar tetap di `components/ui`. Jangan menaruh aturan bisnis domain ke dalam `Button`, `Dialog`, atau komponen UI dasar lainnya.

### Kontrak konsistensi antarhalaman

- Mulai dari layout dan pola halaman terdekat yang sudah ada. Gunakan urutan konteks/judul, deskripsi, aksi, filter, konten, dan pagination yang sama untuk pekerjaan sejenis; sesuaikan isi tanpa menyalin markup komponen bersama.
- Gunakan token warna/radius, breakpoint, padding, tinggi kontrol, tipografi, label status, dan variant tombol pada dokumen ini. Modul baru tidak menambahkan tema, font, warna hex, sidebar, atau pola modal sendiri.
- Form tambah/edit mengikuti pasangan label–kontrol–error yang sama; dialog mengikuti header/body/footer yang sama; daftar mengikuti pola kartu mobile/tabel dan pagination yang sama. Perbedaan ditentukan oleh kebutuhan data dan akses, bukan preferensi tampilan per halaman.
- Tangani loading, kosong, gagal, pending, sukses, tanpa izin, dan sesi habis dengan pola §9. Jangan membuat state sukses palsu atau tautan ke halaman modul yang belum tersedia.
- Uji halaman baru berdampingan dengan minimal satu halaman sejenis yang sudah ada. Jika komponen bersama berubah, periksa seluruh pemanggil yang relevan, termasuk login, dashboard, dan pengelolaan akun bila terdampak.
- Jika halaman lama menyimpang dari pedoman, gunakan pola kanonik dokumen ini dan perbaiki komponen bersama yang digunakan dalam cakupan perubahan. Jangan menyalin ketidakkonsistenan lama ke modul baru atau menyatakan semua halaman lama telah seragam tanpa pemeriksaan.

### Bar tab (Tabs)
Satu bar tab per halaman hanya untuk sub-cakupan dalam izin yang sama (mis. "Penugasan mengajar | Wali kelas", "Jabatan | Kategori pegawai", "Versi jadwal | Entri mingguan | Pertemuan").

- `TabsList` WAJIB `h-14! w-full justify-start gap-1 rounded-xl bg-secondary p-1 sm:w-auto` — token `bg-secondary`, bukan hex.
- `TabsList` bersama mengatur `align-self: flex-start` mulai `sm` untuk orientasi horizontal, agar `sm:w-auto` tidak melebar mengikuti parent flex. Lebar mobile tetap penuh dan tab vertikal tetap mengikuti layout-nya.
- `TabsTrigger` WAJIB `h-11! min-w-0 flex-1 px-3 text-xs sm:flex-none`; bila tab memuat ikon tambahkan `gap-1 px-2 text-xs` dan `sm:gap-2 sm:px-3 sm:text-xs`.
- Label panjang boleh membungkus pada mobile melalui `TabsTrigger` bersama (`whitespace-normal`, mulai `sm` menjadi `whitespace-nowrap`). Teks wajib tetap berada di dalam tombol masing-masing; uji batas teks, bukan hanya overflow halaman.
- Perilaku: mobile — tab membagi lebar penuh dalam satu baris; desktop — pill kompak rata kiri menghugging label. Dilarang `grid grid-cols-N` atau lebar tetap yang merentangkan tab setengah bar.
- Bila isi tab bergantung state lain (mis. versi jadwal terpilih), pakai `Tabs` terkontrol (`value`/`onValueChange`) agar seleksi tidak loncat saat dialog menutup.
- Referensi: `components/accounts/account-workspace.tsx`, `components/master/master-workspace.tsx`, `components/academic/academic-assignments.tsx`, `components/academic/academic-schedules.tsx`, `components/academic/academic-substitutes.tsx`.

### Pola Data Master

- Delapan bagian memakai `MasterWorkspace` dan shell portal yang sama. Jabatan/kategori serta referensi status menggunakan `Tabs`; daftar memakai kartu mobile dan tabel mulai `md`.
- `MasterEditor`, `MasterRelations`, dan `MasterImports` hanya memuat kebutuhan domain. Gunakan `FormDialog`, `ConfirmationDialog`, `ReasonField`, dan `Pager` bersama; jangan menggandakan kerangka tersebut pada fitur berikutnya.
- `MasterField` merender konfigurasi field menjadi kontrol shadcn, termasuk label, format, dan error field. `RecordPicker` mencari referensi di server dan mempertahankan pilihan; jangan memuat seluruh katalog tanpa batas.
- Muat detail terbaru sebelum edit. Kirim versi record dan identitas sesuai kontrak backend. Konflik tidak menimpa data: pertahankan isian, jelaskan masalah, dan sediakan tindakan memuat ulang secara eksplisit.
- Identitas orang terdaftar dapat digunakan ulang. Kandidat duplikasi ditampilkan untuk ditinjau; membuat orang yang berbeda dengan nama/tanggal lahir sama membutuhkan dasar pemeriksaan yang diaudit.
- Hubungan wali baru berstatus pending. Verifikasi memakai checkbox pemeriksaan identitas dan `ConfirmationDialog`; pencabutan serta akhir riwayat juga memakai konfirmasi, tanpa menumpuk overlay.
- Impor mengikuti template → unggah → pratinjau → konfirmasi → hasil. Tampilkan status antrean sebenarnya, baris valid/invalid, pagination pratinjau, dan unduhan laporan. Tombol konfirmasi tidak aktif ketika validasi belum selesai atau masih ada baris bermasalah.
- Upload dan unduhan melewati BFF privat. Token tetap pada cookie HttpOnly; kredensial backend tidak dikirim ke kode browser. Saat upload gagal, retry memakai operation key yang sama selama berkas/sumber belum berubah.

## 7. Form, validasi, dan tindakan

1. Setiap field mempunyai `Label` yang terhubung dengan `id`. Placeholder hanya memberi contoh, bukan menggantikan label.
2. Tandai field opsional dengan `(opsional)`; jelaskan format atau dampak sebelum submit.
3. Gunakan tipe input, `autoComplete`, dan `inputMode` yang sesuai. Password tidak dipangkas otomatis.
4. Error field ditempatkan di bawah field, terhubung lewat `aria-describedby`, dan ditandai `aria-invalid`. Error keseluruhan memakai `Alert` di dalam form.
5. Pertahankan isian saat API gagal. Fokus diarahkan ke field bermasalah atau ringkasan error yang dapat dijangkau.
6. Saat pending, tampilkan spinner/label kerja, nonaktifkan submit serta perubahan yang tidak aman, dan cegah submit ganda. Batal/tutup juga diblokir saat mutasi harus diselesaikan.
7. Tutup form setelah API menyatakan berhasil; perbarui daftar/detail dari respons yang benar. Jangan menampilkan toast sukses ketika request gagal.
8. Konflik versi data harus meminta pengguna memuat data terbaru, bukan menimpa perubahan orang lain secara diam-diam.
9. Alasan perubahan dan verifikasi identitas hanya diminta pada proses yang memang mensyaratkannya. Jangan mengubah aturan verifikasi backend demi mempersingkat form.

Label aksi menggunakan kata kerja spesifik: `Tambah pengguna`, `Buat akun`, `Simpan perubahan`, `Nonaktifkan akun`, `Cabut semua sesi`, `Batal`. Pada form akun, keterangan dampak perubahan email harus tetap terlihat sebelum menyimpan.

## 8. Dialog, konfirmasi, dan detail

### Dialog form

- Struktur: `DialogHeader` → form dengan **body yang bisa digulir** → `DialogFooter`.
- Header dan footer tetap terlihat; hanya isi form yang bergulir. Fieldset yang menonaktifkan field berada di dalam kontainer gulir agar tidak menutupi tombol.
- Batas tinggi `calc(100dvh - 2rem)`. FormDialog memakai maksimum 512 px (`max-w-lg`); ConfirmationDialog maksimum 448 px (`max-w-md`). Dialog bantuan ringkas memakai maksimum 384 px (`max-w-sm`). Lebar mengikuti primitive dialog dan margin viewport; FormDialog pada mobile menjadi panel bawah selebar layar.
- Form pada mobile dapat menjadi panel bawah selebar layar dengan sudut atas membulat. Tetap memakai `Dialog` shadcn, bukan overlay custom.
- Beri ruang untuk tombol tutup 44 px dan safe area perangkat. Gunakan `dvh` agar mengikuti perubahan viewport; tetap uji keyboard virtual pada perangkat nyata.
- Setiap dialog memiliki `DialogTitle` dan `DialogDescription` yang menjelaskan tujuan/dampaknya.
- Fokus terperangkap di dialog; Escape/klik luar bekerja untuk dialog biasa ketika tidak pending. FormDialog/ConfirmationDialog memakai useDialogFocus: kembali ke pemicu bila masih terpasang, fallback ke tombol aktif pada main bila pemicu hilang; jangan merebut fokus bila overlay lain masih terbuka. Error keseluruhan difokuskan melalui ref/tabIndex=-1. Pertahankan pemicu editor policy saat data diperbarui agar fokus tidak berpindah ke tab pertama.

### Alert Dialog

Reset password, penonaktifan akun, pemutusan Google, serta pencabutan sesi menggunakan `AlertDialog`. Tampilkan identitas target dan dampak; tindakan berisiko memakai variant destructive. Fokus awal pada Batal. Periksa checkbox identitas dan alasan jika kontrak API mensyaratkannya. Tombol submit async tidak boleh menutup dialog sebelum respons berhasil.

### Sheet detail

Gunakan `Sheet` untuk melihat detail tanpa kehilangan posisi daftar. Header tetap terlihat dan isi detail dapat digulir. Buka satu overlay aktif: tutup sheet sebelum membuka editor/konfirmasi. Jangan membuat beberapa focus trap bertumpuk.

Password sementara hanya muncul pada dialog khusus setelah sukses; saat dialog ditutup, hapus nilainya dari state. Jangan memasukkan password/token ke toast, URL, log, localStorage, atau screenshot data asli.

## 9. Notifikasi dan semua keadaan UI

`Toaster` hanya dipasang satu kali pada root layout. Fitur mengimpor `toast` dari `sonner`, bukan menambahkan Toaster baru. Konfigurasi bersama saat ini: light, bottom-right, durasi 5 detik, maksimum 3 toast, offset desktop 24 px, offset mobile 16 px dengan safe area, dan tombol tutup berlabel `Tutup notifikasi`.

| Keadaan | Perilaku yang harus terlihat |
| --- | --- |
| Sukses mutasi | Toast singkat, misalnya `Perubahan akun berhasil disimpan.` |
| Validasi gagal | Error field/`Alert` tetap berada di form; pengguna dapat memperbaiki isian |
| Request gagal | Pesan aman, data/isian tidak hilang, ada kesempatan mencoba kembali |
| Pending | Skeleton/spinner sesuai konteks; `aria-busy` bila relevan |
| Data benar-benar kosong | Judul, penjelasan dan aksi relevan jika diizinkan. Dashboard/master memakai `Empty`; AcademicList dan beberapa roster memakai pesan terpusat/border dashed, tanpa menggandakan primitive loading/error |
| Pencarian tanpa hasil | Jelaskan hasil pencarian kosong; beri cara mengubah/menghapus filter |
| Tanpa izin | Ikuti kebijakan route/backend; jangan menampilkan isi sensitif sesaat sebelum redirect |
| Sesi berakhir | Arahkan ke pemulihan sesi/login dengan pesan yang jelas |
| Koneksi terputus | Jangan menganggap mutasi berhasil; pertahankan isian yang aman |

Jangan menggunakan toast untuk informasi yang harus dibaca sebelum tindakan berisiko. Informasi tersebut ditempatkan di dialog/form. Toast tidak boleh berisi detail upstream, stack trace, token, atau data konseling/honor.

## 10. Daftar dan pola modul yang sudah diimplementasikan

- Daftar akun menggunakan kartu pada layar kecil, tabel mulai `md`. Modul lain memilih breakpoint menurut isi, dengan pola interaksi tetap sama.
- Identitas utama, status, dan tombol Kelola mudah ditemukan. Jangan memuat semua kolom desktop ke kartu mobile.
- Pagination menyatakan jumlah hasil dan posisi halaman. Tombol sebelumnya/berikutnya memiliki label aksesibel dan ukuran sentuh cukup.
- Pencarian/filter mereset halaman hasil bila diperlukan dan mempertahankan konteks saat memuat.
- Status memakai `Badge` dengan istilah konsisten. Jangan membedakan status melalui warna saja.
- Angka memakai format `id-ID`; mata uang rupiah memakai format yang sesuai. Waktu ditampilkan konsisten dengan zona `Asia/Jakarta`; tanggal lahir/tanggal kalender tidak boleh bergeser akibat konversi zona waktu.
- Singkatan internal, ID teknis, dan status API diterjemahkan menjadi istilah yang dimengerti pengguna.

### 10.1 Daftar, filter dan status domain

- AcademicList dipakai lintas akademik, pola/riwayat/status pegawai serta realisasi: tabel mulai `md`, ringkasan baris/kartu pada mobile, aksi refresh, DataLoading/DataFailure dan Pager. Jangan menganggap seluruh tabel sistem otomatis menjadi kartu; roster harian dan rekap siswa memakai tabel dengan gulir lokal agar kolom status tetap berhubungan dengan siswa/tanggalnya.
- Daftar/filter memakai FilterSelect/DateFilter dan kontrol 44 px; filter membungkus pada mobile. useAsyncData mempertahankan data terakhir dan menolak respons generasi lama; AcademicList mengabaikan loader yang sudah tidak aktif. Saat filter berubah, respons lama tidak boleh menimpa konteks baru.
- Jumlah hasil saat loading ditampilkan sebagai “Memuat…” atau placeholder, bukan angka nol. Saat memuat ulang policy yang sudah terlihat, tampilkan status pembaruan; nonaktifkan aksi yang bergantung versi terbaru.
- Istilah status sesuai domain, bukan palet baru: akademik `Direncanakan/Terbuka/Ditutup`; realisasi `Terlaksana/Tidak terlaksana` terpisah dari `Draf/Menunggu verifikasi/Disetujui/Ditolak`; absensi siswa `Hadir/Sakit/Izin/Alpa/Belum dicatat`. Badge sekunder/outline dan turunan primary dipakai bersama label. `StatusBadge` akademik hanya memetakan planned/open/closed, bukan penerjemah universal semua status API.
- Angka JP adalah hasil API; label total harus mengikuti filter dan status yang dihitung. Belum ada kalkulasi/halaman honor final; total JP realisasi bukan slip honor.

### 10.2 Absensi pegawai dan editor geofence

**Masuk/pulang:** AttendanceClock memakai dua panel, satu kolom di mobile dan `lg:grid-cols-2`: kamera/lokasi/aksi dan ringkasan hari ini. Video pratinjau `aspect-[4/3]`, kamera dinyalakan/dimatikan secara eksplisit, stream dihentikan saat komponen dilepas. Foto diambil ketika aksi dikirim; belum ada langkah dialog terpisah untuk pratinjau foto statis/ambil ulang sebelum submit. Jangan mendokumentasikan langkah tersebut sebagai fitur yang sudah tersedia.

Tampilkan kondisi kamera belum aktif/ditolak, GPS sedang diambil/gagal, akurasi lokasi dan muat ulang GPS, hari bukan kerja/libur, masuk/pulang berikutnya serta selesai. Aksi clock utama setinggi 48 px. Informasi lokasi memakai aria-live; di dalam radius memakai output, sedangkan luar radius, posisi belum pasti dan akurasi tidak cukup memakai Alert shadcn. Submit diblokir bila posisi belum memenuhi ketentuan, GPS dibaca ulang sebelum pengiriman dan API tetap memutuskan hasil akhir. Sukses hanya setelah respons sah; jika gagal, berikan petunjuk kamera/GPS atau hubungi petugas untuk fallback administratif. Tidak ada pelacakan latar belakang atau bukti foto/GPS sintetis.

**Kebijakan:** `/absensi/pola-kerja` memiliki tiga tab: Pola kerja, Penetapan & hari kerja, Kebijakan foto/GPS. Policy berlaku ditampilkan terpisah dari versi terjadwal; ringkasan memuat tanggal efektif, kewajiban foto/GPS, batas akurasi/kebaruan, kompresi, koreksi dan konfigurasi titik/radius. Tombol Versi kebijakan baru mengikuti `can_manage` dari API, bukan dugaan role di browser. Simpan membuat versi prospektif, bukan mengedit isi historis. UI mengirim expected_policy_id; konflik meminta muat ulang tanpa menimpa perubahan lain.

Editor AttendancePolicyDialog memakai AcademicFormDialog dengan field geofence melalui `extra(busy)`. Interaksi yang sudah tersedia:

1. Aktif/nonaktif batas radius, nama lokasi, latitude/longitude, radius 25–5.000 m dan tanggal efektif. Koordinat dapat diisi manual; tidak ada titik madrasah default yang ditebak.
2. Leaflet/OpenStreetMap diinisialisasi lewat import dalam effect browser. Tampilan awal Indonesia hanya pusat pandang peta; pin baru muncul setelah koordinat dipilih/diisi. Klik peta/geser pin memperbarui koordinat dan lingkaran radius; pilihan lokasi perangkat dilakukan eksplisit.
3. Tinggi peta `min(18rem, 40dvh)`, lebar penuh, panel `relative isolate` dan lapisan `z-0` agar kontrol peta tidak menutup header/footer dialog. Zoom native 44 × 44 px; atribusi minimum 12 px tetap terlihat. Pin dan lingkaran memakai token primary/card melalui globals.css. Pin kecil mempunyai alternatif input koordinat; jangan menganggap semua target peta otomatis berukuran 44 px.
4. Skeleton tampil sebelum peta siap. Gagal inisialisasi memakai DataFailure; gagal tile menampilkan pesan inline. Keduanya tidak menutup akses input manual. Jangan menjadikan keberhasilan latar peta syarat validasi koordinat server.
5. Saat busy, field/editor terkunci; wrapper map memakai inert. Footer tetap terjangkau di layar pendek dan pemicu dialog tetap terpasang ketika policy dimuat ulang, agar fokus kembali ke tombol yang benar.

**Status petugas/riwayat/koreksi:** pakai daftar/filter/dialog bersama. Bukti foto ditampilkan lewat rute privat kepada pihak yang berizin; jangan menyalin foto/koordinat ke toast atau log. Koreksi mempertahankan bukti asli dan menyatakan nilai sebelum/sesudah, alasan serta keputusan. Tab Koreksi pegawai/Koreksi siswa menjaga konteks jenis data; hak keputusan dan scope tetap berasal dari backend.

### 10.3 Realisasi mengajar

MyMeetings, MyRealizations dan Verifications memakai AcademicHeading dengan category Realisasi mengajar, AcademicList, filter periode/status serta AcademicFormDialog/ConfirmAction. Pertemuan menunjukkan pelaksana terjadwal/pengganti, status pelaksanaan dan status verifikasi secara terpisah. Alur pengguna: buat revisi → ajukan → keputusan oleh verifikator; jangan tampilkan isi yang sudah diajukan sebagai form bebas edit. Tampilkan alasan penolakan/konflik dari respons aman dan sediakan aksi sesuai state yang sah. Tombol/halaman verifikasi mengikuti navigation `teaching.verify`; role kepala/Super Admin bukan penanda bahwa pengguna otomatis berhak menyetujui.

### 10.4 Absensi siswa dan kelas perwalian

DailyAttendance memuat kelas penugasan dan tanggal, jumlah terisi/anggota, tenggat serta informasi koreksi. Setiap siswa tanpa catatan diberi label Belum dicatat; pilihan status atau tombol Hadir menjadi perubahan yang perlu dikirim, bukan hasil yang sudah tersimpan. Tombol `Simpan N siswa` mengikuti jumlah pilihan belum dicatat; data tersimpan memakai Ajukan koreksi. Filter kelas/tanggal diblokir saat penyimpanan agar target tidak berganti. Kamera/GPS pegawai tidak diterapkan pada siswa.

StudentRecap menampilkan rekap per tanggal dan per siswa dengan kolom Belum dicatat. Tidak ada kelas/tidak ada anggota/error adalah keadaan berbeda. `/akademik/perwalian` memakai HomeroomClass dan kelas wali aktif, bukan kelas mengajar pertama. Koreksi siswa masuk tab Koreksi siswa pada halaman koreksi pegawai/petugas yang sama, dengan pemisahan data dan pemeriksaan izin backend.

### 10.5 Pola untuk modul berikutnya

Honor, bank soal, BK, TU, portal wali, website, laporan/ekspor, pengaturan sistem, privasi dan notifikasi menggunakan shell/token/form/dialog/notifikasi aksi yang sama saat dikerjakan. UI bisnisnya belum tersedia; jangan membuat status selesai atau contoh angka operasional hanya karena tabel atau menu placeholder ada. Hak pengelolaan akun tetap tidak otomatis membuka data sensitif modul lain.

## 11. Aksesibilitas dan perilaku browser

- Satu `main`, skip link, urutan heading yang benar, label form, fokus terlihat, dan navigasi keyboard harus berfungsi.
- Target kontras: teks biasa minimal 4,5:1; teks besar serta komponen/grafik penting minimal 3:1 sesuai konteks. Uji kombinasi warna nyata, termasuk opacity, hover, dan error; jangan hanya menilai hex token di atas putih.
- Nama/status tidak disampaikan lewat warna saja. Teks tetap dapat dibaca saat zoom 200% dan layout diuji ulang saat ruang berkurang.
- Tooltip/popover harus dapat dibuka tanpa hover. Kontrol utama tidak bergantung pada tooltip.
- Animasi singkat dan fungsional; patuhi `prefers-reduced-motion`. Hindari animasi yang menunda interaksi.
- Tampilkan label foto ilustrasi; foto bukti absensi hanya dapat dilihat pengguna yang berizin.
- Uji resize, orientasi landscape, fokus input, dan keyboard virtual. Uji viewport otomatis tidak menggantikan pemeriksaan perangkat nyata.

Rujukan target kontras: [WCAG 2.2 — teks](https://www.w3.org/WAI/WCAG22/Understanding/contrast-minimum.html) dan [komponen/grafik](https://www.w3.org/WAI/WCAG22/Understanding/non-text-contrast.html).

## 12. SSR, hydration, dan diagnosis error

Next.js yang terpasang menggunakan Cache Components. Baca dokumentasi versi lokal di `frontend/node_modules/next/dist/docs/` sebelum mengubah perilaku rendering.

- Render awal server dan client harus sama. Jangan membaca `Date.now()`, `Math.random()`, `window`, atau localStorage langsung untuk menentukan markup awal.
- Pembacaan jam dinamis pada Server Component mengikuti pola `await io()` dari `next/cache` di dalam batas `Suspense`. Pembacaan sesi mengikuti helper server yang tersedia; jangan membuat cache lintas pengguna untuk data autentikasi.
- Halaman dinamis memiliki fallback yang bermakna. Jangan mematikan Cache Components atau seluruh SSR hanya untuk menyingkirkan overlay error.
- Gunakan hook media query bersama yang memiliki snapshot server stabil. Jangan memilih layout melalui `typeof window` saat render pertama.
- Perhatikan HTML valid: satu tombol tidak membungkus tombol lain; link tidak bersarang; `div` tidak ditempatkan di dalam `p`.
- `suppressHydrationWarning` bukan perbaikan umum dan tidak berlaku rekursif ke semua anak. Pengecualian harus sangat terbatas, disertai alasan serta pengujian.

### Atribut asing `bis_*` pada screenshot error

Screenshot dengan `bis_skin_checked`, `bis_register`, dan `__processed_*` menunjukkan HTML berubah sebelum hydration. Atribut tersebut tidak dibuat oleh komponen proyek; sumber spesifik browser harus dikonfirmasi dengan menonaktifkan ekstensi. Munculnya nama `AuthFrame` atau `AlertDescription` pada stack menunjukkan elemen yang berbeda, bukan bukti bahwa komponen tersebut penyebabnya.

Pada lingkungan pengembangan ini ditemukan **Urban VPN Proxy 5.14.4** (ID `eppiocemhmnlbhjplcgkofciiegomcon`) pada Brave Flatpak. Source `ad-blocker/content.js` menulis `bis_skin_checked` dan `bis_register`, dengan content script yang didaftarkan pada `document_start`. Gunakan temuan spesifik dan langkah di [README frontend](frontend/README.md#hydration-error-dengan-atribut-bis_) untuk menelusuri kasus ini. Jangan mengasumsikan satu nama produk untuk semua kasus atribut serupa.

Langkah penanganan:

1. Buka alamat dan query yang sama, misalnya `/login?error=google-failed`, pada profil browser baru tanpa ekstensi. Incognito hanya membantu jika ekstensi tidak diizinkan berjalan di sana.
2. Jika bersih, nonaktifkan akses ekstensi yang memodifikasi halaman untuk origin development (contoh `http://localhost:3000` dan `http://127.0.0.1:3000`). Aktifkan satu per satu untuk menentukan sumbernya.
3. Muat ulang penuh dengan `Ctrl+Shift+R`. Jika HTML masih berubah, periksa fitur pemindaian web browser/ekstensi yang aktif dan bandingkan respons Network/View Source dengan DOM pada Elements.
4. Jika profil bersih tetap bermasalah, periksa perbedaan SSR/client dan HTML tidak valid; jalankan tes hydration sebelum mengubah kode.

Tidak ada perubahan kode aplikasi yang dapat menjamin ekstensi eksternal tidak mengubah DOM lagi. Jangan menyuntikkan atribut `bis_*` ke SSR, membuat pembersih seluruh DOM, atau menambahkan suppression pada setiap elemen untuk menyamarkan kasus ini. Pesan `Login Google belum berhasil` adalah status autentikasi terpisah dari hydration; diagnosis OAuth tetap mengikuti respons backend dan konfigurasi akun.

Referensi: [Next.js — hydration error](https://nextjs.org/docs/messages/react-hydration-error), dokumentasi lokal `io`, `connection`, dan `authentication-with-cache-components`.

## 13. Pemeriksaan sebelum halaman dinyatakan selesai

- [ ] Pencarian komponen yang ada telah dilakukan; reuse, komposisi, atau perluasan dipilih sebelum membuat komponen baru.
- [ ] Tidak ada salinan primitive/layout/modal/notifikasi/pagination yang sudah tersedia. Alasan komponen baru dijelaskan dan inventaris bersama diperbarui bila relevan.
- [ ] Menggunakan komponen shadcn lokal dan token proyek; tidak menambahkan modal/toast custom.
- [ ] Memakai shell yang sesuai, heading semantik, dan satu aksi utama yang jelas.
- [ ] Dibandingkan dengan halaman sejenis yang sudah ada; susunan, jarak, ukuran kontrol, status, form, dan tindakan konsisten.
- [ ] Bisa dipakai pada 320, 390, 768, 1024, dan 1440 px; periksa pula layar pendek/landscape.
- [ ] Tidak ada overflow horizontal halaman, teks terpotong yang penting, atau tombol di luar viewport.
- [ ] Isi modal bisa digulir dan tombol tindakan tetap terjangkau.
- [ ] Keyboard, fokus, Escape, label, error, dan ukuran sentuh berfungsi.
- [ ] Loading, kosong, error, sukses, tanpa izin, dan sesi habis ditangani sesuai kebutuhan.
- [ ] Tidak ada error hydration/prerender pada browser bersih, termasuk halaman dengan query error/notice.
- [ ] Tidak ada data sensitif di URL, log, toast, atau screenshot.
- [ ] Lint lolos; jalankan build bila kode aplikasi berubah dan tes interaksi yang relevan.
- [ ] Perubahan pola/token bersama telah diperbarui dalam dokumen ini dan diperiksa pada pemanggil yang terdampak: autentikasi/akun/master serta akademik/absensi/realisasi/siswa bila memakai komponen yang diubah.
- [ ] Bila menyentuh policy/map: manual input tetap berfungsi ketika tile gagal, can_manage dihormati, busy mengunci map, footer/fokus/tab tidak bergeser dan alert radius tidak hanya mengandalkan warna.

Perintah dari folder `frontend`:

```bash
pnpm lint
pnpm build
pnpm exec playwright test # setelah pnpm build; default server memakai next start
NEXT_TEST_DEV=1 pnpm exec playwright test tests/e2e/hydration.spec.ts
```

Tes Playwright memakai API fixture terisolasi; tidak membuktikan integrasi OAuth Google nyata. Uji integrasi backend/OAuth dilakukan terpisah tanpa membagikan kredensial. Jika Chromium belum terpasang, siapkan browser Playwright atau arahkan `PLAYWRIGHT_CHROMIUM_EXECUTABLE_PATH` ke executable yang tersedia.

### 13.1 Bukti yang tersedia dan batas pemeriksaan

Tes UI berada di `frontend/tests/e2e`: auth/accounts/navbar/sidebar/master-data/academic/academic-schedule/attendance/teaching/student-attendance/geofence/responsive/hydration. Tes responsif mencakup 320, 390, 768, 1024 dan 1440 px serta layar pendek. Tes geofence menguji map/manual input, kebijakan terjadwal, can_manage, fokus/footer/tab dan penolakan lokasi di UI. Hydration dijalankan juga dalam mode development pada profil terkontrol.

Bukti sesi implementasi 27 Oktober di [log.txt](log.txt): E2E penuh 94/94, hydration development 6/6, regresi layout geofence 9/9, Biome 150 file bersih dan build 38/38 halaman. Angka tersebut bukan hasil eksekusi baru untuk revisi dokumentasi 1.11. Kamera/GPS/API/tile memakai fixture terkontrol; ini tidak membuktikan perangkat lapangan, jaringan/OS/browser nyata, seluruh kontras, pembaca layar atau UAT.

Batas implementasi yang perlu tetap terlihat:

- Primitive registry Button/Input/Select/Tabs masih memiliki ukuran compact; kelas 44 px berasal dari pemanggil/pola bersama. Jangan menyatakan ukuran primitive default sudah memenuhi target sentuh aplikasi.
- Metadata/actions beberapa fitur memakai 12 px; teks isi utama 14–16 px tetap target pedoman. Kelengkapan error aria-describedby/aria-invalid, fokus field dan kontras setiap state harus diperiksa pada form terkait, bukan dianggap otomatis dari shadcn.
- Beberapa daftar kosong memakai pesan inline/berbatas, bukan komponen Empty; tabel siswa memakai gulir lokal. Ini pola source aktual, bukan alasan membuat primitive feedback baru.
- Katalog API role lebih luas daripada pilihan UI saat ini. AccountWorkspace memfilter dengan permissions profil; sebagian izin absensi/verifikasi dan administrasi mandat belum tersedia di editor. Daftar/tombol UI tidak menjadi sumber otorisasi.
- Dark mode, pusat notifikasi in-app, dashboard grafik/laporan lintas modul, pratinjau foto statis sebelum clock dan UI bisnis FR-08–FR-17 belum merupakan fitur tersedia.

## 14. Perubahan dan pemeliharaan

Setiap penambahan halaman dimulai dengan memeriksa komponen bersama dan bagian dokumen yang relevan. Pola baru harus memiliki alasan, komponen tempat implementasinya, state yang didukung, serta bukti pengujian pada ukuran layar yang ditetapkan.

Perubahan token/layout tidak dilakukan pada satu halaman saja. Perbarui sumber bersama, periksa halaman yang terdampak, dan catat perubahan pada riwayat di bawah. Jika implementasi dan dokumen berbeda, selaraskan keduanya dalam pekerjaan yang sama; jangan membuat variasi baru tanpa dokumentasi. Instruksi pengguna dan kebutuhan bisnis yang disepakati tetap menjadi acuan saat mengubah pedoman.

| Versi | Tanggal | Perubahan |
| --- | --- | --- |
| 1.0 | 20 Oktober 2026 | Standar shadcn wajib, token, layout responsif, form/modal/notifikasi, aksesibilitas, SSR/hydration, dan pemeriksaan modul baru berdasarkan frontend saat ini. |
| 1.1 | 20 Oktober 2026 | Navbar bersama, ukuran badge role, kontrol mobile, menu akun responsif, dan pengujian nama panjang serta akses keyboard. |
| 1.2 | 20 Oktober 2026 | Katalog sidebar baseline SRS, kelompok Collapsible shadcn, status menu belum tersedia, dan akses navigasi dari backend berdasarkan role serta mandat/grant aktif. |
| 1.3 | 20 Oktober 2026 | Aturan wajib reuse sebelum membuat komponen, inventaris sumber yang tersedia, ekstraksi pola lintas fitur, kontrak konsistensi antarhalaman, dan kewajiban shadcn sepanjang pengembangan. |
| 1.4 | 20 Oktober 2026 | Halaman Data Master aktif, kerangka form/konfirmasi, alasan dan pagination bersama yang digunakan akun serta master, pencarian referensi, konflik versi, dan alur pratinjau impor. |
| 1.5 | 24 Oktober 2026 | Tipografi panel auth desktop diganti konten institusional faktual (menggantikan tagline pemasaran/pill kaca/hero clamp); latar foto ilustrasi + overlay dipertahankan; tautan "Lupa password?" dirapatkan; © dipindah ke dasar panel kiri. |
| 1.6 | 24 Oktober 2026 | Modul Akademik & Kelas aktif: menu sidebar granular `academic.*.view` dengan href `/akademik/[section]`; komponen `components/academic/` memakai `AcademicList`/`AcademicFormDialog` bersama (tabel desktop + kartu mobile + Pager + ReasonField + konflik versi); BFF `/api/academic` meniru allowlist master; `FilterSelect` shadcn untuk filter; pratinjau-konfirmasi mutasi memakai `ConfirmationDialog`. |
| 1.7 | 25 Oktober 2026 | Pola bar tab dibakukan: pill kompak rata kiri pada desktop dan pembagian lebar penuh pada mobile (`h-14!`/`h-11!`/token `bg-secondary`), menggantikan pola `grid-cols-2` yang merentang tab; tab terkontrol untuk konten bergantung state; modul Jadwal pelajaran & Guru pengganti memakai pola ini. |
| 1.8 | 26 Oktober 2026 | Audit FR-01–FR-07: token teks sekunder diperkuat menjadi #58645d, metadata aplikasi minimal 12 px, deskripsi minimal 14 px; DataLoading/DataFailure diekstrak untuk lintas fitur, alias master tetap kompatibel; kategori heading domain, label filter, proteksi respons usang, dan loading/error seragam. Menu mutasi memerlukan academic.enrollments.manage; perwalian hanya memakai kelas perwalian aktif; kontrak API menggunakan Resource dengan relasi camelCase. |
| 1.9 | 26 Oktober 2026 | Tab horizontal tidak lagi melebar di desktop akibat stretch parent flex; label panjang membungkus pada mobile agar tidak bertumpuk. Perbaikan pada TabsList/TabsTrigger bersama. Kebijakan absensi memakai loading/error/retry bersama dan menjelaskan akurasi GPS serta baseline tanpa titik/radius. Konteks navbar modul mengikuti katalog sidebar. Regresi hydration diperluas ke RootLayout/sidebar, dashboard, kebijakan absensi dan akun pada mobile/desktop. |
| 1.10 | 27 Oktober 2026 | Editor geofence melalui peta Leaflet/OpenStreetMap, pin/koordinat terhubung, radius dan input manual; ukuran kontrol map 44 px, stacking map terisolasi di dialog, atribusi terlihat. AcademicFormDialog.extra dapat berupa render function dengan busy agar peta/field tambahan tidak aktif saat menyimpan. Kebijakan berlaku/terjadwal terpisah, pemicu dialog dipertahankan saat refresh agar fokus tidak mengaktifkan tab lain. Alert radius/ketidakpastian GPS memakai Alert shadcn dan keputusan final tetap di API. |
| 1.11 | 27 Oktober 2026 | Penyelarasan dengan UI aktual FR-01–FR-07: cakupan halaman dan sumber komponen lintas domain; token sidebar/radius lengkap; pola daftar, feedback, dialog/fokus/konflik, kamera/GPS/geofence, realisasi dan siswa/perwalian; membedakan implementasi, target aksesibilitas dan backlog. Revisi dokumentasi tanpa perubahan kode UI. |
