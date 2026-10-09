import { BarChart, Bar, XAxis, YAxis, Tooltip, ResponsiveContainer } from "recharts";
import type { VoteDetail, VoteStats } from "@/lib/types";

export interface VoteChartProps {
  votes?: VoteDetail[];
  stats?: VoteStats;
}

const VOTE_COLORS: Record<string, string> = {
  favor: "#10b981",
  contra: "#ef4444",
  abstencao: "#f59e0b",
  ausente: "#6b7280",
  obstrucao: "#8b5cf6",
  art17: "#3b82f6",
  desconhecido: "#9ca3af",
};

const VOTE_LABELS: Record<string, string> = {
  favor: "Favor",
  contra: "Contra",
  abstencao: "Abstenção",
  ausente: "Ausente",
  obstrucao: "Obstrução",
  art17: "Art. 17",
  desconhecido: "Desconhecido",
};

export function VoteChart({ votes, stats }: VoteChartProps) {
  if (!stats && (!votes || votes.length === 0)) {
    return <p className="text-sm text-gray-500">Nenhum voto registrado.</p>;
  }

  const voteStats = stats || computeStats(votes || []);
  const chartData = Object.entries(voteStats)
    .filter(([, count]) => count > 0)
    .map(([key, count]) => ({
      name: VOTE_LABELS[key] || key,
      count: count,
    }));

  if (chartData.length === 0) {
    return <p className="text-sm text-gray-500">Nenhum voto registrado.</p>;
  }

  return (
    <div className="h-[300px] w-full">
      <ResponsiveContainer width="100%" height="100%">
        <BarChart data={chartData}>
          <XAxis dataKey="name" axisLine={false} tickLine={false} tick={{ fontSize: 12 }} />
          <YAxis axisLine={false} tickLine={false} tick={{ fontSize: 12 }} />
          <Tooltip
            content={({ active, payload }) => {
              if (!active || !payload?.length) return null;
              return (
                <div className="rounded-md bg-white px-3 py-2 text-sm shadow shadow-gray-200">
                  <p className="font-medium">{payload[0].payload.name}</p>
                  <p className="text-gray-600">{payload[0].value} votos</p>
                </div>
              );
            }}
          />
          <Bar dataKey="count" radius={[4, 4, 0, 0]} fill="#3b82f6" />
        </BarChart>
      </ResponsiveContainer>
    </div>
  );
}

export function VoteList({ votes }: { votes: VoteDetail[] }) {
  if (!votes || votes.length === 0) {
    return <p className="text-sm text-gray-500">Nenhum voto registrado.</p>;
  }

  return (
    <div className="space-y-2">
      {votes.slice(0, 20).map((vote) => (
        <div
          key={vote.id || `${vote.politician_id}-${vote.proposition_id}-${vote.session_date}`}
          className="flex items-center justify-between rounded-md border border-gray-200 px-3 py-2"
        >
          <div className="flex flex-col">
            <span className="text-sm font-medium text-gray-900">
              {vote.proposition_title || vote.proposition_id}
            </span>
            <span className="text-xs text-gray-500">
              {new Date(vote.session_date).toLocaleDateString("pt-BR")}
              {vote.session_number && ` • Sessão ${vote.session_number}`}
            </span>
          </div>
          <span
            className="text-xs font-medium uppercase"
            style={{ color: VOTE_COLORS[vote.vote_value] || "#6b7280" }}
          >
            {VOTE_LABELS[vote.vote_value] || vote.vote_value}
          </span>
        </div>
      ))}
    </div>
  );
}

function computeStats(votes: VoteDetail[]): VoteStats {
  const empty: VoteStats = {
    favor: 0,
    contra: 0,
    abstencao: 0,
    ausente: 0,
    obstrucao: 0,
    art17: 0,
    desconhecido: 0,
  };

  for (const v of votes) {
    const key = v.vote_value.toLowerCase() as keyof VoteStats;
    if (key in empty) {
      empty[key] += 1;
    } else {
      empty.desconhecido += 1;
    }
  }

  return empty;
}
