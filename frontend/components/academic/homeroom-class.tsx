"use client";

import { useEffect, useState } from "react";
import { AcademicHeading } from "@/components/academic/academic-shared";
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
import { dateOnly, todayWib } from "@/lib/attendance";
import {
  type RosterPayload,
  studentAttendanceError,
  studentAttendanceRequest,
} from "@/lib/student-attendance";

/** Ringkasan kelas perwalian wali kelas: daftar siswa + status hari ini. */
export function HomeroomClass() {
  const [roster, setRoster] = useState<RosterPayload | null>(null);
  const [failure, setFailure] = useState("");
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    let active = true;
    (async () => {
      try {
        const classes = await studentAttendanceRequest<{
          data: { id: number; display_name: string }[];
        }>("classes");
        if (!active) return;
        const homeroom = classes.data[0];
        if (!homeroom) {
          setRoster(null);
          setLoading(false);

          return;
        }
        const result = await studentAttendanceRequest<{ data: RosterPayload }>(
          `roster?class_period_id=${homeroom.id}&date=${todayWib()}`,
        );
        if (active) setRoster(result.data);
      } catch (error) {
        if (active) setFailure(studentAttendanceError(error));
      } finally {
        if (active) setLoading(false);
      }
    })();

    return () => {
      active = false;
    };
  }, []);

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
      {failure && <p className="text-sm text-destructive">{failure}</p>}
      {loading ? (
        <p className="text-sm text-muted-foreground">Memuat…</p>
      ) : !roster ? (
        <p className="rounded-xl border border-dashed p-5 text-sm leading-6 text-muted-foreground">
          Anda belum ditunjuk sebagai wali kelas pada periode berjalan.
        </p>
      ) : (
        <>
          <div className="flex flex-wrap items-center gap-2 text-xs text-muted-foreground">
            <Badge
              variant="secondary"
              className="bg-primary/8 text-[10px] text-primary"
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
                                  ? "bg-primary/8 text-[10px] text-primary"
                                  : "text-[10px]"
                              }
                            >
                              {row.attendance.status.name}
                            </Badge>
                          ) : (
                            <Badge variant="outline" className="text-[10px]">
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
      )}
    </div>
  );
}
