let context;
let years;
let semesters;
let classes;
let periods;
let enrollments;
let calendar;
let teaching;
let homeroom;
let transitions;
let timetables;
let ttVersions;
let entries;
let meetings;
let substitutes;
let counter = 0;
const time = () => new Date(Date.now() + ++counter).toISOString();

export function resetAcademic() {
  counter = 0;
  years = [
    {
      id: 91,
      code: "2026/2027",
      name: "2026/2027",
      start_date: "2026-07-01",
      end_date: "2027-06-30",
      status: "open",
      semesters_count: 2,
      created_by: 1,
      updated_at: time(),
    },
  ];
  semesters = [
    {
      id: 911,
      academic_year_id: 91,
      number: 1,
      name: "Semester 1",
      start_date: "2026-07-01",
      end_date: "2026-12-31",
      status: "open",
      created_by: 1,
      updated_at: time(),
    },
    {
      id: 912,
      academic_year_id: 91,
      number: 2,
      name: "Semester 2",
      start_date: "2027-01-01",
      end_date: "2027-06-30",
      status: "planned",
      created_by: 1,
      updated_at: time(),
    },
  ];
  classes = [
    {
      id: 71,
      code: "7A",
      name: "Kelas 7A",
      grade: 7,
      is_active: true,
      periods_count: 1,
      updated_at: time(),
    },
    {
      id: 72,
      code: "7B",
      name: "Kelas 7B",
      grade: 7,
      is_active: true,
      periods_count: 1,
      updated_at: time(),
    },
  ];
  periods = [
    {
      id: 711,
      class_id: 71,
      schoolClass: classes[0],
      academic_year_id: 91,
      display_name: "VII-A 2026/2027",
      grade_snapshot: 7,
      capacity: 32,
      status: "open",
      enrollments_count: 2,
      updated_at: time(),
    },
    {
      id: 721,
      class_id: 72,
      schoolClass: classes[1],
      academic_year_id: 91,
      display_name: "VII-B 2026/2027",
      grade_snapshot: 7,
      capacity: 32,
      status: "open",
      enrollments_count: 1,
      updated_at: time(),
    },
  ];
  const student = (id, name) => ({
    student_id: id,
    student: { id, person: { id: id + 5000, full_name: name } },
  });
  enrollments = [
    {
      id: 501,
      ...student(101, "Siswa Mutasi Satu"),
      class_period_id: 711,
      classPeriod: periods[0],
      effective_from: "2026-07-15",
      effective_to: null,
      entry_reason: "admission",
      exit_reason: null,
      updated_at: time(),
    },
    {
      id: 502,
      ...student(102, "Siswa Mutasi Dua"),
      class_period_id: 711,
      classPeriod: periods[0],
      effective_from: "2026-07-15",
      effective_to: null,
      entry_reason: "admission",
      exit_reason: null,
      updated_at: time(),
    },
    {
      id: 503,
      ...student(103, "Siswa Lain"),
      class_period_id: 721,
      classPeriod: periods[1],
      effective_from: "2026-07-15",
      effective_to: null,
      entry_reason: "admission",
      exit_reason: null,
      updated_at: time(),
    },
  ];
  calendar = [
    {
      id: 601,
      academic_year_id: 91,
      title: "Libur akhir semester",
      event_type: "holiday",
      start_date: "2026-12-24",
      end_date: "2027-01-02",
      blocks_teaching: true,
      blocks_employee_work: true,
      description: null,
      updated_at: time(),
    },
  ];
  teaching = [
    {
      id: 801,
      employee_id: 3001,
      employee: { id: 3001, person: { full_name: "Guru Ahmad" } },
      subject_id: 401,
      subject: { name: "Matematika" },
      class_period_id: 711,
      classPeriod: periods[0],
      semester_id: 911,
      semester: semesters[0],
      planned_weekly_jp: "4",
      effective_from: "2026-07-15",
      effective_to: null,
      is_active: true,
      updated_at: time(),
    },
  ];
  homeroom = [
    {
      id: 851,
      employee_id: 3001,
      employee: { id: 3001, person: { full_name: "Guru Ahmad" } },
      class_period_id: 711,
      classPeriod: periods[0],
      semester_id: null,
      effective_from: "2026-07-15",
      effective_to: null,
      updated_at: time(),
    },
  ];
  transitions = [];
  timetables = [
    {
      id: 301,
      semester_id: 911,
      semester: semesters[0],
      code: "MAIN",
      name: "Jadwal utama",
      versions_count: 3,
      created_by: 1,
      lock_version: 0,
      updated_at: time(),
    },
  ];
  ttVersions = [
    {
      id: 3101,
      timetable_id: 301,
      version_number: 1,
      status: "superseded",
      effective_from: "2026-07-13",
      effective_to: "2026-09-13",
      published_by: 1,
      published_at: time(),
      reason: "Jadwal awal",
      entries_count: 1,
      created_by: 1,
      updated_at: time(),
    },
    {
      id: 3102,
      timetable_id: 301,
      version_number: 2,
      status: "published",
      effective_from: "2026-09-14",
      effective_to: null,
      published_by: 1,
      published_at: time(),
      reason: "Penyesuaian pasca libur",
      entries_count: 1,
      created_by: 1,
      updated_at: time(),
    },
    {
      id: 3103,
      timetable_id: 301,
      version_number: 3,
      status: "draft",
      effective_from: "2026-11-02",
      effective_to: null,
      published_by: null,
      published_at: null,
      reason: "Draf uji UI",
      entries_count: 0,
      created_by: 1,
      updated_at: time(),
    },
  ];
  entries = [3101, 3102].map((versionId, index) => ({
    id: 4100 + index,
    schedule_entry_id: 4001,
    timetable_version_id: versionId,
    teaching_assignment_id: 801,
    teachingAssignment: teaching[0],
    day_of_week: 1,
    start_time_snapshot: "07:30:00",
    end_time_snapshot: "08:50:00",
    planned_jp: "2.000",
    planned_minutes: 80,
    room: "R-1",
    effective_from: "2026-07-13",
    effective_to: null,
    updated_at: time(),
  }));
  const meetingBase = {
    schedule_entry_id: 4001,
    schedule_entry_version_id: 4101,
    occurrence_number: 1,
    semester_id: 911,
    class_period_id: 711,
    classPeriod: periods[0],
    subject_id: 401,
    subject: { id: 401, name: "Matematika" },
    scheduled_employee_id: 3001,
    scheduledEmployee: { id: 3001, person: { full_name: "Guru Ahmad" } },
    start_time_snapshot: "07:30:00",
    end_time_snapshot: "08:50:00",
    planned_jp_snapshot: "2.000",
    planned_minutes_snapshot: 80,
    is_cancelled: false,
    cancellation_reason: null,
    created_by: 1,
    lock_version: 0,
  };
  meetings = [
    {
      ...meetingBase,
      id: 5101,
      meeting_date: "2026-09-14",
      activeSubstitute: null,
      updated_at: time(),
    },
    {
      ...meetingBase,
      id: 5102,
      meeting_date: "2026-09-21",
      activeSubstitute: null,
      updated_at: time(),
    },
  ];
  substitutes = [];
  context = {
    active_academic_year_id: 91,
    active_semester_id: 911,
    lock_version: 0,
    year: { ...years[0] },
    semester: { ...semesters[0] },
    changed_by: 1,
    updated_at: time(),
  };
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

/**
 * Handler mock untuk /api/v1/academic/* (dipakai e2e melalui BFF).
 * username "guru" dibatasi ke rombel penugasannya (711) seperti backend.
 */
export function handleAcademic({
  body = {},
  method = "GET",
  response,
  url,
  username,
}) {
  const scoped = username === "guru";
  const route = url.pathname.replace("/api/v1/academic/", "");
  const [resource, id, action] = route.split("/");
  const numId = Number(id);

  if (resource === "context") {
    if (method === "GET") return ok(response, { data: context });
    context.active_academic_year_id = body.active_academic_year_id;
    context.active_semester_id = body.active_semester_id;
    context.lock_version += 1;
    return ok(response, { data: context });
  }

  if (resource === "transitions") {
    const students = (body.student_ids ?? []).map((studentId) => {
      const enrollment = enrollments.find(
        (row) => row.student_id === studentId,
      );
      return {
        student_id: studentId,
        student_name: enrollment?.student?.person?.full_name ?? `#${studentId}`,
        current_status: "active",
        to_status: body.type === "graduation" ? "graduated" : "active",
        current_class_period_id: enrollment?.class_period_id ?? null,
        current_class_name: enrollment?.classPeriod?.display_name ?? null,
        to_class_period_id: body.to_class_period_id ?? null,
        to_class_name:
          periods.find((row) => row.id === Number(body.to_class_period_id))
            ?.display_name ?? null,
        effective_date: body.effective_date,
        problem:
          enrollment &&
          action !== "confirm" &&
          scoped &&
          enrollment.class_period_id !== 711
            ? "Di luar kelas penugasan."
            : null,
      };
    });
    const ready =
      action === "confirm" || students.every((row) => row.problem === null);
    if (action === "confirm") {
      for (const row of students) {
        const enrollment = enrollments.find(
          (item) => item.student_id === row.student_id,
        );
        if (enrollment) {
          enrollment.effective_to = body.effective_date;
          if (body.to_class_period_id)
            enrollments.push({
              id: 900 + transitions.length,
              student_id: row.student_id,
              student: enrollment.student,
              class_period_id: Number(body.to_class_period_id),
              classPeriod: periods.find(
                (item) => item.id === Number(body.to_class_period_id),
              ),
              effective_from: body.effective_date,
              effective_to: null,
              entry_reason: "promotion",
              exit_reason: null,
              updated_at: time(),
            });
        }
        transitions.push({ student_id: row.student_id, type: body.type });
      }
    }
    return ok(response, {
      data: {
        ready,
        students,
        batch_key: action === "confirm" ? "batch:test" : undefined,
        to_status: students[0]?.to_status,
      },
    });
  }

  if (resource === "years") {
    if (method === "POST") {
      const record = {
        id: 92,
        semesters_count: 0,
        created_by: 1,
        updated_at: time(),
        status: "planned",
        ...body,
      };
      years = [record, ...years];
      return ok(response, { data: record }, 201);
    }
    if (method === "PUT") {
      const record = years.find((row) => row.id === numId);
      Object.assign(record, body);
      record.updated_at = time();
      return ok(response, { data: record });
    }
    return ok(response, page(years));
  }

  if (resource === "semesters") {
    if (method === "POST") {
      const record = {
        id: 913,
        created_by: 1,
        updated_at: time(),
        status: "planned",
        ...body,
      };
      semesters.push(record);
      return ok(response, { data: record }, 201);
    }
    if (method === "PUT") {
      const record = semesters.find((row) => row.id === numId);
      Object.assign(record, body);
      record.updated_at = time();
      return ok(response, { data: record });
    }
    const yearId = Number(url.searchParams.get("academic_year_id") ?? 0);
    return ok(
      response,
      page(
        semesters.filter((row) => !yearId || row.academic_year_id === yearId),
      ),
    );
  }

  if (resource === "classes") {
    if (method === "POST") {
      const record = { id: 73, periods_count: 0, updated_at: time(), ...body };
      classes.push(record);
      return ok(response, { data: record }, 201);
    }
    if (method === "PUT") {
      const record = classes.find((row) => row.id === numId);
      Object.assign(record, body);
      record.updated_at = time();
      return ok(response, { data: record });
    }
    const visible = scoped
      ? classes.filter((row) =>
          periods.some(
            (period) => period.class_id === row.id && period.id === 711,
          ),
        )
      : classes;
    return ok(response, page(visible));
  }

  if (resource === "class-periods") {
    if (method === "POST") {
      const record = {
        id: 731,
        schoolClass: classes.find((row) => row.id === Number(body.class_id)),
        academic_year_id: Number(body.academic_year_id),
        display_name: body.display_name,
        grade_snapshot: 7,
        capacity: body.capacity ?? null,
        status: "planned",
        enrollments_count: 0,
        updated_at: time(),
      };
      periods.push(record);
      return ok(response, { data: record }, 201);
    }
    if (method === "PUT") {
      const record = periods.find((row) => row.id === numId);
      Object.assign(record, body);
      record.updated_at = time();
      return ok(response, { data: record });
    }
    const visible = scoped ? periods.filter((row) => row.id === 711) : periods;
    return ok(response, page(visible));
  }

  if (resource === "enrollments") {
    if (method === "POST" && action === "move") {
      const enrollment = enrollments.find(
        (row) => row.student_id === body.student_id && !row.effective_to,
      );
      if (enrollment) enrollment.effective_to = body.effective_date;
      const target = periods.find(
        (row) => row.id === Number(body.to_class_period_id),
      );
      const created = {
        id: 950 + enrollments.length,
        student_id: body.student_id,
        student: enrollment?.student,
        class_period_id: target?.id,
        classPeriod: target,
        effective_from: body.effective_date,
        effective_to: null,
        entry_reason: body.entry_reason,
        exit_reason: null,
        updated_at: time(),
      };
      enrollments.push(created);
      return ok(response, { data: created }, 201);
    }
    if (method === "POST") {
      const period = periods.find(
        (row) => row.id === Number(body.class_period_id),
      );
      const record = {
        id: 970 + enrollments.length,
        student_id: Number(body.student_id),
        person: {
          id: 5000 + Number(body.student_id),
          full_name: `Siswa ${body.student_id}`,
        },
        class_period_id: period?.id,
        classPeriod: period,
        effective_from: body.effective_from,
        effective_to: null,
        entry_reason: body.entry_reason,
        exit_reason: null,
        updated_at: time(),
      };
      enrollments.push(record);
      return ok(response, { data: record }, 201);
    }
    if (method === "PUT") {
      const record = enrollments.find((row) => row.id === numId);
      Object.assign(record, body);
      record.updated_at = time();
      return ok(response, { data: record });
    }
    let visible = scoped
      ? enrollments.filter((row) => row.class_period_id === 711)
      : enrollments;
    const periodId = Number(url.searchParams.get("class_period_id") ?? 0);
    if (periodId)
      visible = visible.filter((row) => row.class_period_id === periodId);
    const date = url.searchParams.get("date");
    if (date)
      visible = visible.filter(
        (row) =>
          row.effective_from <= date &&
          (!row.effective_to || row.effective_to > date),
      );
    return ok(response, page(visible));
  }

  if (resource === "calendar") {
    if (method === "POST") {
      const record = { id: 602, updated_at: time(), ...body };
      calendar.push(record);
      return ok(response, { data: record }, 201);
    }
    if (method === "PUT") {
      const record = calendar.find((row) => row.id === numId);
      Object.assign(record, body);
      record.updated_at = time();
      return ok(response, { data: record });
    }
    return ok(response, page(calendar));
  }

  if (resource === "timetables") {
    if (action === "versions") {
      if (method === "POST") {
        const number =
          Math.max(
            ...ttVersions
              .filter((row) => row.timetable_id === numId)
              .map((row) => row.version_number),
          ) + 1;
        const record = {
          id: 3200 + ttVersions.length,
          timetable_id: numId,
          version_number: number,
          status: "draft",
          effective_from: body.effective_from,
          effective_to: null,
          published_by: null,
          published_at: null,
          reason: body.reason,
          entries_count: 0,
          created_by: 1,
          updated_at: time(),
        };
        ttVersions.unshift(record);
        if (body.copy_from_version_id) {
          for (const source of entries.filter(
            (row) =>
              row.timetable_version_id === Number(body.copy_from_version_id),
          )) {
            const copy = {
              ...source,
              id: 4200 + entries.length,
              schedule_entry_id: source.schedule_entry_id,
              timetable_version_id: record.id,
              updated_at: time(),
            };
            entries.push(copy);
            record.entries_count += 1;
          }
        }
        return ok(response, { data: record }, 201);
      }
      return ok(
        response,
        page(ttVersions.filter((row) => row.timetable_id === numId)),
      );
    }
    if (method === "POST") {
      const record = {
        id: 302,
        versions_count: 0,
        created_by: 1,
        lock_version: 0,
        updated_at: time(),
        code: "MAIN",
        ...body,
      };
      timetables.push(record);
      return ok(response, { data: record }, 201);
    }
    if (method === "PUT") {
      const record = timetables.find((row) => row.id === numId);
      Object.assign(record, body);
      record.updated_at = time();
      return ok(response, { data: record });
    }
    const semesterId = Number(url.searchParams.get("semester_id") ?? 0);
    return ok(
      response,
      page(
        timetables.filter(
          (row) => !semesterId || row.semester_id === semesterId,
        ),
      ),
    );
  }

  if (resource === "timetable-versions") {
    const version = ttVersions.find((row) => row.id === numId);
    if (action === "entries") {
      if (method === "POST") {
        const assignment = teaching.find(
          (row) => row.id === Number(body.teaching_assignment_id),
        );
        const start = String(body.start_time ?? "07:30");
        const end = String(body.end_time ?? "08:50");
        const clash = entries.some(
          (row) =>
            row.timetable_version_id === numId &&
            row.day_of_week === Number(body.day_of_week) &&
            row.start_time_snapshot.slice(0, 5) < end &&
            row.end_time_snapshot.slice(0, 5) > start,
        );
        if (clash)
          return ok(
            response,
            {
              message:
                "Benturan jadwal: guru/kelas sudah terjadwal pada jam tersebut.",
            },
            422,
          );
        const record = {
          id: 4200 + entries.length,
          schedule_entry_id: 4100 + entries.length,
          timetable_version_id: numId,
          teaching_assignment_id: Number(body.teaching_assignment_id),
          teachingAssignment: assignment,
          day_of_week: Number(body.day_of_week),
          start_time_snapshot: `${start}:00`,
          end_time_snapshot: `${end}:00`,
          planned_jp: String(Number(body.planned_jp ?? 1).toFixed(3)),
          planned_minutes: 80,
          room: body.room ?? null,
          effective_from: "2026-11-02",
          effective_to: null,
          updated_at: time(),
        };
        entries.push(record);
        const draft = ttVersions.find((row) => row.id === numId);
        if (draft) draft.entries_count = (draft.entries_count ?? 0) + 1;
        return ok(response, { data: record }, 201);
      }
      let visible = entries.filter((row) => row.timetable_version_id === numId);
      if (scoped)
        visible = visible.filter(
          (row) => row.teachingAssignment?.class_period_id === 711,
        );
      return ok(response, page(visible));
    }
    if (action === "publish" && method === "POST") {
      version.status = "published";
      version.published_at = time();
      for (const other of ttVersions) {
        if (other.id !== version.id && other.status === "published") {
          other.status = "superseded";
          const from = new Date(version.effective_from);
          from.setDate(from.getDate() - 1);
          other.effective_to = from.toISOString().slice(0, 10);
        }
      }
      version.updated_at = time();
      return ok(response, { data: version });
    }
    if (action === "generate-meetings" && method === "POST") {
      const source = entries.filter(
        (row) => row.timetable_version_id === numId,
      );
      let created = 0;
      for (const entry of source) {
        const date = new Date(body.date_from);
        const limit = new Date(body.date_to);
        while (date <= limit) {
          const iso = date.toISOString().slice(0, 10);
          if (
            date.getDay() === entry.day_of_week % 7 &&
            !meetings.some(
              (row) =>
                row.schedule_entry_id === entry.schedule_entry_id &&
                row.meeting_date === iso,
            )
          ) {
            meetings.push({
              ...entry,
              id: 5300 + meetings.length,
              meeting_date: iso,
              semester_id: 911,
              activeSubstitute: null,
              is_cancelled: false,
              class_period_id: entry.teachingAssignment?.class_period_id ?? 711,
              classPeriod: entry.teachingAssignment?.classPeriod ?? periods[0],
              subject: entry.teachingAssignment?.subject ?? { name: "Mapel" },
              scheduled_employee_id:
                entry.teachingAssignment?.employee_id ?? 3001,
              scheduledEmployee: entry.teachingAssignment?.employee ?? {
                person: { full_name: "Guru" },
              },
              updated_at: time(),
            });
            created += 1;
          }
          date.setDate(date.getDate() + 1);
        }
      }
      return ok(response, {
        data: { created, blocked: 0, skipped_existing: 0 },
      });
    }
    if (method === "PUT") {
      Object.assign(version, body);
      version.updated_at = time();
      return ok(response, { data: version });
    }
    return ok(response, { data: version });
  }

  if (resource === "schedule-entry-versions") {
    const entry = entries.find((row) => row.id === numId);
    if (method === "PUT") {
      Object.assign(entry, {
        day_of_week: Number(body.day_of_week ?? entry.day_of_week),
        start_time_snapshot: `${body.start_time ?? entry.start_time_snapshot.slice(0, 5)}:00`,
        end_time_snapshot: `${body.end_time ?? entry.end_time_snapshot.slice(0, 5)}:00`,
        room: body.room ?? entry.room,
      });
      entry.updated_at = time();
      return ok(response, { data: entry });
    }
    if (method === "DELETE") {
      entries = entries.filter((row) => row.id !== numId);
      return ok(response, { data: { removed: true } });
    }
    return ok(response, { data: entry });
  }

  if (resource === "meetings") {
    if (id && action === "cancel" && method === "POST") {
      const meeting = meetings.find((row) => row.id === numId);
      meeting.is_cancelled = true;
      meeting.cancellation_reason = body.reason;
      for (const row of substitutes) {
        if (row.meeting_id === meeting.id && !row.revoked_at) {
          row.revoked_at = time();
          meeting.activeSubstitute = null;
        }
      }
      meeting.updated_at = time();
      return ok(response, { data: meeting });
    }
    if (id && action === "substitute" && method === "POST") {
      const meeting = meetings.find((row) => row.id === numId);
      if (Number(body.substitute_employee_id) === meeting.scheduled_employee_id)
        return ok(
          response,
          { message: "Pengganti tidak boleh sama dengan guru terjadwal." },
          422,
        );
      for (const row of substitutes) {
        if (row.meeting_id === meeting.id && !row.revoked_at)
          row.revoked_at = time();
      }
      const record = {
        id: 6300 + substitutes.length,
        meeting_id: meeting.id,
        substitute_employee_id: Number(body.substitute_employee_id),
        substituteEmployee: {
          id: Number(body.substitute_employee_id),
          person: {
            full_name:
              Number(body.substitute_employee_id) === 1
                ? "Guru Ahmad"
                : "Guru Siti",
          },
        },
        assigned_at: time(),
        reason: body.reason,
        revoked_at: null,
        meeting,
        updated_at: time(),
      };
      substitutes.push(record);
      meeting.activeSubstitute = {
        id: record.id,
        meeting_id: record.meeting_id,
        substitute_employee_id: record.substitute_employee_id,
        assigned_at: record.assigned_at,
        reason: record.reason,
        substitute: record.substituteEmployee,
      };
      meeting.updated_at = time();
      return ok(response, { data: record }, 201);
    }
    if (id) {
      const meeting = meetings.find((row) => row.id === numId);
      return ok(response, { data: meeting });
    }
    let visible = scoped
      ? meetings.filter((row) => row.class_period_id === 711)
      : meetings;
    const dateFrom = url.searchParams.get("date_from");
    const dateTo = url.searchParams.get("date_to");
    const semesterId = Number(url.searchParams.get("semester_id") ?? 0);
    if (dateFrom)
      visible = visible.filter((row) => row.meeting_date >= dateFrom);
    if (dateTo) visible = visible.filter((row) => row.meeting_date <= dateTo);
    if (semesterId)
      visible = visible.filter((row) => row.semester_id === semesterId);
    visible = [...visible].sort((a, b) =>
      `${a.meeting_date}${a.start_time_snapshot}`.localeCompare(
        `${b.meeting_date}${b.start_time_snapshot}`,
      ),
    );
    return ok(response, page(visible));
  }

  if (resource === "substitute-assignments") {
    if (id && action === "revoke" && method === "POST") {
      const record = substitutes.find((row) => row.id === numId);
      record.revoked_at = time();
      const meeting = meetings.find((row) => row.id === record.meeting_id);
      if (meeting) meeting.activeSubstitute = null;
      record.updated_at = time();
      return ok(response, { data: record });
    }
    let visible = substitutes;
    if (scoped)
      visible = visible.filter((row) => row.meeting?.class_period_id === 711);
    if (url.searchParams.get("active_only") === "1")
      visible = visible.filter((row) => !row.revoked_at);
    return ok(response, page(visible));
  }

  if (
    resource === "teaching-assignments" ||
    resource === "homeroom-assignments"
  ) {
    const list = resource === "teaching-assignments" ? teaching : homeroom;
    if (method === "POST") {
      const record = {
        id: list.length + 1000,
        is_active: true,
        updated_at: time(),
        ...body,
      };
      list.push(record);
      return ok(response, { data: record }, 201);
    }
    if (method === "PUT") {
      const record = list.find((row) => row.id === numId);
      Object.assign(record, body);
      record.updated_at = time();
      return ok(response, { data: record });
    }
    const visible =
      scoped && resource === "teaching-assignments"
        ? list.filter((row) => row.class_period_id === 711)
        : list;
    return ok(response, page(visible));
  }

  ok(response, { message: "Tidak ditemukan." }, 404);
}
