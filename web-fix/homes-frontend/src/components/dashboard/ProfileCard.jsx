"use client"
import { useTranslation } from '../context/TranslationContext';
import { Plus } from 'lucide-react';
import ImageWithPlaceholder from '../image-with-placeholder/ImageWithPlaceholder';
import dashboardImg from '@/assets/dashboard_image.png';
import { handlePackageCheck } from '@/utils/helperFunction';
import { PackageTypes } from '@/utils/checkPackages/packageTypes';
import { useRouter } from 'next/router';
import dynamic from "next/dynamic";

const TrustProfileSection = dynamic(
  () => import("@/plugins/trust-verification/TrustProfileSection"),
  { ssr: false, loading: () => null },
);

const ProfileCard = ({ userName }) => {
    const t = useTranslation();
    const router = useRouter();
    return (
        <div className="relative bg-black rounded-2xl text-white w-full h-fit overflow-hidden shadow-2xl">

            {/* Top Section - Greeting */}
            <div className="p-8 lg:p-12 relative z-50">
                <h1 className="text-xl md:text-4xl lg:text-5xl font-bold mb-4 leading-tight line-clamp-2">
                    {t("hy")}, {userName}
                </h1>
                <p className="text-md md:text-lg text-gray-300 leading-relaxed z-50">
                    {t("manageYourProfileAndViewProperty")}
                </p>
                <TrustProfileSection
                    compact
                    className="mt-4 max-w-md rounded-xl bg-white/95 p-3 text-gray-900 shadow-sm !mb-0"
                />
            </div>

            {/* Bottom Section - Button + Image */}
            <div className="relative p-6 rounded-b-2xl flex items-center min-h-[200px] md:min-h-[260px] after:content-[''] 
            after:absolute after:bottom-0 after:left-0 after:w-full after:h-[120px] md:after:h-[160px] after:bg-white/10 after:rounded-b-2xl">
                {/* Add Property Button */}
                <button className="bg-white text-black p-2 md:py-3 md:px-4 rounded-[8px] flex items-center gap-2 z-20 text-base md:text-xl font-medium absolute right-5 left-auto md:left-5 md:right-auto bottom-10"
                    onClick={(e) => handlePackageCheck(e, PackageTypes.PROPERTY_LIST, router, null, null, true, null, t)}>
                    <div className="w-5 h-5 rounded-full flex items-center justify-center ">
                        <Plus size={20} />
                    </div>
                    {t("addProperty")}
                </button>

                {/* Building Image */}
                <div className="absolute bottom-0 right-0 w-[466px] z-10 h-[200px] md:h-[344px]">
                    {/* Uncomment this and add your image path */}
                    <ImageWithPlaceholder
                        priority={true}
                        src={dashboardImg.src}
                        alt="Modern Building"
                        className="w-full h-full object-cover"
                        loading="lazy"
                    />
                </div>
            </div>
        </div>
    )
}

export default ProfileCard