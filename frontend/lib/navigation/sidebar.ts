import {
  BookOpenCheck,
  CalendarDays,
  ChartNoAxesCombined,
  ClipboardCheck,
  Database,
  FileQuestion,
  Globe,
  HeartHandshake,
  Landmark,
  Mail,
  Settings2,
  UsersRound,
  Wallet,
} from "lucide-react";
import type { AuthUser } from "@/lib/auth/types";

interface SidebarItem {
  id: string;
  label: string;
  access: string;
  href?: string;
}

export interface SidebarSection {
  id: string;
  label: string;
  icon: typeof Database;
  items: SidebarItem[];
}

// Keys describe navigation access returned by Laravel, not route authorization.
export const SIDEBAR_SECTIONS: SidebarSection[] = [
  {
    id: "master",
    label: "Data master",
    icon: Database,
    items: [
      {
        id: "institution",
        href: "/data-master/institution",
        label: "Identitas madrasah",
        access: "master.institution.view",
      },
      {
        id: "students",
        href: "/data-master/students",
        label: "Data siswa",
        access: "master.students.view",
      },
      {
        id: "guardians",
        href: "/data-master/guardians",
        label: "Orang tua & wali",
        access: "master.guardians.view",
      },
      {
        id: "employees",
        href: "/data-master/employees",
        label: "Data pegawai",
        access: "master.employees.view",
      },
      {
        id: "positions",
        href: "/data-master/positions",
        label: "Jabatan & kategori pegawai",
        access: "master.positions.view",
      },
      {
        id: "subjects",
        href: "/data-master/subjects",
        label: "Mata pelajaran",
        access: "master.subjects.view",
      },
      {
        id: "references",
        href: "/data-master/references",
        label: "Referensi status",
        access: "master.student-statuses.view",
      },
      {
        id: "imports",
        href: "/data-master/imports",
        label: "Impor data",
        access: "master.imports",
      },
    ],
  },
  {
    id: "academic",
    label: "Akademik & kelas",
    icon: CalendarDays,
    items: [
      {
        id: "years",
        href: "/akademik/tahun-ajaran",
        label: "Tahun ajaran & semester",
        access: "academic.years.view",
      },
      {
        id: "classes",
        href: "/akademik/rombel",
        label: "Rombongan belajar",
        access: "academic.classes.view",
      },
      {
        id: "enrollments",
        href: "/akademik/keanggotaan",
        label: "Keanggotaan & riwayat siswa",
        access: "academic.enrollments.view",
      },
      {
        id: "transitions",
        href: "/akademik/mutasi",
        label: "Kenaikan & mutasi siswa",
        access: "academic.enrollments.view",
      },
      {
        id: "calendar",
        href: "/akademik/kalender",
        label: "Kalender akademik",
        access: "academic.calendar.view",
      },
      {
        id: "assignments",
        href: "/akademik/penugasan",
        label: "Penugasan mengajar & Wali kelas",
        access: "academic.assignments.view",
      },
      {
        id: "schedules",
        href: "/akademik/jadwal",
        label: "Jadwal pelajaran",
        access: "academic.schedules.view",
      },
      {
        id: "substitutes",
        href: "/akademik/pengganti",
        label: "Guru pengganti",
        access: "academic.substitutes.view",
      },
      { id: "my-class", label: "Kelas perwalian saya", access: "class.own" },
    ],
  },
  {
    id: "employee-attendance",
    label: "Absensi pegawai",
    icon: ClipboardCheck,
    items: [
      { id: "check-in-out", label: "Masuk / pulang", access: "attendance.own" },
      {
        id: "my-attendance",
        label: "Riwayat absensi saya",
        access: "attendance.own",
      },
      {
        id: "attendance-records",
        label: "Absensi & izin pegawai",
        access: "attendance.manage",
      },
      {
        id: "work-patterns",
        label: "Pola kerja & kebijakan",
        access: "attendance.manage",
      },
      {
        id: "attendance-recap",
        label: "Rekap kehadiran pegawai",
        access: "attendance.read",
      },
      {
        id: "attendance-corrections",
        label: "Persetujuan koreksi absensi",
        access: "attendance.correct",
      },
    ],
  },
  {
    id: "teaching",
    label: "Realisasi mengajar",
    icon: BookOpenCheck,
    items: [
      {
        id: "my-meetings",
        label: "Pertemuan mengajar saya",
        access: "teaching.own",
      },
      {
        id: "my-realizations",
        label: "Realisasi & riwayat JP saya",
        access: "teaching.own",
      },
      {
        id: "verify-realizations",
        label: "Verifikasi realisasi",
        access: "teaching.verify",
      },
    ],
  },
  {
    id: "student-attendance",
    label: "Absensi siswa",
    icon: UsersRound,
    items: [
      {
        id: "daily-attendance",
        label: "Absensi harian siswa",
        access: "students.attendance",
      },
      {
        id: "student-attendance-recap",
        label: "Rekap kehadiran siswa",
        access: "students.attendance",
      },
    ],
  },
  {
    id: "payroll",
    label: "Honor & keuangan",
    icon: Wallet,
    items: [
      { id: "my-payroll", label: "Honor final saya", access: "payroll.own" },
      {
        id: "payroll-rates",
        label: "Tarif & komponen honor",
        access: "payroll.rates",
      },
      {
        id: "additional-duties",
        label: "Tugas tambahan & potongan",
        access: "payroll.calculate",
      },
      {
        id: "payroll-periods",
        label: "Periode & perhitungan honor",
        access: "payroll.calculate",
      },
      {
        id: "review-payroll",
        label: "Pemeriksaan honor",
        access: "payroll.review",
      },
      {
        id: "approve-payroll",
        label: "Pengesahan & penguncian honor",
        access: "payroll.approve",
      },
      {
        id: "adjust-payroll",
        label: "Penyesuaian honor",
        access: "payroll.adjust",
      },
    ],
  },
  {
    id: "questions",
    label: "Bank soal & paket",
    icon: FileQuestion,
    items: [
      { id: "my-questions", label: "Bank soal saya", access: "questions.own" },
      {
        id: "question-packages",
        label: "Paket soal & cetak naskah PDF",
        access: "questions.packages",
      },
      {
        id: "answer-keys",
        label: "Kunci jawaban & cetak PDF",
        access: "questions.keys",
      },
    ],
  },
  {
    id: "counseling",
    label: "Bimbingan & konseling",
    icon: HeartHandshake,
    items: [
      {
        id: "bk-cases",
        label: "Kasus & catatan internal",
        access: "bk.internal",
      },
      {
        id: "bk-followups",
        label: "Tindak lanjut & pembinaan",
        access: "bk.internal",
      },
      { id: "bk-sharing", label: "Pembagian ringkasan BK", access: "bk.share" },
      {
        id: "shared-bk",
        label: "Ringkasan BK untuk saya",
        access: "bk.shared",
      },
    ],
  },
  {
    id: "administration",
    label: "Tata usaha & arsip",
    icon: Mail,
    items: [
      {
        id: "incoming-letters",
        label: "Surat masuk",
        access: "letters.manage",
      },
      {
        id: "outgoing-letters",
        label: "Surat keluar & penomoran",
        access: "letters.manage",
      },
      { id: "letter-agendas", label: "Agenda surat", access: "letters.manage" },
      {
        id: "document-archive",
        label: "Arsip dokumen",
        access: "documents.access",
      },
    ],
  },
  {
    id: "guardian",
    label: "Portal orang tua / wali",
    icon: Landmark,
    items: [
      {
        id: "my-children",
        label: "Data & kelas anak",
        access: "guardian.portal",
      },
      {
        id: "child-attendance",
        label: "Kehadiran anak",
        access: "guardian.portal",
      },
      {
        id: "guardian-announcements",
        label: "Pengumuman untuk wali",
        access: "guardian.portal",
      },
    ],
  },
  {
    id: "website",
    label: "Website madrasah",
    icon: Globe,
    items: [
      {
        id: "website-profile",
        label: "Profil & halaman sekolah",
        access: "website.edit",
      },
      {
        id: "public-staff",
        label: "Profil publik guru & tendik",
        access: "website.edit",
      },
      { id: "news", label: "Berita & pengumuman", access: "website.edit" },
      { id: "activities", label: "Kegiatan & agenda", access: "website.edit" },
      { id: "gallery", label: "Galeri & prestasi", access: "website.edit" },
      { id: "downloads", label: "Unduhan & kontak", access: "website.edit" },
      {
        id: "publish-content",
        label: "Peninjauan & penerbitan",
        access: "website.publish",
      },
    ],
  },
  {
    id: "reports",
    label: "Laporan & ekspor",
    icon: ChartNoAxesCombined,
    items: [
      {
        id: "operational-reports",
        label: "Laporan operasional",
        access: "reports.view",
      },
      {
        id: "exports",
        label: "Permintaan & hasil ekspor",
        access: "reports.export",
      },
    ],
  },
  {
    id: "system",
    label: "Pengaturan sistem",
    icon: Settings2,
    items: [
      {
        id: "policies",
        label: "Konfigurasi & kebijakan",
        access: "system.manage",
      },
      { id: "audit", label: "Jejak audit sistem", access: "system.manage" },
      { id: "backups", label: "Backup & pemulihan", access: "system.manage" },
      {
        id: "retention",
        label: "Retensi & arsip sistem",
        access: "system.manage",
      },
      {
        id: "monitoring",
        label: "Monitoring operasional",
        access: "system.manage",
      },
    ],
  },
];

export function sidebarSections(user: AuthUser): SidebarSection[] {
  if (user.must_change_password) return [];
  const access = new Set(user.navigation ?? []);
  if (access.has("master.manage")) {
    access.add("master.institution.view");
    access.add("master.students.view");
    access.add("master.guardians.view");
    access.add("master.employees.view");
    access.add("master.positions.view");
    access.add("master.employee-categories.view");
    access.add("master.subjects.view");
    access.add("master.student-statuses.view");
    access.add("master.attendance-statuses.view");
    access.add("master.imports");
  }
  for (const token of ["academic.manage", "academic.view"]) {
    if (access.has(token)) {
      access.add("academic.years.view");
      access.add("academic.classes.view");
      access.add("academic.enrollments.view");
      access.add("academic.calendar.view");
      access.add("academic.assignments.view");
      access.add("academic.schedules.view");
      access.add("academic.substitutes.view");
      access.add("academic.read");
    }
  }
  if (access.has("attendance.manage") || access.has("attendance.reports"))
    access.add("attendance.read");
  return SIDEBAR_SECTIONS.map((section) => ({
    ...section,
    items: section.items.filter((item) => access.has(item.access)),
  })).filter((section) => section.items.length > 0);
}
