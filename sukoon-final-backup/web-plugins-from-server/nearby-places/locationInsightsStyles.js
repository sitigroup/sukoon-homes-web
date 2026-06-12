export const insightChipClass =
  "inline-flex items-center gap-0.5 rounded-full border border-slate-200/80 bg-slate-50 px-1.5 py-0.5 text-[10px] font-medium text-slate-600 sm:text-[11px]";

export const insightActionClass =
  "inline-flex h-8 w-full max-w-[108px] items-center justify-center rounded-md border border-slate-200 bg-white px-2 text-[10px] font-semibold leading-none text-slate-700 transition-all duration-150 hover:border-slate-900 hover:bg-slate-50 sm:w-auto sm:min-w-[96px] sm:max-w-[108px]";

export const insightMutedActionClass =
  "inline-flex h-8 w-full max-w-[108px] items-center justify-center rounded-md border border-slate-200 bg-slate-50 px-1.5 text-center text-[9px] font-medium leading-tight text-slate-500 sm:w-auto sm:min-w-[96px] sm:max-w-[108px] sm:text-[10px]";

export const insightPlaceCardClass =
  "relative flex flex-col rounded-lg border border-slate-200/90 bg-white p-2.5 transition-colors duration-150 hover:border-slate-300 sm:p-3";

export const insightIconBoxClass =
  "flex h-7 w-7 min-w-7 shrink-0 items-center justify-center rounded-md border border-slate-200 bg-slate-50 sm:h-8 sm:w-8 sm:min-w-8";

export const insightSectionLabelClass =
  "px-2.5 pb-1 pt-2 text-[10px] font-bold uppercase tracking-wide text-slate-500 sm:px-3";

export const insightOpenBadgeStyle = (isOpen) =>
  isOpen
    ? {
        color: "#047857",
        backgroundColor: "#ecfdf5",
        border: "1px solid #a7f3d0",
      }
    : {
        color: "#64748b",
        backgroundColor: "#f8fafc",
        border: "1px solid #e2e8f0",
      };
