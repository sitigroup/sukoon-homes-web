"use client";

import { useEffect, useState } from 'react';
import dynamic from 'next/dynamic';
import { Input } from "@/components/ui/input";
import { Textarea } from "@/components/ui/textarea";
import { Button } from "@/components/ui/button";
import ImageWithPlaceholder from '../image-with-placeholder/ImageWithPlaceholder';
import { useTranslation } from '../context/TranslationContext';
import { BiSolidImageAdd } from "react-icons/bi";
import { FaUser } from "react-icons/fa";

const LocationPickerModal = dynamic(() => import('./LocationPickerModal'), {
    ssr: false,
    loading: () => null
});

import toast from 'react-hot-toast';
import { extractAddressComponents, validateAllSocialMediaUrls } from '@/utils/helperFunction';
import { useSelector, useDispatch } from 'react-redux';
import { fetchAgentProfileApi, updateAgentProfileApi, getMapDetailsApi } from '@/api/apiRoutes';
import { updateUserProfile } from '@/redux/slices/authSlice';
import ButtonLoader from '../ui/loaders/ButtonLoader';
import PhoneInput from "react-phone-input-2";
import { PhoneNumberUtil } from "google-libphonenumber";
import Swal from 'sweetalert2';
import { Label } from '../ui/label';

const TrustProfileSection = dynamic(
    () => import("@/plugins/trust-verification/TrustProfileSection"),
    { ssr: false, loading: () => null },
);

const AgentProfile = () => {
    const t = useTranslation();
    const dispatch = useDispatch();

    const userData = useSelector(state => state.User?.data);
    const agentProfile = userData?.agent_profile || null;
    const webSettings = useSelector(state => state.WebSetting?.data);

    useEffect(() => {
        // Fetch address from coordinates if latitude and longitude are available
        if (agentProfile?.latitude && agentProfile?.longitude) {
            fetchAddressFromCoordinates(agentProfile.latitude, agentProfile.longitude);
        }
    }, [userData]);

    useEffect(() => {
        fetchAgentProfile();
    }, []);

    const fetchAgentProfile = async () => {
        try {
            const res = await fetchAgentProfileApi();
            if (res.data) {
                const agentProfile = res.data;
                setFormData((prev) => {
                    return {
                        ...prev,
                        firstName: agentProfile?.agent_name || '',
                        email: agentProfile?.agent_email || '',
                        phone: agentProfile?.country_code && agentProfile?.agent_mobile ? `${agentProfile?.country_code}${agentProfile?.agent_mobile}` : agentProfile?.agent_mobile || '',
                        about_me: agentProfile?.about_me || '',
                        facebook_id: agentProfile?.facebook_id || '',
                        instagram_id: agentProfile?.instagram_id || '',
                        youtube_id: agentProfile?.youtube_id || '',
                        twiiter_id: agentProfile?.twitter_id || '',
                        profile: agentProfile?.agent_profile_photo || '',
                        profilePreview: agentProfile?.agent_profile_photo || ''
                    }
                })
            }
        } catch (error) {
            console.error("Error fetching user data:", error);
        }
    };
    // Function to fetch address from coordinates using API-based reverse geocoding
    const fetchAddressFromCoordinates = async (lat, lng) => {
        try {
            const response = await getMapDetailsApi({
                latitude: lat.toString(),
                longitude: lng.toString(),
                place_id: ""
            });


            if (response?.error === false && response?.data?.result && response.data.result) {
                // Get the first (most specific) result
                const firstResult = response.data.result;
                const addressData = extractAddressComponents(firstResult);
                const formattedAddress = addressData.formattedAddress || firstResult.formatted_address || `${lat.toFixed(6)}, ${lng.toFixed(6)}`;

                setFormData(prev => ({
                    ...prev,
                    location: formattedAddress,
                    city: addressData.city || prev.city,
                    state: addressData.state || prev.state,
                    country: addressData.country || prev.country
                }));
            } else {
                console.warn("No valid results from reverse geocoding API:", response);
                // Fallback to coordinate display
                const coordString = `${lat.toFixed(6)}, ${lng.toFixed(6)}`;
                setFormData(prev => ({
                    ...prev,
                    location: coordString
                }));
            }
        } catch (error) {
            console.error("Error fetching address:", error);
            // Fallback to coordinate display on error
            const coordString = `${lat.toFixed(6)}, ${lng.toFixed(6)}`;
            setFormData(prev => ({
                ...prev,
                location: coordString
            }));
            toast.error(t("locationError"));
        }
    };
    const [isLoadingLocation, setIsLoadingLocation] = useState(false);
    const [mapError, setMapError] = useState(false);
    const [isSubmitting, setIsSubmitting] = useState(false);
    const [isLocationModalOpen, setIsLocationModalOpen] = useState(false);


    // Initialize with userData directly in state definition
    const [formData, setFormData] = useState({
        firstName: agentProfile?.agent_name || '',
        email: agentProfile?.email || '',
        phone: agentProfile?.agent_country_code && agentProfile?.mobile ? `${agentProfile?.agent_country_code}${agentProfile?.mobile}` : agentProfile?.mobile || '',
        countryCode: agentProfile?.agent_country_code || '',
        location: '',
        latitude: agentProfile?.latitude || null,
        longitude: agentProfile?.longitude || null,
        address: agentProfile?.agent_address || '',
        city: agentProfile?.city || '',
        state: agentProfile?.state || '',
        country: agentProfile?.country || '',
        about_me: agentProfile?.about_me || '',
        facebook_id: agentProfile?.facebook_id || '',
        instagram_id: agentProfile?.instagram_id || '',
        youtube_id: agentProfile?.youtube_id || '',
        twiiter_id: agentProfile?.twitter_id || '',
        profile: agentProfile?.agent_profile_photo || '',
        profilePreview: agentProfile?.agent_profile_photo || '',
    });

    // Derive the preview src from formData.profilePreview (single source of truth)
    // - googleusercontent.com URL  → displayed as-is, skipped in API payload
    // - File object selected        → object URL used for preview, File sent to API
    // - any other string URL        → displayed as-is, skipped in API payload (already on server)

    // Handle input changes
    const handleInputChange = (e) => {
        const { name, value } = e.target;
        setFormData(prev => ({
            ...prev,
            [name]: value
        }));
    };

    // Handle image upload — store the File in `profile`, object URL in `profilePreview`
    const handleImageUpload = (e) => {
        const file = e.target.files[0];
        if (file) {
            const objectUrl = URL.createObjectURL(file);
            setFormData(prev => ({
                ...prev,
                profile: file,
                profilePreview: objectUrl
            }));
        }
    };

    // Handle place selection from custom autocomplete
    const handlePlaceSelect = (placeData, placeDetails) => {
        try {
            if (placeData) {
                const { city, state, country, formattedAddress } = extractAddressComponents(placeData);
                const lat = placeData.latitude
                const lng = placeData.longitude;

                if (lat && lng) {
                    setFormData(prev => ({
                        ...prev,
                        location: formattedAddress,
                        city: city,
                        state: state,
                        country: country,
                        latitude: lat,
                        longitude: lng
                    }));
                }
            }
        } catch (error) {
            toast.error(t("locationError"));
        }
    };

    // Handle location selection from modal
    const handleLocationFromModal = (locationData) => {
        setFormData(prev => ({
            ...prev,
            location: locationData.location,
            city: locationData.city,
            state: locationData.state,
            country: locationData.country,
            latitude: locationData.latitude,
            longitude: locationData.longitude
        }));
    };

    // Open location picker modal
    const openLocationModal = () => {
        setIsLocationModalOpen(true);
    };

    // Get current location
    const getCurrentLocation = () => {
        setIsLoadingLocation(true);
        if ("geolocation" in navigator) {
            navigator.geolocation.getCurrentPosition(
                async (position) => {
                    try {
                        const { latitude, longitude } = position.coords;

                        // Use API-based reverse geocoding
                        const response = await getMapDetailsApi({
                            latitude: latitude.toString(),
                            longitude: longitude.toString(),
                            place_id: ""
                        });


                        if (response?.error === false && response?.data?.result) {
                            const firstResult = response.data.result;
                            const addressData = extractAddressComponents(firstResult);
                            const formattedAddress = response.data.result?.formatted_address || firstResult.formatted_address || `${latitude.toFixed(6)}, ${longitude.toFixed(6)}`;

                            setFormData(prev => ({
                                ...prev,
                                location: formattedAddress,
                                city: addressData.city || prev.city,
                                state: addressData.state || prev.state,
                                country: addressData.country || prev.country,
                                latitude,
                                longitude
                            }));
                        } else {
                            console.warn("No valid results from current location reverse geocoding");
                            // Fallback to coordinate display
                            const coordString = `${latitude.toFixed(6)}, ${longitude.toFixed(6)}`;
                            setFormData(prev => ({
                                ...prev,
                                location: coordString,
                                latitude,
                                longitude
                            }));
                        }
                        setIsLoadingLocation(false);
                    } catch (error) {
                        console.error("Error in reverse geocoding:", error);
                        // Still update coordinates even if reverse geocoding fails
                        const { latitude, longitude } = position.coords;
                        const coordString = `${latitude.toFixed(6)}, ${longitude.toFixed(6)}`;
                        setFormData(prev => ({
                            ...prev,
                            location: coordString,
                            latitude,
                            longitude
                        }));
                        toast.error(t("locationError"));
                        setIsLoadingLocation(false);
                    }
                },
                (error) => {
                    console.error("Geolocation error:", error);
                    toast.error(t("locationPermissionDenied"));
                    setIsLoadingLocation(false);
                },
                {
                    enableHighAccuracy: true,
                    timeout: 5000,
                    maximumAge: 0
                }
            );
        } else {
            toast.error(t("locationNotSupported"));
            setIsLoadingLocation(false);
        }
    };
    const phoneUtil = PhoneNumberUtil.getInstance();
    // Handle form submission
    const handleSubmit = async (e) => {
        e?.preventDefault();
        setIsSubmitting(true);

        if (webSettings?.demo_mode && userData?.is_demo_user) {
            Swal.fire({
                title: t("oops"),
                text: t("notAllowdDemo"),
                icon: "warning",
                showCancelButton: false,
                customClass: {
                    confirmButton: 'Swal-confirm-buttons',
                    cancelButton: "Swal-cancel-buttons"
                },
                confirmButtonText: t("ok"),
            });
            setIsSubmitting(false);
            return false;
        }


        // Only require a profile image if we don't already have one
        if (!formData.profilePreview) {
            toast.error(t("profilePictureRequired"));
            setIsSubmitting(false);
            return;
        }
        if (!formData.phone) {
            toast.error(t("phoneNumberIsRequired"));
            setIsSubmitting(false);
            return;
        }

        // Validate social media URLs before submission
        const socialMediaValidation = validateAllSocialMediaUrls(formData);
        if (!socialMediaValidation.isValid) {
            toast.error(t("invalidSocialMediaUrl"));
            setIsSubmitting(false);
            return;
        }

        // Remove any non-digit characters from the country code
        const countryCodeDigitsOnly = formData?.countryCode?.replace(/\D/g, "");

        // Check if the entered number starts with the selected country code
        const startsWithCountryCode = formData?.phone?.startsWith(countryCodeDigitsOnly);

        // If the number starts with the country code, remove it
        const formattedNumber = startsWithCountryCode
            ? formData.phone.substring(countryCodeDigitsOnly.length)
            : formData.phone;

        // Create the full phone number with country code for validation
        const rawPhoneNumber = `+${formData?.countryCode}${formattedNumber}`;

        let phone, isValidPhoneNumber;
        try {
            phone = phoneUtil?.parseAndKeepRawInput(rawPhoneNumber, "ZZ");
            isValidPhoneNumber = phoneUtil.isValidNumber(phone);

            if (!isValidPhoneNumber) {
                toast.error(t("invalidPhoneNumber"));
                setIsSubmitting(false);
                return;
            }
        } catch (error) {
            if (error?.message.includes("Invalid country calling code")) {
                // Handle specific error for invalid phone number format
                console.error("Invalid phone number format:", error);
                toast.error(t("invalidCountryCode"));
                setIsSubmitting(false);
                return;
            }
            toast.error(t("invalidPhoneNumberFormat"));
            setIsSubmitting(false);
            return;

        }
        const countryCode = phone.getCountryCode();
        const phonenum = phone.getNationalNumber().toString();
        // Build payload — only include agent_profile_photo when the user selected a new File.
        // If profile is still a URL string (googleusercontent.com or any other server URL)
        // we skip it entirely so the server keeps the existing photo.
        const isNewFile = formData.profile instanceof File;
        const payload = {
            ...(isNewFile ? { agent_profile_photo: formData.profile } : {}),
            agent_name: formData.firstName,
            agent_email: formData.email,
            country_code: `+${countryCode}`,
            agent_mobile: phonenum,
            location: formData.location,
            latitude: formData.latitude,
            longitude: formData.longitude,
            agent_address: formData.address,
            city: formData.city,
            state: formData.state,
            country: formData.country,
            about_me: formData.about_me,
            facebook_id: formData.facebook_id,
            instagram_id: formData.instagram_id,
            youtube_id: formData.youtube_id,
            twitter_id: formData.twiiter_id,
            agent_country_code: formData.countryCode ? formData?.countryCode?.replace(/\D/g, "") : "",
        };

        try {
            const res = await updateAgentProfileApi({ ...payload });

            // Update Redux store with the new profile data
            if (res.data) {
                // Use the fresh URL returned by the server (or keep the existing preview)
                const updatedPhotoUrl = res.data.agent_profile_photo || formData.profilePreview;
                const data = {
                    ...userData,
                    agent_profile: {
                        ...payload,
                        agent_profile_photo: updatedPhotoUrl,
                    }
                };
                // Sync profilePreview with the server-returned URL after a successful update
                setFormData(prev => ({
                    ...prev,
                    profile: updatedPhotoUrl,
                    profilePreview: updatedPhotoUrl
                }));
                dispatch(updateUserProfile({
                    data: data
                }));
            }
            toast.success(t("profileUpdatedSuccessfully"));
        } catch (error) {
            console.error(error);
            toast.error(t("profileUpdateFailed"));
        } finally {
            setIsSubmitting(false);
        }
    };

    // Required field label component
    const RequiredLabel = ({ children }) => (
        <p className="text-sm mb-2 flex items-center gap-1">
            {children}
            <span className="text-red-500">*</span>
        </p>
    );

    const handlePhoneNumberChange = (value, data) => {
        setFormData({
            ...formData,
            phone: value,
            countryCode: data?.dialCode,
        });
    };

    return (
        <form onSubmit={handleSubmit} className="w-full max-w-full space-y-2 sm:space-y-4">
            <h1 className="text-xl sm:text-2xl font-semibold px-2 sm:px-0">{t("myProfile")}</h1>
            <div className="px-2 sm:px-0">
                <TrustProfileSection />
            </div>
            {/* Main Grid */}
            <div className="grid grid-cols-1 lg:grid-cols-2 gap-4 sm:gap-6 mb-6">
                {/* Personal Info Section */}
                <div className="bg-white rounded-lg p-4 sm:p-6 shadow-sm">
                    <h2 className="text-base sm:text-lg font-medium mb-4 sm:mb-6 border-b border-gray-200 pb-2">{t("personalInfo")}</h2>

                    {/* Profile Image Upload */}
                    <div className="mb-4 sm:mb-6">
                        <RequiredLabel>{t("profilePicture")}</RequiredLabel>
                        <div className="flex flex-col sm:flex-row items-center sm:items-start gap-4">
                            <div
                                className={`relative h-[100px] w-[100px] sm:h-[76px] sm:w-[76px] overflow-hidden rounded-lg bg-[#F7F7F7] border-2 border-dashed ${formData.profilePreview ? "border-none" : "border-gray-400"} focus-within:primaryBorderColor focus-within:ring-2 focus-within:ring-primary/20 transition-all duration-200`}
                                tabIndex="0"
                                role="button"
                                // onClick={() => document.getElementById('profileImage').click()}
                                onKeyDown={(e) => e.key === 'Enter' && document.getElementById('profileImage').click()}
                            >
                                {formData.profilePreview ? (
                                    <ImageWithPlaceholder
                                        src={formData.profilePreview}
                                        alt="Profile"
                                        className="h-full w-full object-cover"
                                        loading="lazy"
                                    />
                                ) : (
                                    <div className="absolute inset-0 flex items-center justify-center text-gray-400">
                                        <FaUser size={30} />
                                    </div>
                                )}
                                {/* Overlay on hover */}
                                <div
                                    className="absolute inset-0 bg-black bg-opacity-50 flex items-center justify-center opacity-0 hover:opacity-100 transition-opacity cursor-pointer"
                                    onClick={() => document.getElementById('profileImage').click()}
                                >
                                    <BiSolidImageAdd size={24} className="text-white" />
                                </div>
                            </div>
                            <div className="flex-1 text-center sm:text-left">
                                <Button
                                    type="button"
                                    variant="outline"
                                    className="h-10 px-4 secondaryTextBg !text-white border-0 text-sm font-normal hover:primaryBg"
                                    onClick={() => document.getElementById('profileImage').click()}
                                >
                                    <span className="flex items-center gap-2">
                                        <BiSolidImageAdd size={20} />
                                        {t("uploadProfile")}
                                    </span>
                                </Button>
                                <input
                                    id="profileImage"
                                    name="profileImage"
                                    type="file"
                                    accept="image/*"
                                    onChange={handleImageUpload}
                                    className="absolute opacity-0 w-0 h-0"
                                />
                                <p className="text-[#FF0000] text-xs mt-2">{t("profilePictureNote")}</p>
                            </div>
                        </div>
                    </div>

                    {/* Form Fields */}
                    <div className="grid grid-cols-1 gap-4">
                        <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <RequiredLabel>{t("fullName")}</RequiredLabel>
                                <Input
                                    name="firstName"
                                    value={formData.firstName}
                                    onChange={handleInputChange}
                                    placeholder={t("enterFirstName")}
                                    className="h-10 sm:h-12 bg-[#F7F7F7] border-0 rounded focus-visible:!primaryBorderColor focus-visible:!ring-2 focus-visible:!ring-primary/20 transition-all duration-200"
                                    required
                                />
                            </div>
                            <div>
                                <RequiredLabel>{t("email")}</RequiredLabel>
                                <Input
                                    name="email"
                                    type="email"
                                    value={formData.email}
                                    onChange={handleInputChange}
                                    disabled={userData?.logintype != "1"}
                                    placeholder={t("enterEmail")}
                                    className="h-10 sm:h-12 bg-[#F7F7F7] border-0 rounded focus-visible:!primaryBorderColor focus-visible:!ring-2 focus-visible:!ring-primary/20 transition-all duration-200 disabled:cursor-not-allowed disabled:opacity-70"
                                    required
                                />
                            </div>
                        </div>

                        <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div className=''>
                                <RequiredLabel>{t("phoneNumber")}</RequiredLabel>
                                <div className="mobile-number">
                                    <PhoneInput
                                        country={process.env.NEXT_PUBLIC_DEFAULT_COUNTRY?.toLowerCase()}
                                        enableAreaCodes={true}
                                        inputProps={{
                                            name: 'phone',
                                            id: 'phone',
                                            required: true,
                                            autoFocus: false,
                                        }}
                                        enableSearch={true}
                                        value={formData?.phone}
                                        onChange={handlePhoneNumberChange}
                                        containerClass="w-full"
                                        inputClass="!primaryBackgroundBg !w-full !rounded !h-11 !border !newBorderColor"
                                        dropdownClass="!primaryBackgroundBg"
                                        buttonClass="!primaryBackgroundBg !w-10 h-11 !rounded-tl !rounded-bl !newBorderColor"
                                        disabled={userData?.logintype == "1"}
                                    />
                                </div>
                            </div>
                            {/* <div>
                                <label htmlFor='location' className='text-sm mb-2'>{t("location")}</label>
                                <div className="flex gap-2 w-full custom-search-box">
                                    <CustomLocationAutocomplete
                                        value={formData.location}
                                        onChange={handleInputChange}
                                        onPlaceSelect={handlePlaceSelect}
                                        placeholder={t("searchLocation")}
                                        className="h-10 p-2 sm:h-12 !w-full bg-[#F7F7F7] border-0 rounded focus:outline-none transition-all duration-200 focus-visible:!primaryBorderColor focus-visible:!ring-2 focus-visible:!ring-primary/20"
                                        showFindMyLocation={true}
                                        debounceMs={1000}
                                        maxResults={10}
                                        inputProps={{
                                            name: "location"
                                        }}
                                    />
                                    <TooltipProvider>
                                        <Tooltip>
                                            <TooltipTrigger asChild>
                                                <Button
                                                    type="button"
                                                    variant="outline"
                                                    size="icon"
                                                    className="h-10 sm:h-12 w-10 sm:w-12 bg-[#F7F7F7] border-0 hover:bg-[#F0F0F0]"
                                                    onClick={openLocationModal}
                                                >
                                                    <HiOutlineMapPin className="w-4 h-4 sm:w-5 sm:h-5" />
                                                </Button>
                                            </TooltipTrigger>
                                            <TooltipContent>
                                                <p>{t("selectLocation")}</p>
                                            </TooltipContent>
                                        </Tooltip>
                                    </TooltipProvider>
                                </div>
                            </div> */}
                        </div>

                        <div>
                            <RequiredLabel>{t("address")}</RequiredLabel>
                            <Textarea
                                name="address"
                                value={formData.address}
                                onChange={handleInputChange}
                                placeholder={t("enterYourAddress")}
                                className="min-h-[100px] sm:min-h-[120px] bg-[#F7F7F7] border-0 rounded resize-none focus-visible:!primaryBorderColor focus-visible:!ring-2 focus-visible:!ring-primary/20 transition-all duration-200"
                                required
                            />
                        </div>
                    </div>
                </div>

                {/* About Section */}
                <div className="bg-white rounded-lg p-4 sm:p-6 shadow-sm">
                    <h2 className="text-base sm:text-lg font-medium mb-4 sm:mb-6 border-b border-gray-200 pb-2">{t("about")}</h2>
                    <Label htmlFor="about_me">{t("aboutMe")}</Label>
                    <Textarea
                        id="about_me"
                        name="about_me"
                        value={formData.about_me}
                        onChange={handleInputChange}
                        placeholder={t("enterAboutMe")}
                        className="min-h-[250px] sm:min-h-[400px] bg-[#F7F7F7] border-0 rounded resize-none focus-visible:!primaryBorderColor focus-visible:!ring-2 focus-visible:!ring-primary/20 transition-all duration-200"
                    />
                </div>
            </div>

            {/* Social Media Section */}
            <div className="bg-white rounded-lg p-4 sm:p-6 shadow-sm mb-6">
                <h2 className="text-base sm:text-lg font-medium mb-4 sm:mb-6 border-b border-gray-200 pb-2">{t("socialMedia")}</h2>
                <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <p className="text-sm mb-2">{t("facebook")}</p>
                        <Input
                            name="facebook_id"
                            value={formData.facebook_id}
                            onChange={handleInputChange}
                            placeholder={t("enterFacebookUrl")}
                            className="h-10 sm:h-12 bg-[#F7F7F7] border-0 rounded focus-visible:!primaryBorderColor focus-visible:!ring-2 focus-visible:!ring-primary/20 transition-all duration-200"
                        />
                    </div>
                    <div>
                        <p className="text-sm mb-2">{t("instagram")}</p>
                        <Input
                            name="instagram_id"
                            value={formData.instagram_id}
                            onChange={handleInputChange}
                            placeholder={t("enterInstagramUrl")}
                            className="h-10 sm:h-12 bg-[#F7F7F7] border-0 rounded focus-visible:!primaryBorderColor focus-visible:!ring-2 focus-visible:!ring-primary/20 transition-all duration-200"
                        />
                    </div>
                    <div>
                        <p className="text-sm mb-2">{t("youtube")}</p>
                        <Input
                            name="youtube_id"
                            value={formData.youtube_id}
                            onChange={handleInputChange}
                            placeholder={t("enterYoutubeUrl")}
                            className="h-10 sm:h-12 bg-[#F7F7F7] border-0 rounded focus-visible:!primaryBorderColor focus-visible:!ring-2 focus-visible:!ring-primary/20 transition-all duration-200"
                        />
                    </div>
                    <div>
                        <p className="text-sm mb-2">{t("twitter")}</p>
                        <Input
                            name="twiiter_id"
                            value={formData.twiiter_id}
                            onChange={handleInputChange}
                            placeholder={t("enterTwitterUrl")}
                            className="h-10 sm:h-12 bg-[#F7F7F7] border-0 rounded focus-visible:!primaryBorderColor focus-visible:!ring-2 focus-visible:!ring-primary/20 transition-all duration-200"
                        />
                    </div>
                </div>
            </div>

            {/* Update Profile Button */}
            <div className="flex justify-center sm:justify-end">
                <Button
                    type="submit"
                    className="h-10 sm:h-12 w-full sm:w-auto px-4 sm:px-8 bg-black text-white rounded hover:opacity-90"
                    disabled={isSubmitting}
                >
                    {isSubmitting ? (
                        <div className='flex items-center gap-2'>
                            <ButtonLoader />
                            {t("updating")}
                        </div>
                    ) : (
                        t("updateProfile")
                    )}
                </Button>
            </div>

            {/* Location Picker Modal */}
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
                    country: formData.country
                }}
            />
        </form>
    );
};

export default AgentProfile;