import { BiHeart, BiLogIn, BiCheckShield } from "react-icons/bi";
import { BiBuildingHouse, BiMessageSquareDetail, BiBell, BiDollarCircle, BiUserX, BiCreditCard, BiNews, BiTachometer } from "react-icons/bi";
import { FaRegCircleUser } from "react-icons/fa6";
import { RiAdvertisementLine } from "react-icons/ri";
import { FaExclamation, FaRegCalendarAlt } from "react-icons/fa";
import ImageWithPlaceholder from "../image-with-placeholder/ImageWithPlaceholder";
import { useTranslation } from "../context/TranslationContext";
import { useRouter } from "next/router";
import { useMediaQuery } from "@/hooks/use-media-query";
import { useDispatch, useSelector } from "react-redux";
import { logout, setRole } from "@/redux/slices/authSlice";
import { isDemoMode } from "@/utils/helperFunction";
import Swal from "sweetalert2";
import { deleteUser, getAuth } from "firebase/auth";
import { deleteUserAccountApi } from "@/api/apiRoutes";
import FirebaseData from "@/utils/Firebase";
import { VerifiedUserBadge } from "@/utils/helperFunction";
import { MdOutlineVerifiedUser } from "react-icons/md";
import dynamic from "next/dynamic";

const DropdownTrustSnippet = dynamic(
  () => import("@/plugins/trust-verification/ui/DropdownTrustSnippet"),
  { ssr: false, loading: () => null },
);

export default function UserDropDown({ user, handleLogout, onClose }) {
    const t = useTranslation();
    const router = useRouter();
    const dispatch = useDispatch()
    const { lang } = router?.query;
    const { signOut } = FirebaseData();
    const userData = useSelector((state) => state?.User?.data)
    const webSettings = useSelector(state => state.WebSetting?.data);
    const isMobileAndTabletScreen = useMediaQuery("(max-width: 1024px)");
    const isAgentOwnListingDetailsPage =
        router?.pathname?.startsWith("/agent/my-property") ||
        router?.pathname?.startsWith("/agent/my-project");
    const isAgent = userData?.become_agent_status === "approved" && userData?.is_agent === true;
    const isAgentVerificationPending = userData?.become_agent_status === "pending";
    const isAgentVerificationRejected = userData?.become_agent_status === "rejected";
    const isAgentVerificationNotApplied = userData?.become_agent_status === "not_applied"
    const isBecomeAgentPage = router?.pathname === "/become-agent";
    const isMyVerificationOrdersPage =
        router?.pathname === "/my-verification-orders" ||
        router?.asPath?.startsWith("/my-verification-orders");

    // Handle delete account functionality
    const handleDeleteAccount = async () => {
        if (isDemoMode() && userData?.is_demo_user) {
            Swal.fire({
                title: t("opps"),
                text: t("notAllowdDemo"),
                icon: "warning",
                showCancelButton: false,
                customClass: {
                    confirmButton: "Swal-confirm-buttons",
                    cancelButton: "Swal-cancel-buttons",
                },
                confirmButtonText: t("ok"),
                cancelButtonText: t("cancel"),
            });
            return; // Stop further execution
        }

        // Initialize Firebase Authentication
        const auth = getAuth();

        // Get the currently signed-in user
        const user = auth?.currentUser;

        Swal.fire({
            title: t("areYouSure"),
            text: t("youNotAbelToRevertThis"),
            icon: "warning",
            showCancelButton: true,
            customClass: {
                confirmButton: "Swal-confirm-buttons",
                cancelButton: "Swal-cancel-buttons",
            },
            cancelButtonColor: "#d33",
            confirmButtonText: t("yes"),
            cancelButtonText: t("cancel"),
        }).then(async (result) => {
            if (result.isConfirmed) {
                // Delete the user
                if (user) {
                    try {
                        // Firebase deleteUser returns undefined on success
                        await deleteUser(user);

                        // After successful Firebase deletion, call the API
                        const response = await deleteUserAccountApi();

                        // Handle success
                        router.push("/");
                        toast.success(t("accountDeletedSuccessfully"));
                        dispatch(logout());
                        signOut();
                    } catch (error) {
                        console.error("Error deleting user:", error.message);
                        if (error.code === "auth/requires-recent-login") {
                            router.push("/");
                            toast.error(error.message);
                            dispatch(logout());
                            signOut();
                        }
                    }
                } else {
                    try {
                        const response = await deleteUserAccountApi();
                        router.push("/");
                        toast.success(t("accountDeletedSuccessfully"));
                        dispatch(logout());
                        signOut();
                    } catch (err) {
                        console.error(err);
                    }
                }
            } else {
                console.error("delete account process canceled ");
            }
        });
    };


    const menuItems = [
        { icon: <BiBuildingHouse className="size-4 md:size-5" />, label: t("myListing"), route: `/user/listings?tab=properties&lang=${lang}` },
        ...(isMobileAndTabletScreen ? [{ icon: <RiAdvertisementLine className="size-4 md:size-5" />, label: t("myAdvertisements"), route: `/user/advertisement?lang=${lang}&tab=properties` }] : []),
        ...(isMobileAndTabletScreen ? [{ icon: <FaRegCalendarAlt className="size-4 md:size-5" />, label: t("myAppointments"), route: `/user/appointments?lang=${lang}` }] : []),
        { icon: <BiMessageSquareDetail className="size-4 md:size-5" />, label: t("messages"), route: `/user/chat?lang=${lang}` },
        { icon: <BiBell className="size-4 md:size-5" />, label: t("notifications"), route: `/user/notifications?lang=${lang}` },
        ...(isMobileAndTabletScreen ? [{ icon: <BiNews className="size-4 md:size-5" />, label: t("personalizedFeeds"), route: `/user/personalized-feeds?lang=${lang}` }] : []),
        { icon: <BiCreditCard className="size-4 md:size-5" />, label: t("mySubscriptions"), route: `/user/my-subscriptions?lang=${lang}` },
        {
            icon: <BiCheckShield className="size-4 md:size-5" />,
            label: "My Verification Orders",
            route: `/my-verification-orders?lang=${lang || "en"}`,
            isVerificationOrders: true,
        },
        ...(isMobileAndTabletScreen ? [{ icon: <BiDollarCircle className="size-4 md:size-5" />, label: t("transactionHistory"), route: `/user/transaction-history?lang=${lang}` }] : []),
        { icon: <FaRegCircleUser className="size-4 md:size-5" />, label: t("myProfile"), route: `/user/profile?lang=${lang}` },
        { icon: <BiHeart className="size-4 md:size-5" />, label: t("favourites"), route: `/user/favourites?lang=${lang}` },
        { icon: <BiUserX className="size-4 md:size-5" />, label: t("deleteAccount"), route: `/user/delete-account?lang=${lang}`, onClick: handleDeleteAccount },
    ];

    const agentMenuItems = [
        { icon: <BiTachometer className="size-4 md:size-5" />, label: t("myDashboard"), route: `/agent/dashboard?lang=${lang}` },
        { icon: <BiBuildingHouse className="size-4 md:size-5" />, label: t("myProperties"), route: `/agent/properties?lang=${lang}` },
        { icon: <BiBuildingHouse className="size-4 md:size-5" />, label: t("myProjects"), route: `/agent/projects?lang=${lang}` },
        { icon: <FaRegCalendarAlt className="size-4 md:size-5" />, label: t("bookings"), route: `/agent/bookings?lang=${lang}` },
        { icon: <BiMessageSquareDetail className="size-4 md:size-5" />, label: t("messages"), route: `/agent/chat?lang=${lang}` },
        { icon: <BiBell className="size-4 md:size-5" />, label: t("notifications"), route: `/agent/notifications?lang=${lang}` },
        { icon: <FaRegCircleUser className="size-4 md:size-5" />, label: t("myProfile"), route: `/agent/profile?lang=${lang}` },
    ];

    const showAgentOwnListingMenu = isAgent && isAgentOwnListingDetailsPage;
    const activeMenuItems = showAgentOwnListingMenu ? agentMenuItems : menuItems;

    const handleSwitchToAgentClick = () => {
        onClose?.();

        if (isAgent) {
            dispatch(setRole({ data: "agent" }))
            return router.push(`/agent/dashboard?lang=${lang}`)
        } else if (userData?.become_agent_status === "not_applied" && userData?.is_agent === false) {
            return router.push(`/become-agent?lang=${lang}`)
        } else if (isAgentVerificationPending) {
            return router.push(`/agent/dashboard?lang=${lang}`)
        }
    }



    return (
        <div className="dropdown-content absolute right-0 top-full z-[100] mt-3 min-w-[250px] max-w-[min(300px,calc(100vw-2rem))] w-full md:min-w-[300px] rounded-b-2xl bg-white shadow-[0_10px_40px_rgba(0,0,0,0.12)] border border-gray-100 overflow-hidden">
            {/* User Info Section */}
            <div className={`flex items-center gap-3 p-3 md:p-4 ${!isMobileAndTabletScreen && !isAgent && !isAgentVerificationPending ? "border-b" : "border-b-0"} newBorderColor`}>
                <div className=" border border-leadBorder p-0.5 flex-shrink-0 rounded-full">
                    {user?.profile ? (
                        <ImageWithPlaceholder
                            src={user?.profile}
                            alt={user?.name}
                            className="rounded-full border aspect-[44/44] md:aspect-[56/56] h-11 w-11 md:h-14 md:w-14"
                        />
                    ) : (
                        <div className="rounded-full md:h-14 md:w-14 h-10 w-10 flex items-center justify-center primaryBg text-white text-xl font-bold uppercase border  shrink-0">
                            {(user?.name || user?.email)?.charAt(0)}
                        </div>
                    )}
                </div>
                <div className={`flex min-w-0 flex-col overflow-hidden`}>
                    <p className="flex w-full min-w-0 items-center gap-1 font-bold text-lg brandColor">
                        <span className="min-w-0 flex-1 truncate" title={user?.name}>
                            {user?.name}
                        </span>
                        {userData?.is_user_verified ? <VerifiedUserBadge color={webSettings?.system_color || "#087C7C"} width={20} height={20} /> : null}
                    </p>
                    <p className="text-sm leadColor truncate">{user?.email}</p>
                    <DropdownTrustSnippet />
                </div>
            </div>


            {isAgent || isAgentVerificationPending ? (
                <div className="px-3 md:px-4 pb-3 md:pb-4 border-b newBorderColor">
                    <button className="w-full border leadBorder rounded-lg py-1 md:py-2 font-bold secondryTextColor" onClick={handleSwitchToAgentClick}>
                        {t("switchToAgent")}
                    </button>
                </div>) : null}

            {isMobileAndTabletScreen && isAgentVerificationNotApplied && !isBecomeAgentPage ? (
                <div className="px-3 md:px-4 pb-3 md:pb-4 border-b newBorderColor">
                    <button className="w-full flex justify-center items-center gap-2 border leadBorder rounded-lg py-2 md:py-2 font-bold secondryTextColor hover:brandBg hover:text-white" onClick={handleSwitchToAgentClick}>
                        <MdOutlineVerifiedUser className="w-5 h-5" />
                        {t("becomeAgent")}
                    </button>
                </div>
            ) : isMobileAndTabletScreen && isAgentVerificationRejected && !isBecomeAgentPage ?
                (
                    <div className="px-3 md:px-4 pb-3 md:pb-4 border-b newBorderColor">
                        <button className="w-full flex justify-center items-center gap-2 border leadBorder rounded-lg py-2 md:py-2 font-bold secondryTextColor hover:brandBg hover:text-white" onClick={() => router?.push(`/become-agent/?lang=${lang}`)}>
                            <FaExclamation className="w-5 h-5" />
                            {t("requestRejected")}
                        </button>
                    </div>
                ) : null}

            <div className={`px-4 py-2 max-h-[400px] overflow-y-auto ${isMobileAndTabletScreen && !isAgent && isBecomeAgentPage ? "border-t" : ""} newBorderColor`}>
                {activeMenuItems.map((item, index) => {
                    const isActive =
                        item.isVerificationOrders && isMyVerificationOrdersPage;
                    return (
                    <button
                        key={item.isVerificationOrders ? "my-verification-orders" : index}
                        onClick={() => {
                            onClose?.();

                            if (item.onClick) {
                                item.onClick();
                            } else {
                                router.push(item.route);
                            }
                        }}
                        className={`w-full flex items-center gap-1 md:gap-2 p-2 md:p-3 rounded-lg transition-colors font-medium ${
                            isActive
                                ? "primaryBg text-white"
                                : "brandColor hover:primaryBg hover:text-white"
                        }`}
                    >
                        {item.icon}
                        <span className="text-base md:text-lg">{item.label}</span>
                    </button>
                    );
                })}
            </div>


            {!showAgentOwnListingMenu ? (
                <div className="p-4 pt-2 border-t newBorderColor">
                    <button className="w-full flex items-center text-white gap-2 rounded-lg p-2 md:p-3 bgRed font-bold" onClick={() => {
                        onClose?.();
                        handleLogout();
                    }}>
                        <BiLogIn className="size-4 md:size-5" />
                        <span className="text-base md:text-lg">{t("logout")}</span>
                    </button>
                </div>
            ) : null}
        </div>
    );
}
