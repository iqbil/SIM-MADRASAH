let classes;
let roster;
let records;
let corrections;
let counter = 0;
const time = () => new Date(Date.now() + ++counter).toISOString();

/** Pencatat mock: wali_kelas (kelas 711, konsisten academic mock). */
const RECORDER_USERS = new Set(["wali_kelas", "guru"]);
const CLASS = {
  id: 711,
  class_id: 71,
  display_name: "VII-A 2026/2027",
  class_name: "Kelas 7A",
};
const STUDENTS = [
  {
    student_id: 101,
    enrollment_id: 501,
    person: { id: 5101, full_name: "Siswa Mutasi Satu" },
  },
  {
    student_id: 102,
    enrollment_id: 502,
    person: { id: 5102, full_name: "Siswa Mutasi Dua" },
  },
  {
    student_id: 103,
    enrollment_id: 503,
    person: { id: 5103, full_name: "Siswa Lain" },
  },
];
const STATUSES = [
  { id: 1, code: "hadir", name: "Hadir" },
  { id: 2, code: "sakit", name: "Sakit" },
  { id: 3, code: "izin", name: "Izin" },
  { id: 4, code: "alpa", name: "Alpa" },
];

export function resetStudentAttendance() {
  counter = 0;
  classes = null;
  roster = null;
  records = new Map();
  corrections = [];
}

const ok = (response, payload, status = 200) => {
  response.statusCode = status;
  response.setHeader("content-type", "application/json");
  response.end(JSON.stringify(payload));
};

const today = () =>
  new Date().toLocaleDateString("en-CA", { timeZone: "Asia/Jakarta" });
const statusOf = (code) => STATUSES.find((s) => s.code === code);

function ensureState() {
  if (classes) return;
  const date = today();
  classes = [{ ...CLASS, roster: STUDENTS.length, recorded: 0 }];
  roster = {
    class_period: { id: CLASS.id, display_name: CLASS.display_name },
    date,
    policy: {
      mode: "daily",
      input_deadline_time: "23:59:00",
      correction_deadline_days: 7,
    },
    statuses: STATUSES,
    rows: STUDENTS.map((student) => ({
      ...student,
      student: { id: student.student_id, person: student.person },
      attendance: null,
    })),
  };
}

function syncRoster() {
  for (const row of roster.rows) {
    const record = records.get(`${row.student_id}|${roster.date}`);
    row.attendance = record
      ? {
          id: record.id,
          status: statusOf(record.status_code),
          lock_version: record.lock_version,
          notes: record.notes,
        }
      : null;
  }
  classes[0].recorded = roster.rows.filter((row) => row.attendance).length;
}

/**
 * Handler mock /api/v1/student-attendance/* (via BFF). Hanya wali_kelas/guru
 * yang memegang students.attendance; koreksi keputusan oleh "mandat_absensi".
 */
export function handleStudentAttendance({
  body = {},
  method = "GET",
  response,
  url,
  username,
}) {
  ensureState();
  const route = url.pathname.replace("/api/v1/student-attendance/", "");
  const [resource, id, action] = route.split("/");
  const recorder = RECORDER_USERS.has(username);

  if (resource === "classes") {
    if (!recorder)
      return ok(
        response,
        { message: "Akses absensi siswa tidak diberikan." },
        403,
      );

    return ok(response, { data: classes });
  }

  if (resource === "roster") {
    if (!recorder)
      return ok(
        response,
        { message: "Akses absensi siswa tidak diberikan." },
        403,
      );
    roster.date = url.searchParams.get("date") ?? today();
    syncRoster();

    return ok(response, { data: roster });
  }

  if (resource === "record") {
    if (!recorder)
      return ok(
        response,
        { message: "Akses absensi siswa tidak diberikan." },
        403,
      );
    if (method === "POST") {
      if (body.date !== today())
        return ok(
          response,
          {
            message:
              "Pengisian hanya untuk hari ini sebelum 23.59; hari lampau melalui koreksi oleh petugas.",
          },
          422,
        );
      const duplicates = [];
      for (const entry of body.entries ?? []) {
        if (records.has(`${entry.student_id}|${body.date}`)) {
          duplicates.push(
            STUDENTS.find((s) => s.student_id === entry.student_id)?.person
              .full_name ?? `#${entry.student_id}`,
          );
        }
      }
      if (duplicates.length > 0)
        return ok(
          response,
          {
            message: `Sudah tercatat pada tanggal ini (gunakan koreksi): ${duplicates.join(", ")}.`,
          },
          422,
        );
      for (const entry of body.entries ?? []) {
        records.set(`${entry.student_id}|${body.date}`, {
          id: 3000 + records.size,
          status_code: entry.status_code,
          notes: entry.notes ?? null,
          lock_version: 0,
          recorded_at: time(),
        });
      }
      syncRoster();

      return ok(response, {
        data: { created: (body.entries ?? []).length, date: body.date },
      });
    }

    return ok(response, { message: "Tidak ditemukan." }, 404);
  }

  if (resource === "recap") {
    if (!recorder)
      return ok(
        response,
        { message: "Akses absensi siswa tidak diberikan." },
        403,
      );
    syncRoster();
    const byDate = [];
    const byStudent = STUDENTS.map((student) => ({
      student_id: student.student_id,
      student: { person: student.person },
      hadir: 0,
      sakit: 0,
      izin: 0,
      alpa: 0,
      belum_dicatat: 0,
      hari_aktif: 0,
    }));
    const from = url.searchParams.get("date_from") ?? roster.date;
    const to = url.searchParams.get("date_to") ?? roster.date;
    for (
      let date = new Date(`${from}T00:00:00+07:00`);
      date <= new Date(`${to}T00:00:00+07:00`);
      date.setDate(date.getDate() + 1)
    ) {
      const iso = date.toLocaleDateString("en-CA", {
        timeZone: "Asia/Jakarta",
      });
      const row = {
        date: iso,
        hadir: 0,
        sakit: 0,
        izin: 0,
        alpa: 0,
        belum_dicatat: 0,
        roster: STUDENTS.length,
      };
      for (const [index, student] of STUDENTS.entries()) {
        byStudent[index].hari_aktif++;
        const record = records.get(`${student.student_id}|${iso}`);
        if (!record) {
          row.belum_dicatat++;
          byStudent[index].belum_dicatat++;

          continue;
        }
        row[record.status_code]++;
        byStudent[index][record.status_code]++;
      }
      byDate.push(row);
    }

    return ok(response, {
      data: {
        class_period: { id: CLASS.id, display_name: CLASS.display_name },
        range: { from, to },
        by_date: byDate,
        by_student: byStudent,
      },
    });
  }

  if (resource === "corrections") {
    if (!recorder && username !== "mandat_absensi")
      return ok(response, { message: "Akses koreksi tidak diberikan." }, 403);
    if (method === "POST" && !id) {
      const record = [...records.entries()].find(
        ([, value]) => value.id === Number(body.student_daily_attendance_id),
      );
      if (!record)
        return ok(response, { message: "Data absensi tidak ditemukan." }, 404);
      const studentId = Number(record[0].split("|")[0]);
      const student = STUDENTS.find((s) => s.student_id === studentId);
      const created = {
        id: 4000 + corrections.length,
        student_daily_attendance_id: record[1].id,
        before_values: { status_code: record[1].status_code },
        after_values: body.after_values,
        reason: body.reason,
        status: "pending",
        requested_at: time(),
        requester: { person: { full_name: "Wali Kelas" } },
        studentDailyAttendance: {
          id: record[1].id,
          attendance_date: record[0].split("|")[1],
          classPeriod: CLASS,
          student: student
            ? { id: student.student_id, person: student.person }
            : null,
        },
        student: student
          ? { id: student.student_id, person: student.person }
          : null,
        updated_at: time(),
      };
      corrections.unshift(created);

      return ok(response, { data: created }, 201);
    }
    if (id && action === "decision") {
      if (username !== "mandat_absensi")
        return ok(
          response,
          { message: "Persetujuan koreksi memerlukan mandat petugas." },
          403,
        );
      const correction = corrections.find((row) => row.id === Number(id));
      if (!correction)
        return ok(response, { message: "Koreksi tidak ditemukan." }, 404);
      if (correction.status !== "pending")
        return ok(response, { message: "Koreksi ini sudah diputuskan." }, 422);
      correction.status = body.decision === "approve" ? "applied" : "rejected";
      correction.approver = { person: { full_name: "Petugas Absensi" } };
      if (body.decision === "approve") {
        const record = [...records.entries()].find(
          ([, value]) => value.id === correction.student_daily_attendance_id,
        );
        if (record) {
          record[1].status_code = correction.after_values.status_code;
          record[1].lock_version += 1;
        }
      }
      correction.updated_at = time();

      return ok(response, { data: correction });
    }
    const status = url.searchParams.get("status");
    const rows = corrections.filter((row) => !status || row.status === status);

    return ok(response, {
      data: {
        rows: {
          data: rows,
          meta: { current_page: 1, last_page: 1, total: rows.length },
          links: {},
        },
      },
    });
  }

  ok(response, { message: "Tidak ditemukan." }, 404);
}
