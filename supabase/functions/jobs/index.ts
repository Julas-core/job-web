/// <reference lib="deno.ns" />
/// <reference lib="dom" />
import { serve } from "https://deno.land/std@0.177.1/http/server.ts";
import { createClient, SupabaseClient } from "https://esm.sh/@supabase/supabase-js@2.47.0";

const supabaseUrl = Deno.env.get("SUPABASE_URL") ?? "";
const supabaseAnonKey = Deno.env.get("SUPABASE_ANON_KEY") ?? "";

const json = (status: number, body: unknown) =>
  new Response(JSON.stringify(body), {
    status,
    headers: {
      "Content-Type": "application/json",
      "Access-Control-Allow-Origin": "*",
      "Access-Control-Allow-Headers": "authorization, x-client-info, apikey, content-type",
      "Access-Control-Allow-Methods": "GET,POST,PUT,PATCH,DELETE,OPTIONS",
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

serve(async (req: Request) => {
  if (req.method === "OPTIONS") return json(204, {});
  const authHeader = req.headers.get("Authorization") ?? "";
  const supabase = createClient(supabaseUrl, supabaseAnonKey, {
    global: { headers: { Authorization: authHeader } },
  });

  const url = new URL(req.url);
  const idParam = url.searchParams.get("id");

  if (req.method === "GET") {
    if (idParam) {
      const { data, error } = await supabase.from("jobs").select("*").eq("id", Number(idParam)).single();
      if (error) return json(404, { error: "Job not found" });
      return json(200, { data });
    }
    const q = url.searchParams.get("q") ?? "";
    const company = url.searchParams.get("company") ?? "";
    const category = url.searchParams.get("category") ?? "";
    const type = url.searchParams.get("type") ?? "";
    const limit = Math.min(Number(url.searchParams.get("limit") ?? "20"), 50);
    const offset = Number(url.searchParams.get("offset") ?? "0");

    let query = supabase.from("jobs").select("*").order("posted_at", { ascending: false }).limit(limit).offset(offset);
    if (q) query = query.or(`title.ilike.%${q}%,description.ilike.%${q}%`);
    if (company) query = query.ilike("company", `%${company}%`);
    if (category) query = query.ilike("category", `%${category}%`);
    if (type) query = query.ilike("type", `%${type}%`);

    const { data, error } = await query;
    if (error) return json(400, { error: error.message });
    return json(200, { data });
  }

  if (req.method === "POST") {
    const auth = await getUserAndProfile(supabase);
    if ("error" in auth) return auth.error as Response;
    const { user, profile } = auth;
    if (profile.role !== "Employer") return forbidden("Employer role required");
    const body = await req.json().catch(() => null) as Record<string, unknown> | null;
    if (!body) return badRequest("Invalid JSON body");
    const { title, description, salary = "", location = "", category = "", type = "", company = profile.company || "" } = body as Record<string, string>;
    if (!title || !description) return badRequest("title and description are required");

    const { data, error } = await supabase.from("jobs").insert({
      title,
      description,
      salary,
      location,
      category,
      type,
      company,
      owner_id: user.id,
    }).select("*").single();
    if (error) return json(400, { error: error.message });
    return json(201, { data });
  }

  if (req.method === "PUT" || req.method === "PATCH") {
    const auth = await getUserAndProfile(supabase);
    if ("error" in auth) return auth.error as Response;
    const { user, profile } = auth;
    if (profile.role !== "Employer") return forbidden("Employer role required");
    const body = await req.json().catch(() => null) as Record<string, unknown> | null;
    if (!body) return badRequest("Invalid JSON body");
    const jobId = Number(body.id);
    if (!jobId) return badRequest("id is required");

    const { data: existing, error: jobError } = await supabase.from("jobs").select("owner_id").eq("id", jobId).single();
    if (jobError || !existing) return json(404, { error: "Job not found" });
    if (existing.owner_id !== user.id) return forbidden("Not your job");

    const updates: Record<string, unknown> = {};
    ["title", "description", "salary", "location", "category", "type", "company"].forEach((key) => {
      if (body[key] !== undefined) updates[key] = body[key];
    });
    if (Object.keys(updates).length === 0) return badRequest("No fields to update");

    const { data, error } = await supabase.from("jobs").update(updates).eq("id", jobId).select("*").single();
    if (error) return json(400, { error: error.message });
    return json(200, { data });
  }

  if (req.method === "DELETE") {
    const auth = await getUserAndProfile(supabase);
    if ("error" in auth) return auth.error as Response;
    const { user, profile } = auth;
    if (profile.role !== "Employer") return forbidden("Employer role required");
    const body = await req.json().catch(() => null) as Record<string, unknown> | null;
    const jobId = Number(body?.id ?? idParam);
    if (!jobId) return badRequest("id is required");

    const { data: existing, error: jobError } = await supabase.from("jobs").select("owner_id").eq("id", jobId).single();
    if (jobError || !existing) return json(404, { error: "Job not found" });
    if (existing.owner_id !== user.id) return forbidden("Not your job");

    const { error } = await supabase.from("jobs").delete().eq("id", jobId);
    if (error) return json(400, { error: error.message });
    return json(200, { data: true });
  }

  return json(405, { error: "Method not allowed" });
});
