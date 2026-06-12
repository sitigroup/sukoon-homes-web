import React, { useState, useEffect } from "react";
import ImageWithPlaceholder from "../image-with-placeholder/ImageWithPlaceholder";
import Logo from "@/assets/logo.png";
import {
  MdClose,
  MdKeyboardArrowRight,
  MdOutlineVerifiedUser
} from "react-icons/md";
import { GiHamburgerMenu } from "react-icons/gi";
import { BiMapPin } from "react-icons/bi";
import {
  Sheet,
  SheetTrigger,
  SheetContent,
  SheetTitle,
  SheetDescription,
} from "@/components/ui/sheet";
import { useSelector, useDispatch } from "react-redux";
import { useRouter } from "next/router";
import { getLocationDisplayLabel, isRTL, showLoginSwal } from "@/utils/helperFunction";
import { useTranslation } from "../context/TranslationContext";
import LocationSearchWithRadius from "../location-search/LocationSearchWithRadius";
import { setLocationAction } from "@/redux/slices/locationSlice";
import toast from "react-hot-toast";

const MobileMenu = ({
  isMenuOpen,
  toggleMenu,
  menus,
  languages,
  isScrolled,
  handleLanguageChange,
  handleShowLogin,
  handleLogout,
  handleShowAreaConverter,
}) => {
  const t = useTranslation();
  const isRtl = isRTL();
  const router = useRouter();
  const dispatch = useDispatch();
  const [openSubMenu, setOpenSubMenu] = useState("");
  const [activeMenu, setActiveMenu] = useState(""); // Track the active menu
  const [isLocationDialogOpen, setIsLocationDialogOpen] = useState(false);

  const defaultLang = useSelector(
    (state) => state.LanguageSettings?.default_language,
  );
  const activeLang = useSelector((state) => state.LanguageSettings?.active_language);
  const currentLang = activeLang || defaultLang;

  const userData = useSelector((state) => state.User?.data);

  const isAgent = userData?.is_agent;
  const isAgentVerificationNotApplied = userData?.become_agent_status === "not_applied";

  const userSelectedLocation = useSelector((state) => state.location);
  const webSettings = useSelector((state) => state.WebSetting?.data);

  const [locationDisplay, setLocationDisplay] = useState(
    () => getLocationDisplayLabel(userSelectedLocation),
  );

  useEffect(() => {
    setLocationDisplay(getLocationDisplayLabel(userSelectedLocation));
  }, [userSelectedLocation]);

  const toggleSubMenu = (menuName) => {
    setOpenSubMenu((prev) => (prev === menuName ? "" : menuName));
  };

  const handleMenuClick = (menuName, path = "") => {
    setActiveMenu(menuName);
    handleNavigation(`${path}`);
    toggleMenu(); // Optionally close the menu on selection
  };

  const handleNavigation = (path) => {
    router.push({
      pathname: path,
      query: { lang: router.query.lang || currentLang },
    });
  };

  const handleBecomeAgentClick = () => {
    if (userData) {
      handleNavigation(`/become-agent`);
    } else {
      showLoginSwal("oops", "plzLoginFirstToBecomeAgent", () => {
        handleShowLogin();
      }, t);
    }
    toggleMenu();
  };

  // Handle location selection
  const handleLocationClick = () => {
    setIsLocationDialogOpen(true);
    toggleMenu(); // Close mobile menu when opening location dialog
  };

  const handlePlaceSelected = (place) => {
    if (place && place.formatted_address) {
      const address = place.formatted_address;
      const latitude = place.geometry?.location?.lat();
      const longitude = place.geometry?.location?.lng();

      setLocation(address); // Update local state
      dispatch(
        setLocationAction({ formatted_address: address, latitude, longitude }),
      ); // Update Redux
      setIsLocationDialogOpen(false); // Close dialog
      toast.success(t("locationUpdated"));
    } else {
      console.error("Invalid place selected:", place);
      toast.error(t("invalidLocationSelected"));
    }
  };

  return (
    <>
      <Sheet open={isMenuOpen} onOpenChange={toggleMenu}>
        <SheetTrigger asChild>
          <button className="xl:hidden" aria-label="MobileMenuToggler">
            <GiHamburgerMenu
              size={30}
              className="text-black"
            />
          </button>
        </SheetTrigger>
        <SheetContent
          className="cardBg flex h-full flex-col justify-between p-0 overflow-y-auto [&>button]:hidden"
          aria-describedby={"mobile-menu"}
          side={isRtl ? "left" : "right"}
        >
          <SheetTitle className="sr-only">Mobile Menu</SheetTitle>
          <SheetDescription className="sr-only"></SheetDescription>
          <div>
            <div className="flex items-center justify-between border-b p-3 md:p-4">
              <div className="h-full max-w-[160px] w-full flex items-center">
                <ImageWithPlaceholder
                  src={webSettings?.web_logo || Logo}
                  alt="logo"
                  loading="lazy"
                  className="w-full h-full"
                />
              </div>
              <MdClose
                size={30}
                className="cursor-pointer primaryBackgroundBg leadColor font-bold rounded-xl p-2 h-9 w-9 sm:h-11 sm:w-11"
                onClick={toggleMenu}
              />
            </div>
            <ul className="flex flex-col ">

              {/* Location Selection */}
              <li
                className="cursor-pointer border-b-2 border-dashed border-gray-300 p-4 font-medium hover:primaryBgLight hover:primaryColor"
                onClick={handleLocationClick}
              >
                <div className="flex items-center gap-3">
                  <div className="rounded bg-[#0000001A] p-2">
                    <BiMapPin size={20} />
                  </div>
                  <div className="flex-1">
                    <div className="flex items-center gap-1 text-gray-700">
                      <span className="text-sm font-medium">{t("location")}</span>
                    </div>
                    <div className="mt-0.5 text-xs text-gray-600">
                      {locationDisplay || t("selectLocation")}
                    </div>
                  </div>
                  <div>
                    <MdKeyboardArrowRight size={18} className="brandColor rtl:rotate-180" />
                  </div>
                </div>
              </li>

              <li
                className={`cursor-pointer border-b-2 border-dashed border-gray-300 p-4 font-medium ${activeMenu === "home" ? `primaryBgLight primaryColor` : ""
                  } hover:primaryBgLight hover:primaryColor`}
                onClick={() => handleMenuClick("home", `/`)}
              >
                {t("home")}
              </li>


              {menus.map((menu) => (
                <React.Fragment key={menu.name}>
                  <li
                    className={`cursor-pointer border-b-2 border-dashed border-gray-300 p-4 font-medium ${activeMenu === menu.name
                      ? `primaryBgLight primaryColor`
                      : ""
                      } hover:primaryBgLight hover:primaryColor`}
                    onClick={() => toggleSubMenu(menu.name)}
                  >
                    <div className="flex items-center justify-between">
                      {t(menu.name)}
                      <MdKeyboardArrowRight size={18}
                        className={`transition-transform duration-300 ltr:rotate-0 rtl:-rotate-180 ${openSubMenu === menu.name ? "!-rotate-90 primaryColor" : ""
                          }`}
                      />
                    </div>
                  </li>
                  <ul
                    className={`overflow-hidden transition-all duration-300 ${openSubMenu === menu.name
                      ? "max-h-96 opacity-100"
                      : "max-h-0 opacity-0"
                      }`}
                  >
                    {menu.links.map((link) => (
                      <li
                        key={link.name}
                        className={`flex cursor-pointer items-center justify-between p-4 py-2 font-medium ${activeMenu === link.name
                          ? `primaryBgLight primaryColor`
                          : ""
                          } hover:primaryBgLight hover:primaryColor last:border-b-2 last:border-dashed last:border-gray-300`}
                        onClick={() => {
                          if (link.name === "areaConverter") {
                            handleShowAreaConverter();
                          } else {
                            handleMenuClick(link.name, link.route);
                          }
                        }}
                      >
                        {t(link.name)}
                        <MdKeyboardArrowRight size={18} className="brandColor rtl:rotate-180" />
                      </li>
                    ))}
                  </ul>
                </React.Fragment>
              ))}
              <li
                className={`font-medium`}
              >
                <div
                  className="flex cursor-pointer items-center justify-between border-b-2 border-dashed border-gray-300 p-4 font-medium"
                  onClick={() => toggleSubMenu("language")}
                >
                  {t("language")}:{" "}
                  {languages &&
                    languages.find((lang) => lang.code === currentLang)?.name}
                  <MdKeyboardArrowRight size={18}
                    className={`transition-transform duration-300 ltr:rotate-0 rtl:-rotate-180 ${openSubMenu === "language" ? "!-rotate-90" : ""
                      }`}
                  />
                </div>
                <ul
                  className={`overflow-hidden transition-all duration-300 ${openSubMenu === "language"
                    ? "max-h-96 opacity-100"
                    : "max-h-0 opacity-0"
                    }`}
                >
                  {languages &&
                    languages.map((lang) => (
                      <li
                        key={lang.code}
                        className={`flex cursor-pointer items-center justify-between p-4 py-2 ${currentLang === lang.code
                          ? "primaryColor font-semibold"
                          : ""
                          } hover:primaryBgLight hover:primaryColor last:border-b-2 last:border-dashed last:border-gray-300`}
                        onClick={() => {
                          handleLanguageChange(lang.code);
                          toggleSubMenu("language");
                        }}
                      >
                        {lang.name}
                        <MdKeyboardArrowRight size={18} className="brandColor rtl:rotate-180" />
                      </li>
                    ))}
                </ul>
              </li>
              {userData && !isAgent && isAgentVerificationNotApplied && (
                <button
                  className="m-2 flex px-4 py-2 border brandBorder rounded-lg justify-center hover:brandBg hover:text-white items-center gap-2 font-medium text-base brandColor max-h-14"
                  onClick={handleBecomeAgentClick}
                >
                  <MdOutlineVerifiedUser className="w-5 h-5" />
                  <span>{t("becomeAgent")}</span>
                </button>
              )}
            </ul>
          </div>
        </SheetContent>
      </Sheet>

      {/* Location Search Dialog */}
      <LocationSearchWithRadius
        isOpen={isLocationDialogOpen}
        onClose={() => setIsLocationDialogOpen(false)}
        onPlaceSelected={handlePlaceSelected}
      />
    </>
  );
};

export default MobileMenu;
