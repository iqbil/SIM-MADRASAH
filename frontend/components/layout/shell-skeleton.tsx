import { Skeleton } from "@/components/ui/skeleton";
export function ShellSkeleton() {
  return (
    <div aria-live="polite" aria-busy="true" className="flex min-h-svh">
      <div className="hidden w-64 shrink-0 space-y-8 border-r bg-white p-6 lg:block">
        <Skeleton className="h-12 w-full" />
        <Skeleton className="h-16 w-full" />
        <Skeleton className="h-10 w-full" />
      </div>
      <div className="flex-1 p-6">
        <Skeleton className="mb-12 h-10 w-full" />
        <Skeleton className="mb-4 h-9 w-2/3" />
        <Skeleton className="mb-8 h-5 w-1/2" />
        <Skeleton className="h-[400px] rounded-2xl" />
      </div>
    </div>
  );
}
