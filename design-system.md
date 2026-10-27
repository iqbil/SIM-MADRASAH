# Design System — SIM Madrasah MTs Al Karimiyyah

Versi 1.9 · 26 Oktober 2026 · Berlaku untuk seluruh `frontend/`.

Dokumen ini menjadi pedoman implementasi UI/UX dari autentikasi hingga seluruh modul madrasah. Perubahan berikutnya wajib mengacu ke pedoman ini. Ketentuan bisnis, otorisasi, dan data mengikuti SRS serta kontrak API; design system mengatur cara pengguna berinteraksi dengan ketentuan tersebut.

## 1. Prinsip dan aturan wajib

1. **Mobile menjadi prioritas.** Semua alur utama dapat diselesaikan pada lebar 320 px, menggunakan sentuhan dan keyboard, tanpa menggulir seluruh halaman secara horizontal.
2. **Komponen interaktif wajib shadcn/ui.** Gunakan komponen lokal `frontend/components/ui/`. Komponen baru ditambahkan melalui registry shadcn dengan style `radix-nova`, lalu disesuaikan dengan token proyek. Elemen semantik seperti `main`, `section`, `form`, `fieldset`, dan `dl` tetap menggunakan HTML.
3. **Satu identitas visual.** Warna hijau madrasah, latar netral hangat, kartu putih, tipografi Geist, ikon Lucide, dan ruang kosong yang cukup. Warna tidak berubah menurut role.
4. **Pola interaksi seragam.** Aksi yang sama memakai label, urutan, indikator proses, modal, dan pesan hasil yang sama di semua modul.
5. **Tampilkan keadaan yang sebenarnya.** Angka berasal dari API; bedakan memuat, kosong, gagal, dan tidak berizin. Loading tidak ditampilkan sebagai angka nol.
6. **Akses mengikuti izin.** Sidebar dan tindakan mengikuti role/permission yang diberikan. Penyembunyian tombol bukan pengganti pemeriksaan izin di backend.
7. **Perbaiki penyebab error.** Peringatan hydration/prerender harus diperiksa, bukan disembunyikan secara global.
8. **Gunakan ulang sebelum membuat.** Jangan membuat komponen baru jika komponen yang ada dapat memenuhi kebutuhan melalui komposisi, props, atau variant yang wajar. Aturan ini berlaku untuk komponen dasar, layout, form, modal, notifikasi, dan komponen fitur, sejak halaman pertama sampai pengembangan sistem selesai.
9. **Halaman baru mengikuti halaman yang sudah ada.** Modul dan role dapat berbeda isi serta hak aksesnya, tetapi memakai shell, token, ukuran kontrol, pola form, dialog, tabel, dan keadaan UI yang sama. Jangan membuat identitas visual atau kumpulan komponen dasar tersendiri per modul/role.

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
| Loading/error data lintas fitur | `frontend/components/layout/data-feedback.tsx` (`DataLoading`, `DataFailure`); alias master dipertahankan |
| Breakpoint sidebar | `frontend/hooks/use-mobile.ts` |
| Referensi form akun, role, konfirmasi | `frontend/components/accounts/account-workspace.tsx` |
| Dialog form lintas fitur | `frontend/components/layout/form-dialog.tsx` |
| Konfirmasi tindakan lintas fitur | `frontend/components/layout/confirmation-dialog.tsx` |
| Pagination dan alasan perubahan | `frontend/components/layout/pager.tsx`, `frontend/components/layout/reason-field.tsx` |
| Data master, form domain, relasi dan impor | `frontend/components/master/`, `frontend/lib/master-data.ts` |
| Modul Akademik & Kelas (tahun ajaran/semester, rombel, keanggotaan, mutasi, kalender, penugasan) | `frontend/components/academic/`, `frontend/lib/academic.ts`, BFF `frontend/app/api/academic/` |
| Request sesi dan loading data bersama | `frontend/lib/api-client.ts`, `frontend/hooks/use-async-data.ts`, `frontend/hooks/use-dialog-focus.ts` |
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
| Dashboard awal kosong | `EmptyDashboard` dan primitive `Empty` | `EmptyDashboard` khusus konteks dashboard role; daftar kosong modul lain disusun dari `Empty` dengan isi yang relevan. |
| Dialog, konfirmasi, detail | `Dialog`, `AlertDialog`, `Sheet`, `ScrollArea` | Pakai struktur pada §8. `UserEditor`, `RoleEditor`, dan `ActionDialog` tetap memuat aturan akun; kerangka tampilannya memakai `FormDialog`/`ConfirmationDialog` bersama. `ReasonField` menjadi kolom alasan lintas fitur. Jangan mengimpor workspace akun untuk menggunakan pola umum. |
| Daftar, status, pagination | `Table`, `Badge`, pola kartu mobile, dan `Pager` bersama di `components/layout/pager.tsx` | Pakai metadata halaman/jumlah hasil yang nyata dan nonaktifkan pagination saat pending. Jangan menyalin fungsi pagination ke modul lain. |
| Form dan notifikasi | `Label`, `Input`, `Textarea`, `Select`, `Checkbox`, `Button`, `Alert`, `Toaster` | Satu pola label/error/pending. `Toaster` hanya pada root layout, fitur memakai `toast` yang sama. |

Inventaris ini menyatakan komponen yang benar-benar ada saat revisi ini. Jangan menganggap nama pola sebagai komponen yang sudah diekspor, dan jangan mengimpor seluruh workspace akun untuk memakai satu bagian tampilan. Lokasi fitur tetap mengikuti domain; komponen lintas fitur ditempatkan di direktori bersama yang sudah tersedia dan sesuai fungsinya.

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

Hijau ringan menggunakan turunan token, misalnya `bg-primary/5` dan `border-primary/10`. Hindari warna hex baru di halaman. Pengecualian terbatas untuk identitas resmi pihak ketiga, gambar, dan overlay foto autentikasi. Jika dibutuhkan warna semantik sukses/peringatan/informasi pada modul baru, definisikan token bersama dan dokumentasikan sebelum dipakai; jangan menambahkan palet berbeda di setiap modul.

Status selalu memiliki label teks. Contoh: `Aktif`, `Nonaktif`, `Terverifikasi`, `Belum terverifikasi`; warna atau ikon saja tidak cukup. Grafik mendatang harus memakai seri yang dapat dibedakan, label/legenda, serta alternatif tekstual; token grafik netral saat ini belum menjadi palet final untuk grafik data.

Tema yang aktif saat ini **light**. Sonner juga menggunakan tema light. Dark mode baru boleh ditambahkan bersama token lengkap, provider, dan pengujian; jangan mengikuti preferensi OS hanya pada sebagian komponen.

### Tipografi

- Geist untuk seluruh UI; Geist Mono untuk kode/identitas teknis pendek yang memang perlu dibaca pengguna.
- Satu `h1` per halaman. Gunakan `h2` untuk bagian dan `h3` untuk subbagian, meskipun teks dibungkus `CardTitle` atau `EmptyTitle`.
- Judul halaman: 24–28 px pada mobile, 30–32 px pada layar lebar, `font-semibold`, `tracking-tight`.
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

Input/tombol memakai `rounded-lg`; kartu, dialog, dan bagian dashboard memakai `rounded-xl`/`rounded-2xl`; avatar dan badge dapat membulat penuh. Lengkung 32 px pada panel login mobile adalah pengecualian identitas autentikasi.

Kartu utama cukup memakai border lembut, tanpa bayangan berat. Dropdown memakai bayangan ringan; dialog/sheet memakai bayangan lebih kuat dan overlay gelap. Hindari border ganda akibat menambahkan ring dan border tanpa meninjau komponen dasar.

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
- Menu Data Master sudah aktif pada `/data-master/{section}` untuk delapan bagian katalog; `href` didefinisikan pada katalog sidebar yang sama. Menu operasional yang sudah tersedia tetap berupa `Link` dengan status aktif dan `aria-current`. Menu yang belum memiliki halaman berupa tombol **disabled**, tanpa `href`, dengan ikon jam dan nama aksesibel `Nama menu — Segera hadir`. Keterangan status tersedia di awal kelompok modul; jangan membuat tautan `#`, halaman kosong, atau toast saat menu diklik.
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

Gunakan ikon Lucide berukuran 16–20 px, gaya stroke konsisten. Ikon tombol selalu mempunyai label teks atau `aria-label`; tooltip bukan pengganti nama aksesibel. Ikon dekoratif diberi `aria-hidden`.

## 6. Pola halaman dan navigasi

Susunan halaman modul: konteks/judul → deskripsi singkat → aksi utama → ringkasan relevan → filter/tabs → daftar atau state kosong → pagination. Tidak semua halaman memerlukan semua bagian.

- Satu aksi primer yang paling menonjol pada setiap konteks; tindakan sekunder memakai outline/ghost.
- Nama menu menjelaskan pekerjaan, misalnya `Pengelolaan akun`, bukan nama tabel database.
- Pergantian role hanya menawarkan role milik pengguna. Role aktif adalah preferensi navigasi, bukan pemberian izin.
- Dashboard awal tetap memakai `Empty` sampai tersedia data/modul nyata. Tautan cepat hanya menuju fitur yang sudah tersedia dan boleh diakses.
- Halaman akun pribadi memakai informasi berlabel (`dl`) dan bagian keamanan terpisah.
- Login/password/pembaruan sesi memakai `AuthFrame`; panel identitasnya tidak disalin ke tiap halaman.
- Latar `AuthFrame` memakai ilustrasi lingkungan sekolah dengan overlay hijau tua; teks panel kiri bersifat faktual dan institusional (overline, garis emas `#e9d6a3`, judul sekala sedang, daftar kemampuan portal) — tanpa tagline pemasaran, pill kaca, atau tipografi hero besar. Penggantian dengan foto asli madrasah dilakukan melalui aset bersama dengan pertimbangan beban muat (NFR-15).

Komponen fitur baru ditempatkan menurut domain (`components/attendance`, `components/students`, dan seterusnya); komponen dasar tetap di `components/ui`. Jangan menaruh aturan bisnis domain ke dalam `Button`, `Dialog`, atau komponen UI dasar lainnya.

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
- Batas tinggi `calc(100dvh - 2rem)`. Lebar default `calc(100% - 2rem)`, maksimum 512 px untuk form; informasi singkat dapat maksimum 384 px.
- Form pada mobile dapat menjadi panel bawah selebar layar dengan sudut atas membulat. Tetap memakai `Dialog` shadcn, bukan overlay custom.
- Beri ruang untuk tombol tutup 44 px dan safe area perangkat. Gunakan `dvh` agar mengikuti perubahan viewport; tetap uji keyboard virtual pada perangkat nyata.
- Setiap dialog memiliki `DialogTitle` dan `DialogDescription` yang menjelaskan tujuan/dampaknya.
- Fokus terperangkap di dialog; Escape/klik luar bekerja untuk dialog biasa ketika tidak pending. Saat ditutup, kembalikan fokus ke pemicu atau kontrol daftar yang relevan.

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
| Data benar-benar kosong | `Empty` berisi judul, penjelasan, dan aksi relevan jika diizinkan |
| Pencarian tanpa hasil | Jelaskan hasil pencarian kosong; beri cara mengubah/menghapus filter |
| Tanpa izin | Ikuti kebijakan route/backend; jangan menampilkan isi sensitif sesaat sebelum redirect |
| Sesi berakhir | Arahkan ke pemulihan sesi/login dengan pesan yang jelas |
| Koneksi terputus | Jangan menganggap mutasi berhasil; pertahankan isian yang aman |

Jangan menggunakan toast untuk informasi yang harus dibaca sebelum tindakan berisiko. Informasi tersebut ditempatkan di dialog/form. Toast tidak boleh berisi detail upstream, stack trace, token, atau data konseling/honor.

## 10. Tabel, filter, dan pola modul berikutnya

- Daftar akun menggunakan kartu pada layar kecil, tabel mulai `md`. Modul lain memilih breakpoint menurut isi, dengan pola interaksi tetap sama.
- Identitas utama, status, dan tombol Kelola mudah ditemukan. Jangan memuat semua kolom desktop ke kartu mobile.
- Pagination menyatakan jumlah hasil dan posisi halaman. Tombol sebelumnya/berikutnya memiliki label aksesibel dan ukuran sentuh cukup.
- Pencarian/filter mereset halaman hasil bila diperlukan dan mempertahankan konteks saat memuat.
- Status memakai `Badge` dengan istilah konsisten. Jangan membedakan status melalui warna saja.
- Angka memakai format `id-ID`; mata uang rupiah memakai format yang sesuai. Waktu ditampilkan konsisten dengan zona `Asia/Jakarta`; tanggal lahir/tanggal kalender tidak boleh bergeser akibat konversi zona waktu.
- Singkatan internal, ID teknis, dan status API diterjemahkan menjadi istilah yang dimengerti pengguna.

Untuk absensi pegawai mendatang, foto + GPS hanya berlaku untuk masuk/pulang sesuai konfirmasi kebutuhan saat ini. UI harus memiliki keadaan izin kamera/lokasi, pengambilan foto, pratinjau/ambil ulang, pengiriman, dan hasil. Jangan menampilkan sukses sebelum konfirmasi server. Kompresi foto mengikuti kontrak backend dan tidak memerlukan parameter teknis di alur pengguna. Ketentuan ini tidak otomatis diterapkan ke absensi siswa.

Modul siswa, BK, keuangan/honor, serta dokumen menggunakan shell, pola form, dan notifikasi yang sama, dengan akses dan privasi mengikuti SRS masing-masing. Hak pengelolaan akun tidak otomatis membuka data sensitif modul lain.

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
- [ ] Perubahan pola/token bersama telah diperbarui dalam dokumen ini dan diperiksa pada autentikasi, dashboard, serta pengelolaan akun.

Perintah dari folder `frontend`:

```bash
pnpm lint
pnpm build
pnpm exec playwright test
NEXT_TEST_DEV=1 pnpm exec playwright test tests/e2e/hydration.spec.ts
```

Tes Playwright memakai API fixture terisolasi; tidak membuktikan integrasi OAuth Google nyata. Uji integrasi backend/OAuth dilakukan terpisah tanpa membagikan kredensial. Jika Chromium belum terpasang, siapkan browser Playwright atau arahkan `PLAYWRIGHT_CHROMIUM_EXECUTABLE_PATH` ke executable yang tersedia.

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
