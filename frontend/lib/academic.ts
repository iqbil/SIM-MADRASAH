import { apiRequest, requestError } from "@/lib/api-client";
import type { FieldDefinition } from "@/lib/master-data";

export type AcademicSection =
  | "tahun-ajaran"
  | "rombel"
  | "keanggotaan"
  | "mutasi"
  | "kalender"
  | "penugasan"
  | "jadwal"
  | "pengganti";

export const ACADEMIC_SECTIONS: Record<
  AcademicSection,
  { title: string; permission: string }
> = {
  "tahun-ajaran": {
    title: "Tahun ajaran & semester",
    permission: "academic.years.view",
  },
  rombel: { title: "Rombongan belajar", permission: "academic.classes.view" },
  keanggotaan: {
    title: "Keanggotaan & riwayat siswa",
    permission: "academic.enrollments.view",
  },
  mutasi: {
    title: "Kenaikan & mutasi siswa",
    permission: "academic.enrollments.view",
  },
  kalender: {
    title: "Kalender akademik",
    permission: "academic.calendar.view",
  },
  penugasan: {
    title: "Penugasan mengajar & wali kelas",
    permission: "academic.assignments.view",
  },
  jadwal: {
    title: "Jadwal pelajaran",
    permission: "academic.schedules.view",
  },
  pengganti: {
    title: "Guru pengganti",
    permission: "academic.substitutes.view",
  },
};

export type AcademicRecord = {
  id: number;
  updated_at: string;
  [key: string]: unknown;
};

export type AcademicPage<T = AcademicRecord> = {
  data: T[];
  meta: { current_page: number; last_page: number; total: number };
};

export type AcademicContext = {
  active_academic_year_id: number | null;
  active_semester_id: number | null;
  lock_version: number;
  year: { id: number; code: string; name: string; status: string } | null;
  semester: { id: number; number: number; name: string; status: string } | null;
};

export type Option = { value: string; label: string };

export const academicRequest = <T>(
  path: string,
  method = "GET",
  data?: unknown,
) => apiRequest<T>("academic", path, method, data);

export const academicError = requestError;

export function academicListPath(
  resource: string,
  params: Record<string, string | number | undefined>,
): string {
  const search = new URLSearchParams();
  for (const [key, value] of Object.entries(params)) {
    if (value !== undefined && value !== "") search.set(key, String(value));
  }
  const suffix = search.size ? `?${search.toString()}` : "";

  return `${resource}${suffix}`;
}

export const dateOnly = (value: unknown): string => {
  if (typeof value !== "string" || !value) return "—";
  const [year, month, day] = value.slice(0, 10).split("-");

  return day && month && year ? `${day}/${month}/${year}` : "—";
};

const optional = (
  name: string,
  label: string,
  max: number,
  type: FieldDefinition["type"] = "text",
): FieldDefinition => ({ name, label, max, type });

export const PERIOD_FIELDS = (creating: boolean): FieldDefinition[] => [
  ...(creating
    ? [
        {
          name: "code",
          label: "Kode tahun ajaran",
          required: true,
          max: 20,
          hint: "Contoh: 2026/2027",
        } satisfies FieldDefinition,
      ]
    : []),
  { name: "name", label: "Nama tahun ajaran", required: true, max: 50 },
  {
    name: "start_date",
    label: "Tanggal mulai",
    required: true,
    max: 10,
    type: "date",
  },
  {
    name: "end_date",
    label: "Tanggal berakhir",
    required: true,
    max: 10,
    type: "date",
  },
];

export const SEMESTER_FIELDS = (creating: boolean): FieldDefinition[] => [
  ...(creating
    ? [
        {
          name: "number",
          label: "Semester",
          required: true,
          type: "select" as const,
          options: [
            { value: "1", label: "Semester 1" },
            { value: "2", label: "Semester 2" },
          ],
        },
      ]
    : []),
  { name: "name", label: "Nama semester", required: true, max: 50 },
  {
    name: "start_date",
    label: "Tanggal mulai",
    required: true,
    max: 10,
    type: "date",
  },
  {
    name: "end_date",
    label: "Tanggal berakhir",
    required: true,
    max: 10,
    type: "date",
  },
];

export const CLASS_FIELDS = (creating: boolean): FieldDefinition[] => [
  ...(creating
    ? [{ name: "code", label: "Kode rombel", required: true, max: 30 }]
    : []),
  { name: "name", label: "Nama rombel", required: true, max: 100 },
  {
    name: "grade",
    label: "Tingkat",
    required: true,
    type: "select",
    options: [
      { value: "7", label: "Kelas 7" },
      { value: "8", label: "Kelas 8" },
      { value: "9", label: "Kelas 9" },
    ],
  },
];

export const CLASS_PERIOD_FIELDS = (
  creating: boolean,
  yearOptions: Option[],
): FieldDefinition[] => [
  ...(creating
    ? [
        {
          name: "class_id",
          label: "Rombongan belajar (identitas)",
          required: true,
          type: "select" as const,
          options: [] as Option[],
          hint: "Pilih setelah daftar kelas termuat",
        },
        {
          name: "academic_year_id",
          label: "Tahun ajaran",
          required: true,
          type: "select" as const,
          options: yearOptions,
        },
      ]
    : []),
  {
    name: "display_name",
    label: "Nama tampilan periode",
    required: true,
    max: 100,
  },
  optional("capacity", "Kapasitas", 3),
];

export const ENROLLMENT_FIELDS = (
  studentOptions: Option[],
  periodOptions: Option[],
): FieldDefinition[] => [
  {
    name: "student_id",
    label: "Siswa",
    required: true,
    type: "select",
    options: studentOptions,
  },
  {
    name: "class_period_id",
    label: "Rombongan belajar",
    required: true,
    type: "select",
    options: periodOptions,
  },
  {
    name: "effective_from",
    label: "Tanggal mulai keanggotaan",
    required: true,
    max: 10,
    type: "date",
  },
  {
    name: "entry_reason",
    label: "Alasan masuk",
    required: true,
    type: "select",
    options: [
      { value: "admission", label: "Penerimaan siswa" },
      { value: "transfer", label: "Masuk dari madrasah lain" },
      { value: "correction", label: "Koreksi data" },
    ],
  },
];

export const CALENDAR_FIELDS = (yearOptions: Option[]): FieldDefinition[] => [
  { name: "title", label: "Nama kejadian", required: true, max: 200 },
  {
    name: "event_type",
    label: "Jenis",
    required: true,
    type: "select",
    options: [
      { value: "holiday", label: "Libur" },
      { value: "activity", label: "Kegiatan" },
      { value: "exam", label: "Ujian" },
      { value: "other", label: "Lainnya" },
    ],
  },
  {
    name: "academic_year_id",
    label: "Tahun ajaran",
    required: true,
    type: "select",
    options: yearOptions,
  },
  {
    name: "start_date",
    label: "Tanggal mulai",
    required: true,
    max: 10,
    type: "date",
  },
  {
    name: "end_date",
    label: "Tanggal berakhir",
    required: true,
    max: 10,
    type: "date",
  },
  {
    name: "blocks_teaching",
    label: "Memblokir jadwal mengajar",
    type: "boolean",
  },
  {
    name: "blocks_employee_work",
    label: "Memblokir hari kerja pegawai",
    type: "boolean",
  },
  optional("description", "Keterangan", 5000, "textarea"),
];

export const TEACHING_FIELDS = (
  employeeOptions: Option[],
  subjectOptions: Option[],
  periodOptions: Option[],
  semesterOptions: Option[],
): FieldDefinition[] => [
  {
    name: "employee_id",
    label: "Guru",
    required: true,
    type: "select",
    options: employeeOptions,
  },
  {
    name: "subject_id",
    label: "Mata pelajaran",
    required: true,
    type: "select",
    options: subjectOptions,
  },
  {
    name: "class_period_id",
    label: "Rombongan belajar",
    required: true,
    type: "select",
    options: periodOptions,
  },
  {
    name: "semester_id",
    label: "Semester",
    required: true,
    type: "select",
    options: semesterOptions,
  },
  optional("planned_weekly_jp", "JP per minggu (rencana)", 5),
  {
    name: "effective_from",
    label: "Berlaku mulai",
    required: true,
    max: 10,
    type: "date",
  },
  optional("effective_to", "Berlaku sampai (kosongkan bila masih)", 10, "date"),
];

export const HOMEROOM_FIELDS = (
  employeeOptions: Option[],
  periodOptions: Option[],
): FieldDefinition[] => [
  {
    name: "employee_id",
    label: "Wali kelas",
    required: true,
    type: "select",
    options: employeeOptions,
  },
  {
    name: "class_period_id",
    label: "Rombongan belajar",
    required: true,
    type: "select",
    options: periodOptions,
  },
  {
    name: "effective_from",
    label: "Berlaku mulai",
    required: true,
    max: 10,
    type: "date",
  },
];

export const STATUS_OPTIONS = [
  { value: "planned", label: "Direncanakan" },
  { value: "open", label: "Terbuka" },
  { value: "closed", label: "Ditutup" },
];

export const statusLabel = (status: unknown): string =>
  STATUS_OPTIONS.find((option) => option.value === status)?.label ??
  (typeof status === "string" ? status : "—");

export const eventTypeLabel = (type: unknown): string =>
  ({ holiday: "Libur", activity: "Kegiatan", exam: "Ujian", other: "Lainnya" })[
    type as string
  ] ?? "—";

export const DAY_OPTIONS: Option[] = [
  { value: "1", label: "Senin" },
  { value: "2", label: "Selasa" },
  { value: "3", label: "Rabu" },
  { value: "4", label: "Kamis" },
  { value: "5", label: "Jumat" },
  { value: "6", label: "Sabtu" },
];

export const dayLabel = (day: unknown): string =>
  DAY_OPTIONS.find((option) => option.value === String(day))?.label ?? "—";

export const timeOnly = (value: unknown): string =>
  typeof value === "string" && value ? value.slice(0, 5) : "—";

export const versionStatusLabel = (status: unknown): string =>
  ({
    draft: "Draf",
    published: "Terbit",
    superseded: "Digantikan",
  })[status as string] ?? (typeof status === "string" ? status : "—");
