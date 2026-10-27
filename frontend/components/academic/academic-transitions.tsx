"use client";

import { CircleAlert, LoaderCircle, Search } from "lucide-react";
import { type FormEvent, useCallback, useEffect, useState } from "react";
import { toast } from "sonner";
import {
  AcademicHeading,
  FilterSelect,
} from "@/components/academic/academic-shared";
import { ConfirmationDialog } from "@/components/layout/confirmation-dialog";
import { ReasonField } from "@/components/layout/reason-field";
import { Alert, AlertDescription } from "@/components/ui/alert";
import { Badge } from "@/components/ui/badge";
import { Button } from "@/components/ui/button";
import { Card, CardContent } from "@/components/ui/card";
import { Checkbox } from "@/components/ui/checkbox";
import { Input } from "@/components/ui/input";
import {
  Table,
  TableBody,
  TableCell,
  TableHead,
  TableHeader,
  TableRow,
} from "@/components/ui/table";
import {
  type AcademicPage,
  type AcademicRecord,
  academicError,
  academicListPath,
  academicRequest,
  type Option,
} from "@/lib/academic";

type Enrollment = AcademicRecord & {
  student_id: number;
  class_period_id: number;
  student?: { id: number; person?: { full_name: string } | null } | null;
  classPeriod?: { id: number; display_name: string } | null;
};
type Plan = {
  student_id: number;
  student_name: string | null;
  current_status: string | null;
  to_status: string | null;
  current_class_name: string | null;
  to_class_name: string | null;
  effective_date: string;
  problem: string | null;
};

const TYPES: Option[] = [
  { value: "promotion", label: "Kenaikan kelas" },
  { value: "class_transfer", label: "Pindah kelas" },
  { value: "graduation", label: "Kelulusan" },
  { value: "school_transfer", label: "Pindah madrasah" },
  { value: "withdrawal", label: "Keluar" },
];

export function AcademicTransitions({ access }: { access: string[] }) {
  const canManage =
    access.includes("academic.enrollments.manage") ||
    access.includes("academic.manage");
  const [periods, setPeriods] = useState<
    (AcademicRecord & { display_name: string })[]
  >([]);
  const [periodId, setPeriodId] = useState("");
  const [date, setDate] = useState(() => new Date().toISOString().slice(0, 10));
  const [type, setType] = useState("promotion");
  const [targetPeriod, setTargetPeriod] = useState("");
  const [candidates, setCandidates] = useState<Enrollment[]>([]);
  const [picked, setPicked] = useState<Set<number>>(new Set());
  const [search, setSearch] = useState("");
  const [plan, setPlan] = useState<{ ready: boolean; students: Plan[] } | null>(
    null,
  );
  const [busy, setBusy] = useState(false);
  const [failure, setFailure] = useState("");
  const [confirming, setConfirming] = useState(false);

  const loadCandidates = useCallback(() => {
    if (!periodId) {
      setCandidates([]);
      return;
    }
    academicRequest<AcademicPage<Enrollment>>(
      academicListPath("enrollments", {
        class_period_id: periodId,
        date,
        per_page: 100,
      }),
    )
      .then((data) => {
        setCandidates(data.data);
        setPicked(new Set());
      })
      .catch((reason) => setFailure(academicError(reason)));
  }, [periodId, date]);

  useEffect(() => {
    academicRequest<AcademicPage<AcademicRecord & { display_name: string }>>(
      academicListPath("class-periods", { per_page: 100 }),
    )
      .then((data) => setPeriods(data.data))
      .catch(() => {});
  }, []);

  useEffect(() => {
    loadCandidates();
    setPlan(null);
  }, [loadCandidates]);

  const periodOptions: Option[] = periods.map((row) => ({
    value: String(row.id),
    label: row.display_name,
  }));
  const needsTarget = type === "promotion" || type === "class_transfer";
  const visible = candidates.filter((row) =>
    search
      ? (row.student?.person?.full_name ?? "")
          .toLowerCase()
          .includes(search.toLowerCase())
      : true,
  );

  async function confirmTransition(event: FormEvent<HTMLFormElement>) {
    event.preventDefault();
    if (!plan || busy) return;
    setBusy(true);
    setFailure("");
    const reason = new FormData(event.currentTarget).get("reason");
    try {
      await academicRequest("transitions/confirm", "POST", {
        student_ids: plan.students.map((row) => row.student_id),
        type,
        effective_date: date,
        ...(needsTarget && targetPeriod
          ? { to_class_period_id: Number(targetPeriod) }
          : {}),
        reason,
      });
      toast.success("Mutasi siswa berhasil diproses.");
      setConfirming(false);
      setPlan(null);
      loadCandidates();
    } catch (reason_) {
      setFailure(academicError(reason_));
    } finally {
      setBusy(false);
    }
  }

  async function preview() {
    if (picked.size === 0) return;
    setBusy(true);
    setFailure("");
    try {
      const body = await academicRequest<{
        data: { ready: boolean; students: Plan[] };
      }>("transitions/preview", "POST", {
        student_ids: [...picked],
        type,
        effective_date: date,
        ...(needsTarget && targetPeriod
          ? { to_class_period_id: Number(targetPeriod) }
          : {}),
      });
      setPlan(body.data);
    } catch (reason) {
      setFailure(academicError(reason));
    } finally {
      setBusy(false);
    }
  }

  return (
    <div className="animate-enter space-y-6">
      <AcademicHeading
        title="Kenaikan & mutasi siswa"
        description="Pratinjau terlebih dahulu, lalu konfirmasi untuk memproses kenaikan, kelulusan, pindah, atau keluar secara batch dengan riwayat tertelusuri."
      />
      <Card className="rounded-2xl border border-border/70 py-0 ring-0">
        <CardContent className="space-y-4 p-4 sm:p-5">
          <div className="grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
            <FilterSelect
              label="Jenis mutasi"
              value={type}
              onChange={(value) => {
                setType(value);
                setPlan(null);
              }}
              options={TYPES}
              placeholder="Pilih jenis"
            />
            <FilterSelect
              label="Rombel asal"
              value={periodId}
              onChange={setPeriodId}
              options={periodOptions}
              placeholder="Pilih rombel"
            />
            <FilterSelect
              label="Rombel tujuan"
              value={targetPeriod}
              onChange={setTargetPeriod}
              options={periodOptions.filter(
                (option) => option.value !== periodId,
              )}
              placeholder={needsTarget ? "Pilih tujuan" : "Tidak perlu"}
            />
            <div>
              <span className="mb-1.5 block text-xs text-muted-foreground">
                Tanggal efektif
              </span>
              <Input
                type="date"
                aria-label="Tanggal efektif"
                value={date}
                onChange={(event) => {
                  setDate(event.target.value);
                  setPlan(null);
                }}
                className="h-11 bg-background"
              />
            </div>
          </div>
          {failure && (
            <Alert variant="destructive">
              <CircleAlert />
              <AlertDescription>{failure}</AlertDescription>
            </Alert>
          )}
          {periodId ? (
            <div className="space-y-3">
              <div className="flex flex-wrap items-center justify-between gap-3">
                <div className="relative min-w-0 flex-1 sm:max-w-xs">
                  <Search className="absolute top-3.5 left-3 size-4 text-muted-foreground" />
                  <Input
                    aria-label="Cari siswa"
                    placeholder="Cari nama siswa…"
                    value={search}
                    onChange={(event) => setSearch(event.target.value)}
                    className="h-11 bg-background/60 pl-10"
                  />
                </div>
                <p className="text-xs text-muted-foreground">
                  {picked.size} siswa dipilih dari {candidates.length} anggota
                </p>
              </div>
              <div className="max-h-80 overflow-y-auto rounded-xl border border-border">
                {visible.map((row) => {
                  const id = row.student_id;
                  return (
                    <div
                      key={row.id}
                      className="flex min-h-11 cursor-pointer items-center gap-3 border-b border-border/60 px-3 py-2 last:border-0 has-[[data-state=checked]]:bg-accent"
                    >
                      <Checkbox
                        aria-label={`Pilih ${row.student?.person?.full_name ?? id}`}
                        checked={picked.has(id)}
                        onCheckedChange={(checked) => {
                          setPicked((current) => {
                            const next = new Set(current);
                            if (checked) next.add(id);
                            else next.delete(id);
                            return next;
                          });
                          setPlan(null);
                        }}
                      />
                      <span className="min-w-0 flex-1 break-words text-sm">
                        {row.student?.person?.full_name ?? `#${id}`}
                      </span>
                    </div>
                  );
                })}
                {visible.length === 0 && (
                  <p className="p-4 text-xs text-muted-foreground">
                    Tidak ada anggota pada rombel dan tanggal ini.
                  </p>
                )}
              </div>
              <Button
                className="h-11 w-full gap-2 sm:w-auto"
                disabled={
                  busy || picked.size === 0 || (needsTarget && !targetPeriod)
                }
                onClick={() => void preview()}
              >
                {busy ? (
                  <>
                    <LoaderCircle className="size-4 animate-spin" /> Menyiapkan
                    pratinjau…
                  </>
                ) : (
                  `Pratinjau ${picked.size} siswa`
                )}
              </Button>
            </div>
          ) : (
            <p className="text-xs leading-5 text-muted-foreground">
              Pilih rombel asal dan tanggal efektif untuk menampilkan anggota
              yang dapat dimutasikan.
            </p>
          )}
        </CardContent>
      </Card>
      {plan && (
        <Card className="rounded-2xl border border-border/70 py-0 ring-0">
          <CardContent className="space-y-4 p-4 sm:p-5">
            <div className="flex flex-wrap items-center justify-between gap-3">
              <h2 className="text-sm font-semibold">Pratinjau mutasi</h2>
              <Badge
                variant={plan.ready ? "secondary" : "outline"}
                className={
                  plan.ready
                    ? "bg-primary/8 text-xs text-primary"
                    : "text-xs text-muted-foreground"
                }
              >
                {plan.ready ? "Siap dikonfirmasi" : "Ada masalah"}
              </Badge>
            </div>
            <div className="overflow-x-auto">
              <Table>
                <TableHeader>
                  <TableRow>
                    <TableHead>Siswa</TableHead>
                    <TableHead>Kelas saat ini</TableHead>
                    <TableHead>Tujuan</TableHead>
                    <TableHead>Status akhir</TableHead>
                    <TableHead>Catatan</TableHead>
                  </TableRow>
                </TableHeader>
                <TableBody>
                  {plan.students.map((row) => (
                    <TableRow key={row.student_id}>
                      <TableCell className="py-3 text-sm font-medium">
                        {row.student_name ?? `#${row.student_id}`}
                      </TableCell>
                      <TableCell className="text-xs">
                        {row.current_class_name ?? "—"}
                      </TableCell>
                      <TableCell className="text-xs">
                        {row.to_class_name ??
                          (needsTarget ? "—" : "Tanpa kelas")}
                      </TableCell>
                      <TableCell className="text-xs capitalize">
                        {row.to_status ?? "—"}
                      </TableCell>
                      <TableCell className="text-xs text-muted-foreground">
                        {row.problem ?? "Aman"}
                      </TableCell>
                    </TableRow>
                  ))}
                </TableBody>
              </Table>
            </div>
            {canManage && (
              <Button
                className="h-11 w-full gap-2 sm:w-auto"
                disabled={!plan.ready}
                onClick={() => setConfirming(true)}
              >
                Konfirmasi mutasi {plan.students.length} siswa
              </Button>
            )}
          </CardContent>
        </Card>
      )}
      {confirming && plan && (
        <ConfirmationDialog
          title="Konfirmasi mutasi siswa"
          description={`Mutasi ${TYPES.find((option) => option.value === type)?.label.toLowerCase()} pada ${date} untuk ${plan.students.length} siswa akan menutup keanggotaan lama, membuka keanggotaan baru bila ada tujuan, mengubah status siswa, dan mencatat riwayat mutasi.`}
          busy={busy}
          error={failure}
          submitLabel="Proses mutasi"
          onClose={() => setConfirming(false)}
          onSubmit={confirmTransition}
        >
          <ReasonField disabled={busy} />
          <p className="text-xs leading-5 text-muted-foreground">
            Tindakan ini tidak dapat dibatalkan; koreksi setelahnya harus
            melalui mutasi koreksi dengan alasan yang jelas.
          </p>
        </ConfirmationDialog>
      )}
    </div>
  );
}
