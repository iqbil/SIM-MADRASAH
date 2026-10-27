export type GeofencePolicy = {
  geofence_enabled: boolean;
  geofence_name: string | null;
  geofence_latitude: string | null;
  geofence_longitude: string | null;
  geofence_radius_meters: number | null;
};

export type LocationReading = {
  latitude: number;
  longitude: number;
  accuracy: number;
  timestamp: number;
};

export function assessGeofence(
  policy: GeofencePolicy,
  location: LocationReading,
) {
  if (!policy.geofence_enabled)
    return { state: "disabled" as const, distance: 0 };
  if (
    policy.geofence_latitude == null ||
    policy.geofence_longitude == null ||
    !policy.geofence_radius_meters
  )
    return { state: "unconfigured" as const, distance: 0 };
  const radians = (value: number) => (value * Math.PI) / 180;
  const centerLatitude = Number(policy.geofence_latitude);
  const centerLongitude = Number(policy.geofence_longitude);
  const haversine =
    Math.sin(radians(location.latitude - centerLatitude) / 2) ** 2 +
    Math.cos(radians(location.latitude)) *
      Math.cos(radians(centerLatitude)) *
      Math.sin(radians(location.longitude - centerLongitude) / 2) ** 2;
  const distance =
    6371008.8 * 2 * Math.asin(Math.sqrt(Math.min(1, Math.max(0, haversine))));
  const radius = policy.geofence_radius_meters;
  const state =
    distance + location.accuracy <= radius
      ? ("inside" as const)
      : distance - location.accuracy > radius
        ? ("outside" as const)
        : ("uncertain" as const);
  return { state, distance };
}

export function geofenceMessage(state: string) {
  if (state === "outside")
    return "Anda berada di luar radius absensi. Mendekat ke lokasi lalu muat ulang GPS.";
  if (state === "unconfigured")
    return "Titik absensi belum lengkap. Hubungi petugas absensi.";
  return "Posisi GPS belum pasti di dalam radius absensi. Mendekat ke pusat lokasi atau keluar ke area terbuka lalu muat ulang GPS.";
}
