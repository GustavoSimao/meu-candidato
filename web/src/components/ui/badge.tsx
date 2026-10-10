export type BadgeVariant = "default" | "success" | "warning" | "danger" | "secondary";

export interface BadgeProps {
  children: React.ReactNode;
  variant?: BadgeVariant;
  className?: string;
}

export function Badge({ children, variant = "default", className = "" }: BadgeProps) {
  const baseClasses = "inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium";
  const variantClasses: Record<BadgeVariant, string> = {
    default: "bg-blue-100 text-blue-800",
    success: "bg-green-100 text-green-800",
    warning: "bg-yellow-100 text-yellow-800",
    danger: "bg-red-100 text-red-800",
    secondary: "bg-neutral-100 text-neutral-800",
  };

  return (
    <span className={`${baseClasses} ${variantClasses[variant]} ${className}`}>{children}</span>
  );
}
