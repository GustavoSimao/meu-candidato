import Link from "next/link";
import { fetchPoliticians } from "@/lib/api";
import { PoliticianCard } from "@/components/PoliticianCard";
import { Pagination } from "@/components/ui/pagination";
import { Input } from "@/components/ui/input";
import { Select } from "@/components/ui/select";
import { Button } from "@/components/ui/button";
import { Container, Header, Footer } from "@/components/layout";
import { UFS } from "@/lib/utils";
import type { PoliticianListDTO } from "@/lib/types";
import { ApiError } from "@/lib/api";

interface SearchParams {
  page?: string;
  per_page?: string;
  uf?: string;
  party?: string;
  name?: string;
}

export default async function PoliticiansPage({
  searchParams,
}: {
  searchParams: Promise<SearchParams>;
}) {
  const params = await searchParams;
  const page = Number(params.page) || 1;
  const perPage = Number(params.per_page) || 20;
  const uf = params.uf || "";
  const party = params.party || "";
  const name = params.name || "";

  let data: PoliticianListDTO | null = null;
  let error: string | null = null;

  try {
    data = await fetchPoliticians({
      page,
      per_page: perPage,
      uf: uf || undefined,
      party: party || undefined,
    });
  } catch (e) {
    if (e instanceof ApiError) {
      error = e.detail;
    } else {
      error = "Failed to load data";
    }
  }

  return (
    <div className="min-h-screen bg-gray-50">
      <Header />

      <main className="py-8">
        <Container>
          <div className="mb-6 flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
            <h1 className="text-2xl font-bold text-gray-900">Políticos</h1>

            <form method="get" action="/politicians" className="flex flex-wrap gap-3">
              {uf && <input type="hidden" name="uf" value={uf} />}
              {party && <input type="hidden" name="party" value={party} />}
              <input type="hidden" name="page" value="1" />
              <input type="hidden" name="per_page" value={perPage} />
              <div className="w-full sm:w-48">
                <Input type="text" name="name" placeholder="Buscar por nome" defaultValue={name} />
              </div>
              <div className="w-full sm:w-32">
                <Select name="uf" placeholder="UF" defaultValue={uf}>
                  <option value="">Todos</option>
                  {UFS.map((state) => (
                    <option key={state} value={state}>
                      {state}
                    </option>
                  ))}
                </Select>
              </div>
              <div className="w-full sm:w-32">
                <Input type="text" name="party" placeholder="Partido" defaultValue={party} />
              </div>
              <Button type="submit" variant="outline" size="sm">
                Filtrar
              </Button>
              <Link href="/politicians" className="text-sm text-gray-600 hover:text-blue-700">
                Limpar
              </Link>
            </form>
          </div>

          {error && <div className="mb-4 rounded-md bg-red-50 p-4 text-red-800">{error}</div>}

          {data ? (
            <>
              {data.total === 0 ? (
                <p className="text-gray-500">
                  Nenhum político encontrado com os filtros aplicados.
                </p>
              ) : (
                <div className="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
                  {data.items.map((politician) => (
                    <PoliticianCard key={politician.id} politician={politician} />
                  ))}
                </div>
              )}

              <div className="mt-6">
                <Pagination
                  currentPage={page}
                  totalPages={data.pages}
                  totalItems={data.total}
                  perPage={perPage}
                  baseUrl="/politicians"
                  searchParams={{ uf, party, name }}
                />
              </div>
            </>
          ) : (
            <div className="space-y-4">
              {[...Array(6)].map((_, i) => (
                <div key={i} className="h-32 animate-pulse rounded-lg bg-gray-200" />
              ))}
            </div>
          )}
        </Container>
      </main>

      <Footer />
    </div>
  );
}
