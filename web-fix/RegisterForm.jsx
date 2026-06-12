import { useState } from 'react';
import ButtonLoader from '../ui/loaders/ButtonLoader';
import { FaEye, FaEyeSlash } from 'react-icons/fa';
import { isRTL } from '@/utils/helperFunction';
import { useTranslation } from '../context/TranslationContext';
import IndiaPhoneInput from './IndiaPhoneInput';

const RegisterForm = ({
    registerFormData,
    handleRegisterInputChange,
    handleRegisterPhoneChange,
    handleRegisterUser,
    handleSignIn,
    showLoader,
    isMobileReq
}) => {
    const t = useTranslation();
    const isRtl = isRTL();

    const [showPassword, setShowPassword] = useState(false);
    const [showConfirmPassword, setShowConfirmPassword] = useState(false);

    const onPhoneChange = (normalized) => {
        handleRegisterPhoneChange(normalized.number, {
            dialCode: normalized.countryCode,
        });
    };

    return (
        <form onSubmit={handleRegisterUser}>
            <div className="flex w-full flex-col justify-center gap-3 p-3 sm:gap-4 sm:p-3 md:p-4">
                <div className="flex flex-col gap-1 sm:gap-2">
                    <label htmlFor="name" className="text-sm font-medium sm:text-base">
                        {t("name")}
                        <span className="ms-1 text-red-600">*</span>
                    </label>
                    <input
                        type="text"
                        name="name"
                        id="name"
                        placeholder={t("enterYourName")}
                        className="primaryBackgroundBg w-full rounded border-none p-2 text-sm outline-none sm:p-[10px] sm:text-base md:text-base"
                        value={registerFormData?.name}
                        onChange={handleRegisterInputChange}
                        required
                    />
                </div>
                <div className="flex flex-col gap-1 sm:gap-2">
                    <label htmlFor="email" className="text-sm font-medium sm:text-base">
                        {t("email")}
                        {!isMobileReq && <span className="ms-1 text-red-600">*</span>}
                    </label>
                    <input
                        type="text"
                        name="email"
                        id="email"
                        placeholder={t("enterEmail")}
                        className="primaryBackgroundBg w-full rounded border-none p-2 text-sm outline-none sm:p-[10px] sm:text-base md:text-base"
                        value={registerFormData?.email}
                        onChange={handleRegisterInputChange}
                        required={!isMobileReq}
                    />
                </div>
                <div className="flex flex-col gap-1 sm:gap-2">
                    <label htmlFor="phone" className="text-sm font-medium sm:text-base">
                        {t("phoneNumber")}
                        {isMobileReq && <span className="ms-1 text-red-600">*</span>}
                    </label>
                    <div className="mobile-number">
                        <IndiaPhoneInput
                            value={{
                                number: registerFormData?.phone,
                                countryCode: registerFormData?.countryCode,
                            }}
                            onChange={onPhoneChange}
                            id="phone"
                            name="phone"
                            required={isMobileReq}
                        />
                    </div>
                </div>

                <div className="flex flex-col gap-1 sm:gap-2">
                    <label
                        htmlFor="password"
                        className="text-sm font-medium sm:text-base"
                    >
                        {t("password")}
                        <span className="ms-1 text-red-600">*</span>
                    </label>
                    <div className="relative">
                        <input
                            type={showPassword ? "text" : "password"}
                            name="password"
                            id="password"
                            placeholder={t("enterYourPassword")}
                            className="primaryBackgroundBg w-full rounded border-none p-2 text-sm outline-none sm:p-[10px] sm:text-base md:text-base"
                            value={registerFormData?.password}
                            onChange={handleRegisterInputChange}
                            required
                        />
                        <button
                            type="button"
                            className={`absolute w-8 h-8 md:w-10 md:h-10 flex items-center justify-center primaryBackgroundBg ${isRtl ? "left-1 top-1/2 -translate-y-1/2" : "right-2 top-1/2 -translate-y-1/2 sm:right-1"}`}
                            onClick={() => setShowPassword(!showPassword)}
                        >
                            {showPassword ? (
                                <FaEyeSlash className="h-4 w-4 hover:opacity-100 sm:h-5 sm:w-5" />
                            ) : (
                                <FaEye className="h-4 w-4 hover:opacity-100 sm:h-5 sm:w-5" />
                            )}
                        </button>
                    </div>
                    <div className="flex flex-col gap-1 sm:gap-2">
                        <label
                            htmlFor="confirmPassword"
                            className="text-sm font-medium sm:text-base"
                        >
                            {t("confirmPassword")}
                            <span className="ms-1 text-red-600">*</span>
                        </label>
                        <div className="relative">
                            <input
                                type={showConfirmPassword ? "text" : "password"}
                                name="confirmPassword"
                                id="confirmPassword"
                                placeholder={t("enterConfirmPassword")}
                                className="primaryBackgroundBg w-full rounded border-none p-2 text-sm outline-none sm:p-[10px] sm:text-base md:text-base"
                                value={registerFormData?.confirmPassword}
                                onChange={handleRegisterInputChange}
                                required
                            />
                            <button
                                type="button"
                                className={`absolute w-8 h-8 md:w-10 md:h-10 flex items-center justify-center primaryBackgroundBg ${isRtl ? "left-1 top-1/2 -translate-y-1/2" : "right-2 top-1/2 -translate-y-1/2 sm:right-1"}`}
                                onClick={() => setShowConfirmPassword(!showConfirmPassword)}
                            >
                                {showConfirmPassword ? (
                                    <FaEyeSlash className="h-4 w-4  hover:opacity-100 sm:h-5 sm:w-5" />
                                ) : (
                                    <FaEye className="h-4 w-4  hover:opacity-100 sm:h-5 sm:w-5" />
                                )}
                            </button>
                        </div>
                    </div>
                </div>
                <button
                    type="submit"
                    className="brandBg primaryTextColor w-full rounded-lg p-2 text-sm transition-all hover:primaryBg sm:p-[10px] sm:text-base md:text-base"
                >
                    {showLoader ? (
                        <ButtonLoader />
                    ) : (
                        t("register")
                    )}
                </button>
                <div className="flex flex-wrap justify-center text-sm sm:text-base">
                    <p className="me-1 text-[#555]">{t("alreadyHaveAccount")}</p>
                    <div
                        className="secondryTextColor font-bold transition-colors hover:cursor-pointer"
                        onClick={handleSignIn}
                    >
                        {t("signIn")}
                    </div>
                </div>
            </div>
        </form>
    );
};

export default RegisterForm;
