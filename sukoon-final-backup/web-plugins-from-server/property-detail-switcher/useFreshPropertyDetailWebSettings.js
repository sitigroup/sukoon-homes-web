import { useEffect, useRef } from "react";
import { useDispatch } from "react-redux";
import * as api from "@/api/apiRoutes";
import { setWebSettings } from "@/redux/slices/webSettingSlice";
import { persistor } from "@/redux/store";

/**
 * Property detail routes only: refresh WebSetting from API so property_detail_layout
 * is not stuck on redux-persist / Layout (refetchOnMount: false) cached values.
 */
export function useFreshPropertyDetailWebSettings() {
  const dispatch = useDispatch();
  const refreshCountRef = useRef(0);

  useEffect(() => {
    let cancelled = false;

    const refreshFromApi = async (reason) => {
      try {
        const response = await api.getWebSetting();
        const settings = response?.data;
        if (cancelled || !settings) return;

        refreshCountRef.current += 1;
        dispatch(setWebSettings({ data: settings }));

        if (process.env.NODE_ENV === "development") {
          console.info(
            `[property-detail] web-settings refreshed (${reason})`,
            settings.property_detail_layout,
          );
        }
      } catch (error) {
        console.error("[property-detail] web-settings refresh failed:", error);
      }
    };

    refreshFromApi("mount");

    const { bootstrapped } = persistor.getState();
    if (!bootstrapped) {
      const unsubscribe = persistor.subscribe(() => {
        if (cancelled) return;
        if (persistor.getState().bootstrapped) {
          refreshFromApi("persist-rehydrate");
          unsubscribe();
        }
      });

      return () => {
        cancelled = true;
        unsubscribe();
      };
    }

    return () => {
      cancelled = true;
    };
  }, [dispatch]);
}
