import { formatDateRange } from "@/lib/utils";
import { SocialIcon } from "@/lib/icons";
import type { Mandate, PoliticianDetail } from "@/lib/types";

export interface MandateTimelineProps {
  mandates: Mandate[];
}

export function MandateTimeline({ mandates }: MandateTimelineProps) {
  if (!mandates || mandates.length === 0) {
    return <p className="text-sm text-neutral-500">Nenhum mandato registrado.</p>;
  }

  const sorted = [...mandates].sort(
    (a, b) => new Date(b.start_date).getTime() - new Date(a.start_date).getTime()
  );

  return (
    <div className="space-y-4">
      {sorted.map((m, idx) => (
        <div key={idx} className="relative pb-4 pl-6 last:pb-0">
          <div className="absolute top-0 left-0 h-full w-0.5 bg-neutral-200" />
          <div className="absolute top-0 left-[-4px] h-3 w-3 rounded-full bg-blue-600" />
          <div className="card">
            <div className="flex flex-wrap gap-2">
              <span className="font-semibold text-neutral-900">{m.house}</span>
              <span className="text-sm text-neutral-600">({m.role})</span>
              <span className="text-sm text-neutral-500">UF: {m.uf}</span>
              {m.is_suplente && <span className="text-xs font-medium text-neutral-500 uppercase">(Suplente)</span>}
            </div>
            <p className="mt-1 text-sm text-neutral-700">
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

  return (
    <div className="flex flex-wrap gap-2">
      {entries.map(([platform, url]) => {
        const key = platform.toLowerCase();
        const label = key.replace(/_url$/, "").replace(/_/g, " ");

        return url ? (
          <a
            key={platform}
            href={url as string}
            target="_blank"
            rel="noopener noreferrer"
            className="inline-flex items-center gap-1.5 rounded-md bg-neutral-100 px-3 py-1.5 text-sm text-neutral-700 hover:bg-neutral-200"
            aria-label={label}
            title={label}
          >
            <SocialIcon platform={platform} className="h-4 w-4 text-neutral-600" />
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
          <div key={field.label} className="rounded-lg border border-neutral-200 p-3">
            <dt className="text-xs font-medium text-neutral-500 uppercase">{field.label}</dt>
            <dd className="mt-1 text-sm text-neutral-900">{field.value}</dd>
          </div>
        ) : null
      )}
    </div>
  );
}

export function Biography({ bio }: { bio: string | null }) {
  if (!bio) {
    return <p className="text-sm text-neutral-500">Biografia não disponível.</p>;
  }
  return <p className="text-sm whitespace-pre-line text-neutral-700">{bio}</p>;
}
