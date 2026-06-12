"use client";

import { ORDER_FILTERS } from "../myVerificationOrderFilters";
import styles from "./trustVerificationPremium.module.css";

export default function MyOrdersFilterBar({
  activeFilter = "all",
  counts = {},
  onFilterChange,
  className = "",
}) {
  const active = ORDER_FILTERS.find(({ id }) => id === activeFilter) || ORDER_FILTERS[0];
  const activeCount = counts[activeFilter] ?? 0;

  return (
    <div className={`${styles.myOrdersFilterWrap} ${className}`.trim()}>
      <label htmlFor="my-orders-status-filter" className={styles.myOrdersFilterLabel}>
        Filter by status
      </label>
      <div className={styles.myOrdersFilterSelectWrap}>
        <select
          id="my-orders-status-filter"
          className={styles.myOrdersFilterSelect}
          value={activeFilter}
          aria-label={`Filter orders. Currently ${active.label}, ${activeCount} orders`}
          onChange={(e) => onFilterChange(e.target.value)}
        >
          {ORDER_FILTERS.map(({ id, label }) => {
            const count = counts[id] ?? 0;
            return (
              <option key={id} value={id}>
                {label} ({count})
              </option>
            );
          })}
        </select>
      </div>
    </div>
  );
}
