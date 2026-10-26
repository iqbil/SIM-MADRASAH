"use client";

import { useCallback, useEffect, useState } from "react";
import {
  AcademicHeading,
  DateFilter,
  FilterSelect,
} from "@/components/academic/academic-shared";
import { Card, CardContent } from "@/components/ui/card";
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
  studentAttendanceError,
  studentAttendanceRequest,
  todayWib,
  wibMonthRange,
} from "@/lib/student-attendance";

type RecapPayload = {
  class_period: { id: number; display_name: string };
  range: { from: string; to: string };
  by_date: {
    date: string;
    hadir: number;
    sakit: number;
    izin: number;
    alpa: number;
    belum_dicatat: number;
    roster: number;
  }[];
  by_student: {
    student_id: number;
    student?: { person?: { full_name?: string } | null };
    hadir: number;
    sakit: number;
    izin: number;
    alpa: number;
    belum_dicatat: number;
    hari_aktif: number;
  }[];
};

export function StudentRecap() {
  const month = wibMonthRange();
  const [classes, setClasses] = useState<ClassSummary[]>([]);
  const [classId, setClassId] = useState("");
  const [from, setFrom] = useState(month.from);
  const [to, setTo] = useState(month.to);
  const [recap, setRecap] = useState<RecapPayload | null>(null);
  const [failure, setFailure] = useState("");
  const [loading, setLoading] = useState(false);

  useEffect(() => {
    let active = true;
    studentAttendanceRequest<{ data: ClassSummary[] }>("classes")
      .then((result) => {
        if (!active) return;
        setClasses(result.data);
        if (result.data.length > 0) setClassId(String(result.data[0].id));
      })
      .catch((error) => active && setFailure(studentAttendanceError(error)));
    return () => {
      active = false;
    };
  }, []);

  const load = useCallback(async () => {
    if (!classId) return;
    setLoading(true);
    try {
      const result = await studentAttendanceRequest<{ data: RecapPayload }>(
        `recap?class_period_id=${classId}&date_from=${from}&date_to=${to}`,
      );
      setRecap(result.data);
      setFailure("");
    } catch (error) {
      setRecap(null);
      setFailure(studentAttendanceError(error));
    } finally {
      setLoading(false);
    }
  }, [classId, from, to]);

  useEffect(() => {
    void load();
  }, [load]);

  return (
    <div className="animate-enter space-y-6">
      <AcademicHeading
        category="Absensi siswa"
        title="Rekap kehadiran siswa"
        description="Rekap per tanggal dan per siswa; kolom belum dicatat menandakan pengisian yang belum dilakukan — bukan alpa."
      />
      {failure && <p className="text-sm text-destructive">{failure}</p>}
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
          className="w-full sm:w-64"
        />
        <DateFilter label="Dari tanggal" value={from} onChange={setFrom} />
        <DateFilter label="Sampai" value={to} onChange={setTo} />
      </div>

      {recap && (
        <>
          <Card className="min-w-0 rounded-2xl border border-border/70 py-0 ring-0">
            <CardContent className="p-4 sm:p-5">
              <h2 className="mb-3 text-base font-semibold">Per tanggal</h2>
              {loading ? (
                <p className="p-2 text-sm text-muted-foreground">Memuat…</p>
              ) : (
                <div className="min-w-0 overflow-x-auto">
                  <Table>
                    <TableHeader>
                      <TableRow>
                        <TableHead>Tanggal</TableHead>
                        <TableHead>Hadir</TableHead>
                        <TableHead>Sakit</TableHead>
                        <TableHead>Izin</TableHead>
                        <TableHead>Alpa</TableHead>
                        <TableHead>Belum dicatat</TableHead>
                        <TableHead>Anggota</TableHead>
                      </TableRow>
                    </TableHeader>
                    <TableBody>
                      {recap.by_date.map((row) => (
                        <TableRow key={row.date}>
                          <TableCell className="whitespace-nowrap font-medium">
                            {dateOnly(row.date)}
                          </TableCell>
                          <TableCell>{row.hadir}</TableCell>
                          <TableCell>{row.sakit}</TableCell>
                          <TableCell>{row.izin}</TableCell>
                          <TableCell>{row.alpa}</TableCell>
                          <TableCell>{row.belum_dicatat}</TableCell>
                          <TableCell>{row.roster}</TableCell>
                        </TableRow>
                      ))}
                    </TableBody>
                  </Table>
                </div>
              )}
            </CardContent>
          </Card>
          <Card className="min-w-0 rounded-2xl border border-border/70 py-0 ring-0">
            <CardContent className="p-4 sm:p-5">
              <h2 className="mb-3 text-base font-semibold">
                Per siswa ({recap.range.from} → {recap.range.to})
              </h2>
              <div className="min-w-0 overflow-x-auto">
                <Table>
                  <TableHeader>
                    <TableRow>
                      <TableHead>Siswa</TableHead>
                      <TableHead>Hari aktif</TableHead>
                      <TableHead>Hadir</TableHead>
                      <TableHead>Sakit</TableHead>
                      <TableHead>Izin</TableHead>
                      <TableHead>Alpa</TableHead>
                      <TableHead>Belum dicatat</TableHead>
                    </TableRow>
                  </TableHeader>
                  <TableBody>
                    {recap.by_student.map((row) => (
                      <TableRow key={row.student_id}>
                        <TableCell className="font-medium">
                          {row.student?.person?.full_name ??
                            `#${row.student_id}`}
                        </TableCell>
                        <TableCell>{row.hari_aktif}</TableCell>
                        <TableCell>{row.hadir}</TableCell>
                        <TableCell>{row.sakit}</TableCell>
                        <TableCell>{row.izin}</TableCell>
                        <TableCell>{row.alpa}</TableCell>
                        <TableCell>{row.belum_dicatat}</TableCell>
                      </TableRow>
                    ))}
                  </TableBody>
                </Table>
              </div>
            </CardContent>
          </Card>
        </>
      )}
      {!recap && !loading && !failure && (
        <p className="rounded-xl border border-dashed p-5 text-sm leading-6 text-muted-foreground">
          Pilih kelas dan rentang tanggal untuk melihat rekap. Hari ini{" "}
          {dateOnly(todayWib())}.
        </p>
      )}
    </div>
  );
}
