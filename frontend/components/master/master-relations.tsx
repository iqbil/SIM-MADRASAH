"use client";

import { Plus } from "lucide-react";
import { type FormEvent, useCallback, useState } from "react";
import { toast } from "sonner";
import { ConfirmationDialog } from "@/components/layout/confirmation-dialog";
import { FormDialog } from "@/components/layout/form-dialog";
import { Pager } from "@/components/layout/pager";
import { ReasonField } from "@/components/layout/reason-field";
import {
  MasterFailure,
  MasterLoading,
} from "@/components/master/master-feedback";
import { MasterField, RecordPicker } from "@/components/master/master-fields";
import { Badge } from "@/components/ui/badge";
import { Button } from "@/components/ui/button";
import { Card, CardContent } from "@/components/ui/card";
import { Checkbox } from "@/components/ui/checkbox";
import { Label } from "@/components/ui/label";
import { Tabs, TabsList, TabsTrigger } from "@/components/ui/tabs";
import { useAsyncData } from "@/hooks/use-async-data";
import { AuthRequestError } from "@/lib/auth/client";
import type { ApiError } from "@/lib/auth/types";
import {
  dateOnly,
  type MasterPage,
  type MasterRecord,
  masterError,
  masterRequest,
  type Resource,
  recordName,
} from "@/lib/master-data";

type Relation = "guardian-links" | "categories" | "positions";
type LinkRecord = MasterRecord & {
  guardian_id?: number;
  category_id?: number;
  position_id?: number;
  effective_from: string;
  effective_to: string | null;
  verification_status?: string;
  is_primary?: boolean;
  revoked_at?: string | null;
};
const relationConfig = {
  "guardian-links": {
    title: "Hubungan wali",
    target: "guardians",
    field: "guardian_id",
    label: "Wali",
    empty: "Belum ada hubungan wali yang tercatat.",
  },
  categories: {
    title: "Riwayat kategori",
    target: "employee-categories",
    field: "category_id",
    label: "Kategori pegawai",
    empty: "Belum ada penetapan kategori pegawai.",
  },
  positions: {
    title: "Riwayat jabatan",
    target: "positions",
    field: "position_id",
    label: "Jabatan",
    empty: "Belum ada penetapan jabatan pegawai.",
  },
};
const verificationLabels: Record<string, string> = {
  pending: "Belum diverifikasi",
  verified: "Terverifikasi",
  rejected: "Ditolak",
  revoked: "Dicabut",
};
const relationships: Record<string, string> = {
  father: "Ayah",
  mother: "Ibu",
  legal_guardian: "Wali sah",
  other: "Lainnya",
};

export function MasterRelations({
  resource,
  owner,
  onClose,
}: {
  resource: Resource;
  owner: MasterRecord;
  onClose: () => void;
}) {
  const [relation, setRelation] = useState<Relation>(
    resource === "students" ? "guardian-links" : "categories",
  );
  const [mode, setMode] = useState<
    "list" | "create" | "verify" | "revoke" | "end"
  >("list");
  const [selected, setSelected] = useState<LinkRecord>();
  const [page, setPage] = useState(1);
  const [target, setTarget] = useState("");
  const [targetLabel, setTargetLabel] = useState("");
  const [busy, setBusy] = useState(false);
  const [failure, setFailure] = useState<ApiError>({});
  const [stale, setStale] = useState(false);
  const [confirmed, setConfirmed] = useState(false);
  const [startDate, setStartDate] = useState("");
  const config = relationConfig[relation];
  const base = `${resource}/${owner.id}/${relation}`;
  const loader = useCallback(async () => {
    const data = await masterRequest<MasterPage<LinkRecord>>(
      `${base}?page=${page}`,
    );
    const ids = [...new Set(data.data.map((row) => Number(row[config.field])))];
    const names = await Promise.all(
      ids.map(
        async (id) =>
          [
            id,
            recordName(
              (
                await masterRequest<{ data: MasterRecord }>(
                  `${config.target}/${id}`,
                )
              ).data,
            ),
          ] as const,
      ),
    );
    return {
      ...data,
      labels: Object.fromEntries(names) as Record<number, string>,
    };
  }, [base, page, config.field, config.target]);
  const {
    data: result,
    loading,
    error: listError,
    reload,
  } = useAsyncData(loader);
  const labels = result?.labels ?? {};
  const ownerVersion = result?.meta.owner_updated_at ?? owner.updated_at;
  function reset() {
    setMode("list");
    setFailure({});
    setStale(false);
    setConfirmed(false);
    setTarget("");
    setTargetLabel("");
    void reload();
  }
  function act(action: typeof mode, row?: LinkRecord) {
    setSelected(row);
    setMode(action);
    setFailure({});
    setStale(false);
    setConfirmed(false);
    setTarget("");
    setTargetLabel("");
    setStartDate(
      new Intl.DateTimeFormat("en-CA", {
        timeZone: "Asia/Jakarta",
        year: "numeric",
        month: "2-digit",
        day: "2-digit",
      }).format(new Date()),
    );
  }
  async function submit(event: FormEvent<HTMLFormElement>) {
    event.preventDefault();
    if (busy || mode === "list") return;
    setBusy(true);
    setFailure({});
    const data = new FormData(event.currentTarget);
    const body: Record<string, unknown> = {
      reason: data.get("reason"),
      expected_updated_at: ownerVersion,
    };
    if (mode === "create") {
      if (!target) {
        setFailure({
          message: `Pilih ${config.label.toLowerCase()} terlebih dahulu.`,
        });
        setBusy(false);
        return;
      }
      body[config.field] = Number(target);
      body.effective_from = data.get("effective_from");
      body.effective_to = data.get("effective_to") || null;
      if (relation === "guardian-links") {
        body.relationship = data.get("relationship");
        body.is_primary = data.get("is_primary") === "on";
      }
      if (relation === "positions")
        body.appointment_number = data.get("appointment_number") || null;
    } else {
      body.expected_link_updated_at = selected?.updated_at;
      if (mode === "verify") body.identity_confirmation = confirmed;
      if (mode === "end") body.effective_to = data.get("effective_to");
    }
    try {
      await masterRequest(
        mode === "create" ? base : `${base}/${selected?.id}/${mode}`,
        "POST",
        body,
      );
      toast.success(
        mode === "verify"
          ? "Hubungan wali berhasil diverifikasi."
          : mode === "revoke"
            ? "Hubungan wali berhasil dicabut."
            : "Riwayat berhasil disimpan.",
      );
      reset();
    } catch (reason) {
      setFailure(
        reason instanceof AuthRequestError
          ? reason.payload
          : { message: masterError(reason) },
      );
      setStale(reason instanceof AuthRequestError && reason.status === 409);
    } finally {
      setBusy(false);
    }
  }
  const modeTitle = {
    list: config.title,
    create: `Tambah ${config.label.toLowerCase()}`,
    verify: "Verifikasi hubungan wali",
    revoke: "Cabut hubungan wali",
    end: "Akhiri riwayat",
  };
  const content = (
    <>
      {mode === "list" ? (
        <>
          {resource === "employees" && (
            <Tabs
              value={relation}
              onValueChange={(value) => {
                setRelation(value as Relation);
                setPage(1);
              }}
            >
              <TabsList className="grid h-auto w-full grid-cols-2">
                <TabsTrigger className="min-h-11" value="categories">
                  Kategori
                </TabsTrigger>
                <TabsTrigger className="min-h-11" value="positions">
                  Jabatan
                </TabsTrigger>
              </TabsList>
            </Tabs>
          )}
          <Button
            type="button"
            className="h-11 w-full"
            disabled={loading || Boolean(listError)}
            onClick={() => act("create")}
          >
            <Plus className="size-4" aria-hidden="true" />
            Tambah {config.label.toLowerCase()}
          </Button>
          {loading ? (
            <MasterLoading />
          ) : listError ? (
            <MasterFailure message={listError} retry={() => void reload()} />
          ) : !result?.data.length ? (
            <p className="rounded-xl border border-dashed p-5 text-sm leading-6 text-muted-foreground">
              {config.empty}
            </p>
          ) : (
            result.data.map((row) => (
              <Card key={row.id} className="rounded-xl border py-0 ring-0">
                <CardContent className="space-y-3 p-4">
                  <p className="break-words text-sm font-semibold">
                    {labels[Number(row[config.field])]}
                  </p>
                  <p className="text-xs leading-5 text-muted-foreground">
                    {dateOnly(row.effective_from)} —{" "}
                    {row.effective_to
                      ? dateOnly(row.effective_to)
                      : "Tanpa tanggal akhir"}
                  </p>
                  {row.verification_status && (
                    <div className="flex flex-wrap gap-2">
                      <Badge variant="outline">
                        {verificationLabels[row.verification_status]}
                      </Badge>
                      {row.is_primary && (
                        <Badge variant="secondary">Wali utama</Badge>
                      )}
                      <Badge variant="outline">
                        {relationships[String(row.relationship)]}
                      </Badge>
                    </div>
                  )}
                  <div className="flex flex-wrap gap-2">
                    {row.verification_status === "pending" && (
                      <Button
                        type="button"
                        variant="outline"
                        className="min-h-11"
                        onClick={() => act("verify", row)}
                      >
                        Verifikasi
                      </Button>
                    )}
                    {relation === "guardian-links" && !row.revoked_at && (
                      <Button
                        type="button"
                        variant="outline"
                        className="min-h-11 text-destructive"
                        onClick={() => act("revoke", row)}
                      >
                        Cabut hubungan
                      </Button>
                    )}
                    {!row.effective_to && !row.revoked_at && (
                      <Button
                        type="button"
                        variant="outline"
                        className="min-h-11"
                        onClick={() => act("end", row)}
                      >
                        Akhiri riwayat
                      </Button>
                    )}
                  </div>
                </CardContent>
              </Card>
            ))
          )}
          {!loading && !listError && result && result.meta.total > 0 && (
            <Pager
              meta={{
                page: result.meta.current_page,
                total: result.meta.total,
                last_page: result.meta.last_page,
              }}
              setPage={setPage}
            />
          )}
        </>
      ) : (
        <div key={`${mode}-${selected?.id ?? "new"}`} className="space-y-4">
          {mode === "create" ? (
            <>
              <RecordPicker
                resource={config.target}
                label={config.label}
                value={target}
                selectedLabel={targetLabel}
                onChange={(id, label) => {
                  setTarget(id);
                  setTargetLabel(label);
                }}
                error={failure.errors?.[config.field]?.[0]}
              />
              {relation === "guardian-links" && (
                <>
                  <MasterField
                    field={{
                      name: "relationship",
                      label: "Hubungan dengan siswa",
                      type: "select",
                      required: true,
                      options: Object.entries(relationships).map(
                        ([value, label]) => ({ value, label }),
                      ),
                    }}
                    error={failure.errors?.relationship?.[0]}
                  />
                  <MasterField
                    field={{
                      name: "is_primary",
                      label: "Sebagai wali utama",
                      type: "boolean",
                    }}
                  />
                </>
              )}
              <MasterField
                field={{
                  name: "effective_from",
                  label: "Berlaku mulai",
                  type: "date",
                  required: true,
                }}
                value={startDate}
                error={failure.errors?.effective_from?.[0]}
              />
              <MasterField
                field={{
                  name: "effective_to",
                  label: "Berlaku sampai (eksklusif)",
                  type: "date",
                }}
                error={failure.errors?.effective_to?.[0]}
              />
              {relation === "positions" && (
                <MasterField
                  field={{
                    name: "appointment_number",
                    label: "Nomor surat penetapan",
                    max: 100,
                  }}
                />
              )}
            </>
          ) : (
            <>
              <p className="break-words text-sm font-medium">
                {labels[Number(selected?.[config.field])]}
              </p>
              {mode === "verify" && (
                <Label className="flex min-h-11 items-start gap-3 rounded-lg border p-3 text-sm leading-6">
                  <Checkbox
                    checked={confirmed}
                    onCheckedChange={(value) => setConfirmed(value === true)}
                  />
                  Saya telah memeriksa identitas wali dan hubungan dengan siswa.
                </Label>
              )}
              {mode === "revoke" && (
                <p className="text-sm leading-6 text-muted-foreground">
                  Akses berdasarkan hubungan ini akan dicabut. Riwayat
                  verifikasi tetap tersimpan.
                </p>
              )}
              {mode === "end" && (
                <MasterField
                  field={{
                    name: "effective_to",
                    label: "Tanggal akhir (eksklusif)",
                    type: "date",
                    required: true,
                  }}
                  error={failure.errors?.effective_to?.[0]}
                />
              )}
            </>
          )}
          <ReasonField disabled={busy} error={failure.errors?.reason?.[0]} />
          {stale && (
            <Button
              type="button"
              variant="outline"
              className="h-11 w-full"
              onClick={reset}
            >
              Muat riwayat terbaru
            </Button>
          )}
        </div>
      )}
    </>
  );
  const description = `${recordName(owner)}. ${relation === "guardian-links" ? "Akses portal wali memerlukan hubungan yang terverifikasi dan masih berlaku." : "Penetapan kategori dan jabatan disimpan sebagai riwayat; tidak memberikan role login."}`;
  if (mode === "verify" || mode === "revoke" || mode === "end")
    return (
      <ConfirmationDialog
        title={modeTitle[mode]}
        description={description}
        busy={busy}
        error={failure.message}
        onClose={reset}
        onSubmit={submit}
        disabled={stale || (mode === "verify" && !confirmed)}
        destructive={mode === "revoke"}
        submitLabel={
          mode === "verify"
            ? "Verifikasi hubungan"
            : mode === "revoke"
              ? "Cabut hubungan"
              : "Simpan riwayat"
        }
      >
        {content}
      </ConfirmationDialog>
    );
  return (
    <FormDialog
      title={modeTitle[mode]}
      description={description}
      onClose={onClose}
      onCancel={mode === "list" ? onClose : reset}
      cancelLabel={mode === "list" ? "Tutup" : "Kembali"}
      busy={busy}
      error={mode === "list" ? undefined : failure.message}
      onSubmit={submit}
      submitLabel={mode === "list" ? undefined : "Simpan riwayat"}
      submitDisabled={stale}
    >
      {content}
    </FormDialog>
  );
}
