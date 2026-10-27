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

Operator atau Super Admin yang memiliki akses pengelolaan absensi membuka
**Absensi pegawai → Pola kerja & kebijakan → Kebijakan foto/GPS**
(`/absensi/pola-kerja`). Pilih **Versi kebijakan baru**, tentukan tanggal mulai
(besok atau setelahnya), sesuaikan akurasi GPS/umur bukti/batas koreksi, isi alasan,
lalu simpan. Kebijakan baru berlaku prospektif; bukti lama tidak diubah.

Baseline [SRS FR-05-04 dan BR-24](../SRS_SIM_Madrasah_MTs_Al_Karimiyyah.txt)
memakai **GPS tanpa geofence**. Belum ada titik pusat madrasah, koordinat kantor,
atau radius lokasi yang dapat diatur. Nilai akurasi 100 m adalah batas perkiraan
ketelitian koordinat perangkat, bukan jarak pegawai dari madrasah. Pegawai
mengizinkan lokasi dan kamera pada menu Masuk/pulang, lalu aplikasi menyimpan
koordinat sebagai bukti; akurasi/umur bukti tetap diverifikasi backend.

Pembatasan jarak dari titik madrasah membutuhkan fitur geofence serta perubahan
aturan SRS, skema dan validasi backend; mengubah angka akurasi saja tidak
mengaktifkan pembatasan tersebut.

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
