let meetings;
let realizations;
let counter = 0;
const time = () => new Date(Date.now() + ++counter).toISOString();

/** Hanya guru yang terhubung pegawai pada mock realisasi (konsisten academic mock). */
const EMPLOYEE_USERS = new Map([
  [
    "guru",
    {
      id: 3001,
      employee_number: "P-001",
      person: { id: 2002, full_name: "Guru Ahmad" },
    },
  ],
]);
const VERIFIER_USERS = new Set(["verifikator"]);

export function resetTeaching() {
  counter = 0;
  const today = new Date().toLocaleDateString("en-CA", {
    timeZone: "Asia/Jakarta",
  });
  const base = {
    semester_id: 911,
    class_period_id: 711,
    subject_id: 401,
    scheduled_employee_id: 3001,
    scheduledEmployee: { id: 3001, person: { full_name: "Guru Ahmad" } },
    start_time_snapshot: "00:30:00",
    end_time_snapshot: "01:50:00",
    planned_jp_snapshot: "2.000",
    planned_minutes_snapshot: 80,
    is_cancelled: false,
    activeSubstitute: null,
    realization: null,
  };
  meetings = [
    {
      ...base,
      id: 5101,
      meeting_date: today,
      classPeriod: { display_name: "VII-A 2026/2027" },
      subject: { id: 401, code: "MTK", name: "Matematika" },
    },
    {
      ...base,
      id: 5102,
      meeting_date: today,
      classPeriod: { display_name: "VII-A 2026/2027" },
      subject: { id: 401, code: "MTK", name: "Matematika" },
      start_time_snapshot: "02:00:00",
      end_time_snapshot: "03:20:00",
    },
  ];
  realizations = new Map();
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

const revisionShape = (revision) => ({
  ...revision,
  meeting: (() => {
    const meeting = meetings.find(
      (item) => realizations.get(item.id)?.id === revision.realization_id,
    );
    return meeting
      ? {
          id: meeting.id,
          meeting_date: meeting.meeting_date,
          start_time_snapshot: meeting.start_time_snapshot,
          planned_jp_snapshot: meeting.planned_jp_snapshot,
          is_cancelled: meeting.is_cancelled,
          classPeriod: meeting.classPeriod,
          subject: meeting.subject,
        }
      : null;
  })(),
  actualEmployee: {
    id: revision.actual_employee_id,
    person: { full_name: "Guru Ahmad" },
  },
  submitter: revision.submitted_at
    ? { person: { full_name: "Guru Ahmad" } }
    : null,
  verification: revision.decision
    ? {
        action: revision.decision,
        reason: revision.decision_reason,
        verified_at: revision.decision_at,
        verifier: { person: { full_name: "Verifikator" } },
      }
    : null,
});

/** Handler mock /api/v1/teaching/* (via BFF). */
export function handleTeaching({
  body = {},
  method = "GET",
  response,
  url,
  username,
}) {
  const route = url.pathname.replace("/api/v1/teaching/", "");
  const [resource, id, action] = route.split("/");
  const employee = EMPLOYEE_USERS.get(username);
  const verifier = VERIFIER_USERS.has(username);

  if (resource === "meetings") {
    if (!employee)
      return ok(
        response,
        { message: "Akses realisasi mengajar tidak diberikan." },
        403,
      );
    if (id && action === "realizations" && method === "POST") {
      const meeting = meetings.find((row) => row.id === Number(id));
      if (!meeting)
        return ok(response, { message: "Pertemuan tidak ditemukan." }, 404);
      const existing = realizations.get(meeting.id);
      if (existing) {
        if (
          existing.revisions[0].verification_status === "pending" ||
          existing.revisions[0].verification_status === "approved"
        )
          return ok(
            response,
            {
              message:
                "Revisi hanya dapat dibuat sebelum diajukan atau setelah ditolak.",
            },
            422,
          );
      }
      const revision = {
        id:
          8000 +
          realizations.size * 10 +
          (existing ? existing.revisions.length : 0),
        realization_id: existing ? existing.id : 8100 + realizations.size,
        revision_number: existing ? existing.revisions.length + 1 : 1,
        actual_employee_id: employee.id,
        substitute_assignment_id: null,
        execution_status: body.execution_status,
        verification_status: "not_submitted",
        material: body.material ?? null,
        actual_jp: String(body.actual_jp ?? 0),
        actual_minutes: body.actual_minutes ?? null,
        nonexecution_reason: body.nonexecution_reason ?? null,
        notes: body.notes ?? null,
        submitted_at: null,
        submitted_by: null,
        updated_at: time(),
      };
      if (existing) existing.revisions.unshift(revision);
      else
        realizations.set(meeting.id, {
          id: revision.realization_id,
          meeting_id: meeting.id,
          revisions: [revision],
        });
      if (body.execution_status === "not_held") {
        revision.actual_jp = "0";
        revision.actual_minutes = 0;
      }

      return ok(response, { data: revisionShape(revision) }, 201);
    }
    const rows = meetings.map((meeting) => ({
      ...meeting,
      realization: realizations.has(meeting.id)
        ? {
            currentRevision: revisionShape(
              realizations.get(meeting.id).revisions[0],
            ),
          }
        : null,
    }));
    if (id) {
      const meeting = rows.find((row) => row.id === Number(id));

      return meeting
        ? ok(response, { data: meeting })
        : ok(response, { message: "Pertemuan tidak ditemukan." }, 404);
    }

    return ok(response, page(rows));
  }

  if (resource === "realizations") {
    if (!employee)
      return ok(
        response,
        { message: "Akses realisasi mengajar tidak diberikan." },
        403,
      );
    const rows = [...realizations.values()]
      .map((realization) => revisionShape(realization.revisions[0]))
      .sort((a, b) => b.id - a.id);
    const from = url.searchParams.get("date_from");
    const to = url.searchParams.get("date_to");
    const status = url.searchParams.get("verification_status");
    const filtered = rows.filter(
      (row) =>
        (!from || row.meeting.meeting_date >= from) &&
        (!to || row.meeting.meeting_date <= to) &&
        (!status || row.verification_status === status),
    );
    const approved = filtered.filter(
      (row) =>
        row.verification_status === "approved" &&
        row.execution_status === "held",
    );

    return ok(response, {
      data: {
        rows: page(filtered),
        totals: {
          approved_jp: approved.reduce(
            (sum, row) => sum + Number(row.actual_jp),
            0,
          ),
          approved_meetings: approved.length,
        },
      },
    });
  }

  if (resource === "revisions") {
    const all = [...realizations.values()].flatMap(
      (realization) => realization.revisions,
    );
    const revision = all.find((row) => row.id === Number(id));
    if (!revision)
      return ok(response, { message: "Revisi tidak ditemukan." }, 404);
    if (action === "submit" && method === "POST") {
      if (!employee)
        return ok(
          response,
          { message: "Akses realisasi mengajar tidak diberikan." },
          403,
        );
      if (revision.verification_status !== "not_submitted")
        return ok(
          response,
          { message: "Revisi ini sudah diajukan atau sudah diputuskan." },
          422,
        );
      revision.verification_status = "pending";
      revision.submitted_at = time();
      revision.submitted_by = 1;

      return ok(response, { data: revisionShape(revision) });
    }
    if (action === "decision" && method === "POST") {
      if (!verifier)
        return ok(
          response,
          { message: "Verifikasi realisasi memerlukan mandat verifikator." },
          403,
        );
      if (revision.verification_status !== "pending")
        return ok(
          response,
          { message: "Revisi ini tidak menunggu keputusan." },
          422,
        );
      if (body.action === "reject" && !body.reason)
        return ok(response, { message: "Alasan penolakan wajib." }, 422);
      revision.verification_status =
        body.action === "approve" ? "approved" : "rejected";
      revision.decision = body.action;
      revision.decision_reason = body.reason ?? null;
      revision.decision_at = time();
      revision.updated_at = time();

      return ok(response, { data: revisionShape(revision) });
    }

    return ok(response, { data: revisionShape(revision) });
  }

  if (resource === "verifications") {
    if (!verifier)
      return ok(
        response,
        { message: "Verifikasi realisasi memerlukan mandat verifikator." },
        403,
      );
    const status = url.searchParams.get("verification_status") ?? "pending";
    const rows = [...realizations.values()]
      .map((realization) => revisionShape(realization.revisions[0]))
      .filter((row) => row.verification_status === status)
      .sort((a, b) => b.id - a.id);

    return ok(response, page(rows));
  }

  ok(response, { message: "Tidak ditemukan." }, 404);
}
