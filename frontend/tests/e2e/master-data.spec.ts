import { expect, type Page, test } from "@playwright/test";

async function login(page: Page, username = "super_admin") {
  await page.goto("/login");
  await page.getByLabel("Username atau email").fill(username);
  await page.getByLabel("Password", { exact: true }).fill("Password12345");
  await page
    .getByRole("button", { name: "Masuk ke portal", exact: true })
    .click();
  await expect(page).toHaveURL(new RegExp(`/dashboard/${username}$`));
}
async function search(page: Page, text: string) {
  await page
    .getByLabel(
      /^Cari (siswa|wali|pegawai|jabatan|kategori pegawai|mata pelajaran|status siswa|status kehadiran)$/,
    )
    .fill(text);
  await page.getByRole("button", { name: "Cari", exact: true }).click();
}
async function fits(page: Page) {
  await expect
    .poll(() =>
      page.evaluate(() => document.documentElement.scrollWidth <= innerWidth),
    )
    .toBe(true);
}

test.beforeEach(async ({ page, request }) => {
  await request.post("http://127.0.0.1:43990/__test/reset");
  await page.emulateMedia({ reducedMotion: "reduce" });
});

for (const item of [
  {
    section: "students",
    singular: "siswa",
    fields: {
      "Nama lengkap": "Siswa Baru",
      "Kode internal siswa": "S-NEW",
      "Tanggal lahir (opsional)": "2012-01-01",
    },
    name: "Siswa Baru",
  },
  {
    section: "guardians",
    singular: "wali",
    fields: { "Nama lengkap": "Wali Baru", "Kode internal wali": "W-NEW" },
    name: "Wali Baru",
  },
  {
    section: "employees",
    singular: "pegawai",
    fields: { "Nama lengkap": "Pegawai Baru", "Nomor pegawai": "P-NEW" },
    name: "Pegawai Baru",
  },
  {
    section: "subjects",
    singular: "mata pelajaran",
    fields: {
      "Kode mata pelajaran": "IPA",
      "Nama mata pelajaran": "Ilmu Pengetahuan Alam",
    },
    name: "Ilmu Pengetahuan Alam",
  },
  {
    section: "positions",
    singular: "jabatan",
    fields: {
      "Kode jabatan": "WAKAMAD",
      "Nama jabatan": "Wakil Kepala Madrasah",
    },
    name: "Wakil Kepala Madrasah",
  },
  {
    section: "positions",
    tab: "Kategori pegawai",
    singular: "kategori pegawai",
    fields: { "Kode kategori": "kontrak", "Nama kategori": "Pegawai Kontrak" },
    name: "Pegawai Kontrak",
  },
  {
    section: "references",
    singular: "status siswa",
    fields: { "Kode status": "cuti", "Nama status": "Cuti Belajar" },
    name: "Cuti Belajar",
  },
  {
    section: "references",
    tab: "Status kehadiran",
    singular: "status kehadiran",
    fields: { "Kode status": "pelatihan", "Nama status": "Pelatihan" },
    name: "Pelatihan",
  },
]) {
  test(`master ${item.singular} supports create, search and archival through real BFF requests`, async ({
    page,
  }) => {
    await login(page);
    await page.goto(`/data-master/${item.section}`);
    if (item.tab)
      await page.getByRole("tab", { name: item.tab, exact: true }).click();
    await page
      .getByRole("button", { name: `Tambah ${item.singular}`, exact: true })
      .click();
    const dialog = page.getByRole("dialog", {
      name: `Tambah ${item.singular}`,
      exact: true,
    });
    for (const [label, value] of Object.entries(item.fields))
      await dialog.getByLabel(label, { exact: true }).fill(value);
    await dialog
      .getByLabel("Alasan / dasar perubahan")
      .fill("Pencatatan data sesuai dokumen terverifikasi");
    await dialog
      .getByRole("button", { name: "Simpan data", exact: true })
      .click();
    await expect(dialog).not.toBeVisible();
    await search(page, item.name);
    await page
      .getByRole("button", { name: `Kelola ${item.name}`, exact: true })
      .click();
    const detail = page.getByRole("dialog", { name: item.name, exact: true });
    await expect(detail).toBeVisible();
    await detail
      .getByRole("button", {
        name:
          item.section === "students" ? "Arsipkan siswa" : "Nonaktifkan data",
        exact: true,
      })
      .click();
    const confirm = page.getByRole("alertdialog");
    await expect(confirm).toBeVisible();
    await confirm
      .getByLabel("Alasan / dasar perubahan")
      .fill("Data tidak lagi dipakai untuk proses berjalan");
    await confirm
      .getByRole("button", { name: "Konfirmasi tindakan", exact: true })
      .click();
    await expect(confirm).not.toBeVisible();
    await expect(
      page
        .getByRole("row")
        .filter({ hasText: item.name })
        .getByText(item.section === "students" ? "Diarsipkan" : "Nonaktif", {
          exact: true,
        }),
    ).toBeVisible();
  });
}

test("institution editing and stale writes preserve inputs and require an explicit reload", async ({
  page,
  request,
}) => {
  await login(page);
  await page.goto("/data-master/institution");
  await page
    .getByRole("button", { name: "Ubah identitas", exact: true })
    .click();
  const dialog = page.getByRole("dialog", {
    name: "Ubah identitas madrasah",
    exact: true,
  });
  await dialog
    .getByLabel("Nama madrasah", { exact: true })
    .fill("MTs Nama Diperbarui");
  await dialog
    .getByLabel("Alasan / dasar perubahan")
    .fill("Pembaruan identitas resmi madrasah");
  await request.post("http://127.0.0.1:43990/__test/master-fail-next", {
    data: { mode: "conflict" },
  });
  await dialog
    .getByRole("button", { name: "Simpan perubahan", exact: true })
    .click();
  await expect(dialog.getByLabel("Nama madrasah", { exact: true })).toHaveValue(
    "MTs Nama Diperbarui",
  );
  await expect(
    dialog.getByRole("button", { name: "Simpan perubahan", exact: true }),
  ).toBeDisabled();
  await dialog
    .getByRole("button", { name: "Muat data terbaru", exact: true })
    .click();
  await expect(dialog.getByLabel("Nama madrasah", { exact: true })).toHaveValue(
    "MTs Al Karimiyyah",
  );
  await dialog
    .getByLabel("Nama madrasah", { exact: true })
    .fill("MTs Nama Diperbarui");
  await dialog
    .getByLabel("Alasan / dasar perubahan")
    .fill("Pembaruan setelah memuat data terbaru");
  await dialog
    .getByRole("button", { name: "Simpan perubahan", exact: true })
    .click();
  await expect(dialog).not.toBeVisible();
  await expect(
    page.getByText("MTs Nama Diperbarui", { exact: true }),
  ).toBeVisible();
});

test("mobile student workflow links a verified guardian and revokes with shadcn confirmation", async ({
  page,
}) => {
  await page.setViewportSize({ width: 390, height: 844 });
  await login(page);
  await page.goto("/data-master/students");
  await page
    .getByRole("button", { name: "Kelola Siswa Contoh 01", exact: true })
    .click();
  await page
    .getByRole("button", { name: "Kelola hubungan wali", exact: true })
    .click();
  const links = page.getByRole("dialog", {
    name: "Hubungan wali",
    exact: true,
  });
  await links.getByRole("button", { name: "Tambah wali", exact: true }).click();
  const form = page.getByRole("dialog", { name: "Tambah wali", exact: true });
  await form.getByLabel("Wali", { exact: true }).click();
  await page.getByRole("option", { name: /Bapak Ahmad/ }).click();
  await form
    .getByLabel("Alasan / dasar perubahan")
    .fill("Hubungan ayah sesuai dokumen keluarga");
  await form
    .getByRole("button", { name: "Simpan riwayat", exact: true })
    .click();
  await links.getByRole("button", { name: "Verifikasi", exact: true }).click();
  const confirm = page.getByRole("alertdialog", {
    name: "Verifikasi hubungan wali",
    exact: true,
  });
  await expect(
    confirm.getByRole("button", { name: "Verifikasi hubungan", exact: true }),
  ).toBeDisabled();
  await confirm.getByRole("checkbox").check();
  await confirm
    .getByLabel("Alasan / dasar perubahan")
    .fill("Identitas ayah dan hubungan siswa sudah diperiksa");
  await confirm
    .getByRole("button", { name: "Verifikasi hubungan", exact: true })
    .click();
  await expect(links.getByText("Terverifikasi", { exact: true })).toBeVisible();
  await links
    .getByRole("button", { name: "Cabut hubungan", exact: true })
    .click();
  const revoke = page.getByRole("alertdialog", {
    name: "Cabut hubungan wali",
    exact: true,
  });
  await revoke
    .getByLabel("Alasan / dasar perubahan")
    .fill("Hubungan perwalian resmi sudah berakhir");
  await revoke
    .getByRole("button", { name: "Cabut hubungan", exact: true })
    .click();
  await expect(links.getByText("Dicabut", { exact: true })).toBeVisible();
  await fits(page);
});

test("employee category and position history does not change account roles", async ({
  page,
  context,
}) => {
  await login(page);
  await page.goto("/data-master/employees");
  await page
    .getByRole("button", { name: "Kelola Guru Ahmad", exact: true })
    .click();
  await page
    .getByRole("button", { name: "Kelola kategori & jabatan", exact: true })
    .click();
  const links = page.getByRole("dialog");
  await links
    .getByRole("button", { name: "Tambah kategori pegawai", exact: true })
    .click();
  await links.getByLabel("Kategori pegawai", { exact: true }).click();
  await page.getByRole("option", { name: "Guru · guru", exact: true }).click();
  await links
    .getByLabel("Alasan / dasar perubahan")
    .fill("Penetapan kategori guru sesuai surat tugas");
  await links
    .getByRole("button", { name: "Simpan riwayat", exact: true })
    .click();
  await links.getByRole("tab", { name: "Jabatan", exact: true }).click();
  await links
    .getByRole("button", { name: "Tambah jabatan", exact: true })
    .click();
  await links.getByLabel("Jabatan", { exact: true }).click();
  await page.getByRole("option", { name: /Kepala Madrasah/ }).click();
  await links
    .getByLabel("Alasan / dasar perubahan")
    .fill("Penetapan jabatan berdasarkan surat keputusan");
  await links
    .getByRole("button", { name: "Simpan riwayat", exact: true })
    .click();
  await expect(
    links.getByText("Kepala Madrasah", { exact: true }),
  ).toBeVisible();
  const me = await context.request.get("/api/auth/session");
  expect(me.ok()).toBe(true);
  expect((await me.json()).data.roles).toEqual([
    { code: "super_admin", name: "Super Admin" },
  ]);
});

test("CSV templates, multipart uploads, preview, confirmation and error reports work through BFF", async ({
  page,
}) => {
  await login(page);
  await page.goto("/data-master/imports");
  await page.getByLabel("Jenis data", { exact: true }).click();
  await page
    .getByRole("option", { name: "Mata pelajaran", exact: true })
    .click();
  const template = page.waitForEvent("download");
  await page
    .getByRole("button", { name: "Unduh template CSV", exact: true })
    .click();
  expect((await template).suggestedFilename()).toBe("template-subjects.csv");
  await page.getByLabel("Berkas CSV", { exact: true }).setInputFiles({
    name: "mapel.csv",
    mimeType: "text/csv",
    buffer: Buffer.from(
      "external_id,code,name,group_name\nimpor-ipa,IPA,Ilmu Alam,Umum\n",
    ),
  });
  await page
    .getByLabel("Alasan / dasar perubahan")
    .fill("Migrasi mata pelajaran yang telah diperiksa");
  await page
    .getByRole("button", { name: "Unggah & siapkan pratinjau", exact: true })
    .click();
  const preview = page.getByRole("dialog", {
    name: "Pratinjau impor #1",
    exact: true,
  });
  await expect(preview.getByText("Ilmu Alam", { exact: true })).toBeVisible();
  await expect(
    preview.getByRole("button", { name: "Konfirmasi impor", exact: true }),
  ).toBeEnabled();
  await preview
    .getByRole("button", { name: "Konfirmasi impor", exact: true })
    .click();
  const confirm = page.getByRole("alertdialog");
  await confirm
    .getByLabel("Alasan / dasar perubahan")
    .fill("Seluruh hasil pratinjau telah diperiksa");
  await confirm
    .getByRole("button", { name: "Mulai impor", exact: true })
    .click();
  await expect(preview.getByText("Selesai", { exact: true })).toBeVisible();
  await expect(
    preview.getByRole("button", { name: "Konfirmasi impor", exact: true }),
  ).toBeDisabled();
  await page.keyboard.press("Escape");
  await page.getByLabel("Berkas CSV", { exact: true }).setInputFiles({
    name: "invalid.csv",
    mimeType: "text/csv",
    buffer: Buffer.from(
      "external_id,code,name,group_name\nimpor-bad,BAD,INVALID,Umum\n",
    ),
  });
  await page
    .getByLabel("Alasan / dasar perubahan")
    .fill("Pemeriksaan berkas yang masih perlu diperbaiki");
  await page
    .getByRole("button", { name: "Unggah & siapkan pratinjau", exact: true })
    .click();
  const invalid = page.getByRole("dialog", {
    name: "Pratinjau impor #2",
    exact: true,
  });
  await expect(
    invalid.getByText("Nama mata pelajaran wajib diisi.", { exact: false }),
  ).toBeVisible();
  await expect(
    invalid.getByRole("button", { name: "Konfirmasi impor", exact: true }),
  ).toBeDisabled();
  const report = page.waitForEvent("download");
  await invalid
    .getByRole("button", { name: "Unduh laporan kesalahan", exact: true })
    .click();
  expect((await report).suggestedFilename()).toBe("impor-2-kesalahan.csv");
});

test("master navigation and BFF reject unauthorized paths, cross-origin requests and unauthorized roles", async ({
  page,
  context,
}) => {
  await login(page, "guru");
  await page.goto("/data-master/students");
  await expect(
    page.getByRole("heading", { name: "Ruang kerja tidak tersedia" }),
  ).toBeVisible();
  expect((await context.request.get("/api/master/students")).status()).toBe(
    403,
  );
  expect((await context.request.get("/api/master/users")).status()).toBe(404);
  expect(
    (
      await context.request.post("/api/master/subjects", {
        headers: { Origin: "https://untrusted.example" },
        data: { code: "X" },
      })
    ).status(),
  ).toBe(403);
  expect(
    (
      await context.request.post("/api/master/imports", {
        headers: { Origin: "https://untrusted.example" },
        multipart: {
          file: {
            name: "x.csv",
            mimeType: "text/csv",
            buffer: Buffer.from("x"),
          },
        },
      })
    ).status(),
  ).toBe(403);
});

test("all master pages and form dialogs fit mobile, tablet and desktop without hydration errors", async ({
  page,
}) => {
  test.setTimeout(120_000);
  const errors: string[] = [];
  page.on("pageerror", (error) => errors.push(error.message));
  page.on("console", (message) => {
    if (
      message.type() === "error" &&
      /hydration|prerender|unstable value|uncached data/i.test(message.text())
    )
      errors.push(message.text());
  });
  await login(page);
  const widths =
    process.env.NEXT_TEST_DEV === "1"
      ? [390, 1024]
      : [320, 390, 768, 1024, 1440];
  for (const width of widths) {
    await page.setViewportSize({ width, height: width === 320 ? 568 : 900 });
    for (const section of [
      "institution",
      "students",
      "guardians",
      "employees",
      "positions",
      "subjects",
      "references",
      "imports",
    ]) {
      await page.goto(`/data-master/${section}`);
      await expect(
        page.getByRole("main").getByRole("heading", { level: 1 }),
      ).toBeVisible();
      await expect(
        page.getByRole("button", { name: "Menu akun", exact: true }),
      ).toBeEnabled();
      await fits(page);
    }
    await page.goto("/data-master/students");
    await expect(
      page.getByRole("button", { name: "Kelola Siswa Contoh 01", exact: true }),
    ).toBeVisible();
    await page.screenshot({
      path: `test-results/screenshots/master-list-${width}.png`,
      animations: "disabled",
    });
    await page
      .getByRole("button", { name: "Tambah siswa", exact: true })
      .click();
    const form = page.getByRole("dialog", {
      name: "Tambah siswa",
      exact: true,
    });
    await expect(form).toBeVisible();
    const box = await form.boundingBox();
    expect(box?.width).toBeLessThanOrEqual(width + 1);
    await expect(
      form.getByRole("button", { name: "Simpan data", exact: true }),
    ).toBeInViewport();
    await form
      .getByLabel("Alasan / dasar perubahan")
      .fill("Pemeriksaan aksesibilitas form mobile");
    await fits(page);
    await page.screenshot({
      path: `test-results/screenshots/master-form-${width}.png`,
      animations: "disabled",
    });
    await page.keyboard.press("Escape");
  }
  expect(errors).toEqual([]);
});

test("student pagination, empty search and identity edits use current versions", async ({
  page,
}) => {
  await login(page);
  await page.goto("/data-master/students");
  await expect(
    page.getByText("27 hasil · Halaman 1 dari 2", { exact: true }),
  ).toBeVisible();
  await page
    .getByRole("button", { name: "Halaman berikutnya", exact: true })
    .click();
  await expect(
    page.getByRole("button", { name: "Kelola Siswa Contoh 27", exact: true }),
  ).toBeVisible();
  await search(page, "Tidak Ada Siswa Ini");
  await expect(
    page.getByRole("heading", { name: "Data tidak ditemukan", exact: true }),
  ).toBeVisible();
  await page.getByRole("button", { name: "Hapus filter", exact: true }).click();
  await page
    .getByRole("button", { name: "Kelola Siswa Contoh 01", exact: true })
    .click();
  await page.getByRole("button", { name: "Ubah data", exact: true }).click();
  const editor = page.getByRole("dialog", { name: "Ubah siswa", exact: true });
  await expect(
    editor.getByLabel("Tanggal lahir (opsional)", { exact: true }),
  ).toHaveValue("2012-01-01");
  await editor
    .getByLabel("Nama lengkap", { exact: true })
    .fill("Siswa Setelah Koreksi");
  await editor
    .getByLabel("Alasan / dasar perubahan")
    .fill("Perbaikan nama sesuai dokumen identitas siswa");
  await editor
    .getByRole("button", { name: "Simpan perubahan", exact: true })
    .click();
  await expect(editor).not.toBeVisible();
  await expect(
    page.getByRole("button", {
      name: "Kelola Siswa Setelah Koreksi",
      exact: true,
    }),
  ).toBeVisible();
});

test("duplicate identity candidates survive BFF validation and can be reused without creating a person", async ({
  page,
  request,
}) => {
  await login(page);
  await page.goto("/data-master/guardians");
  await page.getByRole("button", { name: "Tambah wali", exact: true }).click();
  const editor = page.getByRole("dialog", { name: "Tambah wali", exact: true });
  await editor.getByLabel("Nama lengkap", { exact: true }).fill("Guru Ahmad");
  await editor
    .getByLabel("Tanggal lahir (opsional)", { exact: true })
    .fill("2012-01-01");
  await editor
    .getByLabel("Kode internal wali", { exact: true })
    .fill("W-SHARED");
  await editor
    .getByLabel("Alasan / dasar perubahan")
    .fill("Pegawai yang sama juga menjadi wali siswa");
  await editor
    .getByRole("button", { name: "Simpan data", exact: true })
    .click();
  await expect(
    editor.getByRole("button", { name: "Gunakan identitas ini", exact: true }),
  ).toBeVisible();
  const before = await (
    await request.get("http://127.0.0.1:43990/__test/master-state")
  ).json();
  await editor
    .getByRole("button", { name: "Gunakan identitas ini", exact: true })
    .click();
  await editor
    .getByRole("button", { name: "Simpan data", exact: true })
    .click();
  await expect(editor).not.toBeVisible();
  await expect(
    page.getByRole("button", { name: "Kelola Guru Ahmad", exact: true }),
  ).toBeVisible();
  const after = await (
    await request.get("http://127.0.0.1:43990/__test/master-state")
  ).json();
  expect(after.people.length).toBe(before.people.length);
  expect(
    after.records.guardians.find(
      (row: { internal_code: string }) => row.internal_code === "W-SHARED",
    ).person_id,
  ).toBe(2002);
});

test("reference edits preserve stable codes, keep inputs on failure and return keyboard focus", async ({
  page,
  request,
}) => {
  await login(page);
  await page.goto("/data-master/subjects");
  const add = page.getByRole("button", {
    name: "Tambah mata pelajaran",
    exact: true,
  });
  await add.click();
  await page.keyboard.press("Escape");
  await expect(add).toBeFocused();
  await page
    .getByRole("button", { name: "Kelola Matematika", exact: true })
    .click();
  await page.getByRole("button", { name: "Ubah data", exact: true }).click();
  const editor = page.getByRole("dialog", {
    name: "Ubah mata pelajaran",
    exact: true,
  });
  await expect(
    editor.getByLabel("Kode mata pelajaran", { exact: true }),
  ).toHaveAttribute("readonly", "");
  await editor
    .getByLabel("Nama mata pelajaran", { exact: true })
    .fill("Matematika Terapan");
  await editor
    .getByLabel("Alasan / dasar perubahan")
    .fill("Pembaruan nama tanpa mengubah kode referensi");
  await request.post("http://127.0.0.1:43990/__test/master-fail-next", {
    data: { mode: "unavailable" },
  });
  await editor
    .getByRole("button", { name: "Simpan perubahan", exact: true })
    .click();
  await expect(editor.getByRole("alert")).toBeVisible();
  await expect(
    editor.getByLabel("Nama mata pelajaran", { exact: true }),
  ).toHaveValue("Matematika Terapan");
  await editor
    .getByRole("button", { name: "Simpan perubahan", exact: true })
    .click();
  await expect(editor).not.toBeVisible();
  await expect(
    page.getByRole("button", {
      name: "Kelola Matematika Terapan",
      exact: true,
    }),
  ).toBeVisible();
});
