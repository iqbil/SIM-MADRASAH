"use client";

import { useCallback, useState } from "react";
import {
  AcademicHeading,
  AcademicList,
  ConfirmAction,
  DateFilter,
  FilterSelect,
} from "@/components/academic/academic-shared";
import { Badge } from "@/components/ui/badge";
import { Button } from "@/components/ui/button";
import type { AcademicPage, AcademicRecord } from "@/lib/academic";
import {
  dateOnly,
  executionLabels,
  teachingRequest,
  verificationLabels,
  wibMonthRange,
  wibTime,
} from "@/lib/teaching";

type RevisionRow = AcademicRecord & {
  revision_number: number;
  execution_status: string;
  verification_status: string;
  material: string | null;
  actual_jp: string;
  nonexecution_reason: string | null;
  submitted_at: string | null;
  meeting?: {
    meeting_date: string;
    start_time_snapshot: string;
    classPeriod?: { display_name?: string } | null;
    subject?: { name?: string } | null;
  } | null;
  actualEmployee?: { person?: { full_name?: string } | null } | null;
  submitter?: { person?: { full_name?: string } | null } | null;
  verification?: {
    action: string;
    reason: string | null;
    verified_at: string;
  } | null;
};

const badge = (status: string) => {
  if (status === "approved")
    return (
      <Badge
        variant="secondary"
        className="bg-primary/8 text-[10px] text-primary"
      >
        {verificationLabels.approved}
      </Badge>
    );
  if (status === "rejected")
    return (
      <Badge variant="outline" className="text-[10px] text-destructive">
        {verificationLabels.rejected}
      </Badge>
    );

  return (
    <Badge variant="secondary" className="text-[10px]">
      {verificationLabels.pending}
    </Badge>
  );
};

export function Verifications() {
  const range = wibMonthRange();
  const [from, setFrom] = useState(range.from);
  const [to, setTo] = useState(range.to);
  const [status, setStatus] = useState("pending");
  const [deciding, setDeciding] = useState<{
    row: RevisionRow;
    action: "approve" | "reject";
  } | null>(null);
  const [tick, setTick] = useState(0);
  const reload = useCallback(() => setTick((value) => value + 1), []);

  return (
    <div className="animate-enter space-y-6">
      <AcademicHeading
        title="Verifikasi realisasi"
        description="Keputusan verifikasi diberikan oleh verifikator bermandat yang berbeda dari pengaju; satu keputusan final per revisi dan penolakan memerlukan alasan."
      />
      <div className="flex flex-wrap items-end gap-3">
        <DateFilter label="Dari tanggal" value={from} onChange={setFrom} />
        <DateFilter label="Sampai" value={to} onChange={setTo} />
        <FilterSelect
          label="Status"
          value={status}
          onChange={setStatus}
          options={[
            { value: "pending", label: verificationLabels.pending },
            { value: "approved", label: verificationLabels.approved },
            { value: "rejected", label: verificationLabels.rejected },
          ]}
          placeholder="Semua"
          className="w-full sm:w-48"
        />
      </div>
      <AcademicList<RevisionRow>
        reloadKey={`verifications-${from}-${to}-${status}-${tick}`}
        load={async (page) =>
          teachingRequest<AcademicPage<RevisionRow>>(
            `verifications?page=${page}&per_page=25&date_from=${from}&date_to=${to}&verification_status=${status}`,
          )
        }
        columns={[
          {
            label: "Tanggal",
            cell: (row) => (
              <span className="text-[13px] font-medium">
                {dateOnly(row.meeting?.meeting_date)}
              </span>
            ),
          },
          {
            label: "Pertemuan",
            cell: (row) => (
              <span className="text-xs">
                {row.meeting?.classPeriod?.display_name ?? "—"} ·{" "}
                {row.meeting?.subject?.name ?? "—"} ·{" "}
                {wibTime(row.meeting?.start_time_snapshot)}
              </span>
            ),
          },
          {
            label: "Pelaksana",
            cell: (row) => row.actualEmployee?.person?.full_name ?? "—",
          },
          {
            label: "Pelaksanaan · materi",
            cell: (row) => (
              <span className="line-clamp-2 max-w-80 text-xs">
                {executionLabels[row.execution_status] ?? row.execution_status}{" "}
                · {row.material ?? row.nonexecution_reason ?? "—"}
              </span>
            ),
          },
          { label: "JP", cell: (row) => String(row.actual_jp) },
          {
            label: "Pengaju",
            cell: (row) => row.submitter?.person?.full_name ?? "—",
          },
          { label: "Status", cell: (row) => badge(row.verification_status) },
        ]}
        rowTitle={(row) =>
          `${row.actualEmployee?.person?.full_name ?? ""} — ${dateOnly(row.meeting?.meeting_date)}`
        }
        rowMeta={(row) =>
          `${executionLabels[row.execution_status] ?? ""} · ${row.actual_jp} JP · diajukan ${
            row.submitted_at ? wibTime(row.submitted_at) : "—"
          }`
        }
        rowActions={(row) =>
          row.verification_status === "pending" ? (
            <div className="flex gap-2">
              <Button
                className="h-11 text-xs"
                onClick={() => setDeciding({ row, action: "approve" })}
              >
                Setujui
              </Button>
              <Button
                variant="outline"
                className="h-11 text-xs"
                onClick={() => setDeciding({ row, action: "reject" })}
              >
                Tolak
              </Button>
            </div>
          ) : null
        }
        emptyTitle="Tidak ada realisasi pada filter ini"
        emptyHint="Realisasi muncul di sini setelah guru pelaksana mengirimkannya."
      />

      {deciding && (
        <ConfirmAction
          title={
            deciding.action === "approve"
              ? "Setujui realisasi"
              : "Tolak realisasi"
          }
          description={`${deciding.row.actualEmployee?.person?.full_name ?? ""} — ${dateOnly(
            deciding.row.meeting?.meeting_date,
          )} · ${deciding.row.actual_jp} JP. ${
            deciding.action === "approve"
              ? "JP terverifikasi menjadi dasar perhitungan honor."
              : "Guru pelaksana dapat membuat revisi baru setelah ditolak."
          }`}
          submitLabel={
            deciding.action === "approve"
              ? "Setujui realisasi"
              : "Tolak realisasi"
          }
          destructive={deciding.action === "reject"}
          note="Keputusan bersifat final untuk revisi ini dan dicatat pada jejak audit."
          onClose={() => setDeciding(null)}
          action={async (reason) => {
            await teachingRequest(
              `revisions/${deciding.row.id}/decision`,
              "POST",
              { action: deciding.action, reason },
            );
            reload();
          }}
        />
      )}
    </div>
  );
}
