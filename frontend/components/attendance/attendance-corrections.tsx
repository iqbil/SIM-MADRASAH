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
import type { AcademicPage, AcademicRecord } from "@/lib/academic";
import {
  attendanceError,
  attendanceRequest,
  dateOnly,
  statusLabels,
  wibDateTime,
} from "@/lib/attendance";

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
  const [status, setStatus] = useState("pending");
  const [deciding, setDeciding] = useState<{
    row: CorrectionRow;
    decision: "approve" | "reject";
  } | null>(null);
  const [tick, setTick] = useState(0);
  const reload = useCallback(() => setTick((value) => value + 1), []);

  return (
    <div className="animate-enter space-y-6">
      <AcademicHeading
        title="Persetujuan koreksi absensi"
        description="Koreksi disetujui petugas yang berbeda dari pemohon; setelah diterapkan hanya nilai kehadiran yang berubah — bukti foto/GPS asli tetap utuh."
      />
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
              <span className="text-[13px] font-medium">
                {row.employeeAttendance?.workDay?.employee?.person?.full_name ??
                  "—"}
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
                <Badge variant="outline" className="text-[10px]">
                  Menunggu
                </Badge>
              ) : row.status === "applied" ? (
                <Badge
                  variant="secondary"
                  className="bg-primary/8 text-[10px] text-primary"
                >
                  Diterapkan
                </Badge>
              ) : (
                <Badge variant="outline" className="text-[10px]">
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
                      onClick={() => setDeciding({ row, decision: "approve" })}
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
    </div>
  );
}
