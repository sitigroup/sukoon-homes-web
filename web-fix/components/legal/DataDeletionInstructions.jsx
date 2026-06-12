"use client";

import Link from "next/link";
import { useRouter } from "next/router";
import NewBreadcrumb from "@/components/breadcrumb/NewBreadCrumb";
import { dataDeletionPageContent as content } from "@/components/legal/dataDeletionContent";

export default function DataDeletionInstructions() {
  const router = useRouter();
  const lang = router?.query?.lang || "en";
  const langQuery = lang ? `?lang=${lang}` : "";
  const mailto = `mailto:${content.contactEmail}?subject=${encodeURIComponent(content.emailSubject)}`;

  return (
    <>
      <NewBreadcrumb
        title={content.title}
        subtitle={content.description}
        items={[{ href: `/data-deletion${langQuery}`, label: content.title }]}
      />
      <section className="cardBg px-4 py-8 sm:py-10 md:py-12 lg:py-[60px]">
        <div className="container mx-auto max-w-3xl">
          <div className="rounded-2xl border cardBorder bg-white p-6 shadow-sm sm:p-8 md:p-10">
            <h1 className="text-2xl font-bold text-gray-900 sm:text-3xl">{content.title}</h1>
            <p className="mt-3 text-sm leading-relaxed text-gray-600 sm:text-base">
              {content.description}
            </p>

            <div className="mt-8 rounded-xl border border-gray-200 bg-gray-50 p-5 sm:p-6">
              <h2 className="text-lg font-semibold text-gray-900">Quick steps</h2>
              <ol className="mt-4 list-decimal space-y-3 pl-5 text-sm leading-relaxed text-gray-700 sm:text-base">
                {content.steps.map((step) => (
                  <li key={step}>{step}</li>
                ))}
              </ol>
            </div>

            <div className="mt-8 space-y-6">
              {content.sections.map((section) => (
                <div key={section.heading}>
                  <h2 className="text-lg font-semibold text-gray-900">{section.heading}</h2>
                  {section.paragraphs?.map((paragraph) => (
                    <p
                      key={paragraph}
                      className="mt-3 text-sm leading-relaxed text-gray-700 sm:text-base"
                    >
                      {paragraph}
                    </p>
                  ))}
                  {section.bullets?.length > 0 && (
                    <ul className="mt-3 list-disc space-y-2 pl-5 text-sm leading-relaxed text-gray-700 sm:text-base">
                      {section.bullets.map((item) => (
                        <li key={item}>{item}</li>
                      ))}
                    </ul>
                  )}
                </div>
              ))}
            </div>

            <div className="mt-10 rounded-xl border border-gray-200 bg-white p-5 sm:p-6">
              <h2 className="text-lg font-semibold text-gray-900">Contact</h2>
              <p className="mt-3 text-sm leading-relaxed text-gray-700 sm:text-base">
                For data deletion requests, email us at{" "}
                <Link
                  href={mailto}
                  className="font-medium text-gray-900 underline underline-offset-2 hover:text-gray-700"
                >
                  {content.contactEmail}
                </Link>{" "}
                with subject &ldquo;{content.emailSubject}&rdquo;.
              </p>
              <Link
                href={mailto}
                className="mt-5 inline-flex w-full items-center justify-center rounded-lg bg-gray-900 px-5 py-3 text-sm font-medium text-white transition-colors hover:bg-gray-800 sm:w-auto"
              >
                Email {content.contactEmail}
              </Link>
            </div>

            <p className="mt-8 text-xs leading-relaxed text-gray-500">
              This page is provided for Meta App Review and user transparency. It does not replace
              our Privacy Policy or Terms and Conditions where those apply to your use of Sukoon
              Homes.
            </p>
          </div>
        </div>
      </section>
    </>
  );
}
