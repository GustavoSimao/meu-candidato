import { formatCurrency, formatDate, truncate } from "@/lib/utils";
import type { ExpenseDetail } from "@/lib/types";

export interface ExpenseTableProps {
  expenses: ExpenseDetail[];
}

export function ExpenseTable({ expenses }: ExpenseTableProps) {
  if (!expenses || expenses.length === 0) {
    return <p className="text-sm text-gray-500">Nenhuma despesa registrada.</p>;
  }

  return (
    <div className="overflow-x-auto">
      <table className="min-w-full divide-y divide-gray-200">
        <thead>
          <tr>
            <th className="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">
              Data
            </th>
            <th className="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">
              Tipo
            </th>
            <th className="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">
              Valor
            </th>
            <th className="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">
              Descrição
            </th>
            <th className="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">
              Fornecedor
            </th>
          </tr>
        </thead>
        <tbody className="divide-y divide-gray-200">
          {expenses.map((expense) => (
            <tr key={expense.id || `${expense.expense_date}-${expense.amount}`}>
              <td className="px-4 py-3 text-sm text-gray-700">
                {formatDate(expense.expense_date)}
              </td>
              <td className="px-4 py-3 text-sm text-gray-700">{expense.expense_type}</td>
              <td className="px-4 py-3 text-sm font-medium text-gray-900">
                {formatCurrency(expense.amount)}
              </td>
              <td className="px-4 py-3 text-sm text-gray-600">
                {truncate(expense.description, 80)}
              </td>
              <td className="px-4 py-3 text-sm text-gray-600">{truncate(expense.provider, 50)}</td>
            </tr>
          ))}
        </tbody>
      </table>
    </div>
  );
}
