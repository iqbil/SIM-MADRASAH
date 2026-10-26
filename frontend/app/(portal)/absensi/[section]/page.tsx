import type { Metadata } from "next";
import { notFound } from "next/navigation";
import { Suspense } from "react";
import {
  AttendanceClock,
  AttendanceCorrectionRequests,
  AttendanceHistory,
  AttendancePatterns,
  AttendanceRecap,
  AttendanceRecords,
} from "@/components/attendance/attendance-workspace";
import { PageSkeleton } from "@/components/layout/page-skeleton";
import { ATTENDANCE_SECTIONS, type AttendanceSection } from "@/lib/attendance";
import { requireUser } from "@/lib/auth/session";

export const metadata: Metadata = { title: "Absensi pegawai" };

export function generateStaticParams() {
  return Object.keys(ATTENDANCE_SECTIONS).map((section) => ({ section }));
}

export default function AttendancePage({
  params,
}: {
  params: Promise<{ section: string }>;
}) {
  return (
    <Suspense fallback={<PageSkeleton />}>
      <AttendancePageContent params={params} />
    </Suspense>
  );
}

async function AttendancePageContent({
  params,
}: {
  params: Promise<{ section: string }>;
}) {
  const { section } = await params;
  if (!Object.hasOwn(ATTENDANCE_SECTIONS, section)) notFound();
  const user = await requireUser(`/absensi/${section}`);
  const nav = new Set(user.navigation ?? []);
  const config = ATTENDANCE_SECTIONS[section as AttendanceSection];
  if (!config.allow(nav)) notFound();
  const canManage = nav.has("attendance.manage");
  const canCorrect = nav.has("attendance.correct");
  switch (section as AttendanceSection) {
    case "masuk-pulang":
      return <AttendanceClock />;
    case "riwayat":
      return <AttendanceHistory />;
    case "pegawai":
      return <AttendanceRecords canManage={canManage} />;
    case "pola-kerja":
      return <AttendancePatterns />;
    case "rekap":
      return <AttendanceRecap />;
    case "koreksi":
      return <AttendanceCorrectionRequests canCorrect={canCorrect} />;
  }
}
