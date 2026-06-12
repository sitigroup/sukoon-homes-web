import ImageWithPlaceholder from "@/components/image-with-placeholder/ImageWithPlaceholder";
import { BiBuildingHouse, BiMessageSquareDetail, BiBell, BiDollarCircle, BiUserX, BiCreditCard, BiLogOut, BiNews, BiHeart, BiCheckShield } from "react-icons/bi";
import { FaRegCircleUser } from "react-icons/fa6";
import { RiAdvertisementLine } from "react-icons/ri";
import { FaRegCalendarAlt } from "react-icons/fa";
import { useTranslation } from "../context/TranslationContext";
import { useRouter } from "next/router";
import { useDispatch, useSelector } from "react-redux";
import { deleteUser, getAuth } from "firebase/auth";
import { isDemoMode, VerifiedUserBadge } from "@/utils/helperFunction";
import Swal from "sweetalert2";
import { beforeLogoutApi, deleteUserAccountApi } from "@/api/apiRoutes";
import { logout, setRole } from "@/redux/slices/authSlice";
import FirebaseData from "@/utils/Firebase";
import toast from "react-hot-toast";
import { Skeleton } from "@/components/ui/skeleton";

const UserSidebarSkeleton = () => (
    <div className="hidden h-full w-1/4 max-w-[318px] flex-col rounded-2xl border bg-white xl:flex">
        <div className="border-b p-4 newBorderColor">
            <div className="flex flex-col items-center justify-center gap-4 rounded-lg primaryCatBg p-6">
                <Skeleton className="h-16 w-16 rounded-full" />
                <div className="flex flex-col items-center gap-2">
                    <Skeleton className="h-5 w-32" />
                    <Skeleton className="h-4 w-40" />
                </div>
            </div>
        </div>

        <div className="flex flex-col gap-4 px-4 py-4">
            {Array.from({ length: 10 }).map((_, index) => (
                <div key={index} className="flex items-center gap-4 rounded-lg p-3">
                    <Skeleton className="h-5 w-5 shrink-0 rounded-full" />
                    <Skeleton className="h-4 w-40" />
                </div>
            ))}
        </div>
    </div>
)

const UserSidebar = ({ isLoading }) => {
    const t = useTranslation();
    const router = useRouter();
    const dispatch = useDispatch();
    const { signOut } = FirebaseData();

    const lang = router?.query?.lang;
    const pathname = router?.asPath;

    const user = useSelector(state => state?.User?.data)
    const webSettings = useSelector(state => state.WebSetting?.data);
    const FcmToken = useSelector((state) => state.WebSetting?.fcmToken);

    if (isLoading) {
        return <UserSidebarSkeleton />
    }

    // handle logout functionality
    const handleLogout = async () => {
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
                            dispatch(logout());
                            dispatch(setRole({ data: "user" }))
                            signOut();
                            toast.success(t("logoutSuccess"));
                            router.push("/");
                        }
                    } else {
                        dispatch(logout());
                        dispatch(setRole({ data: "user" }))
                        signOut();
                        toast.success(t("logoutSuccess"));
                        router.push("/");
                    }
                } catch (error) {
                    console.error("Error logging out:", error);
                }
            }
        });
    };

    // Handle delete account functionality
    const handleDeleteAccount = async () => {
        if (isDemoMode() && user?.is_demo_user) {
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
                        await deleteUserAccountApi();

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
                        await deleteUserAccountApi();
                        router.push("/");
                        toast.success(t("accountDeletedSuccessfully"));
                        dispatch(logout());
                        signOut();
                    } catch (err) {
                        console.error(err);
                    }
                }
            }
        });
    };

    const menuItems = [
        { icon: <BiBuildingHouse className="w-4 h-4 xl:w-6 xl:h-6" />, label: t("myListing"), route: `/user/listings?tab=properties&lang=${lang}` },
        { icon: <RiAdvertisementLine className="w-4 h-4 xl:w-6 xl:h-6" />, label: t("myAdvertisements"), route: `/user/advertisement?lang=${lang}` },
        { icon: <FaRegCalendarAlt className="w-4 h-4 xl:w-6 xl:h-6" />, label: t("myAppointments"), route: `/user/appointments?lang=${lang}` },
        { icon: <BiMessageSquareDetail className="w-4 h-4 xl:w-6 xl:h-6" />, label: t("messages"), route: `/user/chat?lang=${lang}` },
        { icon: <BiBell className="w-4 h-4 xl:w-6 xl:h-6" />, label: t("notifications"), route: `/user/notifications?lang=${lang}` },
        { icon: <BiNews className="w-4 h-4 xl:w-6 xl:h-6" />, label: t("personalizedFeeds"), route: `/user/personalized-feeds?lang=${lang}` },
        { icon: <BiCreditCard className="w-4 h-4 xl:w-6 xl:h-6" />, label: t("mySubscriptions"), route: `/user/my-subscriptions?lang=${lang}` },
        { icon: <BiDollarCircle className="w-4 h-4 xl:w-6 xl:h-6" />, label: t("transactionHistory"), route: `/user/transaction-history?lang=${lang}` },
        {
            icon: <BiCheckShield className="w-4 h-4 xl:w-6 xl:h-6" />,
            label: "My Verification Orders",
            route: `/my-verification-orders?lang=${lang || "en"}`,
            isVerificationOrders: true,
        },
        { icon: <FaRegCircleUser className="w-4 h-4 xl:w-6 xl:h-6" />, label: t("myProfile"), route: `/user/profile?lang=${lang}` },
        { icon: <BiHeart className="size-4 md:size-5" />, label: t("favourites"), route: `/user/favourites?lang=${lang}` },
        { icon: <BiLogOut className="w-4 h-4 xl:w-6 xl:h-6" />, label: t("logout"), route: `/user/logout?lang=${lang}`, onClick: handleLogout },
        { icon: <BiUserX className="w-4 h-4 xl:w-6 xl:h-6" />, label: t("deleteAccount"), route: `/user/delete-account?lang=${lang}`, onClick: handleDeleteAccount },
    ];

    return (
        <div className="w-1/4 max-w-[318px] rounded-2xl border newBorderColor bg-white hidden xl:block h-full">
            <div className="p-4 gap-2 border-b newBorderColor">
                <div className="p-6 gap-4 rounded-lg primaryCatBg flex flex-col items-center justify-center">
                    {user?.profile ? (
                        <ImageWithPlaceholder
                            src={user?.profile}
                            alt={user?.name}
                            className="rounded-full border flex-shrink-0 h-16 w-16 aspect-[64/64]"
                        />
                    ) : (
                        <div className="rounded-full h-16 w-16 flex items-center justify-center primaryBg text-white text-xl font-bold uppercase border  shrink-0">
                            {user?.name?.charAt(0)}
                        </div>
                    )}
                    <div className="flex w-full min-w-0 flex-col gap-1 items-center justify-center">
                        <p className="flex w-full min-w-0 items-center gap-1 font-bold text-xl brandColor">
                            <span className="min-w-0 flex-1 truncate text-center" title={user?.name}>
                                {user?.name}
                            </span>
                            {user?.is_user_verified ?
                                <VerifiedUserBadge color={webSettings?.system_color} width={20} height={20} />
                                : null}
                        </p>
                        <p className="w-full min-w-0 truncate text-center text-base leadColor" title={user?.email}>{user?.email}</p>
                    </div>
                </div>

            </div>
            <div className="px-4 py-4 flex flex-col gap-4">
                {menuItems.map((item, index) => {
                    const isActive = item.isVerificationOrders
                        ? router.pathname === "/my-verification-orders" ||
                          pathname?.includes("/my-verification-orders")
                        : pathname?.includes(item.route?.split("?")[0] || "");

                    return (
                        <div key={item.isVerificationOrders ? "my-verification-orders" : index} onClick={() => {
                            if (item?.onClick) {
                                item.onClick();
                            } else {
                                router.push(item.route);
                            }
                        }} className={`flex items-center gap-4 p-3 rounded-lg cursor-pointer transition-all ${isActive ? "primaryBg text-white" : ""}`}>
                            <span className="shrink-0">{item.icon}</span>
                            <span className="text-nowrap truncate text-sm lg:text-base font-medium">{item.label}</span>
                        </div>
                    )
                })}
            </div>
        </div>
    )
}

export default UserSidebar
