"use client";

import { useCallback, useEffect, useState } from "react";
import {
  AcademicHeading,
  FilterSelect,
} from "@/components/academic/academic-shared";
import { DataFailure, DataLoading } from "@/components/layout/data-feedback";
import { Badge } from "@/components/ui/badge";
import { Card, CardContent } from "@/components/ui/card";
import {
  Table,
  TableBody,
  TableCell,
  TableHead,
  TableHeader,
  TableRow,
} from "@/components/ui/table";
import { useAsyncData } from "@/hooks/use-async-data";
import { dateOnly, todayWib } from "@/lib/attendance";
import {
  type RosterPayload,
  studentAttendanceRequest,
} from "@/lib/student-attendance";

/** Ringkasan kelas perwalian wali kelas: daftar siswa + status hari ini. */
export function HomeroomClass() {
  const [classId, setClassId] = useState("");
  const classesQuery = useAsyncData(
    useCallback(
      () =>
        studentAttendanceRequest<{
          data: { id: number; display_name: string }[];
        }>("classes?homeroom_only=1"),
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
      if (!classId) return null;
      return studentAttendanceRequest<{ data: RosterPayload }>(
        `roster?class_period_id=${classId}&date=${todayWib()}`,
      );
    }, [classId]),
  );
  const loading = classesQuery.loading || rosterQuery.loading;
  const payload = rosterQuery.data?.data;
  const roster =
    !loading && String(payload?.class_period.id) === classId ? payload : null;
  const failure = classesQuery.error || rosterQuery.error;

  const recorded = (roster?.rows ?? []).filter(
    (row) => row.attendance !== null,
  ).length;

  return (
    <div className="animate-enter space-y-6">
      <AcademicHeading
        category="Akademik & kelas"
        title="Kelas perwalian saya"
        description="Daftar siswa kelas perwalian beserta status kehadiran hari ini; pengisian absensi dilakukan pada menu Absensi harian siswa."
      />
      {failure && <DataFailure message={failure} />}
      {classes.length > 1 && (
        <FilterSelect
          label="Kelas perwalian"
          value={classId}
          onChange={setClassId}
          options={classes.map((item) => ({
            value: String(item.id),
            label: item.display_name,
          }))}
        />
      )}
      {loading ? (
        <DataLoading />
      ) : !roster && !failure ? (
        <p className="rounded-xl border border-dashed p-5 text-sm leading-6 text-muted-foreground">
          Anda belum ditunjuk sebagai wali kelas pada periode berjalan.
        </p>
      ) : roster ? (
        <>
          <div className="flex flex-wrap items-center gap-2 text-xs text-muted-foreground">
            <Badge
              variant="secondary"
              className="bg-primary/8 text-xs text-primary"
            >
              {roster.class_period.display_name}
            </Badge>
            <span>
              {dateOnly(roster.date)} · {recorded}/{roster.rows.length} terisi
              hari ini
            </span>
          </div>
          <Card className="min-w-0 rounded-2xl border border-border/70 py-0 ring-0">
            <CardContent className="p-4 sm:p-5">
              <div className="min-w-0 overflow-x-auto">
                <Table>
                  <TableHeader>
                    <TableRow>
                      <TableHead>Siswa</TableHead>
                      <TableHead>Status hari ini</TableHead>
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
                              variant="secondary"
                              className={
                                row.attendance.status.code === "hadir"
                                  ? "bg-primary/8 text-xs text-primary"
                                  : "text-xs"
                              }
                            >
                              {row.attendance.status.name}
                            </Badge>
                          ) : (
                            <Badge variant="outline" className="text-xs">
                              Belum dicatat
                            </Badge>
                          )}
                        </TableCell>
                      </TableRow>
                    ))}
                  </TableBody>
                </Table>
              </div>
            </CardContent>
          </Card>
        </>
      ) : null}
    </div>
  );
}
