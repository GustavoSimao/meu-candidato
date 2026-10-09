export interface PaginatedResponse<T> {
  items: T[];
  total: number;
  page: number;
  per_page: number;
  pages: number;
}

export interface Mandate {
  house: string;
  role: string;
  uf: string;
  start_date: string;
  end_date: string | null;
  is_suplente: boolean;
}

export interface PoliticianBase {
  name: string;
  party: string;
  uf: string;
  number: number;
  external_id: number | null;
  cpf: string | null;
  email: string | null;
  office_address: string | null;
  office_phone: string | null;
  biography: string | null;
  social_media: Record<string, unknown> | null;
  education: string | null;
  photo_url: string | null;
}

export interface PoliticianListItem extends PoliticianBase {
  id: number;
  badges: string[];
}

export interface PoliticianDetail extends PoliticianBase {
  id: number;
  mandates: Mandate[];
  badges: string[];
}

export type PoliticianListDTO = PaginatedResponse<PoliticianListItem>;
export type PoliticianDetailDTO = PoliticianDetail;

export interface Proposition {
  id: number | null;
  external_id: string;
  politician_id: number;
  type: string;
  title: string;
  house: string;
  summary: string | null;
  status: string | null;
  presentation_date: string | null;
  url: string | null;
  votes?: Vote[];
}

export type PropositionWithVotes = Proposition;

export type PropositionListDTO = PaginatedResponse<Proposition>;
export type PropositionDetailDTO = PropositionWithVotes;

export interface Vote {
  id: number | null;
  politician_id: number;
  proposition_id: number;
  session_date: string;
  vote_value: string;
  session_number: string | null;
}

export interface VoteDetail extends Vote {
  proposition_title?: string | null;
  proposition_type?: string | null;
}

export type VoteListDTO = PaginatedResponse<VoteDetail>;

export interface VoteStats {
  favor: number;
  contra: number;
  abstencao: number;
  ausente: number;
  obstrucao: number;
  art17: number;
  desconhecido: number;
}

export interface Expense {
  id: number | null;
  politician_id: number;
  expense_type: string;
  amount: number;
  expense_date: string;
  year: number;
  month: number;
  description: string | null;
  provider: string | null;
  document_number: string | null;
  document_url: string | null;
}

export interface ExpenseDetail extends Expense {
  amount_reais: number;
}

export type ExpenseListDTO = PaginatedResponse<ExpenseDetail>;

export interface ExpenseSummary {
  total_amount: number;
  by_type: Record<string, Record<string, number>>;
  by_month: Record<string, Record<string, number>>;
}

export interface CampaignFinance {
  id: number | null;
  politician_id: number;
  election_year: number;
  election_type: string;
  donor_type: string;
  donor_name: string | null;
  donor_cpf_cnpj: string | null;
  amount: number;
  donation_date: string;
  receipt_url: string | null;
}

export interface CampaignFinanceDetail extends CampaignFinance {
  amount_reais: number;
}

export type CampaignFinanceListDTO = PaginatedResponse<CampaignFinanceDetail>;

export interface CampaignFinanceSummary {
  total_amount: number;
  by_donor_type: Record<string, Record<string, number>>;
  by_year: Record<string, Record<string, number>>;
  top_donors: Array<Record<string, number | string>>;
}

export interface Follow {
  id: number | null;
  user_id: string;
  politician_id: number;
  created_at: string;
}

export type FollowListDTO = PaginatedResponse<Follow>;

export interface Badge {
  id: number | null;
  politician_id: number;
  badge_type: string;
  earned_at: string;
  metadata: Record<string, unknown>;
}

export type BadgeListDTO = PaginatedResponse<Badge>;

export interface BadgeRule {
  id: number | null;
  badge_type: string;
  name: string;
  description: string;
  condition: string;
  threshold: number;
  is_active: boolean;
}

export type BadgeRuleListDTO = PaginatedResponse<BadgeRule>;

export interface Dashboard {
  followed_politicians: number[];
  badges: Badge[];
  recent_activity: Record<string, unknown>[];
}

export type BadgeType =
  "ficha_limpa" | "presenca_alta" | "legislador_ativo" | "bom_digitador" | string;
