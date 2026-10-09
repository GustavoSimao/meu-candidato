import Link from "next/link";
import { cn } from "@/lib/utils";

export interface PaginationProps {
  currentPage: number;
  totalPages: number;
  totalItems: number;
  perPage: number;
  baseUrl?: string;
  searchParams?: Record<string, string>;
}

export function Pagination({
  currentPage,
  totalPages,
  totalItems,
  perPage,
  baseUrl = "",
  searchParams = {},
}: PaginationProps) {
  const getPageUrl = (page: number) => {
    const params = new URLSearchParams(searchParams);
    params.set("page", String(page));
    params.set("per_page", String(perPage));
    const query = params.toString();
    return query ? `${baseUrl}?${query}` : baseUrl || "/";
  };

  const pages = [];
  const maxPagesToShow = 5;

  let startPage = Math.max(1, currentPage - Math.floor(maxPagesToShow / 2));
  const endPage = Math.min(totalPages, startPage + maxPagesToShow - 1);
  startPage = Math.max(1, endPage - maxPagesToShow + 1);

  for (let i = startPage; i <= endPage; i++) {
    pages.push(i);
  }

  if (totalPages <= 1) return null;

  return (
    <nav className="flex items-center justify-between border-t border-gray-200 px-4 sm:px-0">
      <div className="mt-2 mb-4 sm:sm:mt-0 sm:flex sm:justify-between">
        <p className="text-sm text-gray-700">
          Mostrando <span className="font-medium">{(currentPage - 1) * perPage + 1}</span> a{" "}
          <span className="font-medium">{Math.min(currentPage * perPage, totalItems)}</span> de{" "}
          <span className="font-medium">{totalItems}</span> resultados
        </p>
      </div>
      <div className="flex items-center space-x-1">
        {currentPage > 1 && (
          <Link
            href={getPageUrl(currentPage - 1)}
            className="rounded-md px-3 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50"
          >
            Anterior
          </Link>
        )}
        {pages.map((page) => (
          <Link
            key={page}
            href={getPageUrl(page)}
            className={cn(
              "rounded-md px-3 py-2 text-sm font-medium",
              page === currentPage ? "bg-blue-600 text-white" : "text-gray-700 hover:bg-gray-50"
            )}
          >
            {page}
          </Link>
        ))}
        {currentPage < totalPages && (
          <Link
            href={getPageUrl(currentPage + 1)}
            className="rounded-md px-3 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50"
          >
            Próxima
          </Link>
        )}
      </div>
    </nav>
  );
}
