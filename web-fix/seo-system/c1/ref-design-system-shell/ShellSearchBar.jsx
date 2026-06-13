"use client";

import { useEffect, useState } from "react";
import { cn } from "@/lib/utils";
import { MdMic, MdSearch, MdStop } from "react-icons/md";

/**
 * Visual mockup search bar with mic states.
 * @param {"idle"|"listening"|"result"} demoState - force a visual state for preview panels
 */
export default function ShellSearchBar({ t, demoState = "idle", interactive = false, className, size = "default" }) {
  const [state, setState] = useState(demoState);
  const [value, setValue] = useState("");
  const [voiceSupported, setVoiceSupported] = useState(true);

  useEffect(() => {
    setState(demoState);
  }, [demoState]);

  useEffect(() => {
    if (typeof window === "undefined") return;
    const supported = "SpeechRecognition" in window || "webkitSpeechRecognition" in window;
    setVoiceSupported(supported);
  }, []);

  useEffect(() => {
    if (demoState === "result") {
      setValue(t("shSearchResultText"));
    } else if (demoState === "idle") {
      setValue("");
    }
  }, [demoState, t]);

  const listening = state === "listening";
  const hasResult = state === "result";
  const isHero = size === "hero";
  const isCompact = size === "compact";

  const handleMicClick = () => {
    if (!interactive || !voiceSupported) return;
    if (listening) {
      setState("result");
      setValue(t("shSearchResultText"));
      return;
    }
    setState("listening");
    setValue("");
  };

  const showMic = voiceSupported || !interactive;

  return (
    <div className={cn("w-full", className)}>
      <div
        className={cn(
          "relative flex items-center gap-sukoon-3 rounded-sukoon-input border bg-sukoon-background transition-all duration-250",
          isHero
            ? "px-sukoon-5 py-sukoon-1 shadow-[0_4px_20px_rgba(31,41,55,0.06)]"
            : isCompact
              ? "px-sukoon-3 py-0 shadow-sukoon-sm"
              : "px-sukoon-4 py-sukoon-1 shadow-[0_2px_12px_rgba(31,41,55,0.05)]",
          listening
            ? "border-sukoon-gold/70 ring-2 ring-sukoon-gold/25"
            : hasResult
              ? "border-sukoon-border-strong"
              : "border-sukoon-border hover:border-sukoon-border-strong hover:shadow-[0_4px_16px_rgba(31,41,55,0.07)] focus-within:border-sukoon-gold/60 focus-within:ring-2 focus-within:ring-sukoon-gold/20",
        )}
      >
        <MdSearch
          className={cn(
            "shrink-0 text-sukoon-graphite-muted",
            isHero ? "h-5 w-5" : isCompact ? "h-4 w-4" : "h-[1.125rem] w-[1.125rem]",
          )}
          aria-hidden
        />
        <input
          type="search"
          readOnly={!interactive}
          value={value}
          placeholder={listening ? t("shSearchListeningHint") : t("shSearchPlaceholder")}
          onChange={(e) => interactive && setValue(e.target.value)}
          className={cn(
            "min-w-0 flex-1 border-0 bg-transparent text-sukoon-graphite placeholder:text-sukoon-graphite-subtle focus:outline-none focus:ring-0",
            isHero ? "py-sukoon-4 text-sukoon-body" : isCompact ? "py-2 text-sukoon-body-sm" : "py-sukoon-3 text-sukoon-body-sm",
          )}
          aria-label={t("shSearchPlaceholder")}
        />
        {showMic && (
          <button
            type="button"
            onClick={handleMicClick}
            disabled={!interactive && demoState !== "idle"}
            className={cn(
              "relative inline-flex shrink-0 items-center justify-center rounded-sukoon-input transition-all duration-200",
              isHero ? "h-10 w-10" : isCompact ? "h-8 w-8" : "h-9 w-9",
              listening
                ? "bg-sukoon-gold-soft text-sukoon-gold"
                : "text-sukoon-graphite-muted hover:bg-sukoon-page hover:text-sukoon-graphite",
            )}
            aria-label={listening ? t("shSearchListening") : "Voice search"}
          >
            {listening && (
              <span
                className="absolute inset-0 rounded-sukoon-input bg-sukoon-gold/15 sukoon-shell-mic-pulse"
                aria-hidden
              />
            )}
            {listening ? <MdStop className="relative h-5 w-5" /> : <MdMic className="relative h-[1.125rem] w-[1.125rem]" />}
          </button>
        )}
      </div>
      {!voiceSupported && interactive && (
        <p className="mt-sukoon-2 text-sukoon-caption text-sukoon-graphite-muted">{t("shSearchVoiceUnsupported")}</p>
      )}
      {listening && !isCompact && (
        <p className="mt-sukoon-2 flex items-center gap-sukoon-2 text-[0.75rem] font-medium text-sukoon-gold">
          <span className="inline-block h-1.5 w-1.5 rounded-full bg-sukoon-gold sukoon-shell-mic-pulse" aria-hidden />
          {t("shSearchListening")}
        </p>
      )}
    </div>
  );
}
