"use client";

import styles from "./trustVerificationPremium.module.css";

const ORDERS_PER_PAGE = 5;

export { ORDERS_PER_PAGE };

/**
 * @param {number} page 1-based
 * @param {number} totalItems
 * @param {number} pageSize
 */
export function getPaginationSlice(page, totalItems, pageSize = ORDERS_PER_PAGE) {
  const totalPages = Math.max(1, Math.ceil(totalItems / pageSize));
  const safePage = Math.min(Math.max(1, page), totalPages);
  const start = (safePage - 1) * pageSize;
  const end = Math.min(start + pageSize, totalItems);
  return { totalPages, safePage, start, end, items: { start, end } };
}

export default function MyOrdersPagination({
  page,
  totalItems,
  pageSize = ORDERS_PER_PAGE,
  onPageChange,
}) {
  const { totalPages, safePage, start, end } = getPaginationSlice(page, totalItems, pageSize);

  if (totalItems <= pageSize) {
    return null;
  }

  const pages = Array.from({ length: totalPages }, (_, i) => i + 1);

  return (
    <nav className={styles.myOrdersPagination} aria-label="Orders pagination">
      <p className={styles.myOrdersPaginationSummary}>
        Showing {start + 1}–{end} of {totalItems} orders
      </p>
      <div className={styles.myOrdersPaginationControls}>
        <button
          type="button"
          className={styles.myOrdersPaginationBtn}
          disabled={safePage <= 1}
          onClick={() => onPageChange(safePage - 1)}
        >
          Previous
        </button>
        {pages.map((n) => (
          <button
            key={n}
            type="button"
            className={`${styles.myOrdersPaginationPage} ${
              n === safePage ? styles.myOrdersPaginationPageActive : ""
            }`}
            onClick={() => onPageChange(n)}
            aria-current={n === safePage ? "page" : undefined}
          >
            {n}
          </button>
        ))}
        <button
          type="button"
          className={styles.myOrdersPaginationBtn}
          disabled={safePage >= totalPages}
          onClick={() => onPageChange(safePage + 1)}
        >
          Next
        </button>
      </div>
    </nav>
  );
}
