"use client";

import { Plus } from "lucide-react";
import { useCallback, useEffect, useState } from "react";
import {
  AcademicFormDialog,
  AcademicHeading,
  AcademicList,
  FilterSelect,
  StatusBadge,
} from "@/components/academic/academic-shared";
import { Button } from "@/components/ui/button";
import {
  type AcademicPage,
  type AcademicRecord,
  academicListPath,
  academicRequest,
  CLASS_FIELDS,
  CLASS_PERIOD_FIELDS,
  type Option,
} from "@/lib/academic";

type SchoolClass = AcademicRecord & {
  code: string;
  name: string;
  grade: number;
  is_active: boolean;
  periods_count?: number;
};
type ClassPeriod = AcademicRecord & {
  display_name: string;
  grade_snapshot: number;
  capacity: number | null;
  status: string;
  academic_year_id: number;
  class_id: number;
  enrollments_count?: number;
  schoolClass?: { code: string; name: string } | null;
};

const STATUS_FIELD = {
  name: "status",
  label: "Status",
  required: true,
  type: "select" as const,
  options: [
    { value: "planned", label: "Direncanakan" },
    { value: "open", label: "Terbuka" },
    { value: "closed", label: "Ditutup" },
  ],
};

export function AcademicClasses({ access }: { access: string[] }) {
  const canManage =
    access.includes("academic.classes.manage") ||
    access.includes("academic.manage");
  const [classes, setClasses] = useState<SchoolClass[]>([]);
  const [years, setYears] = useState<AcademicRecord[]>([]);
  const [yearId, setYearId] = useState("");
  const [editingClass, setEditingClass] = useState<SchoolClass | "new" | null>(
    null,
  );
  const [editingPeriod, setEditingPeriod] = useState<
    ClassPeriod | "new" | null
  >(null);
  const [version, setVersion] = useState(0);
  const reload = useCallback(() => setVersion((value) => value + 1), []);

  // biome-ignore lint/correctness/useExhaustiveDependencies: referensi dimuat ulang lewat version
  useEffect(() => {
    let active = true;
    academicRequest<AcademicPage<SchoolClass>>(
      academicListPath("classes", { per_page: 100 }),
    )
      .then((data) => active && setClasses(data.data))
      .catch(() => {});
    academicRequest<AcademicPage<AcademicRecord>>(
      academicListPath("years", { per_page: 100 }),
    )
      .then((data) => {
        if (!active) return;
        setYears(data.data);
        setYearId((current) => current || String(data.data[0]?.id ?? ""));
      })
      .catch(() => {});
    return () => {
      active = false;
    };
  }, [version]);

  const yearOptions: Option[] = years.map((year) => ({
    value: String(year.id),
    label: String(year.name),
  }));
  const classOptions: Option[] = classes.map((row) => ({
    value: String(row.id),
    label: `${row.name} (tingkat ${row.grade})`,
  }));

  return (
    <div className="animate-enter space-y-6">
      <AcademicHeading
        title="Rombongan belajar"
        description="Identitas rombel bersifat tetap, setiap tahun ajaran membuat periode kelas dengan snapshot tingkat agar laporan historis tidak berubah."
        action={
          canManage ? (
            <Button
              className="h-11 gap-2 text-xs"
              onClick={() => setEditingClass("new")}
            >
              <Plus className="size-4" /> Tambah rombel
            </Button>
          ) : undefined
        }
      />
      <AcademicList<SchoolClass>
        reloadKey={`classes-${version}`}
        load={(page) =>
          academicRequest<AcademicPage<SchoolClass>>(
            academicListPath("classes", { page, per_page: 25 }),
          )
        }
        columns={[
          {
            label: "Rombel",
            cell: (row) => (
              <div>
                <p className="text-sm font-medium">{row.name}</p>
                <p className="mt-1 text-xs text-muted-foreground">{row.code}</p>
              </div>
            ),
          },
          { label: "Tingkat", cell: (row) => `Kelas ${row.grade}` },
          { label: "Periode", cell: (row) => `${row.periods_count ?? 0}` },
          {
            label: "Status",
            cell: (row) => (
              <StatusBadge status={row.is_active ? "open" : "closed"} />
            ),
          },
        ]}
        rowTitle={(row) => row.name}
        rowMeta={(row) => `${row.code} · tingkat ${row.grade}`}
        rowActions={
          canManage
            ? (row) => (
                <Button
                  variant="outline"
                  className="h-11 text-xs"
                  onClick={() => setEditingClass(row)}
                >
                  Ubah
                </Button>
              )
            : undefined
        }
        emptyTitle="Belum ada rombongan belajar"
        emptyHint="Tambahkan identitas rombel terlebih dahulu, lalu buka periodenya per tahun ajaran."
      />
      <section className="space-y-4">
        <div className="flex flex-wrap items-end justify-between gap-3">
          <div>
            <h2 className="text-sm font-semibold">Periode kelas per tahun</h2>
            <p className="mt-1 text-xs text-muted-foreground">
              Keanggotaan siswa dan penugasan melekat pada periode kelas, bukan
              identitas rombel.
            </p>
          </div>
          <div className="flex flex-wrap items-end gap-2">
            <FilterSelect
              label="Tahun ajaran"
              value={yearId}
              onChange={setYearId}
              options={yearOptions}
              placeholder="Pilih tahun"
              className="w-full sm:w-56"
            />
            {canManage && (
              <Button
                variant="outline"
                className="h-11 gap-2 text-xs"
                disabled={!yearId || classes.length === 0}
                onClick={() => setEditingPeriod("new")}
              >
                <Plus className="size-4" /> Tambah periode
              </Button>
            )}
          </div>
        </div>
        <AcademicList<ClassPeriod>
          reloadKey={`periods-${yearId}-${version}`}
          load={(page) =>
            academicRequest<AcademicPage<ClassPeriod>>(
              academicListPath("class-periods", {
                page,
                per_page: 25,
                academic_year_id: yearId,
              }),
            )
          }
          columns={[
            {
              label: "Periode kelas",
              cell: (row) => (
                <span className="text-sm font-medium">{row.display_name}</span>
              ),
            },
            {
              label: "Rombel",
              cell: (row) =>
                row.schoolClass
                  ? `${row.schoolClass.name}`
                  : `#${row.class_id}`,
            },
            { label: "Tingkat", cell: (row) => `Kelas ${row.grade_snapshot}` },
            {
              label: "Anggota",
              cell: (row) => `${row.enrollments_count ?? 0}`,
            },
            {
              label: "Status",
              cell: (row) => <StatusBadge status={row.status} />,
            },
          ]}
          rowTitle={(row) => row.display_name}
          rowMeta={(row) =>
            `${row.enrollments_count ?? 0} anggota · tingkat ${row.grade_snapshot}`
          }
          rowActions={
            canManage
              ? (row) => (
                  <Button
                    variant="outline"
                    className="h-11 text-xs"
                    onClick={() => setEditingPeriod(row)}
                  >
                    Ubah
                  </Button>
                )
              : undefined
          }
          emptyTitle="Belum ada periode kelas"
          emptyHint="Buat periode kelas untuk tahun ajaran ini agar siswa dapat menjadi anggota."
        />
      </section>
      {editingClass && (
        <AcademicFormDialog
          title={editingClass === "new" ? "Tambah rombel" : "Ubah rombel"}
          description="Kode rombel bersifat tetap; tingkat dapat diperbaiki karena riwayat memakai snapshot per tahun."
          fields={
            editingClass === "new"
              ? CLASS_FIELDS(true)
              : [
                  ...CLASS_FIELDS(false),
                  {
                    name: "is_active",
                    label: "Rombel aktif",
                    type: "boolean" as const,
                  },
                ]
          }
          current={editingClass === "new" ? undefined : editingClass}
          onClose={() => setEditingClass(null)}
          submit={async (body) => {
            await academicRequest(
              editingClass === "new" ? "classes" : `classes/${editingClass.id}`,
              editingClass === "new" ? "POST" : "PUT",
              body,
            );
            reload();
          }}
        />
      )}
      {editingPeriod && (
        <AcademicFormDialog
          title={
            editingPeriod === "new"
              ? "Tambah periode kelas"
              : "Ubah periode kelas"
          }
          description="Tingkat disalin dari rombel saat pembuatan dan tidak dapat diubah setelahnya."
          fields={
            editingPeriod === "new"
              ? [
                  ...CLASS_PERIOD_FIELDS(true, yearOptions).map((field) =>
                    field.name === "class_id"
                      ? { ...field, options: classOptions }
                      : field,
                  ),
                ]
              : [...CLASS_PERIOD_FIELDS(false, yearOptions), STATUS_FIELD]
          }
          current={editingPeriod === "new" ? undefined : editingPeriod}
          onClose={() => setEditingPeriod(null)}
          submit={async (body) => {
            if (editingPeriod === "new") {
              await academicRequest("class-periods", "POST", {
                ...body,
                academic_year_id: Number(body.academic_year_id ?? yearId),
              });
            } else {
              await academicRequest(
                `class-periods/${editingPeriod.id}`,
                "PUT",
                body,
              );
            }
            reload();
          }}
        />
      )}
    </div>
  );
}
