-- Schema for JobLaunch Supabase backend (Phase 4)
-- Run in Supabase SQL editor or include in migrations. Assumes `auth.uid()` available.

-- Types
DO $$ BEGIN
  IF NOT EXISTS (SELECT 1 FROM pg_type WHERE typname = 'application_status') THEN
    CREATE TYPE application_status AS ENUM ('Pending','Interview Scheduled','Rejected','Hired');
  END IF;
END $$;

-- Tables
CREATE TABLE IF NOT EXISTS public.profiles (
  id uuid PRIMARY KEY REFERENCES auth.users (id) ON DELETE CASCADE,
  role text NOT NULL CHECK (role IN ('Seeker','Employer')),
  name text NOT NULL,
  company text DEFAULT ''::text,
  title text DEFAULT ''::text,
  city text DEFAULT ''::text,
  bio text DEFAULT ''::text,
  created_at timestamptz NOT NULL DEFAULT now()
);

CREATE TABLE IF NOT EXISTS public.jobs (
  id bigserial PRIMARY KEY,
  owner_id uuid NOT NULL REFERENCES public.profiles (id) ON DELETE CASCADE,
  company text DEFAULT ''::text,
  title text NOT NULL,
  description text NOT NULL,
  salary text DEFAULT ''::text,
  location text DEFAULT ''::text,
  category text DEFAULT ''::text,
  type text DEFAULT ''::text,
  posted_at timestamptz NOT NULL DEFAULT now()
);

CREATE TABLE IF NOT EXISTS public.applications (
  id bigserial PRIMARY KEY,
  job_id bigint NOT NULL REFERENCES public.jobs (id) ON DELETE CASCADE,
  seeker_id uuid NOT NULL REFERENCES public.profiles (id) ON DELETE CASCADE,
  cover text DEFAULT ''::text,
  telegram text DEFAULT ''::text,
  portfolio text DEFAULT ''::text,
  status application_status NOT NULL DEFAULT 'Pending',
  history jsonb NOT NULL DEFAULT '[]'::jsonb,
  created_at timestamptz NOT NULL DEFAULT now(),
  CONSTRAINT applications_job_seeker_unique UNIQUE (job_id, seeker_id)
);

-- Backfill columns on existing tables so reruns won't fail when tables already exist
DO $$ BEGIN
  -- profiles columns
  IF NOT EXISTS (
    SELECT 1 FROM information_schema.columns
    WHERE table_schema='public' AND table_name='profiles' AND column_name='company'
  ) THEN
    ALTER TABLE public.profiles ADD COLUMN company text DEFAULT ''::text;
  END IF;
  IF NOT EXISTS (
    SELECT 1 FROM information_schema.columns
    WHERE table_schema='public' AND table_name='profiles' AND column_name='title'
  ) THEN
    ALTER TABLE public.profiles ADD COLUMN title text DEFAULT ''::text;
  END IF;
  IF NOT EXISTS (
    SELECT 1 FROM information_schema.columns
    WHERE table_schema='public' AND table_name='profiles' AND column_name='city'
  ) THEN
    ALTER TABLE public.profiles ADD COLUMN city text DEFAULT ''::text;
  END IF;
  IF NOT EXISTS (
    SELECT 1 FROM information_schema.columns
    WHERE table_schema='public' AND table_name='profiles' AND column_name='bio'
  ) THEN
    ALTER TABLE public.profiles ADD COLUMN bio text DEFAULT ''::text;
  END IF;

  -- jobs columns
  IF NOT EXISTS (
    SELECT 1 FROM information_schema.columns
    WHERE table_schema='public' AND table_name='jobs' AND column_name='company'
  ) THEN
    ALTER TABLE public.jobs ADD COLUMN company text DEFAULT ''::text;
  END IF;
  IF NOT EXISTS (
    SELECT 1 FROM information_schema.columns
    WHERE table_schema='public' AND table_name='jobs' AND column_name='description'
  ) THEN
    ALTER TABLE public.jobs ADD COLUMN description text NOT NULL DEFAULT ''::text;
  END IF;
  IF NOT EXISTS (
    SELECT 1 FROM information_schema.columns
    WHERE table_schema='public' AND table_name='jobs' AND column_name='salary'
  ) THEN
    ALTER TABLE public.jobs ADD COLUMN salary text DEFAULT ''::text;
  END IF;
  IF NOT EXISTS (
    SELECT 1 FROM information_schema.columns
    WHERE table_schema='public' AND table_name='jobs' AND column_name='location'
  ) THEN
    ALTER TABLE public.jobs ADD COLUMN location text DEFAULT ''::text;
  END IF;
  IF NOT EXISTS (
    SELECT 1 FROM information_schema.columns
    WHERE table_schema='public' AND table_name='jobs' AND column_name='category'
  ) THEN
    ALTER TABLE public.jobs ADD COLUMN category text DEFAULT ''::text;
  END IF;
  IF NOT EXISTS (
    SELECT 1 FROM information_schema.columns
    WHERE table_schema='public' AND table_name='jobs' AND column_name='type'
  ) THEN
    ALTER TABLE public.jobs ADD COLUMN type text DEFAULT ''::text;
  END IF;
  IF NOT EXISTS (
    SELECT 1 FROM information_schema.columns
    WHERE table_schema='public' AND table_name='jobs' AND column_name='posted_at'
  ) THEN
    ALTER TABLE public.jobs ADD COLUMN posted_at timestamptz NOT NULL DEFAULT now();
  END IF;

  -- applications columns
  IF NOT EXISTS (
    SELECT 1 FROM information_schema.columns
    WHERE table_schema='public' AND table_name='applications' AND column_name='status'
  ) THEN
    ALTER TABLE public.applications ADD COLUMN status application_status NOT NULL DEFAULT 'Pending';
  END IF;
  IF NOT EXISTS (
    SELECT 1 FROM information_schema.columns
    WHERE table_schema='public' AND table_name='applications' AND column_name='history'
  ) THEN
    ALTER TABLE public.applications ADD COLUMN history jsonb NOT NULL DEFAULT '[]'::jsonb;
  END IF;
  IF NOT EXISTS (
    SELECT 1 FROM information_schema.columns
    WHERE table_schema='public' AND table_name='applications' AND column_name='created_at'
  ) THEN
    ALTER TABLE public.applications ADD COLUMN created_at timestamptz NOT NULL DEFAULT now();
  END IF;
END $$;

-- Indexes for performance/search
CREATE INDEX IF NOT EXISTS idx_jobs_title ON public.jobs USING gin (to_tsvector('simple', title));
CREATE INDEX IF NOT EXISTS idx_jobs_company ON public.jobs (company);
CREATE INDEX IF NOT EXISTS idx_jobs_owner ON public.jobs (owner_id);
CREATE INDEX IF NOT EXISTS idx_jobs_category ON public.jobs (category);
CREATE INDEX IF NOT EXISTS idx_jobs_posted_at ON public.jobs (posted_at DESC);
CREATE INDEX IF NOT EXISTS idx_applications_job ON public.applications (job_id);
CREATE INDEX IF NOT EXISTS idx_applications_seeker ON public.applications (seeker_id);

-- RLS enable
ALTER TABLE public.profiles ENABLE ROW LEVEL SECURITY;
ALTER TABLE public.jobs ENABLE ROW LEVEL SECURITY;
ALTER TABLE public.applications ENABLE ROW LEVEL SECURITY;

-- Policies: profiles
CREATE POLICY profiles_select_own ON public.profiles
  FOR SELECT USING (id = auth.uid());
CREATE POLICY profiles_insert_self ON public.profiles
  FOR INSERT WITH CHECK (id = auth.uid());
CREATE POLICY profiles_update_own ON public.profiles
  FOR UPDATE USING (id = auth.uid()) WITH CHECK (id = auth.uid());

-- Helper predicates
CREATE OR REPLACE FUNCTION public.is_employer() RETURNS boolean
LANGUAGE sql STABLE AS $$
  SELECT EXISTS (SELECT 1 FROM public.profiles p WHERE p.id = auth.uid() AND p.role = 'Employer');
$$;

CREATE OR REPLACE FUNCTION public.is_seeker() RETURNS boolean
LANGUAGE sql STABLE AS $$
  SELECT EXISTS (SELECT 1 FROM public.profiles p WHERE p.id = auth.uid() AND p.role = 'Seeker');
$$;

-- Policies: jobs
CREATE POLICY jobs_select_all ON public.jobs
  FOR SELECT USING (true);
CREATE POLICY jobs_insert_owner ON public.jobs
  FOR INSERT WITH CHECK (owner_id = auth.uid() AND public.is_employer());
CREATE POLICY jobs_update_owner ON public.jobs
  FOR UPDATE USING (owner_id = auth.uid() AND public.is_employer()) WITH CHECK (owner_id = auth.uid() AND public.is_employer());
CREATE POLICY jobs_delete_owner ON public.jobs
  FOR DELETE USING (owner_id = auth.uid() AND public.is_employer());

-- Policies: applications
CREATE POLICY applications_insert_seeker ON public.applications
  FOR INSERT WITH CHECK (seeker_id = auth.uid() AND public.is_seeker());
CREATE POLICY applications_select_self_or_owner ON public.applications
  FOR SELECT USING (
    seeker_id = auth.uid()
    OR EXISTS (
      SELECT 1 FROM public.jobs j
      WHERE j.id = applications.job_id AND j.owner_id = auth.uid()
    )
  );
CREATE POLICY applications_update_owner ON public.applications
  FOR UPDATE USING (
    EXISTS (
      SELECT 1 FROM public.jobs j
      WHERE j.id = applications.job_id AND j.owner_id = auth.uid()
    )
    AND public.is_employer()
  ) WITH CHECK (
    EXISTS (
      SELECT 1 FROM public.jobs j
      WHERE j.id = applications.job_id AND j.owner_id = auth.uid()
    )
    AND public.is_employer()
  );

-- Restrict deletes to owners (optional: cascade already on job delete)
CREATE POLICY applications_delete_owner ON public.applications
  FOR DELETE USING (
    EXISTS (
      SELECT 1 FROM public.jobs j
      WHERE j.id = applications.job_id AND j.owner_id = auth.uid()
    )
    AND public.is_employer()
  );

-- Default grants: rely on policies; revoke broad access if needed
REVOKE ALL ON public.profiles FROM PUBLIC;
REVOKE ALL ON public.jobs FROM PUBLIC;
REVOKE ALL ON public.applications FROM PUBLIC;
