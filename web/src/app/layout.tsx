import type { Metadata } from "next";
import "./globals.css";

export const metadata: Metadata = {
  title: "Meu Candidato",
  description: "Transparência e dados abertos sobre políticos brasileiros",
};

export default function RootLayout({ children }: { children: React.ReactNode }) {
  return (
    <html lang="pt-BR">
      <body>{children}</body>
    </html>
  );
}
