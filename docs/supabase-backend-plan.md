# Supabase Backend Plan (Phase 4 compliance)

This plan replaces the localStorage demo with a Supabase-backed implementation that satisfies the attached test cases and test plan.

## Data model
- `profiles`: `id UUID PK (auth.uid)`, `role text check in ('Seeker','Employer')`, `name text`, `company text`, `title text`, `city text`, `bio text`, `created_at timestamptz default now()`.
- `jobs`: `id bigserial PK`, `owner_id uuid references profiles(id)`, `title text`, `description text`, `salary text`, `location text`, `category text`, `type text`, `posted_at timestamptz default now()`. Indexes: `idx_jobs_title`, `idx_jobs_company`, `idx_jobs_category`, `idx_jobs_posted_at`.
- `applications`: `id bigserial PK`, `job_id bigint references jobs(id) on delete cascade`, `seeker_id uuid references profiles(id)`, `cover text`, `telegram text`, `portfolio text`, `status text check in ('Pending','Interview Scheduled','Rejected','Hired') default 'Pending'`, `history jsonb default '[]'::jsonb`, `created_at timestamptz default now()`. Constraint: `unique(job_id, seeker_id)`.
- Optional storage: bucket `resumes` with signed uploads for resume URLs (to satisfy resume upload tests if added).

## RLS (row-level security)
- Enable RLS on all tables.
- `profiles`:
  - `SELECT`: user can read own row.
  - `INSERT`: allowed for auth.uid on first upsert (Edge Function controlled).
  - `UPDATE`: only where `id = auth.uid`.
- `jobs`:
  - `SELECT`: allow all (public browse/search).
  - `INSERT/UPDATE/DELETE`: `auth.uid = owner_id` AND `current_setting('request.jwt.claims', true)::jsonb ->> 'role' = 'Employer'`.
- `applications`:
  - `INSERT`: `auth.uid = seeker_id` AND role = 'Seeker'.
  - `SELECT`: allowed if `seeker_id = auth.uid` OR job.owner_id = auth.uid (join via policy using `job_id` subquery).
  - `UPDATE` (status/history only): allowed if job.owner_id = auth.uid AND requester role = 'Employer'.

## Edge Functions (all require service role or RLS-compliant user JWT)
Use typed JSON responses with `{ data, error }` and meaningful messages for UI alerts.
- `create_job`: Employer-only. Body: {title, description, salary, location, category, type}. Sets `owner_id = auth.uid`.
- `update_job`: Employer-only; checks ownership. Body: same as create (partial or full) and `job_id`.
- `delete_job`: Employer-only; checks ownership. Body: { job_id }.
- `list_jobs`: Public. Query params: `q`, `company`, `category`, `type`, `limit`, `offset`. Orders by `posted_at desc`.
- `get_job`: Public. Params: `id`.
- `apply_job`: Seeker-only. Body: { job_id, cover, telegram, portfolio }. Handles unique violation to return duplicate-friendly message.
- `list_applications_for_employer`: Employer-only. Params: `job_id` (must be owned). Returns applicant info + status/history.
- `update_application_status`: Employer-only. Body: { application_id, status }. Appends to `history` with timestamp and actor.
- `list_my_applications`: Seeker-only. Returns applications joined with job info.

## Frontend wiring (per page)
- Global: move Supabase URL/anon key to a single config script (no inline secrets). Initialize Supabase once and drop localStorage auth. Use `supabase.auth.getSession()` for current user; logout via `supabase.auth.signOut()`.
- `login.html`: submit to Supabase Auth; on success fetch profile, enforce role match; store session via Supabase client only; keep Google OAuth with intended role stored before redirect.
- `register.html`: Supabase signUp; then `profiles.upsert` with selected role/name/company; redirect home.
- `catagories.html` / `catagories.cata.html`: call `list_jobs` with query params for search/filter; render cards linking to `apply.html?id=...`.
- `job-details.html`: call `get_job` by id; fill data- attributes; set apply link.
- `apply.html`: submit to `apply_job`; on duplicate, show friendly message; redirect to `thank-you-apply.html` on success.
- Employer dashboards (`company-*` pages): add fetches to `list_jobs` (owner scoped), `list_applications_for_employer`, and mutation to `update_application_status`.
- Seeker dashboards (`user-*` pages): add `list_my_applications` display.

## Performance/security notes
- Pagination everywhere (`limit/offset`) to keep <1s response targets.
- Indexed search fields; avoid wildcard-leading queries.
- Do not store JWT in localStorage; rely on Supabase session handling. All authZ enforced server-side via RLS and functions.
- Validate inputs server-side in Edge Functions (lengths, enums, URL validation for portfolio/telegram formats).
- Remove hard-coded Supabase keys from HTML; use a single config file or environment injection during deployment.

## Work order
1) Add schema SQL + RLS policies (supabase/migrations or single SQL file).
2) Add Edge Functions implementing the endpoints above.
3) Refactor frontend: central Supabase client, auth flows, job list/detail/apply wiring, dashboards.
4) Add loading/error states and align alerts to server messages.
5) Optional: resume uploads via Storage + signed URLs.
