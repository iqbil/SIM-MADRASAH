let records;
let people;
let links;
let batches;
let counter = 0;
let nextFailure = "";
const time = () => new Date(Date.now() + ++counter).toISOString();
const personFields = [
  "full_name",
  "sex",
  "birth_place",
  "birth_date",
  "address",
  "phone",
  "contact_email",
];
export function resetMaster() {
  counter = 0;
  nextFailure = "";
  batches = [];
  links = {};
  const person = (id, name) => ({
    id,
    full_name: name,
    sex: "L",
    birth_place: "Sumenep",
    birth_date: "2012-01-01",
    address: "Alamat ilustrasi",
    phone: null,
    contact_email: null,
    is_active: true,
    updated_at: time(),
  });
  people = [
    person(2001, "Bapak Ahmad"),
    person(2002, "Guru Ahmad"),
    person(2003, "Guru Siti"),
  ];
  const student = (i) => {
    const p = person(i + 1000, `Siswa Contoh ${String(i).padStart(2, "0")}`);
    people.push(p);
    return {
      id: i,
      person_id: p.id,
      person: p,
      internal_code: `S-${String(i).padStart(3, "0")}`,
      nis: String(100 + i),
      nisn: `123456${String(i).padStart(4, "0")}`,
      admitted_on: "2026-07-01",
      status_id: 1,
      archived_at: null,
      updated_at: time(),
    };
  };
  records = {
    institution: [
      {
        id: 1,
        name: "MTs Al Karimiyyah",
        npsn: "12345678",
        nsm: "121200000001",
        address: "Alamat madrasah",
        phone: "0800000000",
        email: "info@madrasah.test",
        website: "https://madrasah.test",
        timezone: "Asia/Jakarta",
        updated_at: time(),
      },
    ],
    students: Array.from({ length: 27 }, (_, i) => student(i + 1)),
    guardians: [
      {
        id: 1,
        internal_code: "W-001",
        occupation: "Wiraswasta",
        is_active: true,
        person_id: 2001,
        person: people[0],
        updated_at: time(),
      },
    ],
    employees: [
      {
        id: 1,
        employee_number: "P-001",
        nip: null,
        nuptk: null,
        joined_on: "2026-07-01",
        left_on: null,
        is_active: true,
        person_id: 2002,
        person: people[1],
        updated_at: time(),
      },
      {
        id: 2,
        employee_number: "P-002",
        nip: null,
        nuptk: null,
        joined_on: "2026-07-01",
        left_on: null,
        is_active: true,
        person_id: 2003,
        person: people[2],
        updated_at: time(),
      },
    ],
    positions: [
      {
        id: 1,
        code: "KAMAD",
        name: "Kepala Madrasah",
        description: "Pimpinan madrasah",
        is_active: true,
        updated_at: time(),
      },
    ],
    "employee-categories": [
      {
        id: 1,
        code: "guru",
        name: "Guru",
        is_active: true,
        updated_at: time(),
      },
      {
        id: 2,
        code: "tendik",
        name: "Tenaga kependidikan",
        is_active: true,
        updated_at: time(),
      },
    ],
    subjects: [
      {
        id: 1,
        code: "MTK",
        name: "Matematika",
        group_name: "Umum",
        is_active: true,
        updated_at: time(),
      },
    ],
    "student-statuses": [
      {
        id: 1,
        code: "active",
        name: "Aktif",
        is_terminal: false,
        is_active: true,
        updated_at: time(),
      },
      {
        id: 2,
        code: "graduated",
        name: "Lulus",
        is_terminal: true,
        is_active: true,
        updated_at: time(),
      },
    ],
    "attendance-statuses": [
      {
        id: 1,
        domain: "employee",
        code: "hadir",
        name: "Hadir pegawai",
        is_active: true,
        updated_at: time(),
      },
      {
        id: 2,
        domain: "student",
        code: "hadir",
        name: "Hadir siswa",
        is_active: true,
        updated_at: time(),
      },
    ],
  };
}
const name = (record) => record.person?.full_name ?? record.name ?? "";
function page(data, params) {
  const size = Math.min(100, Number(params.get("per_page") || 25));
  const current = Number(params.get("page") || 1);
  return {
    data: data.slice((current - 1) * size, current * size),
    meta: {
      current_page: current,
      last_page: Math.max(1, Math.ceil(data.length / size)),
      total: data.length,
    },
  };
}
function advance(batch) {
  if (++batch.polls < 2) return;
  if (batch.status === "uploaded") {
    batch.status = "preview_ready";
    batch.updated_at = time();
  }
  if (batch.status === "importing") {
    for (const row of batch.rows) {
      const resource = batch.module.slice(7);
      const existing = records[resource].find(
        (item) => item.external_id === row.external_id,
      );
      if (!existing) {
        records[resource].push({
          ...row.values,
          external_id: row.external_id,
          id: Math.max(0, ...records[resource].map((item) => item.id)) + 1,
          is_active: true,
          updated_at: time(),
        });
        batch.inserted_rows++;
      } else if (existing.name !== row.values.name) {
        Object.assign(existing, row.values, { updated_at: time() });
        batch.updated_rows++;
      }
      row.status = row.result_action === "unchanged" ? "skipped" : "imported";
    }
    batch.status = "completed";
    batch.updated_at = time();
  }
}
export async function handleMaster({
  pathname,
  method,
  params,
  body,
  form,
  username,
  send,
}) {
  if (pathname === "/__test/master-fail-next") {
    nextFailure = body.mode;
    send(200, {});
    return true;
  }
  if (pathname === "/__test/master-state") {
    send(200, { records, people, links, batches });
    return true;
  }
  if (!pathname.startsWith("/api/v1/master/")) return false;
  if (!username) {
    send(401, { message: "Unauthenticated." });
    return true;
  }
  if (!["super_admin", "operator"].includes(username)) {
    send(403, { message: "Anda tidak memiliki izin data master." });
    return true;
  }
  const path = pathname.slice("/api/v1/master/".length);
  const [resource, rawId, action, rawLink, linkAction] = path.split("/");
  const id = Number(rawId);
  if (nextFailure && method !== "GET") {
    const mode = nextFailure;
    nextFailure = "";
    send(mode === "conflict" ? 409 : 503, {
      message:
        mode === "conflict"
          ? "Data telah berubah. Muat ulang sebelum menyimpan."
          : "Layanan sedang tidak tersedia.",
    });
    return true;
  }
  if (
    method !== "GET" &&
    path !== "imports" &&
    (!body.reason || body.reason.length < 10)
  ) {
    send(422, {
      message: "Alasan belum lengkap.",
      errors: { reason: ["Alasan minimal 10 karakter."] },
    });
    return true;
  }
  if (resource === "options") {
    send(200, {
      data: {
        can_manage: true,
        can_import: true,
        resources: Object.keys(records),
      },
    });
    return true;
  }
  if (resource === "people") {
    send(200, {
      data: people.filter((person) =>
        person.full_name
          .toLowerCase()
          .includes((params.get("q") || "").toLowerCase()),
      ),
    });
    return true;
  }
  if (resource === "imports") {
    if (rawId === "templates") {
      const columns =
        action === "subjects"
          ? ["external_id", "code", "name", "group_name"]
          : [
              "external_id",
              "person_id",
              ...personFields,
              action === "employees" ? "employee_number" : "internal_code",
            ];
      send(200, `${columns.join(",")}\n`, "text/csv; charset=UTF-8");
      return true;
    }
    if (method === "POST" && !rawId) {
      const key = form.get("operation_key");
      let batch = batches.find((item) => item.operation_key === key);
      if (!batch) {
        const content = await form.get("file").text();
        const [header, ...lines] = content.trim().split(/\r?\n/);
        const cols = header.split(",");
        const rows = lines.map((line, index) => {
          const values = Object.fromEntries(
            cols.map((key, i) => [key, line.split(",")[i] || null]),
          );
          const externalId = values.external_id;
          delete values.external_id;
          const invalid = !values.name || values.name === "INVALID";
          const old = records[form.get("resource")].find(
            (item) => item.external_id === externalId,
          );
          return {
            id: index + 1,
            row_number: index + 2,
            external_id: externalId,
            status: invalid ? "invalid" : "valid",
            result_action: old
              ? old.name === values.name
                ? "unchanged"
                : "update"
              : "insert",
            values,
            errors: invalid
              ? { name: ["Nama mata pelajaran wajib diisi."] }
              : {},
            duplicate_candidates: [],
          };
        });
        batch = {
          id: batches.length + 1,
          module: `master.${form.get("resource")}`,
          source_system: form.get("source_system"),
          operation_key: key,
          status: "uploaded",
          updated_at: time(),
          created_at: time(),
          total_rows: rows.length,
          failed_rows: rows.filter((row) => row.status === "invalid").length,
          valid_rows: rows.filter((row) => row.status === "valid").length,
          inserted_rows: 0,
          updated_rows: 0,
          rows,
          polls: 0,
        };
        batches.unshift(batch);
      }
      send(202, { data: batch });
      return true;
    }
    if (!rawId) {
      for (const batch of batches)
        if (["uploaded", "importing"].includes(batch.status)) advance(batch);
      send(200, page(batches, params));
      return true;
    }
    const batch = batches.find((item) => item.id === id);
    if (!batch) {
      send(404, {});
      return true;
    }
    if (action === "rows") {
      send(200, page(batch.rows, params));
      return true;
    }
    if (action === "errors") {
      send(
        200,
        `row_number,external_id,errors\n${batch.rows
          .filter((row) => row.status === "invalid")
          .map((row) => `${row.row_number},${row.external_id},Nama wajib diisi`)
          .join("\n")}`,
        "text/csv; charset=UTF-8",
      );
      return true;
    }
    if (action === "confirm") {
      if (batch.failed_rows)
        send(422, { message: "Masih ada baris tidak valid." });
      else {
        batch.status = "importing";
        batch.polls = 0;
        batch.updated_at = time();
        send(202, { data: batch });
      }
      return true;
    }
    if (["uploaded", "importing"].includes(batch.status)) advance(batch);
    send(200, { data: batch });
    return true;
  }
  const owner = records[resource]?.find((record) => record.id === id);
  if (["guardian-links", "categories", "positions"].includes(action)) {
    if (!owner) {
      send(404, {});
      return true;
    }
    const key = `${resource}/${id}/${action}`;
    links[key] ??= [];
    if (method === "GET") {
      const result = page(links[key], params);
      result.meta.owner_updated_at = owner.updated_at;
      send(200, result);
      return true;
    }
    if (owner.updated_at !== body.expected_updated_at) {
      send(409, { message: "Data telah berubah." });
      return true;
    }
    if (!rawLink) {
      const row = {
        ...body,
        id: links[key].length + 1,
        updated_at: time(),
        ...(action === "guardian-links"
          ? { verification_status: "pending", revoked_at: null }
          : {}),
      };
      links[key].push(row);
      owner.updated_at = time();
      send(201, { data: row, meta: { owner_updated_at: owner.updated_at } });
      return true;
    }
    const row = links[key].find((link) => link.id === Number(rawLink));
    if (!row) {
      send(404, {});
      return true;
    }
    if (linkAction === "verify") {
      if (!body.identity_confirmation) {
        send(422, {});
        return true;
      }
      row.verification_status = "verified";
    }
    if (linkAction === "revoke") {
      row.verification_status = "revoked";
      row.revoked_at = time();
    }
    if (linkAction === "end") row.effective_to = body.effective_to;
    row.updated_at = time();
    owner.updated_at = time();
    send(200, { data: row, meta: { owner_updated_at: owner.updated_at } });
    return true;
  }
  if (!records[resource]) {
    send(404, {});
    return true;
  }
  if (resource === "institution") {
    if (method === "PUT")
      Object.assign(records.institution[0], body, { updated_at: time() });
    send(200, { data: records.institution[0] });
    return true;
  }
  if (method === "GET") {
    if (rawId) send(owner ? 200 : 404, { data: owner });
    else {
      let data = records[resource].filter((record) =>
        [
          name(record),
          record.code || record.internal_code || record.employee_number,
        ].some((value) =>
          String(value || "")
            .toLowerCase()
            .includes((params.get("q") || "").toLowerCase()),
        ),
      );
      if (params.has("domain"))
        data = data.filter((record) => record.domain === params.get("domain"));
      if (params.has("is_active"))
        data = data.filter(
          (record) =>
            (resource === "students"
              ? !record.archived_at
              : record.is_active) ===
            (params.get("is_active") === "1"),
        );
      send(200, page(data, params));
    }
    return true;
  }
  if (owner && body.expected_updated_at !== owner.updated_at) {
    send(409, { message: "Data telah berubah. Muat ulang sebelum menyimpan." });
    return true;
  }
  if (action === "status") {
    if (resource === "students")
      owner.archived_at = body.is_active ? null : time();
    else owner.is_active = body.is_active;
    owner.updated_at = time();
    send(200, { data: owner });
    return true;
  }
  if (body.person && !owner && !body.duplicate_confirmation) {
    const candidates = people.filter(
      (person) =>
        person.full_name === body.person.full_name &&
        (person.birth_date || null) === (body.person.birth_date || null),
    );
    if (candidates.length) {
      send(422, {
        message: "Ditemukan kandidat identitas yang sama.",
        errors: { person: ["Periksa identitas sebelum melanjutkan."] },
        duplicate_candidates: candidates,
      });
      return true;
    }
  }
  let selected = owner;
  if (!selected) {
    selected = {
      id: Math.max(0, ...records[resource].map((record) => record.id)) + 1,
      is_active: true,
      ...(resource === "students" ? { archived_at: null, status_id: 1 } : {}),
    };
    records[resource].push(selected);
  }
  if (body.person) {
    if (
      owner?.person &&
      body.expected_person_updated_at !== owner.person.updated_at
    ) {
      send(409, { message: "Identitas sudah berubah." });
      return true;
    }
    const profile = selected.person || {
      id: 3000 + people.length,
      is_active: true,
    };
    Object.assign(profile, body.person, { updated_at: time() });
    if (!selected.person) people.push(profile);
    selected.person = profile;
    selected.person_id = profile.id;
  }
  if (body.person_id) {
    selected.person_id = body.person_id;
    selected.person = people.find((person) => person.id === body.person_id);
  }
  Object.assign(
    selected,
    Object.fromEntries(
      Object.entries(body).filter(
        ([key]) =>
          ![
            "person",
            "reason",
            "expected_updated_at",
            "expected_person_updated_at",
          ].includes(key),
      ),
    ),
    { updated_at: time() },
  );
  send(owner ? 200 : 201, { data: selected });
  return true;
}
resetMaster();
