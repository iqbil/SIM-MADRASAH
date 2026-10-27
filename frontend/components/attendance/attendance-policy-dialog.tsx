"use client";

import { MapPin } from "lucide-react";
import { useId, useState } from "react";
import { AcademicFormDialog } from "@/components/academic/academic-shared";
import { AttendanceGeofenceMap } from "@/components/attendance/attendance-geofence-map";
import { DataFailure } from "@/components/layout/data-feedback";
import { Button } from "@/components/ui/button";
import { Checkbox } from "@/components/ui/checkbox";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import type { AcademicRecord } from "@/lib/academic";
import {
  type AttendancePolicy,
  attendanceRequest,
  dateOnly,
  todayWib,
  wibAddDays,
} from "@/lib/attendance";

export function AttendancePolicyDialog({
  policy,
  onClose,
  onSaved,
}: {
  policy: AttendancePolicy;
  onClose: () => void;
  onSaved: () => void;
}) {
  const id = useId();
  const [enabled, setEnabled] = useState(policy.geofence_enabled);
  const [name, setName] = useState(policy.geofence_name ?? "");
  const [latitude, setLatitude] = useState(policy.geofence_latitude ?? "");
  const [longitude, setLongitude] = useState(policy.geofence_longitude ?? "");
  const [radius, setRadius] = useState(
    String(policy.geofence_radius_meters ?? 100),
  );
  const [locationError, setLocationError] = useState("");
  const [locating, setLocating] = useState(false);
  const firstDate = wibAddDays(
    policy.effective_from.slice(0, 10) > todayWib()
      ? policy.effective_from.slice(0, 10)
      : todayWib(),
    1,
  );
  const lat = latitude === "" ? null : Number(latitude);
  const lng = longitude === "" ? null : Number(longitude);
  const validPoint =
    lat != null &&
    lng != null &&
    Number.isFinite(lat) &&
    Number.isFinite(lng) &&
    Math.abs(lat) <= 90 &&
    Math.abs(lng) <= 180;
  const setPoint = (nextLatitude: number, nextLongitude: number) => {
    setLatitude(nextLatitude.toFixed(7));
    setLongitude(nextLongitude.toFixed(7));
  };
  const deviceLocation = () => {
    setLocationError("");
    setLocating(true);
    if (!navigator.geolocation) {
      setLocationError(
        "Perangkat tidak mendukung GPS. Pilih titik pada peta atau isi koordinat.",
      );
      setLocating(false);
      return;
    }
    navigator.geolocation.getCurrentPosition(
      (position) => {
        setPoint(position.coords.latitude, position.coords.longitude);
        setLocating(false);
      },
      () => {
        setLocationError(
          "Lokasi perangkat belum didapat. Izinkan GPS atau pilih titik pada peta.",
        );
        setLocating(false);
      },
      { enableHighAccuracy: true, maximumAge: 0, timeout: 15000 },
    );
  };
  return (
    <AcademicFormDialog
      title="Versi kebijakan baru"
      description={`Berlaku mulai ${dateOnly(firstDate)} atau setelahnya. Kebijakan lama dan riwayat tetap disimpan.`}
      fields={[
        {
          name: "effective_from",
          label: "Berlaku mulai",
          type: "date",
          required: true,
        },
        {
          name: "requires_photo",
          label: "Foto + GPS wajib untuk aksi mandiri",
          type: "boolean",
        },
        {
          name: "correction_deadline_days",
          label: "Batas koreksi (hari)",
          type: "number",
          required: true,
        },
        {
          name: "gps_max_accuracy_meters",
          label: "Akurasi GPS maksimal (meter)",
          hint: "Batas ketelitian posisi perangkat, bukan radius absensi dari madrasah.",
          type: "number",
          required: true,
        },
        {
          name: "gps_max_age_seconds",
          label: "Maksimal umur foto/lokasi (detik)",
          type: "number",
          required: true,
        },
      ]}
      current={
        {
          ...policy,
          effective_from: firstDate,
          gps_max_accuracy_meters: String(
            Number(policy.gps_max_accuracy_meters),
          ),
        } as AcademicRecord
      }
      extra={(busy) => (
        <section className="space-y-4" aria-label="Titik dan radius absensi">
          <Label
            className="flex min-h-11 items-center gap-3 rounded-lg border p-3 text-sm"
            htmlFor={`${id}-enabled`}
          >
            <Checkbox
              id={`${id}-enabled`}
              checked={enabled}
              onCheckedChange={(value) => setEnabled(value === true)}
              disabled={busy}
            />
            Batasi absensi dalam radius lokasi
          </Label>
          {enabled && (
            <>
              <div className="space-y-2">
                <Label htmlFor={`${id}-name`}>Nama lokasi absensi</Label>
                <Input
                  id={`${id}-name`}
                  value={name}
                  onChange={(event) => setName(event.target.value)}
                  required
                  maxLength={100}
                  className="h-11 text-base"
                />
              </div>
              <AttendanceGeofenceMap
                latitude={validPoint ? lat : null}
                longitude={validPoint ? lng : null}
                radius={Math.max(25, Math.min(5000, Number(radius) || 100))}
                onChange={setPoint}
                disabled={busy}
              />
              <Button
                type="button"
                variant="outline"
                className="h-11 w-full text-sm"
                disabled={busy || locating}
                onClick={deviceLocation}
              >
                <MapPin aria-hidden="true" className="size-4" />
                {locating ? "Mengambil lokasi…" : "Gunakan lokasi perangkat"}
              </Button>
              {locationError && <DataFailure message={locationError} />}
              <div className="grid min-w-0 gap-4 sm:grid-cols-2">
                <div className="min-w-0 space-y-2">
                  <Label htmlFor={`${id}-lat`}>Latitude titik absensi</Label>
                  <Input
                    id={`${id}-lat`}
                    type="number"
                    min={-90}
                    max={90}
                    step="0.0000001"
                    value={latitude}
                    onChange={(event) => setLatitude(event.target.value)}
                    required
                    className="h-11 text-base"
                  />
                </div>
                <div className="min-w-0 space-y-2">
                  <Label htmlFor={`${id}-lng`}>Longitude titik absensi</Label>
                  <Input
                    id={`${id}-lng`}
                    type="number"
                    min={-180}
                    max={180}
                    step="0.0000001"
                    value={longitude}
                    onChange={(event) => setLongitude(event.target.value)}
                    required
                    className="h-11 text-base"
                  />
                </div>
              </div>
              <div className="space-y-2">
                <Label htmlFor={`${id}-radius`}>Radius absensi (meter)</Label>
                <Input
                  id={`${id}-radius`}
                  type="number"
                  min={25}
                  max={5000}
                  step={1}
                  value={radius}
                  onChange={(event) => setRadius(event.target.value)}
                  required
                  className="h-11 text-base"
                />
                <p className="text-xs leading-5 text-muted-foreground">
                  25–5.000 meter. Pegawai harus berada di dalam radius beserta
                  batas ketelitian GPS; lokasi yang masih meragukan diminta
                  mengambil ulang GPS.
                </p>
              </div>
            </>
          )}
        </section>
      )}
      onClose={onClose}
      submit={async (body) => {
        await attendanceRequest("policy/versions", "POST", {
          ...body,
          expected_policy_id: policy.id,
          requires_photo: body.requires_photo === true,
          correction_deadline_days: Number(body.correction_deadline_days),
          gps_max_accuracy_meters: Number(body.gps_max_accuracy_meters),
          gps_max_age_seconds: Number(body.gps_max_age_seconds),
          photo_max_size_bytes: policy.photo_max_size_bytes,
          photo_max_dimension: policy.photo_max_dimension,
          photo_quality: policy.photo_quality,
          geofence_enabled: enabled,
          geofence_name: enabled ? name.trim() : null,
          geofence_latitude: enabled ? latitude : null,
          geofence_longitude: enabled ? longitude : null,
          geofence_radius_meters: enabled ? Number(radius) : null,
        });
        onSaved();
      }}
    />
  );
}
