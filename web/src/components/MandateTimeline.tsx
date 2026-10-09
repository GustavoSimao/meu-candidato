import { formatDateRange } from "@/lib/utils";
import type { Mandate, PoliticianDetail } from "@/lib/types";

export interface MandateTimelineProps {
  mandates: Mandate[];
}

export function MandateTimeline({ mandates }: MandateTimelineProps) {
  if (!mandates || mandates.length === 0) {
    return <p className="text-sm text-gray-500">Nenhum mandato registrado.</p>;
  }

  const sorted = [...mandates].sort(
    (a, b) => new Date(b.start_date).getTime() - new Date(a.start_date).getTime()
  );

  return (
    <div className="space-y-4">
      {sorted.map((m, idx) => (
        <div key={idx} className="relative pb-4 pl-6 last:pb-0">
          <div className="absolute top-0 left-0 h-full w-0.5 bg-gray-200" />
          <div className="absolute top-0 left-[-4px] h-3 w-3 rounded-full bg-blue-600" />
          <div className="rounded-lg border border-gray-200 bg-gray-50 p-4">
            <div className="flex flex-wrap gap-2">
              <span className="font-semibold text-gray-900">{m.house}</span>
              <span className="text-sm text-gray-600">({m.role})</span>
              <span className="text-sm text-gray-500">UF: {m.uf}</span>
              {m.is_suplente && <span className="text-xs text-gray-500 uppercase">(Suplente)</span>}
            </div>
            <p className="mt-1 text-sm text-gray-700">
              {formatDateRange(m.start_date, m.end_date)}
            </p>
          </div>
        </div>
      ))}
    </div>
  );
}

export interface SocialLinksProps {
  socialMedia: Record<string, unknown> | string | null;
}

export function SocialLinks({ socialMedia }: SocialLinksProps) {
  if (!socialMedia || typeof socialMedia !== "object") {
    return null;
  }

  const social = socialMedia as Record<string, string>;
  const entries = Object.entries(social).filter(([, val]) => val);

  if (entries.length === 0) return null;

  const icons: Record<string, string> = {
    twitter: "𝕏",
    twitter_url: "𝕏",
    facebook: "f",
    facebook_url: "f",
    instagram: "📷",
    instagram_url: "📷",
    linkedin: "💼",
    linkedin_url: "💼",
    youtube: "▶",
    youtube_url: "▶",
    tiktok: "🎵",
    tiktok_url: "🎵",
    site: "🌐",
    site_url: "🌐",
  };

  return (
    <div className="flex flex-wrap gap-3">
      {entries.map(([platform, url]) => {
        const key = platform.toLowerCase();
        const icon = icons[key] || "🔗";
        const label = key.replace(/_url$/, "").replace(/_/g, " ");

        return url ? (
          <a
            key={platform}
            href={url as string}
            target="_blank"
            rel="noopener noreferrer"
            className="flex items-center gap-1.5 rounded-md bg-gray-100 px-3 py-1.5 text-sm text-gray-700 hover:bg-gray-200"
            aria-label={label}
            title={label}
          >
            <span>{icon}</span>
            <span className="capitalize">{label}</span>
          </a>
        ) : null;
      })}
    </div>
  );
}

export interface InfoGridProps {
  politician: PoliticianDetail;
}

export function InfoGrid({ politician }: InfoGridProps) {
  const fields: Array<{ label: string; value: string | null }> = [
    { label: "Nome", value: politician.name },
    { label: "Partido", value: politician.party },
    { label: "UF", value: politician.uf },
    { label: "Número", value: politician.number?.toString() || null },
    { label: "Email", value: politician.email },
    { label: "Endereço", value: politician.office_address },
    { label: "Telefone", value: politician.office_phone },
    { label: "Educação", value: politician.education },
    { label: "CPF", value: politician.cpf },
  ];

  return (
    <div className="grid grid-cols-1 gap-3 sm:grid-cols-2">
      {fields.map((field) =>
        field.value ? (
          <div key={field.label} className="rounded-lg border border-gray-200 p-3">
            <dt className="text-xs font-medium text-gray-500 uppercase">{field.label}</dt>
            <dd className="mt-1 text-sm text-gray-900">{field.value}</dd>
          </div>
        ) : null
      )}
    </div>
  );
}

export function Biography({ bio }: { bio: string | null }) {
  if (!bio) {
    return <p className="text-sm text-gray-500">Biografia não disponível.</p>;
  }
  return <p className="text-sm whitespace-pre-line text-gray-700">{bio}</p>;
}
