import { getBadgeIcon, getBadgeLabel } from "@/lib/utils";
import type { Badge } from "@/lib/types";

export interface BadgeDisplayProps {
  badges: Badge[];
  max?: number;
}

export function BadgeDisplay({ badges, max = 12 }: BadgeDisplayProps) {
  if (!badges || badges.length === 0) {
    return <div className="text-sm text-gray-500">Nenhum badge conquistado ainda</div>;
  }

  const displayBadges = badges.slice(0, max);
  const remaining = badges.length - displayBadges.length;

  return (
    <div className="flex flex-wrap gap-3">
      {displayBadges.map((badge) => (
        <div
          key={badge.id || badge.badge_type}
          className="flex items-center gap-2 rounded-lg bg-yellow-50 px-3 py-2"
          title={
            typeof badge.metadata?.description === "string"
              ? badge.metadata.description
              : getBadgeLabel(badge.badge_type)
          }
        >
          <span className="text-xl">{getBadgeIcon(badge.badge_type)}</span>
          <div>
            <span className="text-sm font-medium">{getBadgeLabel(badge.badge_type)}</span>
          </div>
        </div>
      ))}
      {remaining > 0 && <span className="text-sm text-gray-500">+{remaining} outros</span>}
    </div>
  );
}
