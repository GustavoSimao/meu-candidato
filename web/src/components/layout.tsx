"use client";

import Link from "next/link";
import { usePathname } from "next/navigation";
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

const navItems = [
  { href: "/politicians", label: "Políticos" },
  { href: "/dashboard", label: "Dashboard" },
];

export function Header() {
  const pathname = usePathname();
  return (
    <header className="sticky top-0 z-40 border-b border-neutral-200 bg-white/80 backdrop-blur supports-[backdrop-filter]:bg-white/50">
      <Container>
        <div className="flex h-14 items-center justify-between">
          <Link href="/" className="text-xl font-bold text-blue-700 sm:text-2xl">
            Meu Candidato
          </Link>
          <nav className="flex items-center space-x-1">
            {navItems.map((item) => {
              const isActive = pathname === item.href;
              return (
                <Link
                  key={item.href}
                  href={item.href}
                  className={`rounded-md px-3 py-1.5 text-sm font-medium transition-colors ${
                    isActive
                      ? "text-blue-700"
                      : "text-neutral-600 hover:bg-neutral-100 hover:text-neutral-900"
                  }`}
                >
                  {item.label}
                </Link>
              );
            })}
          </nav>
        </div>
      </Container>
    </header>
  );
}

export function Footer() {
  return (
    <footer className="mt-auto border-t border-neutral-200 bg-white py-6">
      <Container className="text-center text-sm text-neutral-500">
        <p>&copy; {new Date().getFullYear()} Meu Candidato. Todos os direitos reservados.</p>
      </Container>
    </footer>
  );
}
