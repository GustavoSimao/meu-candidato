import { type InputHTMLAttributes, useId } from "react";
import { cn } from "@/lib/utils";

export interface InputProps extends InputHTMLAttributes<HTMLInputElement> {
  label?: string;
  error?: string;
}

export function Input({ label, error, className, id, ...props }: InputProps) {
  const generatedId = useId();
  const inputId = id || `input-${generatedId}`;

  return (
    <div className="w-full">
      {label && (
        <label htmlFor={inputId} className="mb-1 block text-sm font-medium text-neutral-700">
          {label}
        </label>
      )}
      <input
        id={inputId}
        aria-invalid={error ? "true" : undefined}
        className={cn(
          "w-full rounded-md border border-neutral-300 px-3 py-2 text-sm text-neutral-900",
          "placeholder:text-neutral-400",
          "focus-visible:border-blue-600 focus-visible:ring-2 focus-visible:ring-blue-500 focus-visible:outline-none",
          error && "border-red-500 focus-visible:ring-red-500",
          className
        )}
        {...props}
      />
      {error && <p className="mt-1 text-sm text-red-600">{error}</p>}
    </div>
  );
}
