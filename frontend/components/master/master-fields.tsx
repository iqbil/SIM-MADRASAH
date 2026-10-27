"use client";

import { Search } from "lucide-react";
import { useEffect, useId, useState } from "react";
import { Alert, AlertDescription } from "@/components/ui/alert";
import { Checkbox } from "@/components/ui/checkbox";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from "@/components/ui/select";
import { Textarea } from "@/components/ui/textarea";
import {
  type FieldDefinition,
  type MasterRecord,
  masterError,
  masterRequest,
  recordCode,
  recordName,
} from "@/lib/master-data";

export function MasterField({
  field,
  value,
  error,
  editing = false,
}: {
  field: FieldDefinition;
  value?: unknown;
  error?: string;
  editing?: boolean;
}) {
  const id = useId();
  const locked = editing && field.immutable;
  const described = error
    ? `${id}-error`
    : field.hint || locked
      ? `${id}-hint`
      : undefined;
  const common = {
    id,
    name: field.name,
    required: field.required,
    "aria-invalid": Boolean(error),
    "aria-describedby": described,
  };
  const label = `${field.label}${!field.required && field.type !== "boolean" ? " (opsional)" : ""}`;
  return (
    <div className="min-w-0 space-y-2">
      {field.type === "boolean" ? (
        <Label
          htmlFor={id}
          className="flex min-h-11 items-center gap-3 rounded-lg border p-3 text-sm leading-6"
        >
          <Checkbox
            {...common}
            defaultChecked={Boolean(value)}
            disabled={locked}
          />
          {field.label}
        </Label>
      ) : (
        <>
          <Label htmlFor={id} className="text-sm leading-5">
            {label}
          </Label>
          {field.type === "select" ? (
            <Select
              name={field.name}
              defaultValue={String(
                value ??
                  (field.required
                    ? (field.options?.[0]?.value ?? "")
                    : "__empty"),
              )}
              disabled={locked}
              required={field.required}
            >
              <SelectTrigger
                {...common}
                className="h-11! w-full min-w-0 text-base"
              >
                <SelectValue placeholder="Pilih…" />
              </SelectTrigger>
              <SelectContent>
                {!field.required && (
                  <SelectItem value="__empty">Belum diisi</SelectItem>
                )}
                {field.options?.map((option) => (
                  <SelectItem key={option.value} value={option.value}>
                    {option.label}
                  </SelectItem>
                ))}
              </SelectContent>
            </Select>
          ) : field.type === "textarea" ? (
            <Textarea
              {...common}
              defaultValue={String(value ?? "")}
              maxLength={field.max}
              className="min-h-24 text-base"
            />
          ) : (
            <Input
              {...common}
              type={field.type ?? "text"}
              step={field.step}
              defaultValue={String(value ?? "")}
              maxLength={field.max}
              pattern={field.pattern}
              readOnly={locked}
              className="h-11 text-base"
            />
          )}
        </>
      )}
      {(field.hint || locked) && (
        <p
          id={`${id}-hint`}
          className="text-xs leading-5 text-muted-foreground"
        >
          {locked
            ? "Nilai ini tetap untuk menjaga riwayat. Buat referensi baru jika maknanya berubah."
            : field.hint}
        </p>
      )}
      {error && (
        <p id={`${id}-error`} className="text-sm text-destructive">
          {error}
        </p>
      )}
    </div>
  );
}

export function RecordPicker({
  resource,
  label,
  value,
  onChange,
  selectedLabel,
  error,
}: {
  resource: string;
  label: string;
  value: string;
  onChange: (id: string, label: string) => void;
  selectedLabel?: string;
  error?: string;
}) {
  const id = useId();
  const [query, setQuery] = useState("");
  const [items, setItems] = useState<{ id: number; label: string }[]>([]);
  const [busy, setBusy] = useState(false);
  const [loadError, setLoadError] = useState("");
  useEffect(() => {
    if (resource === "people" && query.trim().length < 2) {
      setItems([]);
      return;
    }
    let active = true;
    const timer = window.setTimeout(async () => {
      setBusy(true);
      setLoadError("");
      try {
        const result = await masterRequest<{
          data: (MasterRecord & { full_name?: string })[];
        }>(
          `${resource}?q=${encodeURIComponent(query.trim())}&per_page=50${resource === "people" ? "" : "&is_active=1"}`,
        );
        if (active)
          setItems(
            result.data.map((record) => ({
              id: record.id,
              label:
                record.full_name ??
                `${recordName(record)} · ${recordCode(record)}`,
            })),
          );
      } catch (reason) {
        if (active) setLoadError(masterError(reason));
      } finally {
        if (active) setBusy(false);
      }
    }, 250);
    return () => {
      active = false;
      window.clearTimeout(timer);
    };
  }, [query, resource]);
  const options =
    value && !items.some((item) => String(item.id) === value)
      ? [
          { id: Number(value), label: selectedLabel || `Identitas #${value}` },
          ...items,
        ]
      : items;
  return (
    <div className="space-y-2">
      <Label htmlFor={`${id}-search`} className="text-sm">
        Cari {label.toLowerCase()}
      </Label>
      <div className="relative">
        <Search
          className="pointer-events-none absolute top-3.5 left-3 size-4 text-muted-foreground"
          aria-hidden="true"
        />
        <Input
          id={`${id}-search`}
          value={query}
          onChange={(event) => setQuery(event.target.value)}
          className="h-11 pl-10 text-base"
          placeholder={
            resource === "people"
              ? "Ketik minimal 2 karakter nama"
              : "Cari nama atau kode…"
          }
        />
      </div>
      <Label htmlFor={id} className="text-sm">
        {label}
      </Label>
      <Select
        value={value || "__empty"}
        onValueChange={(next) =>
          onChange(
            next === "__empty" ? "" : next,
            options.find((item) => String(item.id) === next)?.label ?? "",
          )
        }
      >
        <SelectTrigger
          id={id}
          className="h-auto! min-h-11 w-full text-base [&>span]:whitespace-normal"
          aria-invalid={Boolean(error)}
          aria-describedby={error ? `${id}-error` : `${id}-hint`}
        >
          <SelectValue placeholder="Pilih data" />
        </SelectTrigger>
        <SelectContent>
          <SelectItem value="__empty">Pilih {label.toLowerCase()}</SelectItem>
          {options.map((item) => (
            <SelectItem
              key={item.id}
              value={String(item.id)}
              className="break-words whitespace-normal"
            >
              {item.label}
            </SelectItem>
          ))}
        </SelectContent>
      </Select>
      <output
        id={`${id}-hint`}
        className="text-xs leading-5 text-muted-foreground"
      >
        {busy
          ? "Mencari data…"
          : resource === "people" && query.trim().length < 2
            ? "Cari identitas yang sudah terdaftar untuk menghindari data ganda."
            : `${items.length} pilihan ditemukan. Persempit pencarian bila belum terlihat.`}
      </output>
      {error && (
        <p id={`${id}-error`} className="text-sm text-destructive">
          {error}
        </p>
      )}
      {loadError && (
        <Alert variant="destructive">
          <AlertDescription>{loadError}</AlertDescription>
        </Alert>
      )}
    </div>
  );
}
