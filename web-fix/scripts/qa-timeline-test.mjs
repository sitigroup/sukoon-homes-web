import { buildVerificationTimelineSimple } from "../plugins/trust-verification/ui/trustVerificationTimelineUtils.js";

const cases = [
  { name: "null", order: null },
  { name: "empty", order: {} },
  { name: "cancelled", order: { status: "cancelled", payment_status: "pending" } },
  { name: "submitted unpaid", order: { status: "submitted", payment_status: "pending" }, expectPayment: "current" },
  { name: "paid in_progress", order: { status: "in_progress", payment_status: "paid" } },
  { name: "completed with report", order: { status: "completed", payment_status: "paid", report: { download_available: true } } },
  { name: "legacy missing fields", order: { id: 1, order_number: "TV-OLD" } },
  { name: "waived", order: { status: "in_progress", payment_status: "waived" } },
];

let failed = 0;
for (const { name, order, expectPayment } of cases) {
  try {
    const steps = buildVerificationTimelineSimple(order);
    if (!Array.isArray(steps) || steps.length !== 5) {
      throw new Error(`expected 5 steps, got ${steps?.length}`);
    }
    for (const s of steps) {
      if (!["complete", "current", "upcoming"].includes(s.status)) {
        throw new Error(`invalid status ${s.status}`);
      }
    }
    if (expectPayment) {
      const pay = steps.find((s) => s.id === "payment");
      if (pay?.status !== expectPayment) {
        throw new Error(`payment step expected ${expectPayment}, got ${pay?.status}`);
      }
      if (pay?.label?.toLowerCase().includes("confirmed")) {
        throw new Error(`unpaid order must not show payment confirmed label: ${pay.label}`);
      }
    }
    console.log(`OK ${name}:`, steps.map((s) => `${s.id}:${s.status}:${s.label}`).join(" | "));
  } catch (e) {
    failed++;
    console.error(`FAIL ${name}:`, e.message);
  }
}
process.exit(failed ? 1 : 0);
