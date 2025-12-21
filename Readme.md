# JobLaunch

A modern job-hunting web UI built with **HTML**, **CSS**, and **Font Awesome**. It includes a hero landing page, category explorer, about section, contact form, and auth flows (login/register) styled with responsive layouts and gradient accents.

## Features
- **Landing (index.html):** Hero search CTA with job-title input and “Find Job” button.
- **Categories (catagories.html / catagories.cata.html):** Grid of role categories and dynamic job listings populated from localStorage seed data.
- **About (about.html):** Company story and imagery.
- **Contact (contact.html):** Two-column contact form with styled inputs/textarea.
- **Auth (login.html & register.html):** Seeker/Employer tabs with inline validation; login supports demo roles.
- **Job detail + apply:** `job-details.html` reads `?id=` to show a job; `apply.html` submits an application (client-side) and blocks duplicates per user/job.
- **Shared styling (style.css) & behavior (script.js):** Dark theme palette, responsive grids, nav drawer, validation, seeded data, and basic localStorage flows.

## Getting Started (demo)
1. Clone or download the repo.
2. Open `index.html` in your browser (or use a simple static server like `npx serve .`).
3. Ensure internet access to load Font Awesome and Google Fonts (Sora, Manrope).

### Demo data
- Seeded accounts: `seeker@test.com / StrongPass!23` (Seeker) and `employer@test.com / StrongPass!23` (Employer).
- Seeded jobs are stored in `localStorage` and rendered on the listings page.

### Flows to try
- Register (seeker/employer) or use seeded accounts → login.
- Browse listings → open a job detail (`job-details.html?id=1`) → Apply (seeker only, duplicate guarded).
- Applications and auth state persist in `localStorage` for the session/device.

## Supabase mode (optional)
With Supabase credentials present, the UI switches from localStorage to Supabase for auth, jobs, and applications. Without credentials it automatically falls back to the demo.

### Front-end config
Add this snippet before `script.js` on any page you serve (already scaffolded in the HTML files):

```html
<script>
	window.SUPABASE_URL = 'https://YOUR-PROJECT.supabase.co';
	window.SUPABASE_ANON_KEY = 'YOUR_ANON_KEY';
</script>
```

### Database schema
Run these SQL statements in Supabase (SQL editor). They align with the fields used by `script.js` (`posted_at`, `responsibilities`, `requirements`, etc.).

```sql
-- Profiles (one row per auth user)
create table if not exists public.profiles (
	id uuid primary key references auth.users on delete cascade,
	role text check (role in ('Seeker','Employer')),
	name text,
	company text,
	title text,
	city text,
	bio text,
	updated_at timestamptz default now()
);

-- Jobs
create table if not exists public.jobs (
	id uuid primary key default gen_random_uuid(),
	employer_id uuid references auth.users on delete set null,
	title text not null,
	company text not null,
	location text,
	salary text,
	type text,
	category text,
	description text,
	responsibilities text[],
	requirements text[],
	posted_at timestamptz default now(),
	created_at timestamptz default now()
);

-- Applications
create table if not exists public.applications (
	id uuid primary key default gen_random_uuid(),
	job_id uuid references public.jobs on delete cascade,
	seeker_id uuid references auth.users on delete cascade,
	cover text,
	telegram text,
	portfolio text,
	status text default 'Pending',
	created_at timestamptz default now(),
	unique(job_id, seeker_id)
);
```

### Row Level Security (RLS)
Enable RLS and add policies to protect data when using the anon key in the browser:

```sql
alter table public.profiles enable row level security;
alter table public.jobs enable row level security;
alter table public.applications enable row level security;

-- Profiles: users can see/update their own profile
create policy "Profiles select own" on public.profiles for select using (auth.uid() = id);
create policy "Profiles update own" on public.profiles for update using (auth.uid() = id);
create policy "Profiles insert self" on public.profiles for insert with check (auth.uid() = id);

-- Jobs: everyone can read; only owner can write
create policy "Jobs read all" on public.jobs for select using (true);
create policy "Jobs insert owner" on public.jobs for insert with check (auth.uid() = employer_id);
create policy "Jobs update owner" on public.jobs for update using (auth.uid() = employer_id);

-- Applications: seekers manage their own; employers see applications to their jobs
create policy "Apps insert self" on public.applications for insert with check (auth.uid() = seeker_id);
create policy "Apps select own" on public.applications for select using (auth.uid() = seeker_id);
create policy "Apps select employer" on public.applications for select using (
	exists (
		select 1 from public.jobs j
		where j.id = job_id and j.employer_id = auth.uid()
	)
);
```

### Testing Supabase mode
1) Configure URL/key in the HTML (see above). 2) Create the tables and policies. 3) Seed data via SQL or Supabase Table Editor (make sure `posted_at` is set). 4) Load `index.html`; listings and details will pull from `jobs`, and Apply will insert into `applications`. 5) Sign-up/sign-in uses Supabase auth; confirm email if your project requires it.

### Notes
- Column names expected by `script.js`: `title`, `company`, `location`, `salary`, `type`, `category`, `description`, `responsibilities` (text[]), `requirements` (text[]), `posted_at`.
- Duplicate applications are prevented by the unique `(job_id, seeker_id)` constraint.
- If you later remove the local demo, delete the seed/localStorage branches in `script.js` and drop the placeholder config values from the HTML.

## Project Structure
- `index.html` — Landing page.
- `catagories.html` — Category grid.
- `about.html` — About section.
- `contact.html` — Contact form.
- `login.html` — Login screen.
- `register.html` — Seeker/Employer signup.
- `job-details.html` — Job detail page populated via query id.
- `style.css` — Global styles, layout, and responsive rules.
- `script.js` — Nav drawer, inline validation, seeded demo data (localStorage), auth, listings, job detail, and apply flows.
- `assets/` — Images (hero background, students, etc.).

## Customization
- Update accent colors or theme variables in `style.css` under `:root`.
- Replace hero/background images in `assets/`.
- Adjust category cards/icons in `catagories.html`.
- Tweak form fields/placeholders in auth/contact pages.

## Notes
- Responsive breakpoints: 1100px, 800px, 640px, 480px.
- Buttons use shared `.btn`, `.btn-ghost`, `.btn-green` classes with hover shadows.
- Inputs/textareas share consistent padding, borders, and focus outlines.

