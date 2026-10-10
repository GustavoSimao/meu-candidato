import Image from "next/image";
import Link from "next/link";
import { BadgeIcon, UserCircleIcon } from "@/lib/icons";
import { truncate } from "@/lib/utils";
import type { PoliticianListItem } from "@/lib/types";

export interface PoliticianCardProps {
  politician: PoliticianListItem;
}

export function PoliticianCard({ politician }: PoliticianCardProps) {
  return (
    <Link
      href={`/politicians/${politician.id}`}
      className="group/card block rounded-xl border border-neutral-200 bg-white p-6 shadow-sm transition-shadow duration-200 hover:shadow-md focus-visible:outline-2 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-600"
    >
      <div className="flex items-start gap-4">
        <div className="relative -mt-1 h-16 w-16 flex-shrink-0 overflow-hidden rounded-full bg-neutral-100">
          {politician.photo_url ? (
            <Image
              src={politician.photo_url}
              alt={politician.name}
              fill
              className="object-cover object-center transition-transform duration-200 group-hover/card:scale-105"
            />
          ) : (
            <UserCircleIcon
              className="h-full w-full rounded-full text-neutral-300"
              aria-label="Sem foto"
              data-testid="politician-avatar-fallback"
            />
          )}
        </div>
        <div className="min-w-0 flex-1">
          <h3 className="text-lg font-semibold text-neutral-900 group-hover/card:text-blue-700">
            {politician.name}
          </h3>
          <div className="mt-1 flex flex-wrap gap-2">
            <span className="inline-flex items-center rounded-md bg-blue-50 px-2.5 py-0.5 text-xs font-medium text-blue-800">
              {politician.party}
            </span>
            <span className="inline-flex items-center rounded-md bg-neutral-100 px-2.5 py-0.5 text-xs font-medium text-neutral-800">
              {politician.uf}
            </span>
            <span className="text-xs text-neutral-500">#{politician.number}</span>
          </div>
          {politician.badges.length > 0 && (
            <div className="mt-2 flex items-center gap-1.5">
              {politician.badges.slice(0, 3).map((b) => (
                <span key={b} className="text-xs" title={b} aria-label={b} data-testid="politician-badge">
                  <BadgeIcon badgeType={b} className="h-3.5 w-3.5 text-blue-700" />
                </span>
              ))}
              {politician.badges.length > 3 && (
                <span className="text-xs text-neutral-500">+{politician.badges.length - 3}</span>
              )}
            </div>
          )}
          {politician.biography && (
            <p className="mt-2 text-sm text-neutral-600">{truncate(politician.biography, 120)}</p>
          )}
        </div>
      </div>
    </Link>
  );
}
