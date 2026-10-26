"use client";

import { useCallback, useState } from "react";
import {
  AcademicFormDialog,
  AcademicHeading,
  AcademicList,
  ConfirmAction,
  DateFilter,
} from "@/components/academic/academic-shared";
import { Badge } from "@/components/ui/badge";
import { Button } from "@/components/ui/button";
import type { AcademicPage, AcademicRecord } from "@/lib/academic";
import {
  dateOnly,
  teachingRequest,
  verificationLabels,
  wibMonthRange,
  wibTime,
} from "@/lib/teaching";

type MeetingRow = AcademicRecord & {
  meeting_date: string;
  start_time_snapshot: string;
  end_time_snapshot: string;
  planned_jp_snapshot: string;
  is_cancelled?: boolean;
  classPeriod?: { display_name?: string } | null;
  subject?: { name?: string } | null;
  activeSubstitute?: {
    substitute?: { person?: { full_name?: string } | null } | null;
  } | null;
  realization?: {
    currentRevision?: {
      id: number;
      execution_status: string;
      verification_status: string;
      actual_employee_id?: number;
    } | null;
  } | null;
};

const statusBadge = (status: string | undefined) => {
  if (!status)
    return (
      <Badge variant="outline" className="text-[10px]">
        Belum dicatat
      </Badge>
    );
  if (status === "approved")
    return (
      <Badge
        variant="secondary"
        className="bg-primary/8 text-[10px] text-primary"
      >
        {verificationLabels.approved}
      </Badge>
    );
  if (status === "pending")
    return (
      <Badge variant="secondary" className="text-[10px]">
        {verificationLabels.pending}
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
      {verificationLabels.not_submitted}
    </Badge>
  );
};

export function MyMeetings() {
  const range = wibMonthRange();
  const [from, setFrom] = useState(range.from);
  const [to, setTo] = useState(range.to);
  const [recording, setRecording] = useState<MeetingRow | null>(null);
  const [sending, setSending] = useState<MeetingRow | null>(null);
  const [tick, setTick] = useState(0);
  const reload = useCallback(() => setTick((value) => value + 1), []);

  const revisionOf = (row: MeetingRow) =>
    row.realization?.currentRevision ?? null;
  const canRecord = (row: MeetingRow) => {
    const revision = revisionOf(row);

    return (
      revision === null ||
      ["not_submitted", "rejected"].includes(revision.verification_status)
    );
  };

  return (
    <div className="animate-enter space-y-6">
      <AcademicHeading
        title="Pertemuan mengajar saya"
        description="Pertemuan yang menjadi tugas Anda — terjadwal maupun mengganti. Catat realisasi sebagai draf, lalu kirim untuk diverifikasi; isi yang sudah diajukan hanya berubah lewat revisi baru."
      />
      <div className="flex flex-wrap items-end gap-3">
        <DateFilter label="Dari tanggal" value={from} onChange={setFrom} />
        <DateFilter label="Sampai" value={to} onChange={setTo} />
      </div>
      <AcademicList<MeetingRow>
        reloadKey={`meetings-${from}-${to}-${tick}`}
        load={async (page) =>
          teachingRequest<AcademicPage<MeetingRow>>(
            `meetings?page=${page}&per_page=25&date_from=${from}&date_to=${to}`,
          )
        }
        columns={[
          {
            label: "Tanggal",
            cell: (row) => (
              <span className="text-[13px] font-medium">
                {dateOnly(row.meeting_date)}
              </span>
            ),
          },
          {
            label: "Jam",
            cell: (row) => (
              <span className="text-xs">
                {wibTime(row.start_time_snapshot)}–
                {wibTime(row.end_time_snapshot)}
              </span>
            ),
          },
          {
            label: "Rombel",
            cell: (row) => row.classPeriod?.display_name ?? "—",
          },
          { label: "Mapel", cell: (row) => row.subject?.name ?? "—" },
          {
            label: "Pengganti",
            cell: (row) =>
              row.activeSubstitute?.substitute?.person?.full_name ?? "—",
          },
          {
            label: "Realisasi",
            cell: (row) => statusBadge(revisionOf(row)?.verification_status),
          },
        ]}
        rowTitle={(row) =>
          `${dateOnly(row.meeting_date)} — ${row.subject?.name ?? ""}`
        }
        rowMeta={(row) =>
          `${row.classPeriod?.display_name ?? ""} · ${row.planned_jp_snapshot} JP terencana`
        }
        rowActions={(row) => (
          <div className="flex gap-2">
            {canRecord(row) && (
              <Button
                variant={revisionOf(row) ? "outline" : "default"}
                className="h-11 text-xs"
                onClick={() => setRecording(row)}
              >
                {revisionOf(row) ? "Revisi realisasi" : "Catat realisasi"}
              </Button>
            )}
            {revisionOf(row)?.verification_status === "not_submitted" && (
              <Button
                variant="outline"
                className="h-11 text-xs"
                onClick={() => setSending(row)}
              >
                Kirim
              </Button>
            )}
          </div>
        )}
        emptyTitle="Belum ada pertemuan pada rentang ini"
        emptyHint="Pertemuan dibuat dari jadwal pelajaran versi terbit oleh petugas."
      />

      {recording && (
        <AcademicFormDialog
          title={revisionOf(recording) ? "Revisi realisasi" : "Catat realisasi"}
          description={`${dateOnly(recording.meeting_date)} · ${recording.classPeriod?.display_name ?? ""} · ${recording.subject?.name ?? ""} (${recording.planned_jp_snapshot} JP terencana).`}
          fields={[
            {
              name: "execution_status",
              label: "Pelaksanaan",
              required: true,
              type: "select",
              options: [
                { value: "held", label: "Terlaksana" },
                { value: "not_held", label: "Tidak terlaksana" },
              ],
            },
            { name: "material", label: "Materi", type: "textarea", max: 5000 },
            {
              name: "actual_jp",
              label: "JP aktual",
              required: true,
              type: "number",
              max: 4,
            },
            {
              name: "actual_minutes",
              label: "Durasi (menit)",
              type: "number",
              max: 4,
            },
            {
              name: "nonexecution_reason",
              label: "Alasan tidak terlaksana (wajib bila tidak terlaksana)",
              type: "textarea",
              max: 1000,
            },
            ...(revisionOf(recording)
              ? [
                  {
                    name: "revision_reason",
                    label: "Alasan revisi",
                    required: true,
                    type: "textarea" as const,
                    max: 1000,
                  },
                ]
              : []),
          ]}
          onClose={() => setRecording(null)}
          submit={async (body) => {
            await teachingRequest(
              `meetings/${recording.id}/realizations`,
              "POST",
              {
                execution_status: body.execution_status,
                material: body.material,
                actual_jp: Number(body.actual_jp),
                ...(body.actual_minutes
                  ? { actual_minutes: Number(body.actual_minutes) }
                  : {}),
                ...(body.nonexecution_reason
                  ? { nonexecution_reason: body.nonexecution_reason }
                  : {}),
                ...(body.revision_reason
                  ? { revision_reason: body.revision_reason }
                  : {}),
              },
            );
            reload();
          }}
        />
      )}

      {sending && revisionOf(sending) && (
        <ConfirmAction
          title="Kirim realisasi untuk verifikasi"
          description={`${dateOnly(sending.meeting_date)} · ${sending.subject?.name ?? ""} — setelah dikirim, isi tidak dapat diubah sampai diputuskan verifikator.`}
          submitLabel="Kirim realisasi"
          note="Verifikator adalah petugas bermandat yang berbeda dari pengaju."
          onClose={() => setSending(null)}
          action={async (reason) => {
            await teachingRequest(
              `revisions/${revisionOf(sending)?.id}/submit`,
              "POST",
              { reason },
            );
            reload();
          }}
        />
      )}
    </div>
  );
}
