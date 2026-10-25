"use client";

import { CalendarCheck, Plus } from "lucide-react";
import { useCallback, useEffect, useState } from "react";
import { toast } from "sonner";
import {
  AcademicFormDialog,
  AcademicHeading,
  AcademicList,
  FilterSelect,
  StatusBadge,
} from "@/components/academic/academic-shared";
import { FormDialog } from "@/components/layout/form-dialog";
import { ReasonField } from "@/components/layout/reason-field";
import { Button } from "@/components/ui/button";
import { Card, CardContent } from "@/components/ui/card";
import {
  type AcademicContext,
  type AcademicPage,
  type AcademicRecord,
  academicError,
  academicListPath,
  academicRequest,
  type Option,
  PERIOD_FIELDS,
  SEMESTER_FIELDS,
} from "@/lib/academic";

type Year = AcademicRecord & {
  code: string;
  name: string;
  start_date: string;
  end_date: string;
  status: string;
  semesters_count?: number;
};
type Semester = AcademicRecord & {
  number: number;
  name: string;
  start_date: string;
  end_date: string;
  status: string;
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

export function AcademicPeriods({ access }: { access: string[] }) {
  const canManage =
    access.includes("academic.years.manage") ||
    access.includes("academic.manage");
  const [context, setContext] = useState<AcademicContext | null>(null);
  const [years, setYears] = useState<Year[]>([]);
  const [yearId, setYearId] = useState("");
  const [editingYear, setEditingYear] = useState<Year | "new" | null>(null);
  const [editingSemester, setEditingSemester] = useState<
    Semester | "new" | null
  >(null);
  const [activating, setActivating] = useState(false);
  const [version, setVersion] = useState(0);

  const reload = useCallback(() => setVersion((value) => value + 1), []);
  // biome-ignore lint/correctness/useExhaustiveDependencies: referensi dimuat ulang lewat version
  useEffect(() => {
    let active = true;
    academicRequest<{ data: AcademicContext }>("context")
      .then((body) => {
        if (active) setContext(body.data);
      })
      .catch(() => {});
    academicRequest<AcademicPage<Year>>(
      academicListPath("years", { per_page: 100 }),
    )
      .then((data) => {
        if (active) {
          setYears(data.data);
          setYearId((current) => current || String(data.data[0]?.id ?? ""));
        }
      })
      .catch(() => {});
    return () => {
      active = false;
    };
  }, [version]);

  const yearOptions: Option[] = years.map((year) => ({
    value: String(year.id),
    label: year.name,
  }));
  const openYearOptions: Option[] = years
    .filter((year) => year.status === "open")
    .map((year) => ({ value: String(year.id), label: year.name }));
  const currentYear = years.find((year) => String(year.id) === yearId);

  return (
    <div className="animate-enter space-y-6">
      <AcademicHeading
        title="Tahun ajaran & semester"
        description="Kelola tahun ajaran, semester, dan satu periode aktif yang menjadi acuan seluruh modul madrasah (DEC-19)."
        action={
          canManage ? (
            <div className="flex flex-wrap gap-2">
              <Button
                variant="outline"
                className="h-11 gap-2 text-xs"
                disabled={!context}
                onClick={() => setActivating(true)}
              >
                <CalendarCheck className="size-4" /> Atur periode aktif
              </Button>
              <Button
                className="h-11 gap-2 text-xs"
                onClick={() => setEditingYear("new")}
              >
                <Plus className="size-4" /> Tambah tahun ajaran
              </Button>
            </div>
          ) : undefined
        }
      />
      <Card className="rounded-2xl border border-border/70 py-0 ring-0">
        <CardContent className="flex flex-wrap items-center gap-x-8 gap-y-3 p-4 sm:p-5">
          <div>
            <p className="text-[11px] text-muted-foreground">Periode aktif</p>
            <p className="mt-1 text-sm font-semibold">
              {context?.year ? context.year.name : "Belum diatur"}
            </p>
          </div>
          <div>
            <p className="text-[11px] text-muted-foreground">Semester aktif</p>
            <p className="mt-1 text-sm font-semibold">
              {context?.semester ? context.semester.name : "—"}
            </p>
          </div>
          {context?.year && <StatusBadge status={context.year.status} />}
          {context?.semester && (
            <StatusBadge status={context.semester.status} />
          )}
        </CardContent>
      </Card>
      <AcademicList<Year>
        reloadKey={`years-${version}`}
        load={(page) =>
          academicRequest<AcademicPage<Year>>(
            academicListPath("years", { page, per_page: 25 }),
          )
        }
        columns={[
          {
            label: "Tahun ajaran",
            cell: (row) => (
              <div>
                <p className="text-[13px] font-medium">{row.name}</p>
                <p className="mt-1 text-[11px] text-muted-foreground">
                  {row.code}
                </p>
              </div>
            ),
          },
          {
            label: "Rentang",
            cell: (row) => (
              <span className="text-xs">
                {row.start_date} — {row.end_date}
              </span>
            ),
          },
          { label: "Semester", cell: (row) => `${row.semesters_count ?? 0}` },
          {
            label: "Status",
            cell: (row) => <StatusBadge status={row.status} />,
          },
        ]}
        rowTitle={(row) => row.name}
        rowMeta={(row) => `${row.start_date} — ${row.end_date}`}
        rowActions={
          canManage
            ? (row) => (
                <Button
                  variant="outline"
                  className="h-11 text-xs"
                  onClick={() => setEditingYear(row)}
                >
                  Ubah
                </Button>
              )
            : undefined
        }
      />
      {years.length > 0 && (
        <section className="space-y-4">
          <div className="flex flex-wrap items-end justify-between gap-3">
            <div>
              <h2 className="text-sm font-semibold">Semester</h2>
              <p className="mt-1 text-xs text-muted-foreground">
                Rentang semester harus berada dalam tahun ajaran dan tidak
                bertumpang tindih.
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
              {canManage && currentYear && (
                <Button
                  variant="outline"
                  className="h-11 gap-2 text-xs"
                  onClick={() => setEditingSemester("new")}
                >
                  <Plus className="size-4" /> Tambah semester
                </Button>
              )}
            </div>
          </div>
          <AcademicList<Semester>
            reloadKey={`semesters-${yearId}-${version}`}
            load={(page) =>
              academicRequest<AcademicPage<Semester>>(
                academicListPath("semesters", {
                  page,
                  per_page: 25,
                  academic_year_id: yearId,
                }),
              )
            }
            columns={[
              {
                label: "Semester",
                cell: (row) => (
                  <span className="text-[13px] font-medium">{row.name}</span>
                ),
              },
              {
                label: "Rentang",
                cell: (row) => (
                  <span className="text-xs">
                    {row.start_date} — {row.end_date}
                  </span>
                ),
              },
              {
                label: "Status",
                cell: (row) => <StatusBadge status={row.status} />,
              },
            ]}
            rowTitle={(row) => row.name}
            rowMeta={(row) => `${row.start_date} — ${row.end_date}`}
            rowActions={
              canManage
                ? (row) => (
                    <Button
                      variant="outline"
                      className="h-11 text-xs"
                      onClick={() => setEditingSemester(row)}
                    >
                      Ubah
                    </Button>
                  )
                : undefined
            }
            emptyTitle="Belum ada semester"
            emptyHint="Tambahkan semester 1 dan 2 untuk tahun ajaran ini."
          />
        </section>
      )}
      {editingYear && (
        <AcademicFormDialog
          title={
            editingYear === "new" ? "Tambah tahun ajaran" : "Ubah tahun ajaran"
          }
          description="Tahun ajaran menampung semester, rombongan belajar, kalender, dan penugasan."
          fields={
            editingYear === "new"
              ? PERIOD_FIELDS(true)
              : [...PERIOD_FIELDS(false), STATUS_FIELD]
          }
          current={editingYear === "new" ? undefined : editingYear}
          onClose={() => setEditingYear(null)}
          submit={async (body) => {
            await academicRequest(
              editingYear === "new" ? "years" : `years/${editingYear.id}`,
              editingYear === "new" ? "POST" : "PUT",
              body,
            );
            reload();
          }}
        />
      )}
      {editingSemester && (
        <AcademicFormDialog
          title={
            editingSemester === "new" ? "Tambah semester" : "Ubah semester"
          }
          description="Semester hanya dapat dibuat pada tahun ajaran yang dipilih."
          fields={
            editingSemester === "new"
              ? SEMESTER_FIELDS(true)
              : [...SEMESTER_FIELDS(false), STATUS_FIELD]
          }
          current={editingSemester === "new" ? undefined : editingSemester}
          onClose={() => setEditingSemester(null)}
          submit={async (body) => {
            if (editingSemester === "new") {
              await academicRequest("semesters", "POST", {
                ...body,
                academic_year_id: Number(yearId),
              });
            } else {
              await academicRequest(
                `semesters/${editingSemester.id}`,
                "PUT",
                body,
              );
            }
            reload();
          }}
        />
      )}
      {activating && context && (
        <ContextActivator
          context={context}
          yearOptions={openYearOptions}
          onClose={() => setActivating(false)}
          onSaved={reload}
        />
      )}
    </div>
  );
}

function ContextActivator({
  context,
  yearOptions,
  onClose,
  onSaved,
}: {
  context: AcademicContext;
  yearOptions: Option[];
  onClose: () => void;
  onSaved: () => void;
}) {
  const [yearId, setYearId] = useState(
    context.year ? String(context.year.id) : (yearOptions[0]?.value ?? ""),
  );
  const [semesterId, setSemesterId] = useState("");
  const [semesters, setSemesters] = useState<Semester[]>([]);
  const [busy, setBusy] = useState(false);
  const [failure, setFailure] = useState("");

  useEffect(() => {
    if (!yearId) {
      setSemesters([]);
      return;
    }
    let active = true;
    setSemesterId("");
    academicRequest<AcademicPage<Semester>>(
      academicListPath("semesters", {
        academic_year_id: yearId,
        per_page: 10,
        status: "open",
      }),
    )
      .then((data) => {
        if (active) setSemesters(data.data);
      })
      .catch(() => {});
    return () => {
      active = false;
    };
  }, [yearId]);

  return (
    <FormDialog
      title="Atur periode aktif"
      description="Hanya tahun ajaran dan semester berstatus terbuka yang dapat diaktifkan. Seluruh modul mengikuti periode aktif ini."
      busy={busy}
      error={failure}
      onClose={onClose}
      submitLabel="Aktifkan periode"
      onSubmit={async (event) => {
        event.preventDefault();
        if (busy || !yearId || !semesterId) return;
        setBusy(true);
        setFailure("");
        const reason = new FormData(event.currentTarget).get("reason");
        try {
          await academicRequest("context", "PUT", {
            active_academic_year_id: Number(yearId),
            active_semester_id: Number(semesterId),
            expected_lock_version: context.lock_version,
            reason,
          });
          toast.success("Periode aktif diperbarui.");
          onSaved();
          onClose();
        } catch (reason_) {
          setFailure(academicError(reason_));
          setBusy(false);
        }
      }}
      submitDisabled={!yearId || !semesterId}
    >
      <FilterSelect
        label="Tahun ajaran"
        value={yearId}
        onChange={setYearId}
        options={yearOptions}
        placeholder="Pilih tahun"
      />
      <div className="mt-4">
        <FilterSelect
          label="Semester"
          value={semesterId}
          onChange={setSemesterId}
          options={semesters.map((semester) => ({
            value: String(semester.id),
            label: semester.name,
          }))}
          placeholder="Pilih semester"
        />
      </div>
      <div className="mt-4">
        <ReasonField disabled={busy} />
      </div>
    </FormDialog>
  );
}
