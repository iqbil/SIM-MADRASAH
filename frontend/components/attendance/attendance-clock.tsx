"use client";

import {
  Camera,
  CameraOff,
  LoaderCircle,
  MapPin,
  RefreshCw,
} from "lucide-react";
import { useCallback, useEffect, useRef, useState } from "react";
import { toast } from "sonner";
import { AcademicHeading } from "@/components/academic/academic-shared";
import { Alert, AlertDescription } from "@/components/ui/alert";
import { Badge } from "@/components/ui/badge";
import { Button } from "@/components/ui/button";
import { Card, CardContent } from "@/components/ui/card";
import {
  attendanceError,
  attendanceRequest,
  attendanceUpload,
  type ClockStatus,
  entryModeLabel,
  statusLabels,
  wibTime,
} from "@/lib/attendance";

type Capture = {
  latitude: number;
  longitude: number;
  accuracy: number;
  timestamp: number;
};

export function AttendanceClock() {
  const [status, setStatus] = useState<ClockStatus | null>(null);
  const [failure, setFailure] = useState("");
  const [cameraReady, setCameraReady] = useState(false);
  const [cameraError, setCameraError] = useState("");
  const [location, setLocation] = useState<Capture | null>(null);
  const [locationError, setLocationError] = useState("");
  const [busy, setBusy] = useState(false);
  const videoRef = useRef<HTMLVideoElement>(null);
  const streamRef = useRef<MediaStream | null>(null);

  const load = useCallback(async () => {
    try {
      const result = await attendanceRequest<{ data: ClockStatus }>("status");
      setStatus(result.data);
      setFailure("");
    } catch (error) {
      setFailure(attendanceError(error));
    }
  }, []);

  useEffect(() => {
    void load();
  }, [load]);

  const stopCamera = useCallback(() => {
    streamRef.current?.getTracks().forEach((track) => {
      track.stop();
    });
    streamRef.current = null;
    setCameraReady(false);
  }, []);

  useEffect(() => stopCamera, [stopCamera]);

  const startCamera = useCallback(async () => {
    setCameraError("");
    try {
      const stream = await navigator.mediaDevices.getUserMedia({
        video: {
          facingMode: "user",
          width: { ideal: 1280 },
          height: { ideal: 960 },
        },
        audio: false,
      });
      streamRef.current = stream;
      if (videoRef.current) {
        videoRef.current.srcObject = stream;
        await videoRef.current.play();
      }
      setCameraReady(true);
    } catch {
      setCameraError(
        "Kamera tidak dapat diakses. Pastikan izin kamera diberikan pada browser dan tidak dipakai aplikasi lain.",
      );
    }
  }, []);

  const refreshLocation = useCallback(async () => {
    setLocationError("");
    setLocation(null);
    if (!("geolocation" in navigator)) {
      setLocationError("Perangkat tidak mendukung pengambilan lokasi.");

      return;
    }
    try {
      const position = await new Promise<GeolocationPosition>(
        (resolve, reject) => {
          navigator.geolocation.getCurrentPosition(resolve, reject, {
            enableHighAccuracy: true,
            timeout: 15000,
            maximumAge: 0,
          });
        },
      );
      setLocation({
        latitude: position.coords.latitude,
        longitude: position.coords.longitude,
        accuracy: position.coords.accuracy,
        timestamp: position.timestamp,
      });
    } catch {
      setLocationError(
        "Lokasi belum didapat. Izinkan akses lokasi, keluar ke ruang terbuka agar sinyal GPS baik, lalu tekan muat ulang lokasi.",
      );
    }
  }, []);

  useEffect(() => {
    void refreshLocation();
  }, [refreshLocation]);

  async function capturePhoto(): Promise<{
    blob: Blob;
    takenAt: string;
  } | null> {
    const video = videoRef.current;
    if (!video || !cameraReady || video.videoWidth === 0) {
      setCameraError(
        "Kamera belum siap. Nyalakan kamera dan tunggu pratinjau muncul.",
      );

      return null;
    }
    const canvas = document.createElement("canvas");
    canvas.width = video.videoWidth;
    canvas.height = video.videoHeight;
    canvas.getContext("2d")?.drawImage(video, 0, 0);
    const blob = await new Promise<Blob | null>((resolve) =>
      canvas.toBlob(resolve, "image/jpeg", 0.92),
    );
    if (!blob) {
      setCameraError("Foto gagal diproses; ulangi pengambilan foto.");

      return null;
    }

    return { blob, takenAt: new Date().toISOString() };
  }

  async function submit(action: "check_in" | "check_out") {
    if (busy || !status) return;
    setBusy(true);
    setFailure("");
    try {
      const photo = await capturePhoto();
      if (!photo) throw new Error("Kamera belum siap.");
      if (!location) {
        setLocationError(
          "Lokasi belum didapat; muat ulang lokasi lalu ulangi.",
        );
        throw new Error("Lokasi belum didapat.");
      }
      const form = new FormData();
      form.set("action", action);
      form.set("operation_key", crypto.randomUUID());
      form.set("latitude", String(location.latitude));
      form.set("longitude", String(location.longitude));
      form.set("accuracy_meters", String(location.accuracy ?? 0));
      form.set("photo_taken_at", photo.takenAt);
      form.set(
        "location_captured_at",
        new Date(location.timestamp).toISOString(),
      );
      form.set("photo", photo.blob, "photo.jpg");
      const result = await attendanceUpload("clock", form);
      if (result.status >= 400) {
        setFailure(String(result.payload.message ?? "Absensi belum berhasil."));
        await refreshLocation();

        return;
      }
      toast.success(
        action === "check_in"
          ? "Absen masuk tercatat dengan foto dan GPS."
          : "Absen pulang tercatat dengan foto dan GPS.",
      );
      setStatus(result.payload.data as ClockStatus);
    } catch (error) {
      if (
        error instanceof Error &&
        error.message !== "Kamera belum siap." &&
        error.message !== "Lokasi belum didapat."
      ) {
        setFailure(attendanceError(error));
      } else {
        setFailure(
          "Aksi absensi belum dapat dilanjutkan. Periksa kamera dan lokasi, atau hubungi petugas absensi untuk pencatatan administratif.",
        );
      }
    } finally {
      setBusy(false);
    }
  }

  const workDay = status?.work_day ?? null;
  const attendance = status?.attendance ?? null;
  const notWorkDay =
    status !== null && (workDay === null || !workDay.is_work_required);
  const done = status?.next_action == null;

  return (
    <div className="animate-enter space-y-6">
      <AcademicHeading
        category="Absensi pegawai"
        title="Masuk / pulang"
        description="Aksi mandiri dengan foto langsung dari kamera dan lokasi perangkat; waktu yang dicatat adalah waktu server (WIB)."
      />
      {failure && (
        <Alert variant="destructive">
          <AlertDescription className="space-y-1">
            <p>{failure}</p>
            <p className="text-xs">
              Bila kamera/GPS tetap gagal, hubungi petugas absensi untuk
              pencatatan administratif sesuai SOP (FR-05-07).
            </p>
          </AlertDescription>
        </Alert>
      )}
      <div className="grid gap-6 lg:grid-cols-2">
        <Card className="rounded-2xl border border-border/70 py-0 ring-0">
          <CardContent className="space-y-4 p-4 sm:p-6">
            <div className="flex items-center justify-between gap-3">
              <h2 className="text-base font-semibold">Pratinjau kamera</h2>
              {cameraReady ? (
                <Button
                  variant="outline"
                  className="h-11 text-xs"
                  onClick={stopCamera}
                >
                  <CameraOff className="size-4" aria-hidden="true" /> Matikan
                  kamera
                </Button>
              ) : (
                <Button
                  variant="outline"
                  className="h-11 text-xs"
                  onClick={() => void startCamera()}
                >
                  <Camera className="size-4" aria-hidden="true" /> Nyalakan
                  kamera
                </Button>
              )}
            </div>
            <div className="relative aspect-[4/3] overflow-hidden rounded-xl bg-secondary">
              <video
                ref={videoRef}
                muted
                playsInline
                className="size-full object-cover"
                aria-label="Pratinjau kamera untuk foto absensi"
              />
              {!cameraReady && (
                <p className="absolute inset-0 flex items-center justify-center px-4 text-center text-xs leading-5 text-muted-foreground">
                  Kamera belum aktif. Tekan “Nyalakan kamera” dan izinkan akses
                  kamera pada browser.
                </p>
              )}
            </div>
            <div
              className={`flex items-start gap-2 rounded-lg border p-3 text-xs leading-5 ${
                location ? "text-muted-foreground" : "text-destructive"
              }`}
              aria-live="polite"
            >
              <MapPin className="mt-0.5 size-4 shrink-0" aria-hidden="true" />
              <div className="min-w-0 flex-1">
                {location ? (
                  <>
                    <p className="font-medium text-foreground">
                      Lokasi siap · akurasi ±{Math.round(location.accuracy)} m
                    </p>
                    <p className="break-all">
                      {location.latitude.toFixed(5)},{" "}
                      {location.longitude.toFixed(5)}
                    </p>
                  </>
                ) : (
                  <p>{locationError || "Mengambil lokasi GPS…"}</p>
                )}
              </div>
              <Button
                type="button"
                variant="outline"
                className="h-11 shrink-0 px-3 text-xs"
                onClick={() => void refreshLocation()}
              >
                <RefreshCw className="size-3.5" aria-hidden="true" /> Muat ulang
              </Button>
            </div>
            {status && (
              <div className="flex flex-col gap-2">
                {status.next_action === "check_in" && (
                  <Button
                    className="h-12 text-sm"
                    disabled={busy}
                    onClick={() => void submit("check_in")}
                  >
                    {busy && (
                      <LoaderCircle
                        className="size-4 animate-spin"
                        aria-hidden="true"
                      />
                    )}
                    Absen masuk dengan foto + GPS
                  </Button>
                )}
                {status.next_action === "check_out" && (
                  <Button
                    className="h-12 text-sm"
                    disabled={busy}
                    onClick={() => void submit("check_out")}
                  >
                    {busy && (
                      <LoaderCircle
                        className="size-4 animate-spin"
                        aria-hidden="true"
                      />
                    )}
                    Absen pulang dengan foto + GPS
                  </Button>
                )}
                {notWorkDay && (
                  <p className="rounded-lg border border-dashed p-3 text-sm leading-6 text-muted-foreground">
                    {workDay === null
                      ? "Hari ini bukan hari kerja menurut pola kerja Anda; kehadiran tidak perlu dicatat."
                      : "Hari ini libur/kegiatan madrasah; kehadiran tidak dicatat."}
                  </p>
                )}
                {done && !notWorkDay && attendance && (
                  <p className="rounded-lg border border-dashed p-3 text-sm leading-6 text-muted-foreground">
                    Kehadiran hari ini sudah lengkap. Terima kasih.
                  </p>
                )}
              </div>
            )}
            {cameraError && (
              <p className="text-sm text-destructive">{cameraError}</p>
            )}
          </CardContent>
        </Card>

        <Card className="rounded-2xl border border-border/70 py-0 ring-0">
          <CardContent className="space-y-5 p-4 sm:p-6">
            <h2 className="text-base font-semibold">Kehadiran hari ini</h2>
            {status === null ? (
              <p className="text-sm text-muted-foreground">Memuat…</p>
            ) : (
              <dl className="space-y-3 text-sm">
                <div className="flex items-center justify-between gap-3">
                  <dt className="text-muted-foreground">Tanggal</dt>
                  <dd className="font-medium">{status.today}</dd>
                </div>
                <div className="flex items-center justify-between gap-3">
                  <dt className="text-muted-foreground">Jadwal kerja</dt>
                  <dd className="font-medium">
                    {workDay?.scheduled_start_at
                      ? `${wibTime(workDay.scheduled_start_at)}–${wibTime(workDay.scheduled_end_at)} WIB`
                      : "—"}
                  </dd>
                </div>
                <div className="flex items-center justify-between gap-3">
                  <dt className="text-muted-foreground">Absen masuk</dt>
                  <dd className="font-medium">
                    {attendance?.check_in_at
                      ? wibTime(attendance.check_in_at)
                      : "—"}
                  </dd>
                </div>
                <div className="flex items-center justify-between gap-3">
                  <dt className="text-muted-foreground">Absen pulang</dt>
                  <dd className="font-medium">
                    {attendance?.check_out_at
                      ? wibTime(attendance.check_out_at)
                      : "—"}
                  </dd>
                </div>
                {attendance && (
                  <>
                    <div className="flex items-center justify-between gap-3">
                      <dt className="text-muted-foreground">Status</dt>
                      <dd>
                        <Badge
                          variant="secondary"
                          className="bg-primary/8 text-xs text-primary"
                        >
                          {statusLabels[status.attendance_status?.code ?? ""] ??
                            status.attendance_status?.name ??
                            "—"}
                        </Badge>
                      </dd>
                    </div>
                    {(attendance.late_minutes ?? 0) > 0 && (
                      <div className="flex items-center justify-between gap-3">
                        <dt className="text-muted-foreground">Terlambat</dt>
                        <dd className="font-medium">
                          {attendance.late_minutes} menit
                        </dd>
                      </div>
                    )}
                    <div className="flex items-center justify-between gap-3">
                      <dt className="text-muted-foreground">Sumber</dt>
                      <dd className="font-medium">
                        {entryModeLabel(attendance.entry_mode)}
                      </dd>
                    </div>
                  </>
                )}
              </dl>
            )}
            {status && (
              <div className="rounded-lg border border-dashed p-3 text-xs leading-5 text-muted-foreground">
                Kebijakan: foto baru dari kamera wajib; lokasi akurasi maksimal{" "}
                {Math.round(Number(status.policy.gps_max_accuracy_meters))} m;
                foto dan lokasi maksimal {status.policy.gps_max_age_seconds}{" "}
                detik saat mencatat; batas koreksi{" "}
                {status.policy.correction_deadline_days} hari melalui petugas.{" "}
                Lokasi dicatat tanpa pembatasan titik atau radius.
              </div>
            )}
          </CardContent>
        </Card>
      </div>
    </div>
  );
}
