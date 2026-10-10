"use client";

import Image from "next/image";
import Link from "next/link";
import { useState, useEffect } from "react";
import {
  fetchPolitician,
  fetchExpenses,
  fetchPropositions,
  fetchVotes,
  fetchVoteStats,
  fetchBadges,
  fetchCampaigns,
  fetchCampaignSummary,
} from "@/lib/api";
import { MandateTimeline, SocialLinks, InfoGrid, Biography } from "@/components/MandateTimeline";
import { BadgeDisplay } from "@/components/BadgeDisplay";
import { ExpenseTable } from "@/components/ExpenseTable";
import { PropositionList } from "@/components/PropositionList";
import { VoteChart, VoteList } from "@/components/VoteChart";
import { CampaignFinanceTable } from "@/components/CampaignFinance";
import { Container, Header, Footer } from "@/components/layout";
import { UserCircleIcon } from "@/lib/icons";
import { formatCurrency } from "@/lib/utils";
import type {
  PoliticianDetailDTO,
  ExpenseListDTO,
  PropositionListDTO,
  VoteListDTO,
  VoteStats,
  Badge,
  CampaignFinanceListDTO,
  CampaignFinanceSummary,
} from "@/lib/types";
import { ApiError } from "@/lib/api";

interface PoliticianDetailPageProps {
  params: { id: string };
}

export default function PoliticianDetailPage({ params }: PoliticianDetailPageProps) {
  const { id } = params;
  const [politician, setPolitician] = useState<PoliticianDetailDTO | null>(null);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);
  const [activeTab, setActiveTab] = useState("bio");
  const [expensesData, setExpensesData] = useState<ExpenseListDTO | null>(null);
  const [propositionsData, setPropositionsData] = useState<PropositionListDTO | null>(null);
  const [votesData, setVotesData] = useState<{ votes: VoteListDTO; stats: VoteStats } | null>(null);
  const [badges, setBadges] = useState<Badge[]>([]);
  const [subError, setSubError] = useState<string | null>(null);
  const [expensesLoaded, setExpensesLoaded] = useState(false);
  const [propositionsLoaded, setPropositionsLoaded] = useState(false);
  const [votesLoaded, setVotesLoaded] = useState(false);
  const [badgesLoaded, setBadgesLoaded] = useState(false);
  const [campaignFinanceData, setCampaignFinanceData] = useState<CampaignFinanceListDTO | null>(null);
  const [campaignSummary, setCampaignSummary] = useState<CampaignFinanceSummary | null>(null);
  const [campaignLoaded, setCampaignLoaded] = useState(false);

  useEffect(() => {
    let cancelled = false;
    fetchPolitician(Number(id))
      .then((data) => {
        if (!cancelled) {
          setPolitician(data);
          setError(null);
        }
      })
      .catch((e: unknown) => {
        if (!cancelled) {
          if (e instanceof ApiError) {
            setError(e.detail);
          } else {
            setError("Falha ao carregar dados");
          }
        }
      })
      .finally(() => {
        if (!cancelled) setLoading(false);
      });
    return () => {
      cancelled = true;
    };
  }, [id]);

  useEffect(() => {
    if (politician && activeTab === "badges" && !badgesLoaded) {
      let cancelled = false;
      let didSetLoaded = false;
      fetchBadges(politician.id)
        .then((data) => {
          if (!cancelled) {
            if (!didSetLoaded) {
              setBadgesLoaded(true);
              didSetLoaded = true;
            }
            setBadges(data);
            setSubError(null);
          }
        })
        .catch((e: unknown) => {
          if (!cancelled) {
            if (!didSetLoaded) {
              setBadgesLoaded(true);
              didSetLoaded = true;
            }
            if (e instanceof ApiError) {
              setSubError(e.detail);
            } else {
              setSubError("Falha ao carregar badges");
            }
          }
        });
      return () => {
        cancelled = true;
      };
    }
  }, [politician, activeTab, badgesLoaded]);

  const loadExpenses = async () => {
    if (!politician?.id || expensesLoaded) return;
    setExpensesLoaded(true);
    setSubError(null);
    try {
      const data = await fetchExpenses({ politician_id: politician.id });
      setExpensesData(data);
    } catch (e: unknown) {
      if (e instanceof ApiError) {
        setSubError(e.detail);
      } else {
        setSubError("Falha ao carregar despesas");
      }
    }
  };

  const loadPropositions = async () => {
    if (!politician?.id || propositionsLoaded) return;
    setPropositionsLoaded(true);
    setSubError(null);
    try {
      const data = await fetchPropositions({ politician_id: politician.id });
      setPropositionsData(data);
    } catch (e: unknown) {
      if (e instanceof ApiError) {
        setSubError(e.detail);
      } else {
        setSubError("Falha ao carregar proposições");
      }
    }
  };

  const loadVotes = async () => {
    if (!politician?.id || votesLoaded) return;
    setVotesLoaded(true);
    setSubError(null);
    try {
      const votes = await fetchVotes({ politician_id: politician.id });
      const stats = await fetchVoteStats(politician.id);
      setVotesData({ votes, stats });
    } catch (e: unknown) {
      if (e instanceof ApiError) {
        setSubError(e.detail);
      } else {
        setSubError("Falha ao carregar votos");
      }
    }
  };

  const loadCampaignFinance = async () => {
    if (!politician?.id || campaignLoaded) return;
    setCampaignLoaded(true);
    setSubError(null);
    try {
      const [financeData, summaryData] = await Promise.all([
        fetchCampaigns({ politician_id: politician.id }),
        fetchCampaignSummary(politician.id),
      ]);
      setCampaignFinanceData(financeData);
      setCampaignSummary(summaryData);
    } catch (e: unknown) {
      if (e instanceof ApiError) {
        setSubError(e.detail);
      } else {
        setSubError("Falha ao carregar finanças de campanha");
      }
    }
  };

  if (loading) {
    return (
      <div className="min-h-screen bg-neutral-50">
        <Header />
        <main className="py-8">
          <Container>
            <div className="animate-pulse space-y-6">
              <div className="h-8 w-48 rounded bg-neutral-200" />
              <div className="flex items-start gap-6">
                <div className="h-32 w-32 rounded-full bg-neutral-200" />
                <div className="flex-1 space-y-3">
                  <div className="h-8 w-3/4 rounded bg-neutral-200" />
                  <div className="h-4 w-1/2 rounded bg-neutral-200" />
                </div>
              </div>
            </div>
          </Container>
        </main>
        <Footer />
      </div>
    );
  }

  if (error) {
    return (
      <div className="min-h-screen bg-neutral-50">
        <Header />
        <main className="py-8">
          <Container>
            <div className="rounded-md bg-red-50 p-4 text-red-800">{error}</div>
            <Link href="/politicians" className="mt-4 inline-block text-sm text-blue-700 hover:text-blue-800 hover:underline">
              Voltar para a lista
            </Link>
          </Container>
        </main>
        <Footer />
      </div>
    );
  }

  if (!politician) return null;

  const tabs = [
    { id: "bio", label: "Biografia" },
    { id: "mandates", label: "Mandatos" },
    { id: "expenses", label: "Despesas" },
    { id: "campaign", label: "Finanças de Campanha" },
    { id: "propositions", label: "Proposições" },
    { id: "votes", label: "Votos" },
    { id: "badges", label: "Badges" },
  ];

  return (
    <div className="min-h-screen bg-neutral-50">
      <Header />

      <main className="py-8">
        <Container>
            <Link
              href="/politicians"
              className="mb-4 inline-block text-sm text-blue-700 hover:text-blue-800 hover:underline"
            >
              Voltar
            </Link>

          <div className="mb-6 flex items-start gap-6">
            <div className="relative h-32 w-32 flex-shrink-0 overflow-hidden rounded-full bg-neutral-100">
               {politician.photo_url ? (
                <Image
                  src={politician.photo_url}
                  alt={politician.name}
                  fill
                  className="object-cover"
                />
              ) : (
                <UserCircleIcon className="h-full w-full rounded-full text-neutral-300" aria-label="Sem foto" data-testid="politician-avatar-fallback" />
              )}
            </div>
            <div>
              <h1 className="text-2xl font-bold text-neutral-900">{politician.name}</h1>
              <div className="mt-1 flex flex-wrap gap-2">
                <span className="inline-flex items-center rounded-md bg-blue-50 px-2.5 py-0.5 text-sm font-medium text-blue-800">
                  {politician.party}
                </span>
                <span className="inline-flex items-center rounded-md bg-neutral-100 px-2.5 py-0.5 text-sm font-medium text-neutral-800">
                  UF: {politician.uf}
                </span>
                <span className="text-sm text-neutral-500">#{politician.number}</span>
              </div>
              {politician.badges.length > 0 && (
                <BadgeDisplay
                  badges={politician.badges.map((b) => ({
                    id: null,
                    politician_id: politician.id,
                    badge_type: b,
                    earned_at: new Date().toISOString().split("T")[0],
                    metadata: {},
                  }))}
                />
              )}
            </div>
          </div>

          {politician.social_media && (
            <div className="mb-6">
              <SocialLinks socialMedia={politician.social_media} />
            </div>
          )}

          <InfoGrid politician={politician} />

          <div className="mt-8 border-b border-neutral-200">
            <nav className="-mb-px flex flex-wrap gap-x-6 gap-y-2">
              {tabs.map((tab) => (
                <button
                  key={tab.id}
                   onClick={() => {
                     setActiveTab(tab.id);
                     if (tab.id === "expenses") void loadExpenses();
                     if (tab.id === "campaign") void loadCampaignFinance();
                     if (tab.id === "propositions") void loadPropositions();
                     if (tab.id === "votes") void loadVotes();
                   }}
                  className={`border-b-2 px-1 py-2 text-sm font-medium ${
                    activeTab === tab.id
                      ? "border-blue-600 text-blue-700"
                      : "border-transparent text-neutral-600 hover:text-neutral-700"
                  }`}
                >
                  {tab.label}
                </button>
              ))}
            </nav>
          </div>

          <div className="py-6">
            {activeTab === "bio" && <Biography bio={politician.biography} />}

            {activeTab === "mandates" && <MandateTimeline mandates={politician.mandates} />}

            {activeTab === "expenses" && (
              <>
                {subError && (
                  <div className="mb-4 rounded-md bg-red-50 p-4 text-red-800">{subError}</div>
                )}
                {expensesData && expensesData.total > 0 && (
                  <div className="mb-4 rounded-lg bg-yellow-50 p-4">
                    <span className="text-sm font-medium text-neutral-700">
                      Total de despesas:{" "}
                      {formatCurrency(
                        expensesData.items.reduce((sum, e) => sum + (e.amount || 0), 0)
                      )}
                    </span>
                  </div>
                )}
                <ExpenseTable expenses={expensesData?.items || []} />
              </>
            )}

            {activeTab === "campaign" && (
              <>
                {subError && (
                  <div className="mb-4 rounded-md bg-red-50 p-4 text-red-800">{subError}</div>
                )}
                {campaignSummary && campaignSummary.total_amount > 0 && (
                  <div className="mb-4 rounded-lg bg-green-50 p-4">
                    <span className="text-sm font-medium text-neutral-700">
                      Total arrecadado:{" "}
                      {formatCurrency(campaignSummary.total_amount)}
                    </span>
                  </div>
                )}
                <CampaignFinanceTable finances={campaignFinanceData?.items || []} />
              </>
            )}

            {activeTab === "propositions" && (
              <>
                {subError && (
                  <div className="mb-4 rounded-md bg-red-50 p-4 text-red-800">{subError}</div>
                )}
                <PropositionList propositions={propositionsData?.items || []} />
              </>
            )}

            {activeTab === "votes" && (
              <>
                {subError && (
                  <div className="mb-4 rounded-md bg-red-50 p-4 text-red-800">{subError}</div>
                )}
                <div className="space-y-6">
                  {votesData?.stats && <VoteChart stats={votesData.stats} />}
                  <VoteList votes={votesData?.votes?.items || []} />
                </div>
              </>
            )}

            {activeTab === "badges" && (
              <>
                {subError && (
                  <div className="mb-4 rounded-md bg-red-50 p-4 text-red-800">{subError}</div>
                )}
                <BadgeDisplay badges={badges} />
              </>
            )}
          </div>
        </Container>
      </main>

      <Footer />
    </div>
  );
}
