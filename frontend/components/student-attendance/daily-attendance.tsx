"use client";

import { useCallback, useEffect, useState } from "react";
import {
  AcademicFormDialog,
  AcademicHeading,
  DateFilter,
  FilterSelect,
} from "@/components/academic/academic-shared";
import { DataFailure, DataLoading } from "@/components/layout/data-feedback";
import { Badge } from "@/components/ui/badge";
import { Button } from "@/components/ui/button";
import { Card, CardContent } from "@/components/ui/card";
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from "@/components/ui/select";
import {
  Table,
  TableBody,
  TableCell,
  TableHead,
  TableHeader,
  TableRow,
} from "@/components/ui/table";
import { useAsyncData } from "@/hooks/use-async-data";
import {
  type ClassSummary,
  dateOnly,
  type RosterPayload,
  type RosterRow,
  studentAttendanceError,
  studentAttendanceRequest,
  todayWib,
} from "@/lib/student-attendance";

/** Status default saat memilih siswa yang belum tercatat. */
const DEFAULT_CODE = "hadir";

export function DailyAttendance() {
  const [classId, setClassId] = useState("");
  const [date, setDate] = useState(() => todayWib());
  const [choices, setChoices] = useState<Record<number, string>>({});
  const [mutationFailure, setFailure] = useState("");
  const [saving, setSaving] = useState(false);
  const [correcting, setCorrecting] = useState<RosterRow | null>(null);
  const classesQuery = useAsyncData(
    useCallback(
      () => studentAttendanceRequest<{ data: ClassSummary[] }>("classes"),
      [],
    ),
  );
  const classes = classesQuery.data?.data ?? [];
  useEffect(() => {
    const first = classesQuery.data?.data[0];
    if (first) setClassId((current) => current || String(first.id));
  }, [classesQuery.data]);
  const rosterQuery = useAsyncData(
    useCallback(async () => {
      if (!classId || !date) return null;
      return studentAttendanceRequest<{ data: RosterPayload }>(
        `roster?class_period_id=${classId}&date=${date}`,
      );
    }, [classId, date]),
  );
  const loading = classesQuery.loading || rosterQuery.loading;
  const payload = rosterQuery.data?.data;
  const roster =
    !loading &&
    String(payload?.class_period.id) === classId &&
    payload?.date === date
      ? payload
      : null;
  const failure = mutationFailure || classesQuery.error || rosterQuery.error;
  async function loadRoster() {
    setChoices({});
    await rosterQuery.reload();
  }
  function changeClass(value: string) {
    setClassId(value);
    setChoices({});
    setCorrecting(null);
    setFailure("");
  }
  function changeDate(value: string) {
    setDate(value);
    setChoices({});
    setCorrecting(null);
    setFailure("");
  }

  const pending = (roster?.rows ?? []).filter(
    (row) => row.attendance === null && choices[row.student_id],
  );

  async function save() {
    if (!roster || loading || pending.length === 0 || saving) return;
    setSaving(true);
    setFailure("");
    try {
      await studentAttendanceRequest("record", "POST", {
        class_period_id: roster.class_period.id,
        date: roster.date,
        entries: pending.map((row) => ({
          student_id: row.student_id,
          status_code: choices[row.student_id],
        })),
        reason: `Pengisian absensi harian ${roster.class_period.display_name} tanggal ${roster.date}`,
      });
      await loadRoster();
    } catch (error) {
      setFailure(studentAttendanceError(error));
    } finally {
      setSaving(false);
    }
  }

  return (
    <div className="animate-enter space-y-6">
      <AcademicHeading
        category="Absensi siswa"
        title="Absensi harian siswa"
        description="Guru/wali kelas mengisi hadir, sakit, izin, atau alpa untuk kelas penugasannya sebelum 23.59 WIB; tanpa catatan berarti belum dicatat, bukan alpa."
      />
      {failure && <DataFailure message={failure} />}
      <div className="flex flex-wrap items-end justify-between gap-3">
        <div className="flex flex-wrap items-end gap-3">
          <FilterSelect
            label="Kelas"
            value={classId}
            onChange={changeClass}
            disabled={saving}
            options={classes.map((row) => ({
              value: String(row.id),
              label: row.display_name,
            }))}
            placeholder="Pilih kelas"
            className="w-full sm:w-72"
          />
          <DateFilter
            label="Tanggal"
            value={date}
            onChange={changeDate}
            disabled={saving}
          />
        </div>
        {roster && pending.length > 0 && (
          <Button
            className="h-11 text-xs"
            disabled={saving}
            onClick={() => void save()}
          >
            {saving ? "Menyimpan…" : `Simpan ${pending.length} siswa`}
          </Button>
        )}
      </div>

      {loading && <DataLoading />}
      {!loading && classes.length === 0 && !failure && (
        <p className="rounded-xl border border-dashed p-5 text-sm leading-6 text-muted-foreground">
          Belum ada kelas penugasan Anda. Penugasan mengajar/wali kelas
          ditetapkan petugas pada modul Akademik.
        </p>
      )}

      {roster && (
        <>
          <p className="text-xs text-muted-foreground">
            {roster.class_period.display_name} ·{" "}
            {roster.rows.filter((row) => row.attendance !== null).length}/
            {roster.rows.length} terisi{" "}
            {date === todayWib() ? "hari ini" : dateOnly(date)}
          </p>
          <div className="flex flex-wrap items-center gap-2 text-xs text-muted-foreground">
            <Badge
              variant="secondary"
              className="bg-primary/8 text-xs text-primary"
            >
              Tenggat {roster.policy.input_deadline_time.slice(0, 5)} WIB
            </Badge>
            <span>
              Koreksi hingga {roster.policy.correction_deadline_days} hari
              melalui petugas berbeda.
            </span>
          </div>
          <Card className="min-w-0 rounded-2xl border border-border/70 py-0 ring-0">
            <CardContent className="p-4 sm:p-5">
              {loading ? (
                <p className="p-4 text-sm text-muted-foreground">Memuat…</p>
              ) : roster.rows.length === 0 ? (
                <p className="rounded-xl border border-dashed p-5 text-sm leading-6 text-muted-foreground">
                  Tidak ada siswa anggota kelas pada tanggal ini.
                </p>
              ) : (
                <div className="min-w-0 overflow-x-auto">
                  <Table>
                    <TableHeader>
                      <TableRow>
                        <TableHead>Siswa</TableHead>
                        <TableHead>Status</TableHead>
                        <TableHead className="text-right">Tindakan</TableHead>
                      </TableRow>
                    </TableHeader>
                    <TableBody>
                      {roster.rows.map((row) => (
                        <TableRow key={row.student_id}>
                          <TableCell className="font-medium">
                            {row.student.person?.full_name ??
                              `#${row.student_id}`}
                          </TableCell>
                          <TableCell>
                            {row.attendance ? (
                              <Badge
                                variant={
                                  row.attendance.status.code === "alpa"
                                    ? "outline"
                                    : "secondary"
                                }
                                className={
                                  row.attendance.status.code === "hadir"
                                    ? "bg-primary/8 text-xs text-primary"
                                    : "text-xs"
                                }
                              >
                                {row.attendance.status.name}
                              </Badge>
                            ) : choices[row.student_id] ? (
                              <Badge variant="secondary" className="text-xs">
                                {
                                  roster.statuses.find(
                                    (s) => s.code === choices[row.student_id],
                                  )?.name
                                }
                              </Badge>
                            ) : (
                              <Badge variant="outline" className="text-xs">
                                Belum dicatat
                              </Badge>
                            )}
                          </TableCell>
                          <TableCell className="text-right">
                            {row.attendance ? (
                              <Button
                                variant="outline"
                                className="h-11 text-xs"
                                onClick={() => setCorrecting(row)}
                              >
                                Ajukan koreksi
                              </Button>
                            ) : (
                              <div className="flex min-w-56 justify-end gap-2">
                                <Select
                                  value={choices[row.student_id] ?? ""}
                                  onValueChange={(code) =>
                                    setChoices((current) => ({
                                      ...current,
                                      [row.student_id]: code,
                                    }))
                                  }
                                >
                                  <SelectTrigger
                                    aria-label={`Status ${row.student.person?.full_name ?? row.student_id}`}
                                    className="h-11! w-36 text-sm"
                                  >
                                    <SelectValue placeholder="Pilih…" />
                                  </SelectTrigger>
                                  <SelectContent>
                                    {roster.statuses.map((status) => (
                                      <SelectItem
                                        key={status.code}
                                        value={status.code}
                                      >
                                        {status.name}
                                      </SelectItem>
                                    ))}
                                  </SelectContent>
                                </Select>
                                <Button
                                  variant="outline"
                                  className="h-11 text-xs"
                                  onClick={() =>
                                    setChoices((current) => ({
                                      ...current,
                                      [row.student_id]: DEFAULT_CODE,
                                    }))
                                  }
                                >
                                  Hadir
                                </Button>
                              </div>
                            )}
                          </TableCell>
                        </TableRow>
                      ))}
                    </TableBody>
                  </Table>
                </div>
              )}
            </CardContent>
          </Card>
        </>
      )}

      {correcting?.attendance && roster && (
        <AcademicFormDialog
          title="Ajukan koreksi absensi siswa"
          description={`${correcting.student.person?.full_name ?? ""} · ${dateOnly(roster.date)} — status tercatat ${
            correcting.attendance.status.name
          }. Koreksi disetujui petugas akademik yang berbeda.`}
          fields={[
            {
              name: "status_code",
              label: "Status baru",
              required: true,
              type: "select",
              options: roster.statuses.map((status) => ({
                value: status.code,
                label: status.name,
              })),
            },
            { name: "notes", label: "Catatan", type: "textarea", max: 1000 },
          ]}
          onClose={() => setCorrecting(null)}
          submit={async (body) => {
            await studentAttendanceRequest("corrections", "POST", {
              student_daily_attendance_id: correcting.attendance?.id,
              after_values: {
                status_code: body.status_code,
                ...(body.notes ? { notes: body.notes } : {}),
              },
              reason: body.reason,
              expected_lock_version: correcting.attendance?.lock_version,
            });
            await loadRoster();
          }}
        />
      )}
    </div>
  );
}
