"use client";

import {
  ChevronDown,
  Clock3,
  House,
  UserRound,
  UsersRound,
} from "lucide-react";
import Link from "next/link";
import { Badge } from "@/components/ui/badge";
import {
  Collapsible,
  CollapsibleContent,
  CollapsibleTrigger,
} from "@/components/ui/collapsible";
import {
  SidebarGroup,
  SidebarGroupContent,
  SidebarGroupLabel,
  SidebarMenu,
  SidebarMenuButton,
  SidebarMenuItem,
  SidebarMenuSub,
  SidebarMenuSubButton,
  SidebarMenuSubItem,
  useSidebar,
} from "@/components/ui/sidebar";
import { roleHref } from "@/lib/auth/roles";
import type { AuthRole, AuthUser } from "@/lib/auth/types";
import { sidebarSections } from "@/lib/navigation/sidebar";

export function SidebarNavigation({
  user,
  role,
  pathname,
}: {
  user: AuthUser;
  role?: AuthRole;
  pathname: string;
}) {
  const { setOpenMobile, setOpen, state, isMobile } = useSidebar();
  const sections = sidebarSections(user);
  const links = [
    {
      label: "Beranda",
      href: role ? roleHref(role) : "/dashboard",
      icon: House,
      active: pathname.startsWith("/dashboard"),
    },
    ...(user.permissions?.includes("accounts.view")
      ? [
          {
            label: "Pengelolaan akun",
            href: "/pengguna",
            icon: UsersRound,
            active: pathname === "/pengguna",
          },
        ]
      : []),
    {
      label: "Akun saya",
      href: "/akun",
      icon: UserRound,
      active: pathname === "/akun",
    },
  ];
  return (
    <nav aria-label="Menu utama madrasah">
      <SidebarGroup>
        <SidebarGroupLabel className="mb-2 px-3 text-xs text-muted-foreground">
          Menu utama
        </SidebarGroupLabel>
        <SidebarGroupContent>
          <SidebarMenu className="gap-1">
            {links.map((item) => (
              <SidebarMenuItem key={item.href}>
                <SidebarMenuButton
                  asChild
                  tooltip={item.label}
                  isActive={item.active}
                  className="h-11 gap-3 px-3 data-[active=true]:bg-primary/8 data-[active=true]:font-medium data-[active=true]:text-primary"
                >
                  <Link
                    href={item.href}
                    aria-current={item.active ? "page" : undefined}
                    onClick={() => setOpenMobile(false)}
                  >
                    <item.icon className="size-[18px]" aria-hidden="true" />
                    <span>{item.label}</span>
                  </Link>
                </SidebarMenuButton>
              </SidebarMenuItem>
            ))}
          </SidebarMenu>
        </SidebarGroupContent>
      </SidebarGroup>
      {sections.length > 0 && (
        <SidebarGroup>
          <SidebarGroupLabel className="mb-2 px-3 text-xs text-muted-foreground">
            Modul madrasah
          </SidebarGroupLabel>
          <p className="mb-3 px-3 text-xs leading-5 text-muted-foreground group-data-[collapsible=icon]:hidden">
            Menu bertanda jam akan tersedia bertahap.
          </p>
          <SidebarGroupContent>
            <SidebarMenu className="gap-1">
              {sections.map((section) => (
                <Collapsible key={section.id} asChild className="group/section">
                  <SidebarMenuItem>
                    <CollapsibleTrigger asChild>
                      <SidebarMenuButton
                        tooltip={section.label}
                        aria-label={section.label}
                        className="h-auto min-h-11 gap-3 px-3 py-2.5"
                        onClick={() => {
                          if (!isMobile && state === "collapsed") setOpen(true);
                        }}
                      >
                        <section.icon
                          className="size-[18px] shrink-0"
                          aria-hidden="true"
                        />
                        <span className="min-w-0 flex-1 whitespace-normal leading-5 group-data-[collapsible=icon]:hidden">
                          {section.label}
                        </span>
                        <Badge
                          variant="secondary"
                          aria-hidden="true"
                          className="h-5 min-w-5 shrink-0 justify-center px-1 text-xs group-data-[collapsible=icon]:hidden"
                        >
                          {section.items.length}
                        </Badge>
                        <ChevronDown
                          aria-hidden="true"
                          className="size-3.5 shrink-0 transition-transform group-data-[state=open]/section:rotate-180 group-data-[collapsible=icon]:hidden motion-reduce:transition-none"
                        />
                      </SidebarMenuButton>
                    </CollapsibleTrigger>
                    <CollapsibleContent>
                      <SidebarMenuSub className="mr-0 ml-5 px-0 pl-2">
                        {section.items.map((item) => (
                          <SidebarMenuSubItem key={item.id}>
                            <SidebarMenuSubButton
                              asChild
                              className="h-auto min-h-11 gap-2 py-2 text-xs"
                            >
                              <button
                                type="button"
                                disabled
                                aria-label={`${item.label} — Segera hadir`}
                                className="w-full text-left disabled:cursor-not-allowed disabled:opacity-70"
                              >
                                <span className="min-w-0 flex-1 whitespace-normal leading-5">
                                  {item.label}
                                </span>
                                <Clock3
                                  className="size-3.5 shrink-0"
                                  aria-hidden="true"
                                />
                              </button>
                            </SidebarMenuSubButton>
                          </SidebarMenuSubItem>
                        ))}
                      </SidebarMenuSub>
                    </CollapsibleContent>
                  </SidebarMenuItem>
                </Collapsible>
              ))}
            </SidebarMenu>
          </SidebarGroupContent>
        </SidebarGroup>
      )}
    </nav>
  );
}
