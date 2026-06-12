import {
  checkPhoneNoPasswordExistsApi,
  getOTPApi,
  userRegisterApi,
  userSignup,
  verifyOTP,
  updatePhoneNoPasswordApi,
  updateEmailPasswordApi,
} from "@/api/apiRoutes";
import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogHeader,
  DialogTitle,
} from "@/components/ui/dialog";
import { setAuth, setJWTToken } from "@/redux/slices/authSlice";
import FirebaseData from "@/utils/Firebase";
import { handleFirebaseAuthError } from "@/utils/helperFunction";
import {
  GoogleAuthProvider,
  RecaptchaVerifier,
  signInWithPhoneNumber,
  signInWithPopup,
} from "firebase/auth";
import { PhoneNumberUtil } from "google-libphonenumber";
import { useRouter } from "next/router";
import { useEffect, useState } from "react";
import toast from "react-hot-toast";
import { useDispatch, useSelector } from "react-redux";
import Swal from "sweetalert2";
import { useTranslation } from "../context/TranslationContext";
import { AiOutlineClose } from "react-icons/ai";
import PhoneLoginForm from "../forms/PhoneLoginForm";
import PhoneLoginPasswordForm from "../forms/PhoneLoginPasswordForm";
import RegisterTypeForm from "../forms/RegisterTypeForm";
import AuthFooter from "../reusable-components/AuthFooter";
import RegisterForm from "../forms/RegisterForm";
import OTPForm from "../forms/OTPForm";
import EmailLoginForm from "../forms/EmailLoginForm";
import ForgotPasswordForm from "../forms/ForgotPasswordForm";
import GoogleForm from "../forms/GoogleForm";
import { normalizePhoneInputChange, phonePartsForApi } from "@/utils/phoneFieldUtils";

const formatTime = (seconds) => {
  const minutes = Math.floor(seconds / 60);
  const secs = seconds % 60;
  return `${String(minutes).padStart(2, "0")}:${String(secs).padStart(2, "0")}`;
};

const LoginModal = ({ showLogin, setShowLogin }) => {
  const t = useTranslation();
  const router = useRouter();
  const dispatch = useDispatch();
  const { lang } = router?.query;
  const settingsData = useSelector((state) => state.WebSetting?.data);
  const isDemo = settingsData?.demo_mode;
  const CompanyName = settingsData?.company_name;

  const ShowPhoneLogin = settingsData?.number_with_otp_login === "1";
  const AllowSocialLogin = settingsData?.social_login === "1";
  const ShowEmailLogin = settingsData?.email_password_login === "1";
  // Show Google login only if social login is enabled
  const ShowGoogleLogin = AllowSocialLogin;
  const isFirebaseOtp = settingsData?.otp_service_provider === "firebase";
  const isTwilioOtp = settingsData?.otp_service_provider === "twilio";

  // Logic to determine login form
  const getInitialView = () => {
    // Google -> On, Email -> Off, PhoneLogin -> Off
    if (AllowSocialLogin && !ShowEmailLogin && !ShowPhoneLogin) {
      return {
        showPhoneLogin: false,
        showEmailContent: false,
        showEmailLoginForm: false,
        showRegisterOptions: false,
      }
    }
    // Google -> On, Email -> On, PhoneLogin -> Off
    if (AllowSocialLogin && ShowEmailLogin && !ShowPhoneLogin) {
      return {
        showPhoneLogin: false,
        showEmailContent: true,
        showEmailLoginForm: true,
        showRegisterOptions: false,
      }
    }
    // Google -> On, Email -> Off, PhoneLogin -> On
    if (AllowSocialLogin && !ShowEmailLogin && ShowPhoneLogin) {
      return {
        showPhoneLogin: true,
        showEmailContent: false,
        showEmailLoginForm: false,
        showRegisterOptions: false,
      }
    }
    // Google -> On, Email -> On, PhoneLogin -> On
    if (AllowSocialLogin && ShowEmailLogin && ShowPhoneLogin) {
      return {
        showPhoneLogin: true,
        showEmailContent: false,
        showEmailLoginForm: false,
        showRegisterOptions: false,
      }
    }
    // Google -> Off, Email -> On, PhoneLogin -> On
    if (!AllowSocialLogin && ShowEmailLogin && ShowPhoneLogin) {
      return {
        showPhoneLogin: true,
        showEmailContent: false,
        showEmailLoginForm: false,
        showRegisterOptions: false,
      }
    }
    // Google -> Off, Email -> On, PhoneLogin -> Off
    if (!AllowSocialLogin && ShowEmailLogin && !ShowPhoneLogin) {
      return {
        showPhoneLogin: false,
        showEmailContent: true,
        showEmailLoginForm: true,
        showRegisterOptions: false,
      }
    }
    // Google -> Off, Email -> Off, PhoneLogin -> On
    if (!AllowSocialLogin && !ShowEmailLogin && ShowPhoneLogin) {
      return {
        showPhoneLogin: true,
        showEmailContent: false,
        showEmailLoginForm: false,
        showRegisterOptions: false,
      }
    }
    return {
      showPhoneLogin: false,
      showEmailContent: false,
      showEmailLoginForm: false,
      showRegisterOptions: false,
    }
  }

  const initialView = getInitialView();

  const [showForgotPasswd, setShowForgotPasswd] = useState(false);
  const [showPhoneLogin, setShowPhoneLogin] = useState(initialView?.showPhoneLogin);
  const [showOTPContent, setShowOTPContent] = useState(false);
  const [showEmailContent, setShowEmailContent] = useState(initialView?.showEmailContent);
  const [showRegisterContent, setShowRegisterContent] = useState(false);
  const [showRegisterOptions, setShowRegisterOptions] = useState(initialView?.showRegisterOptions);
  const [showEmailLoginForm, setShowEmailLoginForm] = useState(initialView?.showEmailLoginForm);
  const [registerType, setRegisterType] = useState("");
  const [isMobileReq, setIsMobileReq] = useState(false)
  const [showPasswordInput, setShowPasswordInput] = useState(false); // Track if password input should be shown

  const { authentication } = FirebaseData();
  const FcmToken = useSelector((state) => state.WebSetting?.fcmToken);
  const DemoNumber = "911234567890";
  const DemoOTP = "123456";

  const [isEmailOtpEnabled, setIsEmailOtpEnabled] = useState(false); // State to track email OTP
  const [emailOtp, setEmailOtp] = useState(""); // Initialize as string for react-otp-input
  const [emailTimeLeft, setEmailTimeLeft] = useState(120); // 2 minutes in seconds
  const [isEmailCounting, setIsEmailCounting] = useState(false); // State to track email OTP counting
  const [emailReverify, setEmailReverify] = useState(false);
  const [forgotPasswordViaPhone, setForgotPasswordViaPhone] = useState(false);
  const [forgotPasswordViaEmail, setForgotPasswordViaEmail] = useState(false); // Flag for email forgot-password OTP flow
  const [forgotPasswordEmail, setForgotPasswordEmail] = useState(""); // Stores the email across forgot-password steps
  const [isPasswordUpdateFlow, setIsPasswordUpdateFlow] = useState(false); // Flag for user-exists-no-password flow
  const [firebaseAuthId, setFirebaseAuthId] = useState(""); // Store Firebase auth ID for password update

  const [phonenum, setPhonenum] = useState();
  const [value, setValue] = useState({
    number: isDemo ? DemoNumber : "",
    countryCode: isDemo ? "91" : "",
  });
  const {
    mobile: formattedNumber,
    country_code: countryCodeDigitsOnly,
  } = phonePartsForApi(value?.number, value?.countryCode);


  const phoneUtil = PhoneNumberUtil.getInstance();
  const [otp, setOTP] = useState(""); // Initialize as string for react-otp-input
  const [phonePassword, setPhonePassword] = useState("");
  const [confirmPhnPass, setConfirmPhnPass] = useState("")
  const [resetMobilePass, setResetMobilePass] = useState(false);
  const [showLoader, setShowLoader] = useState(false);
  const [timeLeft, setTimeLeft] = useState(120);
  const [isCounting, setIsCounting] = useState(false);


  const [registerFormData, setRegisterFormData] = useState({
    name: "",
    email: "",
    phone: "",
    password: "",
    confirmPassword: "",
    countryCode: "",
  });

  const [signInFormData, setSignInFormData] = useState({
    email: "",
    password: "",
  });

  // Handle countdown logic
  useEffect(() => {
    let timer;
    if (isCounting && timeLeft > 0) {
      timer = setInterval(() => {
        setTimeLeft((prev) => prev - 1);
      }, 1000);
    } else if (timeLeft === 0) {
      clearInterval(timer);
      setTimeLeft(0);
      setIsCounting(false);
    }
    return () => clearInterval(timer);
  }, [isCounting, timeLeft]);

  useEffect(() => {
    let timer;
    if (isEmailCounting && emailTimeLeft > 0) {
      timer = setInterval(() => {
        setEmailTimeLeft((prev) => prev - 1);
      }, 1000);
    } else if (emailTimeLeft === 0) {
      clearInterval(timer);
      setIsEmailCounting(false);
    }
    return () => clearInterval(timer);
  }, [isEmailCounting, emailTimeLeft]);

  useEffect(() => {
    if (showLogin) {
      generateRecaptcha();
    }
    return () => {
      recaptchaClear();
    };
  }, [showLogin]);

  const recaptchaClear = async () => {
    const recaptchaContainer = document.getElementById("recaptcha-container");
    if (window.recaptchaVerifier) {
      try {
        await window.recaptchaVerifier.clear();
        window.recaptchaVerifier = undefined;
      } catch (error) {
        console.error("Error clearing ReCAPTCHA verifier:", error);
      }
    }
    if (recaptchaContainer) {
      recaptchaContainer.innerHTML = "";
      console.info("ReCAPTCHA container cleared");
    }
  };

  const generateRecaptcha = async () => {
    await recaptchaClear();
    const recaptchaContainer = document.getElementById("recaptcha-container");
    if (!recaptchaContainer) {
      console.error("Container element 'recaptcha-container' not found.");
      return null;
    }
    try {
      window.recaptchaVerifier = new RecaptchaVerifier(
        authentication,
        recaptchaContainer,
        {
          size: "invisible",
        },
      );
      return window.recaptchaVerifier;
    } catch (error) {
      console.error("Error initializing RecaptchaVerifier:", error.message);
      return null;
    }
  };

  const handleBackToLogin = () => {
    setShowForgotPasswd(false);
    setShowEmailContent(true);
  };

  const handleForgotPasswordClick = () => {
    setShowForgotPasswd(true);
    setShowEmailContent(false);
    setShowPhoneLogin(false);
    setShowOTPContent(false);
    setShowRegisterContent(false);
  };

  const handlePhoneForgotPassword = async () => {
    if (!formattedNumber) {
      return toast.error(t("phoneRequired"))
    }
    // Determine the phone number to use (prefer formattedNumber from phone input)
    const rawPhoneNumber = `+${value?.countryCode}${formattedNumber}`;
    const phoneNumberToUse = phoneUtil.parseAndKeepRawInput(rawPhoneNumber, "ZZ");

    if (!phoneUtil.isValidNumber(phoneNumberToUse)) {
      return toast.error(t("validPhonenum"));
    }
    // Prepare UI for OTP flow
    setShowForgotPasswd(false);
    setShowEmailContent(false);
    setShowPhoneLogin(false);
    setShowRegisterContent(false);
    setShowOTPContent(true);

    // Reset any previous OTP state
    setOTP("");
    setEmailOtp("");
    setTimeLeft(120);
    setIsCounting(true);
    setIsEmailCounting(false);
    setIsEmailOtpEnabled(false);
    setShowLoader(true);


    // store that this OTP flow was initiated for phone forgot-password reset
    setForgotPasswordViaPhone(true);
    setIsPasswordUpdateFlow(false); // This is forgot-password, NOT the user-exists-no-password flow
    // store raw phone with country code if possible for later password update
    try {
      if (value?.countryCode && formattedNumber) {
        setPhonenum(rawPhoneNumber);
      } else if (phonenum) {
        setPhonenum(phonenum);
      }
    } catch (e) {
      // ignore
    }

    try {
      if (!phoneNumberToUse) {
        toast.error(t("enterPhoneNumber"));
        setShowOTPContent(false);
        return;
      }

      // Send OTP via configured provider
      if (isTwilioOtp) {
        await generateOTPWithTwilio(rawPhoneNumber);
      } else {
        // Default/fallback to Firebase if configured or if Twilio not enabled
        await generateOTP(rawPhoneNumber);
      }

      // Keep OTP form visible and start countdown (already set above)
      setShowOTPContent(true);
    } catch (error) {
      console.error("Error sending OTP for forgot-password (phone):", error);
      toast.error(t("otpSendFailed") || "Failed to send OTP");
      // Revert to forgot-password view so user can try again
      setShowOTPContent(false);
      setShowForgotPasswd(true);
    } finally {
      setShowLoader(false);
    }
  };

  const handleResetAllStates = () => {
    // Reset all form data states
    setSignInFormData({
      email: "",
      password: "",
    });

    setRegisterFormData({
      name: "",
      email: "",
      phone: "",
      password: "",
      confirmPassword: "",
    });

    // Reset OTP related states
    setOTP("");
    setEmailOtp("");
    setTimeLeft(0);
    setEmailTimeLeft(0);
    setIsCounting(false);
    setIsEmailCounting(false);
    setIsEmailOtpEnabled(false);
    setEmailReverify(false);

    // Reset phone related states
    setValue({
      number: isDemo ? DemoNumber : "",
      countryCode: isDemo ? "91" : "",
    });
    setPhonenum("");
    setPhonePassword("");
    setConfirmPhnPass("");
    setResetMobilePass(false);
    setShowPasswordInput(false);
    setPhonePassword("");

    // Reset modal view states based on initial view logic
    const initialView = getInitialView();
    setShowEmailContent(initialView.showEmailContent);
    setShowPhoneLogin(initialView.showPhoneLogin);
    setShowRegisterOptions(initialView.showRegisterOptions);
    setShowOTPContent(false);
    setShowRegisterContent(false);
    setShowForgotPasswd(false);
    setShowLoader(false);
    setForgotPasswordViaEmail(false);
    setForgotPasswordEmail("");
  };

  const handlePhoneLogin = (e) => {
    e.preventDefault();
    setShowPhoneLogin(true);
    setShowRegisterOptions(false);
    setShowEmailLoginForm(false)
    setShowEmailContent(false);
    setShowOTPContent(false);
    setShowRegisterContent(false);
    setIsEmailOtpEnabled(false);
    setShowPasswordInput(false); // Reset password input visibility
    setRegisterFormData({
      ...registerFormData,
      email: "",
    })
  };

  const handleEmailLoginshow = () => {
    setShowEmailContent(true);
    setShowRegisterOptions(false);
    setShowEmailLoginForm(true)
    setShowPhoneLogin(false);
    setShowOTPContent(false);
    setShowRegisterContent(false);
  };

  const handleSignIn = (e) => {
    e.preventDefault();
    setShowRegisterContent(false);
    setShowOTPContent(false);
    if (ShowEmailLogin) {
      setShowEmailLoginForm(true);
      setShowEmailContent(true);
    } else if (ShowPhoneLogin) {
      setShowPhoneLogin(true);
    } else {
      setShowEmailContent(true);
    }
    setShowRegisterOptions(false);
  };

  const handlesignUp = () => {
    setShowRegisterContent(false);
    setShowRegisterOptions(true)
    setShowEmailContent(false);
    setShowOTPContent(false);
    setShowForgotPasswd(false);
    setShowPhoneLogin(false);
  };

  const handleShowSignUp = (mobileReq) => {
    setShowRegisterContent(true);
    setIsMobileReq(mobileReq);
    setShowRegisterOptions(false);
    setShowEmailContent(false);
    setShowOTPContent(false);
    setShowForgotPasswd(false);
  }


  const handleRegisterPhoneChange = (phone, data) => {
    const normalized = normalizePhoneInputChange(phone, data);
    setRegisterFormData({
      ...registerFormData,
      phone: normalized.number,
      countryCode: normalized.countryCode,
    });
  };

  const handleRegisterInputChange = (e) => {
    const { name, value } = e.target;

    setRegisterFormData({
      ...registerFormData,
      [name]: value,
    });
  };

  // login with email
  const SignInWithEmail = async (e) => {
    e.preventDefault();

    if (!signInFormData?.email || !signInFormData?.password) {
      toast.error(t("allFieldsRequired"));
      return;
    }

    const isEmailValid = /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(
      signInFormData?.email,
    );
    if (!isEmailValid) {
      toast.error(t("invalidEmail"));
      return;
    }

    setShowLoader(true);

    try {
      const response = await userSignup({
        type: "3",
        email: signInFormData?.email,
        password: signInFormData?.password,
        fcm_id: FcmToken,
      });
      if (!response.error) {
        setShowLoader(false);
        toast.success(t(response?.message));
        dispatch(setAuth({ data: response?.data }));
        dispatch(setJWTToken({ data: response?.token }));
        onCloseLogin();
      } else if (
        response.error &&
        response?.key === "accountDeactivated"
      ) {
        setShowLoader(false);
        Swal.fire({
          title: t("opps"),
          text: t("accountDeactivatedByAdmin"),
          icon: "warning",
          showCancelButton: false,
          customClass: {
            confirmButton: "Swal-confirm-buttons",
            cancelButton: "Swal-cancel-buttons",
          },
          confirmButtonText: t("ok"),
        }).then((result) => {
          if (result.isConfirmed) {
            router.push(`/contact-us?lang=${lang}`);
          }
        });
      }
    } catch (error) {
      if (error?.key === "emailNotVerified") {
        console.error("Error", error);
        setShowLoader(false);
        setEmailReverify(true);
        setIsEmailOtpEnabled(true);
        toast.error(error?.message);
      } else {
        console.error("SignInWithEmail error:", error);
        toast.error(t(error.message));
        setShowLoader(false);
      }
    }
  };

  // handle input change
  const handleSignInInputChange = (e) => {
    const { name, value } = e.target;
    // Check if the field being changed is the email
    if (name === "email") {
      if (value !== signInFormData.email) {
        setEmailReverify(false); // Email has been changed, set reverify flag
      }
    }

    setSignInFormData({
      ...signInFormData,
      [name]: value,
    });
  };

  const onCloseLogin = (e) => {
    if (e) {
      e.stopPropagation();
    }
    handleResetAllStates();
    setShowLogin(false);
  };

  // login with google
  const handleGoogleSignup = async () => {
    const provider = new GoogleAuthProvider();
    try {
      const response = await signInWithPopup(authentication, provider);

      const res = await userSignup({
        name: response?.user?.displayName,
        email: response?.user?.email,
        type: "0",
        auth_id: response?.user?.uid,
        profile: response?.user?.photoURL,
        fcm_id: FcmToken,
      });
      if (!res.error) {
        toast.success(t(res?.message));
        dispatch(setAuth({ data: res?.data }));
        dispatch(setJWTToken({ data: res?.token }));
        onCloseLogin();
      } else if (
        res?.error &&
        res?.key === "accountDeactivated"
      ) {
        onCloseLogin();
        Swal.fire({
          title: t("oops"),
          text: t("accountDeactivatedByAdmin"),
          icon: "warning",
          showCancelButton: false,
          customClass: {
            confirmButton: "Swal-confirm-buttons",
            cancelButton: "Swal-cancel-buttons",
          },
          confirmButtonText: t("ok"),
        }).then((result) => {
          if (result.isConfirmed) {
            router.push(`/contact-us?lang=${lang}`);
          }
        });
      }
    } catch (error) {
      console.error(error);
      toast.error(t("popupCancel"));
    }
  };

  // handle phone OTP
  const generateOTP = async (phoneNumber) => {
    if (!window.recaptchaVerifier) {
      console.error("window.recaptchaVerifier is null, unable to generate OTP");
      setShowLoader(false);
      return;
    }

    try {
      let appVerifier = window.recaptchaVerifier;
      const confirmationResult = await signInWithPhoneNumber(
        authentication,
        phoneNumber,
        appVerifier,
      );
      window.confirmationResult = confirmationResult;
      toast.success(t("otpSentsuccess"));

      if (isDemo) {
        setOTP(DemoOTP);
      }
      setTimeLeft(120);
      setIsCounting(true);
      setShowLoader(false);
    } catch (error) {
      console.error("Error generating OTP:", error);
      const errorCode = error.code;
      handleFirebaseAuthError(errorCode, t);
      setShowLoader(false);
    }
  };

  const generateOTPWithTwilio = async (phoneNumber) => {
    setShowLoader(true);
    const parsedNumber = phoneUtil.parseAndKeepRawInput(phoneNumber, "ZZ");
    const countryCode = parsedNumber.getCountryCode().toString();
    const formattedNumber = parsedNumber.getNationalNumber().toString();
    try {
      const res = await getOTPApi({ number: formattedNumber, country_code: countryCode });
      if (!res.error) {
        setShowPhoneLogin(false);
        setShowOTPContent(true);
        setTimeLeft(120);
        setIsCounting(true);
        toast.success(t(res?.message));
        setShowLoader(false);
      }
    } catch (error) {
      console.error("Error generating OTP with Twilio:", error);
      toast.error(t(error?.message) || t("otpSendFailed"));
      setShowLoader(false);
    }
  };

  const handleShowCreatePassword = () => {
    // Store the phone number before showing password form
    if (value?.countryCode && formattedNumber) {
      const fullPhone = `+${value.countryCode}${formattedNumber}`;
      setPhonenum(fullPhone);
    }

    setResetMobilePass(true);
    setPhonePassword("");
    setConfirmPhnPass("");
    setShowOTPContent(false);
    setShowPhoneLogin(false);
    setShowRegisterContent(false);
    setShowRegisterOptions(false);
    setIsPasswordUpdateFlow(true); // Mark this as password update flow (user exists, no password)
  }


  // Handle initial phone number check (step 1)
  const handleCheckPhoneNumber = async (e) => {
    e.preventDefault();
    setShowLoader(true);

    // Validate phone number
    if (!value.number) {
      toast.error(t("enterPhonenumber"));
      setShowLoader(false);
      return;
    }

    try {
      const rawPhoneNumber = `+${value?.countryCode}${formattedNumber}`;
      const phoneNumberToUse = phoneUtil.parseAndKeepRawInput(rawPhoneNumber, "ZZ");

      if (!phoneUtil.isValidNumber(phoneNumberToUse)) {
        toast.error(t("validPhonenum"));
        setShowLoader(false);
        return;
      }

      // Check if user and password exist
      const response = await checkPhoneNoPasswordExistsApi({
        mobile: formattedNumber,
        country_code: value?.countryCode,
      });

      // User exists with password - show password input for login
      if (response?.data?.user_exists === true && response?.data?.password_exists === true) {
        setShowPasswordInput(true);
        setPhonePassword("");
        setShowLoader(false);
        return;
      }

      setShowLoader(false);
    } catch (error) {
      // User exists but no password set - send OTP first, then show password creation form
      if (error?.data?.user_exists === true && error?.data?.password_exists === false) {
        try {
          const rawPhoneNumber = `+${value?.countryCode}${formattedNumber}`;
          const phoneNumberToUse = phoneUtil.parseAndKeepRawInput(rawPhoneNumber, "ZZ");
          const fullPhoneNumberWithCountryCode = phoneNumberToUse.getRawInput();

          setPhonenum(fullPhoneNumberWithCountryCode);
          setIsPasswordUpdateFlow(true);

          // Send OTP via respective provider
          if (isFirebaseOtp) {
            if (!window.recaptchaVerifier) {
              await generateRecaptcha();
            }
            await generateOTP(fullPhoneNumberWithCountryCode);
          } else if (isTwilioOtp) {
            await generateOTPWithTwilio(fullPhoneNumberWithCountryCode);
          }

          // Show OTP screen - after verification, will show password setup
          setShowOTPContent(true);
          setShowPhoneLogin(false);
          setShowLoader(false);
          // User doesn't exist - proceed to registration
          setShowPasswordInput(true);
          setShowLoader(false);
          return;
        } catch (otpError) {
          console.error("Error sending OTP for password setup:", otpError);
          toast.error(t(otpError?.message) || t("somethingWentWrong"));
          setShowLoader(false);
          return;
        }
      }
      // User doesn't exist - proceed to registration
      else if (error?.data?.user_exists === false) {
        toast.error(t(error?.message));
        setShowPasswordInput(false);
        setShowPhoneLogin(false);
        setValue({
          number: isDemo ? DemoNumber : "",
          countryCode: "",
        });
        setShowRegisterOptions(true);
      }
    } finally {
      setShowLoader(false);
    }
  };

  // Handle phone login with password (step 2)
  const handlePhoneLoginWithPassword = async (e) => {
    e.preventDefault();
    setShowLoader(true);

    // Validate inputs
    if (!value.number) {
      toast.error(t("enterPhonenumber"));
      setShowLoader(false);
      return;
    }

    if (!phonePassword) {
      toast.error(t("passwordRequired"));
      setShowLoader(false);
      return;
    }

    try {
      // User exists with password - direct login
      if (showPasswordInput) {
        const loginResponse = await userSignup({
          mobile: formattedNumber,
          type: "1",
          password: phonePassword,
          fcm_id: FcmToken,
          country_code: countryCodeDigitsOnly,
        });

        if (!loginResponse.error) {
          setShowLoader(false);
          toast.success(t(loginResponse?.message));
          dispatch(setAuth({ data: loginResponse?.data }));
          dispatch(setJWTToken({ data: loginResponse?.token }));
          onCloseLogin();
        } else if (loginResponse?.error && loginResponse?.key === "accountDeactivated") {
          onCloseLogin();
          Swal.fire({
            title: t("oops"),
            text: t("accountDeactivatedByAdmin"),
            icon: "warning",
            showCancelButton: false,
            customClass: {
              confirmButton: "Swal-confirm-buttons",
              cancelButton: "Swal-cancel-buttons",
            },
            confirmButtonText: t("ok"),
          }).then((result) => {
            if (result.isConfirmed) {
              router.push(`/contact-us?lang=${lang}`);
            }
          });
        } else {
          setShowLoader(false);
          toast.error(t(loginResponse?.message));
        }
        return;
      }
    } catch (error) {
      // User doesn't exist - proceed with OTP registration flow
      if (error?.data?.user_exists === false) {
        try {
          await onSignUp(e);
        } catch (signUpError) {
          console.error("Error during phone registration:", signUpError);
          toast.error(t(signUpError?.message) || t("somethingWentWrong"));
          setShowLoader(false);
        }
        return;
      }

      console.error("Error during phone login:", error);
      toast.error(t(error?.message) || t("somethingWentWrong"));
      setShowLoader(false);
    }
  };

  const handleUpdatePhonePassword = async (e) => {
    e.preventDefault();
    // Validate password inputs
    if (!phonePassword || phonePassword.length < 6) {
      toast.error(t("passwordLengthError"));
      return;
    }
    if (phonePassword !== confirmPhnPass) {
      toast.error(t("passwordsNotMatch"));
      return;
    }

    setShowLoader(true);
    try {
      // Parse phone number to extract mobile and country code
      const phoneNumberObj = phoneUtil.parseAndKeepRawInput(phonenum, "ZZ");
      if (!phoneUtil.isValidNumber(phoneNumberObj)) {
        toast.error(t("validPhonenum"));
        setShowLoader(false);
        return;
      }

      const mobile = phoneNumberObj.getNationalNumber();
      const country_code = phoneNumberObj.getCountryCode();

      // Update password using the stored firebaseAuthId from OTP verification
      const updateResponse = await updatePhoneNoPasswordApi({
        firebase_id: firebaseAuthId,
        mobile: mobile,
        country_code: country_code,
        password: phonePassword,
        re_password: confirmPhnPass,
      });

      if (!updateResponse.error) {
        // toast.success(t(updateResponse?.message) || t("passwordUpdated"));

        // Auto sign in after password is set
        const res = await userSignup({
          mobile: mobile,
          type: "1",
          auth_id: firebaseAuthId,
          fcm_id: FcmToken,
          password: phonePassword,
          country_code: country_code,
        });

        if (!res.error) {
          setShowLoader(false);
          toast.success(t(res.message));
          dispatch(setAuth({ data: res?.data }));
          dispatch(setJWTToken({ data: res?.token }));
          // Reset password states
          setPhonePassword("");
          setConfirmPhnPass("");
          setFirebaseAuthId("");
          setIsPasswordUpdateFlow(false);
          onCloseLogin();
        } else if (res?.error && res?.key === "accountDeactivated") {
          onCloseLogin();
          Swal.fire({
            title: t("oops"),
            text: t("accountDeactivatedByAdmin"),
            icon: "warning",
            showCancelButton: false,
            customClass: {
              confirmButton: "Swal-confirm-buttons",
              cancelButton: "Swal-cancel-buttons",
            },
            confirmButtonText: t("ok"),
          }).then((result) => {
            if (result.isConfirmed) {
              router.push(`/contact-us?lang=${lang}`);
            }
          });
        } else {
          setShowLoader(false);
          toast.error(t(res?.message));
        }
      } else {
        setShowLoader(false);
        toast.error(t(updateResponse?.message) || t("passwordUpdateFailed"));
      }
    } catch (error) {
      console.error("Error updating password:", error);
      toast.error(t(error?.message) || t("somethingWentWrong"));
      setShowLoader(false);
    }
  }

  // Handle forgot password final step - update password with already verified firebase_id
  const handleForgotPasswordUpdate = async (e) => {
    e.preventDefault();

    // Validate password inputs
    if (!phonePassword || phonePassword.length < 6) {
      toast.error(t("passwordLengthError"));
      return;
    }
    if (phonePassword !== confirmPhnPass) {
      toast.error(t("passwordsNotMatch"));
      return;
    }

    if (!firebaseAuthId) {
      toast.error(t("sessionExpired") || "Session expired. Please try again.");
      setResetMobilePass(false);
      setShowPhoneLogin(true);
      return;
    }

    setShowLoader(true);

    try {
      // Parse phone number to get mobile and country_code
      const phoneNumberObj = phoneUtil.parseAndKeepRawInput(phonenum, "ZZ");
      const mobile = phoneNumberObj.getNationalNumber();
      const country_code = phoneNumberObj.getCountryCode();

      // Update password directly using the firebase_id we stored during OTP verification
      const updateResponse = await updatePhoneNoPasswordApi({
        firebase_id: firebaseAuthId,
        mobile: mobile,
        country_code: country_code,
        password: phonePassword,
        re_password: confirmPhnPass,
      });

      if (!updateResponse.error) {
        toast.success(t(updateResponse?.message) || t("passwordUpdated"));

        // Auto-login after password reset
        const res = await userSignup({
          mobile: mobile,
          type: "1",
          auth_id: firebaseAuthId,
          fcm_id: FcmToken,
          password: phonePassword,
          country_code: country_code,
        });

        if (!res.error) {
          setShowLoader(false);
          toast.success(t("passwordResetSuccess") || "Password reset successful");
          dispatch(setAuth({ data: res?.data }));
          dispatch(setJWTToken({ data: res?.token }));
          // Reset all states
          setPhonePassword("");
          setConfirmPhnPass("");
          setFirebaseAuthId("");
          setForgotPasswordViaPhone(false);
          setIsPasswordUpdateFlow(false);
          onCloseLogin();
        } else if (res?.error && res?.key === "accountDeactivated") {
          onCloseLogin();
          Swal.fire({
            title: t("oops"),
            text: t("accountDeactivatedByAdmin"),
            icon: "warning",
            showCancelButton: false,
            customClass: {
              confirmButton: "Swal-confirm-buttons",
              cancelButton: "Swal-cancel-buttons",
            },
            confirmButtonText: t("ok"),
          }).then((result) => {
            if (result.isConfirmed) {
              router.push(`/contact-us?lang=${lang}`);
            }
          });
        } else {
          setShowLoader(false);
          toast.error(t(res?.message));
        }
      } else {
        setShowLoader(false);
        toast.error(t(updateResponse?.message) || t("passwordUpdateFailed"));
      }
    } catch (error) {
      setShowLoader(false);
      console.error("Error updating forgot password:", error);
      toast.error(t(error?.message) || t("somethingWentWrong"));
    }
  };

  // handles phone number and twilio signup
  const onSignUp = async (e) => {
    e.preventDefault();

    try {
      const phoneNumber = phoneUtil.parseAndKeepRawInput(`+${value.countryCode}${formattedNumber}`, "ZZ");
      if (!phoneUtil.isValidNumber(phoneNumber)) {
        toast.error(t("validPhonenum"));
        return;
      }
      const fullPhoneNumberWithCountryCode = phoneNumber.getRawInput();
      setShowLoader(true); // Set loader before any async operations
      setPhonenum(fullPhoneNumberWithCountryCode);

      if (isFirebaseOtp) {
        // Ensure reCAPTCHA is ready
        if (!window.recaptchaVerifier) {
          await generateRecaptcha();
        }
        await generateOTP(fullPhoneNumberWithCountryCode);
      } else if (isTwilioOtp) {
        await generateOTPWithTwilio(fullPhoneNumberWithCountryCode);
      }

      // Only change views after successful OTP generation
      setShowPhoneLogin(false);
      setShowOTPContent(true);

      if (isDemo) {
        setValue({
          number: DemoNumber,
          countryCode: "91"
        });
      } else {
        setValue({
          number: "",
          countryCode: ""
        });
      }
    } catch (error) {
      console.error("Error parsing phone number:", error);
      toast.error(t("validPhonenum"));
      setShowLoader(false);
    }
  };

  const wrongNumber = (e) => {
    e.preventDefault();
    setShowOTPContent(false);
    setTimeLeft(0);
    setIsCounting(false);
    setOTP("");

    // Check if it's a registration flow
    if (registerFormData?.name && registerFormData?.password) {
      // Go back to registration form
      setShowRegisterContent(true);
      setShowPhoneLogin(false);
      setShowEmailContent(false);
    } else {
      // Go back to phone login form
      setShowPhoneLogin(true);
      setShowEmailContent(false);
    }
  };
  const wrongEmail = (e) => {
    e.preventDefault();
    setShowOTPContent(false);
    setIsEmailOtpEnabled(false);
    setEmailTimeLeft(0);
    setIsEmailCounting(false);
    setEmailOtp("");

    // If this was email forgot-password flow, go back to forgot password form
    if (forgotPasswordViaEmail) {
      setForgotPasswordViaEmail(false);
      setForgotPasswordEmail("");
      setShowForgotPasswd(true);
      setShowPhoneLogin(false);
      setShowEmailContent(false);
      return;
    }

    // Check if it's a registration flow
    if (registerFormData?.name && registerFormData?.password) {
      // Go back to registration form
      setShowRegisterContent(true);
      setShowPhoneLogin(false);
      setShowEmailContent(false);
    } else {
      // Go back to email login form
      setShowPhoneLogin(false);
      setShowEmailContent(true);
    }
  };

  // Handle phone registration after OTP verification
  const handlePhoneRegistrationAfterOTP = async (mobile, countryCode, firebaseId) => {
    try {
      // Step 1: Register user with userRegisterApi
      const registerResponse = await userRegisterApi({
        name: registerFormData?.name,
        email: registerFormData?.email || "",
        mobile: mobile,
        password: registerFormData?.password,
        re_password: registerFormData?.confirmPassword,
        type: "1", // Type 1 for phone registration
        firebase_id: firebaseId,
        fcm_id: FcmToken,
        country_code: countryCode,
      });

      if (registerResponse.error) {
        if (registerResponse?.key === "accountDeactivated") {
          handleAccountDeactivated();
        } else {
          toast.error(t(registerResponse?.message) || t("registrationFailed"));
          setShowLoader(false);
        }
        return;
      }

      // Step 2: Sign in the user after successful registration
      const signInResponse = await userSignup({
        mobile: mobile,
        password: registerFormData?.password,
        type: "1",
        auth_id: firebaseId,
        fcm_id: FcmToken,
        country_code: countryCode,
      });

      if (!signInResponse.error) {
        toast.success(t(signInResponse?.message) || t("registrationSuccess"));
        setShowLoader(false);

        // Set auth data
        dispatch(setAuth({ data: signInResponse?.data }));
        dispatch(setJWTToken({ data: signInResponse?.token }));

        // Reset form data
        resetRegistrationForm();
        onCloseLogin();
      } else {
        toast.error(t(signInResponse?.message));
        setShowLoader(false);
      }
    } catch (error) {
      setShowLoader(false);
      console.error("Phone registration after OTP error:", error);
      toast.error(t(error?.message) || t("registrationFailed"));
    }
  };

  // Reset registration form helper
  const resetRegistrationForm = () => {
    setRegisterFormData({
      name: "",
      email: "",
      phone: "",
      password: "",
      confirmPassword: "",
      countryCode: "",
    });
  };

  // Handle account deactivated scenario
  const handleAccountDeactivated = () => {
    onCloseLogin();
    Swal.fire({
      title: t("oops"),
      text: t("accountDeactivatedByAdmin"),
      icon: "warning",
      showCancelButton: false,
      customClass: {
        confirmButton: "Swal-confirm-buttons",
        cancelButton: "Swal-cancel-buttons",
      },
      confirmButtonText: t("ok"),
    }).then((result) => {
      if (result.isConfirmed) {
        router.push(`/contact-us?lang=${lang}`);
      }
    });
  };

  // OTP Confirm handler
  const handleConfirm = (e) => {
    if (e && e.preventDefault) {
      e.preventDefault();
    }
    if (otp === "" || otp.length !== 6) {
      toast.error(t("pleaseEnterOtp"));
      return;
    }
    setShowLoader(true);
    if (isFirebaseOtp) {
      let confirmationResult = window.confirmationResult;
      confirmationResult
        .confirm(otp)
        .then(async (result) => {
          const parsedPhone = phoneUtil.parse(result.user.phoneNumber, "ZZ");
          const mobile = parsedPhone.getNationalNumber();
          const country_code = parsedPhone.getCountryCode();
          const firebase_id = result.user.uid;

          // If this OTP flow was started for forgot-password via phone,
          // show the create-password screen instead of signing up
          if (forgotPasswordViaPhone) {
            try {
              const parsed = phoneUtil.parseAndKeepRawInput(result.user.phoneNumber, "ZZ");
              const raw = parsed.getRawInput();
              setPhonenum(raw);
              setFirebaseAuthId(firebase_id);
            } catch (e) {
              // ignore parse errors
            }
            setShowLoader(false);
            handleShowCreatePassword();
            setForgotPasswordViaPhone(false);
            return;
          }

          // If this is password update flow (user-exists-no-password), show password setup screen
          if (isPasswordUpdateFlow && !phonePassword && !confirmPhnPass) {
            try {
              const parsed = phoneUtil.parseAndKeepRawInput(result.user.phoneNumber, "ZZ");
              const raw = parsed.getRawInput();
              setPhonenum(raw);
              setFirebaseAuthId(firebase_id);
            } catch (e) {
              // ignore parse errors
            }
            setShowLoader(false);
            handleShowCreatePassword();
            return;
          }

          // Handle password update/reset flows (when password already entered and OTP verified)
          if ((isPasswordUpdateFlow || forgotPasswordViaPhone) && phonePassword && confirmPhnPass && phonePassword === confirmPhnPass) {
            try {
              // Update password first
              const updateResponse = await updatePhoneNoPasswordApi({
                firebase_id: firebase_id,
                mobile: mobile,
                country_code: country_code,
                password: phonePassword,
                re_password: confirmPhnPass,
              });

              if (!updateResponse.error) {
                toast.success(t(updateResponse?.message) || t("passwordUpdated"));

                // If this is password update flow (user-exists-no-password), auto sign in
                if (isPasswordUpdateFlow) {
                  const res = await userSignup({
                    mobile: mobile,
                    type: "1",
                    auth_id: firebase_id,
                    fcm_id: FcmToken,
                    password: phonePassword,
                    country_code: country_code,
                  });

                  if (!res.error) {
                    setShowLoader(false);
                    toast.success(t(res.message));
                    dispatch(setAuth({ data: res?.data }));
                    dispatch(setJWTToken({ data: res?.token }));
                    // Reset password states
                    setPhonePassword("");
                    setConfirmPhnPass("");
                    setFirebaseAuthId("");
                    setIsPasswordUpdateFlow(false);
                    onCloseLogin();
                  } else if (res?.error && res?.key === "accountDeactivated") {
                    onCloseLogin();
                    Swal.fire({
                      title: t("oops"),
                      text: t("accountDeactivatedByAdmin"),
                      icon: "warning",
                      showCancelButton: false,
                      customClass: {
                        confirmButton: "Swal-confirm-buttons",
                        cancelButton: "Swal-cancel-buttons",
                      },
                      confirmButtonText: t("ok"),
                    }).then((result) => {
                      if (result.isConfirmed) {
                        router.push(`/contact-us?lang=${lang}`);
                      }
                    });
                  }
                } else {
                  // Forgot password flow - auto login after password reset
                  const res = await userSignup({
                    mobile: mobile,
                    type: "1",
                    auth_id: firebase_id,
                    fcm_id: FcmToken,
                    password: phonePassword,
                    country_code: country_code,
                  });

                  if (!res.error) {
                    setShowLoader(false);
                    dispatch(setAuth({ data: res?.data }));
                    dispatch(setJWTToken({ data: res?.token }));
                    // Reset password states
                    setPhonePassword("");
                    setConfirmPhnPass("");
                    setFirebaseAuthId("");
                    setForgotPasswordViaPhone(false);
                    setIsPasswordUpdateFlow(false);
                    onCloseLogin();
                  } else if (res?.error && res?.key === "accountDeactivated") {
                    onCloseLogin();
                    Swal.fire({
                      title: t("oops"),
                      text: t("accountDeactivatedByAdmin"),
                      icon: "warning",
                      showCancelButton: false,
                      customClass: {
                        confirmButton: "Swal-confirm-buttons",
                        cancelButton: "Swal-cancel-buttons",
                      },
                      confirmButtonText: t("ok"),
                    }).then((result) => {
                      if (result.isConfirmed) {
                        router.push(`/contact-us?lang=${lang}`);
                      }
                    });
                  } else {
                    setShowLoader(false);
                    toast.error(t(res?.message));
                    // Reset and show login screen on error
                    setPhonePassword("");
                    setConfirmPhnPass("");
                    setFirebaseAuthId("");
                    setForgotPasswordViaPhone(false);
                    setShowPhoneLogin(true);
                    setShowOTPContent(false);
                    setShowEmailContent(false);
                  }
                }
              } else {
                setShowLoader(false);
                toast.error(t(updateResponse?.message) || t("passwordUpdateFailed"));
              }
              return;
            } catch (updateError) {
              setShowLoader(false);
              console.error("Password update error:", updateError);
              toast.error(t(updateError?.message) || t("passwordUpdateFailed"));
              return;
            }
          }

          // Check if this is a registration flow (registerFormData has data)
          if (registerFormData?.name && registerFormData?.password) {
            // Phone registration - directly register user after OTP verification
            await handlePhoneRegistrationAfterOTP(mobile, country_code, firebase_id);
          } else {
            // Normal signup flow (no password update needed) - existing user login
            const res = await userSignup({
              mobile: mobile,
              type: "1",
              auth_id: firebase_id,
              fcm_id: FcmToken,
              password: phonePassword,
              country_code: country_code,
            });
            if (!res.error) {
              setShowLoader(false);
              toast.success(t(res.message));
              dispatch(setAuth({ data: res?.data }));
              dispatch(setJWTToken({ data: res?.token }));
              onCloseLogin();
            } else if (
              res?.error &&
              res?.key === "accountDeactivated"
            ) {
              onCloseLogin();
              Swal.fire({
                title: t("oops"),
                text: t("accountDeactivatedByAdmin"),
                icon: "warning",
                showCancelButton: false,
                customClass: {
                  confirmButton: "Swal-confirm-buttons",
                  cancelButton: "Swal-cancel-buttons",
                },
                confirmButtonText: t("ok"),
              }).then((result) => {
                if (result.isConfirmed) {
                  router.push(`/contact-us?lang=${lang}`);
                }
              });
            }
          }
        })
        .catch((error) => {
          setShowLoader(false);
          console.error(error);
          const errorCode = error.code;
          handleFirebaseAuthError(errorCode, t);
        });
    } else if (isTwilioOtp) {
      try {
        handleTwilioOTPConfirm();
      } catch (error) {
        console.error("Error verifying OTP with Twilio:", error);
        toast.error(t(error.message) || t("otpVerificationFailed"));
        setShowLoader(false);
      }
    }
  };

  const handleTwilioOTPConfirm = async () => {
    try {
      const phoneNumber = phoneUtil.parseAndKeepRawInput(phonenum, "ZZ");
      const mobile = phoneNumber.getNationalNumber();
      const country_code = phoneNumber.getCountryCode();

      const res = await verifyOTP({
        number: mobile,
        otp: otp,
        country_code: country_code,
      });
      if (!res.error) {
        const auth_id = res.auth_id;

        // If this OTP flow was started for forgot-password via phone,
        // show the create-password screen instead of signing up
        if (forgotPasswordViaPhone) {
          setShowLoader(false);
          setFirebaseAuthId(auth_id); // Store auth_id for Twilio
          handleShowCreatePassword();
          setForgotPasswordViaPhone(false);
          return;
        }

        // If this is password update flow (user-exists-no-password), show password setup screen
        if (isPasswordUpdateFlow && !phonePassword && !confirmPhnPass) {
          setPhonenum(`+${country_code}${mobile}`);
          setShowLoader(false);
          setFirebaseAuthId(auth_id); // Store auth_id for Twilio
          handleShowCreatePassword();
          return;
        }

        // Handle password update/reset flows (Twilio) (when password already entered and OTP verified)
        if ((isPasswordUpdateFlow || forgotPasswordViaPhone) && phonePassword && confirmPhnPass && phonePassword === confirmPhnPass) {
          try {
            // Update password first
            const updateResponse = await updatePhoneNoPasswordApi({
              firebase_id: auth_id, // For Twilio, we use auth_id from verify response
              mobile: mobile,
              country_code: country_code,
              password: phonePassword,
              re_password: confirmPhnPass,
            });

            if (!updateResponse.error) {
              toast.success(t(updateResponse?.message) || t("passwordUpdated"));

              // If this is password update flow (user-exists-no-password), auto sign in
              if (isPasswordUpdateFlow) {
                const res2 = await userSignup({
                  mobile: mobile,
                  type: "1",
                  auth_id: auth_id,
                  fcm_id: FcmToken,
                  password: phonePassword,
                  country_code: country_code,
                });

                if (!res2.error) {
                  setShowLoader(false);
                  toast.success(t(res2?.message));
                  dispatch(setAuth({ data: res2?.data }));
                  dispatch(setJWTToken({ data: res2?.token }));
                  // Reset password states
                  setPhonePassword("");
                  setConfirmPhnPass("");
                  setFirebaseAuthId("");
                  setIsPasswordUpdateFlow(false);
                  onCloseLogin();
                } else if (res2?.error && res2?.key === "accountDeactivated") {
                  Swal.fire({
                    title: t("opps"),
                    text: t("accountDeactivatedByAdmin"),
                    icon: "warning",
                    showCancelButton: false,
                    customClass: {
                      confirmButton: "Swal-confirm-buttons",
                      cancelButton: "Swal-cancel-buttons",
                    },
                    confirmButtonText: t("ok"),
                  }).then((result) => {
                    if (result.isConfirmed) {
                      router.push(`/contact-us?lang=${lang}`);
                    }
                  });
                }
              } else {
                // Forgot password flow - auto login after password reset
                const res2 = await userSignup({
                  mobile: mobile,
                  type: "1",
                  auth_id: auth_id,
                  fcm_id: FcmToken,
                  password: phonePassword,
                  country_code: country_code,
                });

                if (!res2.error) {
                  setShowLoader(false);
                  toast.success(t("passwordResetSuccess") || "Password reset successful");
                  dispatch(setAuth({ data: res2?.data }));
                  dispatch(setJWTToken({ data: res2?.token }));
                  // Reset password states
                  setPhonePassword("");
                  setConfirmPhnPass("");
                  setFirebaseAuthId("");
                  setForgotPasswordViaPhone(false);
                  setIsPasswordUpdateFlow(false);
                  onCloseLogin();
                } else if (res2?.error && res2?.key === "accountDeactivated") {
                  Swal.fire({
                    title: t("opps"),
                    text: t("accountDeactivatedByAdmin"),
                    icon: "warning",
                    showCancelButton: false,
                    customClass: {
                      confirmButton: "Swal-confirm-buttons",
                      cancelButton: "Swal-cancel-buttons",
                    },
                    confirmButtonText: t("ok"),
                  }).then((result) => {
                    if (result.isConfirmed) {
                      router.push(`/contact-us?lang=${lang}`);
                    }
                  });
                } else {
                  setShowLoader(false);
                  toast.error(t(res2?.message));
                  // Reset and show login screen on error
                  setPhonePassword("");
                  setConfirmPhnPass("");
                  setFirebaseAuthId("");
                  setForgotPasswordViaPhone(false);
                  setShowPhoneLogin(true);
                  setShowOTPContent(false);
                  setShowEmailContent(false);
                }
              }
            } else {
              setShowLoader(false);
              toast.error(t(updateResponse?.message) || t("passwordUpdateFailed"));
            }
            return;
          } catch (updateError) {
            setShowLoader(false);
            console.error("Password update error:", updateError);
            toast.error(t(updateError?.message) || t("passwordUpdateFailed"));
            return;
          }
        }

        // Check if this is a registration flow (registerFormData has data)
        if (registerFormData?.name && registerFormData?.password) {
          // Phone registration - directly register user after OTP verification
          await handlePhoneRegistrationAfterOTP(mobile, country_code, auth_id);
        } else {
          // Normal signup flow (no password update needed) - existing user login
          const res2 = await userSignup({
            mobile: mobile,
            type: "1",
            auth_id: auth_id,
            fcm_id: FcmToken,
            country_code: country_code,
          });
          if (!res2.error) {
            setShowLoader(false);
            toast.success(t(res2?.message));
            dispatch(setAuth({ data: res2?.data }));
            dispatch(setJWTToken({ data: res2?.token }));
            onCloseLogin();
          } else if (
            res2?.error &&
            res2?.key === "accountDeactivated"
          ) {
            Swal.fire({
              title: t("opps"),
              text: t("accountDeactivatedByAdmin"),
              icon: "warning",
              showCancelButton: false,
              customClass: {
                confirmButton: "Swal-confirm-buttons",
                cancelButton: "Swal-cancel-buttons",
              },
              confirmButtonText: t("ok"),
            }).then((result) => {
              if (result.isConfirmed) {
                router.push(`/contact-us?lang=${lang}`);
              }
            });
          }
        }
      }
    } catch (error) {
      console.error("Error verifying OTP with Twilio:", error);
      toast.error(t(error?.message) || t("otpVerificationFailed"));
      setShowLoader(false);
    }
  };

  // Resend OTP handler
  const handleResendOTP = async () => {
    setShowLoader(true);
    try {
      if (isEmailOtpEnabled) {
        // Handle email OTP resend - check if it's registration or login flow
        const email = registerFormData?.email || signInFormData?.email;

        if (registerFormData?.name && registerFormData?.password) {
          // This is email registration - resend using userRegisterApi
          let phoneNumber;
          if (registerFormData?.phone) {
            phoneNumber = phoneUtil.parseAndKeepRawInput(registerFormData?.phone, "ZZ");
          }

          const response = await userRegisterApi({
            name: registerFormData?.name,
            email: registerFormData?.email,
            mobile: phoneNumber && phoneNumber?.getNationalNumber(),
            password: registerFormData?.password,
            re_password: registerFormData?.confirmPassword,
            country_code: phoneNumber && phoneNumber?.getCountryCode(),
            type: "3",
          });

          if (!response.error) {
            toast.success(t("otpSentToEmail"));
            setEmailTimeLeft(120);
            setIsEmailCounting(true);
            setEmailOtp("");
            setShowLoader(false);
          }
        } else if (forgotPasswordViaEmail) {
          // This is email forgot-password flow - resend OTP
          const response = await getOTPApi({
            email: forgotPasswordEmail,
          });
          if (!response.error) {
            toast.success(t(response?.message));
            setEmailTimeLeft(120);
            setIsEmailCounting(true);
            setEmailOtp("");
            setShowLoader(false);
          }
        } else {
          // This is email login - use getOTPApi
          const response = await getOTPApi({
            email: email,
          });
          if (!response.error) {
            toast.success(t(response?.message));
            setShowEmailContent(false);
            setShowOTPContent(true);
            setEmailTimeLeft(120);
            setIsEmailCounting(true);
            setEmailOtp("");
            setShowLoader(false);
          }
        }
      } else {
        // Handle phone OTP resend
        if (isFirebaseOtp) {
          try {
            let appVerifier = window.recaptchaVerifier;
            const confirmationResult = await signInWithPhoneNumber(
              authentication,
              phonenum,
              appVerifier,
            );
            window.confirmationResult = confirmationResult;
            toast.success(t("otpSentSuccessfully"));

            if (isDemo) {
              setOTP(DemoOTP);
            }
            setTimeLeft(120);
            setIsCounting(true);
            setOTP(""); // Reset phone OTP input
            setShowLoader(false);
          } catch (error) {
            console.error("Firebase OTP error:", error);
            const errorCode = error.code;
            handleFirebaseAuthError(errorCode, t);
            setIsCounting(false);
            setTimeLeft(0);
            setShowLoader(false);
          }
        } else if (isTwilioOtp) {
          try {
            const phone = phoneUtil?.parseAndKeepRawInput(
              phonenum,
              "ZZ",
            );
            const countryCode = phone?.getCountryCode();
            const phoneWithoutCountryCode = phone?.getNationalNumber();
            const response = await getOTPApi({ number: phoneWithoutCountryCode, country_code: countryCode });


            if (!response.error) {
              toast.success(t(response?.message));
              setTimeLeft(120);
              setIsCounting(true);
              setOTP(""); // Reset phone OTP input
              setShowLoader(false);
            }
          } catch (error) {
            console.error("Twilio OTP error:", error);
            toast.error(t(error?.message) || t("failedToSendOTP"));
            setIsCounting(false);
            setTimeLeft(0);
            setShowLoader(false);
          }
        }
      }
    } catch (error) {
      setShowLoader(false);
      console.error("Resend OTP error:", error);
      toast.error(t(error?.message) || t("failedToSendOTP"));
      // Reset all counting states in case of error
      if (isEmailOtpEnabled) {
        setIsEmailCounting(false);
        setEmailTimeLeft(0);
      } else {
        setIsCounting(false);
        setTimeLeft(0);
      }
    }
  };

  // Handle email login after OTP verification
  const handleEmailLoginAfterOTP = async (email, auth_id) => {
    try {
      // Call userSignup API to complete login after email verification
      const loginResponse = await userSignup({
        type: "3",
        email: email,
        password: signInFormData?.password,
        fcm_id: FcmToken,
        auth_id: auth_id,
      });

      if (!loginResponse.error) {
        toast.success(t(loginResponse?.message) || t("loginSuccessful"));
        setShowLoader(false);

        // Set auth data
        dispatch(setAuth({ data: loginResponse?.data }));
        dispatch(setJWTToken({ data: loginResponse?.token }));

        // Reset states
        setShowOTPContent(false);
        setEmailReverify(false);
        setIsEmailOtpEnabled(false);
        onCloseLogin();
      } else if (loginResponse?.error && loginResponse?.key === "accountDeactivated") {
        handleAccountDeactivated();
      } else {
        setShowLoader(false);
        toast.error(t(loginResponse?.message));
        // Show email login form again
        setShowOTPContent(false);
        setShowEmailContent(true);
        setIsEmailOtpEnabled(false);
      }
    } catch (error) {
      setShowLoader(false);
      console.error("Email login after OTP error:", error);
      toast.error(t(error?.message));
      // Show email login form again
      setShowOTPContent(false);
      setShowEmailContent(true);
      setIsEmailOtpEnabled(false);
    }
  };

  // Email OTP verification handler
  const handleEmailOtpVerification = async (e) => {
    if (e && e.preventDefault) {
      e.preventDefault();
    }

    if (emailOtp === "" || emailOtp.length !== 6) {
      toast.error(t("pleaseEnterOtp"));
      return;
    }
    setShowLoader(true);
    try {
      const email = forgotPasswordViaEmail ? forgotPasswordEmail : (registerFormData?.email || signInFormData?.email);

      // Call the API to verify the email OTP
      const response = await verifyOTP({
        email: email,
        otp: emailOtp,
      });

      if (!response.error) {
        // Check if this is email forgot-password flow
        if (forgotPasswordViaEmail) {
          // OTP verified — transition to password reset form
          setShowLoader(false);
          setShowOTPContent(false);
          setIsEmailOtpEnabled(false);
          setResetMobilePass(true);
          setPhonePassword("");
          setConfirmPhnPass("");
          return;
        }

        // Check if this is a registration flow (registerFormData has data)
        if (registerFormData?.name && registerFormData?.password) {
          // This is email registration - call userSignup API after OTP verification
          let phoneNumber;
          if (registerFormData?.phone) {
            phoneNumber = phoneUtil.parseAndKeepRawInput(`+${registerFormData?.phone}`, "ZZ");
          }

          const signupResponse = await userSignup({
            name: registerFormData?.name,
            email: registerFormData?.email,
            mobile: phoneNumber && phoneNumber?.getNationalNumber(),
            password: registerFormData?.password,
            type: "3", // Type 3 for email registration
            fcm_id: FcmToken,
            country_code: phoneNumber && phoneNumber?.getCountryCode(),
          });

          if (!signupResponse.error) {
            toast.success(t(signupResponse?.message) || t("registrationSuccessful"));
            dispatch(setAuth({ data: signupResponse?.data }));
            dispatch(setJWTToken({ data: signupResponse?.token }));
            setShowLoader(false);

            // Reset form data
            setRegisterFormData({
              name: "",
              email: "",
              phone: "",
              password: "",
              confirmPassword: "",
              countryCode: ""
            });
            setIsEmailOtpEnabled(false);
            onCloseLogin();
          } else if (signupResponse?.error && signupResponse?.key === "accountDeactivated") {
            onCloseLogin();
            Swal.fire({
              title: t("oops"),
              text: t("accountDeactivatedByAdmin"),
              icon: "warning",
              showCancelButton: false,
              customClass: {
                confirmButton: "Swal-confirm-buttons",
                cancelButton: "Swal-cancel-buttons",
              },
              confirmButtonText: t("ok"),
            }).then((result) => {
              if (result.isConfirmed) {
                router.push(`/contact-us?lang=${lang}`);
              }
            });
          } else {
            toast.error(t(signupResponse?.message));
            resetRegistrationForm();
            setShowRegisterContent(false);
            if (ShowEmailLogin) {
              setShowEmailContent(true)
              setShowEmailLoginForm(true)
            }
            setShowLoader(false);
          }
        } else {
          // This is email login OTP verification - call userSignup API
          await handleEmailLoginAfterOTP(email, response?.auth_id);
        }
      } else if (response.error) {
        console.error(response?.message);
        toast.error(t(response?.message));
        setShowLoader(false);
      }
    } catch (error) {
      console.error(error);
      toast.error(t(error?.message));
      setShowLoader(false);
    }
  };

  // Validation helper for registration form
  const validateRegistrationForm = () => {
    if (registerFormData?.password.length < 6) {
      toast.error(t("passwordLengthError"));
      return false;
    }

    if (isMobileReq && !registerFormData?.phone) {
      toast.error(t("enterPhonenumber"));
      return false;
    }

    if (!isMobileReq && !registerFormData?.email) {
      toast.error(t("enterEmail"));
      return false;
    }

    if (registerFormData?.password !== registerFormData?.confirmPassword) {
      toast.error(t("passwordsNotMatch"));
      return false;
    }

    // Validate email if provided
    if (registerFormData?.email) {
      const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
      if (!emailRegex.test(registerFormData?.email)) {
        toast.error(t("invalidEmail"));
        return false;
      }
    }

    // Validate phone if provided
    if (registerFormData?.phone?.length > 0) {
      const { mobile, country_code } = phonePartsForApi(
        registerFormData.phone,
        registerFormData.countryCode,
      );
      const phNumber = phoneUtil.parseAndKeepRawInput(`+${country_code}${mobile}`, "ZZ");
      if (!phoneUtil.isValidNumber(phNumber)) {
        toast.error(t("validPhonenum"));
        return false;
      }
    }

    return true;
  };

  // Send OTP for phone registration
  const sendPhoneRegistrationOTP = async (fullPhoneNumber) => {
    if (isFirebaseOtp) {
      await generateOTP(fullPhoneNumber);
    } else if (isTwilioOtp) {
      await generateOTPWithTwilio(fullPhoneNumber);
    }
  };

  const checkPhoneExistsForRegistration = async (mobile, countryCode) => {
    try {
      const response = await checkPhoneNoPasswordExistsApi({
        mobile: mobile,
        country_code: countryCode,
      });
      return true;
    } catch (error) {
      setShowLoader(false);
      if (error?.data?.user_exists === true) {
        resetRegistrationForm();
        setShowRegisterContent(false);
        setShowPhoneLogin(true);
        toast.error(t("phoneNumberAlreadyRegistered"));
        return false;
      } else if (error?.data?.user_exists === false) {
        return true;
      } else {
        return false;
      }
    }
  }

  // Handle phone registration flow
  const handlePhoneRegistration = async (phoneNumber) => {
    try {
      const mobile = phoneNumber.getNationalNumber();
      const countryCode = phoneNumber.getCountryCode();
      const fullPhoneNumber = phoneNumber.getRawInput();

      // Step 1: Check if phone and password exist using checkPhoneNoPasswordExistsApi
      const phoneCheck = await checkPhoneExistsForRegistration(mobile, countryCode);
      if (!phoneCheck) {
        return;
      }

      // Step 2: Phone doesn't exist, send OTP
      setPhonenum(fullPhoneNumber);
      await sendPhoneRegistrationOTP(fullPhoneNumber);

      // Step 3: Show OTP screen
      setShowLoader(false);
      setIsEmailOtpEnabled(false);
      setShowRegisterContent(false);
      setShowOTPContent(true);
      setTimeLeft(120);
      setIsCounting(true);
    } catch (error) {
      setShowLoader(false);
      console.error("Phone registration error:", error);
      toast.error(t(error?.message) || t("registrationFailed"));
    }
  };

  // Handle email registration flow
  const handleEmailRegistration = async (phoneNumber) => {
    try {
      const response = await userRegisterApi({
        name: registerFormData?.name,
        email: registerFormData?.email,
        mobile: phoneNumber && phoneNumber?.getNationalNumber(),
        password: registerFormData?.password,
        re_password: registerFormData?.confirmPassword,
        country_code: phoneNumber && phoneNumber?.getCountryCode(),
        type: "3", // Type 3 for email registration
      });

      if (!response.error) {
        setIsEmailOtpEnabled(true);
        setShowRegisterContent(false);
        setShowOTPContent(true);
        setEmailTimeLeft(120);
        setIsEmailCounting(true);
        toast.success(t("otpSentToEmail"));
        setShowLoader(false);
      }
    } catch (error) {
      setShowLoader(false);
      console.error("Email registration error:", error);
      toast.error(t(error?.message));
      resetRegistrationForm();
      if (ShowEmailLogin) {
        setShowRegisterContent(false);
        setShowEmailContent(true)
        setShowEmailLoginForm(true)
      } else {
        setShowPhoneLogin(true)
      }
    }
  };

  // Main registration handler
  const handleRegisterUser = async (e) => {
    e.preventDefault();

    // Step 1: Validate form
    if (!validateRegistrationForm()) {
      return;
    }

    setShowLoader(true);

    try {
      // Step 2: Parse phone number if provided
      let phoneNumber;
      if (registerFormData?.phone) {
        const { mobile, country_code } = phonePartsForApi(
          registerFormData.phone,
          registerFormData.countryCode,
        );
        phoneNumber = phoneUtil.parseAndKeepRawInput(`+${country_code}${mobile}`, "ZZ");

        if (phoneNumber && !phoneNumber?.getCountryCode() && phoneNumber.getNationalNumber().length < 10) {
          toast.error(t("validPhonenum"));
          setShowLoader(false);
          return;
        }
      }

      // Step 3: Route to appropriate registration flow
      if (isMobileReq) {
        const { mobile, country_code } = phonePartsForApi(
          registerFormData.phone,
          registerFormData.countryCode,
        );
        const phNumber = phoneUtil.parseAndKeepRawInput(`+${country_code}${mobile}`, "ZZ");
        await handlePhoneRegistration(phNumber);
      } else {
        // Email registration flow
        await handleEmailRegistration(phoneNumber);
      }
    } catch (error) {
      setShowLoader(false);
      console.error("Registration error:", error);
      toast.error(t(error?.message) || t("registrationFailed"));
    }
  };



  // Forgot Password Handler — sends OTP to email, transitions to OTP form
  const handleForgotPasswordSubmit = async (email) => {
    if (!email) {
      toast.error(t("pleaseEnterEmail"));
      return;
    }

    const isEmailValid = /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email);
    if (!isEmailValid) {
      toast.error(t("invalidEmail"));
      return;
    }

    setShowLoader(true);

    try {
      const response = await getOTPApi({ email });
      if (!response.error) {
        toast.success(t(response?.message) || t("otpSentToEmail"));
        // Store email and mark the flow
        setForgotPasswordEmail(email);
        setForgotPasswordViaEmail(true);
        // Transition to OTP form in email mode
        setIsEmailOtpEnabled(true);
        setEmailOtp("");
        setEmailTimeLeft(120);
        setIsEmailCounting(true);
        setShowForgotPasswd(false);
        setShowEmailContent(false);
        setShowPhoneLogin(false);
        setShowRegisterContent(false);
        setShowOTPContent(true);
        setShowLoader(false);
      }
    } catch (error) {
      console.error("Forgot password OTP error:", error);
      toast.error(t(error?.message) || t("somethingWentWrong"));
      setShowLoader(false);
    }
  };

  const getApiErrorMessage = (error, fallback = "somethingWentWrong") =>
    error?.response?.data?.message
    || error?.data?.message
    || error?.message
    || fallback;

  // Email Forgot Password — update password & auto-login
  const handleEmailForgotPasswordUpdate = async (e) => {
    e.preventDefault();

    // Validate password inputs
    if (!phonePassword || phonePassword.length < 6) {
      toast.error(t("passwordLengthError"));
      return;
    }
    if (phonePassword !== confirmPhnPass) {
      toast.error(t("passwordsNotMatch"));
      return;
    }

    setShowLoader(true);

    try {
      const updateResponse = await updateEmailPasswordApi({
        email: forgotPasswordEmail,
        password: phonePassword,
        re_password: confirmPhnPass,
      });

      if (updateResponse?.error) {
        setShowLoader(false);
        toast.error(t(updateResponse?.message) || t("passwordUpdateFailed"));
        return;
      }

      try {
        const res = await userSignup({
          type: "3",
          email: forgotPasswordEmail,
          password: phonePassword,
          fcm_id: FcmToken,
        });

        if (!res.error) {
          toast.success(t("passwordResetSuccess") || "Password reset successful");
          dispatch(setAuth({ data: res?.data }));
          dispatch(setJWTToken({ data: res?.token }));
          setPhonePassword("");
          setConfirmPhnPass("");
          setForgotPasswordViaEmail(false);
          setForgotPasswordEmail("");
          onCloseLogin();
        } else if (res?.error && res?.key === "accountDeactivated") {
          handleAccountDeactivated();
        } else {
          toast.success(t("passwordResetSuccess") || "Password updated");
          toast.error(t(res?.message) || "Sign in manually with your new password.");
        }
      } catch (loginError) {
        console.error("Email forgot password auto-login:", getApiErrorMessage(loginError), loginError);
        toast.success(t("passwordResetSuccess") || "Password updated");
        toast.error(t(getApiErrorMessage(loginError)) || t("somethingWentWrong"));
      }
    } catch (error) {
      console.error("Email forgot password update error:", getApiErrorMessage(error), error);
      toast.error(t(getApiErrorMessage(error)) || t("somethingWentWrong"));
    } finally {
      setShowLoader(false);
    }
  };

  return (
    <>
      <Dialog open={showLogin} onOpenChange={setShowLogin}>
        <DialogContent
          onInteractOutside={(e) => e.preventDefault()}
          className="mx-auto max-w-md rounded-lg p-0 shadow-lg sm:max-w-lg [&>button]:hidden max-h-screen overflow-y-auto custom-scrollbar"
        >
          <DialogHeader className="w-full">
            <DialogTitle className="flex w-full items-center justify-between border-b p-3 sm:p-3 md:p-6">
              <div className="truncate text-base font-semibold sm:text-xl md:text-2xl">
                {resetMobilePass && t("setPassword")}
                {showRegisterOptions && t("registerOptions")}
                {showPhoneLogin && t("loginOrRegister")}
                {showForgotPasswd && t("resetPassword")}
                {showEmailContent && ShowEmailLogin && t("loginWithEmail")}
                {AllowSocialLogin && !ShowEmailLogin && !ShowPhoneLogin && t("loginNow")}
                {showOTPContent && t("verification")}
                {showRegisterContent && t("registerAccount")}
              </div>
              <AiOutlineClose
                className="primaryBackgroundBg leadColor font-bold rounded-xl h-6 w-6 flex-shrink-0 p-1 md:p-2 hover:cursor-pointer sm:h-7 sm:w-7 md:h-10 md:w-10"
                onClick={onCloseLogin}
              />
            </DialogTitle>
            <DialogDescription className="sr-only">
              Auth Modal
            </DialogDescription>
          </DialogHeader>

          {showForgotPasswd ? (
            <ForgotPasswordForm
              showLoader={showLoader}
              onBackToLogin={handleBackToLogin}
              onSubmit={handleForgotPasswordSubmit}
            />
          ) : showEmailContent ? (
            <EmailLoginForm
              signInFormData={signInFormData}
              handleSignInInputChange={handleSignInInputChange}
              SignInWithEmail={(e) => SignInWithEmail(e)}
              handlesignUp={handlesignUp}
              AllowSocialLogin={AllowSocialLogin}
              ShowPhoneLogin={ShowPhoneLogin}
              handlePhoneLogin={handlePhoneLogin}
              handleGoogleSignup={handleGoogleSignup}
              showLoader={showLoader}
              emailReverify={emailReverify}
              onForgotPasswordClick={handleForgotPasswordClick}
              handleResendOTP={handleResendOTP}
              formatTime={formatTime}
              showEmailLoginForm={showEmailLoginForm}
              showEmailContent={showEmailContent}
            />
          ) : showPhoneLogin ? (
            <PhoneLoginForm
              value={value}
              setValue={setValue}
              onSignUp={onSignUp}
              AllowSocialLogin={AllowSocialLogin}
              handleEmailLoginshow={handleEmailLoginshow}
              CompanyName={CompanyName}
              handleGoogleSignup={handleGoogleSignup}
              ShowPhoneLogin={ShowPhoneLogin}
              showLoader={showLoader}
              handlesignUp={handlesignUp}
              phonePassword={phonePassword}
              setPhonePassword={setPhonePassword}
              handleCheckPhoneNumber={handleCheckPhoneNumber}
              handlePhoneLoginWithPassword={handlePhoneLoginWithPassword}
              ShowEmailLogin={ShowEmailLogin}
              handlePhoneForgotPassword={handlePhoneForgotPassword}
              showPasswordInput={showPasswordInput}
            />
          ) : showOTPContent ? (
            <OTPForm
              phonenum={phonenum}
              wrongNumber={wrongNumber}
              wrongEmail={wrongEmail}
              otp={otp}
              setOTP={setOTP}
              handleConfirm={handleConfirm}
              showLoader={showLoader}
              timeLeft={timeLeft}
              isEmailOtpEnabled={isEmailOtpEnabled}
              emailOtp={emailOtp}
              email={
                forgotPasswordViaEmail
                  ? forgotPasswordEmail
                  : registerFormData?.email
                    ? registerFormData?.email
                    : signInFormData?.email
              }
              setEmailOtp={setEmailOtp}
              handleEmailOtpVerification={handleEmailOtpVerification}
              isEmailCounting={isEmailCounting}
              emailTimeLeft={emailTimeLeft}
              formatTime={formatTime}
              isCounting={isCounting}
              handleResendOTP={handleResendOTP}
            />
          ) : showRegisterContent ? (
            <RegisterForm
              registerFormData={registerFormData}
              handleRegisterInputChange={handleRegisterInputChange}
              handleRegisterPhoneChange={handleRegisterPhoneChange}
              handleRegisterUser={handleRegisterUser}
              handleSignIn={handleSignIn}
              showLoader={showLoader}
              isMobileReq={isMobileReq}
            />
          ) : showRegisterOptions ? (
            <RegisterTypeForm
              registrationType={registerType}
              setRegistrationType={setRegisterType}
              showRegisterOptions={setShowRegisterOptions}
              setShowRegisterOptions={setShowRegisterOptions}
              CompanyName={CompanyName}
              ShowPhoneLogin={ShowPhoneLogin}
              AllowSocialLogin={AllowSocialLogin}
              handleShowSignUp={handleShowSignUp}
              handleSignIn={handleSignIn}
              ShowEmailLogin={ShowEmailLogin}
              handlesignUp={handlesignUp}
              handlePhoneLogin={handleShowSignUp}
              handleGoogleSignup={handleGoogleSignup}
            />
          ) : resetMobilePass ? (
            <PhoneLoginPasswordForm
              phone={phonenum}
              password={phonePassword}
              handlePasswordChange={setPhonePassword}
              confirmPassword={confirmPhnPass}
              handleConfirmPasswordChange={setConfirmPhnPass}
              onSubmit={forgotPasswordViaEmail ? handleEmailForgotPasswordUpdate : (firebaseAuthId ? handleForgotPasswordUpdate : handleUpdatePhonePassword)}
              showLoader={showLoader}
            />
          ) : <GoogleForm handleGoogleSignup={handleGoogleSignup} />}

          <div className="w-full border-t p-3 sm:p-3">
            <AuthFooter setShowLogin={setShowLogin} />
          </div>
          <div id="recaptcha-container" style={{ display: "none" }}></div>
        </DialogContent>
      </Dialog>
    </>
  );
};

export default LoginModal;