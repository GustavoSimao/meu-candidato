import Link from "next/link";
import { Container } from "@/components/layout";
import { Input } from "@/components/ui/input";
import { Select } from "@/components/ui/select";
import { Button } from "@/components/ui/button";
import { UFS } from "@/lib/utils";

export default function Home() {
  return (
    <div className="min-h-screen bg-gray-50">
      <header className="border-b border-gray-200 bg-white">
        <Container>
          <div className="flex h-16 items-center justify-between">
            <Link href="/" className="text-2xl font-bold text-blue-700">
              Meu Candidato
            </Link>
            <nav className="flex items-center space-x-6">
              <Link href="/politicians" className="text-gray-700 hover:text-blue-700">
                Políticos
              </Link>
              <Link href="/dashboard" className="text-gray-700 hover:text-blue-700">
                Dashboard
              </Link>
            </nav>
          </div>
        </Container>
      </header>

      <main className="flex-1">
        <section className="bg-gradient-to-b from-blue-700 to-blue-800 py-16">
          <Container className="text-center">
            <h1 className="text-4xl font-bold text-white sm:text-5xl">Meu Candidato</h1>
            <p className="mt-4 text-lg text-blue-100">
              Transparência e dados abertos sobre políticos brasileiros
            </p>
            <p className="mt-2 text-sm text-blue-200">
              Dados de despesas, votações, proposições e mandatos
            </p>

            <form
              method="get"
              action="/politicians"
              className="mx-auto mt-8 max-w-2xl space-y-4 sm:flex sm:gap-3 sm:space-y-0"
            >
              <div className="flex-1">
                <Input
                  type="text"
                  name="name"
                  placeholder="Buscar por nome ou partido"
                  className="w-full"
                />
              </div>
              <div className="sm:w-32">
                <Select name="uf" placeholder="UF">
                  <option value="">Todos</option>
                  {UFS.map((state) => (
                    <option key={state} value={state}>
                      {state}
                    </option>
                  ))}
                </Select>
              </div>
              <Button type="submit" className="sm:w-auto">
                Buscar
              </Button>
            </form>
          </Container>
        </section>

        <section className="py-12">
          <Container>
            <h2 className="mb-6 text-2xl font-bold text-gray-900">Funcionalidades</h2>
            <div className="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-3">
              <div className="rounded-lg border border-gray-200 bg-white p-6">
                <div className="mb-3 text-3xl">🧑‍💼</div>
                <h3 className="font-semibold text-gray-900">Políticos</h3>
                <p className="mt-2 text-sm text-gray-600">
                  Busque políticos por nome, partido ou UF. Veja fotos, mandatos, redes sociais e
                  biografia.
                </p>
              </div>
              <div className="rounded-lg border border-gray-200 bg-white p-6">
                <div className="mb-3 text-3xl">💰</div>
                <h3 className="font-semibold text-gray-900">Despesas</h3>
                <p className="mt-2 text-sm text-gray-600">
                  Transparência total sobre as despesas de cada parlamentar. Valores e fornecedores.
                </p>
              </div>
              <div className="rounded-lg border border-gray-200 bg-white p-6">
                <div className="mb-3 text-3xl">📝</div>
                <h3 className="font-semibold text-gray-900">Votações</h3>
                <p className="mt-2 text-sm text-gray-600">
                  Acompanhe como cada político vota em proposições legislativas.
                </p>
              </div>
              <div className="rounded-lg border border-gray-200 bg-white p-6">
                <div className="mb-3 text-3xl">📜</div>
                <h3 className="font-semibold text-gray-900">Proposições</h3>
                <p className="mt-2 text-sm text-gray-600">
                  Veja as proposições criadas por cada parlamentar e seu status.
                </p>
              </div>
              <div className="rounded-lg border border-gray-200 bg-white p-6">
                <div className="mb-3 text-3xl">🏅</div>
                <h3 className="font-semibold text-gray-900">Badges</h3>
                <p className="mt-2 text-sm text-gray-600">
                  Conquistas baseadas no comportamento legislativo de cada político.
                </p>
              </div>
              <div className="rounded-lg border border-gray-200 bg-white p-6">
                <div className="mb-3 text-3xl">📊</div>
                <h3 className="font-semibold text-gray-900">Dashboard</h3>
                <p className="mt-2 text-sm text-gray-600">
                  Crie seu dashboard personalizado seguindo políticos e coletando badges.
                </p>
              </div>
            </div>
          </Container>
        </section>

        <section className="bg-white py-12">
          <Container className="text-center">
            <h2 className="mb-4 text-2xl font-bold text-gray-900">Pronto para começar?</h2>
            <p className="mb-6 text-gray-600">
              Explore dados abertos da Câmara dos Deputados e do Senado Federal
            </p>
            <Link href="/politicians">
              <Button size="lg">Ver Políticos</Button>
            </Link>
          </Container>
        </section>
      </main>

      <footer className="border-t border-gray-200 bg-white py-6">
        <Container className="text-center text-sm text-gray-600">
          <p>&copy; {new Date().getFullYear()} Meu Candidato. Todos os direitos reservados.</p>
        </Container>
      </footer>
    </div>
  );
}
