# Frontend SIM Madrasah

Pedoman UI/UX wajib: [Design System](../design-system.md). Semua halaman menggunakan
komponen shadcn/ui lokal dan token tema bersama, termasuk modal dan notifikasi.

## Hydration error dengan atribut `bis_*`

Jika overlay Next.js menampilkan `bis_skin_checked`, `bis_register`, atau
`__processed_*`, bandingkan halaman yang sama dengan profil browser baru tanpa
ekstensi. Atribut tersebut tidak dihasilkan frontend ini; ekstensi dapat mengubah
HTML sebelum React melakukan hydration. Nama komponen pada stack menunjukkan
lokasi perbedaan HTML dan belum tentu sumber masalahnya.

Sumber konkret yang ditemukan pada lingkungan pengembangan ini adalah **Urban VPN
Proxy 5.14.4**, ID `eppiocemhmnlbhjplcgkofciiegomcon`, di profil Brave Flatpak.
Kode `ad-blocker/content.js` miliknya memuat penulisan `bis_skin_checked` dan
`bis_register`. Manifest mendaftarkan script pada `document_start` untuk halaman
HTTP/HTTPS. Temuan ini berasal dari paket ekstensi lokal, bukan tebakan dari nama
atribut; status browser/profil lain tetap perlu diperiksa tersendiri.

Untuk Brave, buka alamat berikut melalui address bar:

```text
brave://extensions/?id=eppiocemhmnlbhjplcgkofciiegomcon
```

Nonaktifkan Urban VPN Proxy pada halaman tersebut untuk memverifikasi penyebab,
lalu tutup dan buka kembali tab aplikasi. Untuk penggunaan selanjutnya, batasi
izin membaca/mengubah situs menjadi **Saat diklik** jika opsi tersedia, sehingga
content script tidak otomatis berjalan pada localhost. Profil baru perlu tetap
diperiksa jika sinkronisasi atau pengelolaan browser memasang kembali ekstensi.

Referensi identitas ekstensi: [Urban VPN Proxy di Chrome Web Store](https://chromewebstore.google.com/detail/urban-vpn-proxy/eppiocemhmnlbhjplcgkofciiegomcon).

Nonaktifkan akses ekstensi yang memodifikasi halaman pada origin development
(`localhost:3000`/`127.0.0.1:3000`, sesuaikan port), lalu tekan `Ctrl+Shift+R`.
Incognito dapat dipakai jika ekstensi tidak diizinkan berjalan di sana.
Jika browser bersih masih error, lanjutkan pemeriksaan SSR/client pada
[panduan diagnosis design system](../design-system.md#12-ssr-hydration-dan-diagnosis-error).

Pengujian login normal, callback Google gagal, pemberitahuan password, serta
RootLayout/sidebar pada dashboard, halaman kebijakan absensi, dan akun (mobile
dan desktop):

```bash
NEXT_TEST_DEV=1 pnpm exec playwright test tests/e2e/hydration.spec.ts
```

Tes memakai Chromium tanpa ekstensi dan API fixture; tes tidak menonaktifkan
ekstensi pada browser pribadi Anda. Penyebab eksternal perlu ditangani pada
browser tersebut. Peringatan tidak disembunyikan dengan suppression global.

Referensi: [Next.js — hydration error](https://nextjs.org/docs/messages/react-hydration-error).

## Pengaturan GPS dan titik absensi

Operator/Super Admin, pemilik izin role `attendance.manage`, atau mandat
institusi yang sesuai membuka
**Absensi pegawai → Pola kerja & kebijakan → Kebijakan foto/GPS → Versi kebijakan baru**.
Aktifkan **Batasi absensi dalam radius lokasi**, isi nama lokasi, lalu klik peta
atau geser pin. Latitude/longitude langsung terisi; ubah radius (25–5.000 m)
untuk melihat cakupan lingkarannya. Koordinat dapat diisi manual, atau gunakan
**Gunakan lokasi perangkat** jika pengelola berada di lokasi yang akan ditetapkan.
Peta Leaflet memakai tile OpenStreetMap dan tetap menyediakan input manual ketika
latar peta gagal dimuat. Tile memakai layanan pihak ketiga; lihat
[kebijakan tile OpenStreetMap](https://operations.osmfoundation.org/policies/tiles/).

Pilih tanggal mulai besok/setelah versi terjadwal terakhir, isi alasan dan simpan.
Ringkasan menampilkan **Kebijakan berlaku** dan **Kebijakan terjadwal** secara
terpisah; membuka dialog kembali memakai pengaturan terakhir termasuk yang belum
aktif. Klik peta mengubah form; penyimpanan tetap melalui tombol Simpan.

Pegawai mendapat alert setelah GPS dibaca bila berada di luar radius atau posisi
masih meragukan di dekat batas. Kedua kondisi menonaktifkan tombol absen. GPS
segar dibaca ulang saat mengirim dan API menghitung ulang jarak; mengubah request
frontend tidak melewati validasi server. Hanya `jarak + akurasi <= radius`
diterima. Contoh: radius 100 m, jarak 80 m dan akurasi ±30 m belum diterima;
mendekat atau ambil ulang GPS hingga lebih pasti. Akurasi 100 m bukan radius.
Ini pemeriksaan saat absensi, bukan pelacakan latar belakang atau jaminan anti
pemalsuan GPS. Pencatatan administratif oleh petugas tetap tersedia sesuai SOP.

Geofence memerlukan migrasi tambahan lima kolom pada database MySQL yang sama;
tabel bukti memakai JSON snapshot yang sudah tersedia. Setelah meninjau dan
mencadangkan database tujuan, jalankan dari folder `backend`:

```bash
php artisan migrate --force
```

Gunakan migration tersebut, **jangan migrate:fresh/reset pada database berisi
data**. Migrasi tidak mengaktifkan radius atau mengisi koordinat produksi secara
otomatis; policy lama tetap nonaktif. Bukti/waktu absensi lama tidak diubah.
Migrasi menormalkan batas akhir policy berurutan ke interval eksklusif sambil
mempertahankan hari berlaku. Deploy backend dan frontend bersamaan setelah schema
tersedia. SRS v1.4 FR-05-08/BR-24/DEC-16 menjelaskan aturan dan acceptance test.

### Jika terlanjur menjalankan rollback

`php artisan rollback` bukan perintah Laravel. `php artisan migrate:rollback`
membatalkan satu batch terakhir secara default, sehingga bisa menghapus banyak
tabel jika instalasi awal dilakukan dalam satu batch. Untuk menambahkan geofence
atau migration baru gunakan `php artisan migrate`, tanpa rollback sebelumnya.

Pengaman `GuardedMigrator` memeriksa target downgrade sebelum menjalankan satu pun
`down()`: jika migrasi identitas ikut diturunkan sementara ada akun tanpa email,
seluruh operasi dihentikan lebih awal. Batas NOT NULL di schema lama tetap
memerlukan email sah; jangan menambah email palsu atau menghapus akun untuk
melewati pemeriksaan. Rollback terbatas pada geofence tetap dapat digunakan
ketika memang ingin membatalkan konfigurasi tersebut.

Jika rollback sudah menghapus tabel, `migrate` membangun ulang struktur, tetapi
record yang terhapus tidak otomatis kembali. Gunakan backup untuk data penting.
Pengguna yang tabel sesi autentikasinya ikut dihapus perlu login kembali.

## Menjalankan proyek

Gunakan pnpm sesuai versi pada `package.json`. Untuk checkout baru, pasang
paket menggunakan lockfile dan siapkan `.env.local` dari `.env.example` bila
belum ada. Arahkan API ke backend Laravel yang berjalan. Kredensial Google dan
secret JWT tetap berada di backend, tidak di variabel `NEXT_PUBLIC_*`.

```bash
pnpm install --frozen-lockfile
pnpm dev
```

Buka [localhost:3000](http://localhost:3000). Dokumentasi Next.js versi yang
terpasang tersedia di `node_modules/next/dist/docs/`; baca sebelum mengubah
perilaku rendering karena versi proyek dapat berbeda dari dokumentasi versi lama.

## Pemeriksaan frontend

```bash
pnpm lint
pnpm build
pnpm exec playwright test
```

Playwright menjalankan API fixture pada port 43990 dan frontend produksi pada
port 3410. Build harus tersedia sebelum tes produksi. Untuk memeriksa mode
development, tambahkan `NEXT_TEST_DEV=1`; output Next.js development terisolasi
pada `.next-e2e` sehingga tidak memakai direktori server development pribadi.
Tes menggunakan akun fixture, bukan database madrasah yang sebenarnya.

Browser Playwright harus tersedia. Jika perlu, set
`PLAYWRIGHT_CHROMIUM_EXECUTABLE_PATH` ke executable Chromium yang terpasang.
