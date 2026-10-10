/* eslint-disable react-hooks/set-state-in-effect */
"use client";

import { useState, useEffect } from "react";
import { fetchDashboard, fetchPolitician } from "@/lib/api";
import { BadgeDisplay } from "@/components/BadgeDisplay";
import { PoliticianCard } from "@/components/PoliticianCard";
import { Container, Header, Footer } from "@/components/layout";
import { Input } from "@/components/ui/input";
import { Button } from "@/components/ui/button";
import Link from "next/link";
import { ApiError } from "@/lib/api";
import type { Dashboard, PoliticianListItem } from "@/lib/types";

export default function DashboardPage() {
  const [userId, setUserId] = useState("");
  const [showUserIdPrompt, setShowUserIdPrompt] = useState(false);
  const [dashboard, setDashboard] = useState<Dashboard | null>(null);
  const [followedPoliticians, setFollowedPoliticians] = useState<PoliticianListItem[]>([]);
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState<string | null>(null);

  const handleFetchDashboard = async (uid: string) => {
    if (!uid) return;
    setLoading(true);
    setError(null);
    try {
      localStorage.setItem("meu_candidato_user_id", uid);
      setShowUserIdPrompt(false);
      setUserId(uid);
      const data = await fetchDashboard(uid);
      setDashboard(data);

      const politicians: PoliticianListItem[] = [];
      for (const pid of data.followed_politicians) {
        try {
          const pol = await fetchPolitician(pid);
          politicians.push({
            id: pol.id,
            name: pol.name,
            party: pol.party,
            uf: pol.uf,
            number: pol.number,
            external_id: pol.external_id,
            cpf: pol.cpf,
            email: pol.email,
            office_address: pol.office_address,
            office_phone: pol.office_phone,
            biography: pol.biography,
            social_media: pol.social_media,
            education: pol.education,
            photo_url: pol.photo_url,
            badges: pol.badges || [],
          });
        } catch {
          // skip politicians that can't be fetched
        }
      }
      setFollowedPoliticians(politicians);
    } catch (e) {
      if (e instanceof ApiError) {
        setError(e.detail);
      } else {
        setError("Falha ao carregar dashboard");
      }
    } finally {
      setLoading(false);
    }
  };

  useEffect(() => {
    const storedUserId = localStorage.getItem("meu_candidato_user_id");
    if (storedUserId) {
      void handleFetchDashboard(storedUserId);
    } else {
      setShowUserIdPrompt(true);
    }
  }, []);

  if (showUserIdPrompt) {
    return (
      <div className="min-h-screen bg-neutral-50">
        <Header />
        <main className="py-16">
          <Container>
            <div className="mx-auto max-w-md rounded-lg border border-neutral-200 bg-white p-8 shadow">
              <h1 className="mb-4 text-2xl font-bold text-neutral-900">Dashboard</h1>
              <p className="mb-4 text-neutral-600">
                Digite seu ID de usuário para acessar seu dashboard personalizado.
              </p>
              <form
                onSubmit={(e) => {
                  e.preventDefault();
                  void handleFetchDashboard(userId);
                }}
                className="space-y-4"
              >
                <Input
                  label="User ID"
                  placeholder="ex: user_123"
                  value={userId}
                  onChange={(e) => setUserId(e.target.value)}
                  required
                />
                <Button type="submit" className="w-full">
                  Acessar Dashboard
                </Button>
              </form>
              <div className="mt-4">
                <Link href="/politicians" className="text-sm text-blue-600 hover:underline">
                  Ver todos os políticos
                </Link>
              </div>
            </div>
          </Container>
        </main>
        <Footer />
      </div>
    );
  }

  return (
    <div className="min-h-screen bg-neutral-50">
      <Header />

      <main className="py-8">
        <Container>
          <div className="mb-4 flex items-center justify-between">
            <h1 className="text-2xl font-bold text-neutral-900">Dashboard</h1>
            <button
              onClick={() => setShowUserIdPrompt(true)}
              className="text-sm text-blue-600 hover:underline"
            >
              Trocar de usuário
            </button>
          </div>

          {error && <div className="mb-4 rounded-md bg-red-50 p-4 text-red-800">{error}</div>}

          {loading ? (
            <div className="space-y-4">
              <div className="h-6 w-48 animate-pulse rounded bg-neutral-200" />
              <div className="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
                {[...Array(3)].map((_, i) => (
                  <div key={i} className="h-32 animate-pulse rounded-lg bg-neutral-200" />
                ))}
              </div>
            </div>
          ) : (
            <>
              <div className="mb-8">
                <h2 className="mb-4 text-lg font-semibold text-neutral-900">
                  Políticos seguidos ({followedPoliticians.length})
                </h2>
                {followedPoliticians.length > 0 ? (
                  <div className="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
                    {followedPoliticians.map((p) => (
                      <PoliticianCard key={p.id} politician={p} />
                    ))}
                  </div>
                ) : (
                  <p className="text-sm text-neutral-500">Você não segue nenhum político.</p>
                )}
              </div>

              <div>
                <h2 className="mb-4 text-lg font-semibold text-neutral-900">Badges conquistados</h2>
                {dashboard?.badges && dashboard.badges.length > 0 ? (
                  <BadgeDisplay badges={dashboard.badges} />
                ) : (
                  <p className="text-sm text-neutral-500">Nenhum badge conquistado ainda.</p>
                )}
              </div>
            </>
          )}
        </Container>
      </main>

      <Footer />
    </div>
  );
}
