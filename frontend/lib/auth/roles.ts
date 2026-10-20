import type { AuthRole, AuthUser } from "./types";

export const ROLE_COOKIE = "madrasah_role";

const descriptions: Record<string, string> = {
  super_admin: "Ruang kerja untuk pengelolaan akun, role, dan akses madrasah.",
  kepala_madrasah: "Ruang kerja untuk pemantauan dan pengelolaan madrasah.",
  kepala: "Ruang kerja untuk pemantauan dan pengelolaan madrasah.",
  admin: "Ruang kerja untuk pengelolaan sistem dan data madrasah.",
  administrator: "Ruang kerja untuk pengelolaan sistem dan data madrasah.",
  operator: "Ruang kerja untuk pengelolaan sistem dan data madrasah.",
  admin_operator: "Ruang kerja untuk pengelolaan sistem dan data madrasah.",
  guru: "Ruang kerja untuk mendukung kegiatan belajar mengajar.",
  wali_kelas: "Ruang kerja untuk pendampingan dan pengelolaan kelas.",
  guru_bk: "Ruang kerja untuk layanan bimbingan dan konseling.",
  bk: "Ruang kerja untuk layanan bimbingan dan konseling.",
  tenaga_kependidikan: "Ruang kerja untuk kegiatan pendukung madrasah.",
  bendahara: "Ruang kerja untuk administrasi keuangan sesuai kewenangan.",
  petugas_tu: "Ruang kerja untuk administrasi dan tata usaha madrasah.",
  tu: "Ruang kerja untuk administrasi dan tata usaha madrasah.",
  tata_usaha: "Ruang kerja untuk administrasi dan tata usaha madrasah.",
  orang_tua:
    "Ruang kerja untuk mengikuti informasi pendidikan putra-putri Anda.",
  orang_tua_wali:
    "Ruang kerja untuk mengikuti informasi pendidikan putra-putri Anda.",
  wali: "Ruang kerja untuk mengikuti informasi pendidikan putra-putri Anda.",
  guardian:
    "Ruang kerja untuk mengikuti informasi pendidikan putra-putri Anda.",
};

export function roleDescription(role: AuthRole): string {
  const code = role.code.toLowerCase().replace(/[\s/-]+/g, "_");
  return (
    descriptions[code] ??
    "Ruang kerja untuk informasi dan aktivitas Anda di madrasah."
  );
}

export function roleHref(role: AuthRole): string {
  return `/dashboard/${encodeURIComponent(role.code)}`;
}

export function userHome(user: AuthUser, preferredRole?: string): string {
  if (user.must_change_password) return "/ganti-password";
  const role =
    user.roles.find((item) => item.code === preferredRole) ?? user.roles[0];
  return role ? roleHref(role) : "/dashboard";
}

export function safeReturnTo(value: string | null | undefined): string {
  if (
    !value ||
    value.includes("\\") ||
    Array.from(value).some((character) => character.charCodeAt(0) < 32)
  )
    return "/dashboard";
  if (
    value === "/akun" ||
    value === "/pengguna" ||
    value === "/ganti-password" ||
    /^\/dashboard(?:\/[^?#]*)?$/.test(value)
  ) {
    return value;
  }
  return "/dashboard";
}

export function initials(name: string): string {
  return name
    .trim()
    .split(/\s+/)
    .slice(0, 2)
    .map((part) => part[0])
    .join("")
    .toUpperCase();
}
