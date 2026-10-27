"use client";

import { Plus } from "lucide-react";
import { useCallback, useEffect, useState } from "react";
import {
  AcademicFormDialog,
  AcademicHeading,
  AcademicList,
  FilterSelect,
} from "@/components/academic/academic-shared";
import { Badge } from "@/components/ui/badge";
import { Button } from "@/components/ui/button";
import { Tabs, TabsContent, TabsList, TabsTrigger } from "@/components/ui/tabs";
import {
  type AcademicPage,
  type AcademicRecord,
  academicListPath,
  academicRequest,
  HOMEROOM_FIELDS,
  type Option,
  TEACHING_FIELDS,
} from "@/lib/academic";
import { masterRequest } from "@/lib/master-data";

type Assignment = AcademicRecord & {
  employee_id: number;
  class_period_id: number;
  semester_id: number | null;
  effective_from: string;
  effective_to: string | null;
  planned_weekly_jp?: string | number | null;
  is_active?: boolean;
  subject_id?: number;
  employee?: { person?: { full_name: string } | null } | null;
  subject?: { name: string } | null;
  classPeriod?: { display_name: string } | null;
  semester?: { name: string } | null;
};

const ACTIVE_FIELD = {
  name: "is_active",
  label: "Penugasan aktif",
  type: "boolean" as const,
};

export function AcademicAssignments({ access }: { access: string[] }) {
  const canManage =
    access.includes("academic.assignments.manage") ||
    access.includes("academic.manage");
  const [periods, setPeriods] = useState<
    (AcademicRecord & { display_name: string })[]
  >([]);
  const [semesters, setSemesters] = useState<
    (AcademicRecord & { name: string; academic_year_id: number })[]
  >([]);
  const [semesterFilter, setSemesterFilter] = useState("");
  const [employees, setEmployees] = useState<Option[]>([]);
  const [subjects, setSubjects] = useState<Option[]>([]);
  const [editingTeaching, setEditingTeaching] = useState<
    Assignment | "new" | null
  >(null);
  const [editingHomeroom, setEditingHomeroom] = useState<
    Assignment | "new" | null
  >(null);
  const [version, setVersion] = useState(0);
  const reload = useCallback(() => setVersion((value) => value + 1), []);

  useEffect(() => {
    let active = true;
    academicRequest<AcademicPage<AcademicRecord & { display_name: string }>>(
      academicListPath("class-periods", { per_page: 100 }),
    )
      .then((data) => active && setPeriods(data.data))
      .catch(() => {});
    academicRequest<AcademicPage<AcademicRecord>>("years?per_page=100")
      .then(async ({ data: yearRows }) => {
        const lists = await Promise.all(
          yearRows.map((year) =>
            academicRequest<
              AcademicPage<
                AcademicRecord & { name: string; academic_year_id: number }
              >
            >(`semesters?per_page=10&academic_year_id=${year.id}`).catch(
              () => null,
            ),
          ),
        );
        if (active)
          setSemesters(
            lists.filter(Boolean).flatMap((list) => list?.data ?? []),
          );
      })
      .catch(() => {});
    if (canManage) {
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
      masterRequest<AcademicPage<AcademicRecord>>("subjects?per_page=100")
        .then((data) => {
          if (active)
            setSubjects(
              data.data.map((row) => ({
                value: String(row.id),
                label: String(row.name ?? `#${row.id}`),
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
  const semesterOptions: Option[] = semesters.map((row) => ({
    value: String(row.id),
    label: String(row.name),
  }));
  const employeeLabel = (row: Assignment) =>
    row.employee?.person?.full_name ?? `#${row.employee_id}`;

  return (
    <div className="animate-enter space-y-6">
      <AcademicHeading
        title="Penugasan mengajar & wali kelas"
        description="Penugasan menentukan hak akses guru/wali kelas terhadap kelas; satu wali kelas aktif per rombel dan penonaktifan tidak menghapus riwayat."
      />
      <Tabs defaultValue="teaching">
        <TabsList className="mb-5 h-14! w-full justify-start gap-1 rounded-xl bg-secondary p-1 sm:w-auto">
          <TabsTrigger
            value="teaching"
            className="h-11! min-w-0 flex-1 px-3 text-xs sm:flex-none"
          >
            Penugasan mengajar
          </TabsTrigger>
          <TabsTrigger
            value="homeroom"
            className="h-11! min-w-0 flex-1 px-3 text-xs sm:flex-none"
          >
            Wali kelas
          </TabsTrigger>
        </TabsList>
        <TabsContent value="teaching" className="space-y-4">
          <div className="flex flex-wrap items-end justify-between gap-3">
            <FilterSelect
              label="Semester"
              value={semesterFilter}
              onChange={setSemesterFilter}
              options={semesterOptions}
              placeholder="Semua semester"
              className="w-full sm:w-64"
            />
            {canManage && (
              <Button
                className="h-11 gap-2 text-xs"
                disabled={
                  periodOptions.length === 0 ||
                  semesterOptions.length === 0 ||
                  employees.length === 0
                }
                onClick={() => setEditingTeaching("new")}
              >
                <Plus className="size-4" /> Tambah penugasan
              </Button>
            )}
          </div>
          <AcademicList<Assignment>
            reloadKey={`teaching-${semesterFilter}-${version}`}
            load={(page) =>
              academicRequest<AcademicPage<Assignment>>(
                academicListPath("teaching-assignments", {
                  page,
                  per_page: 25,
                  semester_id: semesterFilter,
                }),
              )
            }
            columns={[
              {
                label: "Guru",
                cell: (row) => (
                  <span className="text-sm font-medium">
                    {employeeLabel(row)}
                  </span>
                ),
              },
              {
                label: "Mapel",
                cell: (row) =>
                  row.subject?.name ??
                  (row.subject_id ? `#${row.subject_id}` : "—"),
              },
              {
                label: "Rombel",
                cell: (row) =>
                  row.classPeriod?.display_name ?? `#${row.class_period_id}`,
              },
              { label: "Semester", cell: (row) => row.semester?.name ?? "—" },
              {
                label: "JP/minggu",
                cell: (row) => `${row.planned_weekly_jp ?? "—"}`,
              },
              {
                label: "Masa",
                cell: (row) => (
                  <span className="text-xs">
                    {row.effective_from} → {row.effective_to ?? "berjalan"}
                  </span>
                ),
              },
              {
                label: "Status",
                cell: (row) =>
                  row.is_active === false ? (
                    <Badge
                      variant="outline"
                      className="text-xs text-muted-foreground"
                    >
                      Nonaktif
                    </Badge>
                  ) : (
                    <Badge
                      variant="secondary"
                      className="bg-primary/8 text-xs text-primary"
                    >
                      Aktif
                    </Badge>
                  ),
              },
            ]}
            rowTitle={(row) =>
              `${employeeLabel(row)} — ${row.subject?.name ?? ""}`
            }
            rowMeta={(row) =>
              `${row.classPeriod?.display_name ?? ""} · ${row.effective_from} → ${row.effective_to ?? "berjalan"}`
            }
            rowActions={
              canManage
                ? (row) => (
                    <Button
                      variant="outline"
                      className="h-11 text-xs"
                      onClick={() => setEditingTeaching(row)}
                    >
                      Ubah
                    </Button>
                  )
                : undefined
            }
            emptyTitle="Belum ada penugasan mengajar"
            emptyHint="Tambahkan penugasan guru agar jadwal dan realisasi dapat dibuat."
          />
        </TabsContent>
        <TabsContent value="homeroom" className="space-y-4">
          <div className="flex flex-wrap items-end justify-between gap-3">
            <p className="text-xs leading-5 text-muted-foreground">
              Menunjuk wali kelas baru otomatis menutup penugasan lama pada
              tanggal efektif.
            </p>
            {canManage && (
              <Button
                className="h-11 gap-2 text-xs"
                disabled={periodOptions.length === 0 || employees.length === 0}
                onClick={() => setEditingHomeroom("new")}
              >
                <Plus className="size-4" /> Tambah wali kelas
              </Button>
            )}
          </div>
          <AcademicList<Assignment>
            reloadKey={`homeroom-${version}`}
            load={(page) =>
              academicRequest<AcademicPage<Assignment>>(
                academicListPath("homeroom-assignments", {
                  page,
                  per_page: 25,
                }),
              )
            }
            columns={[
              {
                label: "Wali kelas",
                cell: (row) => (
                  <span className="text-sm font-medium">
                    {employeeLabel(row)}
                  </span>
                ),
              },
              {
                label: "Rombel",
                cell: (row) =>
                  row.classPeriod?.display_name ?? `#${row.class_period_id}`,
              },
              {
                label: "Masa",
                cell: (row) => (
                  <span className="text-xs">
                    {row.effective_from} → {row.effective_to ?? "berjalan"}
                  </span>
                ),
              },
            ]}
            rowTitle={(row) =>
              `${employeeLabel(row)} — ${row.classPeriod?.display_name ?? ""}`
            }
            rowMeta={(row) =>
              `${row.effective_from} → ${row.effective_to ?? "berjalan"}`
            }
            rowActions={
              canManage
                ? (row) => (
                    <Button
                      variant="outline"
                      className="h-11 text-xs"
                      onClick={() => setEditingHomeroom(row)}
                    >
                      Akhiri
                    </Button>
                  )
                : undefined
            }
            emptyTitle="Belum ada wali kelas"
            emptyHint="Tunjuk wali kelas untuk setiap rombongan belajar."
          />
        </TabsContent>
      </Tabs>
      {editingTeaching && (
        <AcademicFormDialog
          title={
            editingTeaching === "new"
              ? "Tambah penugasan mengajar"
              : "Ubah penugasan mengajar"
          }
          description="Semester dan rombel harus berada pada tahun ajaran yang sama; masa penugasan harus dalam rentang semester."
          fields={
            editingTeaching === "new"
              ? TEACHING_FIELDS(
                  employees,
                  subjects,
                  periodOptions,
                  semesterOptions,
                )
              : [
                  {
                    name: "planned_weekly_jp",
                    label: "JP per minggu (rencana)",
                    max: 5,
                  },
                  {
                    name: "effective_from",
                    label: "Berlaku mulai",
                    required: true,
                    max: 10,
                    type: "date" as const,
                  },
                  {
                    name: "effective_to",
                    label: "Berlaku sampai (opsional)",
                    max: 10,
                    type: "date" as const,
                  },
                  ACTIVE_FIELD,
                ]
          }
          current={editingTeaching === "new" ? undefined : editingTeaching}
          onClose={() => setEditingTeaching(null)}
          submit={async (body) => {
            const payload =
              editingTeaching === "new"
                ? {
                    ...body,
                    employee_id: Number(body.employee_id),
                    subject_id: Number(body.subject_id),
                    class_period_id: Number(body.class_period_id),
                    semester_id: Number(body.semester_id),
                  }
                : body;
            await academicRequest(
              editingTeaching === "new"
                ? "teaching-assignments"
                : `teaching-assignments/${editingTeaching.id}`,
              editingTeaching === "new" ? "POST" : "PUT",
              payload,
            );
            reload();
          }}
        />
      )}
      {editingHomeroom && (
        <AcademicFormDialog
          title={
            editingHomeroom === "new"
              ? "Tambah wali kelas"
              : "Akhiri penugasan wali kelas"
          }
          description={
            editingHomeroom === "new"
              ? "Penugasan wali kelas baru menutup penugasan sebelumnya pada tanggal efektif."
              : "Isi tanggal berakhir untuk mengakhiri penugasan ini; riwayat tetap tersimpan."
          }
          fields={
            editingHomeroom === "new"
              ? HOMEROOM_FIELDS(employees, periodOptions)
              : [
                  {
                    name: "effective_to",
                    label: "Berlaku sampai",
                    required: true,
                    max: 10,
                    type: "date" as const,
                  },
                ]
          }
          current={editingHomeroom === "new" ? undefined : editingHomeroom}
          onClose={() => setEditingHomeroom(null)}
          submit={async (body) => {
            const payload =
              editingHomeroom === "new"
                ? {
                    ...body,
                    employee_id: Number(body.employee_id),
                    class_period_id: Number(body.class_period_id),
                  }
                : body;
            await academicRequest(
              editingHomeroom === "new"
                ? "homeroom-assignments"
                : `homeroom-assignments/${editingHomeroom.id}`,
              editingHomeroom === "new" ? "POST" : "PUT",
              payload,
            );
            reload();
          }}
        />
      )}
    </div>
  );
}
