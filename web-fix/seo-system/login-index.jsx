"use client";

import { useEffect, useState } from "react";
import dynamic from "next/dynamic";
import { useRouter } from "next/router";
import Layout from "@/components/layout/Layout";
import MetaData from "@/components/meta/MetaData";
import { useAuthStatus } from "@/hooks/useAuthStatus";
import { useTranslation } from "@/components/context/TranslationContext";

const LoginModal = dynamic(() => import("@/components/modal/LoginModal"), {
  ssr: false,
});

export default function LoginPage() {
  const router = useRouter();
  const t = useTranslation();
  const isLoggedIn = useAuthStatus();
  const [showLogin, setShowLogin] = useState(true);

  useEffect(() => {
    setShowLogin(true);
  }, []);

  useEffect(() => {
    if (!isLoggedIn) {
      return;
    }
    const dest =
      typeof router.query.redirect === "string" && router.query.redirect.startsWith("/")
        ? router.query.redirect
        : "/";
    router.replace(dest);
  }, [isLoggedIn, router]);

  return (
    <Layout>
      <MetaData title="Login" robots="noindex, nofollow" />
      <LoginModal showLogin={showLogin} setShowLogin={setShowLogin} />
      {!isLoggedIn && (
        <p style={{ padding: "2rem", color: "#6B7280", textAlign: "center" }}>
          {t("authLoginSubtitle")}
        </p>
      )}
    </Layout>
  );
}
