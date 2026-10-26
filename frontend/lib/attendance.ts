import { apiRequest, requestError } from "@/lib/api-client";

export type AttendanceSection =
  | "masuk-pulang"
  | "riwayat"
  | "pegawai"
  | "pola-kerja"
  | "rekap"
  | "koreksi";

export const ATTENDANCE_SECTIONS: Record<
  AttendanceSection,
  { title: string; description: string; allow: (nav: Set<string>) => boolean }
> = {
  "masuk-pulang": {
    title: "Masuk / pulang",
    description:
      "Aksi mandiri dengan foto kamera dan GPS perangkat; waktu yang dicatat adalah waktu server.",
    allow: (nav) => nav.has("attendance.own"),
  },
  riwayat: {
    title: "Riwayat absensi saya",
    description:
      "Kehadiran pribadi per hari kerja beserta bukti foto dan lokasi.",
    allow: (nav) => nav.has("attendance.own"),
  },
  pegawai: {
    title: "Absensi & izin pegawai",
    description:
      "Pencatatan izin, sakit, cuti, dan dinas luar oleh petugas tanpa bukti foto; fallback administratif bila kamera/GPS pegawai gagal.",
    allow: (nav) => nav.has("attendance.manage"),
  },
  "pola-kerja": {
    title: "Pola kerja & kebijakan",
    description:
      "Pola kerja berversi per hari, penetapan pegawai, generator hari kerja, dan kebijakan foto/GPS.",
    allow: (nav) => nav.has("attendance.manage"),
  },
  rekap: {
    title: "Rekap kehadiran pegawai",
    description:
      "Rekap hadir, terlambat, izin/sakit/cuti/dinas luar, dan tanpa catatan per pegawai.",
    allow: (nav) =>
      nav.has("attendance.manage") || nav.has("attendance.reports"),
  },
  koreksi: {
    title: "Persetujuan koreksi absensi",
    description:
      "Koreksi kehadiran disetujui petugas berbeda dari pemohon; bukti asli tidak pernah diubah.",
    allow: (nav) =>
      nav.has("attendance.correct") || nav.has("attendance.manage"),
  },
};

export type AttendanceRecord = {
  id: number;
  updated_at: string;
  [key: string]: unknown;
};

export type ClockStatus = {
  today: string;
  employee: {
    id: number;
    employee_number: string | null;
    person?: { id: number; full_name: string } | null;
  };
  work_day: {
    id: number;
    work_date: string;
    scheduled_start_at: string | null;
    scheduled_end_at: string | null;
    tolerance_minutes_snapshot: number;
    is_work_required: boolean;
    calendar_event_id: number | null;
  } | null;
  attendance: {
    id: number;
    check_in_at: string | null;
    check_out_at: string | null;
    late_minutes: number | null;
    entry_mode: string;
    notes: string | null;
    lock_version: number;
    updated_at: string;
    status?: { code: string; name: string } | null;
  } | null;
  attendance_status?: { code: string; name: string } | null;
  next_action: "check_in" | "check_out" | null;
  policy: {
    id: number;
    requires_photo: boolean;
    requires_gps: boolean;
    photo_max_dimension: number;
    photo_quality: number;
    photo_max_size_bytes: number;
    gps_max_accuracy_meters: string;
    gps_max_age_seconds: number;
    correction_deadline_days: number;
  };
  replayed?: boolean;
};

export const attendanceRequest = <T>(
  path: string,
  method = "GET",
  data?: unknown,
) => apiRequest<T>("attendance", path, method, data);

export async function attendanceUpload(
  path: string,
  form: FormData,
): Promise<{ status: number; payload: Record<string, unknown> }> {
  const response = await fetch(`/api/attendance/${path}`, {
    method: "POST",
    headers: { Origin: window.location.origin },
    body: form,
  });
  let payload: Record<string, unknown> = {};
  try {
    payload = (await response.json()) as Record<string, unknown>;
  } catch {
    payload = { message: "Permintaan belum berhasil. Silakan coba kembali." };
  }

  return { status: response.status, payload };
}

export const attendanceError = requestError;

export const statusLabels: Record<string, string> = {
  hadir: "Hadir",
  izin: "Izin",
  sakit: "Sakit",
  cuti: "Cuti",
  dinas_luar: "Dinas luar",
  alpa: "Alpa",
};

export const entryModeLabel = (mode: unknown): string =>
  mode === "self"
    ? "Mandiri (foto+GPS)"
    : mode === "officer"
      ? "Petugas"
      : typeof mode === "string"
        ? mode
        : "—";

export const wibDateTime = (value: unknown): string => {
  if (typeof value !== "string" || !value) return "—";
  const date = new Date(value);
  if (Number.isNaN(date.getTime())) return "—";

  return date.toLocaleString("id-ID", {
    timeZone: "Asia/Jakarta",
    day: "2-digit",
    month: "short",
    year: "numeric",
    hour: "2-digit",
    minute: "2-digit",
  });
};

export const wibTime = (value: unknown): string => {
  if (typeof value !== "string" || !value) return "—";
  const date = new Date(value);
  if (Number.isNaN(date.getTime())) return "—";

  return date.toLocaleTimeString("id-ID", {
    timeZone: "Asia/Jakarta",
    hour: "2-digit",
    minute: "2-digit",
  });
};

export const dateOnly = (value: unknown): string => {
  if (typeof value !== "string" || !value) return "—";
  const [y, m, d] = value.slice(0, 10).split("-");

  return d && m && y ? `${d}/${m}/${y}` : "—";
};

/** Tanggal hari ini (WIB) tanpa pergeseran zona waktu dari toISOString. */
export const todayWib = (): string =>
  new Date().toLocaleDateString("en-CA", { timeZone: "Asia/Jakarta" });

/** Rentang bulan berjalan dalam zona WIB. */
export const wibMonthRange = (): { from: string; to: string } => {
  const [year, month] = todayWib().split("-").map(Number);
  const lastDay = new Date(Date.UTC(year, month, 0)).getUTCDate();
  const mm = String(month).padStart(2, "0");

  return {
    from: `${year}-${mm}-01`,
    to: `${year}-${mm}-${String(lastDay).padStart(2, "0")}`,
  };
};

/** Tanggal WIB + jumlah hari (aritmetika UTC agar bebas zona). */
export const wibAddDays = (date: string, days: number): string => {
  const [year, month, day] = date.split("-").map(Number);

  return new Date(Date.UTC(year, month - 1, day + days))
    .toISOString()
    .slice(0, 10);
};

export const WEEKDAYS = [
  { value: 1, label: "Senin" },
  { value: 2, label: "Selasa" },
  { value: 3, label: "Rabu" },
  { value: 4, label: "Kamis" },
  { value: 5, label: "Jumat" },
  { value: 6, label: "Sabtu" },
];

export const dayLabel = (day: unknown): string =>
  WEEKDAYS.find((option) => option.value === Number(day))?.label ?? "Minggu";
