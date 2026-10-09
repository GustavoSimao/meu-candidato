import type {
  Badge,
  CampaignFinanceListDTO,
  CampaignFinanceSummary,
  Dashboard,
  ExpenseListDTO,
  ExpenseSummary,
  PoliticianDetailDTO,
  PoliticianListDTO,
  PropositionDetailDTO,
  PropositionListDTO,
  VoteListDTO,
  VoteStats,
} from "@/lib/types";

export class ApiError extends Error {
  constructor(
    public status: number,
    public detail: string
  ) {
    super(detail);
    this.name = "ApiError";
  }
}

const API_BASE = process.env.NEXT_PUBLIC_API_URL || "http://localhost:8000/api/v1";

async function fetchAPI<T>(path: string): Promise<T> {
  const res = await fetch(`${API_BASE}${path}`, {
    headers: { "Content-Type": "application/json" },
    next: { revalidate: 60 },
  });

  if (!res.ok) {
    let detail = "Unknown error";
    try {
      const err = await res.json();
      detail = err.detail || err.message || detail;
    } catch {
      detail = res.statusText || detail;
    }
    throw new ApiError(res.status, detail);
  }

  return res.json();
}

function buildParams(params: Record<string, string | number | undefined>): string {
  const searchParams = new URLSearchParams();
  for (const [key, value] of Object.entries(params)) {
    if (value !== undefined && value !== "") {
      searchParams.set(key, String(value));
    }
  }
  return searchParams.toString();
}

export async function fetchPoliticians(params?: {
  page?: number;
  per_page?: number;
  uf?: string;
  party?: string;
  name?: string;
}): Promise<PoliticianListDTO> {
  const query = buildParams({
    page: params?.page,
    per_page: params?.per_page,
    uf: params?.uf,
    party: params?.party,
    name: params?.name,
  });
  return fetchAPI<PoliticianListDTO>(`/politicians${query ? `?${query}` : ""}`);
}

export async function fetchPolitician(id: number): Promise<PoliticianDetailDTO> {
  return fetchAPI<PoliticianDetailDTO>(`/politicians/${id}`);
}

export async function fetchPropositions(params?: {
  politician_id?: number;
  page?: number;
  per_page?: number;
}): Promise<PropositionListDTO> {
  const query = buildParams({
    politician_id: params?.politician_id,
    page: params?.page,
    per_page: params?.per_page,
  });
  return fetchAPI<PropositionListDTO>(`/legislative/propositions${query ? `?${query}` : ""}`);
}

export async function fetchProposition(id: number): Promise<PropositionDetailDTO> {
  return fetchAPI<PropositionDetailDTO>(`/legislative/propositions/${id}`);
}

export async function fetchVotes(params?: {
  politician_id?: number;
  page?: number;
  per_page?: number;
}): Promise<VoteListDTO> {
  const query = buildParams({
    politician_id: params?.politician_id,
    page: params?.page,
    per_page: params?.per_page,
  });
  return fetchAPI<VoteListDTO>(`/legislative/votes${query ? `?${query}` : ""}`);
}

export async function fetchVoteStats(politician_id?: number): Promise<VoteStats> {
  const query = buildParams({ politician_id });
  return fetchAPI<VoteStats>(`/legislative/votes/stats${query ? `?${query}` : ""}`);
}

export async function fetchExpenses(params?: {
  politician_id?: number;
  year?: number;
  page?: number;
  per_page?: number;
}): Promise<ExpenseListDTO> {
  const query = buildParams({
    politician_id: params?.politician_id,
    year: params?.year,
    page: params?.page,
    per_page: params?.per_page,
  });
  return fetchAPI<ExpenseListDTO>(`/financial/expenses${query ? `?${query}` : ""}`);
}

export async function fetchExpenseSummary(
  politician_id: number,
  year?: number
): Promise<ExpenseSummary> {
  const query = buildParams({ politician_id, year });
  return fetchAPI<ExpenseSummary>(`/financial/expenses/summary${query ? `?${query}` : ""}`);
}

export async function fetchCampaigns(params?: {
  politician_id?: number;
  election_year?: number;
  page?: number;
  per_page?: number;
}): Promise<CampaignFinanceListDTO> {
  const query = buildParams({
    politician_id: params?.politician_id,
    election_year: params?.election_year,
    page: params?.page,
    per_page: params?.per_page,
  });
  return fetchAPI<CampaignFinanceListDTO>(`/financial/campaigns${query ? `?${query}` : ""}`);
}

export async function fetchCampaignSummary(
  politician_id: number,
  election_year?: number
): Promise<CampaignFinanceSummary> {
  const query = buildParams({ politician_id, election_year });
  return fetchAPI<CampaignFinanceSummary>(`/financial/campaigns/summary${query ? `?${query}` : ""}`);
}

export async function fetchDashboard(user_id: string): Promise<Dashboard> {
  return fetchAPI<Dashboard>(`/engagement/dashboard?user_id=${user_id}`);
}

export async function fetchBadges(politician_id: number): Promise<Badge[]> {
  return fetchAPI<Badge[]>("/engagement/badges/politician/" + politician_id);
}

export async function fetchHealth(): Promise<{ status: string; environment: string }> {
  return fetchAPI<{ status: string; environment: string }>("/health");
}
