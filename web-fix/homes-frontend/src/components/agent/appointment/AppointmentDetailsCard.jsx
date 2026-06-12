import React from 'react';
import dynamic from 'next/dynamic';
import { BiPhoneCall, BiEnvelope, BiMap } from 'react-icons/bi';
import ImageWithPlaceholder from '@/components/image-with-placeholder/ImageWithPlaceholder';
import { useTranslation } from '@/components/context/TranslationContext';
import { BadgeSvg, formatPriceAbbreviated, getDisplayValueForOption, VerifiedUserBadge } from '@/utils/helperFunction';
import { ReactSVG } from 'react-svg';
import Link from 'next/link';
import {
    Tooltip,
    TooltipContent, TooltipTrigger
} from "@/components/ui/tooltip";
import { useSelector } from 'react-redux';

const TenantApplicantReliabilityPanel = dynamic(
    () => import('@/plugins/trust-verification/ui/TenantApplicantReliabilityPanel'),
    { ssr: false, loading: () => null },
);

/**
 * AppointmentDetailsCard component for displaying appointment details
 * Shows different layouts for user view (agent details) and agent view (user details)
 * 
 * @param {Object} props - Component props
 * @param {boolean} props.is_user - If true, shows agent details (for user view)
 * @param {boolean} props.is_agent - If true, shows user details (for agent view)  
 * @param {Object} props.appointmentData - Appointment related data
 * @param {Function} props.onReportUser - Callback for report user action (agent view only)
 */


const AppointmentDetailsCard = ({
    is_user = false,
    is_agent = false,
    appointmentData = {},
    onReportUser = () => { }
}) => {

    const t = useTranslation();
    const webSettings = useSelector((state) => state.WebSetting?.data);
    const propertyData = appointmentData?.property || {};
    const agentData = appointmentData?.admin ? appointmentData?.admin : appointmentData?.agent;
    const userData = appointmentData?.user || {};
    const contactData = is_agent ? userData : agentData;
    const location = `${propertyData?.city || ''}, ${propertyData?.state || ''}, ${propertyData?.country || ''}`;


    const translatedTitle = propertyData?.translated_title || propertyData?.title;

    const PropertySection = () => (
        <div className="flex flex-col bg-white rounded-2xl p-4 relative">
            <div className="flex flex-col sm:flex-row items-start sm:items-center gap-4 mb-4">
                {/* Property Image */}
                <div className="w-full sm:w-36 h-24 rounded-2xl overflow-hidden flex-shrink-0">
                    <ImageWithPlaceholder
                        src={propertyData?.title_image}
                        alt={translatedTitle}
                        className="w-full h-full object-cover"
                    />
                </div>

                {/* Property Info */}
                <div className="flex-1 space-y-3 w-full">
                    <h3 className="text-base font-bold brandColor">{translatedTitle}</h3>
                    <div className="flex items-center gap-1">
                        <BiMap className="w-4 h-4 leadColor" />
                        <span className="text-sm leadColor">{location}</span>
                    </div>
                </div>
            </div>

            {/* Property Type Badge */}
            <div className={`absolute top-3 right-3 px-2 py-1 rounded-full text-xs font-semibold ${propertyData?.property_type === "sell" ? "primarySellBg primarySellText" : "primaryRentBg primaryRentText"}`}>
                {t(propertyData?.property_type)}
            </div>

            {/* Property Details */}
            <div className="space-y-4">
                {/* Amenities */}
                <div className="newBorder rounded-lg p-4">
                    <div className="flex items-center justify-between">
                        <div className="flex flex-wrap items-center gap-2 md:gap-4">
                            {propertyData?.parameters?.length > 0 ? (
                                <>
                                    {propertyData?.parameters?.slice(0, 4)?.map((parameter, idx) => {
                                        const translatedName = parameter?.translated_name || parameter?.name;
                                        return (
                                            <Tooltip key={parameter.id || idx}>
                                                <TooltipTrigger asChild>
                                                    <div
                                                        className="flex cursor-default items-center justify-center gap-1 px-2"
                                                        aria-label={translatedName}
                                                    >
                                                        <ReactSVG
                                                            src={parameter.image}
                                                            beforeInjection={(svg) => {
                                                                svg.setAttribute(
                                                                    "style",
                                                                    `height: 100%; width: 100%;`,
                                                                );
                                                                svg.querySelectorAll("path").forEach((path) => {
                                                                    path.setAttribute(
                                                                        "style",
                                                                        `fill: var(--facilities-icon-color);`,
                                                                    );
                                                                });
                                                            }}
                                                            className="w-4 h-4 flex items-center justify-center object-contain shrink-0"
                                                            alt={translatedName}
                                                        />
                                                        <span className="truncate leadColor">
                                                            {getDisplayValueForOption(parameter)}
                                                        </span>
                                                    </div>
                                                </TooltipTrigger>
                                                <TooltipContent side="top">
                                                    <p>{translatedName}</p>
                                                </TooltipContent>
                                            </Tooltip>
                                        )
                                    })}
                                </>
                            ) : (
                                <div />
                            )}
                        </div>
                    </div>
                </div>

                {/* Price */}
                <div className="flex items-center justify-between">
                    <div className="flex items-center justify-between w-full gap-4">
                        <span className="text-base font-bold brandColor">{t('price')}</span>
                        <span className="text-xl font-bold primaryColor">{formatPriceAbbreviated(propertyData.price)} <span className="text-sm font-normal">{propertyData?.property_type === "rent" && propertyData?.rentduration ? "/ " + propertyData?.rentduration : ""}</span></span>
                    </div>
                </div>
            </div>
        </div>
    );

    const ContactSection = () => (
        <div className="flex flex-col bg-white rounded-2xl p-4 space-y-6 w-full">
            {/* Contact Header */}
            <div className="flex flex-col sm:flex-row gap-3 items-start sm:items-center justify-between">
                <div className="flex flex-col gap-2">
                    <span className="text-base leadColor">
                        {is_user ? (t('verifiedUser')) : (t('userName'))}
                    </span>
                    <span className="text-base flex items-center gap-1 font-bold text-gray-800">
                        {contactData.name}
                        {contactData?.is_user_verified && <VerifiedUserBadge color={webSettings?.system_color} />}
                    </span>
                </div>

                <div className="flex flex-col sm:flex-row gap-2">
                    {/* Agent View: Report User Button */}
                    {is_agent && (
                        <button
                            onClick={(e) => onReportUser(e)}
                            className="px-4 py-2 newBorder brandColor rounded-lg font-medium hover:primaryBackgroundBg transition-colors"
                        >
                            {t('reportUser')}
                        </button>
                    )}
                </div>
            </div>

            <div className="w-full h-px newBorderColor bg-gray-300"></div>

            {/* Contact Information */}
            <div className="space-y-6 md:space-y-8">
                {/* Phone */}
                {contactData?.phone && (
                    <div className="flex flex-col sm:flex-row items-start sm:items-center gap-4">
                        <div className="w-12 h-12 primaryBgLight12 rounded-lg flex items-center justify-center flex-shrink-0">
                            <BiPhoneCall className="w-6 h-6 primaryColor" />
                        </div>
                        <div className="flex-1 w-full">
                            <h4 className="text-base font-bold brandColor">{t('phone')}</h4>
                            <p className="text-base leadColor mt-2">{contactData.phone}</p>
                        </div>
                    </div>)}

                {/* Email */}
                {contactData?.email && (
                    <div className="flex flex-col sm:flex-row items-start sm:items-center gap-4">
                        <div className="w-12 h-12 primaryBgLight12 rounded-lg flex items-center justify-center flex-shrink-0">
                            <BiEnvelope className="w-6 h-6 primaryColor" />
                        </div>
                        <div className="flex-1 w-full">
                            <h4 className="text-sm md:text-base font-bold brandColor">{t('email')}</h4>
                            <Link href={`mailto:${contactData?.email}`} className="text-sm md:text-base break-all leadColor mt-2 block">{contactData?.email}</Link>
                        </div>
                    </div>)}

                {/* Address - Only for Agent View */}
                {is_agent && contactData.client_address && (
                    <div className="flex flex-col sm:flex-row items-start sm:items-center gap-4">
                        <div className="w-12 h-12 primaryBgLight12 rounded-lg flex items-center justify-center flex-shrink-0">
                            <BiMap className="w-6 h-6 primaryColor" />
                        </div>
                        <div className="flex-1 w-full">
                            <h4 className="text-base font-bold brandColor">{t('address')}</h4>
                            <p className="text-base leadColor mt-2">{contactData.client_address}</p>
                        </div>
                    </div>
                )}
            </div>
            {/* User Message - Only for Agent View */}
            {is_agent && appointmentData.notes && (
                <>
                    <div className="w-full h-px newBorderColor bg-gray-300"></div>
                    <div>
                        <h4 className="text-base font-bold brandColor mb-2">
                            {t('userMessage')}
                        </h4>
                        <p className="text-base leadColor">{appointmentData.notes}</p>
                    </div>
                </>
            )}
        </div>
    );

    return (
        <div className="primaryBackgroundBg rounded-2xl p-4 md:p-6 flex flex-col lg:flex-row gap-4 md:gap-6 m-3">
            <div className="flex-1 w-full">
                <PropertySection />
            </div>
            <div className="flex-1 w-full space-y-4">
                <ContactSection />
                {is_agent ? (
                    <TenantApplicantReliabilityPanel applicant={userData} />
                ) : null}
            </div>
        </div>
    );
};

export default React.memo(AppointmentDetailsCard);