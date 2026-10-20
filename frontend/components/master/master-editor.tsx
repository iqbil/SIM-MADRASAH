"use client";

import { type FormEvent, useId, useState } from "react";
import { toast } from "sonner";
import { FormDialog } from "@/components/layout/form-dialog";
import { ReasonField } from "@/components/layout/reason-field";
import { MasterField, RecordPicker } from "@/components/master/master-fields";
import { Alert, AlertDescription } from "@/components/ui/alert";
import { Button } from "@/components/ui/button";
import { Label } from "@/components/ui/label";
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from "@/components/ui/select";
import { Textarea } from "@/components/ui/textarea";
import { AuthRequestError } from "@/lib/auth/client";
import type { ApiError } from "@/lib/auth/types";
import {
  fieldValue,
  hasPerson,
  type MasterRecord,
  masterError,
  masterRequest,
  PERSON_FIELDS,
  RESOURCE_CONFIG,
  type Resource,
} from "@/lib/master-data";

export function MasterEditor({
  resource,
  current,
  onClose,
  onSaved,
  onReload,
}: {
  resource: Resource;
  current?: MasterRecord;
  onClose: () => void;
  onSaved: () => void;
  onReload: () => void;
}) {
  const config = RESOURCE_CONFIG[resource];
  const identityId = useId();
  const [busy, setBusy] = useState(false);
  const [failure, setFailure] = useState<ApiError>({});
  const [conflict, setConflict] = useState(false);
  const [identityMode, setIdentityMode] = useState("new");
  const [personId, setPersonId] = useState("");
  const [personLabel, setPersonLabel] = useState("");
  const errorFor = (name: string) => failure.errors?.[name]?.[0];
  async function submit(event: FormEvent<HTMLFormElement>) {
    event.preventDefault();
    if (busy) return;
    setBusy(true);
    setFailure({});
    setConflict(false);
    const data = new FormData(event.currentTarget);
    const body: Record<string, unknown> = { reason: data.get("reason") };
    for (const field of config.fields) {
      body[field.name] =
        current && field.immutable
          ? current[field.name]
          : field.type === "boolean"
            ? data.get(field.name) === "on"
            : data.get(field.name) === "__empty" || data.get(field.name) === ""
              ? null
              : data.get(field.name);
    }
    if (hasPerson(resource)) {
      if (!current && identityMode === "existing") {
        if (!personId) {
          setFailure({
            message: "Pilih identitas yang sudah terdaftar.",
            errors: { person_id: ["Identitas wajib dipilih."] },
          });
          setBusy(false);
          return;
        }
        body.person_id = Number(personId);
      } else {
        body.person = Object.fromEntries(
          PERSON_FIELDS.map((field) => [
            field.name.slice(7),
            data.get(field.name) === "__empty" || data.get(field.name) === ""
              ? null
              : data.get(field.name),
          ]),
        );
        if (current)
          body.expected_person_updated_at = current.person?.updated_at;
      }
    }
    const confirmation = data.get("duplicate_confirmation");
    if (confirmation) body.duplicate_confirmation = confirmation;
    if (current) body.expected_updated_at = current.updated_at;
    try {
      await masterRequest(
        resource === "institution"
          ? "institution"
          : current
            ? `${resource}/${current.id}`
            : resource,
        current || resource === "institution" ? "PUT" : "POST",
        body,
      );
      toast.success("Data berhasil disimpan.");
      onSaved();
    } catch (reason) {
      setFailure(
        reason instanceof AuthRequestError
          ? reason.payload
          : { message: masterError(reason) },
      );
      setConflict(reason instanceof AuthRequestError && reason.status === 409);
      setBusy(false);
    }
  }
  return (
    <FormDialog
      title={`${current ? "Ubah" : resource === "institution" ? "Lengkapi" : "Tambah"} ${config.singular}`}
      description={
        hasPerson(resource)
          ? "Identitas ini dipakai bersama oleh profil yang terhubung. Penyimpanan data master tidak otomatis membuat akun login."
          : config.description
      }
      busy={busy}
      error={failure.message}
      onClose={onClose}
      onSubmit={submit}
      submitLabel={current ? "Simpan perubahan" : "Simpan data"}
      submitDisabled={conflict}
    >
      {conflict && (
        <Button
          type="button"
          variant="outline"
          className="h-11 w-full"
          onClick={onReload}
        >
          Muat data terbaru
        </Button>
      )}
      {!current && hasPerson(resource) && (
        <div className="space-y-2">
          <Label htmlFor={identityId}>Identitas orang</Label>
          <Select
            value={identityMode}
            onValueChange={(value) => {
              setIdentityMode(value);
              setFailure({});
            }}
          >
            <SelectTrigger id={identityId} className="h-11! w-full text-base">
              <SelectValue />
            </SelectTrigger>
            <SelectContent>
              <SelectItem value="new">Buat identitas baru</SelectItem>
              <SelectItem value="existing">
                Gunakan identitas terdaftar
              </SelectItem>
            </SelectContent>
          </Select>
        </div>
      )}
      {!current && hasPerson(resource) && identityMode === "existing" ? (
        <RecordPicker
          resource="people"
          label="Identitas terdaftar"
          value={personId}
          selectedLabel={personLabel}
          onChange={(id, label) => {
            setPersonId(id);
            setPersonLabel(label);
          }}
          error={errorFor("person_id")}
        />
      ) : (
        hasPerson(resource) && (
          <section aria-label="Identitas pribadi" className="space-y-4">
            <h2 className="text-sm font-semibold">Identitas pribadi</h2>
            {PERSON_FIELDS.map((field) => (
              <MasterField
                key={field.name}
                field={field}
                value={fieldValue(current, field.name)}
                error={errorFor(field.name)}
              />
            ))}
          </section>
        )
      )}
      {failure.duplicate_candidates?.length ? (
        <Alert>
          <AlertDescription className="space-y-3">
            <p>Ditemukan identitas serupa. Periksa terlebih dahulu.</p>
            {failure.duplicate_candidates.map((person) => (
              <div key={person.id} className="space-y-2">
                <p className="break-words text-sm">
                  {person.full_name} · #{person.id}
                </p>
                {!current && (
                  <Button
                    type="button"
                    variant="outline"
                    className="h-auto min-h-11 w-full whitespace-normal"
                    disabled={!person.is_active}
                    onClick={() => {
                      setIdentityMode("existing");
                      setPersonId(String(person.id));
                      setPersonLabel(person.full_name);
                      setFailure({});
                    }}
                  >
                    Gunakan identitas ini
                  </Button>
                )}
              </div>
            ))}
            <Label htmlFor="duplicate_confirmation">
              Dasar pemeriksaan bila orangnya berbeda (opsional)
            </Label>
            <Textarea
              id="duplicate_confirmation"
              name="duplicate_confirmation"
              minLength={20}
              maxLength={1000}
              className="text-base"
              placeholder="Jelaskan hasil pemeriksaan identitas, minimal 20 karakter."
            />
          </AlertDescription>
        </Alert>
      ) : null}
      <section aria-label="Informasi master" className="space-y-4">
        {hasPerson(resource) && (
          <h2 className="text-sm font-semibold">Informasi {config.singular}</h2>
        )}
        {config.fields.map((field) => (
          <MasterField
            key={field.name}
            field={field}
            value={
              fieldValue(current, field.name) ??
              (field.name === "timezone" ? "Asia/Jakarta" : undefined)
            }
            error={errorFor(field.name)}
            editing={Boolean(current)}
          />
        ))}
      </section>
      <ReasonField disabled={busy} error={errorFor("reason")} />
    </FormDialog>
  );
}
