"use client";

import { AcademicAssignments } from "@/components/academic/academic-assignments";
import { AcademicCalendar } from "@/components/academic/academic-calendar";
import { AcademicClasses } from "@/components/academic/academic-classes";
import { AcademicEnrollments } from "@/components/academic/academic-enrollments";
import { AcademicPeriods } from "@/components/academic/academic-periods";
import { AcademicSchedules } from "@/components/academic/academic-schedules";
import { AcademicSubstitutes } from "@/components/academic/academic-substitutes";
import { AcademicTransitions } from "@/components/academic/academic-transitions";
import { HomeroomClass } from "@/components/academic/homeroom-class";
import type { AcademicSection } from "@/lib/academic";

export function AcademicWorkspace({
  section,
  access,
}: {
  section: AcademicSection;
  access: string[];
}) {
  switch (section) {
    case "tahun-ajaran":
      return <AcademicPeriods access={access} />;
    case "rombel":
      return <AcademicClasses access={access} />;
    case "keanggotaan":
      return <AcademicEnrollments access={access} />;
    case "mutasi":
      return <AcademicTransitions access={access} />;
    case "kalender":
      return <AcademicCalendar access={access} />;
    case "penugasan":
      return <AcademicAssignments access={access} />;
    case "jadwal":
      return <AcademicSchedules access={access} />;
    case "pengganti":
      return <AcademicSubstitutes access={access} />;
    case "perwalian":
      return <HomeroomClass />;
  }
}
