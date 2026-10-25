<?php

namespace Database\Seeders;

use App\Models\BusinessDecision;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

/**
 * Menyemai register business_decisions dengan DEC-01 s.d. DEC-21 sebagai
 * baseline sesuai SRS v1.3 (DEC-01–DEC-19 baseline v1.2; DEC-16 versi 2
 * addendum foto/GPS 20 Oktober 2026; DEC-20/DEC-21 addendum 24 Oktober 2026).
 *
 * Dijalankan saat onboarding (php artisan db:seed --class=BusinessDecisionSeeder),
 * bukan sebagai migration, agar lingkungan uji tetap bekerja dengan register
 * kosong dan pengesahan nyata tetap dibuat sebagai versi baru oleh akun nyata.
 * Idempoten: baris baseline tidak ditimpa apabila sudah ada.
 */
class BusinessDecisionSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        $baselineV12 = 'SRS v1.2 baseline 14 Oktober 2026 (arahan pemilik proyek)';

        $decisions = [
            ['code' => 'DEC-01', 'title' => 'Sumber honor dan verifikator realisasi', 'text' => 'Honor berasal dari realisasi berstatus held dan approved; verifikator adalah penanggung jawab akademik dengan mandat semester/kelas, berbeda dari pengaju.'],
            ['code' => 'DEC-02', 'title' => 'Definisi JP dan pembulatan uang', 'text' => 'Satu JP = 40 menit sebagai parameter awal; JP diakui = min(JP rencana, floor(menit aktual / 40 × 1000) / 1000), tidak melebihi rencana. Hitung uang dengan desimal presisi tinggi, bulatkan half-up ke rupiah per sumber lalu jumlahkan.'],
            ['code' => 'DEC-03', 'title' => 'Satu pengajar dan penerima honor per pertemuan', 'text' => 'Satu pengajar dan satu penerima per pertemuan; penerima adalah guru pelaksana yang sah termasuk pengganti aktif. Co-teaching nonaktif. Salah penerima setelah locked dikoreksi lewat batch adjustment debit/kredit berpasangan; klaim sumber asal tetap ada.'],
            ['code' => 'DEC-04', 'title' => 'Libur dan kehadiran tidak membentuk/memotong honor', 'text' => 'Libur/kelas batal tidak membentuk JP honor. Izin, sakit, cuti, dinas luar tidak menghasilkan JP tanpa pelaksanaan. Kehadiran pegawai tidak otomatis menambah atau memotong honor; tidak ada potongan otomatis.'],
            ['code' => 'DEC-05', 'title' => 'Periode honor dan prioritas tarif', 'text' => 'Periode honor bulanan dengan batas akhir eksklusif. Tarif prioritas pegawai → kategori pada tanggal sumber → global. Tambahan bulanan diprorata menurut hari kalender penugasan aktif / hari kalender bulan; pergantian tarif memakai segmen tanggal. Potongan manual wajib alasan dan persetujuan.'],
            ['code' => 'DEC-06', 'title' => 'Pembuat–pemeriksa–pengesah honor', 'text' => 'Bendahara membuat; petugas bermandat memeriksa; kepala bermandat mengesahkan; ketiganya akun berbeda. Pengesahan dan penguncian satu transaksi. Setelah locked hanya adjustment selisih pada periode terbuka berikutnya.'],
            ['code' => 'DEC-07', 'title' => 'Absensi siswa harian', 'text' => 'Absensi siswa harian oleh guru/wali kelas yang ditugaskan sebelum 23.59 waktu lokal. Tidak ada baris berarti belum dicatat, bukan alpa. Koreksi sampai 7 hari kalender disetujui petugas akademik berbeda; sesudah itu hanya mandat khusus dengan alasan. Mode pertemuan/both nonaktif.'],
            ['code' => 'DEC-08', 'title' => 'Akun wali dan pemulihan akun', 'text' => 'Operator membuat akun wali dan memverifikasi hubungan dari data administrasi serta konfirmasi kontak; beberapa wali/anak didukung. Akses sejak tanggal efektif hubungan. Pencabutan segera berlaku. Reset oleh petugas berizin dengan pemeriksaan identitas dan sandi sementara 24 jam sekali pakai; pemulihan mandiri via email belum diimplementasikan.'],
            ['code' => 'DEC-09', 'title' => 'Fitur di luar rilis awal', 'text' => 'Akun siswa, PPDB, nilai/rapor, SPP, pembayaran digital, akuntansi, ujian online, integrasi otomatis EMIS dan multi-madrasah nonaktif/di luar rilis awal. Identitas siswa tetap tercatat dan diakses lewat petugas/wali berizin.'],
            ['code' => 'DEC-10', 'title' => 'Editor dan penerbit publikasi', 'text' => 'TU/operator menjadi editor; kepala atau delegasi bermandat menjadi penerbit, berbeda dari editor revisi. Publik hanya data terpilih dan foto dengan izin publikasi tercatat. Tidak memuat identitas sensitif siswa; pencabutan publikasi juga menutup aset dan cache.'],
            ['code' => 'DEC-11', 'title' => 'Deployment awal', 'text' => 'Satu VPS Linux awal, Nginx TLS, PHP-FPM, Node.js untuk Next.js, MySQL privat, worker/scheduler terawasi. Satu domain publik lewat reverse proxy; salinan backup terenkripsi di lokasi terpisah. Staging terpisah dari produksi. Domain, kontak dan kredensial hosting adalah data konfigurasi nyata.'],
            ['code' => 'DEC-12', 'title' => 'Format paket soal', 'text' => 'PDF wajib; naskah dan kunci terpisah. DOCX nonaktif. Jenis awal pilihan ganda satu jawaban dan esai, rumus LaTeX tervalidasi serta gambar PNG/JPEG/WebP. Template header madrasah berversi; finalisasi menyimpan snapshot urutan dan kunci.'],
            ['code' => 'DEC-13', 'title' => 'Stack teknologi dan autentikasi', 'text' => 'Laravel backend API, Next.js App Router + React/TypeScript frontend, Tailwind CSS/shadcn UI, MySQL 8.4 InnoDB. Auth JWT HS256 + Google OAuth opsional melalui BFF cookie HttpOnly; access 15 menit dan sesi/refresh absolut 120 menit. API /api/v1 dan BFF /api/auth/* serta /api/accounts/*. Satu monorepo dengan dua proses aplikasi; versi paket dikunci lockfile.'],
            ['code' => 'DEC-14', 'title' => 'Profil kapasitas dan backup', 'text' => 'Profil penerimaan 50 pengguna aktif, lonjakan 100, p95 API rutin ≤ 2 detik, ketersediaan 99,5% per bulan, RPO 24 jam/RTO 4 jam. Server awal 4 vCPU, RAM 8 GB, SSD 80 GB; ukuran akhir mengikuti uji. Backup harian 14 hari/mingguan 8 minggu dan restore tiap 3 bulan.'],
            ['code' => 'DEC-15', 'title' => 'Akses BK dan retensi teknis', 'text' => 'BK internal hanya petugas BK bermandat kategori; kepala/admin tanpa bypass. BK/tindak lanjut tertutup diarsipkan setelah 2 tahun; akademik/honor/TU setelah 5 tahun dari penutupan periode/dokumen; byte sumber historis tidak dihapus otomatis. Audit aktif 1 tahun lalu arsip. Ekspor 24 jam, payload impor 30 hari. Disposal manual tercatat dengan legal hold. Default teknis, bukan klaim masa retensi hukum.'],
            ['code' => 'DEC-17', 'title' => 'Kepemilikan dan kolaborasi soal', 'text' => 'Soal milik pembuat; kolaborasi hanya grant eksplisit read/edit. Kunci/ekspor perlu izin tersendiri. Paket hanya memakai soal ready, final immutable; perubahan menjadi paket baru.'],
            ['code' => 'DEC-18', 'title' => 'Penomoran surat', 'text' => 'Nomor surat keluar urut per tahun kalender seluruh madrasah: NNNN/MTs-AK/YYYY; agenda masuk/keluar punya counter terpisah. Nomor batal tidak dipakai ulang. Nomor eksternal surat masuk tidak unik global. Disposisi nonaktif; arsip internal dan grant kategori aktif.'],
            ['code' => 'DEC-19', 'title' => 'Tahun ajaran dan mutasi siswa', 'text' => 'Satu tahun/semester aktif lewat pointer singleton; semester 1/2, kelas 7/8/9. Tanggal berasal dari kalender resmi yang diinput. Mutasi efektif tanggal baru tanpa overlap; kenaikan/kelulusan melalui preview dan konfirmasi, tidak otomatis berdasarkan usia/tanggal. Tingkat kelas disnapshot per tahun.'],
            ['code' => 'DEC-20', 'title' => 'Notifikasi in-app; kanal eksternal backlog', 'text' => 'Notifikasi in-app aktif pada baseline untuk pengumuman, perubahan status penting (verifikasi realisasi, pengesahan honor, koreksi absensi) dan pesan sistem, dengan status terbaca per pengguna. Kanal eksternal email/WhatsApp/SMS nonaktif dan menjadi backlog hingga penyedia, biaya, dasar pemrosesan, dan persetujuan penerima ditetapkan melalui revisi kebijakan. Notifikasi tidak memuat BK internal, rincian honor pihak lain, atau bukti foto/GPS.', 'source' => 'SRS v1.3 addendum 24 Oktober 2026', 'effective_from' => '2026-10-24'],
            ['code' => 'DEC-21', 'title' => 'Persetujuan publikasi siswa dan UU PDP', 'text' => 'Persetujuan publikasi foto/identitas siswa direkam per siswa berversi dan diperiksa sebelum konten terbit; penarikan berlaku ke depan tanpa menghapus riwayat. Register dasar pemrosesan dan layanan hak subjek data (akses, perbaikan, pembatasan, keberatan) menjadi kebutuhan onboarding dan operasional dengan petugas bertugas. Default teknis ini membantu kepatuhan UU PDP No. 27/2022 dan bukan opini hukum; interpretasi final mengikuti penanggung jawab perlindungan data madrasah.', 'source' => 'SRS v1.3 addendum 24 Oktober 2026', 'effective_from' => '2026-10-24'],
        ];

        foreach ($decisions as $decision) {
            BusinessDecision::query()->firstOrCreate(
                ['code' => $decision['code'], 'version' => 1],
                [
                    'title' => $decision['title'],
                    'status' => 'baseline',
                    'decision_text' => $decision['text'],
                    'source_reference' => $decision['source'] ?? $baselineV12,
                    'recorded_at' => now('UTC'),
                    'effective_from' => $decision['effective_from'] ?? '2026-10-14',
                ],
            );
        }

        BusinessDecision::query()->firstOrCreate(
            ['code' => 'DEC-16', 'version' => 1],
            [
                'title' => 'Pola kerja dan absensi pegawai (baseline semula)',
                'status' => 'superseded',
                'decision_text' => 'Satu shift Senin–Sabtu 07.00–14.00 Asia/Jakarta; Minggu/libur tidak wajib kerja. Toleransi terlambat 10 menit. Petugas mencatat izin/sakit/cuti/dinas luar; koreksi 7 hari dengan persetujuan petugas berbeda. Biometrik nonaktif; absensi siswa harian oleh guru/wali kelas.',
                'source_reference' => $baselineV12,
                'recorded_at' => now('UTC'),
                'effective_from' => '2026-10-14',
                'effective_to' => '2026-10-19',
            ],
        );

        $decision16V1 = BusinessDecision::query()->where('code', 'DEC-16')->where('version', 1)->first();

        BusinessDecision::query()->firstOrCreate(
            ['code' => 'DEC-16', 'version' => 2],
            [
                'title' => 'Pola kerja dan absensi pegawai (addendum foto/GPS)',
                'status' => 'baseline',
                'decision_text' => 'Satu shift Senin–Sabtu 07.00–14.00 Asia/Jakarta; Minggu/libur tidak wajib kerja. Toleransi terlambat 10 menit. Masuk/pulang pegawai wajib foto kamera baru + GPS, dikompresi server ke WebP maksimal 300 KiB/sisi 1.280 px/kualitas awal 80. Akurasi GPS maksimal 100 meter dan usia foto/lokasi maksimal 60 detik saat diterima; tanpa geofence pada baseline. Jam absensi tetap waktu server. Petugas mencatat izin/sakit/cuti/dinas luar; koreksi 7 hari dengan persetujuan petugas berbeda, selebihnya izin khusus. Biometrik tetap nonaktif; absensi siswa tetap harian. Berlaku prospektif lewat versi efektif baru; tanggal efektif operasional ditetapkan saat onboarding.',
                'rationale' => 'Addendum 20 Oktober 2026 membatasi foto/GPS pada masuk/pulang pegawai.',
                'source_reference' => 'SRS v1.2 addendum foto/GPS 20 Oktober 2026',
                'recorded_at' => now('UTC'),
                'supersedes_id' => $decision16V1?->id,
                'effective_from' => '2026-10-20',
            ],
        );
    }
}
