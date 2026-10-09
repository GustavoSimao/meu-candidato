import { render, screen } from "@testing-library/react";
import { ExpenseTable } from "../ExpenseTable";
import type { ExpenseDetail } from "@/lib/types";

const mockExpenses: ExpenseDetail[] = [
  {
    id: 1,
    politician_id: 1,
    expense_type: "Alimentação",
    amount: 5000,
    expense_date: "2024-01-15",
    year: 2024,
    month: 1,
    description: "Refeições oficiais",
    provider: "Restaurante X",
    document_number: "12345",
    document_url: null,
    amount_reais: 50.0,
  },
  {
    id: 2,
    politician_id: 1,
    expense_type: "Transporte",
    amount: 3000,
    expense_date: "2024-02-10",
    year: 2024,
    month: 2,
    description: "Passagens áreas",
    provider: "Companhia Aérea",
    document_number: "67890",
    document_url: null,
    amount_reais: 30.0,
  },
];

describe("ExpenseTable", () => {
  it("renders empty state when no expenses", () => {
    render(<ExpenseTable expenses={[]} />);
    expect(screen.getByText("Nenhuma despesa registrada.")).toBeInTheDocument();
  });

  it("renders expense rows with correct data", () => {
    render(<ExpenseTable expenses={mockExpenses} />);
    expect(screen.getByText("Alimentação")).toBeInTheDocument();
    expect(screen.getByText("Transporte")).toBeInTheDocument();
    expect(screen.getByText("Refeições oficiais")).toBeInTheDocument();
    expect(screen.getByText("Restaurante X")).toBeInTheDocument();
  });
});
