"use client";

import { useState } from "react";
import styles from "./trustVerificationPremium.module.css";

const FAQ_ITEMS = [
  {
    q: "Who should order tenant verification?",
    a: "Landlords and property owners screening a prospective renter before signing a lease or handing over keys.",
  },
  {
    q: "Who should order owner verification?",
    a: "Tenants and renters who want to confirm the landlord and property details before paying token or rent.",
  },
  {
    q: "How long does the report take?",
    a: "Depending on your package, reports are typically delivered within 48–72 hours by email as a PDF.",
  },
  {
    q: "Is my data secure?",
    a: "We use encrypted storage for documents and reports. ID numbers are masked; full ID is not stored in plain text.",
  },
  {
    q: "Can I pay online?",
    a: "Yes — Cashfree is available when enabled. You can also complete payment offline with our team.",
  },
];

export default function VerificationFaq({ items = FAQ_ITEMS, className = "" }) {
  const [open, setOpen] = useState(0);

  return (
    <section className={`${styles.faqCard} ${className}`.trim()}>
      <div className={styles.faqHeader}>
        <h2 className={styles.faqHeaderTitle}>Frequently asked questions</h2>
      </div>
      {items.map((item, i) => {
        const isOpen = open === i;
        const question = item.question || item.q || "";
        const answer = item.answer || item.a || "";
        return (
          <div key={question || i} className={styles.faqItem}>
            <button
              type="button"
              className={styles.faqButton}
              onClick={() => setOpen(isOpen ? -1 : i)}
              aria-expanded={isOpen}
            >
              <span>{question}</span>
              <span
                className={`${styles.faqToggle} ${isOpen ? styles.faqToggleOpen : ""}`}
                aria-hidden
              >
                +
              </span>
            </button>
            {isOpen && <p className={styles.faqAnswer}>{answer}</p>}
          </div>
        );
      })}
    </section>
  );
}
