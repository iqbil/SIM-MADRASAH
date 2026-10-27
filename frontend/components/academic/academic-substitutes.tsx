"use client";

import { useCallback, useEffect, useState } from "react";
import {
  AcademicFormDialog,
  AcademicHeading,
  AcademicList,
  ConfirmAction,
  DateFilter,
  FilterSelect,
} from "@/components/academic/academic-shared";
import { Badge } from "@/components/ui/badge";
import { Button } from "@/components/ui/button";
import { Tabs, TabsContent, TabsList, TabsTrigger } from "@/components/ui/tabs";
import {
  type AcademicPage,
  type AcademicRecord,
  academicListPath,
  academicRequest,
  type Option,
  timeOnly,
} from "@/lib/academic";
import { masterRequest } from "@/lib/master-data";

type Meeting = AcademicRecord & {
  meeting_date: string;
  start_time_snapshot: string;
  end_time_snapshot: string;
  class_period_id: number;
  subject_id: number;
  scheduled_employee_id: number;
  is_cancelled?: boolean;
  classPeriod?: { display_name?: string } | null;
  subject?: { name?: string } | null;
  scheduledEmployee?: { person?: { full_name?: string } | null } | null;
  activeSubstitute?: {
    substitute?: { person?: { full_name?: string } | null } | null;
  } | null;
};

type SubstituteRow = AcademicRecord & {
  meeting_id: number;
  substitute_employee_id: number;
  assigned_at: string;
  revoked_at?: string | null;
  reason: string;
  meeting?: Meeting | null;
  substituteEmployee?: { person?: { full_name?: string } | null } | null;
};

export function AcademicSubstitutes({ access }: { access: string[] }) {
  const canManage =
    access.includes("academic.substitutes.manage") ||
    access.includes("academic.manage");

  const [from, setFrom] = useState("");
  const [to, setTo] = useState("");
  const [scope, setScope] = useState("active");
  const [employees, setEmployees] = useState<Option[]>([]);
  const [assigning, setAssigning] = useState<Meeting | null>(null);
  const [revoking, setRevoking] = useState<SubstituteRow | null>(null);
  const [tick, setTick] = useState(0);
  const [tab, setTab] = useState("meetings");
  const reload = useCallback(() => setTick((value) => value + 1), []);

  useEffect(() => {
    if (!canManage) return;
    let active = true;
    masterRequest<AcademicPage<AcademicRecord>>(
      "employees?per_page=100&is_active=1",
    )
      .then((data) => {
        if (active)
          setEmployees(
            data.data.map((row) => ({
              value: String(row.id),
              label: String(
                (row as { person?: { full_name?: string } }).person
                  ?.full_name ?? `#${row.id}`,
              ),
            })),
          );
      })
      .catch(() => {});
    return () => {
      active = false;
    };
  }, [canManage]);

  const meetingColumns = [
    {
      label: "Tanggal",
      cell: (row: Meeting) => (
        <span className="text-sm font-medium">
          {row.meeting_date.slice(0, 10)}
        </span>
      ),
    },
    {
      label: "Jam",
      cell: (row: Meeting) => (
        <span className="text-xs">
          {timeOnly(row.start_time_snapshot)}–{timeOnly(row.end_time_snapshot)}
        </span>
      ),
    },
    {
      label: "Rombel",
      cell: (row: Meeting) =>
        row.classPeriod?.display_name ?? `#${row.class_period_id}`,
    },
    {
      label: "Mapel",
      cell: (row: Meeting) => row.subject?.name ?? `#${row.subject_id}`,
    },
    {
      label: "Guru terjadwal",
      cell: (row: Meeting) =>
        row.scheduledEmployee?.person?.full_name ??
        `#${row.scheduled_employee_id}`,
    },
    {
      label: "Pengganti aktif",
      cell: (row: Meeting) =>
        row.activeSubstitute?.substitute?.person?.full_name ?? "—",
    },
    {
      label: "Status",
      cell: (row: Meeting) =>
        row.is_cancelled ? (
          <Badge variant="outline" className="text-xs">
            Dibatalkan
          </Badge>
        ) : row.activeSubstitute ? (
          <Badge
            variant="secondary"
            className="bg-primary/8 text-xs text-primary"
          >
            Digantikan
          </Badge>
        ) : (
          <Badge variant="outline" className="text-xs">
            Menunggu
          </Badge>
        ),
    },
  ];

  return (
    <div className="animate-enter space-y-6">
      <AcademicHeading
        title="Guru pengganti"
        description="Penunjukan pengganti tercatat terpisah dari guru terjadwal, satu pengganti aktif per pertemuan dan benturan jadwal pengganti ditolak."
      />
      <Tabs value={tab} onValueChange={setTab}>
        <TabsList className="mb-5 h-14! w-full justify-start gap-1 rounded-xl bg-secondary p-1 sm:w-auto">
          <TabsTrigger
            value="meetings"
            className="h-11! min-w-0 flex-1 px-3 text-xs sm:flex-none"
          >
            Pertemuan
          </TabsTrigger>
          <TabsTrigger
            value="history"
            className="h-11! min-w-0 flex-1 px-3 text-xs sm:flex-none"
          >
            Riwayat pengganti
          </TabsTrigger>
        </TabsList>

        <TabsContent value="meetings" className="space-y-4">
          <div className="flex flex-wrap items-end gap-3">
            <DateFilter label="Dari tanggal" value={from} onChange={setFrom} />
            <DateFilter label="Sampai" value={to} onChange={setTo} />
          </div>
          <AcademicList<Meeting>
            reloadKey={`sub-meetings-${from}-${to}-${tick}`}
            load={async (page) =>
              academicRequest<AcademicPage<Meeting>>(
                academicListPath("meetings", {
                  page,
                  per_page: 25,
                  date_from: from,
                  date_to: to,
                }),
              )
            }
            columns={meetingColumns}
            rowTitle={(row) =>
              `${row.meeting_date.slice(0, 10)} ${timeOnly(row.start_time_snapshot)} — ${row.subject?.name ?? ""}`
            }
            rowMeta={(row) =>
              `${row.classPeriod?.display_name ?? ""} · ${row.scheduledEmployee?.person?.full_name ?? ""} · pengganti: ${row.activeSubstitute?.substitute?.person?.full_name ?? "belum ada"}`
            }
            rowActions={
              canManage
                ? (row) =>
                    row.is_cancelled ? null : (
                      <Button
                        variant={row.activeSubstitute ? "outline" : "default"}
                        className="h-11 text-xs"
                        onClick={() => setAssigning(row)}
                      >
                        {row.activeSubstitute
                          ? "Ganti pengganti"
                          : "Tunjuk pengganti"}
                      </Button>
                    )
                : undefined
            }
            emptyTitle="Belum ada pertemuan pada rentang ini"
            emptyHint="Pertemuan dibuat dari jadwal pelajaran versi terbit."
          />
        </TabsContent>

        <TabsContent value="history" className="space-y-4">
          <FilterSelect
            label="Status penunjukan"
            value={scope}
            onChange={setScope}
            options={[
              { value: "active", label: "Masih aktif" },
              { value: "all", label: "Semua" },
            ]}
            placeholder="Semua"
            className="w-full sm:w-56"
          />
          <AcademicList<SubstituteRow>
            reloadKey={`sub-history-${scope}-${from}-${to}-${tick}`}
            load={async (page) =>
              academicRequest<AcademicPage<SubstituteRow>>(
                academicListPath("substitute-assignments", {
                  page,
                  per_page: 25,
                  active_only: scope === "active" ? 1 : "",
                  date_from: from,
                  date_to: to,
                }),
              )
            }
            columns={[
              {
                label: "Pengganti",
                cell: (row) => (
                  <span className="text-sm font-medium">
                    {row.substituteEmployee?.person?.full_name ??
                      `#${row.substitute_employee_id}`}
                  </span>
                ),
              },
              {
                label: "Pertemuan",
                cell: (row) =>
                  row.meeting
                    ? `${row.meeting.meeting_date.slice(0, 10)} ${timeOnly(row.meeting.start_time_snapshot)}`
                    : `#${row.meeting_id}`,
              },
              {
                label: "Rombel · mapel",
                cell: (row) =>
                  row.meeting
                    ? `${row.meeting.classPeriod?.display_name ?? "—"} · ${row.meeting.subject?.name ?? "—"}`
                    : "—",
              },
              {
                label: "Ditunjuk",
                cell: (row) => (
                  <span className="text-xs">
                    {row.assigned_at.slice(0, 10)}
                  </span>
                ),
              },
              {
                label: "Status",
                cell: (row) =>
                  row.revoked_at ? (
                    <Badge variant="outline" className="text-xs">
                      Dicabut {row.revoked_at.slice(0, 10)}
                    </Badge>
                  ) : (
                    <Badge
                      variant="secondary"
                      className="bg-primary/8 text-xs text-primary"
                    >
                      Aktif
                    </Badge>
                  ),
              },
            ]}
            rowTitle={(row) =>
              row.substituteEmployee?.person?.full_name ??
              `#${row.substitute_employee_id}`
            }
            rowMeta={(row) => row.reason}
            rowActions={
              canManage
                ? (row) =>
                    row.revoked_at ? null : (
                      <Button
                        variant="outline"
                        className="h-11 text-xs"
                        onClick={() => setRevoking(row)}
                      >
                        Cabut
                      </Button>
                    )
                : undefined
            }
            emptyTitle="Belum ada penunjukan guru pengganti"
            emptyHint="Tunjuk pengganti dari daftar pertemuan pada tab sebelumnya."
          />
        </TabsContent>
      </Tabs>

      {assigning && (
        <AcademicFormDialog
          title={
            assigning.activeSubstitute
              ? "Ganti guru pengganti"
              : "Tunjuk guru pengganti"
          }
          description={`${assigning.meeting_date.slice(0, 10)} ${timeOnly(assigning.start_time_snapshot)} — ${assigning.classPeriod?.display_name ?? ""} · ${assigning.subject?.name ?? ""}. Menunjuk pengganti baru otomatis menggantikan penunjukan aktif.`}
          fields={[
            {
              name: "substitute_employee_id",
              label: "Guru pengganti",
              required: true,
              type: "select",
              options: employees.filter(
                (option) =>
                  Number(option.value) !== assigning.scheduled_employee_id,
              ),
            },
          ]}
          onClose={() => setAssigning(null)}
          submit={async (body) => {
            await academicRequest(
              `meetings/${assigning.id}/substitute`,
              "POST",
              { substitute_employee_id: Number(body.substitute_employee_id) },
            );
            reload();
          }}
        />
      )}

      {revoking && (
        <ConfirmAction
          title="Cabut penunjukan pengganti"
          description={`Pertemuan kembali menjadi tanggung jawab guru terjadwal. Riwayat penunjukan tetap tersimpan.`}
          destructive
          submitLabel="Cabut penunjukan"
          onClose={() => setRevoking(null)}
          action={async (reason) => {
            await academicRequest(
              `substitute-assignments/${revoking.id}/revoke`,
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
