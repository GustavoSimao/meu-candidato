import { formatDate, truncate } from "@/lib/utils";
import type { Proposition } from "@/lib/types";

export interface PropositionListProps {
  propositions: Proposition[];
}

export function PropositionList({ propositions }: PropositionListProps) {
  if (!propositions || propositions.length === 0) {
    return <p className="text-sm text-gray-500">Nenhuma proposição registrada.</p>;
  }

  return (
    <div className="space-y-3">
      {propositions.map((prop) => (
        <div
          key={prop.id || prop.external_id}
          className="rounded-lg border border-gray-200 p-4 hover:bg-gray-50"
        >
          <div className="flex flex-wrap items-start justify-between gap-2">
            <h4 className="text-sm font-semibold text-gray-900">{prop.title}</h4>
            <span className="text-xs text-gray-500 uppercase">{prop.type}</span>
          </div>
          <div className="mt-2 flex flex-wrap gap-2 text-xs text-gray-600">
            <span>Casa: {prop.house}</span>
            <span>Data: {formatDate(prop.presentation_date)}</span>
            {prop.status && <span>Status: {prop.status}</span>}
          </div>
          {prop.summary && (
            <p className="mt-2 text-sm text-gray-600">{truncate(prop.summary, 150)}</p>
          )}
          {prop.url && (
            <a
              href={prop.url}
              target="_blank"
              rel="noopener noreferrer"
              className="mt-1 inline-block text-sm text-blue-600 hover:underline"
            >
              Ver documento
            </a>
          )}
        </div>
      ))}
    </div>
  );
}
