"use client";

import { REFERENCE_TYPES, EMPTY_REFERENCE } from "../trustVerificationReferenceUtils";
import { tv, cn } from "./trustVerificationTheme";

export default function ReferenceDetailsForm({
  value = [],
  onChange,
  className = "",
  maxItems = 5,
}) {
  const rows = value.length ? value : [{ ...EMPTY_REFERENCE }];

  const updateRow = (index, field, fieldValue) => {
    const next = rows.map((row, i) => (i === index ? { ...row, [field]: fieldValue } : row));
    onChange?.(next);
  };

  const addRow = () => {
    if (rows.length >= maxItems) return;
    onChange?.([...rows, { ...EMPTY_REFERENCE }]);
  };

  const removeRow = (index) => {
    if (rows.length <= 1) {
      onChange?.([{ ...EMPTY_REFERENCE }]);
      return;
    }
    onChange?.(rows.filter((_, i) => i !== index));
  };

  return (
    <div className={cn("space-y-4", className)}>
      <div>
        <p className={cn(tv.heading, "text-base")}>Reference contacts</p>
        <p className={cn(tv.subheading, "mt-1 text-sm")}>
          Provide previous landlord, employer, or family references we can contact.
        </p>
      </div>

      {rows.map((row, index) => (
        <div
          key={index}
          className="rounded-[12px] border border-[#E5E7EB] bg-[#FAFAFA] p-4"
        >
          <div className="mb-3 flex items-center justify-between gap-2">
            <span className="text-sm font-semibold text-[#374151]">Reference {index + 1}</span>
            {rows.length > 1 ? (
              <button
                type="button"
                className="text-xs font-medium text-red-600 hover:underline"
                onClick={() => removeRow(index)}
              >
                Remove
              </button>
            ) : null}
          </div>

          <div className="grid gap-3 sm:grid-cols-2">
            <label className="block text-sm">
              <span className="mb-1 block font-medium text-[#374151]">Type</span>
              <select
                className={tv.input}
                value={row.reference_type}
                onChange={(e) => updateRow(index, "reference_type", e.target.value)}
              >
                {REFERENCE_TYPES.map((t) => (
                  <option key={t.id} value={t.id}>
                    {t.label}
                  </option>
                ))}
              </select>
            </label>
            <label className="block text-sm">
              <span className="mb-1 block font-medium text-[#374151]">Name</span>
              <input
                type="text"
                className={tv.input}
                value={row.name}
                maxLength={120}
                onChange={(e) => updateRow(index, "name", e.target.value)}
                placeholder="Full name"
              />
            </label>
            <label className="block text-sm">
              <span className="mb-1 block font-medium text-[#374151]">Relation</span>
              <input
                type="text"
                className={tv.input}
                value={row.relation}
                maxLength={80}
                onChange={(e) => updateRow(index, "relation", e.target.value)}
                placeholder="e.g. Former landlord"
              />
            </label>
            <label className="block text-sm">
              <span className="mb-1 block font-medium text-[#374151]">Mobile</span>
              <input
                type="tel"
                className={tv.input}
                value={row.mobile}
                maxLength={20}
                onChange={(e) => updateRow(index, "mobile", e.target.value.replace(/\D/g, "").slice(0, 10))}
                placeholder="10-digit mobile"
              />
            </label>
            <label className="block text-sm sm:col-span-2">
              <span className="mb-1 block font-medium text-[#374151]">Email (optional)</span>
              <input
                type="email"
                className={tv.input}
                value={row.email}
                maxLength={190}
                onChange={(e) => updateRow(index, "email", e.target.value)}
                placeholder="email@example.com"
              />
            </label>
            <label className="block text-sm sm:col-span-2">
              <span className="mb-1 block font-medium text-[#374151]">Notes (optional)</span>
              <textarea
                className={cn(tv.input, "min-h-[72px]")}
                rows={2}
                value={row.notes}
                maxLength={2000}
                onChange={(e) => updateRow(index, "notes", e.target.value)}
                placeholder="Property address, employment period, etc."
              />
            </label>
          </div>
        </div>
      ))}

      {rows.length < maxItems ? (
        <button type="button" className={tv.btnSecondary} onClick={addRow}>
          Add another reference
        </button>
      ) : null}
    </div>
  );
}
