"use client";

import { Plus } from "lucide-react";
import { useCallback, useEffect, useMemo, useState } from "react";
import { toast } from "sonner";
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
  DAY_OPTIONS,
  dayLabel,
  type Option,
  timeOnly,
  versionStatusLabel,
} from "@/lib/academic";

type Semester = AcademicRecord & {
  name: string;
  academic_year_id: number;
  start_date?: string;
  end_date?: string;
};

type Timetable = AcademicRecord & {
  semester_id: number;
  code: string;
  name: string;
};

type Version = AcademicRecord & {
  version_number: number;
  status: string;
  effective_from: string;
  effective_to: string | null;
  entries_count?: number;
};

type Entry = AcademicRecord & {
  day_of_week: number;
  start_time_snapshot: string;
  end_time_snapshot: string;
  planned_jp: string;
  planned_minutes: number;
  room?: string | null;
  teaching_assignment_id: number;
  teachingAssignment?: {
    employee?: { person?: { full_name?: string } | null } | null;
    subject?: { name?: string } | null;
    classPeriod?: { display_name?: string } | null;
  } | null;
};

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

type Assignment = AcademicRecord & {
  employee_id: number;
  subject_id: number;
  class_period_id: number;
  employee?: { person?: { full_name?: string } | null } | null;
  subject?: { name?: string } | null;
  classPeriod?: { display_name?: string } | null;
};

export function AcademicSchedules({ access }: { access: string[] }) {
  const canManage =
    access.includes("academic.schedules.manage") ||
    access.includes("academic.manage");

  const [semesters, setSemesters] = useState<Semester[]>([]);
  const [semesterId, setSemesterId] = useState("");
  const [timetable, setTimetable] = useState<Timetable | null>(null);
  const [versions, setVersions] = useState<Version[]>([]);
  const [assignments, setAssignments] = useState<Assignment[]>([]);
  const [versionId, setVersionId] = useState("");
  const [from, setFrom] = useState("");
  const [to, setTo] = useState("");
  const [creatingTimetable, setCreatingTimetable] = useState(false);
  const [creatingDraft, setCreatingDraft] = useState(false);
  const [publishing, setPublishing] = useState<Version | null>(null);
  const [generating, setGenerating] = useState(false);
  const [entryDialog, setEntryDialog] = useState<Entry | "new" | null>(null);
  const [removing, setRemoving] = useState<Entry | null>(null);
  const [cancelling, setCancelling] = useState<Meeting | null>(null);
  const [tick, setTick] = useState(0);
  const [tab, setTab] = useState("versions");
  const reload = useCallback(() => setTick((value) => value + 1), []);

  useEffect(() => {
    let active = true;
    academicRequest<AcademicPage<AcademicRecord>>("years?per_page=100")
      .then(async ({ data: yearRows }) => {
        const lists = await Promise.all(
          yearRows.map((year) =>
            academicRequest<AcademicPage<Semester>>(
              `semesters?per_page=10&academic_year_id=${year.id}`,
            ).catch(() => null),
          ),
        );
        if (active)
          setSemesters(
            lists.filter(Boolean).flatMap((list) => list?.data ?? []),
          );
      })
      .catch(() => {});
    return () => {
      active = false;
    };
  }, []);

  useEffect(() => {
    if (!semesters.length || semesterId) return;
    setSemesterId(String(semesters[0].id));
  }, [semesters, semesterId]);

  // biome-ignore lint/correctness/useExhaustiveDependencies: tick memicu pemuatan ulang setelah mutasi jadwal
  const loadTimetable = useCallback(async () => {
    if (!semesterId) return;
    try {
      const page = await academicRequest<AcademicPage<Timetable>>(
        academicListPath("timetables", { semester_id: semesterId }),
      );
      const main =
        page.data.find((row) => row.code === "MAIN") ?? page.data[0] ?? null;
      setTimetable(main);
      const versionRows = main
        ? (
            await academicRequest<AcademicPage<Version>>(
              `timetables/${main.id}/versions`,
            )
          ).data
        : [];
      setVersions(versionRows);
      setVersionId((current) =>
        versionRows.some((row) => String(row.id) === current)
          ? current
          : String(
              (
                versionRows.find((row) => row.status === "published") ??
                versionRows[0]
              )?.id ?? "",
            ),
      );
      if (main) {
        const assignmentRows = await academicRequest<AcademicPage<Assignment>>(
          academicListPath("teaching-assignments", {
            semester_id: semesterId,
            per_page: 100,
            active_only: 1,
          }),
        );
        setAssignments(assignmentRows.data);
      } else {
        setAssignments([]);
      }
    } catch {
      setTimetable(null);
      setVersions([]);
    }
  }, [semesterId, tick]);

  useEffect(() => {
    void loadTimetable();
  }, [loadTimetable]);

  const semester = semesters.find((row) => String(row.id) === semesterId);
  const selectedVersion = versions.find((row) => String(row.id) === versionId);
  const publishedVersion = versions.find((row) => row.status === "published");
  const versionOptions: Option[] = versions.map((row) => ({
    value: String(row.id),
    label: `Versi ${row.version_number} · ${versionStatusLabel(row.status)}`,
  }));
  const assignmentOptions: Option[] = assignments.map((row) => ({
    value: String(row.id),
    label: `${row.employee?.person?.full_name ?? `#${row.employee_id}`} — ${
      row.subject?.name ?? `#${row.subject_id}`
    } · ${row.classPeriod?.display_name ?? `#${row.class_period_id}`}`,
  }));

  const entryFields = (editing: boolean) => [
    {
      name: "teaching_assignment_id",
      label: "Penugasan mengajar",
      required: true,
      type: "select" as const,
      options: assignmentOptions,
      immutable: editing,
    },
    {
      name: "day_of_week",
      label: "Hari",
      required: true,
      type: "select" as const,
      options: DAY_OPTIONS,
    },
    {
      name: "start_time",
      label: "Jam mulai",
      required: true,
      type: "time" as const,
    },
    {
      name: "end_time",
      label: "Jam selesai",
      required: true,
      type: "time" as const,
    },
    {
      name: "planned_jp",
      label: "JP terencana",
      required: true,
      type: "number" as const,
      max: 5,
    },
    { name: "room", label: "Ruang", max: 100 },
  ];

  async function submitEntry(body: Record<string, unknown>) {
    if (!selectedVersion) throw new Error("Versi tidak dipilih.");
    const payload = {
      ...body,
      teaching_assignment_id: Number(body.teaching_assignment_id),
      day_of_week: Number(body.day_of_week),
      planned_jp: Number(body.planned_jp),
    };
    await academicRequest(
      entryDialog === "new"
        ? `timetable-versions/${selectedVersion.id}/entries`
        : `schedule-entry-versions/${(entryDialog as Entry).id}`,
      entryDialog === "new" ? "POST" : "PUT",
      payload,
    );
    reload();
  }

  const meetingColumns = useMemo(
    () => [
      {
        label: "Tanggal",
        cell: (row: Meeting) => (
          <span className="text-[13px] font-medium">
            {row.meeting_date.slice(0, 10)}
          </span>
        ),
      },
      {
        label: "Jam",
        cell: (row: Meeting) => (
          <span className="text-xs">
            {timeOnly(row.start_time_snapshot)}–
            {timeOnly(row.end_time_snapshot)}
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
        label: "Pengganti",
        cell: (row: Meeting) =>
          row.activeSubstitute?.substitute?.person?.full_name ?? "—",
      },
      {
        label: "Status",
        cell: (row: Meeting) =>
          row.is_cancelled ? (
            <Badge variant="outline" className="text-[10px]">
              Dibatalkan
            </Badge>
          ) : row.activeSubstitute ? (
            <Badge variant="secondary" className="text-[10px]">
              Digantikan
            </Badge>
          ) : (
            <Badge
              variant="secondary"
              className="bg-primary/8 text-[10px] text-primary"
            >
              Terjadwal
            </Badge>
          ),
      },
    ],
    [],
  );

  return (
    <div className="animate-enter space-y-6">
      <AcademicHeading
        title="Jadwal pelajaran"
        description="Jadwal berversi per semester: entri diubah pada draf, penerbitan menutup versi sebelumnya, dan pertemuan bertanggal dibuat dengan memperhitungkan kalender akademik."
      />
      <div className="flex flex-wrap items-end gap-3">
        <FilterSelect
          label="Semester"
          value={semesterId}
          onChange={setSemesterId}
          options={semesters.map((row) => ({
            value: String(row.id),
            label: String(row.name),
          }))}
          placeholder="Pilih semester"
          className="w-full sm:w-64"
        />
        {timetable && versionOptions.length > 0 && (
          <FilterSelect
            label="Versi jadwal"
            value={versionId}
            onChange={setVersionId}
            options={versionOptions}
            placeholder="Pilih versi"
            className="w-full sm:w-64"
          />
        )}
        {canManage && timetable && (
          <Button
            className="h-11 gap-2 text-xs"
            disabled={semesters.length === 0}
            onClick={() => setCreatingDraft(true)}
          >
            <Plus className="size-4" /> Draf versi baru
          </Button>
        )}
      </div>

      {!semester ? (
        <p className="rounded-xl border border-dashed p-5 text-sm leading-6 text-muted-foreground">
          Belum ada semester. Buat tahun ajaran dan semester terlebih dahulu.
        </p>
      ) : !timetable ? (
        <div className="space-y-4 rounded-xl border border-dashed p-5">
          <p className="text-sm leading-6 text-muted-foreground">
            Semester {semester.name} belum memiliki jadwal.
          </p>
          {canManage && (
            <Button
              className="h-11 gap-2 text-xs"
              onClick={() => setCreatingTimetable(true)}
            >
              <Plus className="size-4" /> Buat jadwal utama
            </Button>
          )}
        </div>
      ) : (
        <Tabs value={tab} onValueChange={setTab}>
          <TabsList className="mb-5 h-14! w-full justify-start gap-1 rounded-xl bg-secondary p-1 sm:w-auto">
            <TabsTrigger
              value="versions"
              className="h-11! min-w-0 flex-1 px-3 text-xs sm:flex-none"
            >
              Versi jadwal
            </TabsTrigger>
            <TabsTrigger
              value="entries"
              className="h-11! min-w-0 flex-1 px-3 text-xs sm:flex-none"
            >
              Entri mingguan
            </TabsTrigger>
            <TabsTrigger
              value="meetings"
              className="h-11! min-w-0 flex-1 px-3 text-xs sm:flex-none"
            >
              Pertemuan
            </TabsTrigger>
          </TabsList>

          <TabsContent value="versions" className="space-y-4">
            <AcademicList<Version>
              reloadKey={`versions-${versionId}-${tick}`}
              load={async (listPage) =>
                academicRequest<AcademicPage<Version>>(
                  academicListPath(`timetables/${timetable.id}/versions`, {
                    page: listPage,
                  }),
                )
              }
              columns={[
                {
                  label: "Versi",
                  cell: (row) => (
                    <span className="text-[13px] font-medium">
                      Versi {row.version_number}
                    </span>
                  ),
                },
                {
                  label: "Status",
                  cell: (row) => (
                    <Badge
                      variant={
                        row.status === "published" ? "secondary" : "outline"
                      }
                      className={
                        row.status === "published"
                          ? "bg-primary/8 text-[10px] text-primary"
                          : "text-[10px]"
                      }
                    >
                      {versionStatusLabel(row.status)}
                    </Badge>
                  ),
                },
                {
                  label: "Masa berlaku",
                  cell: (row) => (
                    <span className="text-xs">
                      {row.effective_from.slice(0, 10)} →{" "}
                      {row.effective_to?.slice(0, 10) ?? "berjalan"}
                    </span>
                  ),
                },
                {
                  label: "Entri",
                  cell: (row) => String(row.entries_count ?? "—"),
                },
              ]}
              rowTitle={(row) => `Versi ${row.version_number}`}
              rowMeta={(row) =>
                `${versionStatusLabel(row.status)} · ${row.effective_from.slice(0, 10)} → ${row.effective_to?.slice(0, 10) ?? "berjalan"}`
              }
              rowActions={
                canManage
                  ? (row) =>
                      row.status === "draft" ? (
                        <Button
                          className="h-11 text-xs"
                          onClick={() => setPublishing(row)}
                        >
                          Terbitkan
                        </Button>
                      ) : null
                  : undefined
              }
              emptyTitle="Belum ada versi jadwal"
              emptyHint="Buat draf versi pertama untuk mulai menyusun entri jadwal."
            />
          </TabsContent>

          <TabsContent value="entries" className="space-y-4">
            <div className="flex flex-wrap items-end justify-between gap-3">
              <p className="text-xs leading-5 text-muted-foreground">
                {selectedVersion?.status === "draft"
                  ? "Entri hanya dapat ditambahkan dan diubah pada versi draf."
                  : "Versi terbit bersifat tetap. Buat draf versi baru untuk mengubah entri."}
              </p>
              {canManage && selectedVersion?.status === "draft" && (
                <Button
                  className="h-11 gap-2 text-xs"
                  disabled={assignmentOptions.length === 0}
                  onClick={() => setEntryDialog("new")}
                >
                  <Plus className="size-4" /> Tambah entri
                </Button>
              )}
            </div>
            {selectedVersion && (
              <AcademicList<Entry>
                reloadKey={`entries-${versionId}-${tick}`}
                load={async (page) =>
                  academicRequest<AcademicPage<Entry>>(
                    academicListPath(
                      `timetable-versions/${selectedVersion.id}/entries`,
                      { page, per_page: 100 },
                    ),
                  )
                }
                columns={[
                  {
                    label: "Hari",
                    cell: (row) => (
                      <span className="text-[13px] font-medium">
                        {dayLabel(row.day_of_week)}
                      </span>
                    ),
                  },
                  {
                    label: "Jam",
                    cell: (row) => (
                      <span className="text-xs">
                        {timeOnly(row.start_time_snapshot)}–
                        {timeOnly(row.end_time_snapshot)}
                      </span>
                    ),
                  },
                  {
                    label: "Mapel",
                    cell: (row) => row.teachingAssignment?.subject?.name ?? "—",
                  },
                  {
                    label: "Guru",
                    cell: (row) =>
                      row.teachingAssignment?.employee?.person?.full_name ??
                      "—",
                  },
                  {
                    label: "Rombel",
                    cell: (row) =>
                      row.teachingAssignment?.classPeriod?.display_name ?? "—",
                  },
                  {
                    label: "JP",
                    cell: (row) => String(row.planned_jp),
                  },
                  {
                    label: "Ruang",
                    cell: (row) => row.room ?? "—",
                  },
                ]}
                rowTitle={(row) =>
                  `${dayLabel(row.day_of_week)} ${timeOnly(row.start_time_snapshot)} — ${row.teachingAssignment?.subject?.name ?? ""}`
                }
                rowMeta={(row) =>
                  `${row.teachingAssignment?.employee?.person?.full_name ?? ""} · ${row.teachingAssignment?.classPeriod?.display_name ?? ""} · ${row.planned_jp} JP`
                }
                rowActions={
                  canManage && selectedVersion?.status === "draft"
                    ? (row) => (
                        <div className="flex gap-2">
                          <Button
                            variant="outline"
                            className="h-11 text-xs"
                            onClick={() => setEntryDialog(row)}
                          >
                            Ubah
                          </Button>
                          <Button
                            variant="outline"
                            className="h-11 text-xs"
                            onClick={() => setRemoving(row)}
                          >
                            Hapus
                          </Button>
                        </div>
                      )
                    : undefined
                }
                emptyTitle="Belum ada entri jadwal"
                emptyHint="Tambahkan penugasan mengajar ke hari dan jam tertentu pada versi draf."
              />
            )}
          </TabsContent>

          <TabsContent value="meetings" className="space-y-4">
            <div className="flex flex-wrap items-end justify-between gap-3">
              <div className="flex flex-wrap items-end gap-3">
                <DateFilter
                  label="Dari tanggal"
                  value={from}
                  onChange={setFrom}
                />
                <DateFilter label="Sampai" value={to} onChange={setTo} />
              </div>
              {canManage && selectedVersion?.status === "published" && (
                <Button
                  className="h-11 gap-2 text-xs"
                  onClick={() => setGenerating(true)}
                >
                  <Plus className="size-4" /> Buat pertemuan
                </Button>
              )}
            </div>
            {selectedVersion?.status === "published" && (
              <AcademicList<Meeting>
                reloadKey={`meetings-${versionId}-${from}-${to}-${tick}`}
                load={async (page) =>
                  academicRequest<AcademicPage<Meeting>>(
                    academicListPath("meetings", {
                      page,
                      per_page: 25,
                      semester_id: semesterId,
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
                  `${row.classPeriod?.display_name ?? ""} · ${row.scheduledEmployee?.person?.full_name ?? ""}`
                }
                rowActions={
                  canManage
                    ? (row) =>
                        row.is_cancelled ? null : (
                          <Button
                            variant="outline"
                            className="h-11 text-xs"
                            onClick={() => setCancelling(row)}
                          >
                            Batalkan
                          </Button>
                        )
                    : undefined
                }
                emptyTitle="Belum ada pertemuan pada rentang ini"
                emptyHint="Buat pertemuan dari versi terbit; tanggal yang diblokir kalender dilewati."
              />
            )}
            {selectedVersion?.status !== "published" && (
              <p className="rounded-xl border border-dashed p-5 text-sm leading-6 text-muted-foreground">
                Pertemuan hanya dihasilkan dari versi terbit.
              </p>
            )}
          </TabsContent>
        </Tabs>
      )}

      {creatingTimetable && semester && (
        <AcademicFormDialog
          title="Buat jadwal utama"
          description={`Jadwal untuk semester ${semester.name}. Satu jadwal utama per semester.`}
          fields={[
            { name: "name", label: "Nama jadwal", required: true, max: 150 },
          ]}
          onClose={() => setCreatingTimetable(false)}
          submit={async (body) => {
            await academicRequest("timetables", "POST", {
              semester_id: Number(semesterId),
              name: body.name,
            });
            reload();
          }}
        />
      )}

      {creatingDraft && timetable && (
        <AcademicFormDialog
          title="Draf versi baru"
          description="Perubahan jadwal berlaku ke depan: menerbitkan versi baru menutup masa versi terbit sebelumnya pada hari sebelumnya."
          fields={[
            {
              name: "effective_from",
              label: "Berlaku mulai",
              required: true,
              max: 10,
              type: "date",
            },
            {
              name: "copy_entries",
              label: "Salin entri dari versi terbit saat ini",
              type: "boolean",
            },
          ]}
          onClose={() => setCreatingDraft(false)}
          submit={async (body) => {
            const created = await academicRequest<{ data: Version }>(
              `timetables/${timetable.id}/versions`,
              "POST",
              {
                effective_from: body.effective_from,
                ...(body.copy_entries
                  ? { copy_from_version_id: publishedVersion?.id }
                  : {}),
              },
            );
            setVersionId(String(created.data.id));
            reload();
          }}
        />
      )}

      {publishing && (
        <ConfirmAction
          title={`Terbitkan versi ${publishing.version_number}`}
          description="Entri versi ini menjadi tetap dan pertemuan dapat dibuat. Versi terbit sebelumnya ditutup pada hari sebelum tanggal mulai."
          submitLabel="Terbitkan versi"
          note="Benturan guru/rombel dengan versi terbit lain akan ditolak."
          onClose={() => setPublishing(null)}
          action={async (reason) => {
            await academicRequest(
              `timetable-versions/${publishing.id}/publish`,
              "POST",
              { reason },
            );
            reload();
          }}
        />
      )}

      {generating && selectedVersion && semester && (
        <AcademicFormDialog
          title="Buat pertemuan dari versi terbit"
          description="Pertemuan dibuat untuk setiap entri pada hari sesuai jadwal. Tanggal yang diblokir kalender akademik dilewati dan generator aman dijalankan ulang."
          fields={[
            {
              name: "date_from",
              label: "Dari tanggal",
              required: true,
              max: 10,
              type: "date",
              hint: `Rentang otomatis dibatasi masa berlaku versi dan semester ${semester.name}.`,
            },
            {
              name: "date_to",
              label: "Sampai tanggal",
              required: true,
              max: 10,
              type: "date",
            },
          ]}
          submitLabel="Buat pertemuan"
          onClose={() => setGenerating(false)}
          submit={async (body) => {
            const result = await academicRequest<{
              data: {
                created: number;
                blocked: number;
                skipped_existing: number;
              };
            }>(
              `timetable-versions/${selectedVersion.id}/generate-meetings`,
              "POST",
              {
                date_from: body.date_from,
                date_to: body.date_to,
              },
            );
            toast.success(
              `${result.data.created} pertemuan dibuat, ${result.data.blocked} diblokir kalender, ${result.data.skipped_existing} sudah ada.`,
            );
            reload();
          }}
        />
      )}

      {entryDialog && selectedVersion?.status === "draft" && (
        <AcademicFormDialog
          title={
            entryDialog === "new" ? "Tambah entri jadwal" : "Ubah entri jadwal"
          }
          description="Benturan waktu guru dan rombel pada hari yang sama akan ditolak."
          fields={entryFields(entryDialog !== "new")}
          current={
            entryDialog === "new"
              ? undefined
              : {
                  ...entryDialog,
                  start_time: timeOnly(entryDialog.start_time_snapshot),
                  end_time: timeOnly(entryDialog.end_time_snapshot),
                  day_of_week: String(entryDialog.day_of_week),
                  teaching_assignment_id: String(
                    entryDialog.teaching_assignment_id,
                  ),
                  planned_jp: entryDialog.planned_jp,
                }
          }
          onClose={() => setEntryDialog(null)}
          submit={submitEntry}
        />
      )}

      {removing && (
        <ConfirmAction
          title="Hapus entri draf"
          description="Entri dihapus dari versi draf ini saja; riwayat versi lain tetap tersimpan."
          destructive
          submitLabel="Hapus entri"
          onClose={() => setRemoving(null)}
          action={async (reason) => {
            await academicRequest(
              `schedule-entry-versions/${removing.id}`,
              "DELETE",
              { reason },
            );
            reload();
          }}
        />
      )}

      {cancelling && (
        <ConfirmAction
          title="Batalkan pertemuan"
          description={`${cancelling.meeting_date.slice(0, 10)} ${timeOnly(cancelling.start_time_snapshot)} — pembatalan bersifat administratif dan mencabut guru pengganti aktif bila ada.`}
          destructive
          submitLabel="Batalkan pertemuan"
          onClose={() => setCancelling(null)}
          action={async (reason) => {
            await academicRequest(`meetings/${cancelling.id}/cancel`, "POST", {
              reason,
              expected_updated_at: cancelling.updated_at,
            });
            reload();
          }}
        />
      )}
    </div>
  );
}
