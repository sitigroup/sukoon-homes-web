"use client";
import enTranslation from "./en.json";
import hiTranslation from "./hi.json";

export const getTranslationByLocale = (locale) => {
  switch (locale) {
    case "hi":
      return hiTranslation;
    case "en":
    default:
      return enTranslation;
  }
};
