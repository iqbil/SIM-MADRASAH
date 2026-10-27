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
import {
  type AcademicPage,
  type AcademicRecord,
  academicListPath,
  academicRequest,
  CALENDAR_FIELDS,
  eventTypeLabel,
  type Option,
} from "@/lib/academic";

type CalendarEvent = AcademicRecord & {
  title: string;
  event_type: string;
  start_date: string;
  end_date: string;
  blocks_teaching: boolean;
  blocks_employee_work: boolean;
};

export function AcademicCalendar({ access }: { access: string[] }) {
  const canManage =
    access.includes("academic.calendar.manage") ||
    access.includes("academic.manage");
  const [years, setYears] = useState<AcademicRecord[]>([]);
  const [yearId, setYearId] = useState("");
  const [typeFilter, setTypeFilter] = useState("");
  const [editing, setEditing] = useState<CalendarEvent | "new" | null>(null);
  const [version, setVersion] = useState(0);
  const reload = useCallback(() => setVersion((value) => value + 1), []);

  // biome-ignore lint/correctness/useExhaustiveDependencies: referensi dimuat ulang lewat version
  useEffect(() => {
    let active = true;
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

  return (
    <div className="animate-enter space-y-6">
      <AcademicHeading
        title="Kalender akademik"
        description="Catat libur, kegiatan, dan ujian. Kejadian yang memblokir mengajar mencegah JP honor otomatis (DEC-04); kejadian tanpa kelas berlaku seluruh madrasah."
        action={
          canManage ? (
            <Button
              className="h-11 gap-2 text-xs"
              disabled={!yearId}
              onClick={() => setEditing("new")}
            >
              <Plus className="size-4" /> Tambah kejadian
            </Button>
          ) : undefined
        }
      />
      <div className="flex flex-wrap items-end gap-3">
        <FilterSelect
          label="Tahun ajaran"
          value={yearId}
          onChange={setYearId}
          options={yearOptions}
          placeholder="Pilih tahun"
          className="w-full sm:w-56"
        />
        <FilterSelect
          label="Jenis"
          value={typeFilter}
          onChange={setTypeFilter}
          options={[
            { value: "holiday", label: "Libur" },
            { value: "activity", label: "Kegiatan" },
            { value: "exam", label: "Ujian" },
            { value: "other", label: "Lainnya" },
          ]}
          placeholder="Semua jenis"
          className="w-full sm:w-44"
        />
      </div>
      <AcademicList<CalendarEvent>
        reloadKey={`calendar-${yearId}-${typeFilter}-${version}`}
        load={(page) =>
          academicRequest<AcademicPage<CalendarEvent>>(
            academicListPath("calendar", {
              page,
              per_page: 25,
              academic_year_id: yearId,
              event_type: typeFilter,
            }),
          )
        }
        columns={[
          {
            label: "Kejadian",
            cell: (row) => (
              <div>
                <p className="text-sm font-medium">{row.title}</p>
                <p className="mt-1 text-xs text-muted-foreground">
                  {eventTypeLabel(row.event_type)}
                </p>
              </div>
            ),
          },
          {
            label: "Tanggal",
            cell: (row) => (
              <span className="text-xs">
                {row.start_date} — {row.end_date}
              </span>
            ),
          },
          {
            label: "Blokir",
            cell: (row) => (
              <div className="flex flex-wrap gap-1">
                {row.blocks_teaching && (
                  <Badge variant="secondary" className="text-xs">
                    Mengajar
                  </Badge>
                )}
                {row.blocks_employee_work && (
                  <Badge variant="secondary" className="text-xs">
                    Hari kerja
                  </Badge>
                )}
                {!row.blocks_teaching && !row.blocks_employee_work && (
                  <span className="text-xs text-muted-foreground">—</span>
                )}
              </div>
            ),
          },
        ]}
        rowTitle={(row) => row.title}
        rowMeta={(row) => `${row.start_date} — ${row.end_date}`}
        rowActions={
          canManage
            ? (row) => (
                <Button
                  variant="outline"
                  className="h-11 text-xs"
                  onClick={() => setEditing(row)}
                >
                  Ubah
                </Button>
              )
            : undefined
        }
        emptyTitle="Belum ada kejadian"
        emptyHint="Tambahkan libur dan kegiatan madrasah untuk tahun ajaran ini."
      />
      {editing && (
        <AcademicFormDialog
          title={editing === "new" ? "Tambah kejadian" : "Ubah kejadian"}
          description="Tanggal kejadian harus berada dalam tahun ajaran yang dipilih."
          fields={CALENDAR_FIELDS(yearOptions)}
          current={editing === "new" ? undefined : editing}
          onClose={() => setEditing(null)}
          submit={async (body) => {
            const payload = {
              ...body,
              academic_year_id: Number(
                body.academic_year_id ??
                  (editing === "new" ? yearId : editing.academic_year_id),
              ),
            };
            await academicRequest(
              editing === "new" ? "calendar" : `calendar/${editing.id}`,
              editing === "new" ? "POST" : "PUT",
              payload,
            );
            reload();
          }}
        />
      )}
    </div>
  );
}
