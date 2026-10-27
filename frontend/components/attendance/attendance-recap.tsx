"use client";

import { useCallback, useEffect, useState } from "react";
import {
  AcademicHeading,
  DateFilter,
} from "@/components/academic/academic-shared";
import { DataFailure } from "@/components/layout/data-feedback";
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
  attendanceError,
  attendanceRequest,
  dateOnly,
  wibMonthRange,
  wibTime,
} from "@/lib/attendance";

type SummaryRow = {
  employee_id: number;
  employee: {
    employee_number: string | null;
    person?: { full_name?: string } | null;
  };
  work_days: number;
  hadir: number;
  terlambat: number;
  izin: number;
  sakit: number;
  cuti: number;
  dinas_luar: number;
  tanpa_catatan: number;
};

type DailyRow = {
  work_date: string;
  is_work_required: boolean;
  status: string | null;
  check_in_at: string | null;
  check_out_at: string | null;
  late_minutes: number | null;
};

export function AttendanceRecap() {
  const range = wibMonthRange();
  const [from, setFrom] = useState(range.from);
  const [to, setTo] = useState(range.to);
  const [summary, setSummary] = useState<SummaryRow[]>([]);
  const [daily, setDaily] = useState<DailyRow[] | null>(null);
  const [failure, setFailure] = useState("");
  const [loading, setLoading] = useState(true);

  const load = useCallback(async () => {
    setLoading(true);
    try {
      const result = await attendanceRequest<{
        data: { summary: SummaryRow[]; daily: DailyRow[] | null };
      }>(`recap?date_from=${from}&date_to=${to}`);
      setSummary(result.data.summary);
      setDaily(result.data.daily);
      setFailure("");
    } catch (error) {
      setFailure(attendanceError(error));
    } finally {
      setLoading(false);
    }
  }, [from, to]);

  useEffect(() => {
    void load();
  }, [load]);

  return (
    <div className="animate-enter space-y-6">
      <AcademicHeading
        category="Absensi pegawai"
        title="Rekap kehadiran pegawai"
        description="Hadir, terlambat, izin, sakit, cuti, dinas luar, dan tanpa catatan per pegawai. Hari libur tidak dihitung sebagai alpa."
      />
      {failure && <DataFailure message={failure} />}
      <div className="flex flex-wrap items-end gap-3">
        <DateFilter label="Dari tanggal" value={from} onChange={setFrom} />
        <DateFilter label="Sampai" value={to} onChange={setTo} />
      </div>
      <Card className="min-w-0 rounded-2xl border border-border/70 py-0 ring-0">
        <CardContent className="p-4 sm:p-5">
          {loading ? (
            <p className="p-4 text-sm text-muted-foreground">Memuat…</p>
          ) : (
            <div className="min-w-0 overflow-x-auto">
              <Table>
                <TableHeader>
                  <TableRow>
                    <TableHead>Pegawai</TableHead>
                    <TableHead>Hari kerja</TableHead>
                    <TableHead>Hadir</TableHead>
                    <TableHead>Terlambat</TableHead>
                    <TableHead>Izin</TableHead>
                    <TableHead>Sakit</TableHead>
                    <TableHead>Cuti</TableHead>
                    <TableHead>Dinas luar</TableHead>
                    <TableHead>Tanpa catatan</TableHead>
                  </TableRow>
                </TableHeader>
                <TableBody>
                  {summary.length === 0 ? (
                    <TableRow>
                      <TableCell
                        colSpan={9}
                        className="py-6 text-center text-sm text-muted-foreground"
                      >
                        Belum ada data pada rentang ini.
                      </TableCell>
                    </TableRow>
                  ) : (
                    summary.map((row) => (
                      <TableRow key={row.employee_id}>
                        <TableCell className="font-medium">
                          {row.employee?.person?.full_name ??
                            `#${row.employee_id}`}
                        </TableCell>
                        <TableCell>{row.work_days}</TableCell>
                        <TableCell>{row.hadir}</TableCell>
                        <TableCell>{row.terlambat}</TableCell>
                        <TableCell>{row.izin}</TableCell>
                        <TableCell>{row.sakit}</TableCell>
                        <TableCell>{row.cuti}</TableCell>
                        <TableCell>{row.dinas_luar}</TableCell>
                        <TableCell>{row.tanpa_catatan}</TableCell>
                      </TableRow>
                    ))
                  )}
                </TableBody>
              </Table>
            </div>
          )}
        </CardContent>
      </Card>
      {daily !== null && (
        <Card className="min-w-0 rounded-2xl border border-border/70 py-0 ring-0">
          <CardContent className="p-4 sm:p-5">
            <h2 className="mb-3 text-base font-semibold">Rincian harian</h2>
            <div className="min-w-0 overflow-x-auto">
              <Table>
                <TableHeader>
                  <TableRow>
                    <TableHead>Tanggal</TableHead>
                    <TableHead>Status</TableHead>
                    <TableHead>Masuk</TableHead>
                    <TableHead>Pulang</TableHead>
                    <TableHead>Terlambat</TableHead>
                  </TableRow>
                </TableHeader>
                <TableBody>
                  {daily.map((row) => (
                    <TableRow key={row.work_date}>
                      <TableCell className="whitespace-nowrap font-medium">
                        {dateOnly(row.work_date)}
                      </TableCell>
                      <TableCell>
                        {row.is_work_required
                          ? (row.status ?? "Belum dicatat")
                          : "Libur"}
                      </TableCell>
                      <TableCell className="whitespace-nowrap text-xs">
                        {row.check_in_at ? wibTime(row.check_in_at) : "—"}
                      </TableCell>
                      <TableCell className="whitespace-nowrap text-xs">
                        {row.check_out_at ? wibTime(row.check_out_at) : "—"}
                      </TableCell>
                      <TableCell className="whitespace-nowrap text-xs">
                        {row.late_minutes ? `${row.late_minutes} mnt` : "—"}
                      </TableCell>
                    </TableRow>
                  ))}
                </TableBody>
              </Table>
            </div>
          </CardContent>
        </Card>
      )}
    </div>
  );
}
