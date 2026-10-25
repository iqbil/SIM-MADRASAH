import { type NextRequest, NextResponse } from "next/server";
import { BackendError, backendJson } from "@/lib/auth/backend";
import {
  ACCESS_COOKIE,
  isSameOrigin,
  privateResponse,
} from "@/lib/auth/cookies";

type Context = { params: Promise<{ path: string[] }> };

const id = "[1-9]\\d*";
const RESOURCE =
  "(?:years|semesters|classes|class-periods|enrollments|calendar|teaching-assignments|homeroom-assignments|timetables|timetable-versions|schedule-entry-versions|lesson-slots|meetings|substitute-assignments)";
const ACTION_POST = `(?:${RESOURCE}|enrollments\\/move|transitions\\/(?:preview|confirm)|timetables\\/${id}\\/versions|timetable-versions\\/${id}\\/(?:entries|publish|generate-meetings)|meetings\\/${id}\\/(?:cancel|substitute)|substitute-assignments\\/${id}\\/revoke)`;

async function proxy(
  request: NextRequest,
  context: Context,
): Promise<NextResponse> {
  const path = (await context.params).path.join("/");
  const method = request.method;
  const allowed =
    method === "GET"
      ? new RegExp(
          `^(?:context|${RESOURCE}(?:\\/${id})?|timetables\\/${id}\\/versions|timetable-versions\\/${id}\\/entries)$`,
        ).test(path)
      : method === "PUT"
        ? path === "context" || new RegExp(`^${RESOURCE}\\/${id}$`).test(path)
        : method === "POST"
          ? new RegExp(`^${ACTION_POST}$`).test(path)
          : method === "DELETE" &&
            new RegExp(`^schedule-entry-versions\\/${id}$`).test(path);
  if (!allowed)
    return privateResponse(
      NextResponse.json({ message: "Tidak ditemukan." }, { status: 404 }),
    );
  if (method !== "GET" && !isSameOrigin(request))
    return privateResponse(
      NextResponse.json(
        { message: "Permintaan tidak diizinkan." },
        { status: 403 },
      ),
    );
  const access = request.cookies.get(ACCESS_COOKIE)?.value;
  if (!access)
    return privateResponse(
      NextResponse.json({ message: "Silakan login kembali." }, { status: 401 }),
    );
  try {
    let body: string | undefined;
    if (method !== "GET") {
      const data = await request.json();
      if (!data || typeof data !== "object" || Array.isArray(data))
        return privateResponse(
          NextResponse.json(
            { message: "Format permintaan tidak valid." },
            { status: 422 },
          ),
        );
      body = JSON.stringify(data);
      if (body.length > 20_000)
        return privateResponse(
          NextResponse.json(
            { message: "Permintaan terlalu besar." },
            { status: 413 },
          ),
        );
    }
    const params = new URLSearchParams();
    for (const name of [
      "q",
      "page",
      "per_page",
      "status",
      "grade",
      "is_active",
      "academic_year_id",
      "semester_id",
      "class_period_id",
      "student_id",
      "employee_id",
      "subject_id",
      "event_type",
      "from",
      "to",
      "date",
      "date_from",
      "date_to",
      "day_of_week",
      "active_only",
    ]) {
      const value = request.nextUrl.searchParams.get(name);
      if (value !== null) params.set(name, value);
    }
    const result = await backendJson(
      path + (params.size ? `?${params.toString()}` : ""),
      {
        method,
        headers: {
          Authorization: `Bearer ${access}`,
          "Content-Type": "application/json",
        },
        body,
      },
      "academic",
    );
    return privateResponse(NextResponse.json(result));
  } catch (error) {
    return privateResponse(
      NextResponse.json(
        error instanceof BackendError
          ? error.payload
          : { message: "Permintaan belum berhasil. Silakan coba kembali." },
        {
          status:
            error instanceof BackendError
              ? error.status
              : error instanceof SyntaxError
                ? 422
                : 500,
        },
      ),
    );
  }
}

export const GET = proxy;
export const POST = proxy;
export const PUT = proxy;
export const DELETE = proxy;
