import Image from "next/image";
import Link from "next/link";
import { getBadgeIcon, truncate } from "@/lib/utils";
import type { PoliticianListItem } from "@/lib/types";

export interface PoliticianCardProps {
  politician: PoliticianListItem;
}

export function PoliticianCard({ politician }: PoliticianCardProps) {
  return (
    <Link
      href={`/politicians/${politician.id}`}
      className="group block rounded-lg border border-gray-200 bg-white p-6 shadow-sm transition-shadow hover:shadow-md"
    >
      <div className="flex items-start gap-4">
        <div className="relative h-20 w-20 flex-shrink-0 overflow-hidden rounded-full bg-gray-100">
          {politician.photo_url ? (
            <Image
              src={politician.photo_url}
              alt={politician.name}
              fill
              className="object-cover object-center transition-transform group-hover:scale-105"
            />
          ) : (
            <div className="flex h-full w-full items-center justify-center text-2xl">🧑</div>
          )}
        </div>
        <div className="min-w-0 flex-1">
          <h3 className="text-lg font-semibold text-gray-900 group-hover:text-blue-700">
            {politician.name}
          </h3>
          <div className="mt-1 flex flex-wrap gap-2">
            <span className="inline-flex items-center rounded-md bg-blue-50 px-2.5 py-0.5 text-xs font-medium text-blue-800">
              {politician.party}
            </span>
            <span className="inline-flex items-center rounded-md bg-gray-100 px-2.5 py-0.5 text-xs font-medium text-gray-800">
              {politician.uf}
            </span>
            <span className="text-xs text-gray-500">#{politician.number}</span>
          </div>
          {politician.badges.length > 0 && (
            <div className="mt-2 flex items-center gap-1">
              {politician.badges.slice(0, 3).map((b) => (
                <span key={b} className="text-xs" title={b} aria-label={b}>
                  {getBadgeIcon(b)}
                </span>
              ))}
              {politician.badges.length > 3 && (
                <span className="text-xs text-gray-500">+{politician.badges.length - 3}</span>
              )}
            </div>
          )}
          {politician.biography && (
            <p className="mt-2 text-sm text-gray-600">{truncate(politician.biography, 120)}</p>
          )}
        </div>
      </div>
    </Link>
  );
}
