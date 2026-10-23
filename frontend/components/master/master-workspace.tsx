"use client";

import { Plus, Search } from "lucide-react";
import { type FormEvent, useCallback, useEffect, useId, useState } from "react";
import { toast } from "sonner";
import { ConfirmationDialog } from "@/components/layout/confirmation-dialog";
import { Pager } from "@/components/layout/pager";
import { ReasonField } from "@/components/layout/reason-field";
import { MasterEditor } from "@/components/master/master-editor";
import {
  MasterEmpty,
  MasterFailure,
  MasterHeading,
  MasterLoading,
} from "@/components/master/master-feedback";
import { MasterImports } from "@/components/master/master-imports";
import { MasterRelations } from "@/components/master/master-relations";
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
  Sheet,
  SheetContent,
  SheetDescription,
  SheetHeader,
  SheetTitle,
} from "@/components/ui/sheet";
import { Skeleton } from "@/components/ui/skeleton";
import {
  Table,
  TableBody,
  TableCell,
  TableHead,
  TableHeader,
  TableRow,
} from "@/components/ui/table";
import { Tabs, TabsContent, TabsList, TabsTrigger } from "@/components/ui/tabs";
import { useAsyncData } from "@/hooks/use-async-data";
import { useDialogFocus } from "@/hooks/use-dialog-focus";
import { formatDate } from "@/lib/accounts";
import { AuthRequestError } from "@/lib/auth/client";
import {
  dateOnly,
  fieldValue,
  hasPerson,
  MASTER_SECTIONS,
  type MasterOptions,
  type MasterPage,
  type MasterRecord,
  type MasterSection,
  masterError,
  masterRequest,
  PERSON_FIELDS,
  RESOURCE_CONFIG,
  type Resource,
  recordActive,
  recordCode,
  recordName,
} from "@/lib/master-data";

export function MasterWorkspace({ section }: { section: MasterSection }) {
  const config = MASTER_SECTIONS[section];
  const { data: options, loading: optionsLoading } = useAsyncData(
    useCallback(
      () =>
        masterRequest<{ data: MasterOptions }>("options")
          .then((res) => res.data)
          .catch(() => null),
      [],
    ),
  );

  if (section === "imports") return <MasterImports />;
  if (section === "institution")
    return (
      <Institution
        options={options ?? undefined}
        loadingOptions={optionsLoading}
      />
    );

  const configuredResources = config.resources as Resource[];
  const allowedResources = options?.resources
    ? configuredResources.filter((r) => options.resources?.includes(r))
    : configuredResources;
  const resources =
    allowedResources.length > 0 ? allowedResources : configuredResources;

  if (resources.length === 1)
    return (
      <MasterList
        resource={resources[0]}
        options={options ?? undefined}
        loadingOptions={optionsLoading}
      />
    );

  return (
    <Tabs
      key={resources.join("-")}
      defaultValue={resources[0]}
      className="space-y-6"
    >
      <TabsList className="grid h-auto w-full grid-cols-2 gap-1 rounded-xl p-1 sm:max-w-lg">
        {resources.map((resource) => (
          <TabsTrigger
            key={resource}
            value={resource}
            className="min-h-11 whitespace-normal text-sm"
          >
            {RESOURCE_CONFIG[resource].title}
          </TabsTrigger>
        ))}
      </TabsList>
      {resources.map((resource) => (
        <TabsContent key={resource} value={resource}>
          <MasterList
            resource={resource}
            pageTitle={config.title}
            options={options ?? undefined}
            loadingOptions={optionsLoading}
          />
        </TabsContent>
      ))}
    </Tabs>
  );
}

type Overlay =
  | { kind: "edit"; record?: MasterRecord }
  | { kind: "detail" | "status" | "relations"; record: MasterRecord }
  | null;
function MasterList({
  resource,
  pageTitle,
  options,
  loadingOptions,
}: {
  resource: Resource;
  pageTitle?: string;
  options?: MasterOptions;
  loadingOptions?: boolean;
}) {
  const config = RESOURCE_CONFIG[resource];
  const focus = useDialogFocus();
  const searchId = useId();
  const filterId = useId();
  const [query, setQuery] = useState("");
  const [search, setSearch] = useState("");
  const [active, setActive] = useState("all");
  const [domain, setDomain] = useState("all");
  const domainId = useId();
  const [page, setPage] = useState(1);
  const [opening, setOpening] = useState<number>();
  const [overlay, setOverlay] = useState<Overlay>(null);
  const [editorRevision, setEditorRevision] = useState(0);

  const canManage = options
    ? (options.capabilities?.[resource]?.manage ?? options.can_manage)
    : false;
  const canStatus = options
    ? (options.capabilities?.[resource]?.status ?? options.can_manage)
    : false;

  const loader = useCallback(() => {
    const params = new URLSearchParams({ page: String(page), q: search });
    if (active !== "all") params.set("is_active", active);
    if (resource === "attendance-statuses" && domain !== "all")
      params.set("domain", domain);
    return masterRequest<MasterPage>(`${resource}?${params}`);
  }, [resource, page, search, active, domain]);
  const { data: result, loading, error, reload } = useAsyncData(loader);
  const refresh = () => {
    void reload();
  };
  async function inspect(id: number, kind: "detail" | "edit" = "detail") {
    if (kind === "edit" && !canManage) return;
    setOpening(id);
    try {
      const result = await masterRequest<{ data: MasterRecord }>(
        `${resource}/${id}`,
      );
      setOverlay({ kind, record: result.data });
      if (kind === "edit") setEditorRevision((value) => value + 1);
    } catch (reason) {
      toast.error(masterError(reason));
    } finally {
      setOpening(undefined);
    }
  }
  function saved() {
    setOverlay(null);
    refresh();
  }
  function filter(event: FormEvent) {
    event.preventDefault();
    setPage(1);
    setSearch(query.trim());
  }
  const filtered = Boolean(search || active !== "all" || domain !== "all");
  return (
    <div className="animate-enter space-y-6">
      <MasterHeading
        title={pageTitle ?? config.title}
        description={config.description}
        action={
          canManage ? (
            <Button
              className="h-11"
              onClick={() => setOverlay({ kind: "edit" })}
            >
              <Plus className="size-4" aria-hidden="true" />
              Tambah {config.singular}
            </Button>
          ) : undefined
        }
      />
      <Card className="min-w-0 rounded-xl border py-0 ring-0">
        <CardContent className="space-y-5 p-4 sm:p-6">
          <div className="flex flex-wrap items-center justify-between gap-3">
            <h2 className="text-base font-semibold">
              Daftar {config.singular}
            </h2>
            {loading ? (
              <Skeleton className="h-6 w-20" />
            ) : result && !error ? (
              <Badge variant="secondary" className="text-xs">
                {result.meta.total} data
              </Badge>
            ) : null}
          </div>
          <div className="flex min-w-0 flex-col gap-3 lg:flex-row lg:items-end">
            <form onSubmit={filter} className="min-w-0 flex-1 space-y-2">
              <Label htmlFor={searchId}>Cari {config.singular}</Label>
              <div className="flex gap-2">
                <div className="relative min-w-0 flex-1">
                  <Search
                    className="pointer-events-none absolute top-3.5 left-3 size-4 text-muted-foreground"
                    aria-hidden="true"
                  />
                  <Input
                    id={searchId}
                    value={query}
                    maxLength={100}
                    onChange={(event) => setQuery(event.target.value)}
                    className="h-11 pl-10 text-base"
                    placeholder="Nama atau kode…"
                  />
                </div>
                <Button type="submit" variant="outline" className="h-11">
                  Cari
                </Button>
              </div>
            </form>
            <div className="space-y-2 lg:w-48">
              <Label htmlFor={filterId}>Status data</Label>
              <Select
                value={active}
                onValueChange={(value) => {
                  setActive(value);
                  setPage(1);
                }}
              >
                <SelectTrigger id={filterId} className="h-11! w-full text-base">
                  <SelectValue />
                </SelectTrigger>
                <SelectContent>
                  <SelectItem value="all">Semua status</SelectItem>
                  <SelectItem value="1">Aktif</SelectItem>
                  <SelectItem value="0">
                    {resource === "students" ? "Diarsipkan" : "Nonaktif"}
                  </SelectItem>
                </SelectContent>
              </Select>
            </div>
            {resource === "attendance-statuses" && (
              <div className="space-y-2 lg:w-48">
                <Label htmlFor={domainId}>Jenis kehadiran</Label>
                <Select
                  value={domain}
                  onValueChange={(value) => {
                    setDomain(value);
                    setPage(1);
                  }}
                >
                  <SelectTrigger
                    id={domainId}
                    className="h-11! w-full text-base"
                  >
                    <SelectValue />
                  </SelectTrigger>
                  <SelectContent>
                    <SelectItem value="all">Pegawai & siswa</SelectItem>
                    <SelectItem value="employee">Pegawai</SelectItem>
                    <SelectItem value="student">Siswa</SelectItem>
                  </SelectContent>
                </Select>
              </div>
            )}
          </div>
          {error ? (
            <MasterFailure message={error} retry={refresh} />
          ) : loading ? (
            <MasterLoading />
          ) : !result?.data.length ? (
            <MasterEmpty
              search={filtered}
              reset={() => {
                setQuery("");
                setSearch("");
                setActive("all");
                setDomain("all");
                setPage(1);
              }}
            />
          ) : (
            <>
              <div className="space-y-3 md:hidden">
                {result.data.map((record) => (
                  <Card
                    key={record.id}
                    className="rounded-xl border py-0 ring-0"
                  >
                    <CardContent className="space-y-4 p-4">
                      <div className="flex min-w-0 items-start justify-between gap-3">
                        <p className="min-w-0 break-words text-sm font-semibold">
                          {recordName(record)}
                        </p>
                        <StatusBadge record={record} />
                      </div>
                      <p className="break-all text-xs text-muted-foreground">
                        {recordCode(record)}
                        {resource === "attendance-statuses"
                          ? ` · ${record.domain === "employee" ? "Pegawai" : "Siswa"}`
                          : ""}
                      </p>
                      <Button
                        variant="outline"
                        className="h-11 w-full"
                        aria-label={`${opening === record.id ? "Memuat…" : loadingOptions ? "Memuat…" : canManage ? "Kelola" : "Lihat"} ${recordName(record)}`}
                        disabled={opening !== undefined}
                        onClick={() => void inspect(record.id)}
                      >
                        {opening === record.id
                          ? "Memuat…"
                          : loadingOptions
                            ? "Memuat…"
                            : canManage
                              ? "Kelola"
                              : "Lihat"}
                      </Button>
                    </CardContent>
                  </Card>
                ))}
              </div>
              <div className="hidden min-w-0 md:block">
                <Table>
                  <TableHeader>
                    <TableRow>
                      <TableHead>Nama</TableHead>
                      <TableHead>Kode / nomor</TableHead>
                      <TableHead>Status data</TableHead>
                      <TableHead className="text-right">
                        {loadingOptions
                          ? "Tindakan"
                          : canManage
                            ? "Tindakan"
                            : "Rincian"}
                      </TableHead>
                    </TableRow>
                  </TableHeader>
                  <TableBody>
                    {result.data.map((record) => (
                      <TableRow key={record.id}>
                        <TableCell className="max-w-80 break-words whitespace-normal font-medium">
                          {recordName(record)}
                        </TableCell>
                        <TableCell className="max-w-48 break-all whitespace-normal">
                          {recordCode(record)}
                          {resource === "attendance-statuses"
                            ? ` · ${record.domain === "employee" ? "Pegawai" : "Siswa"}`
                            : ""}
                        </TableCell>
                        <TableCell>
                          <StatusBadge record={record} />
                        </TableCell>
                        <TableCell className="text-right">
                          <Button
                            variant="outline"
                            className="h-11"
                            aria-label={`${opening === record.id ? "Memuat…" : loadingOptions ? "Memuat…" : canManage ? "Kelola" : "Lihat"} ${recordName(record)}`}
                            disabled={opening !== undefined}
                            onClick={() => void inspect(record.id)}
                          >
                            {opening === record.id
                              ? "Memuat…"
                              : loadingOptions
                                ? "Memuat…"
                                : canManage
                                  ? "Kelola"
                                  : "Lihat"}
                          </Button>
                        </TableCell>
                      </TableRow>
                    ))}
                  </TableBody>
                </Table>
              </div>
              <Pager
                meta={{
                  page: result.meta.current_page,
                  last_page: result.meta.last_page,
                  total: result.meta.total,
                }}
                setPage={setPage}
                busy={loading}
              />
            </>
          )}
        </CardContent>
      </Card>
      {overlay?.kind === "edit" && canManage && (
        <MasterEditor
          key={`${overlay.record?.updated_at ?? "new"}-${editorRevision}`}
          resource={resource}
          current={overlay.record}
          onClose={() => setOverlay(null)}
          onSaved={saved}
          onReload={() => {
            if (overlay.record) void inspect(overlay.record.id, "edit");
          }}
        />
      )}
      {overlay?.kind === "detail" && (
        <Sheet
          open
          onOpenChange={(open) => {
            if (!open) setOverlay(null);
          }}
        >
          <SheetContent
            {...focus}
            className="flex w-full max-w-full flex-col gap-0 p-0 sm:max-w-lg"
          >
            <SheetHeader className="shrink-0 border-b p-5 pr-14">
              <SheetTitle className="break-words">
                {recordName(overlay.record)}
              </SheetTitle>
              <SheetDescription>
                {canManage || canStatus
                  ? `Rincian ${config.singular} dan tindakan pengelolaan.`
                  : `Rincian data ${config.singular}.`}
              </SheetDescription>
            </SheetHeader>
            <div className="min-h-0 space-y-6 overflow-y-auto p-5">
              <StatusBadge record={overlay.record} />
              <RecordDetails record={overlay.record} resource={resource} />
              {(canManage || canStatus) && (
                <div className="flex flex-col gap-2">
                  {canManage && (
                    <Button
                      variant="outline"
                      className="min-h-11"
                      onClick={() =>
                        setOverlay({ kind: "edit", record: overlay.record })
                      }
                    >
                      Ubah data
                    </Button>
                  )}
                  {canManage &&
                    ["students", "employees"].includes(resource) && (
                      <Button
                        variant="outline"
                        className="min-h-11"
                        onClick={() =>
                          setOverlay({
                            kind: "relations",
                            record: overlay.record,
                          })
                        }
                      >
                        {resource === "students"
                          ? "Kelola hubungan wali"
                          : "Kelola kategori & jabatan"}
                      </Button>
                    )}
                  {canStatus && (
                    <Button
                      variant="outline"
                      className="min-h-11"
                      disabled={
                        resource === "student-statuses" &&
                        overlay.record.code === "active"
                      }
                      onClick={() =>
                        setOverlay({ kind: "status", record: overlay.record })
                      }
                    >
                      {recordActive(overlay.record)
                        ? resource === "students"
                          ? "Arsipkan siswa"
                          : "Nonaktifkan data"
                        : "Aktifkan kembali"}
                    </Button>
                  )}
                </div>
              )}
            </div>
          </SheetContent>
        </Sheet>
      )}
      {overlay?.kind === "status" && canStatus && (
        <MasterStatus
          resource={resource}
          record={overlay.record}
          onClose={() => setOverlay(null)}
          onSaved={saved}
        />
      )}
      {overlay?.kind === "relations" && canManage && (
        <MasterRelations
          resource={resource}
          owner={overlay.record}
          onClose={() => {
            setOverlay(null);
            refresh();
          }}
        />
      )}
    </div>
  );
}
export function StatusBadge({ record }: { record: MasterRecord }) {
  const active = recordActive(record);
  return (
    <Badge
      variant={active ? "secondary" : "outline"}
      className="shrink-0 text-xs"
    >
      {active ? "Aktif" : record.archived_at ? "Diarsipkan" : "Nonaktif"}
    </Badge>
  );
}
function RecordDetails({
  resource,
  record,
}: {
  resource: Resource;
  record: MasterRecord;
}) {
  const fields = [
    ...(hasPerson(resource) ? PERSON_FIELDS : []),
    ...RESOURCE_CONFIG[resource].fields,
  ];
  return (
    <dl className="grid min-w-0 grid-cols-1 gap-5 sm:grid-cols-2">
      {fields.map((field) => {
        const value = fieldValue(record, field.name);
        const display =
          field.type === "date"
            ? dateOnly(value)
            : field.type === "boolean"
              ? value
                ? "Ya"
                : "Tidak"
              : field.options
                ? (field.options.find((option) => option.value === value)
                    ?.label ?? "—")
                : String(value ?? "—");
        return (
          <div
            key={field.name}
            className={field.type === "textarea" ? "sm:col-span-2" : ""}
          >
            <dt className="text-xs text-muted-foreground">{field.label}</dt>
            <dd className="mt-1.5 break-words whitespace-pre-wrap text-sm font-medium">
              {display || "—"}
            </dd>
          </div>
        );
      })}
      <div className="sm:col-span-2">
        <dt className="text-xs text-muted-foreground">
          Terakhir diperbarui (WIB)
        </dt>
        <dd className="mt-1.5 text-sm">{formatDate(record.updated_at)}</dd>
      </div>
    </dl>
  );
}
function MasterStatus({
  resource,
  record,
  onClose,
  onSaved,
}: {
  resource: Resource;
  record: MasterRecord;
  onClose: () => void;
  onSaved: () => void;
}) {
  const [busy, setBusy] = useState(false);
  const [error, setError] = useState("");
  const [stale, setStale] = useState(false);
  const active = recordActive(record);
  async function submit(event: FormEvent<HTMLFormElement>) {
    event.preventDefault();
    if (busy) return;
    setBusy(true);
    setError("");
    try {
      await masterRequest(`${resource}/${record.id}/status`, "POST", {
        is_active: !active,
        expected_updated_at: record.updated_at,
        reason: new FormData(event.currentTarget).get("reason"),
      });
      toast.success("Status data berhasil diperbarui.");
      onSaved();
    } catch (reason) {
      setError(masterError(reason));
      setStale(reason instanceof AuthRequestError && reason.status === 409);
      setBusy(false);
    }
  }
  return (
    <ConfirmationDialog
      title={
        active
          ? resource === "students"
            ? "Arsipkan siswa"
            : "Nonaktifkan data"
          : "Aktifkan kembali"
      }
      description={`${recordName(record)}. ${active ? "Data tidak dihapus. Riwayat transaksi tetap tersimpan." : "Data akan kembali tersedia untuk proses berikutnya."}`}
      busy={busy}
      error={error}
      destructive={active}
      disabled={stale}
      onClose={onClose}
      onSubmit={submit}
    >
      <ReasonField disabled={busy} />
      {stale && (
        <p className="text-sm leading-6 text-muted-foreground">
          Tutup dialog dan buka kembali detail untuk memuat data terbaru.
        </p>
      )}
    </ConfirmationDialog>
  );
}
function Institution({
  options,
  loadingOptions,
}: {
  options?: MasterOptions;
  loadingOptions?: boolean;
}) {
  const [record, setRecord] = useState<MasterRecord | null>();
  const [error, setError] = useState("");
  const [loading, setLoading] = useState(true);
  const [editing, setEditing] = useState(false);
  const [revision, setRevision] = useState(0);

  const canManage = options
    ? (options.capabilities?.institution?.manage ?? options.can_manage)
    : false;

  const load = useCallback(async () => {
    setLoading(true);
    setError("");
    try {
      setRecord(
        (await masterRequest<{ data: MasterRecord | null }>("institution"))
          .data,
      );
      return true;
    } catch (reason) {
      setError(masterError(reason));
      return false;
    } finally {
      setLoading(false);
    }
  }, []);
  useEffect(() => {
    void load();
  }, [load]);
  const config = RESOURCE_CONFIG.institution;
  return (
    <div className="animate-enter space-y-6">
      <MasterHeading
        title={config.title}
        description={config.description}
        action={
          canManage ? (
            <Button
              className="h-11"
              disabled={loading || Boolean(loadingOptions) || Boolean(error)}
              onClick={() => setEditing(true)}
            >
              {record ? "Ubah identitas" : "Lengkapi identitas"}
            </Button>
          ) : undefined
        }
      />
      {error ? (
        <MasterFailure message={error} retry={() => void load()} />
      ) : loading ? (
        <MasterLoading />
      ) : record ? (
        <Card className="rounded-xl border ring-0">
          <CardContent className="p-5 sm:p-6">
            <RecordDetails resource="institution" record={record} />
          </CardContent>
        </Card>
      ) : (
        <MasterEmpty />
      )}
      {editing && canManage && (
        <MasterEditor
          key={`${record?.updated_at ?? "new"}-${revision}`}
          resource="institution"
          current={record ?? undefined}
          onClose={() => setEditing(false)}
          onSaved={() => {
            setEditing(false);
            void load();
          }}
          onReload={() => {
            void load().then((success) => {
              if (success) setRevision((value) => value + 1);
            });
          }}
        />
      )}
    </div>
  );
}
