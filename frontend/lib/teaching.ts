import { apiRequest, requestError } from "@/lib/api-client";

export type TeachingSection = "pertemuan" | "riwayat" | "verifikasi";

export const TEACHING_SECTIONS: Record<
  TeachingSection,
  { title: string; description: string; allow: (nav: Set<string>) => boolean }
> = {
  pertemuan: {
    title: "Pertemuan mengajar saya",
    description:
      "Pertemuan terjadwal dan tugas mengganti yang dapat direalisasikan; status pelaksanaan dan verifikasi terpisah.",
    allow: (nav) => nav.has("teaching.own"),
  },
  riwayat: {
    title: "Realisasi & riwayat JP saya",
    description:
      "Riwayat realisasi beserta total JP disetujui; isi yang sudah diajukan bersifat tetap dan hanya berubah lewat revisi baru.",
    allow: (nav) => nav.has("teaching.own"),
  },
  verifikasi: {
    title: "Verifikasi realisasi",
    description:
      "Persetujuan atau penolakan realisasi oleh verifikator bermandat, berbeda dari pengaju; satu keputusan final per revisi.",
    allow: (nav) => nav.has("teaching.verify"),
  },
};

export type TeachingRecord = {
  id: number;
  updated_at: string;
  [key: string]: unknown;
};

export const teachingRequest = <T>(
  path: string,
  method = "GET",
  data?: unknown,
) => apiRequest<T>("teaching", path, method, data);

export const teachingError = requestError;

export const executionLabels: Record<string, string> = {
  held: "Terlaksana",
  not_held: "Tidak terlaksana",
};

export const verificationLabels: Record<string, string> = {
  not_submitted: "Draf",
  pending: "Menunggu verifikasi",
  approved: "Disetujui",
  rejected: "Ditolak",
};

export { dateOnly, todayWib, wibMonthRange, wibTime } from "@/lib/attendance";
