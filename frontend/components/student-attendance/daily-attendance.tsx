"use client";

import { useCallback, useEffect, useState } from "react";
import {
  AcademicFormDialog,
  AcademicHeading,
  DateFilter,
  FilterSelect,
} from "@/components/academic/academic-shared";
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
  const [classes, setClasses] = useState<ClassSummary[]>([]);
  const [classId, setClassId] = useState("");
  const [date, setDate] = useState(() => todayWib());
  const [roster, setRoster] = useState<RosterPayload | null>(null);
  const [choices, setChoices] = useState<Record<number, string>>({});
  const [failure, setFailure] = useState("");
  const [loading, setLoading] = useState(false);
  const [saving, setSaving] = useState(false);
  const [correcting, setCorrecting] = useState<RosterRow | null>(null);

  useEffect(() => {
    let active = true;
    studentAttendanceRequest<{ data: ClassSummary[] }>("classes")
      .then((result) => {
        if (!active) return;
        setClasses(result.data);
        if (result.data.length > 0) {
          setClassId((current) => current || String(result.data[0].id));
        }
      })
      .catch((error) => active && setFailure(studentAttendanceError(error)));
    return () => {
      active = false;
    };
  }, []);

  const loadRoster = useCallback(async () => {
    if (!classId || !date) return;
    setLoading(true);
    try {
      const result = await studentAttendanceRequest<{ data: RosterPayload }>(
        `roster?class_period_id=${classId}&date=${date}`,
      );
      setRoster(result.data);
      setChoices({});
      setFailure("");
    } catch (error) {
      setRoster(null);
      setFailure(studentAttendanceError(error));
    } finally {
      setLoading(false);
    }
  }, [classId, date]);

  useEffect(() => {
    void loadRoster();
  }, [loadRoster]);

  const pending = (roster?.rows ?? []).filter(
    (row) => row.attendance === null && choices[row.student_id],
  );

  async function save() {
    if (!roster || pending.length === 0 || saving) return;
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
      {failure && <p className="text-sm text-destructive">{failure}</p>}
      <div className="flex flex-wrap items-end justify-between gap-3">
        <div className="flex flex-wrap items-end gap-3">
          <FilterSelect
            label="Kelas"
            value={classId}
            onChange={setClassId}
            options={classes.map((row) => ({
              value: String(row.id),
              label: row.display_name,
            }))}
            placeholder="Pilih kelas"
            className="w-full sm:w-72"
          />
          <DateFilter label="Tanggal" value={date} onChange={setDate} />
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

      {classes.length === 0 && !failure && (
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
            {roster.rows.length} terisi hari ini
          </p>
          <div className="flex flex-wrap items-center gap-2 text-xs text-muted-foreground">
            <Badge
              variant="secondary"
              className="bg-primary/8 text-[10px] text-primary"
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
                                    ? "bg-primary/8 text-[10px] text-primary"
                                    : "text-[10px]"
                                }
                              >
                                {row.attendance.status.name}
                              </Badge>
                            ) : choices[row.student_id] ? (
                              <Badge
                                variant="secondary"
                                className="text-[10px]"
                              >
                                {
                                  roster.statuses.find(
                                    (s) => s.code === choices[row.student_id],
                                  )?.name
                                }
                              </Badge>
                            ) : (
                              <Badge variant="outline" className="text-[10px]">
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
