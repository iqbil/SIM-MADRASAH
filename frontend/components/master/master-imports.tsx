"use client";

import { Download, FileUp, RefreshCw } from "lucide-react";
import {
  type FormEvent,
  useCallback,
  useEffect,
  useId,
  useRef,
  useState,
} from "react";
import { toast } from "sonner";
import { ConfirmationDialog } from "@/components/layout/confirmation-dialog";
import { Pager } from "@/components/layout/pager";
import { ReasonField } from "@/components/layout/reason-field";
import {
  MasterFailure,
  MasterHeading,
  MasterLoading,
} from "@/components/master/master-feedback";
import { Alert, AlertDescription } from "@/components/ui/alert";
import { Badge } from "@/components/ui/badge";
import { Button } from "@/components/ui/button";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
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
  Sheet,
  SheetContent,
  SheetDescription,
  SheetFooter,
  SheetHeader,
  SheetTitle,
} from "@/components/ui/sheet";
import { useAsyncData } from "@/hooks/use-async-data";
import { useDialogFocus } from "@/hooks/use-dialog-focus";
import { formatDate } from "@/lib/accounts";
import { AuthRequestError } from "@/lib/auth/client";
import {
  downloadMaster,
  type MasterPage,
  masterError,
  masterRequest,
  PERSON_FIELDS,
  RESOURCE_CONFIG,
  type Resource,
} from "@/lib/master-data";

type Batch = {
  id: number;
  module: string;
  status: string;
  updated_at: string;
  created_at: string;
  total_rows: number;
  valid_rows: number;
  failed_rows: number;
  inserted_rows: number;
  updated_rows: number;
  source_system: string;
};
type ImportRow = {
  id: number;
  row_number: number;
  external_id: string | null;
  status: string;
  result_action: string | null;
  values: Record<string, unknown> | null;
  errors: Record<string, string[]> | null;
  duplicate_candidates: { id: number; full_name: string }[] | null;
};
const resources: Resource[] = [
  "students",
  "guardians",
  "employees",
  "subjects",
];
const statusLabels: Record<string, string> = {
  uploaded: "Menunggu validasi",
  validating: "Sedang divalidasi",
  preview_ready: "Pratinjau tersedia",
  importing: "Sedang diimpor",
  completed: "Selesai",
  failed: "Gagal",
  cancelled: "Dibatalkan",
};
const actions: Record<string, string> = {
  insert: "Tambah data",
  update: "Perbarui data",
  unchanged: "Tidak berubah",
};
const rowLabels: Record<string, string> = {
  pending: "Menunggu",
  valid: "Valid",
  invalid: "Perlu diperbaiki",
  imported: "Diimpor",
  skipped: "Tidak berubah",
  failed: "Gagal",
};
const activeBatch = (batch: Batch) =>
  ["uploaded", "validating", "importing"].includes(batch.status);
const batchResource = (batch: Batch) =>
  batch.module.replace("master.", "") as Resource;

export function MasterImports() {
  const id = useId();
  const formRef = useRef<HTMLFormElement>(null);
  const uploadErrorRef = useRef<HTMLDivElement>(null);
  const operationKey = useRef<string | null>(null);
  const [resource, setResource] = useState<Resource>("students");
  const [busy, setBusy] = useState(false);
  const [error, setError] = useState("");
  const [listError, setListError] = useState("");
  const [loading, setLoading] = useState(true);
  const [downloading, setDownloading] = useState(false);
  const [page, setPage] = useState(1);
  const [result, setResult] = useState<MasterPage<Batch>>();
  const [selected, setSelected] = useState<Batch>();
  const [confirming, setConfirming] = useState(false);
  const load = useCallback(async () => {
    setListError("");
    try {
      setResult(await masterRequest<MasterPage<Batch>>(`imports?page=${page}`));
    } catch (reason) {
      setListError(masterError(reason));
    } finally {
      setLoading(false);
    }
  }, [page]);
  useEffect(() => {
    setLoading(true);
    void load();
  }, [load]);
  useEffect(() => {
    if (error) uploadErrorRef.current?.focus();
  }, [error]);
  const pending = result?.data.some(activeBatch);
  useEffect(() => {
    if (!pending) return;
    const timer = window.setInterval(() => {
      if (document.visibilityState === "visible") void load();
    }, 3000);
    return () => window.clearInterval(timer);
  }, [pending, load]);
  async function upload(event: FormEvent<HTMLFormElement>) {
    event.preventDefault();
    if (busy) return;
    const data = new FormData(event.currentTarget);
    const file = data.get("file");
    if (
      !(file instanceof File) ||
      !file.size ||
      file.size > 10 * 1024 * 1024 ||
      !file.name.toLowerCase().endsWith(".csv")
    ) {
      setError("Pilih berkas CSV UTF-8 maksimal 10 MB.");
      return;
    }
    setBusy(true);
    setError("");
    operationKey.current ??= crypto.randomUUID();
    data.set("operation_key", operationKey.current);
    data.set("resource", resource);
    try {
      const batch = (
        await masterRequest<{ data: Batch }>("imports", "POST", data)
      ).data;
      setSelected(batch);
      setConfirming(false);
      toast.success("Berkas diterima. Pratinjau sedang disiapkan.");
      formRef.current?.reset();
      operationKey.current = null;
      if (page === 1) void load();
      else setPage(1);
    } catch (reason) {
      setError(masterError(reason));
    } finally {
      setBusy(false);
    }
  }
  async function template() {
    setDownloading(true);
    try {
      await downloadMaster(
        `imports/templates/${resource}`,
        `template-${resource}.csv`,
      );
    } catch (reason) {
      toast.error(masterError(reason));
    } finally {
      setDownloading(false);
    }
  }
  return (
    <div className="animate-enter space-y-6">
      <MasterHeading
        title="Impor data"
        description="Unggah data melalui template, periksa pratinjau, lalu konfirmasikan. Satu baris invalid menghalangi impor seluruh berkas."
      />
      <Card className="min-w-0 rounded-xl border ring-0">
        <CardHeader>
          <CardTitle>
            <h2 className="text-base font-semibold">Siapkan berkas impor</h2>
          </CardTitle>
        </CardHeader>
        <CardContent>
          <form
            ref={formRef}
            onSubmit={upload}
            aria-busy={busy}
            className="space-y-5"
          >
            <fieldset disabled={busy} className="min-w-0 space-y-5">
              {error && (
                <Alert variant="destructive" tabIndex={-1} ref={uploadErrorRef}>
                  <AlertDescription>{error}</AlertDescription>
                </Alert>
              )}
              <div className="grid min-w-0 gap-4 sm:grid-cols-2">
                <div className="space-y-2">
                  <Label htmlFor={`${id}-resource`}>Jenis data</Label>
                  <Select
                    value={resource}
                    onValueChange={(value) => {
                      setResource(value as Resource);
                      operationKey.current = null;
                    }}
                  >
                    <SelectTrigger
                      id={`${id}-resource`}
                      className="h-11! w-full text-base"
                    >
                      <SelectValue />
                    </SelectTrigger>
                    <SelectContent>
                      {resources.map((value) => (
                        <SelectItem key={value} value={value}>
                          {RESOURCE_CONFIG[value].title}
                        </SelectItem>
                      ))}
                    </SelectContent>
                  </Select>
                </div>
                <div className="space-y-2">
                  <Label htmlFor={`${id}-source`}>Sumber data</Label>
                  <Input
                    id={`${id}-source`}
                    name="source_system"
                    defaultValue="manual_csv"
                    required
                    pattern="[a-zA-Z0-9_.-]+"
                    maxLength={100}
                    className="h-11 text-base"
                    onChange={() => {
                      operationKey.current = null;
                    }}
                    aria-describedby={`${id}-source-hint`}
                  />
                  <p
                    id={`${id}-source-hint`}
                    className="text-xs leading-5 text-muted-foreground"
                  >
                    Gunakan nama sumber yang sama pada impor berikutnya,
                    misalnya emis.
                  </p>
                </div>
              </div>
              <Button
                type="button"
                variant="outline"
                className="min-h-11 w-full sm:w-auto"
                disabled={downloading}
                onClick={() => void template()}
              >
                <Download className="size-4" aria-hidden="true" />
                {downloading ? "Mengunduh…" : "Unduh template CSV"}
              </Button>
              <div className="space-y-2">
                <Label htmlFor={`${id}-file`}>Berkas CSV</Label>
                <Input
                  id={`${id}-file`}
                  name="file"
                  type="file"
                  accept=".csv,text/csv"
                  required
                  className="h-12 min-w-0 text-sm"
                  onChange={() => {
                    operationKey.current = null;
                  }}
                  aria-describedby={`${id}-file-hint`}
                />
                <p
                  id={`${id}-file-hint`}
                  className="text-xs leading-5 text-muted-foreground"
                >
                  UTF-8, maksimal 10 MB dan 5.000 baris. Pertahankan urutan
                  kolom template. Isi external_id yang stabil untuk mencegah
                  duplikasi saat impor ulang.
                </p>
              </div>
              <ReasonField disabled={busy} />
              <Button
                type="submit"
                className="min-h-11 w-full sm:w-auto"
                disabled={busy}
              >
                <FileUp className="size-4" aria-hidden="true" />
                {busy ? "Mengunggah…" : "Unggah & siapkan pratinjau"}
              </Button>
            </fieldset>
          </form>
        </CardContent>
      </Card>
      <Card className="rounded-xl border ring-0">
        <CardHeader className="flex flex-wrap items-center justify-between gap-3">
          <CardTitle>
            <h2 className="text-base font-semibold">Riwayat impor</h2>
          </CardTitle>
          <Button
            variant="outline"
            className="h-11"
            disabled={loading}
            onClick={() => void load()}
          >
            <RefreshCw className="size-4" aria-hidden="true" />
            Perbarui
          </Button>
        </CardHeader>
        <CardContent>
          {listError ? (
            <MasterFailure message={listError} retry={() => void load()} />
          ) : loading ? (
            <MasterLoading />
          ) : !result?.data.length ? (
            <p className="rounded-xl border border-dashed p-6 text-sm text-muted-foreground">
              Belum ada berkas yang diimpor.
            </p>
          ) : (
            <div className="space-y-3">
              {result.data.map((batch) => (
                <Card key={batch.id} className="rounded-xl border py-0 ring-0">
                  <CardContent className="flex min-w-0 flex-col justify-between gap-3 p-4 sm:flex-row sm:items-center">
                    <div className="min-w-0 space-y-2">
                      <p className="break-words text-sm font-medium">
                        {RESOURCE_CONFIG[batchResource(batch)]?.title ??
                          "Data master"}{" "}
                        · Impor #{batch.id}
                      </p>
                      <Badge variant="outline">
                        {statusLabels[batch.status] ?? batch.status}
                      </Badge>
                      <p className="break-words text-xs leading-5 text-muted-foreground">
                        {batch.total_rows} baris · {batch.source_system} ·{" "}
                        {formatDate(batch.created_at)} WIB
                      </p>
                    </div>
                    <Button
                      variant="outline"
                      className="h-11 shrink-0"
                      aria-label={`Lihat impor ${batch.id}`}
                      onClick={() => {
                        setSelected(batch);
                        setConfirming(false);
                      }}
                    >
                      Lihat pratinjau
                    </Button>
                  </CardContent>
                </Card>
              ))}
              <Pager
                meta={{
                  page: result.meta.current_page,
                  last_page: result.meta.last_page,
                  total: result.meta.total,
                }}
                setPage={setPage}
              />
            </div>
          )}
        </CardContent>
      </Card>
      {selected && !confirming && (
        <ImportPreview
          batch={selected}
          onClose={() => setSelected(undefined)}
          onUpdate={setSelected}
          onConfirm={() => setConfirming(true)}
        />
      )}
      {selected && confirming && (
        <ImportConfirmation
          batch={selected}
          onClose={() => setConfirming(false)}
          onSaved={(batch) => {
            setSelected(batch);
            setConfirming(false);
            void load();
          }}
        />
      )}
    </div>
  );
}
function ImportPreview({
  batch,
  onClose,
  onUpdate,
  onConfirm,
}: {
  batch: Batch;
  onClose: () => void;
  onUpdate: (batch: Batch) => void;
  onConfirm: () => void;
}) {
  const focus = useDialogFocus();
  const [page, setPage] = useState(1);
  const [downloading, setDownloading] = useState(false);
  const loader = useCallback(async () => {
    const [current, rows] = await Promise.all([
      masterRequest<{ data: Batch }>(`imports/${batch.id}`),
      masterRequest<MasterPage<ImportRow>>(
        `imports/${batch.id}/rows?page=${page}`,
      ),
    ]);
    return { batch: current.data, rows };
  }, [batch.id, page]);
  const { data, loading, error, reload } = useAsyncData(loader);
  const rows = data?.rows;
  useEffect(() => {
    if (data) onUpdate(data.batch);
  }, [data, onUpdate]);
  const polling = activeBatch(data?.batch ?? batch);
  useEffect(() => {
    if (!polling || error) return;
    const timer = window.setInterval(() => {
      if (document.visibilityState === "visible") void reload(true);
    }, 3000);
    return () => window.clearInterval(timer);
  }, [polling, error, reload]);
  async function errors() {
    setDownloading(true);
    try {
      await downloadMaster(
        `imports/${batch.id}/errors`,
        `impor-${batch.id}-kesalahan.csv`,
      );
    } catch (reason) {
      toast.error(masterError(reason));
    } finally {
      setDownloading(false);
    }
  }
  return (
    <Sheet
      open
      onOpenChange={(open) => {
        if (!open) onClose();
      }}
    >
      <SheetContent
        {...focus}
        className="flex w-full max-w-full flex-col gap-0 p-0 sm:max-w-2xl"
      >
        <SheetHeader className="shrink-0 border-b p-5 pr-14">
          <SheetTitle>Pratinjau impor #{batch.id}</SheetTitle>
          <SheetDescription>
            Periksa hasil validasi dan perubahan sebelum konfirmasi. Berkas
            belum mengubah data master sampai proses impor selesai.
          </SheetDescription>
        </SheetHeader>
        <div className="min-h-0 space-y-5 overflow-y-auto p-5">
          <Badge variant="outline">{statusLabels[batch.status]}</Badge>
          <div className="grid grid-cols-2 gap-3">
            {[
              ["Total baris", batch.total_rows],
              ["Valid", batch.valid_rows],
              ["Bermasalah", batch.failed_rows],
              [
                "Ditambahkan / diperbarui",
                `${batch.inserted_rows} / ${batch.updated_rows}`,
              ],
            ].map(([label, count]) => (
              <Card key={label} className="rounded-xl border py-0 ring-0">
                <CardContent className="p-3">
                  <p className="text-xs leading-5 text-muted-foreground">
                    {label}
                  </p>
                  <p className="mt-1 text-lg font-semibold">{count}</p>
                </CardContent>
              </Card>
            ))}
          </div>
          {activeBatch(batch) && (
            <Alert>
              <AlertDescription>
                Proses sedang berjalan di antrean. Status diperbarui otomatis
                selama halaman terbuka.
              </AlertDescription>
            </Alert>
          )}
          {batch.status === "completed" && (
            <Alert>
              <AlertDescription>
                Impor selesai. Baris yang sama tidak digandakan; data yang
                berubah diperbarui sesuai pratinjau.
              </AlertDescription>
            </Alert>
          )}
          {error ? (
            <MasterFailure message={error} retry={() => void reload()} />
          ) : loading ? (
            <MasterLoading />
          ) : (
            <>
              {rows?.data.map((row) => (
                <PreviewRow
                  key={row.id}
                  row={row}
                  resource={batchResource(batch)}
                />
              ))}
              {rows && rows.meta.total > 0 && (
                <Pager
                  meta={{
                    page: rows.meta.current_page,
                    last_page: rows.meta.last_page,
                    total: rows.meta.total,
                  }}
                  setPage={setPage}
                />
              )}
            </>
          )}
        </div>
        <SheetFooter className="shrink-0 gap-2 border-t p-4 pb-[max(1rem,env(safe-area-inset-bottom))]">
          <Button
            variant="outline"
            className="min-h-11"
            disabled={!batch.failed_rows || downloading || Boolean(error)}
            onClick={() => void errors()}
          >
            <Download className="size-4" aria-hidden="true" />
            {downloading ? "Mengunduh…" : "Unduh laporan kesalahan"}
          </Button>
          <Button
            className="min-h-11"
            disabled={
              batch.status !== "preview_ready" ||
              batch.failed_rows > 0 ||
              batch.valid_rows === 0 ||
              loading ||
              Boolean(error)
            }
            onClick={onConfirm}
          >
            Konfirmasi impor
          </Button>
        </SheetFooter>
      </SheetContent>
    </Sheet>
  );
}
function PreviewRow({ row, resource }: { row: ImportRow; resource: Resource }) {
  const values = row.values;
  const person = values?.person as Record<string, unknown> | undefined;
  const labels = new Map(
    [...PERSON_FIELDS, ...(RESOURCE_CONFIG[resource]?.fields ?? [])].map(
      (field) => [field.name, field.label],
    ),
  );
  const entries = values
    ? Object.entries(values)
        .flatMap(([key, value]) =>
          key === "person" && person
            ? Object.entries(person).map(
                ([name, item]) => [`person.${name}`, item] as const,
              )
            : [[key, value] as const],
        )
        .filter(([name]) => !name.startsWith("expected_"))
    : [];
  return (
    <Card className="rounded-xl border py-0 ring-0">
      <CardContent className="space-y-4 p-4">
        <div className="flex flex-wrap items-center justify-between gap-2">
          <h3 className="text-sm font-semibold">Baris {row.row_number}</h3>
          <Badge variant="outline">{rowLabels[row.status]}</Badge>
        </div>
        <p className="text-xs leading-5 text-muted-foreground">
          {row.result_action ? actions[row.result_action] : "Menunggu validasi"}{" "}
          · ID sumber:{" "}
          <span className="break-all">{row.external_id ?? "—"}</span>
        </p>
        <dl className="grid min-w-0 grid-cols-1 gap-3 sm:grid-cols-2">
          {entries.map(([name, value]) => (
            <div key={name}>
              <dt className="text-xs text-muted-foreground">
                {name === "person_id"
                  ? "Identitas terdaftar"
                  : (labels.get(name) ?? name)}
              </dt>
              <dd className="mt-1 break-words text-sm">
                {value == null || value === ""
                  ? "—"
                  : typeof value === "boolean"
                    ? value
                      ? "Ya"
                      : "Tidak"
                    : String(value)}
              </dd>
            </div>
          ))}
        </dl>
        {row.errors && Object.keys(row.errors).length > 0 && (
          <Alert variant="destructive">
            <AlertDescription>
              <ul className="list-disc space-y-1 pl-4">
                {Object.entries(row.errors).flatMap(([field, errors]) =>
                  errors.map((message) => (
                    <li key={`${field}-${message}`} className="break-words">
                      {labels.get(field) ? `${labels.get(field)}: ` : ""}
                      {message}
                    </li>
                  )),
                )}
              </ul>
            </AlertDescription>
          </Alert>
        )}
        {row.duplicate_candidates?.length ? (
          <p className="break-words text-sm">
            Kandidat identitas serupa:{" "}
            {row.duplicate_candidates
              .map((person) => person.full_name)
              .join(", ")}
            . Periksa dan gunakan person_id pada berkas yang diperbaiki.
          </p>
        ) : null}
      </CardContent>
    </Card>
  );
}
function ImportConfirmation({
  batch,
  onClose,
  onSaved,
}: {
  batch: Batch;
  onClose: () => void;
  onSaved: (batch: Batch) => void;
}) {
  const [busy, setBusy] = useState(false);
  const [error, setError] = useState("");
  const [stale, setStale] = useState(false);
  async function submit(event: FormEvent<HTMLFormElement>) {
    event.preventDefault();
    if (busy) return;
    setBusy(true);
    setError("");
    try {
      const result = await masterRequest<{ data: Batch }>(
        `imports/${batch.id}/confirm`,
        "POST",
        {
          reason: new FormData(event.currentTarget).get("reason"),
          expected_updated_at: batch.updated_at,
        },
      );
      toast.success("Konfirmasi diterima. Impor sedang diproses.");
      onSaved(result.data);
    } catch (reason) {
      setError(masterError(reason));
      setStale(
        reason instanceof AuthRequestError &&
          [409, 410].includes(reason.status),
      );
      setBusy(false);
    }
  }
  return (
    <ConfirmationDialog
      title="Konfirmasi impor data"
      description={`${batch.valid_rows} baris valid akan diproses sebagai satu berkas. Data yang sama tidak digandakan. Pastikan seluruh pratinjau telah diperiksa.`}
      busy={busy}
      error={error}
      disabled={stale}
      onClose={onClose}
      onSubmit={submit}
      submitLabel="Mulai impor"
    >
      <ReasonField disabled={busy} />
      {stale && (
        <p className="text-sm leading-6">
          Kembali ke pratinjau untuk memuat status terbaru.
        </p>
      )}
    </ConfirmationDialog>
  );
}
