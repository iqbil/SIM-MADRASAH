"use client";

import { Plus } from "lucide-react";
import { useCallback, useEffect, useState } from "react";
import {
  AcademicFormDialog,
  AcademicHeading,
  AcademicList,
  DateFilter,
  FilterSelect,
} from "@/components/academic/academic-shared";
import { Badge } from "@/components/ui/badge";
import { Button } from "@/components/ui/button";
import type { AcademicPage, AcademicRecord, Option } from "@/lib/academic";
import {
  attendanceError,
  attendanceRequest,
  dateOnly,
  entryModeLabel,
  statusLabels,
  todayWib,
  wibTime,
} from "@/lib/attendance";
import { masterRequest } from "@/lib/master-data";

type RecordRow = AcademicRecord & {
  work_date: string;
  scheduled_start_at: string | null;
  scheduled_end_at: string | null;
  is_work_required: boolean;
  employee_id?: number;
  employee?: { person?: { full_name?: string } | null } | null;
  attendance?: {
    id: number;
    check_in_at: string | null;
    check_out_at: string | null;
    late_minutes: number | null;
    entry_mode: string;
    lock_version: number;
    attendanceStatus?: { code: string; name: string } | null;
  } | null;
};

const STATUS_OPTIONS = ["izin", "sakit", "cuti", "dinas_luar", "hadir"].map(
  (code) => ({ value: code, label: statusLabels[code] ?? code }),
);

export function AttendanceRecords({ canManage }: { canManage: boolean }) {
  const [date, setDate] = useState(() => todayWib());
  const [employee, setEmployee] = useState("");
  const [presence, setPresence] = useState("all");
  const [employees, setEmployees] = useState<Option[]>([]);
  const [recording, setRecording] = useState<RecordRow | "new" | null>(null);
  const [correcting, setCorrecting] = useState<RecordRow | null>(null);
  const [version, setVersion] = useState(0);
  const reload = useCallback(() => setVersion((value) => value + 1), []);

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

  return (
    <div className="animate-enter space-y-6">
      <AcademicHeading
        category="Absensi pegawai"
        title="Absensi & izin pegawai"
        description="Petugas mencatat izin, sakit, cuti, dan dinas luar tanpa bukti foto, pencatatan ini juga menjadi fallback administratif bila kamera/GPS pegawai gagal."
      />
      <div className="flex flex-wrap items-end justify-between gap-3">
        <div className="flex flex-wrap items-end gap-3">
          <DateFilter label="Tanggal" value={date} onChange={setDate} />
          <FilterSelect
            label="Pegawai"
            value={employee}
            onChange={setEmployee}
            options={employees}
            placeholder="Semua pegawai"
            className="w-full sm:w-56"
          />
          <FilterSelect
            label="Kehadiran"
            value={presence}
            onChange={setPresence}
            options={[
              { value: "recorded", label: "Sudah tercatat" },
              { value: "missing", label: "Belum tercatat" },
            ]}
            placeholder="Semua"
            className="w-full sm:w-48"
          />
        </div>
        {canManage && employees.length > 0 && (
          <Button
            className="h-11 gap-2 text-xs"
            onClick={() => setRecording("new")}
          >
            <Plus className="size-4" /> Catat status pegawai
          </Button>
        )}
      </div>
      <AcademicList<RecordRow>
        reloadKey={`${date}-${employee}-${presence}-${version}`}
        load={async (page) =>
          attendanceRequest<AcademicPage<RecordRow>>(
            `records?page=${page}&per_page=25&date_from=${date}&date_to=${date}&presence=${presence}${
              employee ? `&employee_id=${employee}` : ""
            }`,
          )
        }
        columns={[
          {
            label: "Pegawai",
            cell: (row) => (
              <span className="text-sm font-medium">
                {row.employee?.person?.full_name ?? "—"}
              </span>
            ),
          },
          {
            label: "Tanggal",
            cell: (row) => dateOnly(row.work_date),
          },
          {
            label: "Jadwal",
            cell: (row) => (
              <span className="text-xs">
                {row.scheduled_start_at
                  ? `${wibTime(row.scheduled_start_at)}–${wibTime(row.scheduled_end_at ?? row.scheduled_start_at)}`
                  : "—"}
              </span>
            ),
          },
          {
            label: "Status",
            cell: (row) =>
              !row.is_work_required ? (
                <Badge variant="outline" className="text-xs">
                  Libur
                </Badge>
              ) : row.attendance?.attendanceStatus ? (
                <Badge
                  variant="secondary"
                  className="bg-primary/8 text-xs text-primary"
                >
                  {statusLabels[row.attendance.attendanceStatus.code] ??
                    row.attendance.attendanceStatus.name}
                </Badge>
              ) : (
                <Badge variant="outline" className="text-xs">
                  Belum dicatat
                </Badge>
              ),
          },
          {
            label: "Masuk → pulang",
            cell: (row) => (
              <span className="text-xs">
                {row.attendance?.check_in_at
                  ? `${wibTime(row.attendance.check_in_at)} → ${
                      row.attendance.check_out_at
                        ? wibTime(row.attendance.check_out_at)
                        : "belum pulang"
                    }`
                  : "—"}
              </span>
            ),
          },
          {
            label: "Terlambat",
            cell: (row) =>
              row.attendance?.late_minutes
                ? `${row.attendance.late_minutes} mnt`
                : "—",
          },
          {
            label: "Sumber",
            cell: (row) =>
              row.attendance ? entryModeLabel(row.attendance.entry_mode) : "—",
          },
        ]}
        rowTitle={(row) =>
          row.employee?.person?.full_name ?? `Pegawai #${row.employee_id ?? ""}`
        }
        rowMeta={(row) =>
          `${dateOnly(row.work_date)} · ${
            row.attendance?.attendanceStatus?.name ?? "belum dicatat"
          }`
        }
        rowActions={
          canManage
            ? (row) =>
                row.is_work_required ? (
                  row.attendance ? (
                    <Button
                      variant="outline"
                      className="h-11 text-xs"
                      onClick={() => setCorrecting(row)}
                    >
                      Ajukan koreksi
                    </Button>
                  ) : (
                    <Button
                      variant="outline"
                      className="h-11 text-xs"
                      onClick={() => setRecording(row)}
                    >
                      Catat status
                    </Button>
                  )
                ) : null
            : undefined
        }
        emptyTitle="Tidak ada hari kerja pada filter ini"
        emptyHint="Ubah tanggal/filter, atau buat hari kerja di menu Pola kerja & kebijakan."
      />

      {recording && (
        <AcademicFormDialog
          title="Catat status kehadiran pegawai"
          description="Pencatatan petugas tanpa bukti foto/GPS: izin, sakit, cuti, dinas luar, atau fallback administratif hadir."
          fields={[
            {
              name: "employee_id",
              label: "Pegawai",
              required: true,
              type: "select",
              options: employees,
              immutable: recording !== "new",
            },
            {
              name: "work_date",
              label: "Tanggal",
              required: true,
              max: 10,
              type: "date",
              immutable: recording !== "new",
            },
            {
              name: "status_code",
              label: "Status kehadiran",
              required: true,
              type: "select",
              options: STATUS_OPTIONS,
            },
            { name: "notes", label: "Catatan", type: "textarea", max: 1000 },
          ]}
          current={
            {
              work_date:
                recording === "new" ? date : recording.work_date.slice(0, 10),
            } as unknown as AcademicRecord
          }
          onClose={() => setRecording(null)}
          submit={async (body) => {
            await attendanceRequest("records", "POST", {
              employee_id: Number(body.employee_id),
              work_date: body.work_date,
              status_code: body.status_code,
              notes: body.notes,
              reason: body.reason,
            });
            reload();
          }}
        />
      )}

      {correcting?.attendance && (
        <AcademicFormDialog
          title="Ajukan koreksi kehadiran"
          description="Perubahan diajukan ke penyetuju koreksi (petugas berbeda). Bukti foto/GPS asli tidak diubah."
          fields={[
            {
              name: "status_code",
              label: "Status baru",
              required: true,
              type: "select",
              options: STATUS_OPTIONS,
            },
            {
              name: "notes",
              label: "Catatan baru",
              type: "textarea",
              max: 1000,
            },
          ]}
          onClose={() => setCorrecting(null)}
          submit={async (body) => {
            try {
              await attendanceRequest("corrections", "POST", {
                employee_attendance_id: correcting.attendance?.id,
                after_values: {
                  status_code: body.status_code,
                  ...(body.notes ? { notes: body.notes } : {}),
                },
                expected_lock_version: correcting.attendance?.lock_version,
                reason: body.reason,
              });
              reload();
            } catch (error) {
              throw error instanceof Error
                ? error
                : new Error(attendanceError(error));
            }
          }}
        />
      )}
    </div>
  );
}
