import Link from "next/link";
import { type ReactNode } from "react";

export function Container({
  children,
  className = "",
}: {
  children: ReactNode;
  className?: string;
}) {
  return <div className={`mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 ${className}`}>{children}</div>;
}

export function Header() {
  return (
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
  );
}

export function Footer() {
  return (
    <footer className="mt-auto border-t border-gray-200 bg-white py-6">
      <Container className="text-center text-sm text-gray-600">
        <p>&copy; {new Date().getFullYear()} Meu Candidato. Todos os direitos reservados.</p>
      </Container>
    </footer>
  );
}
