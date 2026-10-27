"use client";

import {
  AlertCircle,
  ArrowRight,
  Copy,
  History,
  KeyRound,
  Plus,
  RefreshCw,
  Search,
  ShieldCheck,
  ShieldOff,
  UserRound,
  UsersRound,
} from "lucide-react";
import { type FormEvent, useCallback, useEffect, useState } from "react";
import { toast } from "sonner";
import { ConfirmationDialog } from "@/components/layout/confirmation-dialog";
import { FormDialog } from "@/components/layout/form-dialog";
import { Pager } from "@/components/layout/pager";
import { ReasonField as Reason } from "@/components/layout/reason-field";
import { Alert, AlertDescription } from "@/components/ui/alert";

import { Badge } from "@/components/ui/badge";
import { Button } from "@/components/ui/button";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { Checkbox } from "@/components/ui/checkbox";
import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogFooter,
  DialogHeader,
  DialogTitle,
} from "@/components/ui/dialog";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import { ScrollArea } from "@/components/ui/scroll-area";
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from "@/components/ui/select";
import {
  Sheet,
  SheetContent,
  SheetDescription,
  SheetHeader,
  SheetTitle,
} from "@/components/ui/sheet";
import { Skeleton } from "@/components/ui/skeleton";
import {
  Table,
  TableBody,
  TableCell,
  TableHead,
  TableHeader,
  TableRow,
} from "@/components/ui/table";
import { Tabs, TabsContent, TabsList, TabsTrigger } from "@/components/ui/tabs";
import {
  type AccountRole,
  type AuditEntry,
  accountError,
  accountRequest,
  formatDate,
  type ManagedUser,
  type PageMeta,
} from "@/lib/accounts";
import type { AuthUser } from "@/lib/auth/types";

type RoleData = {
  data: AccountRole[];
  permissions: { code: string; name: string }[];
};
type UserList = {
  data: ManagedUser[];
  meta: PageMeta;
  stats: { total: number; active: number; disabled: number };
};
const emptyMeta: PageMeta = { page: 1, last_page: 1, total: 0 };
const actionLabels: Record<string, string> = {
  status: "Ubah status akun",
  "reset-password": "Reset password",
  "revoke-sessions": "Cabut semua sesi",
  "unlink-google": "Putuskan akun Google",
  "verify-email": "Verifikasi email",
};

export function AccountWorkspace({ user }: { user: AuthUser }) {
  const can = (permission: string) =>
    user.permissions?.includes(`accounts.${permission}`) ?? false;
  const [tab, setTab] = useState("users");
  const [query, setQuery] = useState("");
  const [search, setSearch] = useState("");
  const [status, setStatus] = useState("all");
  const [page, setPage] = useState(1);
  const [list, setList] = useState<UserList>();
  const [catalog, setCatalog] = useState<RoleData>({
    data: [],
    permissions: [],
  });
  const [rolesReady, setRolesReady] = useState(false);
  const [audit, setAudit] = useState<{ data: AuditEntry[]; meta: PageMeta }>();
  const [auditPage, setAuditPage] = useState(1);
  const [error, setError] = useState("");
  const [busy, setBusy] = useState(false);
  const [editor, setEditor] = useState<ManagedUser | "new" | null>(null);
  const [detail, setDetail] = useState<ManagedUser>();
  const [roleEditor, setRoleEditor] = useState<AccountRole | "new" | null>(
    null,
  );
  const [action, setAction] = useState<{ code: string; target: ManagedUser }>();
  const [secret, setSecret] = useState<{
    password: string;
    username: string;
  }>();

  const load = useCallback(async () => {
    setBusy(true);
    setError("");
    try {
      const params = new URLSearchParams({ page: String(page), q: search });
      if (status !== "all") params.set("status", status);
      const result = await accountRequest<UserList>(
        `users?${params.toString()}`,
      );
      setList(result);
    } catch (reason) {
      setError(accountError(reason));
    } finally {
      setBusy(false);
    }
  }, [page, search, status]);
  const loadRoles = useCallback(async () => {
    setCatalog(await accountRequest<RoleData>("roles"));
    setRolesReady(true);
  }, []);
  const loadAudit = useCallback(async () => {
    try {
      setAudit(await accountRequest(`audit?page=${auditPage}`));
    } catch (reason) {
      setError(accountError(reason));
    }
  }, [auditPage]);
  useEffect(() => {
    void load();
  }, [load]);
  useEffect(() => {
    void loadRoles().catch((reason) => setError(accountError(reason)));
  }, [loadRoles]);
  useEffect(() => {
    if (tab === "audit") void loadAudit();
  }, [tab, loadAudit]);

  async function inspect(id: number) {
    try {
      setDetail(
        (await accountRequest<{ data: ManagedUser }>(`users/${id}`)).data,
      );
    } catch (reason) {
      toast.error(accountError(reason));
    }
  }
  function saved(target: ManagedUser, password?: string) {
    setEditor(null);
    setAction(undefined);
    setDetail(undefined);
    if (password) setSecret({ password, username: target.username });
    toast.success("Perubahan akun berhasil disimpan.");
    void load();
    if (target.id === user.id) window.location.assign("/dashboard");
  }

  return (
    <div className="animate-enter space-y-6">
      <div className="flex flex-col justify-between gap-4 sm:flex-row sm:items-center">
        <div>
          <p className="mb-2 text-xs text-muted-foreground">
            Administrasi / Akun & akses
          </p>
          <h1 className="text-[27px] font-semibold tracking-tight sm:text-3xl">
            Pengelolaan akun
          </h1>
          <p className="mt-2 text-sm leading-6 text-muted-foreground">
            Kelola pengguna, role, dan akses portal madrasah dalam satu tempat.
          </p>
        </div>
        {can("create") && (
          <Button
            className="h-11 gap-2 sm:self-center"
            onClick={() => setEditor("new")}
            disabled={can("assign") && !rolesReady}
          >
            <Plus className="size-4" /> Tambah pengguna
          </Button>
        )}
      </div>
      <div className="grid grid-cols-3 gap-3 sm:gap-4">
        {[
          ["Total akun", list?.stats.total, UsersRound],
          ["Aktif", list?.stats.active, ShieldCheck],
          ["Nonaktif", list?.stats.disabled, ShieldOff],
        ].map(([label, count, Icon]) => {
          const StatIcon = Icon as typeof UsersRound;
          return (
            <Card
              key={String(label)}
              className="rounded-xl border border-border/70 py-4 ring-0"
            >
              <CardContent className="px-4">
                <div className="flex items-center justify-between gap-1">
                  <p className="text-xs text-muted-foreground sm:text-xs">
                    {String(label)}
                  </p>
                  <StatIcon className="hidden size-4 text-primary/65 sm:block" />
                </div>
                <p className="mt-2 text-2xl font-semibold tracking-tight">
                  {count === undefined ? "—" : String(count)}
                </p>
              </CardContent>
            </Card>
          );
        })}
      </div>
      {error && (
        <Alert variant="destructive">
          <AlertCircle />
          <AlertDescription>{error}</AlertDescription>
        </Alert>
      )}
      <Tabs value={tab} onValueChange={setTab}>
        <TabsList className="mb-5 h-14! w-full justify-start gap-1 rounded-xl bg-secondary p-1 sm:w-auto">
          <TabsTrigger
            value="users"
            className="h-11! min-w-0 flex-1 gap-1 px-2 text-xs sm:flex-none sm:gap-2 sm:px-3 sm:text-xs"
          >
            <UsersRound className="size-3.5" /> Pengguna
          </TabsTrigger>
          <TabsTrigger
            value="roles"
            className="h-11! min-w-0 flex-1 gap-1 px-2 text-xs sm:flex-none sm:gap-2 sm:px-3 sm:text-xs"
          >
            <ShieldCheck className="size-3.5" /> Role & izin
          </TabsTrigger>
          {can("audit") && (
            <TabsTrigger
              value="audit"
              className="h-11! min-w-0 flex-1 gap-1 px-2 text-xs sm:flex-none sm:gap-2 sm:px-3 sm:text-xs"
            >
              <History className="size-3.5" /> Audit
            </TabsTrigger>
          )}
        </TabsList>
        <TabsContent value="users" className="space-y-4">
          <Card className="rounded-2xl border border-border/70 py-0 ring-0">
            <CardContent className="p-4 sm:p-5">
              <div className="mb-5 flex flex-wrap items-center gap-3">
                <form
                  className="flex min-w-0 basis-full items-center gap-2 sm:flex-1 sm:basis-auto"
                  onSubmit={(event) => {
                    event.preventDefault();
                    setPage(1);
                    setSearch(query.trim());
                  }}
                >
                  <div className="relative min-w-0 flex-1">
                    <Search className="absolute top-3.5 left-3 size-4 text-muted-foreground" />
                    <Input
                      aria-label="Cari pengguna"
                      placeholder="Cari nama, username, atau email…"
                      value={query}
                      onChange={(event) => setQuery(event.target.value)}
                      maxLength={100}
                      className="h-11 bg-background/60 pl-10"
                    />
                  </div>
                  <Button
                    type="submit"
                    variant="outline"
                    className="h-11 text-xs"
                  >
                    Cari
                  </Button>
                </form>
                <Select
                  value={status}
                  onValueChange={(value) => {
                    setPage(1);
                    setStatus(value);
                  }}
                >
                  <SelectTrigger
                    aria-label="Filter status akun"
                    className="h-11! w-[130px]"
                  >
                    <SelectValue />
                  </SelectTrigger>
                  <SelectContent>
                    <SelectItem value="all">Semua status</SelectItem>
                    <SelectItem value="active">Aktif</SelectItem>
                    <SelectItem value="disabled">Nonaktif</SelectItem>
                  </SelectContent>
                </Select>
                <Button
                  variant="outline"
                  size="icon"
                  className="size-11"
                  aria-label="Muat ulang pengguna"
                  disabled={busy}
                  onClick={() => {
                    void load();
                    void loadRoles().catch((reason) =>
                      setError(accountError(reason)),
                    );
                  }}
                >
                  <RefreshCw
                    className={busy ? "size-4 animate-spin" : "size-4"}
                  />
                </Button>
              </div>
              {busy && !list ? (
                <div className="space-y-3">
                  {[1, 2, 3].map((key) => (
                    <Skeleton key={key} className="h-16 w-full" />
                  ))}
                </div>
              ) : !list?.data.length ? (
                <div className="py-14 text-center">
                  <UsersRound className="mx-auto mb-4 size-8 text-primary/45" />
                  <p className="text-sm font-medium">
                    Belum ada pengguna yang sesuai
                  </p>
                  <p className="mt-2 text-xs text-muted-foreground">
                    Tambahkan pengguna atau sesuaikan pencarian Anda.
                  </p>
                </div>
              ) : (
                <>
                  <div className="hidden md:block">
                    <Table>
                      <TableHeader>
                        <TableRow>
                          <TableHead>Pengguna</TableHead>
                          <TableHead>Role</TableHead>
                          <TableHead>Status</TableHead>
                          <TableHead>Login terakhir</TableHead>
                          <TableHead className="text-right">Detail</TableHead>
                        </TableRow>
                      </TableHeader>
                      <TableBody>
                        {list.data.map((item) => (
                          <TableRow key={item.id}>
                            <TableCell className="py-4">
                              <p className="text-sm font-medium">{item.name}</p>
                              <p className="mt-1 text-xs text-muted-foreground">
                                {item.email ?? item.username}
                              </p>
                            </TableCell>
                            <TableCell>
                              <div className="flex max-w-52 flex-wrap gap-1">
                                {item.roles.length ? (
                                  item.roles.map((role) => (
                                    <Badge
                                      variant="secondary"
                                      key={role.id}
                                      className="h-auto min-h-5 max-w-full break-all whitespace-normal text-xs"
                                    >
                                      {role.name}
                                    </Badge>
                                  ))
                                ) : (
                                  <span className="text-xs text-muted-foreground">
                                    Belum ada role
                                  </span>
                                )}
                              </div>
                            </TableCell>
                            <TableCell>
                              <Status user={item} />
                            </TableCell>
                            <TableCell className="text-xs text-muted-foreground">
                              {formatDate(item.last_login_at)}
                            </TableCell>
                            <TableCell className="text-right">
                              <Button
                                variant="outline"
                                className="h-11 text-xs"
                                aria-label={`Kelola ${item.name}`}
                                onClick={() => inspect(item.id)}
                              >
                                Kelola
                              </Button>
                            </TableCell>
                          </TableRow>
                        ))}
                      </TableBody>
                    </Table>
                  </div>
                  <div className="divide-y md:hidden">
                    {list.data.map((item) => (
                      <div key={item.id} className="py-4 first:pt-0">
                        <div className="flex items-start justify-between gap-2">
                          <div className="min-w-0">
                            <p className="break-words text-sm font-medium">
                              {item.name}
                            </p>
                            <p className="mt-1 break-all text-xs text-muted-foreground">
                              {item.email ?? item.username}
                            </p>
                          </div>
                          <Status user={item} />
                        </div>
                        <div className="mt-3 flex flex-wrap items-center justify-between gap-3">
                          <div className="flex max-w-[70%] flex-wrap gap-1">
                            {item.roles.map((role) => (
                              <Badge
                                variant="secondary"
                                key={role.id}
                                className="h-auto min-h-5 max-w-full break-all whitespace-normal text-xs"
                              >
                                {role.name}
                              </Badge>
                            ))}
                          </div>
                          <Button
                            variant="outline"
                            className="h-11 text-xs"
                            aria-label={`Kelola ${item.name}`}
                            onClick={() => inspect(item.id)}
                          >
                            Kelola <ArrowRight className="size-3" />
                          </Button>
                        </div>
                      </div>
                    ))}
                  </div>
                </>
              )}
              <Pager meta={list?.meta ?? emptyMeta} setPage={setPage} />
            </CardContent>
          </Card>
        </TabsContent>
        <TabsContent value="roles" className="space-y-4">
          <div className="flex items-center justify-between gap-3">
            <p className="break-words text-xs leading-6 text-muted-foreground">
              Izin di halaman ini khusus pengelolaan akun. Akses data sensitif
              tetap memerlukan mandat.
            </p>
            {can("roles") && (
              <Button
                variant="outline"
                className="h-11 shrink-0 gap-2 text-xs"
                onClick={() => setRoleEditor("new")}
                disabled={!rolesReady}
              >
                <Plus className="size-4" /> Tambah role
              </Button>
            )}
          </div>
          <div className="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
            {catalog.data.map((role) => (
              <Card
                key={role.id}
                className="rounded-xl border border-border/70 ring-0"
              >
                <CardHeader>
                  <div className="flex items-center justify-between gap-2">
                    <ShieldCheck className="size-5 text-primary" />
                    <Badge
                      variant={role.is_active ? "secondary" : "outline"}
                      className="text-xs"
                    >
                      {role.is_active ? "Aktif" : "Nonaktif"}
                    </Badge>
                  </div>
                  <CardTitle className="mt-3 break-words text-sm font-semibold">
                    {role.name}
                  </CardTitle>
                  <p className="break-words text-xs text-muted-foreground">
                    {role.code}
                  </p>
                </CardHeader>
                <CardContent>
                  <p className="break-words text-xs leading-6 text-muted-foreground">
                    {role.code === "super_admin"
                      ? "Pengelola utama akun dan akses madrasah. Role ini dilindungi."
                      : (role.description ?? "Role pengguna portal madrasah.")}
                  </p>
                  <p className="mt-3 text-xs text-muted-foreground">
                    {role.permission_codes?.length ?? 0} izin pengelolaan akun
                  </p>
                  {can("roles") && role.code !== "super_admin" && (
                    <Button
                      variant="outline"
                      className="mt-4 h-11 w-full text-xs"
                      onClick={() => setRoleEditor(role)}
                    >
                      Atur role & izin
                    </Button>
                  )}
                </CardContent>
              </Card>
            ))}
          </div>
        </TabsContent>
        {can("audit") && (
          <TabsContent value="audit">
            <Card className="rounded-2xl border border-border/70 py-0 ring-0">
              <CardContent className="p-4 sm:p-5">
                <p className="mb-4 text-xs leading-6 text-muted-foreground">
                  Riwayat perubahan akun dan aktivitas autentikasi. Password dan
                  token tidak ditampilkan dalam audit.
                </p>
                <div className="divide-y">
                  {audit?.data.map((item) => (
                    <div key={item.id} className="py-4">
                      <div className="flex flex-wrap justify-between gap-2">
                        <p className="text-xs font-medium">{item.event}</p>
                        <p className="break-words text-xs text-muted-foreground">
                          {formatDate(item.occurred_at)}
                        </p>
                      </div>
                      <p className="mt-2 text-xs text-muted-foreground">
                        {item.actor} · {item.object_type} #{item.object_id}
                      </p>
                      {item.reason && (
                        <p className="mt-2 break-words text-xs leading-6">
                          {item.reason}
                        </p>
                      )}
                    </div>
                  ))}
                  {audit?.data.length === 0 && (
                    <p className="py-10 text-center text-sm text-muted-foreground">
                      Belum ada aktivitas yang dicatat.
                    </p>
                  )}
                </div>
                <Pager meta={audit?.meta ?? emptyMeta} setPage={setAuditPage} />
              </CardContent>
            </Card>
          </TabsContent>
        )}
      </Tabs>
      <Sheet
        open={Boolean(detail)}
        onOpenChange={(open) => {
          if (!open) setDetail(undefined);
        }}
      >
        <SheetContent
          side="right"
          className="w-full! gap-0 overflow-hidden sm:max-w-lg!"
        >
          <SheetHeader className="border-b p-6">
            <SheetTitle>Detail pengguna</SheetTitle>
            <SheetDescription>
              Informasi akun, keamanan, dan riwayat role.
            </SheetDescription>
          </SheetHeader>
          {detail && (
            <ScrollArea className="min-h-0 flex-1">
              <div className="space-y-6 p-5 pb-[max(1.5rem,env(safe-area-inset-bottom))] sm:p-6">
                <div>
                  <div className="mb-3 flex size-12 items-center justify-center rounded-xl bg-primary/8">
                    <UserRound className="size-6 text-primary" />
                  </div>
                  <h2 className="break-words text-lg font-semibold">
                    {detail.name}
                  </h2>
                  <p className="mt-1 break-all text-xs text-muted-foreground">
                    {detail.email ?? "Tanpa email"}
                  </p>
                  <div className="mt-3">
                    <Status user={detail} />
                  </div>
                </div>
                <dl className="grid grid-cols-2 gap-4 text-xs">
                  {[
                    ["Username", detail.username],
                    [
                      "Email",
                      detail.email_verified
                        ? "Terverifikasi"
                        : "Belum terverifikasi",
                    ],
                    [
                      "Google",
                      detail.google_linked ? "Terhubung" : "Belum terhubung",
                    ],
                    [
                      "Password",
                      detail.must_change_password ? "Wajib diganti" : "Pribadi",
                    ],
                  ].map(([label, value]) => (
                    <div key={label}>
                      <dt className="text-muted-foreground">{label}</dt>
                      <dd className="mt-1.5 break-all font-medium">{value}</dd>
                    </div>
                  ))}
                </dl>
                <div className="flex flex-wrap gap-2">
                  {can("update") && (
                    <Button
                      variant="outline"
                      className="h-11 text-xs"
                      onClick={() => {
                        setEditor(detail);
                        setDetail(undefined);
                      }}
                    >
                      Ubah akun
                    </Button>
                  )}
                  {can("status") && detail.id !== user.id && (
                    <Button
                      variant="outline"
                      className="h-11 text-xs"
                      onClick={() => {
                        setAction({ code: "status", target: detail });
                        setDetail(undefined);
                      }}
                    >
                      {detail.status === "active"
                        ? "Nonaktifkan akun"
                        : "Aktifkan akun"}
                    </Button>
                  )}
                  {can("reset") && detail.id !== user.id && (
                    <Button
                      variant="outline"
                      className="h-11 text-xs"
                      onClick={() => {
                        setAction({ code: "reset-password", target: detail });
                        setDetail(undefined);
                      }}
                    >
                      <KeyRound className="size-3.5" />
                      Reset password
                    </Button>
                  )}
                  {can("verify") && detail.email && !detail.email_verified && (
                    <Button
                      variant="outline"
                      className="h-11 text-xs"
                      onClick={() => {
                        setAction({ code: "verify-email", target: detail });
                        setDetail(undefined);
                      }}
                    >
                      Verifikasi email
                    </Button>
                  )}
                  {can("sessions") && detail.google_linked && (
                    <Button
                      variant="outline"
                      className="h-11 text-xs"
                      onClick={() => {
                        setAction({ code: "unlink-google", target: detail });
                        setDetail(undefined);
                      }}
                    >
                      Putuskan Google
                    </Button>
                  )}
                </div>
                <div className="border-t pt-5">
                  <div className="flex flex-wrap items-center justify-between gap-3">
                    <h3 className="text-sm font-medium">
                      Sesi aktif ({detail.active_sessions})
                    </h3>
                    {can("sessions") && detail.active_sessions > 0 && (
                      <Button
                        variant="outline"
                        className="h-11 text-xs text-destructive"
                        onClick={() => {
                          setAction({
                            code: "revoke-sessions",
                            target: detail,
                          });
                          setDetail(undefined);
                        }}
                      >
                        Cabut semua sesi
                      </Button>
                    )}
                  </div>
                  <div className="mt-3 space-y-2">
                    {detail.sessions?.map((session) => (
                      <Card
                        key={session.id}
                        className="rounded-lg bg-background py-3 ring-0"
                      >
                        <CardContent className="px-3">
                          <p className="text-xs font-medium">
                            Login{" "}
                            {session.login_method === "google"
                              ? "Google"
                              : "password"}
                          </p>
                          <p className="mt-1 text-xs text-muted-foreground">
                            Aktivitas terakhir:{" "}
                            {formatDate(session.last_used_at)}
                          </p>
                        </CardContent>
                      </Card>
                    ))}
                    {!detail.sessions?.length && (
                      <p className="text-xs text-muted-foreground">
                        Tidak ada sesi aktif.
                      </p>
                    )}
                  </div>
                </div>
                <div className="border-t pt-5">
                  <h3 className="mb-4 text-sm font-medium">Riwayat role</h3>
                  <div className="space-y-4">
                    {detail.role_history?.map((grant) => (
                      <div
                        key={grant.id}
                        className="border-l-2 border-primary/20 pl-3"
                      >
                        <p className="text-xs font-medium">{grant.role}</p>
                        <p className="mt-1 text-xs leading-5 text-muted-foreground">
                          {formatDate(grant.effective_from)} →{" "}
                          {grant.effective_to
                            ? formatDate(grant.effective_to)
                            : "Masih berlaku"}
                        </p>
                        <p className="mt-1 text-xs text-muted-foreground">
                          Diberikan oleh {grant.granted_by}
                          {grant.revoked_by
                            ? `; dicabut oleh ${grant.revoked_by}`
                            : ""}
                        </p>
                        {grant.reason && (
                          <p className="mt-1 text-xs leading-5">
                            {grant.reason}
                          </p>
                        )}
                      </div>
                    ))}
                    {!detail.role_history?.length && (
                      <p className="text-xs text-muted-foreground">
                        Belum ada role yang diberikan.
                      </p>
                    )}
                  </div>
                </div>
              </div>
            </ScrollArea>
          )}
        </SheetContent>
      </Sheet>
      {editor && (
        <UserEditor
          current={editor === "new" ? undefined : editor}
          roles={catalog.data}
          assign={can("assign")}
          actorIsSuper={user.roles.some((role) => role.code === "super_admin")}
          close={() => setEditor(null)}
          saved={saved}
        />
      )}
      {action && (
        <ActionDialog
          action={action.code}
          target={action.target}
          close={() => setAction(undefined)}
          saved={saved}
        />
      )}
      {roleEditor && (
        <RoleEditor
          current={roleEditor === "new" ? undefined : roleEditor}
          catalog={catalog}
          ownPermissions={user.permissions ?? []}
          close={() => setRoleEditor(null)}
          saved={() => {
            setRoleEditor(null);
            void loadRoles().catch((reason) => setError(accountError(reason)));
            toast.success("Role dan izin berhasil disimpan.");
          }}
        />
      )}
      <Dialog
        open={Boolean(secret)}
        onOpenChange={(open) => {
          if (!open) setSecret(undefined);
        }}
      >
        <DialogContent className="max-w-md">
          <DialogHeader>
            <DialogTitle>Password sementara siap</DialogTitle>
            <DialogDescription>
              Password ini berlaku 24 jam untuk satu kali login. Pengguna wajib
              menggantinya setelah masuk. Simpan sekarang karena password hanya
              ditampilkan sekali.
            </DialogDescription>
          </DialogHeader>
          <Card className="bg-background ring-0">
            <CardContent className="space-y-3">
              <p className="text-xs text-muted-foreground">
                Username:{" "}
                <span className="font-medium text-foreground">
                  {secret?.username}
                </span>
              </p>
              <code className="block break-all rounded-lg border bg-white p-3 text-sm font-semibold">
                {secret?.password}
              </code>
              <Button
                variant="outline"
                className="h-11 w-full gap-2"
                onClick={async () => {
                  try {
                    await navigator.clipboard.writeText(secret?.password ?? "");
                    toast.success("Password disalin.");
                  } catch {
                    toast.error("Salin password secara manual.");
                  }
                }}
              >
                <Copy className="size-4" /> Salin password
              </Button>
            </CardContent>
          </Card>
          <DialogFooter>
            <Button
              className="h-11 w-full"
              onClick={() => setSecret(undefined)}
            >
              Sudah saya simpan
            </Button>
          </DialogFooter>
        </DialogContent>
      </Dialog>
    </div>
  );
}

function Status({ user }: { user: ManagedUser }) {
  return (
    <Badge
      variant={user.status === "active" ? "secondary" : "outline"}
      className={
        user.status === "active"
          ? "shrink-0 bg-primary/8 text-xs text-primary"
          : "shrink-0 text-xs text-muted-foreground"
      }
    >
      {user.status === "active" ? "Aktif" : "Nonaktif"}
    </Badge>
  );
}

function UserEditor({
  current,
  roles,
  assign,
  actorIsSuper,
  close,
  saved,
}: {
  current?: ManagedUser;
  roles: AccountRole[];
  assign: boolean;
  actorIsSuper: boolean;
  close: () => void;
  saved: (user: ManagedUser, password?: string) => void;
}) {
  const [busy, setBusy] = useState(false);
  const [error, setError] = useState("");
  const [roleIds, setRoleIds] = useState(
    current?.roles.map((role) => role.id) ?? [],
  );
  const [personId, setPersonId] = useState("new");
  const [people, setPeople] = useState<{ id: number; full_name: string }[]>([]);
  const [personQuery, setPersonQuery] = useState("");
  useEffect(() => {
    if (current) return;
    let active = true;
    const timer = window.setTimeout(() => {
      accountRequest<{ data: { id: number; full_name: string }[] }>(
        `people?q=${encodeURIComponent(personQuery)}`,
      )
        .then((r) => {
          if (active) setPeople(r.data);
        })
        .catch((reason) => {
          if (active) setError(accountError(reason));
        });
    }, 250);
    return () => {
      active = false;
      window.clearTimeout(timer);
    };
  }, [current, personQuery]);
  async function submit(event: FormEvent<HTMLFormElement>) {
    event.preventDefault();
    setBusy(true);
    setError("");
    const body: Record<string, unknown> = Object.fromEntries(
      new FormData(event.currentTarget),
    );
    if (assign) body.role_ids = roleIds;
    if (current) body.lock_version = current.lock_version;
    else if (personId !== "new") body.person_id = Number(personId);
    try {
      const result = await accountRequest<{
        data: ManagedUser;
        temporary_password?: string;
      }>(
        current ? `users/${current.id}` : "users",
        current ? "PUT" : "POST",
        body,
      );
      saved(result.data, result.temporary_password);
    } catch (reason) {
      setError(accountError(reason));
      setBusy(false);
    }
  }
  return (
    <FormDialog
      title={current ? "Ubah akun pengguna" : "Tambah pengguna"}
      description={
        current
          ? "Perubahan email akan mencabut sesi, tautan Google, dan verifikasi email."
          : "Satu orang menggunakan satu akun. Password sementara dibuat otomatis dan wajib diganti saat login pertama."
      }
      busy={busy}
      onClose={close}
      onSubmit={submit}
      submitLabel={current ? "Simpan perubahan" : "Buat akun"}
    >
      {error && (
        <Alert variant="destructive">
          <AlertDescription>{error}</AlertDescription>
        </Alert>
      )}
      {!current && (
        <div className="space-y-2">
          <Label htmlFor="person">Identitas pengguna</Label>
          <Select value={personId} onValueChange={setPersonId}>
            <SelectTrigger id="person" className="h-11! w-full">
              <SelectValue />
            </SelectTrigger>
            <SelectContent>
              <SelectItem value="new">Buat identitas baru</SelectItem>
              {people.map((person) => (
                <SelectItem key={person.id} value={String(person.id)}>
                  {person.full_name} (#{person.id})
                </SelectItem>
              ))}
            </SelectContent>
          </Select>
          <Input
            aria-label="Cari identitas yang sudah ada"
            placeholder="Cari identitas yang sudah ada…"
            value={personQuery}
            onChange={(event) => setPersonQuery(event.target.value)}
            className="h-11"
          />
          <p className="text-xs leading-5 text-muted-foreground">
            Pilih identitas yang sudah ada agar profil pegawai/wali tetap
            menggunakan akun yang sama.
          </p>
        </div>
      )}
      {[
        {
          name: "name",
          label: "Nama tampilan",
          value: current?.name ?? "",
          max: 200,
        },
        {
          name: "username",
          label: "Username",
          value: current?.username ?? "",
          max: 100,
        },
        {
          name: "email",
          label: "Email (opsional)",
          value: current?.email ?? "",
          max: 254,
        },
      ].map((field) => (
        <div key={field.name} className="space-y-2">
          <Label htmlFor={field.name}>{field.label}</Label>
          <Input
            id={field.name}
            name={field.name}
            type={field.name === "email" ? "email" : "text"}
            defaultValue={field.value}
            maxLength={field.max}
            required={field.name !== "email"}
            className="h-11"
            autoComplete="off"
            disabled={busy}
          />
        </div>
      ))}
      {assign && (
        <fieldset className="space-y-2">
          <legend className="mb-2 text-xs font-medium">Role pengguna</legend>
          <ScrollArea className="h-44 rounded-xl border">
            <div className="grid grid-cols-1 gap-1 p-2 sm:grid-cols-2">
              {roles
                .filter(
                  (role) =>
                    role.is_active &&
                    (role.can_assign !== false || roleIds.includes(role.id)) &&
                    (actorIsSuper || role.code !== "super_admin"),
                )
                .map((role) => (
                  <Label
                    key={role.id}
                    className="flex min-h-11 cursor-pointer items-center gap-3 rounded-lg px-2 text-sm hover:bg-muted"
                  >
                    <Checkbox
                      checked={roleIds.includes(role.id)}
                      disabled={busy}
                      onCheckedChange={(checked) =>
                        setRoleIds(
                          checked
                            ? [...roleIds, role.id]
                            : roleIds.filter((id) => id !== role.id),
                        )
                      }
                    />
                    {role.name}
                  </Label>
                ))}
            </div>
          </ScrollArea>
        </fieldset>
      )}
      <Reason disabled={busy} />
    </FormDialog>
  );
}

function ActionDialog({
  action,
  target,
  close,
  saved,
}: {
  action: string;
  target: ManagedUser;
  close: () => void;
  saved: (user: ManagedUser, password?: string) => void;
}) {
  const [busy, setBusy] = useState(false);
  const [error, setError] = useState("");
  const [confirmed, setConfirmed] = useState(false);
  const identity = ["reset-password", "verify-email"].includes(action);
  async function submit(event: FormEvent<HTMLFormElement>) {
    event.preventDefault();
    setBusy(true);
    setError("");
    const body: Record<string, unknown> = {
      ...Object.fromEntries(new FormData(event.currentTarget)),
      lock_version: target.lock_version,
    };
    if (action === "status")
      body.status = target.status === "active" ? "disabled" : "active";
    if (identity) body.identity_confirmed = confirmed;
    try {
      const result = await accountRequest<{
        data: ManagedUser;
        temporary_password?: string;
      }>(`users/${target.id}/${action}`, "POST", body);
      saved(result.data, result.temporary_password);
    } catch (reason) {
      setError(accountError(reason));
      setBusy(false);
    }
  }
  return (
    <ConfirmationDialog
      title={actionLabels[action]}
      description={
        <>
          {target.name} · {target.username}.{" "}
          {action === "reset-password"
            ? "Password lama diganti, seluruh sesi dicabut, dan password sementara berlaku 24 jam untuk satu kali login."
            : action === "unlink-google"
              ? "Tautan Google dan verifikasi email akan dicabut agar tidak terhubung otomatis kembali."
              : action === "verify-email"
                ? "Lanjutkan hanya setelah kepemilikan email dan identitas telah diverifikasi."
                : "Tindakan ini akan mencabut sesi login pengguna."}
        </>
      }
      busy={busy}
      onClose={close}
      onSubmit={submit}
      disabled={identity && !confirmed}
      destructive={
        action !== "verify-email" &&
        !(action === "status" && target.status !== "active")
      }
    >
      {error && (
        <Alert variant="destructive">
          <AlertDescription>{error}</AlertDescription>
        </Alert>
      )}
      {identity && (
        <Label className="flex min-h-12 items-center gap-3 rounded-xl border bg-muted/30 p-3 text-sm leading-6">
          <Checkbox
            checked={confirmed}
            onCheckedChange={(value) => setConfirmed(value === true)}
          />
          Saya telah memeriksa identitas dan bukti kepemilikan akun/email.
        </Label>
      )}
      <Reason disabled={busy} />
    </ConfirmationDialog>
  );
}

function RoleEditor({
  current,
  catalog,
  ownPermissions,
  close,
  saved,
}: {
  current?: AccountRole;
  catalog: RoleData;
  ownPermissions: string[];
  close: () => void;
  saved: () => void;
}) {
  const [permissions, setPermissions] = useState(
    current?.permission_codes ?? [],
  );
  const [active, setActive] = useState(current?.is_active ?? true);
  const [busy, setBusy] = useState(false);
  const [error, setError] = useState("");
  async function submit(event: FormEvent<HTMLFormElement>) {
    event.preventDefault();
    setBusy(true);
    setError("");
    const body = {
      ...Object.fromEntries(new FormData(event.currentTarget)),
      permission_codes: permissions,
      is_active: active,
    };
    try {
      await accountRequest(
        current ? `roles/${current.id}` : "roles",
        current ? "PUT" : "POST",
        body,
      );
      saved();
    } catch (reason) {
      setError(accountError(reason));
      setBusy(false);
    }
  }
  return (
    <FormDialog
      title={current ? "Ubah role" : "Tambah role"}
      description={"Atur hak akses pengelolaan akun sesuai tugas petugas."}
      busy={busy}
      onClose={close}
      onSubmit={submit}
      submitLabel={"Simpan role"}
    >
      {error && (
        <Alert variant="destructive">
          <AlertDescription>{error}</AlertDescription>
        </Alert>
      )}
      {[
        {
          name: "code",
          label: "Kode role",
          max: 50,
          value: current?.code ?? "",
        },
        {
          name: "name",
          label: "Nama role",
          max: 100,
          value: current?.name ?? "",
        },
        {
          name: "description",
          label: "Deskripsi",
          max: 1000,
          value: current?.description ?? "",
        },
      ].map((field) => (
        <div key={field.name} className="space-y-2">
          <Label htmlFor={field.name}>{field.label}</Label>
          <Input
            id={field.name}
            name={field.name}
            defaultValue={field.value}
            required={field.name !== "description"}
            readOnly={field.name === "code" && current?.is_system === true}
            maxLength={field.max}
            className="h-11"
          />
        </div>
      ))}
      <Label className="flex min-h-11 items-center gap-3 text-sm">
        <Checkbox
          checked={active}
          onCheckedChange={(value) => setActive(value === true)}
        />
        Role aktif
      </Label>
      <fieldset>
        <legend className="mb-2 text-xs font-medium">
          Izin pengelolaan akun
        </legend>
        <ScrollArea className="h-52 rounded-xl border">
          <div className="space-y-1 p-2">
            {catalog.permissions
              .filter((p) => ownPermissions.includes(p.code))
              .map((p) => (
                <Label
                  key={p.code}
                  className="flex min-h-11 items-center gap-3 rounded-lg px-2 text-sm leading-5 hover:bg-muted"
                >
                  <Checkbox
                    checked={permissions.includes(p.code)}
                    onCheckedChange={(checked) =>
                      setPermissions(
                        checked
                          ? [...permissions, p.code]
                          : permissions.filter((code) => code !== p.code),
                      )
                    }
                  />
                  {p.name}
                </Label>
              ))}
          </div>
        </ScrollArea>
      </fieldset>
      <Reason disabled={busy} />
    </FormDialog>
  );
}
