/// <reference lib="deno.ns" />
// @deno-types="https://esm.sh/@supabase/supabase-js@2.47.0?dts"
import { createClient, SupabaseClient } from "https://esm.sh/@supabase/supabase-js@2.47.0";

const supabaseUrl = Deno.env.get("SUPABASE_URL") ?? "";
const supabaseAnonKey = Deno.env.get("SUPABASE_ANON_KEY") ?? "";
const allowedStatuses = new Set(["Pending", "Interview Scheduled", "Rejected", "Hired"]);

const json = (status: number, body: unknown) =>
  new Response(JSON.stringify(body), {
    status,
    headers: {
      "Content-Type": "application/json",
      "Access-Control-Allow-Origin": "*",
      "Access-Control-Allow-Headers": "authorization, x-client-info, apikey, content-type",
      "Access-Control-Allow-Methods": "GET,POST,PATCH,OPTIONS",
    },
  });

const badRequest = (message: string) => json(400, { error: message });
const unauthorized = (message = "Unauthorized") => json(401, { error: message });
const forbidden = (message = "Forbidden") => json(403, { error: message });

async function getUserAndProfile(supabase: SupabaseClient) {
  const { data: authData, error: authError } = await supabase.auth.getUser();
  if (authError || !authData.user) return { error: unauthorized("Sign in required") };
  const user = authData.user;
  const { data: profile, error: profileError } = await supabase
    .from("profiles")
    .select("id, role, name, company")
    .eq("id", user.id)
    .single();
  if (profileError || !profile) return { error: forbidden("Profile missing") };
  return { user, profile };
}

Deno.serve(async (req) => {
  if (req.method === "OPTIONS") return json(204, {});
  const authHeader = req.headers.get("Authorization") ?? "";
  const supabase = createClient(supabaseUrl, supabaseAnonKey, {
    global: { headers: { Authorization: authHeader } },
  });

  if (req.method === "POST") {
    const auth = await getUserAndProfile(supabase);
    if ("error" in auth) return auth.error as Response;
    const { user, profile } = auth;
    if (profile.role !== "Seeker") return forbidden("Seeker role required");
    const body = await req.json().catch(() => null) as Record<string, unknown> | null;
    if (!body) return badRequest("Invalid JSON body");
    const jobId = Number(body.job_id);
    if (!jobId) return badRequest("job_id is required");
    const cover = (body.cover as string) ?? "";
    const telegram = (body.telegram as string) ?? "";
    const portfolio = (body.portfolio as string) ?? "";

    const { data: job, error: jobError } = await supabase.from("jobs").select("id").eq("id", jobId).single();
    if (jobError || !job) return json(404, { error: "Job not found" });

    const { data, error } = await supabase
      .from("applications")
      .insert({ job_id: jobId, seeker_id: user.id, cover, telegram, portfolio })
      .select("*")
      .single();
    if (error) {
      const duplicate = error.code === "23505";
      return json(400, { error: duplicate ? "You already applied to this job." : error.message });
    }
    return json(201, { data });
  }

  if (req.method === "GET") {
    const auth = await getUserAndProfile(supabase);
    if ("error" in auth) return auth.error as Response;
    const { user, profile } = auth;
    const url = new URL(req.url);
    const mine = url.searchParams.get("mine") === "1" || url.searchParams.get("mine") === "true";
    const jobIdParam = url.searchParams.get("job_id");

    if (mine) {
      const { data, error } = await supabase
        .from("applications")
        .select("id, job_id, status, history, created_at, cover, telegram, portfolio, jobs(id, title, company, location, category, type, posted_at, salary)")
        .eq("seeker_id", user.id)
        .order("created_at", { ascending: false });
      if (error) return json(400, { error: error.message });
      return json(200, { data });
    }

    if (jobIdParam) {
      if (profile.role !== "Employer") return forbidden("Employer role required");
      const jobId = Number(jobIdParam);
      if (!jobId) return badRequest("job_id is required");
      const { data: job, error: jobError } = await supabase
        .from("jobs")
        .select("owner_id")
        .eq("id", jobId)
        .single();
      if (jobError || !job) return json(404, { error: "Job not found" });
      if (job.owner_id !== user.id) return forbidden("Not your job");

      const { data, error } = await supabase
        .from("applications")
        .select("id, status, history, created_at, cover, telegram, portfolio, seeker:profiles(id, name, company, title, city, bio, role)")
        .eq("job_id", jobId)
        .order("created_at", { ascending: false });
      if (error) return json(400, { error: error.message });
      return json(200, { data });
    }

    return badRequest("Specify mine=true or job_id");
  }

  if (req.method === "PATCH") {
    const auth = await getUserAndProfile(supabase);
    if ("error" in auth) return auth.error as Response;
    const { user, profile } = auth;
    if (profile.role !== "Employer") return forbidden("Employer role required");
    const body = await req.json().catch(() => null) as Record<string, unknown> | null;
    if (!body) return badRequest("Invalid JSON body");
    const applicationId = Number(body.application_id);
    const status = body.status as string;
    if (!applicationId) return badRequest("application_id is required");
    if (!allowedStatuses.has(status)) return badRequest("Invalid status");

    const { data: app, error: appError } = await supabase
      .from("applications")
      .select("id, job_id, history")
      .eq("id", applicationId)
      .single();
    if (appError || !app) return json(404, { error: "Application not found" });

    const { data: job, error: jobError } = await supabase
      .from("jobs")
      .select("owner_id")
      .eq("id", app.job_id)
      .single();
    if (jobError || !job) return json(404, { error: "Job not found" });
    if (job.owner_id !== user.id) return forbidden("Not your job");

    const history = Array.isArray(app.history) ? [...app.history] : [];
    history.push({ status, at: new Date().toISOString(), actor: user.id });

    const { data, error } = await supabase
      .from("applications")
      .update({ status, history })
      .eq("id", applicationId)
      .select("*")
      .single();
    if (error) return json(400, { error: error.message });
    return json(200, { data });
  }

  return json(405, { error: "Method not allowed" });
});
