"use client";

import { useCallback, useState } from "react";
import {
  AcademicHeading,
  AcademicList,
  DateFilter,
  FilterSelect,
} from "@/components/academic/academic-shared";
import { DataFailure, DataLoading } from "@/components/layout/data-feedback";
import { Badge } from "@/components/ui/badge";
import { Card, CardContent } from "@/components/ui/card";
import { useAsyncData } from "@/hooks/use-async-data";
import type { AcademicPage, AcademicRecord } from "@/lib/academic";
import {
  dateOnly,
  executionLabels,
  teachingRequest,
  verificationLabels,
  wibMonthRange,
  wibTime,
} from "@/lib/teaching";

type RealizationRow = AcademicRecord & {
  revision_number: number;
  execution_status: string;
  verification_status: string;
  material: string | null;
  actual_jp: string;
  actual_minutes: number | null;
  nonexecution_reason: string | null;
  notes: string | null;
  submitted_at: string | null;
  meeting?: {
    meeting_date: string;
    start_time_snapshot: string;
    classPeriod?: { display_name?: string } | null;
    subject?: { name?: string } | null;
  } | null;
  verification?: {
    action: string;
    reason: string | null;
    verified_at: string;
  } | null;
};

const badge = (status: string) => {
  if (status === "approved")
    return (
      <Badge variant="secondary" className="bg-primary/8 text-xs text-primary">
        {verificationLabels.approved}
      </Badge>
    );
  if (status === "pending")
    return (
      <Badge variant="secondary" className="text-xs">
        {verificationLabels.pending}
      </Badge>
    );
  if (status === "rejected")
    return (
      <Badge variant="outline" className="text-xs text-destructive">
        {verificationLabels.rejected}
      </Badge>
    );

  return (
    <Badge variant="secondary" className="text-xs">
      {verificationLabels.not_submitted}
    </Badge>
  );
};

export function MyRealizations() {
  const range = wibMonthRange();
  const [from, setFrom] = useState(range.from);
  const [to, setTo] = useState(range.to);
  const [status, setStatus] = useState("");

  return (
    <div className="animate-enter space-y-6">
      <AcademicHeading
        category="Realisasi mengajar"
        title="Realisasi & riwayat JP saya"
        description="Revisi terkini per pertemuan beserta status verifikasi; hanya realisasi terlaksana dan disetujui yang masuk perhitungan JP."
      />
      <div className="flex flex-wrap items-end gap-3">
        <DateFilter label="Dari tanggal" value={from} onChange={setFrom} />
        <DateFilter label="Sampai" value={to} onChange={setTo} />
        <FilterSelect
          label="Status verifikasi"
          value={status}
          onChange={setStatus}
          options={[
            { value: "not_submitted", label: verificationLabels.not_submitted },
            { value: "pending", label: verificationLabels.pending },
            { value: "approved", label: verificationLabels.approved },
            { value: "rejected", label: verificationLabels.rejected },
          ]}
          placeholder="Semua"
          className="w-full sm:w-56"
        />
      </div>
      <RealizationTotals from={from} to={to} status={status} />
      <AcademicList<RealizationRow>
        reloadKey={`rows-${from}-${to}-${status}`}
        load={async (page) => {
          const result = await teachingRequest<{
            data: { rows: AcademicPage<RealizationRow> };
          }>(
            `realizations?page=${page}&per_page=25&date_from=${from}&date_to=${to}${
              status ? `&verification_status=${status}` : ""
            }`,
          );

          return (
            result.data?.rows ?? {
              data: [],
              meta: { current_page: 1, last_page: 1, total: 0 },
            }
          );
        }}
        columns={[
          {
            label: "Tanggal",
            cell: (row) => (
              <span className="text-sm font-medium">
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
            label: "Pelaksanaan",
            cell: (row) =>
              executionLabels[row.execution_status] ?? row.execution_status,
          },
          {
            label: "Materi",
            cell: (row) => (
              <span className="line-clamp-2 max-w-72 text-xs">
                {row.material ?? row.nonexecution_reason ?? "—"}
              </span>
            ),
          },
          { label: "JP", cell: (row) => String(row.actual_jp) },
          {
            label: "Revisi",
            cell: (row) => `#${row.revision_number}`,
          },
          {
            label: "Verifikasi",
            cell: (row) => badge(row.verification_status),
          },
        ]}
        rowTitle={(row) =>
          `${dateOnly(row.meeting?.meeting_date)} — ${row.meeting?.subject?.name ?? ""}`
        }
        rowMeta={(row) =>
          `${executionLabels[row.execution_status] ?? ""} · ${row.actual_jp} JP · ${verificationLabels[row.verification_status] ?? ""}`
        }
        emptyTitle="Belum ada realisasi pada filter ini"
        emptyHint="Catat realisasi dari menu Pertemuan mengajar saya."
      />
    </div>
  );
}

function RealizationTotals({
  from,
  to,
  status,
}: {
  from: string;
  to: string;
  status: string;
}) {
  const query = useAsyncData(
    useCallback(
      () =>
        teachingRequest<{
          data: { totals: { approved_jp: number; approved_meetings: number } };
        }>(
          `realizations?per_page=1&date_from=${from}&date_to=${to}${status ? `&verification_status=${status}` : ""}`,
        ),
      [from, to, status],
    ),
  );
  if (query.loading) return <DataLoading />;
  if (query.error) return <DataFailure message={query.error} />;
  const totals = query.data?.data.totals;
  if (!totals) return null;

  return (
    <Card className="rounded-2xl border border-border/70 py-0 ring-0">
      <CardContent className="flex flex-wrap gap-6 p-4 sm:p-5">
        <div>
          <p className="text-xs text-muted-foreground">
            JP disetujui (terlaksana)
          </p>
          <p className="text-2xl font-semibold">{totals.approved_jp}</p>
        </div>
        <div>
          <p className="text-xs text-muted-foreground">Pertemuan disetujui</p>
          <p className="text-2xl font-semibold">{totals.approved_meetings}</p>
        </div>
      </CardContent>
    </Card>
  );
}
