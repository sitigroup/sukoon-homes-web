import ImageWithPlaceholder from "../image-with-placeholder/ImageWithPlaceholder";
import { useTranslation } from "../context/TranslationContext";
import {
  FaFacebookF,
  FaInstagram, FaYoutube,
  FaEnvelope,
  FaMapMarkerAlt,
  FaPhoneAlt
} from "react-icons/fa";
import { VerifiedAgentBadge } from "@/utils/helperFunction";
import Link from "next/link";
import { FaXTwitter } from "react-icons/fa6";
import { RiUserVoiceLine } from "react-icons/ri";
import { useSelector } from "react-redux";
import dynamic from "next/dynamic";

const SukoonPublicTrustProfile = dynamic(
  () => import("@/plugins/trust-verification/ui/SukoonPublicTrustProfile"),
  { ssr: false, loading: () => null },
);

const AgentHorizontalCard = ({ agentDetails, isFeatureAvailable = false, setShowAppointmentModule }) => {
  const t = useTranslation();
  const userData = useSelector(state => state.User?.data);
  const webSettings = useSelector(state => state?.WebSetting?.data);
  // Extract data from agentDetails prop
  let {
    id,
    name,
    profile: imgSrc,
    mobile: phone,
    email,
    address,
    facebook_id,
    twitter_id,
    youtube_id,
    instagram_id,
    is_agent_verified: isVerified,
    is_agent: isAgent,
    is_appointment_available: isAppointmentAvailable,
    country_code = "",
  } = agentDetails || {};
  address = agentDetails?.agent_profile?.agent_address;
  facebook_id = agentDetails?.agent_profile?.facebook_id;
  twitter_id = agentDetails?.agent_profile?.twitter_id;
  youtube_id = agentDetails?.agent_profile?.youtube_id;
  instagram_id = agentDetails?.agent_profile?.instagram_id;
  country_code = agentDetails?.agent_profile?.agent_country_code;
  phone = `+${country_code}${phone}`;

  // Create social links object from the API data
  const socialLinks = [
    {
      icon: <FaFacebookF className="h-4 w-4" />,
      link: facebook_id && facebook_id !== "null" ? facebook_id : null,
    },
    {
      icon: <FaXTwitter className="h-4 w-4" />,
      link: twitter_id && twitter_id !== "null" ? twitter_id : null,
    },
    {
      icon: <FaYoutube className="h-4 w-4" />,
      link: youtube_id && youtube_id !== "null" ? youtube_id : null,
    },
    {
      icon: <FaInstagram className="h-4 w-4" />,
      link: instagram_id && instagram_id !== "null" ? instagram_id : null,
    },
  ];

  const renderSocialLinks = () => {
    if (socialLinks.length === 0) return <div className="h-8 w-8" />;
    return (
      <div className="flex items-center gap-3">
        {socialLinks.map((link) => {
          if (!link.link) return;
          return (
            <Link
              href={link.link}
              key={link.link}
              target="_blank"
              className="primaryBackgroundBg flex h-8 w-8 items-center justify-center rounded-full transition-all duration-200 hover:cursor-pointer hover:bg-gray-100 sm:h-10 sm:w-10"
            >
              {link.icon}
            </Link>
          );
        })}
      </div>
    );
  };

  return (
    <div className="w-full h-full rounded-2xl border border-gray-200 bg-white p-4 md:p-6 md:max-h-[1000px] lg:max-h-[900px]">
      <div className="flex flex-col items-start gap-4 sm:gap-2 md:flex-row md:gap-6">
        {/* Agent Image Section */}
        <div className={`h-fit w-full md:max-w-[16rem] primaryBackgroundBg rounded-2xl`}>
          <ImageWithPlaceholder
            src={imgSrc}
            alt={`${name} - Real Estate Agent`}
            className="h-[373px] max-h-full w-full object-cover rounded-2xl"
            loading="lazy"
          />
        </div>

        {/* Agent Details Section */}
        <div className="relative flex w-full flex-1 flex-col justify-between h-[400px] xl:h-[373px]">
          {/* Header Section */}
          <div className="space-y-4">
            {/* Name and Agent ID */}
            <div className="flex items-center justify-between border-b pb-4">
              <h2 className="brandColor mb-1 max-w-min text-base font-bold sm:text-xl md:max-w-full md:text-2xl">
                <div className="flex items-center gap-2">
                  {name}
                  {isVerified && <VerifiedAgentBadge width={26} height={26} color={webSettings?.system_color} />}
                </div>
                <span className="block md:hidden text-sm font-medium leadColor">{t("agentId")}: {id}</span>
              </h2>
              <div className="flex flex-col items-center gap-2 sm:flex-row">
                {/* Display Agent ID if available */}
                {id && (
                  <p className="hidden md:block text-sm font-medium leadColor">
                    {t("agentId")}: {id}
                  </p>
                )}
              </div>
            </div>

            {/* Contact Information */}
            <div className="space-y-3">
              {/* Address */}
              {address && (
                <div className="flex items-center gap-3">
                  <div className="primaryBgLight flex h-12 w-12 flex-shrink-0 items-center justify-center rounded-lg">
                    <FaMapMarkerAlt className="primaryColor h-5 w-5" />
                  </div>
                  <p className="flex flex-col gap-2 md:text-base">
                    <span className="brandColor font-bold">{t("address")}</span>
                    <span className="leadColor text-sm font-medium sm:text-base line-clamp-1">
                      {address}
                    </span>
                  </p>
                </div>
              )}

              {/* Email */}
              {email && (
                <div className="flex items-center gap-3">
                  <div className="primaryBgLight flex h-12 w-12 flex-shrink-0 items-center justify-center rounded-lg">
                    <FaEnvelope className="primaryColor h-5 w-5 flex-shrink-0" />
                  </div>
                  <div className="flex w-full flex-col gap-2">
                    <span className="brandColor font-bold">{t("email")}</span>
                    <Link
                      href={`mailto:${email}`}
                      className="leadColor w-fit break-all text-sm font-medium hover:underline sm:text-base"
                    >
                      {email}
                    </Link>
                  </div>
                </div>
              )}

              {/* Phone */}
              {country_code && phone && phone !== "null" && (
                <div className="flex items-center gap-3">
                  <div className="primaryBgLight flex h-12 w-12 flex-shrink-0 items-center justify-center rounded-lg">
                    <FaPhoneAlt className="primaryColor h-5 w-5" />
                  </div>
                  <div className="flex w-full flex-col items-start gap-2 sm:flex-row sm:items-center sm:justify-between">
                    <div className="flex flex-col gap-2">
                      <span className="brandColor font-bold">{t("call")}</span>
                      <Link
                        href={`tel:${phone}`}
                        className="leadColor text-sm font-medium sm:text-base"
                      >
                        {phone}
                      </Link>
                    </div>
                  </div>
                </div>
              )}
            </div>
          </div>

          {/* Footer Section */}
          <div className={`border-t py-4 md:py-2 mt-4 md:mt-1 xl:py-4 xl:mt-4 flex flex-col xl:flex-row items-center ${socialLinks.every((link) => link.link) ? "justify-between" : "justify-end"} w-full gap-4`}>
            {/* Social Media Links */}
            {socialLinks.every((link) => link.link) && (
              <div className="flex flex-col items-center justify-between gap-4 sm:flex-row">
                <div className="flex items-center gap-4">
                  <p className="brandColor text-sm font-bold md:text-base">
                    {t("followMe")}:
                  </p>
                  <div className="flex items-center gap-3">
                    {renderSocialLinks()}
                  </div>
                </div>
              </div>
            )}
            {userData?.id !== id && isAppointmentAvailable ?
              (
                <button
                  onClick={() => setShowAppointmentModule(true)}
                  className="flex primaryBg px-4 w-fit py-3 text-white items-center gap-2 rounded-lg"
                >
                  <RiUserVoiceLine className="w-5 h-5" />
                  <span className="font-medium text-base">{t("scheduleAnAppointment")}</span>
                </button>
              ) : null}
          </div>

          {id ? <SukoonPublicTrustProfile customerId={id} className="px-4 pb-4 md:px-6" /> : null}
        </div>
      </div>
    </div>
  );
};

export default AgentHorizontalCard;
