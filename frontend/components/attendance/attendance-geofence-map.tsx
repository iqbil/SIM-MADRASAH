"use client";

import type * as Leaflet from "leaflet";
import { useEffect, useRef, useState } from "react";
import { DataFailure } from "@/components/layout/data-feedback";
import { Skeleton } from "@/components/ui/skeleton";
import "leaflet/dist/leaflet.css";

export function AttendanceGeofenceMap({
  latitude,
  longitude,
  radius,
  disabled,
  onChange,
}: {
  latitude: number | null;
  longitude: number | null;
  radius: number;
  disabled: boolean;
  onChange: (latitude: number, longitude: number) => void;
}) {
  const container = useRef<HTMLElement>(null);
  const map = useRef<Leaflet.Map | null>(null);
  const engine = useRef<typeof Leaflet | null>(null);
  const marker = useRef<Leaflet.Marker | null>(null);
  const circle = useRef<Leaflet.Circle | null>(null);
  const callback = useRef(onChange);
  const inactive = useRef(disabled);
  const [ready, setReady] = useState(false);
  const [error, setError] = useState("");
  const [tileFailure, setTileFailure] = useState(false);
  useEffect(() => {
    callback.current = onChange;
    inactive.current = disabled;
  }, [onChange, disabled]);

  useEffect(() => {
    let active = true;
    let observer: ResizeObserver | undefined;
    import("leaflet")
      .then((L) => {
        if (!active || !container.current) return;
        engine.current = L;
        const instance = L.map(container.current, {
          scrollWheelZoom: false,
          zoomControl: false,
        }).setView([-2.548926, 118.014863], 4);
        map.current = instance;
        L.control
          .zoom({ zoomInTitle: "Perbesar peta", zoomOutTitle: "Perkecil peta" })
          .addTo(instance);
        L.tileLayer("https://tile.openstreetmap.org/{z}/{x}/{y}.png", {
          maxZoom: 19,
          attribution:
            '&copy; <a href="https://www.openstreetmap.org/copyright" target="_blank" rel="noopener noreferrer">OpenStreetMap</a>',
        })
          .on("tileerror", () => {
            if (active) setTileFailure(true);
          })
          .addTo(instance);
        instance.on("click", (event: Leaflet.LeafletMouseEvent) => {
          if (!inactive.current)
            callback.current(event.latlng.lat, event.latlng.lng);
        });
        observer = new ResizeObserver(() => instance.invalidateSize());
        observer.observe(container.current);
        setReady(true);
      })
      .catch(() => {
        if (active)
          setError(
            "Peta belum dapat dimuat. Anda tetap dapat mengisi koordinat secara manual.",
          );
      });
    return () => {
      active = false;
      observer?.disconnect();
      map.current?.remove();
      map.current = null;
    };
  }, []);

  useEffect(() => {
    const L = engine.current;
    const instance = map.current;
    if (!ready || !L || !instance) return;
    if (latitude == null || longitude == null) {
      marker.current?.remove();
      marker.current = null;
      circle.current?.remove();
      circle.current = null;
      return;
    }
    const point: Leaflet.LatLngTuple = [latitude, longitude];
    if (marker.current) marker.current.setLatLng(point);
    else {
      marker.current = L.marker(point, {
        draggable: true,
        title: "Pusat absensi",
        icon: L.divIcon({
          className: "attendance-map-pin",
          iconSize: [24, 24],
          iconAnchor: [12, 12],
        }),
      })
        .addTo(instance)
        .on("dragend", () => {
          const position = marker.current?.getLatLng();
          if (position && !inactive.current)
            callback.current(position.lat, position.lng);
        });
    }
    if (circle.current) circle.current.setLatLng(point).setRadius(radius);
    else
      circle.current = L.circle(point, {
        radius,
        className: "attendance-map-radius",
      }).addTo(instance);
    instance.fitBounds(circle.current.getBounds(), {
      padding: [24, 24],
      maxZoom: 18,
      animate: false,
    });
  }, [ready, latitude, longitude, radius]);

  return (
    <div className="space-y-2">
      {error && <DataFailure message={error} />}
      <div className="relative isolate" inert={disabled || undefined}>
        {!ready && !error && (
          <Skeleton
            className="absolute inset-0 z-10 h-[min(18rem,40dvh)]"
            aria-label="Memuat peta"
          />
        )}
        <section
          ref={container}
          aria-label="Peta titik absensi"
          data-ready={ready}
          className="attendance-map relative z-0 h-[min(18rem,40dvh)] w-full rounded-lg border"
        />
      </div>
      {tileFailure && (
        <p className="text-sm text-muted-foreground">
          Latar peta belum dapat dimuat. Periksa koneksi atau isi koordinat
          secara manual, titik dan radius tetap dapat diatur.
        </p>
      )}
      <p className="text-xs leading-5 text-muted-foreground">
        Klik peta atau geser pin untuk mengisi koordinat. Koordinat juga dapat
        diisi manual. Latar peta disediakan OpenStreetMap.
      </p>
    </div>
  );
}
