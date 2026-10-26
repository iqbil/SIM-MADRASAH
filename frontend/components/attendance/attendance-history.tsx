"use client";

import { useCallback, useEffect, useState } from "react";
import {
  AcademicHeading,
  DateFilter,
} from "@/components/academic/academic-shared";
import { Badge } from "@/components/ui/badge";
import { Button } from "@/components/ui/button";
import { Card, CardContent } from "@/components/ui/card";
import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogHeader,
  DialogTitle,
} from "@/components/ui/dialog";
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
  entryModeLabel,
  statusLabels,
  wibMonthRange,
  wibTime,
} from "@/lib/attendance";

type HistoryRow = {
  id: number;
  work_date: string;
  scheduled_start_at: string | null;
  scheduled_end_at: string | null;
  is_work_required: boolean;
  attendance?: {
    id: number;
    check_in_at: string | null;
    check_out_at: string | null;
    late_minutes: number | null;
    entry_mode: string;
    attendance_status?: { code: string; name: string } | null;
    captures?: { id: number; event_type: string; recorded_at: string }[];
  } | null;
  calendar_event_id?: number | null;
};

export function AttendanceHistory() {
  const range = wibMonthRange();
  const [from, setFrom] = useState(range.from);
  const [to, setTo] = useState(range.to);
  const [rows, setRows] = useState<HistoryRow[]>([]);
  const [failure, setFailure] = useState("");
  const [loading, setLoading] = useState(true);
  const [photo, setPhoto] = useState<{ url: string; event: string } | null>(
    null,
  );

  const load = useCallback(async () => {
    setLoading(true);
    try {
      const result = await attendanceRequest<{
        data: { rows: { data: HistoryRow[] } };
      }>(`me?date_from=${from}&date_to=${to}&per_page=62`);
      setRows(result.data.rows.data);
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

  async function showEvidence(captureId: number, event: string) {
    try {
      const response = await fetch(
        `/api/attendance/captures/${captureId}/photo`,
      );
      if (!response.ok) throw new Error("bukti");
      const blob = await response.blob();
      setPhoto({ url: URL.createObjectURL(blob), event });
    } catch {
      setFailure("Bukti foto tidak dapat dimuat.");
    }
  }

  return (
    <div className="animate-enter space-y-6">
      <AcademicHeading
        title="Riwayat absensi saya"
        description="Kehadiran pribadi per hari kerja beserta bukti foto dan lokasi; bukti hanya dapat dilihat oleh Anda dan petugas absensi."
      />
      {failure && <p className="text-sm text-destructive">{failure}</p>}
      <div className="flex flex-wrap items-end gap-3">
        <DateFilter label="Dari tanggal" value={from} onChange={setFrom} />
        <DateFilter label="Sampai" value={to} onChange={setTo} />
      </div>
      <Card className="min-w-0 rounded-2xl border border-border/70 py-0 ring-0">
        <CardContent className="p-4 sm:p-5">
          {loading ? (
            <p className="p-4 text-sm text-muted-foreground">Memuat…</p>
          ) : rows.length === 0 ? (
            <p className="rounded-xl border border-dashed p-5 text-sm leading-6 text-muted-foreground">
              Belum ada hari kerja pada rentang ini.
            </p>
          ) : (
            <div className="min-w-0 overflow-x-auto">
              <Table>
                <TableHeader>
                  <TableRow>
                    <TableHead>Tanggal</TableHead>
                    <TableHead>Jadwal</TableHead>
                    <TableHead>Status</TableHead>
                    <TableHead>Masuk</TableHead>
                    <TableHead>Pulang</TableHead>
                    <TableHead>Terlambat</TableHead>
                    <TableHead>Sumber</TableHead>
                    <TableHead>Bukti</TableHead>
                  </TableRow>
                </TableHeader>
                <TableBody>
                  {rows.map((row) => (
                    <TableRow key={row.id}>
                      <TableCell className="whitespace-nowrap font-medium">
                        {dateOnly(row.work_date)}
                      </TableCell>
                      <TableCell className="whitespace-nowrap text-xs">
                        {row.scheduled_start_at
                          ? `${wibTime(row.scheduled_start_at)}–${wibTime(row.scheduled_end_at ?? row.scheduled_start_at)}`
                          : "—"}
                      </TableCell>
                      <TableCell>
                        {row.is_work_required ? (
                          row.attendance?.attendance_status ? (
                            <Badge
                              variant="secondary"
                              className="bg-primary/8 text-[10px] text-primary"
                            >
                              {statusLabels[
                                row.attendance.attendance_status.code
                              ] ?? row.attendance.attendance_status.name}
                            </Badge>
                          ) : (
                            <Badge variant="outline" className="text-[10px]">
                              Belum dicatat
                            </Badge>
                          )
                        ) : (
                          <Badge variant="outline" className="text-[10px]">
                            Libur
                          </Badge>
                        )}
                      </TableCell>
                      <TableCell className="whitespace-nowrap text-xs">
                        {row.attendance?.check_in_at
                          ? wibTime(row.attendance.check_in_at)
                          : "—"}
                      </TableCell>
                      <TableCell className="whitespace-nowrap text-xs">
                        {row.attendance?.check_out_at
                          ? wibTime(row.attendance.check_out_at)
                          : "—"}
                      </TableCell>
                      <TableCell className="whitespace-nowrap text-xs">
                        {row.attendance?.late_minutes
                          ? `${row.attendance.late_minutes} mnt`
                          : "—"}
                      </TableCell>
                      <TableCell className="whitespace-nowrap text-xs">
                        {row.attendance
                          ? entryModeLabel(row.attendance.entry_mode)
                          : "—"}
                      </TableCell>
                      <TableCell>
                        <div className="flex gap-2">
                          {(row.attendance?.captures ?? []).map((capture) => (
                            <Button
                              key={capture.id}
                              variant="outline"
                              className="h-11 px-3 text-xs"
                              onClick={() =>
                                void showEvidence(
                                  capture.id,
                                  capture.event_type === "check_in"
                                    ? "Bukti absen masuk"
                                    : "Bukti absen pulang",
                                )
                              }
                            >
                              {capture.event_type === "check_in"
                                ? "Masuk"
                                : "Pulang"}
                            </Button>
                          ))}
                        </div>
                      </TableCell>
                    </TableRow>
                  ))}
                </TableBody>
              </Table>
            </div>
          )}
        </CardContent>
      </Card>
      <Dialog
        open={photo !== null}
        onOpenChange={(open) => {
          if (!open && photo) {
            URL.revokeObjectURL(photo.url);
            setPhoto(null);
          }
        }}
      >
        <DialogContent>
          <DialogHeader>
            <DialogTitle>{photo?.event ?? "Bukti"}</DialogTitle>
            <DialogDescription>
              Bukti foto tersimpan privat; hanya Anda dan petugas absensi yang
              dapat melihatnya.
            </DialogDescription>
          </DialogHeader>
          {photo && (
            // biome-ignore lint/performance/noImgElement: blob bukti foto dinamis dari server privat
            <img
              src={photo.url}
              alt={photo.event}
              className="max-h-[60dvh] w-full rounded-xl object-contain"
            />
          )}
        </DialogContent>
      </Dialog>
    </div>
  );
}
