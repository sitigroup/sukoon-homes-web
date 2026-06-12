"use client";

import { useEffect, useRef, useState } from "react";
import dynamic from "next/dynamic";
import { useQuery } from "@tanstack/react-query";
import { useDispatch, useSelector } from "react-redux";
import { Input } from "@/components/ui/input";
import { Textarea } from "@/components/ui/textarea";
import { Button } from "@/components/ui/button";
import { Label } from "@/components/ui/label";
import { Skeleton } from "@/components/ui/skeleton";
import ImageWithPlaceholder from "../image-with-placeholder/ImageWithPlaceholder";
import { useTranslation } from "../context/TranslationContext";
import CustomLocationAutocomplete from "../location-search/CustomLocationAutocomplete";
import { extractAddressComponents } from "@/utils/helperFunction";
import { getUserProfileApi, updateUserProfileApi, getMapDetailsApi } from "@/api/apiRoutes";
import { updateUserProfile } from "@/redux/slices/authSlice";
import toast from "react-hot-toast";
import Swal from "sweetalert2";
import PhoneInput from "react-phone-input-2";
import { PhoneNumberUtil } from "google-libphonenumber";
import { BiSolidImageAdd } from "react-icons/bi";
import { FaUser } from "react-icons/fa";
import { HiOutlineMapPin } from "react-icons/hi2";
import ButtonLoader from "../ui/loaders/ButtonLoader";
import VerifyUserCTA from "./VerifyUserCTA";
const TrustProfileSection = dynamic(
    () => import("@/plugins/trust-verification/TrustProfileSection"),
    { ssr: false, loading: () => null },
);

const LocationPickerModal = dynamic(
    () => import("../agent/LocationPickerModal"),
    {
        ssr: false,
        loading: () => null,
    },
);

const phoneUtil = PhoneNumberUtil.getInstance();

const buildFormData = (data = {}) => ({
    firstName: data?.name || "",
    email: data?.email || "",
    phone:
        data?.country_code && data?.mobile
            ? `${data.country_code}${data.mobile}`
            : data?.mobile || "",
    countryCode: data?.country_code || "",
    location: "",
    latitude: data?.latitude || null,
    longitude: data?.longitude || null,
    address: data?.address || "",
    city: data?.city || "",
    state: data?.state || "",
    country: data?.country || "",
    about_me: data?.about_me || "",
    profileImage: null,
});

const UserProfile = () => {
    const t = useTranslation();
    const dispatch = useDispatch();
    const fileInputRef = useRef(null);
    const userData = useSelector((state) => state.User?.data);
    const webSettings = useSelector((state) => state.WebSetting?.data);
    const activeLanguage = useSelector(
        (state) => state.LanguageSettings?.active_language,
    );

    const [formData, setFormData] = useState(() => buildFormData(userData));
    const [previewImage, setPreviewImage] = useState(userData?.profile || "");
    const [isSubmitting, setIsSubmitting] = useState(false);
    const [isLocationModalOpen, setIsLocationModalOpen] = useState(false);

    const fetchAddressFromCoordinates = async (lat, lng) => {
        try {
            const response = await getMapDetailsApi({
                latitude: lat.toString(),
                longitude: lng.toString(),
                place_id: "",
            });

            if (response?.error === false && response?.data?.result) {
                const firstResult = response.data.result;
                const addressData = extractAddressComponents(firstResult);
                const formattedAddress = addressData.formattedAddress || firstResult.formatted_address || `${lat.toFixed(6)}, ${lng.toFixed(6)}`;

                setFormData((previous) => ({
                    ...previous,
                    location: formattedAddress,
                    city: addressData.city || previous.city,
                    state: addressData.state || previous.state,
                    country: addressData.country || previous.country,
                }));
            } else {
                const coordString = `${lat.toFixed(6)}, ${lng.toFixed(6)}`;
                setFormData((previous) => ({
                    ...previous,
                    location: coordString,
                }));
            }
        } catch (error) {
            const coordString = `${lat.toFixed(6)}, ${lng.toFixed(6)}`;
            setFormData((previous) => ({
                ...previous,
                location: coordString,
            }));
            toast.error(t("locationError"));
        }
    };

    const profileQuery = useQuery({
        queryKey: ["userProfile", activeLanguage],
        queryFn: async () => {
            const response = await getUserProfileApi();

            if (response?.error) {
                throw new Error(response?.message || t("somethingWentWrong"));
            }

            dispatch(updateUserProfile({ data: response.data }));

            return response?.data || response;
        },
        staleTime: 10 * 60 * 1000,
        refetchOnWindowFocus: false,
        refetchOnReconnect: false,
        refetchOnMount: true,
        retry: 2,
    });

    const profileData = profileQuery.data || userData;
    const isLoadingProfile = profileQuery.isLoading && !profileData;

    useEffect(() => {
        if (!profileData) return;

        setFormData(buildFormData(profileData));
        setPreviewImage(profileData?.profile || "");
    }, [profileData]);

    useEffect(() => {
        if (profileData?.latitude && profileData?.longitude) {
            fetchAddressFromCoordinates(profileData.latitude, profileData.longitude);
        }
    }, [profileData?.latitude, profileData?.longitude]);

    const handleInputChange = (event) => {
        const { name, value } = event.target;
        setFormData((previous) => ({ ...previous, [name]: value }));
    };

    const handleImageUpload = (event) => {
        const file = event.target.files?.[0];
        if (!file) return;

        setFormData((previous) => ({ ...previous, profileImage: file }));

        const reader = new FileReader();
        reader.onloadend = () => setPreviewImage(reader.result);
        reader.readAsDataURL(file);
    };

    const handlePlaceSelect = (placeData) => {
        try {
            if (!placeData) return;

            const { city, state, country, formattedAddress } =
                extractAddressComponents(placeData);

            setFormData((previous) => ({
                ...previous,
                location: formattedAddress || previous.location,
                city: city || previous.city,
                state: state || previous.state,
                country: country || previous.country,
                latitude: placeData.latitude || previous.latitude,
                longitude: placeData.longitude || previous.longitude,
            }));
        } catch (error) {
            toast.error(t("locationError"));
        }
    };

    const handleLocationFromModal = (locationData) => {
        setFormData((previous) => ({
            ...previous,
            location: locationData.location,
            city: locationData.city,
            state: locationData.state,
            country: locationData.country,
            latitude: locationData.latitude,
            longitude: locationData.longitude,
        }));
    };

    const handlePhoneNumberChange = (value, data) => {
        setFormData((previous) => ({
            ...previous,
            phone: value,
            countryCode: data?.dialCode || previous.countryCode,
        }));
    };

    const handleUploadClick = () => {
        fileInputRef.current?.click();
    };

    const handleSubmit = async (event) => {
        event?.preventDefault();

        if (webSettings?.demo_mode && profileData?.is_demo_user) {
            Swal.fire({
                title: t("oops"),
                text: t("notAllowdDemo"),
                icon: "warning",
                showCancelButton: false,
                customClass: {
                    confirmButton: "Swal-confirm-buttons",
                    cancelButton: "Swal-cancel-buttons",
                },
                confirmButtonText: t("ok"),
            });
            return;
        }

        if (!previewImage && !profileData?.profile) {
            toast.error(t("profilePictureRequired"));
            return;
        }

        if (!formData.phone) {
            toast.error(t("phoneNumberIsRequired"));
            return;
        }

        const countryCodeDigitsOnly =
            formData?.countryCode?.replace(/\D/g, "") || "";
        const startsWithCountryCode =
            countryCodeDigitsOnly &&
            formData?.phone?.startsWith(countryCodeDigitsOnly);
        const formattedNumber = startsWithCountryCode
            ? formData.phone.substring(countryCodeDigitsOnly.length)
            : formData.phone;

        const rawPhoneNumber = `+${formData?.countryCode || ""}${formattedNumber}`;

        let phone;
        try {
            phone = phoneUtil.parseAndKeepRawInput(rawPhoneNumber, "ZZ");

            if (!phoneUtil.isValidNumber(phone)) {
                toast.error(t("invalidPhoneNumber"));
                return;
            }
        } catch (error) {
            toast.error(
                error?.message?.includes("Invalid country calling code")
                    ? t("invalidCountryCode")
                    : t("invalidPhoneNumberFormat"),
            );
            return;
        }

        const countryCode = String(phone.getCountryCode());
        const mobile = phone.getNationalNumber().toString();

        setIsSubmitting(true);
        try {
            const response = await updateUserProfileApi({
                userid: profileData?.id || userData?.id || "",
                name: formData.firstName,
                email: formData.email,
                mobile,
                address: formData.address,
                firebase_id: profileData?.firebase_id || userData?.firebase_id || "",
                logintype: profileData?.logintype || userData?.logintype || "",
                profile: formData.profileImage || undefined,
                latitude: formData.latitude || "",
                longitude: formData.longitude || "",
                about_me: formData.about_me,
                city: formData.city,
                state: formData.state,
                country: formData.country,
                country_code: countryCode,
            });

            if (response?.data) {
                dispatch(updateUserProfile({ data: response.data }));
                setPreviewImage(response.data?.profile || previewImage);
                setFormData(buildFormData(response.data));
                toast.success(t("profileUpdatedSuccessfully"));
                await profileQuery.refetch();
                return;
            }

            toast.error(t("profileUpdateFailed"));
        } catch (error) {
            toast.error(t("profileUpdateFailed"));
        } finally {
            setIsSubmitting(false);
        }
    };

    if (isLoadingProfile) {
        return <Skeleton className="h-[720px] w-full rounded-2xl" />;
    }

    return (
        <form
            onSubmit={handleSubmit}
            className="flex w-full h-full flex-col overflow-hidden rounded-2xl border newBorderColor bg-white shadow-sm"
        >
            <div className="border-b newBorderColor px-3 py-2 sm:p-3 md:px-4">
                <h1 className="text-base md:text-xl font-bold brandColor">
                    {t("myProfile")}
                </h1>
            </div>

            <div className="flex flex-col gap-7 p-4 sm:p-6">
                <section className="flex w-full flex-col gap-6">

                    {profileQuery.isError && !profileData ? (
                        <div className="rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-600">
                            {profileQuery.error?.message || t("somethingWentWrong")}
                        </div>
                    ) : null}

                    <div className="flex flex-col justify-center md:items-start gap-3">
                        <Label className="mb-2 flex items-center gap-1 text-base font-medium brandColor">
                            {t("profilePicture")}
                            <span className="ml-1 text-red-500">*</span>
                        </Label>
                        <div className="flex flex-col gap-4 rounded-lg border-2 border-dashed newBorderColor bg-white p-4 sm:flex-row sm:items-center sm:justify-between w-full">
                            <div className="flex flex-col items-center gap-4 sm:flex-row sm:items-center">
                                <div className="w-[72px] h-[72px] overflow-hidden rounded-xl primaryBackgroundBg">
                                    {previewImage ? (
                                        <ImageWithPlaceholder
                                            src={previewImage}
                                            alt={profileData?.name || t("profilePicture")}
                                            width={72}
                                            height={72}
                                            className="h-full w-full rounded-xl aspect-[72/72]"
                                        />
                                    ) : (
                                        <div className="flex h-full w-full items-center justify-center leadColor">
                                            <FaUser size={28} />
                                        </div>
                                    )}
                                </div>

                                <div className="flex flex-col gap-2">
                                    <p className="text-base text-center md:text-start font-bold brandColor">
                                        {profileData?.name || t("myProfile")}
                                    </p>
                                    <div className="flex w-fit rounded-md redBgLight12 p-2">
                                        <p className="text-xs md:text-sm text-center font-medium redColorText">
                                            {t("profilePictureNote")}
                                        </p>
                                    </div>
                                </div>
                            </div>

                            <Button
                                type="button"
                                className="flex h-12 rounded-lg brandBg px-2 md:px-4 py-1 md:py-3 text-base md:text-xl font-medium text-white hover:primaryBg"
                                onClick={handleUploadClick}
                            >
                                <span className="flex items-center gap-2">
                                    <BiSolidImageAdd className="shrink-0 size-4 md:size-6" />
                                    {t("uploadProfile")}
                                </span>
                            </Button>
                            <input
                                ref={fileInputRef}
                                id="profileImage"
                                name="profileImage"
                                type="file"
                                accept="image/*"
                                onChange={handleImageUpload}
                                className="hidden"
                            />
                        </div>
                    </div>

                    <VerifyUserCTA />

                    <TrustProfileSection />

                    <div className="grid grid-cols-1 gap-4 lg:grid-cols-2">
                        <div className="flex flex-col gap-2">
                            <Label className="mb-2 flex items-center gap-1 text-base font-medium brandColor">
                                {t("fullName")}
                                <span className="ml-1 text-red-500">*</span>
                            </Label>
                            <Input
                                name="firstName"
                                value={formData.firstName}
                                onChange={handleInputChange}
                                placeholder={t("enterFullName")}
                                className="h-14 rounded-lg border newBorderColor primaryBackgroundBg px-4 text-base brandColor placeholder:text-gray-500 focus-visible:ring-0 focus:!primaryBorderColor"
                                required
                            />
                        </div>
                        <div className="flex flex-col gap-2">
                            <Label className="mb-2 flex items-center gap-1 text-base font-medium brandColor">
                                {t("emailAddress")}
                                <span className="ml-1 text-red-500">*</span>
                            </Label>
                            <Input
                                name="email"
                                type="email"
                                value={formData.email}
                                onChange={handleInputChange}
                                disabled={profileData?.logintype != "1"}
                                placeholder={t("enterEmail")}
                                className="h-14 rounded-lg border newBorderColor primaryBackgroundBg px-4 text-base brandColor placeholder:text-gray-500 focus-visible:ring-0 focus:!primaryBorderColor"
                                required
                            />
                        </div>
                    </div>

                    <div className="grid grid-cols-1 gap-4 lg:grid-cols-2">
                        <div className="flex flex-col gap-2">
                            <Label className="mb-2 flex items-center gap-1 text-base font-medium brandColor">
                                {t("phoneNumber")}
                                <span className="ml-1 text-red-500">*</span>
                            </Label>
                            <PhoneInput
                                country={process.env.NEXT_PUBLIC_DEFAULT_COUNTRY?.toLowerCase()}
                                enableAreaCodes
                                enableSearch
                                value={formData.phone}
                                onChange={handlePhoneNumberChange}
                                containerClass="w-full"
                                inputClass="!w-full !h-14 !rounded-r-lg !border !newBorderColor !primaryBackgroundBg p-4 !text-base !brandColor placeholder:!text-gray-500 placeholder:p-3"
                                buttonClass="!h-14 !rounded-l-lg !border !newBorderColor !primaryBackgroundBg"
                                disabled={profileData?.logintype == "1"}
                                inputProps={{ name: "phone", id: "phone", required: true }}
                            />
                        </div>
                        <div className="flex flex-col gap-2">
                            <Label className="mb-2 flex items-center gap-1 text-base font-medium brandColor">
                                {t("location")}
                                <span className="ml-1 text-red-500">*</span>
                            </Label>
                            <div className="flex items-end gap-4">
                                <CustomLocationAutocomplete
                                    value={formData.location}
                                    onChange={handleInputChange}
                                    onPlaceSelect={handlePlaceSelect}
                                    placeholder={t("searchLocation")}
                                    className="h-14 rounded-lg border newBorderColor primaryBackgroundBg px-4 text-base brandColor placeholder:text-gray-500 focus:outline-none focus:!primaryBorderColor"
                                    debounceMs={1000}
                                    maxResults={10}
                                    inputProps={{ name: "location" }}
                                />
                                <Button
                                    type="button"
                                    variant="outline"
                                    size="icon"
                                    aria-label={t("selectLocation")}
                                    className="h-14 w-14 shrink-0 rounded-lg border newBorderColor primaryBackgroundBg leadColor"
                                    onClick={() => setIsLocationModalOpen(true)}
                                >
                                    <HiOutlineMapPin className="shrink-0 size-8" />
                                </Button>
                            </div>
                        </div>
                    </div>

                    <div className="flex flex-col gap-2">
                        <Label className="mb-2 flex items-center gap-1 text-base font-medium brandColor">
                            {t("address")}
                            <span className="ml-1 text-red-500">*</span>
                        </Label>
                        <Textarea
                            name="address"
                            value={formData.address}
                            onChange={handleInputChange}
                            placeholder={t("enterYourAddress")}
                            className="min-h-32 resize-none rounded-lg border newBorderColor primaryBackgroundBg px-4 py-4 text-base brandColor placeholder:text-gray-500 focus-visible:!primaryBorderColor"
                            required
                        />
                    </div>

                </section>

                <div className="flex justify-end">
                    <Button
                        type="submit"
                        className="h-12 rounded-lg brandBg px-6 py-3 text-base font-medium hover:primaryBg text-white"
                        disabled={isSubmitting}
                    >
                        {isSubmitting ? (
                            <span className="flex items-center gap-2">
                                <ButtonLoader />
                                {t("updating")}
                            </span>
                        ) : (
                            t("updateProfile")
                        )}
                    </Button>
                </div>
            </div>

            <LocationPickerModal
                isOpen={isLocationModalOpen}
                onClose={() => setIsLocationModalOpen(false)}
                onLocationSelect={handleLocationFromModal}
                initialLocation={{
                    location: formData.location,
                    latitude: formData.latitude,
                    longitude: formData.longitude,
                    city: formData.city,
                    state: formData.state,
                    country: formData.country,
                }}
            />
        </form>
    );
};

export default UserProfile;
