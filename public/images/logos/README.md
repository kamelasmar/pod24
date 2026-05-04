# Client logos

Drop SVG (preferred) or PNG files here, named after the client slug:

- `aldar.svg` — Aldar Properties
- `doh.svg` — Department of Health Abu Dhabi
- `adnec.svg` — Abu Dhabi National Exhibition Centre
- `<slug>.svg` — other clients

The `b2b-form.blade.php` component checks for the file with `file_exists()`
and falls back to a styled wordmark if the SVG isn't present yet.

Add new clients by editing the `$clients` array in
`resources/views/components/pod24/b2b-form.blade.php`.

**Sourcing tip:** simpleicons.org has many corporate brand SVGs. For
GCC-specific brands like Aldar / ADNEC / DoH, request a vector from the
brand directly — most have a press / brand kit page.

**Sizing:** logos render at `h-7` mobile / `h-9` desktop. Aim for SVGs
where the visual weight is balanced at those heights (typical wordmark
ratios are fine).

**Color:** the section background is dark (`bg-pod-ink`). Use white or
near-white logos. If the brand asset is colored, ask the brand for a
mono-white version.
