import { clsx, type ClassValue } from "clsx";
import { twMerge } from "tailwind-merge";

export function cn(...inputs: ClassValue[]) {
  return twMerge(clsx(inputs));
}

export function formatCurrency(amount: number): string {
  return new Intl.NumberFormat("pt-BR", {
    style: "currency",
    currency: "BRL",
  }).format(amount / 100);
}

export function formatDate(dateString: string | null): string {
  if (!dateString) return "-";
  const date = new Date(dateString);
  return new Intl.DateTimeFormat("pt-BR", {
    day: "2-digit",
    month: "2-digit",
    year: "numeric",
  }).format(date);
}

export function formatDateRange(start: string, end: string | null): string {
  const startStr = new Date(start).toLocaleDateString("pt-BR", {
    month: "short",
    year: "numeric",
  });
  const endStr = end
    ? new Date(end).toLocaleDateString("pt-BR", {
        month: "short",
        year: "numeric",
      })
    : "atualmente";
  return `${startStr} - ${endStr}`;
}

export function getBadgeLabel(badge_type: string): string {
  const labels: Record<string, string> = {
    ficha_limpa: "Ficha Limpa",
    presenca_alta: "Presença Alta",
    legislador_ativo: "Legislador Ativo",
  };
  return (
    labels[badge_type] || badge_type.replace(/_/g, " ").replace(/\b\w/g, (l) => l.toUpperCase())
  );
}

export function getBadgeIcon(badge_type: string): string {
  const icons: Record<string, string> = {
    ficha_limpa: "🧹",
    presenca_alta: "✓",
    legislador_ativo: "📝",
  };
  return icons[badge_type] || "🏅";
}

export const UFS = [
  "AC",
  "AL",
  "AP",
  "AM",
  "BA",
  "CE",
  "DF",
  "ES",
  "GO",
  "MA",
  "MT",
  "MS",
  "MG",
  "PA",
  "PB",
  "PR",
  "PE",
  "PI",
  "RJ",
  "RN",
  "RS",
  "RO",
  "RR",
  "SC",
  "SP",
  "SE",
  "TO",
];

export function truncate(text: string | null, maxLength: number): string {
  if (!text) return "";
  if (text.length <= maxLength) return text;
  return text.slice(0, maxLength - 3) + "...";
}
