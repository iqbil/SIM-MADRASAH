"use client";

import { RefreshCw } from "lucide-react";
import {
  type FormEvent,
  type ReactNode,
  useEffect,
  useId,
  useState,
} from "react";
import { toast } from "sonner";
import { ConfirmationDialog } from "@/components/layout/confirmation-dialog";
import { DataFailure, DataLoading } from "@/components/layout/data-feedback";
import { FormDialog } from "@/components/layout/form-dialog";
import { Pager } from "@/components/layout/pager";
import { ReasonField } from "@/components/layout/reason-field";
import { MasterField } from "@/components/master/master-fields";
import { Badge } from "@/components/ui/badge";
import { Button } from "@/components/ui/button";
import { Card, CardContent } from "@/components/ui/card";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from "@/components/ui/select";
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
  normalizeAcademicPage,
} from "@/lib/academic";
import { AuthRequestError } from "@/lib/auth/client";
import type { ApiError } from "@/lib/auth/types";
import type { FieldDefinition } from "@/lib/master-data";

export function AcademicHeading({
  title,
  description,
  category = "Akademik & kelas",
  action,
}: {
  title: string;
  description: string;
  category?: string;
  action?: ReactNode;
}) {
  return (
    <div className="flex flex-col justify-between gap-4 sm:flex-row sm:items-end">
      <div>
        <p className="mb-2 text-xs text-muted-foreground">{category}</p>
        <h1 className="text-[27px] font-semibold tracking-tight sm:text-3xl">
          {title}
        </h1>
        <p className="mt-2 max-w-2xl text-sm leading-6 text-muted-foreground">
          {description}
        </p>
      </div>
      {action}
    </div>
  );
}

export function StatusBadge({ status }: { status: unknown }) {
  const value = typeof status === "string" ? status : "";
  const label =
    { planned: "Direncanakan", open: "Terbuka", closed: "Ditutup" }[value] ??
    value;
  return (
    <Badge
      variant={value === "open" ? "secondary" : "outline"}
      className={
        value === "open"
          ? "bg-primary/8 text-xs text-primary"
          : "text-xs text-muted-foreground"
      }
    >
      {label}
    </Badge>
  );
}

type Column<T> = {
  label: string;
  cell: (row: T) => ReactNode;
  align?: "left" | "right";
  hidden?: boolean;
};

/** Daftar tabel desktop + kartu mobile dengan pagination bersama. */
export function AcademicList<T extends AcademicRecord>({
  load,
  columns,
  rowTitle,
  rowMeta,
  rowActions,
  create,
  emptyTitle = "Belum ada data",
  emptyHint = "Tambahkan data atau sesuaikan filter Anda.",
  reloadKey,
}: {
  load: (page: number) => Promise<AcademicPage<T>>;
  columns: Column<T>[];
  rowTitle: (row: T) => ReactNode;
  rowMeta?: (row: T) => ReactNode;
  rowActions?: (row: T) => ReactNode;
  create?: { label: string; onClick: () => void; disabled?: boolean };
  emptyTitle?: string;
  emptyHint?: string;
  reloadKey?: unknown;
}) {
  const [page, setPage] = useState(1);
  const [tick, setTick] = useState(0);
  const [result, setResult] = useState<AcademicPage<T> | null>(null);
  const [failure, setFailure] = useState("");
  const [busy, setBusy] = useState(false);

  // biome-ignore lint/correctness/useExhaustiveDependencies: loader dimuat ulang saat halaman/kunci berubah
  useEffect(() => {
    let active = true;
    setBusy(true);
    load(page)
      .then((data) => {
        if (active) {
          setResult(normalizeAcademicPage<T>(data));
          setFailure("");
        }
      })
      .catch((reason) => {
        if (active) setFailure(academicError(reason));
      })
      .finally(() => {
        if (active) setBusy(false);
      });
    return () => {
      active = false;
    };
  }, [page, reloadKey, tick]);

  const desktopColumns = columns.filter((column) => !column.hidden);
  const mobileColumns = columns.filter(
    (column) => column !== desktopColumns[0] && !column.hidden,
  );

  const totalCount =
    result?.meta?.total ?? (result as { total?: number } | null)?.total ?? 0;
  const currentPage =
    result?.meta?.current_page ??
    (result as { current_page?: number } | null)?.current_page ??
    1;
  const lastPage =
    result?.meta?.last_page ??
    (result as { last_page?: number } | null)?.last_page ??
    1;

  return (
    <Card className="rounded-2xl border border-border/70 py-0 ring-0">
      <CardContent className="p-4 sm:p-5">
        <div className="mb-4 flex items-center justify-between gap-3">
          <p className="text-xs text-muted-foreground">
            {result ? `${totalCount} data` : busy ? "Memuat…" : "—"}
          </p>
          <div className="flex items-center gap-2">
            {create && (
              <Button
                className="h-11 gap-2 text-xs"
                onClick={create.onClick}
                disabled={create.disabled}
              >
                {create.label}
              </Button>
            )}
            <Button
              variant="outline"
              size="icon"
              className="size-11"
              aria-label="Muat ulang data"
              disabled={busy}
              onClick={() => setTick((value) => value + 1)}
            >
              <RefreshCw className={busy ? "size-4 animate-spin" : "size-4"} />
            </Button>
          </div>
        </div>
        {failure && <DataFailure message={failure} />}
        {busy ? (
          <DataLoading />
        ) : failure ? null : !result?.data?.length ? (
          <div className="py-12 text-center">
            <p className="text-sm font-medium">{emptyTitle}</p>
            <p className="mt-2 text-xs leading-5 text-muted-foreground">
              {emptyHint}
            </p>
          </div>
        ) : (
          <>
            <div className="hidden md:block">
              <Table>
                <TableHeader>
                  <TableRow>
                    {desktopColumns.map((column) => (
                      <TableHead
                        key={column.label}
                        className={column.align === "right" ? "text-right" : ""}
                      >
                        {column.label}
                      </TableHead>
                    ))}
                    {rowActions && (
                      <TableHead className="text-right">Tindakan</TableHead>
                    )}
                  </TableRow>
                </TableHeader>
                <TableBody>
                  {result?.data.map((row) => (
                    <TableRow key={row.id}>
                      {desktopColumns.map((column) => (
                        <TableCell key={column.label} className="py-4">
                          {column.cell(row)}
                        </TableCell>
                      ))}
                      {rowActions && (
                        <TableCell className="text-right">
                          {rowActions(row)}
                        </TableCell>
                      )}
                    </TableRow>
                  ))}
                </TableBody>
              </Table>
            </div>
            <div className="divide-y md:hidden">
              {result?.data.map((row) => (
                <div key={row.id} className="space-y-2 py-4 first:pt-0">
                  <div className="text-sm font-medium">{rowTitle(row)}</div>
                  {rowMeta && (
                    <div className="text-xs text-muted-foreground">
                      {rowMeta(row)}
                    </div>
                  )}
                  <dl className="space-y-1.5">
                    {mobileColumns.map((column) => (
                      <div
                        key={column.label}
                        className="flex items-start justify-between gap-3 text-xs"
                      >
                        <dt className="shrink-0 text-muted-foreground">
                          {column.label}
                        </dt>
                        <dd className="min-w-0 text-right">
                          {column.cell(row)}
                        </dd>
                      </div>
                    ))}
                  </dl>
                  {rowActions && (
                    <div className="flex flex-wrap justify-end gap-2">
                      {rowActions(row)}
                    </div>
                  )}
                </div>
              ))}
            </div>
            <Pager
              meta={{
                page: currentPage,
                last_page: lastPage,
                total: totalCount,
              }}
              setPage={setPage}
              busy={busy}
            />
          </>
        )}
      </CardContent>
    </Card>
  );
}

/** Dialog form generik: field FieldDefinition + alasan + konflik versi. */
export function AcademicFormDialog({
  title,
  description,
  fields,
  current,
  onClose,
  submit,
  extra,
  submitLabel,
}: {
  title: string;
  description: string;
  fields: FieldDefinition[];
  current?: AcademicRecord;
  onClose: () => void;
  submit: (body: Record<string, unknown>) => Promise<void>;
  extra?: ReactNode;
  submitLabel?: string;
}) {
  const [busy, setBusy] = useState(false);
  const [failure, setFailure] = useState<ApiError>({});
  const [conflict, setConflict] = useState(false);
  const errorFor = (name: string) => failure.errors?.[name]?.[0];

  async function onSubmit(event: FormEvent<HTMLFormElement>) {
    event.preventDefault();
    if (busy) return;
    setBusy(true);
    setFailure({});
    setConflict(false);
    const data = new FormData(event.currentTarget);
    const body: Record<string, unknown> = { reason: data.get("reason") };
    for (const field of fields) {
      body[field.name] =
        field.type === "boolean"
          ? data.get(field.name) === "on"
          : data.get(field.name) === "__empty" || data.get(field.name) === ""
            ? null
            : data.get(field.name);
    }
    if (current) body.expected_updated_at = current.updated_at;
    try {
      await submit(body);
      toast.success("Perubahan berhasil disimpan.");
      onClose();
    } catch (reason) {
      setFailure(
        reason instanceof AuthRequestError
          ? reason.payload
          : { message: academicError(reason) },
      );
      setConflict(reason instanceof AuthRequestError && reason.status === 409);
      setBusy(false);
    }
  }

  return (
    <FormDialog
      title={title}
      description={description}
      busy={busy}
      error={failure.message}
      onClose={onClose}
      onSubmit={onSubmit}
      submitLabel={submitLabel ?? "Simpan"}
      submitDisabled={conflict}
    >
      {conflict && (
        <p className="rounded-lg border border-destructive/30 bg-destructive/5 p-3 text-xs leading-5 text-destructive">
          Data telah berubah sejak terakhir dimuat. Tutup dialog, muat ulang
          daftar, lalu ulangi perubahan Anda.
        </p>
      )}
      {extra}
      <section aria-label={title} className="space-y-4">
        {fields.map((field) => (
          <MasterField
            key={field.name}
            field={field}
            value={current?.[field.name]}
            error={errorFor(field.name)}
            editing={Boolean(current)}
          />
        ))}
      </section>
      <ReasonField disabled={busy} error={errorFor("reason")} />
    </FormDialog>
  );
}

/** Opsi pilihan ringkas untuk dialog (label + nilai string ID). */
export type PickerOption = { value: string; label: string };

export function FilterSelect({
  label,
  value,
  onChange,
  options,
  placeholder,
  className,
  disabled = false,
}: {
  label: string;
  value: string;
  onChange: (value: string) => void;
  options: PickerOption[];
  placeholder?: string;
  className?: string;
  disabled?: boolean;
}) {
  const id = useId();
  return (
    <div className={className ?? "w-full sm:w-64"}>
      <Label
        htmlFor={id}
        className="mb-1.5 block text-sm text-muted-foreground"
      >
        {label}
      </Label>
      <Select
        disabled={disabled}
        value={value || undefined}
        onValueChange={(next) => onChange(next === "__all" ? "" : next)}
      >
        <SelectTrigger
          id={id}
          aria-label={label}
          className="h-11! w-full text-base md:text-sm"
        >
          <SelectValue placeholder={placeholder ?? "Semua"} />
        </SelectTrigger>
        <SelectContent>
          <SelectItem value="__all">{placeholder ?? "Semua"}</SelectItem>
          {options.map((option) => (
            <SelectItem key={option.value} value={option.value}>
              {option.label}
            </SelectItem>
          ))}
        </SelectContent>
      </Select>
    </div>
  );
}

export function DateFilter({
  label,
  value,
  onChange,
  className,
  disabled = false,
}: {
  label: string;
  value: string;
  onChange: (value: string) => void;
  className?: string;
  disabled?: boolean;
}) {
  const id = useId();
  return (
    <div className={className ?? "w-full sm:w-44"}>
      <Label
        htmlFor={id}
        className="mb-1.5 block text-sm text-muted-foreground"
      >
        {label}
      </Label>
      <Input
        id={id}
        disabled={disabled}
        type="date"
        aria-label={label}
        value={value}
        onChange={(event) => onChange(event.target.value)}
        className="h-11 bg-background"
      />
    </div>
  );
}

/** Dialog konfirmasi dengan alasan (min. 10 karakter) untuk tindakan akademik. */
export function ConfirmAction({
  title,
  description,
  submitLabel,
  destructive = false,
  note,
  onClose,
  action,
}: {
  title: string;
  description: string;
  submitLabel: string;
  destructive?: boolean;
  note?: string;
  onClose: () => void;
  action: (reason: string) => Promise<void>;
}) {
  const [busy, setBusy] = useState(false);
  const [error, setError] = useState("");
  return (
    <ConfirmationDialog
      title={title}
      description={description}
      busy={busy}
      error={error}
      submitLabel={submitLabel}
      destructive={destructive}
      onClose={onClose}
      onSubmit={(event) => {
        event.preventDefault();
        if (busy) return;
        setBusy(true);
        setError("");
        const reason = new FormData(event.currentTarget).get("reason");
        action(String(reason ?? ""))
          .then(() => {
            toast.success("Perubahan berhasil disimpan.");
            onClose();
          })
          .catch((failure: unknown) => {
            setError(academicError(failure));
            setBusy(false);
          });
      }}
    >
      <ReasonField disabled={busy} />
      {note && (
        <p className="text-xs leading-5 text-muted-foreground">{note}</p>
      )}
    </ConfirmationDialog>
  );
}
