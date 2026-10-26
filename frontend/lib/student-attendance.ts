import { apiRequest, requestError } from "@/lib/api-client";

export type StudentAttendanceSection = "harian" | "rekap";

export const STUDENT_ATTENDANCE_SECTIONS: Record<
  StudentAttendanceSection,
  { title: string; description: string }
> = {
  harian: {
    title: "Absensi harian siswa",
    description:
      "Guru/wali kelas mengisi hadir, sakit, izin, atau alpa untuk kelas penugasannya sebelum 23.59 WIB; tanpa catatan berarti belum dicatat, bukan alpa.",
  },
  rekap: {
    title: "Rekap kehadiran siswa",
    description:
      "Rekap per tanggal dan per siswa; kolom belum dicatat menandakan pengisian yang belum dilakukan.",
  },
};

export type RosterRow = {
  enrollment_id: number;
  student_id: number;
  student: { id: number; person?: { full_name?: string } | null };
  attendance: {
    id: number;
    status: { code: string; name: string };
    lock_version: number;
    notes: string | null;
  } | null;
};

export type RosterPayload = {
  class_period: { id: number; display_name: string };
  date: string;
  policy: {
    mode: string;
    input_deadline_time: string;
    correction_deadline_days: number;
  };
  statuses: { id: number; code: string; name: string }[];
  rows: RosterRow[];
};

export type ClassSummary = {
  id: number;
  class_id: number;
  display_name: string;
  class_name?: string | null;
  roster: number;
  recorded: number;
};

export const studentAttendanceRequest = <T>(
  path: string,
  method = "GET",
  data?: unknown,
) => apiRequest<T>("student-attendance", path, method, data);

export const studentAttendanceError = requestError;

export { dateOnly, todayWib, wibMonthRange } from "@/lib/attendance";
