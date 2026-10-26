import type { Metadata } from "next";
import { notFound } from "next/navigation";
import { Suspense } from "react";
import { PageSkeleton } from "@/components/layout/page-skeleton";
import { DailyAttendance } from "@/components/student-attendance/daily-attendance";
import { StudentRecap } from "@/components/student-attendance/student-recap";
import { requireUser } from "@/lib/auth/session";
import {
  STUDENT_ATTENDANCE_SECTIONS,
  type StudentAttendanceSection,
} from "@/lib/student-attendance";

export const metadata: Metadata = { title: "Absensi siswa" };

export function generateStaticParams() {
  return Object.keys(STUDENT_ATTENDANCE_SECTIONS).map((section) => ({
    section,
  }));
}

export default function StudentAttendancePage({
  params,
}: {
  params: Promise<{ section: string }>;
}) {
  return (
    <Suspense fallback={<PageSkeleton />}>
      <StudentAttendancePageContent params={params} />
    </Suspense>
  );
}

async function StudentAttendancePageContent({
  params,
}: {
  params: Promise<{ section: string }>;
}) {
  const { section } = await params;
  if (!Object.hasOwn(STUDENT_ATTENDANCE_SECTIONS, section)) notFound();
  const user = await requireUser(`/absensi-siswa/${section}`);
  const nav = new Set(user.navigation ?? []);
  // Pencatat = guru/wali kelas (token students.attendance); koreksi siswa
  // disetujui lewat halaman koreksi petugas (mandat attendance.correct).
  if (!nav.has("students.attendance")) notFound();
  switch (section as StudentAttendanceSection) {
    case "harian":
      return <DailyAttendance />;
    case "rekap":
      return <StudentRecap />;
  }
}
