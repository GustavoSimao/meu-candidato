import { render, screen } from "@testing-library/react";
import { PoliticianCard } from "../PoliticianCard";
import type { PoliticianListItem } from "@/lib/types";

const mockPolitician: PoliticianListItem = {
  id: 1,
  name: "João Silva",
  party: "PTB",
  uf: "SP",
  number: 42,
  external_id: 12345,
  cpf: "123.456.789-00",
  email: "joao@example.com",
  office_address: "Rua Teste, 123",
  office_phone: "(11) 99999-9999",
  biography: "Político experiente na área de transparência.",
  social_media: null,
  education: "Engenharia",
  photo_url: null,
  badges: ["ficha_limpa", "presenca_alta"],
};

describe("PoliticianCard", () => {
  it("renders politician name, party, and UF", () => {
    render(<PoliticianCard politician={mockPolitician} />);
    expect(screen.getByText("João Silva")).toBeInTheDocument();
    expect(screen.getByText("PTB")).toBeInTheDocument();
    expect(screen.getByText("SP")).toBeInTheDocument();
    expect(screen.getByText("#42")).toBeInTheDocument();
  });

  it("renders badge icons", () => {
    render(<PoliticianCard politician={mockPolitician} />);
    const badgeElements = screen.getAllByTitle("ficha_limpa");
    expect(badgeElements).toHaveLength(1);
  });

  it("truncates long biography", () => {
    const longBioPolitician = {
      ...mockPolitician,
      biography: "A".repeat(200),
    };
    render(<PoliticianCard politician={longBioPolitician} />);
    const truncatedBio = screen.getByText((content) => /\.{3}$/.test(content));
    expect(truncatedBio).toBeInTheDocument();
  });

  it("renders fallback avatar when no photo", () => {
    render(<PoliticianCard politician={mockPolitician} />);
    const avatar = screen.getByText("🧑");
    expect(avatar).toBeInTheDocument();
  });
});
