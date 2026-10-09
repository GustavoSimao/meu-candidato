import { render, screen } from "@testing-library/react";
import { BadgeDisplay } from "../BadgeDisplay";
import type { Badge } from "@/lib/types";

describe("BadgeDisplay", () => {
  it("renders empty state when no badges", () => {
    render(<BadgeDisplay badges={[]} />);
    expect(screen.getByText("Nenhum badge conquistado ainda")).toBeInTheDocument();
  });

  it("renders badge with correct label", () => {
    const badges: Badge[] = [
      {
        id: 1,
        politician_id: 1,
        badge_type: "ficha_limpa",
        earned_at: "2024-01-01",
        metadata: {},
      },
    ];
    render(<BadgeDisplay badges={badges} />);
    expect(screen.getByText("Ficha Limpa")).toBeInTheDocument();
  });

  it("renders unknown badge type with fallback label", () => {
    const badges: Badge[] = [
      {
        id: 2,
        politician_id: 1,
        badge_type: "custom_badge",
        earned_at: "2024-01-01",
        metadata: {},
      },
    ];
    render(<BadgeDisplay badges={badges} />);
    expect(screen.getByText("Custom Badge")).toBeInTheDocument();
  });

  it("respects max prop", () => {
    const badges: Badge[] = Array.from({ length: 15 }, (_, i) => ({
      id: i + 1,
      politician_id: 1,
      badge_type: "ficha_limpa",
      earned_at: "2024-01-01",
      metadata: {},
    }));
    render(<BadgeDisplay badges={badges} max={5} />);
    expect(screen.getByText("+10 outros")).toBeInTheDocument();
  });
});
