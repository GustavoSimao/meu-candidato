import type { ComponentType, SVGProps } from "react";
import {
  AtSymbolIcon,
  BanknotesIcon,
  BriefcaseIcon,
  CheckCircleIcon,
  ChartBarIcon,
  ClipboardDocumentListIcon,
  DocumentTextIcon,
  GlobeAltIcon,
  LinkIcon,
  MegaphoneIcon,
  MusicalNoteIcon,
  PhotoIcon,
  PresentationChartBarIcon,
  ShieldCheckIcon,
  TrophyIcon,
  UserCircleIcon,
  UserGroupIcon,
  VideoCameraIcon,
} from "@heroicons/react/24/outline";

export {
  UserCircleIcon,
  UserGroupIcon,
  BanknotesIcon,
  ClipboardDocumentListIcon,
  DocumentTextIcon,
  ChartBarIcon,
  PresentationChartBarIcon,
  ShieldCheckIcon,
  CheckCircleIcon,
  MegaphoneIcon,
  TrophyIcon,
  PhotoIcon,
  BriefcaseIcon,
  VideoCameraIcon,
  MusicalNoteIcon,
  GlobeAltIcon,
  LinkIcon,
  AtSymbolIcon,
};

type Icon = ComponentType<SVGProps<SVGSVGElement>>;

const badgeIconMap: Record<string, Icon> = {
  ficha_limpa: ShieldCheckIcon,
  presenca_alta: CheckCircleIcon,
  legislador_ativo: MegaphoneIcon,
};

export interface BadgeIconProps extends SVGProps<SVGSVGElement> {
  badgeType: string;
}

export function BadgeIcon({ badgeType, className = "h-4 w-4", ...props }: BadgeIconProps) {
  const Icon = badgeIconMap[badgeType] || TrophyIcon;
  return <Icon className={className} {...props} />;
}

const socialIconMap: Record<string, Icon> = {
  twitter: AtSymbolIcon,
  twitter_url: AtSymbolIcon,
  facebook: AtSymbolIcon,
  facebook_url: AtSymbolIcon,
  instagram: PhotoIcon,
  instagram_url: PhotoIcon,
  linkedin: BriefcaseIcon,
  linkedin_url: BriefcaseIcon,
  youtube: VideoCameraIcon,
  youtube_url: VideoCameraIcon,
  tiktok: MusicalNoteIcon,
  tiktok_url: MusicalNoteIcon,
  site: GlobeAltIcon,
  site_url: GlobeAltIcon,
};

export interface SocialIconProps extends SVGProps<SVGSVGElement> {
  platform: string;
}

export function SocialIcon({ platform, className = "h-4 w-4", ...props }: SocialIconProps) {
  const key = platform.toLowerCase().replace(/_url$/, "");
  const Icon = socialIconMap[key] || LinkIcon;
  return <Icon className={className} {...props} />;
}
