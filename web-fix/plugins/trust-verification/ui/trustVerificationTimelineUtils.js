/**

 * Maps API order shape → premium timeline steps with date/time detail.

 */



import {

  formatTrustVerificationDateTime,

  getOrderTimestamps,

  TV_NOT_RECORDED_LABEL,

  TV_PENDING_LABEL,

} from "../trustVerificationDateTime";



export const TIMELINE_STEP_IDS = [

  "submitted",

  "payment",

  "verification",

  "review",

  "report",

];



export const TIMELINE_STEP_LABELS = {

  submitted: "Submitted",

  payment: "Payment",

  verification: "Verification",

  review: "Review",

  report: "Report Ready",

};



export function isOrderPaid(order) {

  return ["paid", "waived"].includes(String(order?.payment_status || "pending").toLowerCase());

}



/**

 * Step label reflects actual payment state (avoids "Payment Confirmed" while still unpaid).

 */

export function getTimelineStepLabel(stepId, order) {

  const paid = isOrderPaid(order);

  const status = order?.status || "submitted";



  switch (stepId) {

    case "submitted":

      return TIMELINE_STEP_LABELS.submitted;

    case "payment":

      if (!paid) return "Payment pending";

      return "Payment confirmed";

    case "verification":

      return TIMELINE_STEP_LABELS.verification;

    case "review":

      return TIMELINE_STEP_LABELS.review;

    case "report":

      return TIMELINE_STEP_LABELS.report;

    default:

      return stepId;

  }

}



/**

 * Secondary line under each timeline step (date/time or status).

 */

export function getTimelineStepDetail(stepId, order) {

  const ts = getOrderTimestamps(order);

  const paid = isOrderPaid(order);

  const status = order?.status || "submitted";

  const hasReport =

    order?.can_download_report === true ||

    order?.can_download_report === 1 ||

    !!order.report?.download_available ||

    !!order.report?.has_report_file ||

    !!order.report?.uploaded;



  switch (stepId) {

    case "submitted": {

      const at = formatTrustVerificationDateTime(ts.created_at || order?.created_at);

      return at || TV_NOT_RECORDED_LABEL;

    }

    case "payment":

      if (!paid) return "Payment pending";

      if (ts.paid_at) {

        const at = formatTrustVerificationDateTime(ts.paid_at);

        return at ? `Paid on ${at}` : "Paid";

      }

      return "Paid";

    case "verification": {
      const policeDetail = order?.police_verification_enabled
        ? (() => {
            const pv = order?.police_verification;
            if (!pv?.status || pv.status === "not_submitted") return null;
            const label =
              pv.status === "submitted"
                ? "Police verification submitted"
                : pv.status === "under_review"
                  ? "Police verification under review"
                  : pv.status === "completed"
                    ? "Police verification completed"
                    : pv.status === "rejected"
                      ? "Police verification rejected"
                      : pv.status === "need_more_documents"
                        ? "Police verification — more documents needed"
                        : null;
            if (!label) return null;
            const at = formatTrustVerificationDateTime(
              ts.police_verification_submitted_at || pv.submitted_at
            );
            return at && pv.status !== "not_submitted" ? `${label} · ${at}` : label;
          })()
        : null;

      if (policeDetail) {
        return policeDetail;
      }

      if (order?.reference_progress?.required && order.reference_progress.summary) {
        const summary = order.reference_progress.summary;
        const submittedAt = formatTrustVerificationDateTime(ts.references_submitted_at);
        if (order.reference_progress.status === "pending_submission") {
          return summary;
        }
        if (submittedAt && order.reference_progress.submitted > 0) {
          return `${summary} · Submitted ${submittedAt}`;
        }
        return summary;
      }

      if (ts.verification_started_at) {

        const at = formatTrustVerificationDateTime(ts.verification_started_at);

        return at ? `Started on ${at}` : "In progress";

      }

      if (status === "in_progress" || order?.automation_status === "running") {

        return "In progress";

      }

      if (paid && ["submitted", "in_progress"].includes(status)) {

        return TV_PENDING_LABEL;

      }

      return paid ? TV_PENDING_LABEL : TV_NOT_RECORDED_LABEL;

    }

    case "review": {

      if (ts.review_completed_at || order?.completed_at) {

        const at = formatTrustVerificationDateTime(ts.review_completed_at || order?.completed_at);

        return at ? `Completed on ${at}` : "Completed";

      }

      if (status === "completed") return "Completed";

      return TV_PENDING_LABEL;

    }

    case "report": {

      if (hasReport && (ts.report_uploaded_at || ts.report_created_at)) {

        const at = formatTrustVerificationDateTime(ts.report_uploaded_at || ts.report_created_at);

        return at || TV_NOT_RECORDED_LABEL;

      }

      if (status === "completed" && paid && !hasReport) {

        return TV_PENDING_LABEL;

      }

      if (hasReport) return TV_NOT_RECORDED_LABEL;

      return TV_PENDING_LABEL;

    }

    default:

      return "";

  }

}



/**

 * @param {object} order

 * @returns {Array<{id:string,label:string,detail:string,status:'complete'|'current'|'upcoming'}>}

 */

export function buildVerificationTimeline(order) {

  return buildVerificationTimelineSimple(order);

}



/** Deterministic timeline — payment step only completes when payment_status is paid/waived */

export function buildVerificationTimelineSimple(order) {

  const steps = TIMELINE_STEP_IDS.map((id) => ({

    id,

    label: getTimelineStepLabel(id, order),

    detail: getTimelineStepDetail(id, order),

    status: "upcoming",

  }));



  if (!order) {

    steps[0].status = "current";

    return steps;

  }



  if (order.status === "cancelled") {

    steps[0].status = "complete";

    return steps;

  }



  const status = order.status || "submitted";

  const paid = isOrderPaid(order);

  const hasReport =

    order?.can_download_report === true ||

    order?.can_download_report === 1 ||

    !!order.report?.download_available ||

    !!order.report?.has_report_file ||

    !!order.report?.uploaded;



  steps[0].status = "complete";



  if (!paid) {

    steps[1].status = "current";

    return steps;

  }



  steps[1].status = "complete";



  if (hasReport) {

    steps.forEach((s) => {

      s.status = "complete";

    });

    return steps;

  }



  if (status === "submitted") {

    steps[2].status = "current";

    return steps;

  }



  if (status === "in_progress") {

    steps[2].status = "current";

    steps[3].status = "upcoming";

    return steps;

  }



  if (status === "completed") {

    steps[2].status = "complete";

    steps[3].status = "complete";

    steps[4].status = "current";

    return steps;

  }



  steps[2].status = "current";

  return steps;

}



/**

 * @param {object|null|undefined} order

 * @returns {string|null}

 */

export function formatOrderLastUpdated(order) {

  const ts = getOrderTimestamps(order);

  return formatTrustVerificationDateTime(ts.updated_at || order?.updated_at);

}


