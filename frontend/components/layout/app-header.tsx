"use client";

import {
  ChevronDown,
  Layers3,
  LockKeyhole,
  Menu,
  PanelLeft,
  UserRound,
} from "lucide-react";
import Link from "next/link";
import { LogoutButton } from "@/components/auth/logout-button";
import { Avatar, AvatarFallback } from "@/components/ui/avatar";
import { Badge } from "@/components/ui/badge";
import { Button } from "@/components/ui/button";
import {
  DropdownMenu,
  DropdownMenuContent,
  DropdownMenuItem,
  DropdownMenuLabel,
  DropdownMenuSeparator,
  DropdownMenuTrigger,
} from "@/components/ui/dropdown-menu";
import { SidebarTrigger, useSidebar } from "@/components/ui/sidebar";
import { initials } from "@/lib/auth/roles";
import type { AuthRole, AuthUser } from "@/lib/auth/types";

export function AppHeader({
  user,
  role,
  pathname,
}: {
  user: AuthUser;
  role?: AuthRole;
  pathname: string;
}) {
  const title =
    pathname === "/pengguna"
      ? "Pengelolaan akun"
      : pathname === "/akun"
        ? "Akun saya"
        : "Beranda";
  return (
    <header className="sticky top-0 z-20 shrink-0 border-b border-border/80 bg-card/95 backdrop-blur-md">
      <nav
        aria-label="Navigasi portal"
        className="mx-auto flex h-16 w-full min-w-0 max-w-[1440px] items-center justify-between gap-3 px-4 sm:px-6 lg:h-[72px] lg:px-8 xl:px-10"
      >
        <div className="flex min-w-0 flex-1 items-center gap-3 sm:gap-4">
          <SidebarTrigger
            className="size-11 shrink-0 rounded-xl border border-border bg-card text-primary hover:border-primary/20 hover:bg-primary/5"
            aria-label="Buka atau tutup menu"
          >
            <Menu aria-hidden="true" className="size-[18px] lg:hidden" />
            <PanelLeft
              aria-hidden="true"
              className="hidden size-[18px] lg:block"
            />
          </SidebarTrigger>
          <div className="min-w-0 space-y-0.5">
            <p className="truncate text-xs leading-4 text-muted-foreground">
              MTs Al Karimiyyah
            </p>
            <p className="truncate text-sm leading-5 font-semibold tracking-tight text-foreground">
              {title}
            </p>
          </div>
        </div>
        <div className="flex shrink-0 items-center gap-3">
          {role && (
            <Badge
              variant="outline"
              className="hidden h-8 max-w-44 min-w-0 items-center gap-2 rounded-full border-primary/15 bg-primary/5 px-3 py-0 text-xs leading-4 font-medium text-primary md:inline-flex"
            >
              <Layers3 aria-hidden="true" />
              <span className="min-w-0 truncate">{role.name}</span>
            </Badge>
          )}
          <UserMenu user={user} role={role} />
        </div>
      </nav>
    </header>
  );
}

function UserMenu({ user, role }: { user: AuthUser; role?: AuthRole }) {
  const { ready } = useSidebar();
  return (
    <DropdownMenu>
      <DropdownMenuTrigger asChild>
        <Button
          type="button"
          variant="ghost"
          disabled={!ready}
          className="h-11 min-w-11 gap-2 rounded-xl border border-border bg-card p-1 hover:border-primary/20 hover:bg-primary/5 sm:pr-2 lg:h-12 lg:gap-3 lg:px-2"
          aria-label="Menu akun"
        >
          <Avatar className="size-9">
            <AvatarFallback className="bg-secondary text-xs font-semibold text-primary">
              {initials(user.name)}
            </AvatarFallback>
          </Avatar>
          <span className="hidden min-w-0 max-w-36 flex-col gap-0.5 text-left lg:flex">
            <span className="truncate text-sm leading-5 font-medium">
              {user.name}
            </span>
            <span className="text-xs leading-4 font-normal text-muted-foreground">
              Kelola akun Anda
            </span>
          </span>
          <ChevronDown
            aria-hidden="true"
            className="hidden size-3.5 shrink-0 text-muted-foreground sm:block"
          />
        </Button>
      </DropdownMenuTrigger>
      <DropdownMenuContent
        align="end"
        sideOffset={10}
        collisionPadding={16}
        className="w-72 max-w-[calc(100vw-2rem)] rounded-xl p-2"
      >
        <DropdownMenuLabel className="space-y-1.5 px-3 py-3 font-normal">
          <p className="break-words text-sm leading-5 font-semibold">
            {user.name}
          </p>
          <p className="break-all text-xs leading-5 text-muted-foreground">
            {user.email ?? user.username}
          </p>
          <Badge
            variant="secondary"
            className="h-auto max-w-full break-words whitespace-normal text-xs leading-5"
          >
            {role?.name ?? "Belum ada role"}
          </Badge>
        </DropdownMenuLabel>
        <DropdownMenuSeparator />
        <DropdownMenuItem asChild className="min-h-11 gap-3 px-3">
          <Link href="/akun">
            <UserRound className="size-4" /> Akun saya
          </Link>
        </DropdownMenuItem>
        <DropdownMenuItem asChild className="min-h-11 gap-3 px-3">
          <Link href="/ganti-password">
            <LockKeyhole className="size-4" /> Ganti password
          </Link>
        </DropdownMenuItem>
        <DropdownMenuSeparator />
        <LogoutButton compact />
      </DropdownMenuContent>
    </DropdownMenu>
  );
}
