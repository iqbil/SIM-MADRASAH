"use client";

import { Plus } from "lucide-react";
import { useCallback, useEffect, useState } from "react";
import {
  AcademicFormDialog,
  AcademicHeading,
  AcademicList,
  ConfirmAction,
} from "@/components/academic/academic-shared";
import { Badge } from "@/components/ui/badge";
import { Button } from "@/components/ui/button";
import { Card, CardContent } from "@/components/ui/card";
import { Checkbox } from "@/components/ui/checkbox";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import { Tabs, TabsContent, TabsList, TabsTrigger } from "@/components/ui/tabs";
import type { AcademicPage, AcademicRecord, Option } from "@/lib/academic";
import {
  attendanceError,
  attendanceRequest,
  dateOnly,
  dayLabel,
  todayWib,
  WEEKDAYS,
  wibAddDays,
} from "@/lib/attendance";
import { masterRequest } from "@/lib/master-data";

type PatternRow = AcademicRecord & {
  code: string;
  name: string;
  is_active: boolean;
  assignments_count?: number;
  versions?: {
    id: number;
    version_number: number;
    day_of_week: number;
    start_time: string;
    end_time: string;
    late_tolerance_minutes: number;
    effective_from: string;
    effective_to: string | null;
  }[];
  assignments?: {
    id: number;
    effective_from: string;
    effective_to: string | null;
    employee?: { person?: { full_name?: string } | null } | null;
  }[];
};

type AssignmentRow = AcademicRecord & {
  effective_from: string;
  effective_to: string | null;
  workPattern?: { code: string; name: string } | null;
  employee?: { person?: { full_name?: string } | null } | null;
};

type Policy = AcademicRecord & {
  version_number: number;
  requires_photo: boolean;
  correction_deadline_days: number;
  photo_max_size_bytes: number;
  photo_max_dimension: number;
  photo_quality: number;
  gps_max_accuracy_meters: string;
  gps_max_age_seconds: number;
  effective_from: string;
  effective_to: string | null;
};

/** Ringkasan hari aktif versi terakhir, mis. "Senin–Sabtu 07:00–14:00". */
function patternSummary(pattern: PatternRow): string {
  const latest = Math.max(
    ...(pattern.versions ?? []).map((row) => row.version_number),
    0,
  );
  const rows = (pattern.versions ?? []).filter(
    (row) => row.version_number === latest,
  );
  if (rows.length === 0) return "—";
  const time = `${rows[0].start_time.slice(0, 5)}–${rows[0].end_time.slice(0, 5)}`;
  const first = dayLabel(rows[0].day_of_week);
  const last = dayLabel(rows[rows.length - 1].day_of_week);

  return `${first}${rows.length > 1 ? `–${last}` : ""} ${time} (tol. ${rows[0].late_tolerance_minutes} mnt)`;
}

export function AttendancePatterns() {
  const [tab, setTab] = useState("patterns");
  const [employees, setEmployees] = useState<Option[]>([]);
  const [creating, setCreating] = useState(false);
  const [versioning, setVersioning] = useState<PatternRow | null>(null);
  const [assigning, setAssigning] = useState<PatternRow | null>(null);
  const [generating, setGenerating] = useState(false);
  const [policy, setPolicy] = useState<Policy | null>(null);
  const [policying, setPolicying] = useState(false);
  const [failure, setFailure] = useState("");
  const [tick, setTick] = useState(0);
  const reload = useCallback(() => setTick((value) => value + 1), []);

  // biome-ignore lint/correctness/useExhaustiveDependencies: tick memuat ulang kebijakan setelah mutasi
  useEffect(() => {
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
    attendanceRequest<{ data: Policy }>("policy")
      .then((data) => active && setPolicy(data.data))
      .catch((error) => setFailure(attendanceError(error)));

    return () => {
      active = false;
    };
  }, [tick]);

  return (
    <div className="animate-enter space-y-6">
      <AcademicHeading
        title="Pola kerja & kebijakan"
        description="Pola kerja berversi per hari kerja; penetapan pegawai menggantikan pola lama secara efektif; generator hari kerja menandai libur kalender tanpa dianggap alpa."
      />
      {failure && <p className="text-sm text-destructive">{failure}</p>}
      <Tabs value={tab} onValueChange={setTab}>
        <TabsList className="mb-5 h-14! w-full justify-start gap-1 rounded-xl bg-secondary p-1 sm:w-auto">
          <TabsTrigger
            value="patterns"
            className="h-11! min-w-0 flex-1 px-3 text-xs sm:flex-none"
          >
            Pola kerja
          </TabsTrigger>
          <TabsTrigger
            value="assignments"
            className="h-11! min-w-0 flex-1 px-3 text-xs sm:flex-none"
          >
            Penetapan & hari kerja
          </TabsTrigger>
          <TabsTrigger
            value="policy"
            className="h-11! min-w-0 flex-1 px-3 text-xs sm:flex-none"
          >
            Kebijakan foto/GPS
          </TabsTrigger>
        </TabsList>

        <TabsContent value="patterns" className="space-y-4">
          <div className="flex justify-end">
            <Button
              className="h-11 gap-2 text-xs"
              onClick={() => setCreating(true)}
            >
              <Plus className="size-4" /> Tambah pola kerja
            </Button>
          </div>
          <AcademicList<PatternRow>
            reloadKey={`patterns-${tick}`}
            load={async (page) =>
              attendanceRequest<AcademicPage<PatternRow>>(
                `work-patterns?page=${page}&per_page=25`,
              )
            }
            columns={[
              {
                label: "Kode",
                cell: (row) => (
                  <span className="text-[13px] font-medium">{row.code}</span>
                ),
              },
              { label: "Nama", cell: (row) => row.name },
              { label: "Jadwal", cell: (row) => patternSummary(row) },
              {
                label: "Status",
                cell: (row) =>
                  row.is_active ? (
                    <Badge
                      variant="secondary"
                      className="bg-primary/8 text-[10px] text-primary"
                    >
                      Aktif
                    </Badge>
                  ) : (
                    <Badge variant="outline" className="text-[10px]">
                      Nonaktif
                    </Badge>
                  ),
              },
              {
                label: "Pegawai",
                cell: (row) => String(row.assignments_count ?? 0),
              },
            ]}
            rowTitle={(row) => `${row.code} — ${row.name}`}
            rowMeta={(row) => patternSummary(row)}
            rowActions={(row) => (
              <div className="flex gap-2">
                <Button
                  variant="outline"
                  className="h-11 text-xs"
                  onClick={() => setVersioning(row)}
                >
                  Versi baru
                </Button>
                <Button
                  variant="outline"
                  className="h-11 text-xs"
                  onClick={() => setAssigning(row)}
                >
                  Tetapkan
                </Button>
              </div>
            )}
            emptyTitle="Belum ada pola kerja"
            emptyHint="Tambahkan pola kerja bawaan atau pola khusus madrasah."
          />
        </TabsContent>

        <TabsContent value="assignments" className="space-y-4">
          <div className="flex justify-end">
            <Button
              className="h-11 gap-2 text-xs"
              onClick={() => setGenerating(true)}
            >
              <Plus className="size-4" /> Buat hari kerja
            </Button>
          </div>
          <AcademicList<AssignmentRow>
            reloadKey={`assignments-${tick}`}
            load={async (page) =>
              attendanceRequest<AcademicPage<AssignmentRow>>(
                `work-assignments?page=${page}&per_page=50`,
              )
            }
            columns={[
              {
                label: "Pegawai",
                cell: (row) => (
                  <span className="text-[13px] font-medium">
                    {row.employee?.person?.full_name ?? `#${row.employee_id}`}
                  </span>
                ),
              },
              {
                label: "Pola",
                cell: (row) =>
                  row.workPattern
                    ? `${row.workPattern.code} · ${row.workPattern.name}`
                    : "—",
              },
              {
                label: "Masa",
                cell: (row) => (
                  <span className="text-xs">
                    {dateOnly(row.effective_from)} →{" "}
                    {row.effective_to ? dateOnly(row.effective_to) : "berjalan"}
                  </span>
                ),
              },
            ]}
            rowTitle={(row) =>
              row.employee?.person?.full_name ?? `#${row.employee_id}`
            }
            rowMeta={(row) => row.workPattern?.name ?? ""}
            emptyTitle="Belum ada penetapan pola kerja"
            emptyHint="Tetapkan pola kerja ke pegawai agar hari kerja dapat dibuat."
          />
        </TabsContent>

        <TabsContent value="policy" className="space-y-4">
          {policy && (
            <Card className="rounded-2xl border border-border/70 py-0 ring-0">
              <CardContent className="space-y-4 p-4 sm:p-6">
                <div className="flex flex-wrap items-center justify-between gap-3">
                  <h2 className="text-base font-semibold">
                    Kebijakan berlaku (versi {policy.version_number})
                  </h2>
                  <Button
                    className="h-11 text-xs"
                    onClick={() => setPolicying(true)}
                  >
                    Versi kebijakan baru
                  </Button>
                </div>
                <dl className="grid gap-3 text-sm sm:grid-cols-2">
                  <div>
                    <dt className="text-xs text-muted-foreground">Berlaku</dt>
                    <dd className="font-medium">
                      {dateOnly(policy.effective_from)} →{" "}
                      {policy.effective_to
                        ? dateOnly(policy.effective_to)
                        : "berjalan"}
                    </dd>
                  </div>
                  <div>
                    <dt className="text-xs text-muted-foreground">
                      Foto + GPS wajib
                    </dt>
                    <dd className="font-medium">
                      {policy.requires_photo ? "Ya" : "Tidak"}
                    </dd>
                  </div>
                  <div>
                    <dt className="text-xs text-muted-foreground">
                      Akurasi GPS maksimal
                    </dt>
                    <dd className="font-medium">
                      {Math.round(Number(policy.gps_max_accuracy_meters))} m
                    </dd>
                  </div>
                  <div>
                    <dt className="text-xs text-muted-foreground">
                      Kesegaran foto/lokasi
                    </dt>
                    <dd className="font-medium">
                      {policy.gps_max_age_seconds} detik
                    </dd>
                  </div>
                  <div>
                    <dt className="text-xs text-muted-foreground">
                      Kompresi foto
                    </dt>
                    <dd className="font-medium">
                      WebP ≤ {Math.round(policy.photo_max_size_bytes / 1024)}{" "}
                      KiB · sisi {policy.photo_max_dimension} px · kualitas{" "}
                      {policy.photo_quality}
                    </dd>
                  </div>
                  <div>
                    <dt className="text-xs text-muted-foreground">
                      Batas koreksi
                    </dt>
                    <dd className="font-medium">
                      {policy.correction_deadline_days} hari kalender
                    </dd>
                  </div>
                </dl>
              </CardContent>
            </Card>
          )}
        </TabsContent>
      </Tabs>

      {creating && (
        <PatternDaysDialog
          title="Tambah pola kerja"
          withCodeFields
          onClose={() => setCreating(false)}
          submit={async (payload) => {
            await attendanceRequest("work-patterns", "POST", payload);
            reload();
          }}
        />
      )}

      {versioning && (
        <PatternDaysDialog
          title={`Versi baru pola ${versioning.code}`}
          description="Versi lama ditutup sehari sebelum tanggal efektif; hari kerja yang sudah terbentuk tetap memakai snapshot lama."
          withCodeFields={false}
          onClose={() => setVersioning(null)}
          submit={async (payload) => {
            await attendanceRequest(
              `work-patterns/${versioning.id}/versions`,
              "POST",
              payload,
            );
            reload();
          }}
        />
      )}

      {assigning && (
        <AcademicFormDialog
          title="Tetapkan pola kerja"
          description={`Pola ${assigning.code}. Penetapan baru menutup penetapan lama pada hari sebelum tanggal efektif.`}
          fields={[
            {
              name: "employee_id",
              label: "Pegawai",
              required: true,
              type: "select",
              options: employees,
            },
            {
              name: "effective_from",
              label: "Berlaku mulai",
              required: true,
              max: 10,
              type: "date",
            },
          ]}
          onClose={() => setAssigning(null)}
          submit={async (body) => {
            await attendanceRequest(
              `work-patterns/${assigning.id}/assignments`,
              "POST",
              {
                employee_id: Number(body.employee_id),
                effective_from: body.effective_from,
                reason: body.reason,
              },
            );
            reload();
          }}
        />
      )}

      {generating && (
        <GenerateDialog onClose={() => setGenerating(false)} reload={reload} />
      )}

      {policying && policy && (
        <AcademicFormDialog
          title="Versi kebijakan baru"
          description="Kebijakan baru berlaku mulai besok atau setelahnya; data lama tidak diubah dan tidak retroaktif (FR-05-06)."
          fields={[
            {
              name: "effective_from",
              label: "Berlaku mulai",
              required: true,
              max: 10,
              type: "date",
            },
            {
              name: "requires_photo",
              label: "Foto + GPS wajib untuk aksi mandiri",
              type: "boolean",
            },
            {
              name: "correction_deadline_days",
              label: "Batas koreksi (hari)",
              required: true,
              type: "number",
              max: 2,
            },
            {
              name: "gps_max_accuracy_meters",
              label: "Akurasi GPS maksimal (meter)",
              required: true,
              type: "number",
              max: 4,
            },
            {
              name: "gps_max_age_seconds",
              label: "Maksimal umur foto/lokasi (detik)",
              required: true,
              type: "number",
              max: 3,
            },
          ]}
          current={
            {
              requires_photo: policy.requires_photo,
              correction_deadline_days: policy.correction_deadline_days,
              gps_max_accuracy_meters: String(
                Number(policy.gps_max_accuracy_meters),
              ),
              gps_max_age_seconds: policy.gps_max_age_seconds,
            } as unknown as AcademicRecord
          }
          onClose={() => setPolicying(false)}
          submit={async (body) => {
            await attendanceRequest("policy/versions", "POST", {
              effective_from: body.effective_from,
              requires_photo: body.requires_photo === true,
              correction_deadline_days: Number(body.correction_deadline_days),
              photo_max_size_bytes: policy.photo_max_size_bytes,
              photo_max_dimension: policy.photo_max_dimension,
              photo_quality: policy.photo_quality,
              gps_max_accuracy_meters: Number(body.gps_max_accuracy_meters),
              gps_max_age_seconds: Number(body.gps_max_age_seconds),
              reason: body.reason,
            });
            reload();
          }}
        />
      )}
    </div>
  );
}

/** Dialog pola: pilih hari aktif + jam + toleransi yang sama untuk semua hari. */
function PatternDaysDialog({
  title,
  description,
  withCodeFields,
  onClose,
  submit,
}: {
  title: string;
  description?: string;
  withCodeFields: boolean;
  onClose: () => void;
  submit: (payload: Record<string, unknown>) => Promise<void>;
}) {
  const [days, setDays] = useState<number[]>([1, 2, 3, 4, 5, 6]);
  const [start, setStart] = useState("07:00");
  const [end, setEnd] = useState("14:00");
  const [tolerance, setTolerance] = useState("10");
  const [effective, setEffective] = useState(() => todayWib());
  const [error, setError] = useState("");
  return (
    <AcademicFormDialog
      title={title}
      description={
        description ??
        "Pilih hari kerja aktif beserta jam dan toleransi keterlambatan yang berlaku untuk hari-hari tersebut."
      }
      fields={
        withCodeFields
          ? [
              { name: "code", label: "Kode pola", required: true, max: 50 },
              { name: "name", label: "Nama pola", required: true, max: 150 },
            ]
          : []
      }
      onClose={onClose}
      submit={async (body) => {
        if (days.length === 0) {
          setError("Pilih minimal satu hari kerja.");

          return;
        }
        if (!start || !end || end <= start) {
          setError("Jam selesai harus setelah jam mulai.");

          return;
        }
        setError("");
        await submit({
          ...body,
          effective_from: effective,
          days: days.map((day) => ({
            day_of_week: day,
            start_time: start,
            end_time: end,
            late_tolerance_minutes: Number(tolerance || 10),
          })),
        });
      }}
      extra={
        <div className="space-y-4">
          <div className="space-y-2">
            <Label htmlFor="pattern-effective">Berlaku mulai</Label>
            <Input
              id="pattern-effective"
              type="date"
              value={effective}
              onChange={(event) => setEffective(event.target.value)}
              className="h-11 text-base"
            />
          </div>
          <div className="space-y-2">
            <Label>Hari kerja aktif</Label>
            <div className="grid grid-cols-2 gap-2 sm:grid-cols-3">
              {WEEKDAYS.map((day) => (
                <Label
                  key={day.value}
                  className="flex min-h-11 items-center gap-3 rounded-lg border p-3 text-sm"
                >
                  <Checkbox
                    checked={days.includes(day.value)}
                    onCheckedChange={(checked) =>
                      setDays((current) =>
                        checked === true
                          ? [...current, day.value]
                          : current.filter((value) => value !== day.value),
                      )
                    }
                  />
                  {day.label}
                </Label>
              ))}
            </div>
          </div>
          <div className="grid grid-cols-3 gap-3">
            <div className="space-y-2">
              <Label htmlFor="pattern-start">Jam mulai</Label>
              <Input
                id="pattern-start"
                type="time"
                value={start}
                onChange={(event) => setStart(event.target.value)}
                className="h-11"
              />
            </div>
            <div className="space-y-2">
              <Label htmlFor="pattern-end">Jam selesai</Label>
              <Input
                id="pattern-end"
                type="time"
                value={end}
                onChange={(event) => setEnd(event.target.value)}
                className="h-11"
              />
            </div>
            <div className="space-y-2">
              <Label htmlFor="pattern-tolerance">Toleransi (mnt)</Label>
              <Input
                id="pattern-tolerance"
                type="number"
                value={tolerance}
                min={0}
                max={240}
                onChange={(event) => setTolerance(event.target.value)}
                className="h-11"
              />
            </div>
          </div>
          {error && <p className="text-sm text-destructive">{error}</p>}
        </div>
      }
    />
  );
}

function GenerateDialog({
  onClose,
  reload,
}: {
  onClose: () => void;
  reload: () => void;
}) {
  const today = todayWib();
  const end = wibAddDays(today, 13);
  return (
    <ConfirmAction
      title="Buat hari kerja pegawai"
      description="Membuat baris hari kerja untuk pegawai dengan penetapan pola aktif selama 14 hari ke depan. Tanggal libur kalender ditandai tidak wajib kerja dan tidak dihitung alpa."
      submitLabel="Buat hari kerja"
      onClose={onClose}
      action={async (reason) => {
        await attendanceRequest("work-days/generate", "POST", {
          date_from: today,
          date_to: end,
          reason,
        });
        reload();
      }}
    />
  );
}
