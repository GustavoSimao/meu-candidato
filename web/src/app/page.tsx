import Link from "next/link";
import { Container } from "@/components/layout";
import { Input } from "@/components/ui/input";
import { Select } from "@/components/ui/select";
import { Button } from "@/components/ui/button";
import {
  BanknotesIcon,
  ChartBarIcon,
  ClipboardDocumentListIcon,
  DocumentTextIcon,
  TrophyIcon,
  UserGroupIcon,
} from "@/lib/icons";
import { UFS } from "@/lib/utils";

const features = [
  {
    title: "Políticos",
    description: "Busque políticos por nome, partido ou UF. Veja fotos, mandatos, redes sociais e biografia.",
    Icon: UserGroupIcon,
  },
  {
    title: "Despesas",
    description: "Transparência total sobre as despesas de cada parlamentar. Valores e fornecedores.",
    Icon: BanknotesIcon,
  },
  {
    title: "Votações",
    description: "Acompanhe como cada político vota em proposições legislativas.",
    Icon: ClipboardDocumentListIcon,
  },
  {
    title: "Proposições",
    description: "Veja as proposições criadas por cada parlamentar e seu status.",
    Icon: DocumentTextIcon,
  },
  {
    title: "Badges",
    description: "Conquistas baseadas no comportamento legislativo de cada político.",
    Icon: TrophyIcon,
  },
  {
    title: "Dashboard",
    description: "Crie seu dashboard personalizado seguindo políticos e coletando badges.",
    Icon: ChartBarIcon,
  },
];

export default function Home() {
  return (
    <div className="flex min-h-screen flex-col">
      <header className="border-b border-neutral-200 bg-white">
        <Container>
          <div className="flex h-14 items-center justify-between">
            <Link href="/" className="text-xl font-bold text-blue-700 sm:text-2xl">
              Meu Candidato
            </Link>
            <nav className="flex items-center space-x-1">
              <Link
                href="/politicians"
                className="rounded-md px-3 py-1.5 text-sm font-medium text-neutral-600 transition-colors hover:bg-neutral-100 hover:text-neutral-900"
              >
                Políticos
              </Link>
              <Link
                href="/dashboard"
                className="rounded-md px-3 py-1.5 text-sm font-medium text-neutral-600 transition-colors hover:bg-neutral-100 hover:text-neutral-900"
              >
                Dashboard
              </Link>
            </nav>
          </div>
        </Container>
      </header>

      <main className="flex-1">
        <section className="bg-gradient-to-b from-blue-700 to-blue-800 py-16">
          <Container className="text-center">
            <h1 className="text-4xl font-bold tracking-tight text-white sm:text-5xl">Meu Candidato</h1>
            <p className="mt-4 text-lg text-blue-100">
              Transparência e dados abertos sobre políticos brasileiros
            </p>
            <p className="mt-2 text-sm text-blue-200">
              Dados de despesas, votações, proposições e mandatos
            </p>

            <form
              method="get"
              action="/politicians"
              className="mx-auto mt-8 max-w-2xl space-y-4 sm:flex sm:items-end sm:gap-3 sm:space-y-0"
            >
              <div className="flex-1">
                <Input
                  type="text"
                  name="name"
                  placeholder="Buscar por nome ou partido"
                  className="w-full"
                />
              </div>
              <div className="w-full sm:w-32">
                <Select name="uf" placeholder="UF">
                  <option value="">Todos</option>
                  {UFS.map((state) => (
                    <option key={state} value={state}>
                      {state}
                    </option>
                  ))}
                </Select>
              </div>
              <Button type="submit" className="w-full sm:w-auto">
                Buscar
              </Button>
            </form>
          </Container>
        </section>

        <section className="py-12">
          <Container>
            <h2 className="mb-2 text-2xl font-bold text-neutral-900">Funcionalidades</h2>
            <p className="mb-8 text-neutral-500">
              Tudo o que você precisa para acompanhar a atuação dos políticos que você segue.
            </p>
            <div className="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-3">
              {features.map((feature) => (
                <div
                  key={feature.title}
                  className="card card-hover flex flex-col text-center"
                >
                  <div className="mb-4 flex h-12 w-12 items-center justify-center rounded-lg bg-blue-50 text-blue-700">
                    <feature.Icon className="h-6 w-6" />
                  </div>
                  <h3 className="mb-2 text-lg font-semibold text-neutral-900">{feature.title}</h3>
                  <p className="text-sm text-neutral-600">{feature.description}</p>
                </div>
              ))}
            </div>
          </Container>
        </section>

        <section className="bg-white py-12">
          <Container className="text-center">
            <h2 className="mb-4 text-2xl font-bold text-neutral-900">Pronto para começar?</h2>
            <p className="mb-6 text-neutral-500">
              Explore dados abertos da Câmara dos Deputados e do Senado Federal
            </p>
            <Link href="/politicians">
              <Button size="lg">Ver Políticos</Button>
            </Link>
          </Container>
        </section>
      </main>

      <footer className="border-t border-neutral-200 bg-white py-6">
        <Container className="text-center text-sm text-neutral-500">
          <p>&copy; {new Date().getFullYear()} Meu Candidato. Todos os direitos reservados.</p>
        </Container>
      </footer>
    </div>
  );
}
