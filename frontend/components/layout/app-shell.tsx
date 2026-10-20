"use client";

import {
  ArrowUpRight,
  Check,
  ChevronsUpDown,
  CircleHelp,
  GraduationCap,
  Layers3,
  ShieldCheck,
  X,
} from "lucide-react";
import { usePathname, useRouter } from "next/navigation";
import type { CSSProperties, ReactNode } from "react";
import { SessionMonitor } from "@/components/auth/session-monitor";
import { Brand } from "@/components/brand";
import { AppHeader } from "@/components/layout/app-header";
import { SidebarNavigation } from "@/components/layout/sidebar-navigation";
import { Avatar, AvatarFallback } from "@/components/ui/avatar";
import { Button } from "@/components/ui/button";
import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogHeader,
  DialogTitle,
  DialogTrigger,
} from "@/components/ui/dialog";
import {
  DropdownMenu,
  DropdownMenuContent,
  DropdownMenuItem,
  DropdownMenuLabel,
  DropdownMenuTrigger,
} from "@/components/ui/dropdown-menu";
import {
  Sidebar,
  SidebarContent,
  SidebarFooter,
  SidebarHeader,
  SidebarInset,
  SidebarMenu,
  SidebarMenuButton,
  SidebarMenuItem,
  SidebarProvider,
  SidebarRail,
  useSidebar,
} from "@/components/ui/sidebar";
import { initials, ROLE_COOKIE, roleHref } from "@/lib/auth/roles";
import type { AuthRole, AuthUser } from "@/lib/auth/types";

export function AppShell({
  user,
  preferredRole,
  sidebarOpen,
  children,
}: {
  user: AuthUser;
  preferredRole?: string;
  sidebarOpen: boolean;
  children: ReactNode;
}) {
  const pathname = usePathname();
  const role =
    user.roles.find((item) => pathname === roleHref(item)) ??
    user.roles.find((item) => item.code === preferredRole) ??
    user.roles[0];
  return (
    <SidebarProvider
      defaultOpen={sidebarOpen}
      style={
        {
          "--sidebar-width": "16rem",
          "--sidebar-width-icon": "4.5rem",
        } as CSSProperties
      }
    >
      <SessionMonitor user={user} />
      <a
        href="#main-content"
        className="sr-only fixed top-3 left-3 z-100 rounded-lg bg-primary px-4 py-3 text-white focus:not-sr-only"
      >
        Lewati ke konten
      </a>
      <AppSidebar user={user} role={role} pathname={pathname} />
      <SidebarInset className="min-w-0 bg-background">
        <AppHeader user={user} role={role} pathname={pathname} />
        <main
          id="main-content"
          className="mx-auto flex w-full min-w-0 max-w-[1440px] flex-1 flex-col gap-6 p-4 py-6 sm:p-6 lg:p-8 xl:p-10"
        >
          {children}
        </main>
        <footer className="flex flex-wrap justify-between gap-2 border-t px-5 py-4 text-[10px] text-muted-foreground sm:px-7 lg:px-9">
          <span>© MTs Al Karimiyyah</span>
          <span>Sistem Informasi Madrasah</span>
        </footer>
      </SidebarInset>
    </SidebarProvider>
  );
}

function RoleSwitcher({ user, role }: { user: AuthUser; role?: AuthRole }) {
  const router = useRouter();
  const { setOpenMobile } = useSidebar();
  return (
    <DropdownMenu>
      <DropdownMenuTrigger asChild>
        <SidebarMenuButton
          className="h-auto min-h-16 rounded-xl border border-border bg-background/80 px-3 py-3 group-data-[collapsible=icon]:min-h-10 group-data-[collapsible=icon]:size-10! group-data-[collapsible=icon]:justify-center group-data-[collapsible=icon]:p-0!"
          tooltip="Pilih ruang kerja"
          aria-label="Pilih ruang kerja"
          disabled={!user.roles.length}
        >
          <span className="flex size-9 shrink-0 items-center justify-center rounded-lg bg-primary/10 text-primary">
            <Layers3 className="size-[18px]" />
          </span>
          <span className="min-w-0 flex-1 group-data-[collapsible=icon]:hidden">
            <span className="block text-[10px] text-muted-foreground">
              Ruang kerja aktif
            </span>
            <span className="mt-0.5 block truncate text-xs font-semibold">
              {role?.name ?? "Belum ada role"}
            </span>
          </span>
          <ChevronsUpDown className="size-3.5 shrink-0 text-muted-foreground group-data-[collapsible=icon]:hidden" />
        </SidebarMenuButton>
      </DropdownMenuTrigger>
      <DropdownMenuContent align="start" className="w-64" sideOffset={8}>
        <DropdownMenuLabel className="px-3 py-2 text-xs text-muted-foreground">
          Pilih ruang kerja
        </DropdownMenuLabel>
        {user.roles.map((item) => (
          <DropdownMenuItem
            key={item.code}
            className="min-h-11 gap-3 px-3"
            onSelect={() => {
              // biome-ignore lint/suspicious/noDocumentCookie: Non-sensitive preference supports browsers without Cookie Store.
              document.cookie =
                ROLE_COOKIE +
                "=" +
                encodeURIComponent(item.code) +
                "; path=/; max-age=2592000; samesite=lax";
              setOpenMobile(false);
              router.push(roleHref(item));
            }}
          >
            <GraduationCap className="size-4 text-primary" />
            <span className="flex-1">{item.name}</span>
            {role?.code === item.code && (
              <Check className="size-4 text-primary" />
            )}
          </DropdownMenuItem>
        ))}
      </DropdownMenuContent>
    </DropdownMenu>
  );
}

function AppSidebar({
  user,
  role,
  pathname,
}: {
  user: AuthUser;
  role?: AuthRole;
  pathname: string;
}) {
  const { setOpenMobile } = useSidebar();
  return (
    <Sidebar collapsible="icon" className="border-r-border">
      <SidebarHeader className="gap-6 px-4 pt-6 pb-4">
        <div className="flex items-center justify-between">
          <Brand compact />
          <Button
            type="button"
            variant="ghost"
            size="icon"
            className="size-11 shrink-0 lg:hidden"
            aria-label="Tutup menu"
            onClick={() => setOpenMobile(false)}
          >
            <X className="size-4" />
          </Button>
        </div>
        <SidebarMenu>
          <SidebarMenuItem>
            <RoleSwitcher user={user} role={role} />
          </SidebarMenuItem>
        </SidebarMenu>
      </SidebarHeader>
      <SidebarContent className="px-2">
        <SidebarNavigation user={user} role={role} pathname={pathname} />
        <div className="mx-4 mt-auto mb-5 rounded-xl border border-primary/10 bg-primary/4 p-4 group-data-[collapsible=icon]:hidden">
          <ShieldCheck className="mb-2 size-5 text-primary" />
          <p className="text-xs font-medium">Akun pribadi, akses terjaga</p>
          <p className="mt-2 text-[11px] leading-5 text-muted-foreground">
            Jaga kerahasiaan password dan keluar setelah menggunakan perangkat
            bersama.
          </p>
        </div>
      </SidebarContent>
      <SidebarFooter className="border-t p-3">
        <SidebarMenu>
          <SidebarMenuItem>
            <HelpMenu />
          </SidebarMenuItem>
        </SidebarMenu>
        <div className="flex items-center gap-3 rounded-lg px-2 py-3 group-data-[collapsible=icon]:justify-center group-data-[collapsible=icon]:px-0">
          <Avatar className="size-9">
            <AvatarFallback className="bg-[#edf1eb] text-xs font-medium text-primary">
              {initials(user.name)}
            </AvatarFallback>
          </Avatar>
          <div className="min-w-0 group-data-[collapsible=icon]:hidden">
            <p className="truncate text-xs font-medium">{user.name}</p>
            <p className="mt-1 truncate text-[10px] text-muted-foreground">
              {user.email ?? user.username}
            </p>
          </div>
        </div>
      </SidebarFooter>
      <SidebarRail aria-label="Buka atau tutup sidebar" />
    </Sidebar>
  );
}

function HelpMenu() {
  return (
    <Dialog>
      <DialogTrigger asChild>
        <SidebarMenuButton
          tooltip="Pusat bantuan"
          className="h-11 gap-3 px-3 text-muted-foreground"
        >
          <CircleHelp className="size-[18px]" />
          <span>Pusat bantuan</span>
          <ArrowUpRight className="ml-auto size-3.5 group-data-[collapsible=icon]:hidden" />
        </SidebarMenuButton>
      </DialogTrigger>
      <DialogContent className="max-w-sm">
        <DialogHeader>
          <DialogTitle>Bantuan portal madrasah</DialogTitle>
          <DialogDescription className="pt-3 leading-7">
            Hubungi operator madrasah jika memerlukan bantuan penggunaan portal,
            perubahan data akun, atau penyesuaian role. Untuk keamanan, jangan
            membagikan password Anda.
          </DialogDescription>
        </DialogHeader>
      </DialogContent>
    </Dialog>
  );
}
