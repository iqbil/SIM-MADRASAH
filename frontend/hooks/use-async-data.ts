"use client";

import { useCallback, useEffect, useRef, useState } from "react";
import { requestError } from "@/lib/api-client";

export function useAsyncData<T>(loader: () => Promise<T>) {
  const [data, setData] = useState<T>();
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState("");
  const generation = useRef(0);
  const mounted = useRef(false);
  const reload = useCallback(
    async (quiet = false) => {
      const current = ++generation.current;
      if (!quiet) setLoading(true);
      setError("");
      try {
        const result = await loader();
        if (mounted.current && current === generation.current) setData(result);
      } catch (reason) {
        if (mounted.current && current === generation.current)
          setError(requestError(reason));
      } finally {
        if (mounted.current && current === generation.current)
          setLoading(false);
      }
    },
    [loader],
  );
  useEffect(() => {
    mounted.current = true;
    void reload();
    return () => {
      mounted.current = false;
      generation.current += 1;
    };
  }, [reload]);
  return { data, loading, error, reload };
}
