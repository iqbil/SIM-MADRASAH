<!-- BEGIN:nextjs-agent-rules -->

## This is NOT the Next.js you know

This version has breaking changes — APIs, conventions, and file structure may all differ from your training data. Read the relevant guide in `node_modules/next/dist/docs/` (resolved from this file's directory; in monorepos the `next` package may not be visible from the repo root) before writing any code. Heed deprecation notices.

This block is written and re-added by `next dev` — verify at `node_modules/next/dist/server/lib/generate-agent-files.js`. Removing it from a diff only re-creates the uncommitted change; committing it with your work keeps the tree clean.

<!-- END:nextjs-agent-rules -->

## Pedoman UI/UX proyek

Sebelum mengubah UI frontend, baca [design-system.md](../design-system.md).
Gunakan komponen shadcn/ui lokal, token tema, dan pola responsif yang ditetapkan di sana.
Perubahan pola bersama harus diikuti pembaruan design system dan pemeriksaan halaman yang terdampak.
Untuk error hydration dengan atribut `bis_*`, ikuti diagnosis browser di dokumen tersebut;
jangan menambahkan suppression global atau pembersih DOM untuk menutupi injeksi ekstensi.
