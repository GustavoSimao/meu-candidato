import { BadgeIcon } from "@/lib/icons";
import { getBadgeLabel } from "@/lib/utils";
import type { Badge } from "@/lib/types";

export interface BadgeDisplayProps {
  badges: Badge[];
  max?: number;
}

export function BadgeDisplay({ badges, max = 12 }: BadgeDisplayProps) {
  if (!badges || badges.length === 0) {
    return <div className="text-sm text-neutral-500">Nenhum badge conquistado ainda</div>;
  }

  const displayBadges = badges.slice(0, max);
  const remaining = badges.length - displayBadges.length;

  return (
    <div className="flex flex-wrap gap-2">
      {displayBadges.map((badge) => (
        <span
          key={badge.id || badge.badge_type}
          className="badge-chip"
          title={
            typeof badge.metadata?.description === "string"
              ? badge.metadata.description
              : getBadgeLabel(badge.badge_type)
          }
        >
          <BadgeIcon badgeType={badge.badge_type} className="h-3.5 w-3.5" />
          <span className="truncate">{getBadgeLabel(badge.badge_type)}</span>
        </span>
      ))}
      {remaining > 0 && <span className="text-sm text-neutral-500">+{remaining} outros</span>}
    </div>
  );
}
