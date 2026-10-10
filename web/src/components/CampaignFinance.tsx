import { formatCurrency, formatDate } from "@/lib/utils";
import type { CampaignFinanceDetail, CampaignFinanceSummary } from "@/lib/types";

export interface CampaignFinanceProps {
  finances: CampaignFinanceDetail[];
  summary?: CampaignFinanceSummary | null;
}

export function CampaignFinanceTable({ finances }: { finances: CampaignFinanceDetail[] }) {
  if (!finances || finances.length === 0) {
    return <p className="text-sm text-neutral-500">Nenhuma contribuição registrada.</p>;
  }

  const sorted = [...finances].sort((a, b) => b.amount - a.amount);

  return (
    <div className="overflow-x-auto">
      <table className="min-w-full divide-y divide-neutral-200">
        <thead>
          <tr>
            <th className="px-4 py-3 text-left text-xs font-medium text-neutral-500 uppercase">
              Data
            </th>
            <th className="px-4 py-3 text-left text-xs font-medium text-neutral-500 uppercase">
              Doador
            </th>
            <th className="px-4 py-3 text-left text-xs font-medium text-neutral-500 uppercase">
              Tipo
            </th>
            <th className="px-4 py-3 text-right text-xs font-medium text-neutral-500 uppercase">
              Valor
            </th>
          </tr>
        </thead>
        <tbody className="divide-y divide-neutral-200">
          {sorted.map((finance) => (
            <tr key={finance.id || `${finance.donor_name}-${finance.donation_date}`}>
              <td className="px-4 py-3 text-sm text-neutral-700">
                {formatDate(finance.donation_date)}
              </td>
              <td className="px-4 py-3 text-sm text-neutral-700">
                {finance.donor_name || "-"}
              </td>
              <td className="px-4 py-3 text-sm text-neutral-600">
                {finance.donor_type || "-"}
              </td>
              <td className="px-4 py-3 text-right text-sm font-medium text-neutral-900">
                {formatCurrency(finance.amount)}
              </td>
            </tr>
          ))}
        </tbody>
      </table>
    </div>
  );
}