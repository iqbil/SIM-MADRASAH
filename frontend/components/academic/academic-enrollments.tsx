"use client";

import { ArrowRightLeft, Plus } from "lucide-react";
import { useCallback, useEffect, useState } from "react";
import {
  AcademicFormDialog,
  AcademicHeading,
  AcademicList,
  DateFilter,
  FilterSelect,
} from "@/components/academic/academic-shared";
import { Button } from "@/components/ui/button";
import {
  type AcademicPage,
  type AcademicRecord,
  academicListPath,
  academicRequest,
  ENROLLMENT_FIELDS,
  type Option,
} from "@/lib/academic";
import { masterRequest } from "@/lib/master-data";

type Enrollment = AcademicRecord & {
  student_id: number;
  class_period_id: number;
  effective_from: string;
  effective_to: string | null;
  entry_reason: string;
  exit_reason: string | null;
  student?: { id: number; person?: { full_name: string } | null } | null;
  classPeriod?: { id: number; display_name: string } | null;
};
type ClassPeriod = AcademicRecord & { display_name: string };

const ENTRY_LABEL: Record<string, string> = {
  admission: "Penerimaan",
  transfer: "Pindah masuk",
  correction: "Koreksi",
  promotion: "Naik kelas",
  graduation: "Lulus",
  withdrawal: "Keluar",
};

export function AcademicEnrollments({ access }: { access: string[] }) {
  const canManage =
    access.includes("academic.enrollments.manage") ||
    access.includes("academic.manage");
  const [periods, setPeriods] = useState<ClassPeriod[]>([]);
  const [periodId, setPeriodId] = useState("");
  const [date, setDate] = useState(() => new Date().toISOString().slice(0, 10));
  const [creating, setCreating] = useState(false);
  const [moving, setMoving] = useState<Enrollment | null>(null);
  const [closing, setClosing] = useState<Enrollment | null>(null);
  const [students, setStudents] = useState<Option[]>([]);
  const [version, setVersion] = useState(0);
  const reload = useCallback(() => setVersion((value) => value + 1), []);

  useEffect(() => {
    let active = true;
    academicRequest<AcademicPage<ClassPeriod>>(
      academicListPath("class-periods", { per_page: 100 }),
    )
      .then((data) => active && setPeriods(data.data))
      .catch(() => {});
    if (canManage) {
      masterRequest<AcademicPage<AcademicRecord>>(
        "students?per_page=100&is_active=1",
      )
        .then((data) => {
          if (active)
            setStudents(
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
    }
    return () => {
      active = false;
    };
  }, [canManage]);

  const periodOptions: Option[] = periods.map((row) => ({
    value: String(row.id),
    label: row.display_name,
  }));

  return (
    <div className="animate-enter space-y-6">
      <AcademicHeading
        title="Keanggotaan & riwayat siswa"
        description="Keanggotaan kelas bertanggal efektif; perpindahan menutup keanggotaan lama dan membuka yang baru sehingga riwayat tidak tertimpa (FR-03-02)."
        action={
          canManage ? (
            <Button
              className="h-11 gap-2 text-xs"
              disabled={periodOptions.length === 0 || students.length === 0}
              onClick={() => setCreating(true)}
            >
              <Plus className="size-4" /> Tambah keanggotaan
            </Button>
          ) : undefined
        }
      />
      <div className="flex flex-wrap items-end gap-3">
        <FilterSelect
          label="Rombongan belajar"
          value={periodId}
          onChange={setPeriodId}
          options={periodOptions}
          placeholder="Semua rombel"
          className="w-full sm:w-64"
        />
        <DateFilter
          label="Posisi pada tanggal"
          value={date}
          onChange={setDate}
        />
      </div>
      <AcademicList<Enrollment>
        reloadKey={`enrollments-${periodId}-${date}-${version}`}
        load={(page) =>
          academicRequest<AcademicPage<Enrollment>>(
            academicListPath("enrollments", {
              page,
              per_page: 25,
              class_period_id: periodId,
              date,
            }),
          )
        }
        columns={[
          {
            label: "Siswa",
            cell: (row) => (
              <span className="text-[13px] font-medium">
                {row.student?.person?.full_name ?? `#${row.student_id}`}
              </span>
            ),
          },
          {
            label: "Rombel",
            cell: (row) =>
              row.classPeriod?.display_name ?? `#${row.class_period_id}`,
          },
          {
            label: "Keanggotaan",
            cell: (row) => (
              <span className="text-xs">
                {row.effective_from} → {row.effective_to ?? "berjalan"}
              </span>
            ),
          },
          {
            label: "Masuk/keluar",
            cell: (row) => (
              <span className="text-xs">
                {ENTRY_LABEL[row.entry_reason] ?? row.entry_reason}
                {row.exit_reason
                  ? ` · keluar: ${ENTRY_LABEL[row.exit_reason] ?? row.exit_reason}`
                  : ""}
              </span>
            ),
          },
        ]}
        rowTitle={(row) =>
          row.student?.person?.full_name ?? `#${row.student_id}`
        }
        rowMeta={(row) =>
          `${row.classPeriod?.display_name ?? ""} · ${row.effective_from} → ${row.effective_to ?? "berjalan"}`
        }
        rowActions={
          canManage
            ? (row) => (
                <div className="flex flex-wrap justify-end gap-2">
                  {!row.effective_to && (
                    <>
                      <Button
                        variant="outline"
                        className="h-11 gap-2 text-xs"
                        onClick={() => setMoving(row)}
                      >
                        <ArrowRightLeft className="size-3.5" /> Pindah kelas
                      </Button>
                      <Button
                        variant="outline"
                        className="h-11 text-xs"
                        onClick={() => setClosing(row)}
                      >
                        Akhiri
                      </Button>
                    </>
                  )}
                </div>
              )
            : undefined
        }
        emptyTitle="Tidak ada keanggotaan pada filter ini"
        emptyHint="Sesuaikan rombel atau tanggal, atau tambahkan keanggotaan baru."
      />
      {creating && (
        <AcademicFormDialog
          title="Tambah keanggotaan"
          description="Siswa hanya dapat memiliki satu keanggotaan aktif; gunakan pindah kelas untuk memindahkan siswa yang sudah terdaftar."
          fields={ENROLLMENT_FIELDS(students, periodOptions)}
          onClose={() => setCreating(false)}
          submit={async (body) => {
            await academicRequest("enrollments", "POST", {
              ...body,
              student_id: Number(body.student_id),
              class_period_id: Number(body.class_period_id),
            });
            reload();
          }}
        />
      )}
      {moving && (
        <AcademicFormDialog
          title="Pindah kelas"
          description={`Keanggotaan lama akan ditutup pada tanggal efektif dan keanggotaan baru dibuka pada rombel tujuan (${moving.student?.person?.full_name ?? `#${moving.student_id}`}).`}
          fields={[
            {
              name: "to_class_period_id",
              label: "Rombel tujuan",
              required: true,
              type: "select",
              options: periodOptions.filter(
                (option) => option.value !== String(moving.class_period_id),
              ),
            },
            {
              name: "effective_date",
              label: "Tanggal efektif perpindahan",
              required: true,
              max: 10,
              type: "date",
            },
            {
              name: "entry_reason",
              label: "Jenis perpindahan",
              required: true,
              type: "select",
              options: [
                { value: "promotion", label: "Kenaikan kelas" },
                { value: "transfer", label: "Perpindahan" },
                { value: "correction", label: "Koreksi data" },
              ],
            },
          ]}
          onClose={() => setMoving(null)}
          submit={async (body) => {
            await academicRequest("enrollments/move", "POST", {
              student_id: moving.student_id,
              to_class_period_id: Number(body.to_class_period_id),
              effective_date: body.effective_date,
              entry_reason: body.entry_reason,
              reason: body.reason,
            });
            reload();
          }}
        />
      )}
      {closing && (
        <AcademicFormDialog
          title="Akhiri keanggotaan"
          description="Gunakan untuk keluar/lulus manual; kenaikan dan kelulusan massal sebaiknya memakai menu Kenaikan & mutasi siswa."
          fields={[
            {
              name: "effective_to",
              label: "Tanggal berakhir (eksklusif)",
              required: true,
              max: 10,
              type: "date",
            },
            {
              name: "exit_reason",
              label: "Alasan keluar",
              required: true,
              type: "select",
              options: [
                { value: "graduation", label: "Lulus" },
                { value: "transfer", label: "Pindah" },
                { value: "withdrawal", label: "Keluar" },
                { value: "correction", label: "Koreksi" },
              ],
            },
          ]}
          current={closing}
          onClose={() => setClosing(null)}
          submit={async (body) => {
            await academicRequest(`enrollments/${closing.id}`, "PUT", body);
            reload();
          }}
        />
      )}
    </div>
  );
}
