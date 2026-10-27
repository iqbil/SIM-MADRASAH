"use client";

import { useCallback, useState } from "react";
import {
  AcademicHeading,
  AcademicList,
  ConfirmAction,
  FilterSelect,
} from "@/components/academic/academic-shared";
import { Badge } from "@/components/ui/badge";
import { Button } from "@/components/ui/button";
import { Tabs, TabsList, TabsTrigger } from "@/components/ui/tabs";
import type { AcademicPage, AcademicRecord } from "@/lib/academic";
import {
  attendanceError,
  attendanceRequest,
  dateOnly,
  statusLabels,
  wibDateTime,
} from "@/lib/attendance";
import {
  studentAttendanceError,
  studentAttendanceRequest,
} from "@/lib/student-attendance";

type CorrectionRow = AcademicRecord & {
  reason: string;
  status: string;
  requested_at: string;
  approved_at: string | null;
  rejection_reason: string | null;
  before_values: Record<string, unknown>;
  after_values: Record<string, unknown>;
  requester?: { person?: { full_name?: string } | null } | null;
  approver?: { person?: { full_name?: string } | null } | null;
  employeeAttendance?: {
    id: number;
    check_in_at: string | null;
    check_out_at: string | null;
    attendanceStatus?: { code: string; name: string } | null;
    workDay?: {
      work_date: string;
      employee?: { person?: { full_name?: string } | null } | null;
    } | null;
  } | null;
};

type StudentCorrectionRow = AcademicRecord & {
  reason: string;
  status: string;
  requested_at: string;
  approved_at: string | null;
  rejection_reason: string | null;
  before_values: Record<string, unknown>;
  after_values: Record<string, unknown>;
  requester?: { person?: { full_name?: string } | null } | null;
  approver?: { person?: { full_name?: string } | null } | null;
  studentDailyAttendance?: {
    id: number;
    attendance_date: string;
    student?: { person?: { full_name?: string } | null } | null;
    classPeriod?: { display_name?: string } | null;
  } | null;
  student?: { person?: { full_name?: string } | null } | null;
};

const describe = (values: Record<string, unknown>): string => {
  const parts: string[] = [];
  if (values.status_code)
    parts.push(
      `status → ${statusLabels[String(values.status_code)] ?? values.status_code}`,
    );
  if (values.check_in_at)
    parts.push(`masuk ${wibDateTime(values.check_in_at)}`);
  if (values.check_out_at)
    parts.push(`pulang ${wibDateTime(values.check_out_at)}`);
  if (values.notes) parts.push(`catatan: ${String(values.notes).slice(0, 40)}`);

  return parts.length > 0 ? parts.join(" · ") : "—";
};

export function AttendanceCorrectionRequests({
  canCorrect,
}: {
  canCorrect: boolean;
}) {
  const [domain, setDomain] = useState<"pegawai" | "siswa">("pegawai");
  const [status, setStatus] = useState("pending");
  const [deciding, setDeciding] = useState<{
    row: CorrectionRow;
    decision: "approve" | "reject";
  } | null>(null);
  const [decidingStudent, setDecidingStudent] = useState<{
    row: StudentCorrectionRow;
    decision: "approve" | "reject";
  } | null>(null);
  const [tick, setTick] = useState(0);
  const reload = useCallback(() => setTick((value) => value + 1), []);

  return (
    <div className="animate-enter space-y-6">
      <AcademicHeading
        category="Absensi pegawai"
        title="Persetujuan koreksi absensi"
        description="Koreksi disetujui petugas yang berbeda dari pemohon, setelah diterapkan hanya nilai kehadiran yang berubah — bukti foto/GPS asli tetap utuh."
      />
      {canCorrect && (
        <Tabs
          value={domain}
          onValueChange={(val) => setDomain(val as "pegawai" | "siswa")}
          className="w-full"
        >
          <TabsList className="h-14! w-full justify-start gap-1 rounded-xl bg-secondary p-1 sm:w-auto">
            <TabsTrigger
              value="pegawai"
              className="h-11! min-w-0 flex-1 px-3 text-xs sm:flex-none"
            >
              Koreksi pegawai
            </TabsTrigger>
            <TabsTrigger
              value="siswa"
              className="h-11! min-w-0 flex-1 px-3 text-xs sm:flex-none"
            >
              Koreksi siswa
            </TabsTrigger>
          </TabsList>
        </Tabs>
      )}
      <div className="flex flex-wrap items-end gap-3">
        <FilterSelect
          label="Status koreksi"
          value={status}
          onChange={setStatus}
          options={[
            { value: "pending", label: "Menunggu" },
            { value: "applied", label: "Diterapkan" },
            { value: "rejected", label: "Ditolak" },
          ]}
          placeholder="Semua"
          className="w-full sm:w-48"
        />
      </div>
      {domain === "pegawai" ? (
        <AcademicList<CorrectionRow>
          reloadKey={`corrections-${status}-${tick}`}
          load={async (page) =>
            attendanceRequest<AcademicPage<CorrectionRow>>(
              `corrections?page=${page}&per_page=25${status ? `&status=${status}` : ""}`,
            )
          }
          columns={[
            {
              label: "Pegawai",
              cell: (row) => (
                <span className="text-sm font-medium">
                  {row.employeeAttendance?.workDay?.employee?.person
                    ?.full_name ?? "—"}
                </span>
              ),
            },
            {
              label: "Tanggal",
              cell: (row) =>
                row.employeeAttendance?.workDay
                  ? dateOnly(row.employeeAttendance.workDay.work_date)
                  : "—",
            },
            {
              label: "Perubahan",
              cell: (row) => (
                <span className="text-xs">
                  {describe(row.before_values)} → {describe(row.after_values)}
                </span>
              ),
            },
            {
              label: "Pemohon · alasan",
              cell: (row) => (
                <span className="text-xs">
                  {row.requester?.person?.full_name ?? "—"} · {row.reason}
                </span>
              ),
            },
            {
              label: "Status",
              cell: (row) =>
                row.status === "pending" ? (
                  <Badge variant="outline" className="text-xs">
                    Menunggu
                  </Badge>
                ) : row.status === "applied" ? (
                  <Badge
                    variant="secondary"
                    className="bg-primary/8 text-xs text-primary"
                  >
                    Diterapkan
                  </Badge>
                ) : (
                  <Badge variant="outline" className="text-xs">
                    Ditolak
                  </Badge>
                ),
            },
          ]}
          rowTitle={(row) =>
            row.employeeAttendance?.workDay?.employee?.person?.full_name ??
            "Koreksi"
          }
          rowMeta={(row) =>
            `${describe(row.before_values)} → ${describe(row.after_values)} · ${row.reason}`
          }
          rowActions={
            canCorrect
              ? (row) =>
                  row.status === "pending" ? (
                    <div className="flex gap-2">
                      <Button
                        className="h-11 text-xs"
                        onClick={() =>
                          setDeciding({ row, decision: "approve" })
                        }
                      >
                        Setujui
                      </Button>
                      <Button
                        variant="outline"
                        className="h-11 text-xs"
                        onClick={() => setDeciding({ row, decision: "reject" })}
                      >
                        Tolak
                      </Button>
                    </div>
                  ) : null
              : undefined
          }
          emptyTitle="Tidak ada koreksi pada filter ini"
          emptyHint="Koreksi diajukan dari menu Absensi & izin pegawai oleh petugas."
        />
      ) : (
        <AcademicList<StudentCorrectionRow>
          reloadKey={`student-corrections-${status}-${tick}`}
          load={async (page) => {
            const res = await studentAttendanceRequest<{
              data: { rows: AcademicPage<StudentCorrectionRow> };
            }>(
              `corrections?page=${page}&per_page=25${status ? `&status=${status}` : ""}`,
            );
            return (
              res.data?.rows ?? {
                data: [],
                meta: { current_page: 1, last_page: 1, total: 0 },
              }
            );
          }}
          columns={[
            {
              label: "Siswa",
              cell: (row) => (
                <span className="text-sm font-medium">
                  {row.studentDailyAttendance?.student?.person?.full_name ??
                    row.student?.person?.full_name ??
                    "—"}
                </span>
              ),
            },
            {
              label: "Kelas & tanggal",
              cell: (row) => (
                <span className="text-xs">
                  {row.studentDailyAttendance?.classPeriod?.display_name ?? "—"}
                  {row.studentDailyAttendance?.attendance_date &&
                    ` · ${dateOnly(row.studentDailyAttendance.attendance_date)}`}
                </span>
              ),
            },
            {
              label: "Perubahan",
              cell: (row) => (
                <span className="text-xs">
                  {describe(row.before_values)} → {describe(row.after_values)}
                </span>
              ),
            },
            {
              label: "Pemohon · alasan",
              cell: (row) => (
                <span className="text-xs">
                  {row.requester?.person?.full_name ?? "—"} · {row.reason}
                </span>
              ),
            },
            {
              label: "Status",
              cell: (row) =>
                row.status === "pending" ? (
                  <Badge variant="outline" className="text-xs">
                    Menunggu
                  </Badge>
                ) : row.status === "applied" ? (
                  <Badge
                    variant="secondary"
                    className="bg-primary/8 text-xs text-primary"
                  >
                    Diterapkan
                  </Badge>
                ) : (
                  <Badge variant="outline" className="text-xs">
                    Ditolak
                  </Badge>
                ),
            },
          ]}
          rowTitle={(row) =>
            row.studentDailyAttendance?.student?.person?.full_name ??
            row.student?.person?.full_name ??
            "Koreksi siswa"
          }
          rowMeta={(row) =>
            `${describe(row.before_values)} → ${describe(row.after_values)} · ${row.reason}`
          }
          rowActions={
            canCorrect
              ? (row) =>
                  row.status === "pending" ? (
                    <div className="flex gap-2">
                      <Button
                        className="h-11 text-xs"
                        onClick={() =>
                          setDecidingStudent({ row, decision: "approve" })
                        }
                      >
                        Setujui
                      </Button>
                      <Button
                        variant="outline"
                        className="h-11 text-xs"
                        onClick={() =>
                          setDecidingStudent({ row, decision: "reject" })
                        }
                      >
                        Tolak
                      </Button>
                    </div>
                  ) : null
              : undefined
          }
          emptyTitle="Tidak ada koreksi siswa pada filter ini"
          emptyHint="Koreksi diajukan dari menu Absensi harian siswa oleh guru atau wali kelas."
        />
      )}

      {deciding && (
        <ConfirmAction
          title={
            deciding.decision === "approve"
              ? "Setujui koreksi kehadiran"
              : "Tolak koreksi kehadiran"
          }
          description={`${describe(deciding.row.before_values)} → ${describe(deciding.row.after_values)}. ${
            deciding.decision === "approve"
              ? "Perubahan diterapkan pada data kehadiran; bukti asli tetap utuh."
              : "Koreksi ditolak dan tidak mengubah data."
          }`}
          submitLabel={
            deciding.decision === "approve"
              ? "Setujui koreksi"
              : "Tolak koreksi"
          }
          destructive={deciding.decision === "reject"}
          onClose={() => setDeciding(null)}
          action={async (reason) => {
            try {
              await attendanceRequest(
                `corrections/${deciding.row.id}/decision`,
                "POST",
                { decision: deciding.decision, reason },
              );
              reload();
            } catch (error) {
              throw new Error(attendanceError(error));
            }
          }}
        />
      )}

      {decidingStudent && (
        <ConfirmAction
          title={
            decidingStudent.decision === "approve"
              ? "Setujui koreksi absensi siswa"
              : "Tolak koreksi absensi siswa"
          }
          description={`${describe(decidingStudent.row.before_values)} → ${describe(decidingStudent.row.after_values)}. ${
            decidingStudent.decision === "approve"
              ? "Perubahan diterapkan pada data absensi siswa."
              : "Koreksi ditolak dan tidak mengubah data."
          }`}
          submitLabel={
            decidingStudent.decision === "approve"
              ? "Setujui koreksi"
              : "Tolak koreksi"
          }
          destructive={decidingStudent.decision === "reject"}
          onClose={() => setDecidingStudent(null)}
          action={async (reason) => {
            try {
              await studentAttendanceRequest(
                `corrections/${decidingStudent.row.id}/decision`,
                "POST",
                { decision: decidingStudent.decision, reason },
              );
              reload();
            } catch (error) {
              throw new Error(studentAttendanceError(error));
            }
          }}
        />
      )}
    </div>
  );
}
