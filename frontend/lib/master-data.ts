import { apiRequest, apiResponse, requestError } from "@/lib/api-client";

export type Resource =
  | "students"
  | "guardians"
  | "employees"
  | "positions"
  | "employee-categories"
  | "subjects"
  | "student-statuses"
  | "attendance-statuses"
  | "institution";
export type ResourceCapability = {
  view: boolean;
  manage: boolean;
  status: boolean;
};
export type MasterOptions = {
  can_manage: boolean;
  capabilities?: Partial<Record<Resource, ResourceCapability>>;
  resources?: Resource[];
  student_scope?: "all" | "active_assignments";
  can_verify_guardians?: boolean;
  can_import?: boolean;
  import_resources?: Resource[];
};
export type MasterRecord = {
  id: number;
  updated_at: string;
  created_at?: string;
  code?: string;
  name?: string;
  internal_code?: string;
  employee_number?: string;
  is_active?: boolean;
  archived_at?: string | null;
  status_id?: number;
  person_id?: number;
  person?: {
    id: number;
    full_name: string;
    updated_at: string;
    birth_date: string | null;
    [key: string]: unknown;
  };
  [key: string]: unknown;
};
export type MasterPage<T = MasterRecord> = {
  data: T[];
  meta: {
    current_page: number;
    last_page: number;
    total: number;
    owner_updated_at?: string;
  };
};
export type FieldDefinition = {
  name: string;
  label: string;
  type?:
    | "text"
    | "textarea"
    | "date"
    | "time"
    | "number"
    | "email"
    | "url"
    | "select"
    | "boolean";
  required?: boolean;
  max?: number;
  step?: number;
  options?: { value: string; label: string }[];
  immutable?: boolean;
  pattern?: string;
  hint?: string;
};
const optional = (
  name: string,
  label: string,
  max: number,
  type: FieldDefinition["type"] = "text",
): FieldDefinition => ({ name, label, max, type });
export const PERSON_FIELDS: FieldDefinition[] = [
  { name: "person.full_name", label: "Nama lengkap", required: true, max: 200 },
  {
    name: "person.sex",
    label: "Jenis kelamin",
    type: "select",
    options: [
      { value: "L", label: "Laki-laki" },
      { value: "P", label: "Perempuan" },
    ],
  },
  optional("person.birth_place", "Tempat lahir", 100),
  optional("person.birth_date", "Tanggal lahir", 10, "date"),
  optional("person.address", "Alamat", 5000, "textarea"),
  optional("person.phone", "Nomor telepon", 30),
  optional("person.contact_email", "Email kontak", 254, "email"),
];
export const RESOURCE_CONFIG: Record<
  Resource,
  {
    title: string;
    singular: string;
    description: string;
    fields: FieldDefinition[];
  }
> = {
  institution: {
    title: "Identitas madrasah",
    singular: "identitas madrasah",
    description: "Informasi resmi dan kontak madrasah dalam satu sumber data.",
    fields: [
      { name: "name", label: "Nama madrasah", required: true, max: 200 },
      optional("npsn", "NPSN", 20),
      optional("nsm", "NSM", 30),
      optional("address", "Alamat madrasah", 5000, "textarea"),
      optional("phone", "Nomor telepon", 30),
      optional("email", "Email madrasah", 254, "email"),
      optional("website", "Website", 255, "url"),
      {
        name: "timezone",
        label: "Zona waktu operasional",
        type: "select",
        required: true,
        options: [
          { value: "Asia/Jakarta", label: "WIB — Jakarta" },
          { value: "Asia/Makassar", label: "WITA — Makassar" },
          { value: "Asia/Jayapura", label: "WIT — Jayapura" },
        ],
      },
    ],
  },
  students: {
    title: "Data siswa",
    singular: "siswa",
    description:
      "Kelola identitas siswa dan hubungan dengan orang tua atau wali.",
    fields: [
      {
        name: "internal_code",
        label: "Kode internal siswa",
        required: true,
        max: 50,
      },
      optional("nis", "NIS", 30),
      {
        ...optional("nisn", "NISN", 10),
        pattern: "[0-9]{10}",
        hint: "NISN terdiri dari 10 digit angka.",
      },
      optional("admitted_on", "Tanggal masuk", 10, "date"),
    ],
  },
  guardians: {
    title: "Orang tua & wali",
    singular: "wali",
    description:
      "Data orang tua dan wali menggunakan identitas orang yang sama bila sudah terdaftar.",
    fields: [
      {
        name: "internal_code",
        label: "Kode internal wali",
        required: true,
        max: 50,
      },
      optional("occupation", "Pekerjaan", 150),
    ],
  },
  employees: {
    title: "Data pegawai",
    singular: "pegawai",
    description:
      "Kelola identitas pegawai beserta riwayat jabatan dan kategorinya.",
    fields: [
      {
        name: "employee_number",
        label: "Nomor pegawai",
        required: true,
        max: 50,
      },
      optional("nip", "NIP", 30),
      optional("nuptk", "NUPTK", 30),
      optional("joined_on", "Tanggal bergabung", 10, "date"),
      optional("left_on", "Tanggal berhenti", 10, "date"),
    ],
  },
  positions: {
    title: "Jabatan",
    singular: "jabatan",
    description:
      "Daftar jabatan organisasi madrasah. Jabatan tidak memberikan role login.",
    fields: [
      {
        name: "code",
        label: "Kode jabatan",
        required: true,
        max: 50,
        immutable: true,
      },
      { name: "name", label: "Nama jabatan", required: true, max: 150 },
      optional("description", "Deskripsi", 5000, "textarea"),
    ],
  },
  "employee-categories": {
    title: "Kategori pegawai",
    singular: "kategori pegawai",
    description:
      "Referensi kategori pegawai untuk penugasan dan riwayat kepegawaian.",
    fields: [
      {
        name: "code",
        label: "Kode kategori",
        required: true,
        max: 30,
        immutable: true,
      },
      { name: "name", label: "Nama kategori", required: true, max: 100 },
    ],
  },
  subjects: {
    title: "Mata pelajaran",
    singular: "mata pelajaran",
    description:
      "Referensi mata pelajaran untuk penugasan dan kegiatan pembelajaran.",
    fields: [
      {
        name: "code",
        label: "Kode mata pelajaran",
        required: true,
        max: 50,
        immutable: true,
      },
      { name: "name", label: "Nama mata pelajaran", required: true, max: 150 },
      optional("group_name", "Kelompok mata pelajaran", 100),
    ],
  },
  "student-statuses": {
    title: "Status siswa",
    singular: "status siswa",
    description:
      "Referensi status akademik siswa. Perubahan status siswa diproses melalui modul akademik.",
    fields: [
      {
        name: "code",
        label: "Kode status",
        required: true,
        max: 30,
        immutable: true,
      },
      { name: "name", label: "Nama status", required: true, max: 100 },
      {
        name: "is_terminal",
        label: "Status akhir (lulus, pindah, atau keluar)",
        type: "boolean",
        immutable: true,
      },
    ],
  },
  "attendance-statuses": {
    title: "Status kehadiran",
    singular: "status kehadiran",
    description:
      "Pisahkan referensi kehadiran pegawai dan siswa sesuai penggunaannya.",
    fields: [
      {
        name: "domain",
        label: "Jenis kehadiran",
        type: "select",
        required: true,
        immutable: true,
        options: [
          { value: "employee", label: "Pegawai" },
          { value: "student", label: "Siswa" },
        ],
      },
      {
        name: "code",
        label: "Kode status",
        required: true,
        max: 30,
        immutable: true,
      },
      { name: "name", label: "Nama status", required: true, max: 100 },
    ],
  },
};
export const MASTER_SECTIONS = {
  institution: { title: "Identitas madrasah", resources: ["institution"] },
  students: { title: "Data siswa", resources: ["students"] },
  guardians: { title: "Orang tua & wali", resources: ["guardians"] },
  employees: { title: "Data pegawai", resources: ["employees"] },
  positions: {
    title: "Jabatan & kategori pegawai",
    resources: ["positions", "employee-categories"],
  },
  subjects: { title: "Mata pelajaran", resources: ["subjects"] },
  references: {
    title: "Referensi status",
    resources: ["student-statuses", "attendance-statuses"],
  },
  imports: { title: "Impor data", resources: [] },
} satisfies Record<string, { title: string; resources: Resource[] }>;
export type MasterSection = keyof typeof MASTER_SECTIONS;
export const hasPerson = (resource: Resource) =>
  ["students", "guardians", "employees"].includes(resource);
export const recordName = (record: MasterRecord) =>
  record.person?.full_name ?? record.name ?? "Tanpa nama";
export const recordCode = (record: MasterRecord) =>
  record.internal_code ?? record.employee_number ?? record.code ?? "—";
export const recordActive = (record: MasterRecord) =>
  record.archived_at !== undefined
    ? !record.archived_at
    : record.is_active !== false;
export const masterRequest = <T>(
  path: string,
  method = "GET",
  data?: unknown,
) => apiRequest<T>("master", path, method, data);
export const masterError = requestError;
export function dateOnly(value: unknown): string {
  if (typeof value !== "string" || !value) return "—";
  const [year, month, day] = value.slice(0, 10).split("-");
  return day && month && year ? `${day}/${month}/${year}` : "—";
}
export function fieldValue(
  record: MasterRecord | undefined,
  name: string,
): unknown {
  if (name.startsWith("person.")) return record?.person?.[name.slice(7)];
  return record?.[name];
}
export async function downloadMaster(
  path: string,
  filename: string,
): Promise<void> {
  const response = await apiResponse("master", path);
  const blob = await response.blob();
  const url = URL.createObjectURL(blob);
  const anchor = document.createElement("a");
  anchor.href = url;
  anchor.download = filename;
  document.body.append(anchor);
  anchor.click();
  anchor.remove();
  window.setTimeout(() => URL.revokeObjectURL(url), 1000);
}
