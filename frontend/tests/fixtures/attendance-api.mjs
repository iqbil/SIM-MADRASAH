let policy;
let patterns;
let assignments;
let workDays;
let attendances;
let captures;
let corrections;
let counter = 0;
const time = () => new Date(Date.now() + ++counter).toISOString();

/** username mock -> pegawai (harus sinkron dengan profil navigasi attendance.own). */
const EMPLOYEE_USERS = new Map([
  [
    "guru",
    {
      id: 1,
      employee_number: "P-001",
      person: { id: 2002, full_name: "Guru Ahmad" },
    },
  ],
  [
    "tenaga_kependidikan",
    {
      id: 2,
      employee_number: "P-002",
      person: { id: 2003, full_name: "Guru Siti" },
    },
  ],
]);

export function resetAttendance() {
  counter = 0;
  policy = {
    id: 1,
    code: "EMP-ATTENDANCE",
    version_number: 1,
    status: "baseline",
    requires_photo: true,
    requires_gps: true,
    photo_max_size_bytes: 307200,
    photo_max_dimension: 1280,
    photo_quality: 80,
    gps_max_accuracy_meters: "100.00",
    gps_max_age_seconds: 60,
    correction_deadline_days: 7,
    effective_from: "2026-10-01",
    effective_to: null,
  };
  patterns = [
    {
      id: 1,
      code: "DEFAULT",
      name: "Pola kerja bawaan madrasah",
      is_active: true,
      assignments_count: 2,
      versions: [1, 2, 3, 4, 5, 6, 7].map((day) => ({
        id: day,
        version_number: 1,
        day_of_week: day,
        start_time: "07:00:00",
        end_time: "14:00:00",
        late_tolerance_minutes: 10,
        effective_from: "2026-10-01",
        effective_to: null,
      })),
      assignments: [],
    },
  ];
  assignments = [1, 2].map((employeeId, index) => ({
    id: 100 + index,
    employee_id: employeeId,
    work_pattern_id: 1,
    effective_from: "2026-10-01",
    effective_to: null,
    employee: EMPLOYEE_USERS.get(
      employeeId === 1 ? "guru" : "tenaga_kependidikan",
    ),
    workPattern: {
      id: 1,
      code: "DEFAULT",
      name: "Pola kerja bawaan madrasah",
      is_active: true,
    },
  }));
  patterns[0].assignments = assignments;
  workDays = new Map();
  attendances = new Map();
  captures = [];
  corrections = [];
}

const page = (data, current = 1) => ({
  data,
  meta: { current_page: current, last_page: 1, total: data.length },
});

function ok(response, payload, status = 200) {
  response.statusCode = status;
  response.setHeader("content-type", "application/json");
  response.end(JSON.stringify(payload));
}

const today = () =>
  new Date().toLocaleDateString("en-CA", { timeZone: "Asia/Jakarta" });

function ensureWorkDay(employeeId, date) {
  // Pola mock tujuh hari agar pengujian bebas kalender.
  const key = `${employeeId}|${date}`;
  if (workDays.has(key)) return workDays.get(key);
  const day = {
    id: 500 + workDays.size,
    employee_id: employeeId,
    work_date: date,
    shift_number: 1,
    scheduled_start_at: `${date}T00:00:00.000Z`,
    scheduled_end_at: `${date}T07:00:00.000Z`,
    tolerance_minutes_snapshot: 10,
    is_work_required: true,
    calendar_event_id: null,
    attendance: null,
  };
  day.workDay = {
    employee: EMPLOYEE_USERS.get(
      employeeId === 1 ? "guru" : "tenaga_kependidikan",
    ),
  };
  day.employee = day.workDay.employee;
  workDays.set(key, day);
  if (attendances.has(key)) day.attendance = attendances.get(key);

  return day;
}

function statusPayload(username) {
  const employee = EMPLOYEE_USERS.get(username);
  const date = today();
  const workDay = ensureWorkDay(employee.id, date);
  const attendance = attendances.get(`${employee.id}|${date}`) ?? null;
  const next = !workDay
    ? null
    : attendance === null
      ? "check_in"
      : attendance.check_out_at === null
        ? "check_out"
        : null;

  return {
    today: date,
    employee,
    work_day: workDay
      ? {
          id: workDay.id,
          work_date: workDay.work_date,
          scheduled_start_at: workDay.scheduled_start_at,
          scheduled_end_at: workDay.scheduled_end_at,
          tolerance_minutes_snapshot: workDay.tolerance_minutes_snapshot,
          is_work_required: workDay.is_work_required,
          calendar_event_id: null,
        }
      : null,
    attendance: attendance
      ? {
          id: attendance.id,
          check_in_at: attendance.check_in_at,
          check_out_at: attendance.check_out_at,
          late_minutes: attendance.late_minutes,
          entry_mode: attendance.entry_mode,
          notes: null,
          lock_version: attendance.lock_version,
          updated_at: attendance.updated_at,
        }
      : null,
    attendance_status: attendance
      ? { code: attendance.status_code, name: attendance.status_name }
      : null,
    next_action: next,
    policy,
  };
}

const employeeGuard = (username, response) => {
  if (!EMPLOYEE_USERS.has(username)) {
    ok(
      response,
      { message: "Akun tidak terhubung ke profil pegawai aktif." },
      403,
    );

    return null;
  }

  return EMPLOYEE_USERS.get(username);
};

/**
 * Handler mock /api/v1/attendance/* (via BFF). username tanpa linkage pegawai
 * ditolak 403 untuk aksi mandiri; operator/kepala mengikuti profil navigasi.
 */
export function handleAttendance({
  body = {},
  form = null,
  method = "GET",
  response,
  url,
  username,
}) {
  const route = url.pathname.replace("/api/v1/attendance/", "");
  const [resource, id, action] = route.split("/");
  const employee = EMPLOYEE_USERS.get(username);
  const manage = ["operator", "super_admin"].includes(username);
  const date = today();

  if (resource === "clock") {
    const guard = employeeGuard(username, response);
    if (!guard) return;
    {
      // clock multipart
      const fields = {};
      for (const key of [
        "action",
        "operation_key",
        "latitude",
        "longitude",
        "accuracy_meters",
        "photo_taken_at",
        "location_captured_at",
      ]) {
        fields[key] = form?.get(key);
      }
      const photo = form?.get("photo");
      if (!(photo instanceof File) || photo.size === 0)
        return ok(
          response,
          { message: "Foto wajib diambil dari kamera saat mencatat." },
          422,
        );
      if (!fields.action || !fields.operation_key)
        return ok(response, { message: "Data absensi belum lengkap." }, 422);
      if (
        Number(fields.accuracy_meters) > Number(policy.gps_max_accuracy_meters)
      )
        return ok(
          response,
          {
            message:
              "Akurasi GPS melebihi batas; keluar ke ruang terbuka lalu ulangi.",
          },
          422,
        );
      const taken = Date.parse(fields.photo_taken_at ?? "");
      if (
        Number.isNaN(taken) ||
        Date.now() - taken > policy.gps_max_age_seconds * 1000
      )
        return ok(
          response,
          {
            message:
              "Foto lebih tua dari batas; ambil ulang foto saat akan mencatat.",
          },
          422,
        );
      const workDay = ensureWorkDay(employee.id, date);
      if (!workDay)
        return ok(
          response,
          { message: "Hari ini bukan hari kerja menurut pola kerja Anda." },
          422,
        );
      const key = `${employee.id}|${date}`;
      const existing = attendances.get(key);
      if (fields.action === "check_in" && existing)
        return ok(
          response,
          { message: "Absen masuk hari ini sudah tercatat." },
          422,
        );
      if (fields.action === "check_out" && (!existing || existing.check_out_at))
        return ok(
          response,
          { message: "Absen pulang tidak dapat dicatat." },
          422,
        );
      const now = new Date().toISOString();
      if (fields.action === "check_in") {
        attendances.set(key, {
          id: 600 + attendances.size,
          work_day_id: workDay.id,
          status_code: "hadir",
          status_name: "Hadir",
          check_in_at: now,
          check_out_at: null,
          late_minutes: 0,
          entry_mode: "self",
          lock_version: 0,
          updated_at: now,
        });
      } else {
        existing.check_out_at = now;
        existing.updated_at = now;
      }
      captures.push({
        id: 700 + captures.length,
        employee_attendance_id: attendances.get(key)?.id ?? 0,
        event_type: fields.action,
        photo_file_id: 900 + captures.length,
        latitude: Number(fields.latitude),
        longitude: Number(fields.longitude),
        accuracy_meters: Number(fields.accuracy_meters),
        photo_taken_at: fields.photo_taken_at,
        location_captured_at: fields.location_captured_at,
        recorded_at: now,
      });

      return ok(response, { data: statusPayload(username) });
    }
  }

  if (resource === "status") {
    const guard = employeeGuard(username, response);
    if (!guard) return;

    return ok(response, { data: statusPayload(username) });
  }

  if (resource === "me") {
    const guard = employeeGuard(username, response);
    if (!guard) return;
    const month = date.slice(0, 7);
    const rows = [...workDays.values()]
      .filter(
        (day) =>
          day.employee_id === guard.id && day.work_date.startsWith(month),
      )
      .sort((a, b) => b.work_date.localeCompare(a.work_date))
      .map((day) => ({
        ...day,
        attendance:
          attendances.get(`${day.employee_id}|${day.work_date}`) ?? null,
      }));

    return ok(response, {
      data: { rows: page(rows), range: { from: `${month}-01`, to: date } },
    });
  }

  if (resource === "policy") return ok(response, { data: policy });

  if (resource === "records") {
    if (!manage)
      return ok(
        response,
        { message: "Akses pengelolaan absensi tidak diberikan." },
        403,
      );
    if (method === "POST") {
      const workDay = ensureWorkDay(Number(body.employee_id), body.work_date);
      if (!workDay)
        return ok(
          response,
          { message: "Tanggal tersebut bukan hari kerja pegawai." },
          422,
        );
      const key = `${body.employee_id}|${body.work_date}`;
      if (attendances.has(key))
        return ok(
          response,
          { message: "Kehadiran sudah tercatat; gunakan koreksi." },
          409,
        );
      const now = time();
      attendances.set(key, {
        id: 600 + attendances.size,
        work_day_id: workDay.id,
        status_code: body.status_code,
        status_name:
          {
            hadir: "Hadir",
            izin: "Izin",
            sakit: "Sakit",
            cuti: "Cuti",
            dinas_luar: "Dinas luar",
          }[body.status_code] ?? body.status_code,
        attendance_status: {
          code: body.status_code,
          name:
            {
              hadir: "Hadir",
              izin: "Izin",
              sakit: "Sakit",
              cuti: "Cuti",
              dinas_luar: "Dinas luar",
            }[body.status_code] ?? body.status_code,
        },
        captures: [],
        check_in_at: null,
        check_out_at: null,
        late_minutes: null,
        entry_mode: "officer",
        lock_version: 0,
        updated_at: now,
      });
      const record = { ...attendances.get(key) };
      record.attendanceStatus = {
        code: record.status_code,
        name: record.status_name,
      };

      return ok(
        response,
        { data: { ...workDay, attendance: record, updated_at: now } },
        201,
      );
    }
    const from = url.searchParams.get("date_from") ?? date;
    const to = url.searchParams.get("date_to") ?? from;
    const employeeId = Number(url.searchParams.get("employee_id") ?? 0);
    for (const emp of EMPLOYEE_USERS.values()) {
      for (
        let day = new Date(`${from}T00:00:00+07:00`);
        day <= new Date(`${to}T00:00:00+07:00`);
        day.setDate(day.getDate() + 1)
      ) {
        ensureWorkDay(emp.id, day.toLocaleDateString("en-CA"));
      }
    }
    const rows = [...workDays.values()]
      .filter(
        (day) =>
          day.work_date >= from &&
          day.work_date <= to &&
          (!employeeId || day.employee_id === employeeId),
      )
      .sort((a, b) => b.work_date.localeCompare(a.work_date))
      .map((day) => ({
        ...day,
        attendance: attendances.get(`${day.employee_id}|${day.work_date}`)
          ? {
              ...attendances.get(`${day.employee_id}|${day.work_date}`),
              attendanceStatus: {
                code: attendances.get(`${day.employee_id}|${day.work_date}`)
                  .status_code,
                name: attendances.get(`${day.employee_id}|${day.work_date}`)
                  .status_name,
              },
            }
          : null,
      }));

    return ok(response, page(rows));
  }

  if (resource === "recap") {
    const from = url.searchParams.get("date_from") ?? date;
    const to = url.searchParams.get("date_to") ?? date;
    for (const emp of EMPLOYEE_USERS.values()) {
      for (
        let day = new Date(`${from}T00:00:00+07:00`);
        day <= new Date(`${to}T00:00:00+07:00`);
        day.setDate(day.getDate() + 1)
      ) {
        ensureWorkDay(emp.id, day.toLocaleDateString("en-CA"));
      }
    }
    const summary = [...EMPLOYEE_USERS.values()].map((emp) => {
      const row = {
        employee_id: emp.id,
        employee: emp,
        work_days: 0,
        hadir: 0,
        terlambat: 0,
        izin: 0,
        sakit: 0,
        cuti: 0,
        dinas_luar: 0,
        tanpa_catatan: 0,
      };
      for (const day of workDays.values()) {
        if (
          day.employee_id !== emp.id ||
          day.work_date < from ||
          day.work_date > to
        )
          continue;
        row.work_days++;
        const attendance = attendances.get(`${emp.id}|${day.work_date}`);
        if (!attendance) row.tanpa_catatan++;
        else if (attendance.status_code === "hadir") row.hadir++;
        else if (attendance.status_code in row) row[attendance.status_code]++;
      }

      return row;
    });

    return ok(response, {
      data: { range: { from, to }, summary, daily: null },
    });
  }

  if (resource === "corrections") {
    if (!manage)
      return ok(response, { message: "Akses koreksi tidak diberikan." }, 403);
    if (method === "POST") {
      const attendance = [...attendances.values()].find(
        (row) => row.id === Number(body.employee_attendance_id),
      );
      const record = {
        id: 800 + corrections.length,
        employee_attendance_id: Number(body.employee_attendance_id),
        employeeAttendance: attendance
          ? {
              ...attendance,
              attendanceStatus: {
                code: attendance.status_code,
                name: attendance.status_name,
              },
              workDay:
                [...workDays.values()].find(
                  (day) => day.id === attendance.work_day_id,
                ) ?? null,
            }
          : null,
        before_values: { status_code: attendance?.status_code ?? null },
        after_values: body.after_values ?? {},
        reason: body.reason,
        requested_at: time(),
        status: "pending",
        requester: { person: { full_name: "Admin/Operator" } },
        updated_at: time(),
      };
      corrections.unshift(record);

      return ok(response, { data: record }, 201);
    }
    if (id && action === "decision") {
      const record = corrections.find((row) => row.id === Number(id));
      if (!record)
        return ok(response, { message: "Koreksi tidak ditemukan." }, 404);
      if (username === "operator")
        return ok(
          response,
          { message: "Persetujuan koreksi memerlukan mandat petugas." },
          403,
        );
      record.status = body.decision === "approve" ? "applied" : "rejected";
      record.updated_at = time();

      return ok(response, { data: record });
    }
    const status = url.searchParams.get("status");
    const visible = corrections.filter(
      (row) => !status || row.status === status,
    );

    return ok(response, page(visible));
  }

  if (resource === "work-patterns") {
    if (!manage)
      return ok(
        response,
        { message: "Akses pengelolaan absensi tidak diberikan." },
        403,
      );
    if (method === "POST" && !id) {
      const record = {
        id: patterns.length + 1,
        is_active: true,
        assignments_count: 0,
        assignments: [],
        updated_at: time(),
        ...body,
        versions: (body.days ?? []).map((day, index) => ({
          id: 100 + index,
          version_number: 1,
          day_of_week: day.day_of_week,
          start_time: `${day.start_time}:00`,
          end_time: `${day.end_time}:00`,
          late_tolerance_minutes: day.late_tolerance_minutes,
          effective_from: body.effective_from,
          effective_to: null,
        })),
      };
      patterns.push(record);

      return ok(response, { data: record }, 201);
    }
    if (id && action === "versions" && method === "POST") {
      const pattern = patterns.find((row) => row.id === Number(id));
      for (const version of pattern.versions) {
        if (version.effective_to === null) {
          const close = new Date(body.effective_from);
          close.setDate(close.getDate() - 1);
          version.effective_to = close.toISOString().slice(0, 10);
        }
      }
      const number =
        Math.max(...pattern.versions.map((row) => row.version_number)) + 1;
      for (const day of body.days ?? []) {
        pattern.versions.push({
          id: 100 + pattern.versions.length,
          version_number: number,
          day_of_week: day.day_of_week,
          start_time: `${day.start_time}:00`,
          end_time: `${day.end_time}:00`,
          late_tolerance_minutes: day.late_tolerance_minutes,
          effective_from: body.effective_from,
          effective_to: null,
        });
      }

      return ok(response, { data: pattern });
    }
    if (id && action === "assignments" && method === "POST") {
      for (const assignment of assignments) {
        if (
          assignment.employee_id === Number(body.employee_id) &&
          assignment.effective_to === null
        ) {
          const close = new Date(body.effective_from);
          close.setDate(close.getDate() - 1);
          assignment.effective_to = close.toISOString().slice(0, 10);
        }
      }
      const record = {
        id: 100 + assignments.length,
        employee_id: Number(body.employee_id),
        work_pattern_id: Number(id),
        effective_from: body.effective_from,
        effective_to: null,
        employee: [...EMPLOYEE_USERS.values()].find(
          (emp) => emp.id === Number(body.employee_id),
        ),
        workPattern: patterns.find((row) => row.id === Number(id)),
      };
      assignments.unshift(record);
      const pattern = patterns.find((row) => row.id === Number(id));
      pattern.assignments_count = assignments.filter(
        (row) => row.work_pattern_id === pattern.id,
      ).length;

      return ok(response, { data: record }, 201);
    }

    return ok(response, page(patterns));
  }

  if (resource === "work-assignments") {
    if (!manage)
      return ok(
        response,
        { message: "Akses pengelolaan absensi tidak diberikan." },
        403,
      );

    return ok(response, page(assignments));
  }

  if (resource === "work-days" && action === "generate") {
    if (!manage)
      return ok(
        response,
        { message: "Akses pengelolaan absensi tidak diberikan." },
        403,
      );
    let created = 0;
    for (const emp of EMPLOYEE_USERS.values()) {
      for (
        let day = new Date(`${body.date_from}T00:00:00+07:00`);
        day <= new Date(`${body.date_to}T00:00:00+07:00`);
        day.setDate(day.getDate() + 1)
      ) {
        if (ensureWorkDay(emp.id, day.toLocaleDateString("en-CA")) !== null)
          created++;
      }
    }

    return ok(response, {
      data: { employees: EMPLOYEE_USERS.size, work_days: created },
    });
  }

  if (resource === "captures") {
    const capture = captures.find((row) => row.id === Number(id));
    if (!capture)
      return ok(response, { message: "Bukti tidak ditemukan." }, 404);
    if (!employee && !manage)
      return ok(response, { message: "Akses bukti tidak diberikan." }, 404);
    if (action === "photo") {
      // WebP 1x1 transparan.
      const bytes = Buffer.from(
        "UklGRhoAAABXRUJQVlA4TA0AAAAvAAAAEAcQERGIiP4HAA==",
        "base64",
      );
      response.statusCode = 200;
      response.setHeader("content-type", "image/webp");
      response.end(bytes);

      return;
    }

    return ok(response, { data: capture });
  }

  ok(response, { message: "Tidak ditemukan." }, 404);
}
