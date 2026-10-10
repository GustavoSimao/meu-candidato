import { forwardRef, type SelectHTMLAttributes, useId } from "react";
import { cn } from "@/lib/utils";

export interface SelectProps extends SelectHTMLAttributes<HTMLSelectElement> {
  label?: string;
  error?: string;
  placeholder?: string;
}

export const Select = forwardRef<HTMLSelectElement, SelectProps>(
  ({ label, error, placeholder, className, id, children, ...props }, ref) => {
    const generatedId = useId();
    const selectId = id || `select-${generatedId}`;

    return (
      <div className="w-full">
        {label && (
          <label htmlFor={selectId} className="mb-1 block text-sm font-medium text-neutral-700">
            {label}
          </label>
        )}
          <select
            ref={ref}
            id={selectId}
            aria-invalid={error ? "true" : undefined}
            className={cn(
              "w-full rounded-md border border-neutral-300 px-3 py-2 text-sm text-neutral-900",
              "focus-visible:border-blue-600 focus-visible:ring-2 focus-visible:ring-blue-500 focus-visible:outline-none",
              error && "border-red-500 focus-visible:ring-red-500",
              className
            )}
            {...props}
          >
          {placeholder && (
            <option value="" disabled hidden>
              {placeholder}
            </option>
          )}
          {children}
        </select>
        {error && <p className="mt-1 text-sm text-red-600">{error}</p>}
      </div>
    );
  }
);
Select.displayName = "Select";
