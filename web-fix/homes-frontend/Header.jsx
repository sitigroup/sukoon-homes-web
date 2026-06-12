"use client";
import dynamic from "next/dynamic";
import { beforeLogoutApi, getCustomPagesApi } from "@/api/apiRoutes";
import Logo from "@/assets/logo.png";
import { useAuthStatus } from "@/hooks/useAuthStatus";
import { logout } from "@/redux/slices/authSlice";
import { setIsLanguageLoaded } from "@/redux/slices/languageSlice";
import FirebaseData from "@/utils/Firebase";
import { getLocationDisplayLabel, showLoginSwal, truncate } from "@/utils/helperFunction";
import { clearAgentPortalLogin } from "@/utils/agentPortal";
import Link from "next/link";
import { useRouter } from "next/router";
import { useEffect, useState, useMemo } from "react";
import toast from "react-hot-toast";
import { AiFillInstagram } from "react-icons/ai";
import { BiMapPin } from "react-icons/bi";
import { FaChevronDown, FaExclamation, FaFacebookF, FaRegClock, FaRegUserCircle, FaYoutube } from "react-icons/fa";
import { FaPhone, FaXTwitter } from "react-icons/fa6";
import { MdEmail, MdOutlineVerifiedUser } from "react-icons/md";
import { useDispatch, useSelector } from "react-redux";
import Swal from "sweetalert2";
import { useTranslation } from "../context/TranslationContext";
import ImageWithPlaceholder from "../image-with-placeholder/ImageWithPlaceholder";
import LocationSearchWithRadius from "../location-search/LocationSearchWithRadius";
import { useQuery, useQueryClient } from "@tanstack/react-query";
import { setCustomPages } from "@/redux/slices/cacheSlice";
import UserDropDown from "../reusable-components/UserDropDown";
import { IoCaretDownSharp } from "react-icons/io5";
import useUserRoles from "@/plugins/user-roles/useUserRoles";
import { buildMyServiceLinks } from "@/plugins/user-roles/myServiceMenu";
import { filterUserAccountMenuItems, SHOW_BECOME_AGENT_PROMO } from "@/plugins/user-roles/userAccountMenu";
import usePluginT from "@/plugins/shared/i18n/usePluginT";
import { displayContactEmail } from "@/utils/publicContact";

const LoginModal = dynamic(() => import("../modal/LoginModal"), { ssr: false });
const AreaConverter = dynamic(() => import("../area-converter/AreaConverter"), { ssr: false });
const MobileMenu = dynamic(() => import("./MobileMenu"), { ssr: false });
const SCROLL_THRESHOLD = 50;


const Header = () => {
  const queryClient = useQueryClient();
  const dispatch = useDispatch();
  const router = useRouter();
  const t = useTranslation();
  const pt = usePluginT();
  const { lang } = router?.query;

  const userSelectedLocation = useSelector((state) => state.location);

  const isUserLoggedIn = useAuthStatus();

  const [isScrolled, setIsScrolled] = useState(false);
  const [isMenuOpen, setIsMenuOpen] = useState(false);
  const [showLogin, setShowLogin] = useState(false);
  const [showAreaConverter, setShowAreaConverter] = useState(false);
  const [showLangDropdown, setShowLangDropdown] = useState(false);
  const [locationDisplay, setLocationDisplay] = useState(
    () => getLocationDisplayLabel(userSelectedLocation),
  );
  const [isLocationDialogOpen, setIsLocationDialogOpen] = useState(false);
  const [openMenu, setOpenMenu] = useState(null);

  const languages = useSelector((state) => state.LanguageSettings?.languages);
  const defaultLang = useSelector((state) => state.LanguageSettings?.default_language);
  const activeLang = useSelector((state) => state.LanguageSettings?.active_language);
  const currentLang = activeLang || defaultLang;
  const userData = useSelector((state) => state.User?.data);
  const FcmToken = useSelector((state) => state.WebSetting?.fcmToken);
  const webSettings = useSelector((state) => state.WebSetting?.data);

  const isAgent = userData?.become_agent_status === "approved" && userData?.is_agent === true;
  const isAgentRequestRejected = userData?.become_agent_status === "rejected";
  const isAgentRequestPending = userData?.become_agent_status === "pending";
  const isAgentNotApplied = userData?.become_agent_status === "not_applied";
  const isBecomeAgentPage = router?.pathname === "/become-agent";
  const { signOut } = FirebaseData();
  const agentStatusButtonClass =
    "hidden m-2 xl:flex items-center gap-2 rounded-lg border brandBorder px-4 py-2 text-base font-medium brandColor max-h-14 justify-center hover:brandBg hover:text-white";
  const handleBecomeAgentClick = () => {
    if (userData) {
      router.push(`/become-agent?lang=${currentLang}`);
    } else {
      showLoginSwal("oops", "plzLoginFirstToBecomeAgent", () => {
        handleShowLogin()
      }, t)
    }
  }

  const agentStatusButton = SHOW_BECOME_AGENT_PROMO &&
    (!userData || isAgentNotApplied) && !isBecomeAgentPage
      ? {
        icon: <MdOutlineVerifiedUser className="size-4" />,
        label: t("becomeAgent"),
        onClick: handleBecomeAgentClick,
        disabled: false,
      }
      : isAgentRequestPending && !isBecomeAgentPage
        ? {
          icon: <FaRegClock className="size-4" />,
          label: t("requestPending"),
          onClick: undefined,
          disabled: true,
        }
        : isAgentRequestRejected && !isBecomeAgentPage
          ? {
            icon: <FaExclamation className="size-4" />,
            label: t("requestRejected"),
            onClick: () => router?.push(`/become-agent?lang=${currentLang}`),
            disabled: false,
          }
          : null;

  useEffect(() => {
    setIsScrolled(window.scrollY > SCROLL_THRESHOLD);
    const handleScroll = () => setIsScrolled(window.scrollY > SCROLL_THRESHOLD);
    window.addEventListener("scroll", handleScroll);
    return () => window.removeEventListener("scroll", handleScroll);
  }, []);

  // Close navigation menus and language dropdown when clicking outside
  useEffect(() => {
    const handleClickOutside = (event) => {
      // Check if the click is outside any dropdown menu
      const isInsideDropdown = event.target.closest('.dropdown-menu') ||
        event.target.closest('.dropdown-trigger');

      // Check if the click is outside the language dropdown
      const isInsideLangDropdown = event.target.closest('.language-dropdown');

      if (!isInsideDropdown && openMenu) {
        setOpenMenu(null);
      }

      if (!isInsideLangDropdown && showLangDropdown) {
        setShowLangDropdown(false);
      }
    };

    if (openMenu || showLangDropdown) {
      document.addEventListener('click', handleClickOutside);
    }

    return () => {
      document.removeEventListener('click', handleClickOutside);
    };
  }, [openMenu, showLangDropdown]);

  const toggleMenu = () => setIsMenuOpen(!isMenuOpen);
  const handleShowLogin = () => {
    setShowLogin(true);
    setIsMenuOpen(false);
  };
  const handleShowAreaConverter = () => {
    setShowAreaConverter(true);
    setIsMenuOpen(false);
  };
  useEffect(() => {
    setLocationDisplay(getLocationDisplayLabel(userSelectedLocation));
  }, [userSelectedLocation]);

  const { roles: userRoles } = useUserRoles(isUserLoggedIn);
  const showTenantDashboard = !!userRoles.is_tenant;
  const myServicesLinks = useMemo(
    () => buildMyServiceLinks(userRoles, { variant: "desktop", showTenantDashboard }),
    [userRoles, showTenantDashboard],
  );
  const mobileServiceLinks = useMemo(
    () => buildMyServiceLinks(userRoles, { variant: "mobile", showTenantDashboard }),
    [userRoles, showTenantDashboard],
  );

  const getCustomPages = async ({ limit, offset }) => {
    try {
      const response = await getCustomPagesApi({ limit, offset });
      dispatch(setCustomPages(response.data))
      return response.data;
    } catch (error) {
      console.error("Failed to fetch custom pages:", error);
      return null;
    }
  };
  const customPages = useQuery({
    queryKey: ["customPages", lang],
    queryFn: () => getCustomPages({ limit: "10", offset: "0" }),
    enabled: !!lang,
    staleTime: 10 * 60 * 1000,
    refetchOnWindowFocus: false,
    refetchOnMount: false,
    refetchOnReconnect: false,
  });

  const menus = [
    {
      name: "properties",
      links: [
        {
          name: "allProperties",
          route: `/properties`,
        },
        {
          name: "featuredProperties",
          route: `/properties/featured-properties`,
        },
        {
          name: "mostViewedProperties",
          route: `/properties/most-viewed-properties`,
        },
        {
          name: "mostFavouriteProperties",
          route: `/properties/most-favourite-properties`,
        },
        {
          name: "propertiesNearbyCity",
          route: `/properties/properties-nearby-city`,
        },
      ],
    },
    {
      name: "pages",
      links: [
        {
          name: "verificationServices",
          route: `/verification`,
        },
        { name: "aboutUs", route: `/about-us` },
        { name: "contactUs", route: `/contact-us` },
        { name: "privacyPolicy", route: `/privacy-policy` },
        { name: "termsAndConditions", route: `/terms-and-conditions` },
        { name: "dataDeletion", route: `/data-deletion` },
        { name: "faqs", route: `/faqs` },
      ],
    }
  ];

  const handleLanguageChange = async (newLang) => {
    try {
      // Skip if already the current language
      if (newLang === currentLang) return;

      // Fetch language data for the new language
      dispatch(setIsLanguageLoaded({ data: false }));
      // Update URL with new language query parameter
      const currentQuery = { ...router.query };
      currentQuery.lang = newLang;

      // Use router.push to update the language query param
      router.push(
        {
          pathname: router.pathname,
          query: currentQuery,
        },
        undefined,
        { shallow: true }
      );

      if (isMenuOpen) {
        toggleMenu();
      }
    } catch (error) {
      console.error("Failed to change language:", error);
      toast.error(t("languageChangeError"));
    }
  };

  const handleNavigateLinks = (e, link) => {
    e.preventDefault();
    const query = { lang: router.query.lang || currentLang };
    if (link.filters) {
      query.filters = encodeURIComponent(btoa(JSON.stringify(link.filters)));
    }
    router.push({
      pathname: link.route,
      query,
    });
  };

  const handleLogout = async () => {
    setIsMenuOpen(false);
    Swal.fire({
      title: t("areYouSure"),
      text: t("youNotAbelToRevertThis"),
      icon: "warning",
      showCancelButton: true,
      customClass: {
        confirmButton: "Swal-confirm-buttons",
        cancelButton: "Swal-cancel-buttons",
      },
      confirmButtonText: t("yesLogout"),
      cancelButtonText: t("cancel"),
    }).then(async (result) => {
      if (result.isConfirmed) {
        try {
          if (FcmToken) {
            const res = await beforeLogoutApi({ fcm_id: FcmToken });
            if (!res.error) {
              clearAgentPortalLogin();
              dispatch(logout());
              signOut();
              toast.success(t("logoutSuccess"));
            }
          } else {
            clearAgentPortalLogin();
            dispatch(logout());
            signOut();
            toast.success(t("logoutSuccess"));
          }
        } catch (error) {
          error;
        }
      }
    });
  };

  const handleMenuToggle = (menuName) => {
    setOpenMenu(openMenu === menuName ? null : menuName);
  };


  const handleShowLanguageDropdown = () => {
    if (languages?.length <= 1) {
      return;
    }
    setShowLangDropdown(!showLangDropdown);
  };



  return (
    <>
      <div
        className={`flex w-full h-[80px] md:h-[128px] flex-col ${isScrolled ? "fixed left-0 top-0 z-40 w-full animate-headerSlideDown bg-white shadow-md" : ""}`}
      >
        <div className={`primaryBg hidden h-[48px] py-2 text-white md:block`}>
          <div className="container h-[32px] px-3 md:px-0">
            <div className="flex items-center justify-between">
              <div className="flex items-center gap-4">
                {(webSettings?.company_email || true) && (
                  <Link
                    href={`mailto:${displayContactEmail(webSettings?.company_email)}`}
                    className="flex items-center gap-2 text-sm"
                  >
                    <MdEmail
                      className="rounded-full bg-[#FFFFFF3D] p-1 text-white"
                      size={26}
                    />
                    {displayContactEmail(webSettings?.company_email)}
                  </Link>
                )}
                {webSettings?.company_tel1 && (
                  <Link
                    href={`tel:${webSettings?.company_tel1}`}
                    className="flex items-center gap-2 text-sm"
                  >
                    <FaPhone
                      className="rounded-full bg-[#FFFFFF3D] p-1 text-white"
                      size={26}
                    />
                    <span className="ltr-number">{webSettings?.company_tel1}</span>
                  </Link>
                )}
                {webSettings?.company_tel2 && (
                  <Link
                    href={`tel:${webSettings?.company_tel2}`}
                    className="flex items-center gap-2 text-sm"
                  >
                    <FaPhone
                      className="rounded-full bg-[#FFFFFF3D] p-1 text-white"
                      size={26}
                    />
                    <span className="ltr-number">{webSettings?.company_tel2}</span>
                  </Link>
                )}
              </div>
              <div className="flex items-center gap-4">
                <div
                  className="relative language-dropdown hidden lg:block"
                  onClick={handleShowLanguageDropdown}
                >
                  <button className="flex items-center gap-1 text-sm font-medium rounded-full bg-[#FFFFFF3D] px-2 py-1 text-white focus:outline-none">
                    {languages.find((lang) => lang.code === currentLang)
                      ?.name || t("language")}
                    {languages?.length > 1 && <FaChevronDown size={10} />}
                  </button>

                  {showLangDropdown && (
                    <div
                      className="absolute right-0 top-3 z-[9999] mt-2 w-[110px] rounded-md border border-gray-100 bg-white shadow-lg"
                    >
                      {languages.map((lang) => (
                        <div
                          key={lang.code}
                          className="hover:primaryColor hover:primaryBorderColor group block cursor-pointer border-b-2 border-dashed px-3 py-2 text-black transition-all duration-150 last:border-b-0"
                          onClick={() => {
                            handleLanguageChange(lang.code);
                            setShowLangDropdown(false);
                            setOpenMenu(null); // Close any open navigation menus
                          }}
                        >
                          <span className="transition-all duration-150 group-hover:ml-2">
                            {lang.name}
                          </span>
                        </div>
                      ))}
                    </div>
                  )}
                </div>
                {(webSettings?.facebook_id || webSettings?.twitter_id || webSettings?.instagram_id || webSettings?.youtube_id) && (
                  <>
                    <div className="h-4 border-r border-white/30"></div>
                    <div className="flex items-center gap-1">
                      <span className="text-sm font-medium">{t("followUs")}</span>
                      <div className="ml-2 flex items-center gap-1">
                        {webSettings?.facebook_id && (
                          <Link
                            href={webSettings.facebook_id}
                            target="_blank"
                            className="flex items-center justify-center w-8 h-8 rounded-full hover:bg-white/20 text-white hover:text-gray-200 transition-colors"
                            aria-label="FacebookSocialIcon"
                          >
                            <FaFacebookF size={16} />
                          </Link>
                        )}
                        {webSettings?.twitter_id && (
                          <Link
                            href={webSettings.twitter_id}
                            target="_blank"
                            className="flex items-center justify-center w-8 h-8 rounded-full hover:bg-white/20 text-white hover:text-gray-200 transition-colors"
                            aria-label="TwitterSocialIcon"
                          >
                            <FaXTwitter size={16} />
                          </Link>
                        )}
                        {webSettings?.instagram_id && (
                          <Link
                            href={webSettings.instagram_id}
                            target="_blank"
                            className="flex items-center justify-center w-8 h-8 rounded-full hover:bg-white/20 text-white hover:text-gray-200 transition-colors"
                            aria-label="InstagramSocialIcon"
                          >
                            <AiFillInstagram size={16} />
                          </Link>
                        )}
                        {webSettings?.youtube_id && (
                          <Link
                            href={webSettings.youtube_id}
                            target="_blank"
                            className="flex items-center justify-center w-8 h-8 rounded-full hover:bg-white/20 text-white hover:text-gray-200 transition-colors"
                            aria-label="YouTubeSocialIcon"
                          >
                            <FaYoutube size={16} />
                          </Link>
                        )}
                      </div>
                    </div>
                  </>
                )}
              </div>
            </div>
          </div>
        </div>

        <header className="relative z-50 w-full h-[80px] bg-white">
          <div className="container px-2 md:px-0 h-[56px]">
            <div className="my-3 flex items-center justify-between">
              <div className="flex items-center gap-6">
                <Link href={`/?lang=${lang || "en"}`} title="Home">
                  <ImageWithPlaceholder
                    src={webSettings?.web_logo ? webSettings?.web_logo : Logo}
                    alt="logo"
                    width={176}
                    height={56}
                    className="md:w-44 md:h-14 w-32 h-10 aspect-[176/56] object-cover"
                    priority={true}
                  />
                </Link>
                <div className="h-14 md:border-r border-gray-200 md:block"></div>
                <div className="relative">
                  <div
                    className="relative hidden cursor-pointer transition-all md:block"
                    role="button"
                    tabIndex={0}
                    onClick={() => setIsLocationDialogOpen(true)}
                    onKeyDown={(e) => {
                      if (e.key === "Enter" || e.key === " ") {
                        setIsLocationDialogOpen(true);
                      }
                    }}
                  >
                    <div className="flex items-center gap-2">
                      <div className="rounded bg-[#0000001A] p-2">
                        <BiMapPin size={28} />
                      </div>
                      <div>
                        <div className="flex items-center gap-1 text-gray-700">
                          <span className="text-sm">{t("location")}</span>
                          <FaChevronDown size={10} className="mb-0.5" />
                        </div>
                        <div className="mt-0.5 text-sm text-gray-600 max-w-[220px] truncate">
                          {locationDisplay || t("selectLocation")}
                        </div>
                      </div>
                    </div>
                  </div>
                </div>
              </div>

              <div className="flex items-center gap-3">
                <div className="xl:hidden">
                  {userData ?
                    (
                      <div className="relative dropdown-menu xl:hidden">
                        <button
                          type="button"
                          className="dropdown-trigger flex items-center gap-2 cursor-pointer bg-transparent p-0"
                          onClick={(e) => {
                            e.preventDefault();
                            e.stopPropagation();
                            handleMenuToggle("user");
                          }}
                        >
                          {userData?.profile ? (
                            <ImageWithPlaceholder
                              src={userData?.profile}
                              alt={userData?.name}
                              width={44}
                              height={44}
                              className="rounded-full aspect-[44/44] h-11 w-11 border newBorderColor p-0.5"
                            />
                          ) : (
                            <div className="rounded-full aspect-[44/44] h-11 w-11 flex items-center justify-center primaryBg text-white text-xl font-bold uppercase border newBorderColor">
                              {(userData?.name)?.charAt(0) || t("user")}
                            </div>
                          )}
                          <IoCaretDownSharp className={`size-4 transition-transform duration-200 ${openMenu === "user" ? "rotate-180" : ""}`} />
                        </button>
                        {openMenu === "user" && (
                          <UserDropDown user={userData} handleLogout={handleLogout} handleLanguageChange={handleLanguageChange} onClose={() => handleMenuToggle(null)} />
                        )}
                      </div>
                    ) : (
                      <div className="flex items-center gap-2">
                        <button
                          onClick={handleShowLogin}
                          className="rounded-full h-10 w-10 flex items-center justify-center primaryBg text-white border shrink-0 hover:brandBg"
                        >
                          <FaRegUserCircle size={24} />
                        </button>
                      </div>
                    )}
                </div>
                <MobileMenu
                  isScrolled={isScrolled}
                  isMenuOpen={isMenuOpen}
                  toggleMenu={toggleMenu}
                  languages={languages}
                  menus={menus}
                  isLoggedIn={isUserLoggedIn}
                  handleLanguageChange={handleLanguageChange}
                  handleShowLogin={handleShowLogin}
                  handleLogout={handleLogout}
                  handleShowAreaConverter={handleShowAreaConverter}
                  serviceLinks={mobileServiceLinks}
                />

                <ul className="hidden items-center gap-4 xl:flex">
                  <li className="hover:primaryColor font-medium text-gray-700">
                    <Link href={`/`} className="flex flex-col items-center">
                      <span className={router.pathname === '/' ? 'primaryColor font-bold' : ''}>
                        {t("home")}
                      </span>
                      {router.pathname === '/' && (
                        <div className="flex gap-1 mt-1">
                          <div className="w-1 h-1 rounded-full primaryBg"></div>
                          <div className="w-1 h-1 rounded-full primaryBg"></div>
                          <div className="w-1 h-1 rounded-full primaryBg"></div>
                        </div>
                      )}
                    </Link>
                  </li>
                  <li className="hover:primaryColor font-medium text-gray-700">
                    <Link
                      href={{ pathname: "/my-profile/verification", query: { lang: router.query.lang || currentLang } }}
                      className="flex flex-col items-center"
                    >
                      <span className={router.pathname === "/my-profile/verification" ? "primaryColor font-bold" : ""}>
                        {t("getVerifiedKycCta")}
                      </span>
                    </Link>
                  </li>
                  {menus.map((menu) => (
                    <li key={menu.name} className="relative dropdown-menu flex flex-col items-center">
                      <button
                        className="dropdown-trigger hover:primaryColor flex items-center gap-1 bg-transparent p-0 text-base font-medium text-gray-700 transition-all"
                        onClick={(e) => {
                          e.preventDefault();
                          e.stopPropagation();
                          handleMenuToggle(menu.name);
                        }}
                      >
                        <span className={menu?.links?.some(link => {
                          return router.pathname.includes(link.route) && link.route !== '/';
                        }) ? 'primaryColor font-bold' : ''}>
                          {t(menu.name)}
                        </span>
                        <FaChevronDown
                          size={10}
                          className={`transition-transform duration-200 ${openMenu === menu.name ? 'rotate-180' : ''} ${menu?.links?.some(link => {
                            return router.pathname.includes(link.route) && link.route !== '/';
                          }) ? 'primaryColor' : ''
                            }`}
                        />
                      </button>

                      {menu?.links?.some(link => {
                        return router.pathname.includes(link.route) && link.route !== '/';
                      }) && (
                          <div className="flex gap-1 mt-1">
                            <div className="w-1 h-1 rounded-full primaryBg"></div>
                            <div className="w-1 h-1 rounded-full primaryBg"></div>
                            <div className="w-1 h-1 rounded-full primaryBg"></div>
                          </div>
                        )}

                      {openMenu === menu.name && (
                        <div className="dropdown-content absolute left-0 top-full z-20 mt-2 w-[250px] rounded-md cardBg shadow-lg newBorder">
                          <ul className="py-1 [&>li:last-child>button]:border-b-0">
                            {menu?.links?.map((link) => (
                              <li key={link.name}>
                                <button
                                  className="hover:primaryColor hover:primaryBorderColor group block w-full cursor-pointer border-b-2 border-dashed px-3 py-2 text-left transition-all duration-150"
                                  onClick={(e) => {
                                    e.preventDefault();
                                    e.stopPropagation();
                                    if (link.name === "areaConverter") {
                                      setShowAreaConverter(true);
                                    } else {
                                      handleNavigateLinks(e, link);
                                    }
                                    setOpenMenu(null);
                                  }}
                                >
                                  <span className="transition-all duration-150 group-hover:ml-2">
                                    {truncate(t(link.name), 26)}
                                  </span>
                                </button>
                              </li>
                            ))}
                          </ul>
                        </div>
                      )}
                    </li>
                  ))}
                  {isUserLoggedIn && (myServicesLinks.length > 0 || showTenantDashboard) && (
                    <li className="relative dropdown-menu flex flex-col items-center">
                      <button
                        type="button"
                        className="dropdown-trigger hover:primaryColor flex items-center gap-1 bg-transparent p-0 text-base font-medium text-gray-700 transition-all"
                        onClick={(e) => {
                          e.preventDefault();
                          e.stopPropagation();
                          handleMenuToggle("myServices");
                        }}
                      >
                        <span className={myServicesLinks.some((link) => router.pathname.startsWith(link.route)) ? "primaryColor font-bold" : ""}>
                          {pt("myServices")}
                        </span>
                        <FaChevronDown
                          size={10}
                          className={`transition-transform duration-200 ${openMenu === "myServices" ? "rotate-180" : ""}`}
                        />
                      </button>
                      {openMenu === "myServices" && (
                        <div className="dropdown-content absolute left-0 top-full z-20 mt-2 w-[250px] rounded-md cardBg shadow-lg newBorder">
                          <ul className="py-1">
                            {myServicesLinks.map((link) => (
                              <li key={link.route}>
                                <Link
                                  href={{ pathname: link.route, query: { lang: router.query.lang || currentLang } }}
                                  className="hover:primaryColor hover:primaryBorderColor group block w-full border-b-2 border-dashed px-3 py-2 text-left text-sm transition-all duration-150"
                                  onClick={() => setOpenMenu(null)}
                                >
                                  <span className="transition-all duration-150 group-hover:ml-2">
                                    {pt(link.labelKey)}
                                  </span>
                                </Link>
                              </li>
                            ))}
                          </ul>
                        </div>
                      )}
                    </li>
                  )}
                </ul>

                {agentStatusButton && (
                  <button
                    onClick={agentStatusButton.onClick}
                    disabled={agentStatusButton.disabled}
                    className={agentStatusButtonClass}
                  >
                    {agentStatusButton.icon}
                    <span className="text-nowrap">{agentStatusButton.label}</span>
                  </button>
                )}

                <div className="hidden items-center gap-3 font-medium xl:flex">
                  {userData === null ? (
                    <button
                      className="hover:primaryBg flex items-center gap-2 rounded bg-gray-900 px-4 py-2 text-white transition-all"
                      onClick={() => setShowLogin(true)}
                    >
                      <FaRegUserCircle size={16} />
                      {t("login")}/{t("register")}
                    </button>
                  ) : (userData && userData?.name) ||
                    userData?.email ||
                    userData?.mobile ? (
                    <div className="relative dropdown-menu">
                      <button
                        className="dropdown-trigger hover:primaryColor flex w-max items-center gap-1 bg-transparent p-0 text-base font-medium text-gray-700 transition-all"
                        onClick={(e) => {
                          e.preventDefault();
                          e.stopPropagation();
                          handleMenuToggle("user");
                        }}
                      >
                        {userData?.profile ? (
                          <ImageWithPlaceholder
                            src={userData?.profile}
                            alt={userData?.name}
                            width={44}
                            height={44}
                            className="rounded-full aspect-[44/44] h-11 w-11 border newBorderColor p-0.5"
                          />
                        ) : (
                          <div className="rounded-full aspect-[44/44] h-11 w-11 flex items-center justify-center primaryBg text-white text-xl font-bold uppercase border newBorderColor">
                            {(userData?.name)?.charAt(0) || t("user")}
                          </div>
                        )}
                        {userData?.name
                          ? truncate(userData?.name, 15)
                          : t("welcomeUser")}
                        <FaChevronDown
                          size={10}
                          className={`transition-transform duration-200 ${openMenu === "user" ? 'rotate-180' : ''
                            }`}
                        />
                      </button>

                      {openMenu === "user" && (
                        <UserDropDown user={userData} handleLogout={handleLogout} handleLanguageChange={handleLanguageChange} onClose={() => handleMenuToggle(null)} />
                      )}
                    </div>
                  ) : null}
                </div>
              </div>
            </div>
          </div>
        </header>
      </div>

      <LoginModal showLogin={showLogin} setShowLogin={setShowLogin} />
      {showAreaConverter && (
        <AreaConverter
          isOpen={showAreaConverter}
          onClose={() => setShowAreaConverter(false)}
        />
      )}
      <LocationSearchWithRadius
        isOpen={isLocationDialogOpen}
        onClose={() => setIsLocationDialogOpen(false)}
      />
    </>
  );
};

export default Header;
