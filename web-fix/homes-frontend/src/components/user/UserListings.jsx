import { useEffect, useState } from "react";
import { useRouter } from "next/router";
import ImageWithPlaceholder from "@/components/image-with-placeholder/ImageWithPlaceholder";
import { BiMap, BiBuildingHouse } from "react-icons/bi";
import { FiEye } from "react-icons/fi";
import { BsThreeDotsVertical } from "react-icons/bs";
import { AiOutlineEdit } from "react-icons/ai";
import { MdAutorenew, MdDeleteOutline, MdOutlineSell, MdRemoveRedEye } from "react-icons/md";
import { FaCheck, FaCrown } from "react-icons/fa";
import { Avatar, AvatarFallback, AvatarImage } from "@/components/ui/avatar";
import { Switch } from "@/components/ui/switch";
import { Button } from "@/components/ui/button";
import { useQuery } from "@tanstack/react-query";
import { changePropertyStatusApi, changeProjectStatusApi, deletePropertyApi, deleteProjectApi, getAddedPropertiesApi, getUserProjectsApi, activateListingApi } from "@/api/apiRoutes";
import toast from "react-hot-toast";
import Swal from "sweetalert2";
import { useTranslation } from "../context/TranslationContext";
import { isRTL, renderStatusBadge, RejectionTooltip, formatPriceAbbreviated, handlePackageCheck, handleProjectAddCheck, handlePropertyAddCheck } from "@/utils/helperFunction";
import { exclamationIcon } from '@/assets/svg';
import CustomLink from "../context/CustomLink";
import ReusableTable from "@/components/ui/reusable-table";
import CustomPagination from "@/components/ui/custom-pagination";
import {
    Tooltip,
    TooltipContent,
    TooltipProvider,
    TooltipTrigger
} from "@/components/ui/tooltip";
import { NavigationMenu, NavigationMenuContent, NavigationMenuItem, NavigationMenuLink, NavigationMenuList, NavigationMenuTrigger } from '../ui/navigation-menu';
import ChangeStatusModal from '../agent/ChangeStatusModal';
import { Skeleton } from "@/components/ui/skeleton";
import { useSelector } from "react-redux";
import { PackageTypes } from '@/utils/checkPackages/packageTypes';
import { checkPackageAvailable } from '@/utils/checkPackages/checkPackage';
import PayAsYouGoModal from '@/components/modal/PayAsYouGoModal';
import PaymentSelectionModalWrapper from '@/components/modal/PaymentSelectionModalWrapper';
import { getPackagesApi, getPaymentSettingsApi, renewListingApi } from '@/api/apiRoutes';

const EditReasonTooltip = ({ reason }) => {
    const [open, setOpen] = useState(false);
    return (
        <TooltipProvider>
            <Tooltip open={open} onOpenChange={setOpen}>
                <TooltipTrigger
                    asChild
                    onClick={(e) => { e.preventDefault(); e.stopPropagation(); setOpen(true); }}
                >
                    <span className="cursor-help">
                        {exclamationIcon()}
                    </span>
                </TooltipTrigger>
                <TooltipContent side="top" className="max-w-xs" onPointerDownOutside={() => setOpen(false)}>
                    <p className="text-sm">{reason}</p>
                </TooltipContent>
            </Tooltip>
        </TooltipProvider>
    );
};

const TableLoadingSkeleton = ({ itemLength }) => (
    <div className="w-full">
        <div className="bg-gray-50 border-y">
            <div className="grid grid-cols-9 px-6 py-4 gap-4">
                {['w-32', 'w-24', 'w-24', 'w-32', 'w-24', 'w-24', 'w-24', 'w-24', 'w-20'].map((width, i) => (
                    <Skeleton key={i} className={`h-4 ${width}`} />
                ))}
            </div>
        </div>

        {[...Array(Number(itemLength))].map((_, rowIndex) => (
            <div key={rowIndex} className="border-b px-6 py-4">
                <div className="grid grid-cols-9 items-center gap-4">
                    <div className="flex items-center gap-3">
                        <Skeleton className="h-16 w-16 rounded-md" />
                        <div className="flex flex-col gap-2">
                            <Skeleton className="h-4 w-24" />
                            <Skeleton className="h-3 w-20" />
                        </div>
                    </div>
                    <Skeleton className="h-4 w-24" />
                    <Skeleton className="h-6 w-20 rounded-full" />
                    <div className="flex items-center justify-center gap-2">
                        <Skeleton className="h-8 w-8 rounded-full" />
                        <Skeleton className="h-8 w-8 rounded-full" />
                    </div>
                    <Skeleton className="h-6 w-20 rounded-full" />
                    <div className="flex items-center justify-center gap-2">
                        <Skeleton className="h-6 w-12 rounded-full" />
                        <Skeleton className="h-4 w-16" />
                    </div>
                    <Skeleton className="h-4 w-20" />
                    <Skeleton className="h-4 w-24" />
                    <div className="flex items-center justify-center gap-2">
                        <Skeleton className="h-8 w-8 rounded-md" />
                        <Skeleton className="h-8 w-8 rounded-md" />
                    </div>
                </div>
            </div>
        ))}
    </div>
);

const formatDisplayValue = (value) => {
    if (!value) return "-";

    return String(value)
        .replace(/_/g, " ")
        .replace(/\b\w/g, (char) => char.toUpperCase());
};

const getLocationLabel = (item) => [item?.city, item?.state, item?.country].filter(Boolean).join(", ") || "-";

const getListingImage = (item) => item?.title_image || item?.image || "";

const getListingTitle = (item) => item?.translated_title || item?.title || "-";

const getListingType = (item, activeTab) => {
    const rawType = activeTab === "projects" ? item?.type : item?.property_type;
    return formatDisplayValue(rawType);
};

const normalizeListing = (item, activeTab) => ({
    ...item,
    id: item?.id,
    slugId: item?.slug_id,
    title: getListingTitle(item),
    image: getListingImage(item),
    type: getListingType(item, activeTab),
    location: getLocationLabel(item),
    propertyType: item?.category?.translated_name || item?.category?.category || "-",
    views: item?.total_click ?? item?.views ?? 0,
    adminStatus: formatDisplayValue(item?.request_status),
    statusActive: Number(item?.status) === 1 && item?.request_status !== "pending" && item?.request_status !== "rejected",
    price: item?.price || "-",
    requestStatus: item?.request_status,
    is_feature_available: item?.is_feature_available,
    property_type: item?.property_type || null,
    reject_reason: item?.reject_reason || null,
    edit_reason: item?.edit_reason || null,
    is_expired: item?.is_expired ?? 0,
    interestedUsers: item?.interested_users || [],
    categoryImage: item?.category?.image || "",
});

const MyListings = () => {
    const t = useTranslation();
    const router = useRouter();
    const isRtl = isRTL();
    const { lang } = router?.query;
    const activeTab = router?.query?.tab === "projects" ? "projects" : "properties";
    const isRouterReady = router.isReady;
    const [currentPage, setCurrentPage] = useState(1);
    const itemsPerPage = 4;
    const offset = (currentPage - 1) * itemsPerPage;

    // Status change modal state
    const [statusModalOpen, setStatusModalOpen] = useState(false);
    const [selectedListing, setSelectedListing] = useState(null);
    const webSettings = useSelector(state => state?.WebSetting?.data);

    // Renew / pay as you go state
    const [showPayAsYouGoModal, setShowPayAsYouGoModal] = useState(false);
    const [payAsYouGoData, setPayAsYouGoData] = useState(null);
    const [paymentSettingsData, setPaymentSettingsData] = useState([]);
    const [showPaymentSelection, setShowPaymentSelection] = useState(false);
    const [paymentLoading, setPaymentLoading] = useState(false);
    const [pendingPayAsYouGoAction, setPendingPayAsYouGoAction] = useState(null);

    const handleOpenStatusModal = (item) => {
        setSelectedListing(item);
        setStatusModalOpen(true);
    };

    const handleFeatureClick = (e, itemId) => {
        e.preventDefault();
        handlePackageCheck(e, activeTab === "projects" ? "project_feature" : "property_feature", router, itemId, null, null, null, t, true);
    };

    const isPayAsYouGoPackageError = (message = "") => {
        const normalizedMessage = String(message).toLowerCase();
        return (
            normalizedMessage.includes("package limit is exceeded") ||
            normalizedMessage.includes("upgrade your package to activate this listing")
        );
    };

    const clearPayAsYouGoFlow = () => {
        setShowPayAsYouGoModal(false);
        setShowPaymentSelection(false);
        setPendingPayAsYouGoAction(null);
        setPayAsYouGoData(null);
    };

    const openPayAsYouGoFlow = async ({ actionType, listingId, listingType }) => {
        try {
            const packagesRes = await getPackagesApi();
            const payAsYouGoList = packagesRes?.pay_as_you_go || [];
            const listingPayAsYouGo = payAsYouGoList.find(
                (item) => item.type === listingType && item.status === 1
            );
            setPayAsYouGoData(listingPayAsYouGo || null);
        } catch (err) {
            console.error("Error fetching pay as you go data:", err);
            setPayAsYouGoData(null);
        }

        setPendingPayAsYouGoAction({ actionType, listingId, listingType });
        setShowPayAsYouGoModal(true);
    };

    const handlePostPaymentRenew = async () => {
        const pendingAction = pendingPayAsYouGoAction;
        if (!pendingAction?.listingId || !pendingAction?.actionType) return;

        try {
            if (pendingAction.actionType === "renew") {
                const res = await renewListingApi({ id: pendingAction.listingId, type: pendingAction.listingType });
                if (!res?.error) {
                    toast.success(t(res?.message) || t("renewed"));
                    listingsQuery.refetch();
                } else {
                    toast.error(t(res?.message) || t("somethingWentWrong"));
                }
                return;
            }

            if (pendingAction.actionType === "approve") {
                const res = await activateListingApi({
                    id: pendingAction.listingId,
                    listing_type: pendingAction.listingType,
                });
                if (!res?.error) {
                    toast.success(t(pendingAction.listingType === "project" ? "projectSubmittedForApproval" : "propertySubmittedForApproval"));
                    listingsQuery.refetch();
                } else {
                    toast.error(t(res?.message) || t("somethingWentWrong"));
                }
            }
        } catch (error) {
            console.error("Error in renew listing after payment:", error);
            toast.error(t("somethingWentWrong"));
        } finally {
            clearPayAsYouGoFlow();
        }
    };

    const handleRenewListing = async (item) => {
        try {
            const listingType = activeTab === "projects" ? "project" : "property";
            const packageType = activeTab === "projects" ? PackageTypes.PROJECT_LIST : PackageTypes.PROPERTY_LIST;
            const isAvailable = await checkPackageAvailable(packageType);

            if (isAvailable) {
                const res = await renewListingApi({ id: item.id, type: listingType });
                if (!res?.error) {
                    toast.success(t(res?.message) || t("renewed"));
                    listingsQuery.refetch();
                } else {
                    toast.error(t(res?.message) || t("somethingWentWrong"));
                }
                return;
            }

            await openPayAsYouGoFlow({ actionType: "renew", listingId: item.id, listingType });
        } catch (error) {
            console.error("Error checking package for renew:", error);
            toast.error(t("somethingWentWrong"));
        }
    };

    useEffect(() => {
        const fetchPaymentSettings = async () => {
            try {
                const res = await getPaymentSettingsApi();
                if (!res?.error) setPaymentSettingsData(res?.data || []);
            } catch (err) {
                console.error("Error fetching payment settings:", err);
            }
        };

        fetchPaymentSettings();
    }, []);

    const listingsQuery = useQuery({
        queryKey: ["userListings", activeTab, currentPage],
        enabled: isRouterReady,
        queryFn: async () => {
            const params = {
                limit: itemsPerPage.toString(),
                offset: offset.toString(),
                added_as: "user",
            };

            if (activeTab === "projects") {
                return await getUserProjectsApi(params);
            }

            return await getAddedPropertiesApi(params);
        },
        staleTime: 10 * 60_000,
        refetchOnWindowFocus: false,
        refetchOnReconnect: false,
        refetchOnMount: "always",
        retry: 3
    });

    const currentListings = (listingsQuery.data?.data || []).map((item) => normalizeListing(item, activeTab));
    const totalItems = Number(listingsQuery.data?.total) || 0;
    const isLoading = listingsQuery.isLoading || listingsQuery.isFetching;
    const showPagination = activeTab !== "projects" || totalItems > itemsPerPage;

    const handleToggleStatus = (id) => {
        const currentItem = currentListings.find((item) => item.id === id);
        if (!currentItem) return;

        const nextStatus = currentItem.statusActive ? 0 : 1;

        const statusApi = activeTab === "projects" ? changeProjectStatusApi : changePropertyStatusApi;
        const statusPayload = activeTab === "projects"
            ? { project_id: id, status: nextStatus }
            : { property_id: id, status: nextStatus };

        statusApi(statusPayload)
            .then((response) => {
                if (!response?.error) {
                    toast.success(t(activeTab === "projects" ? "projectStatusUpdatedSuccessfully" : "statusUpdatedSuccessfully"));
                    listingsQuery.refetch();
                } else {
                    toast.error(t(response?.message) || t("somethingWentWrong"));
                }
            })
            .catch(() => {
                toast.error(t("somethingWentWrong"));
            });
    };

    const handlePageChange = (page) => {
        setCurrentPage(page);
    };

    const handleTabChange = (tab) => {
        setCurrentPage(1);

        router.push(
            {
                pathname: router.pathname,
                query: {
                    ...router.query,
                    tab,
                },
            }
        );
    };

    const handleAddListing = (e) => {
        if (activeTab === "projects") {
            handleProjectAddCheck(e, router, t, true);
        } else if (activeTab === "properties") {
            handlePropertyAddCheck(e, router, t, true);
        }
    };

    const handleViewListing = (item) => {
        const targetPath = activeTab === "projects" ? "/my-project" : "/my-property";
        router.push(`${targetPath}/${item.slugId}?lang=${router.query?.lang || "en"}`);
    };

    const handleEditListing = (item) => {
        const targetPath = activeTab === "projects" ? "/user/edit-project" : "/user/edit-property";
        router.push(`${targetPath}/${item.slugId}?lang=${router.query?.lang || "en"}`);
    };

    const handleInterestedUsers = (item) => {
        if (!item?.slugId) return;
        router.push(`/user/interested/${item.slugId}?lang=${router.query?.lang || "en"}`);
    };

    const handleDeleteListing = (item) => {
        const isProjectListing = activeTab === "projects";

        Swal.fire({
            icon: "warning",
            title: t("areYouSure"),
            text: isProjectListing ? t("deleteProjectWarning") : t("youWantToDeleteProperty"),
            showCancelButton: true,
            confirmButtonText: t("yesDelete") || t("delete"),
            cancelButtonText: t("cancel"),
            customClass: {
                confirmButton: "Swal-confirm-buttons",
                cancelButton: "Swal-cancel-buttons",
            },
        }).then(async (result) => {
            if (!result.isConfirmed) return;

            try {
                const response = isProjectListing
                    ? await deleteProjectApi({ id: item.id })
                    : await deletePropertyApi({ id: item.id });

                if (!response?.error) {
                    toast.success(t(response?.message) || t("deletedSuccessfully"));
                    listingsQuery.refetch();
                } else {
                    toast.error(t(response?.message) || t("somethingWentWrong"));
                }
            } catch (error) {
                toast.error(t(error?.message) || t("somethingWentWrong"));
            }
        });
    };


    const handleSubmitListingForApproval = async (item) => {
        const isProjectListing = activeTab === "projects";
        try {
            const response = await activateListingApi({
                id: item.id,
                listing_type: isProjectListing ? "project" : "property",
            });
            if (!response?.error) {
                toast.success(t(isProjectListing ? "projectSubmittedForApproval" : "propertySubmittedForApproval"));
                listingsQuery.refetch();
            } else if (isPayAsYouGoPackageError(response?.message)) {
                await openPayAsYouGoFlow({
                    actionType: "approve",
                    listingId: item.id,
                    listingType: isProjectListing ? "project" : "property",
                });
            } else {
                toast.error(t(response?.message));
            }
        } catch (error) {
            const errorMessage = error?.response?.data?.message || error?.message || "";
            if (isPayAsYouGoPackageError(errorMessage)) {
                await openPayAsYouGoFlow({
                    actionType: "approve",
                    listingId: item.id,
                    listingType: isProjectListing ? "project" : "property",
                });
                return;
            }
            console.error(error);
            toast.error(t("somethingWentWrong"));
        }
    }

    const desktopTableColumns = [
        {
            header: activeTab === "projects" ? t("projects") : t("property"),
            accessor: "title",
            align: "left",
            renderCell: (item) => (
                <div className="flex items-center gap-3">
                    <div className="h-12 w-12 shrink-0 overflow-hidden rounded-md">
                        <ImageWithPlaceholder
                            src={item.image}
                            alt={item.title}
                            className="h-full w-full object-cover"
                        />
                    </div>
                    <div className="flex flex-col">
                        <h3 className="max-w-48 truncate text-sm font-bold text-gray-900 xl:max-w-56">
                            {item.title}
                        </h3>
                        <div className="mt-1 flex flex-col md:flex-row gap-1 text-xs text-gray-500">
                            <div className="flex items-center min-w-0 max-w-full flex-1">
                                <BiMap size={14} className="mr-1 flex-shrink-0" />
                                <span className="truncate">{item.location}</span>
                            </div>
                            <div className="flex items-center gap-1 border-l px-1">
                                <FiEye size={14} />
                                <span>{item.views}</span>
                            </div>
                        </div>
                    </div>
                </div>
            ),
        },
        {
            header: t("propertyType"),
            accessor: "propertyType",
            align: "center",
            renderCell: (item) => (
                <span className="rounded-md px-3 py-1 text-xs font-medium secondaryTextColor bg-gray-100">
                    {item.propertyType}
                </span>
            ),
        },
        {
            header: activeTab === "projects" ? t("type") : t("rentSale"),
            accessor: "type",
            align: "center",
            renderCell: (item) => (
                <span className={`rounded-md px-3 py-1 text-xs text-nowrap font-medium ${item.type === "Sell" ? "primarySellBg primarySellText" : "premiumBgColor primaryRentText"}`}>
                    {item.type}
                </span>
            ),
        },
        {
            header: t("interestedUsers"),
            accessor: "interestedUsers",
            align: "center",
            renderCell: (item) => (
                item.interestedUsers?.length > 0 ? (
                    <CustomLink href={`/user/interested/${item.slugId}?lang=${lang || "en"}`} className="flex justify-center">
                        <div className="flex justify-center overflow-hidden">
                            {item.interestedUsers.slice(0, 3).map((user) => (
                                <Avatar key={user?.id} className="h-8 w-8 border-2 border-white">
                                    <AvatarImage src={user?.profile || ""} alt={user?.name || "User"} />
                                    <AvatarFallback>{user?.name ? user?.name?.charAt(0) : "U"}</AvatarFallback>
                                </Avatar>
                            ))}
                            {item.interestedUsers.length > 3 && (
                                <div className="flex h-8 w-8 items-center justify-center rounded-full border-2 border-white bg-gray-200 text-xs">
                                    +{item.interestedUsers.length - 3}
                                </div>
                            )}
                        </div>
                    </CustomLink>
                ) : (
                    <span className="text-xs text-gray-500">-</span>
                )
            ),
        },
        {
            header: t("adminStatus"),
            accessor: "requestStatus",
            align: "center",
            renderCell: (item) => (
                <div className="flex items-center justify-center gap-2">
                    {renderStatusBadge(item.requestStatus, t)}
                    {item.requestStatus === "rejected" && <RejectionTooltip reason={item?.reject_reason?.reason} t={t} />}
                    {item?.edit_reason && <EditReasonTooltip reason={item.edit_reason} />}
                </div>
            ),
        },
        {
            header: t("propertyStatus"),
            accessor: "status",
            align: "center",
            renderCell: (item) => (
                <div className="flex items-center justify-center gap-1">
                    <Switch
                        checked={item.statusActive}
                        onCheckedChange={() => handleToggleStatus(item.id)}
                        disabled={item.requestStatus === "draft" || item.requestStatus === "pending" || item.requestStatus === "rejected" || item.is_expired === 1}
                        className={`${item.statusActive ? "!primaryBg" : "!bg-[#28252566]"} rounded-[100px] h-4 w-8 transition-colors duration-300 sm:h-5 sm:w-10 [&>span]:h-2.5 [&>span]:w-2.5 ${isRtl ? "data-[state=checked]:[&>span]:-translate-x-4" : "data-[state=checked]:[&>span]:translate-x-4"} sm:[&>span]:h-3 sm:[&>span]:w-3 ${isRtl ? "sm:data-[state=checked]:[&>span]:-translate-x-5" : "sm:data-[state=checked]:[&>span]:translate-x-5"}`}
                    />
                    <span className={`text-sm ${item.statusActive ? "primaryColor" : "secondryTextColor"} capitalize`}>
                        {item.statusActive ? t("active") : t("deactive")}
                    </span>
                </div>
            ),
        },
        {
            header: t("price"),
            accessor: "price",
            align: "center",
            renderCell: (item) => item?.price !== "-" ? formatPriceAbbreviated(item.price) : "-",
        },
        {
            header: t("expiryDate"),
            accessor: "expiry_date",
            align: "center",
            renderCell: (item) => {
                if (!item.expiry_date) return <span className="text-xs">-</span>;
                const now = new Date();
                const expiry = new Date(item.expiry_date);
                const diffDays = Math.ceil((expiry - now) / (1000 * 60 * 60 * 24));
                const label = diffDays <= 0 ? t("expired") : `${t("expiresIn")} ${diffDays} ${t("days")}`;
                return <span className="text-sm font-medium secondryTextColor text-nowrap">{label}</span>;
            },
        },
        {
            header: t("action"),
            accessor: "id",
            align: "center",
            renderCell: (item, rowIndex) => {
                const isLastRow = rowIndex === currentListings.length - 1;
                const isFirstRow = rowIndex === 0;
                const totalRows = currentListings.length;
                return (
                    <div className="flex items-center justify-center gap-2">
                        <Button size="sm" variant="outline" className="blueTextColor blueBgLight hover:blueBgLight hover:blueTextColor h-8 w-8 p-0" onClick={() => handleViewListing(item)}>
                            <MdRemoveRedEye className="h-4 w-4" />
                        </Button>
                        {item?.request_status === "draft" ? (
                            <Button size="sm" variant="ghost" className="primaryBg primaryText hover:primaryBg hover:primaryColor h-8 w-8 p-0" onClick={() => handleSubmitListingForApproval(item)}>
                                <FaCheck className="text-white h-4 w-4" />
                            </Button>
                        ) : null}
                        <NavigationMenu className={`${isRtl ? "[&_div:nth-child(2)]:!left-5 [&_div:nth-child(2)]:!right-auto" : "[&_div:nth-child(2)]:!right-5 [&_div:nth-child(2)]:!left-auto"} relative  [&_div:nth-child(2)>div]:!z-[9999] [&_div:nth-child(2)>div]:!bg-white [&_div:nth-child(2)>div]:!shadow-lg [&_div:nth-child(2)>div]:!border [&_div:nth-child(2)>div]:!rounded-lg [&_div:nth-child(2)>div]:!relative ${totalRows === 1 ? "[&_div:nth-child(2)]:!-bottom-[30px] [&_div:nth-child(2)]:!top-auto" : ""} ${isLastRow && totalRows > 1 ? "[&_div:nth-child(2)]:!bottom-[40px] [&_div:nth-child(2)]:!top-auto" : ""} ${isFirstRow && totalRows > 1 ? "[&_div:nth-child(2)]:!top-0 [&_div:nth-child(2)]:!bottom-auto" : ""}`}>
                            <NavigationMenuList>
                                <NavigationMenuItem>
                                    <NavigationMenuTrigger className="primaryRentBg primaryRentText [&_svg.lucide]:hidden hover:primaryRentBg data-[state=open]:!primaryRentBg data-[state=open]:!primaryRentText hover:primaryRentText data-[state=active]:!primaryRentBg data-[state=active]:!primaryRentText p-3 focus:primaryRentBg focus:primaryRentText">
                                        <BsThreeDotsVertical className="h-4 w-4" />
                                    </NavigationMenuTrigger>
                                    <NavigationMenuContent className="grid !w-[170px] gap-1 relative z-[9999] !max-w-[170px] !min-w-[170px]">
                                        {item.is_expired === 1 ? (
                                            <NavigationMenuLink className='rtl:text-start px-3 py-2 flex justify-start items-center gap-2 bg-white hover:primaryBgLight hover:primaryColor hover:cursor-pointer text-sm' onClick={() => handleRenewListing(item)}>
                                                <MdAutorenew />{t("renewListing")}
                                            </NavigationMenuLink>
                                        ) : (
                                            <>
                                                {item.slugId && item.interestedUsers?.length > 0 && (
                                                    <NavigationMenuLink className='rtl:text-start px-3 py-2 flex justify-start items-center gap-2 bg-white hover:primaryBgLight hover:primaryColor hover:cursor-pointer text-sm' onClick={() => handleInterestedUsers(item)}>
                                                        <FiEye />{t("interestedUsers")}
                                                    </NavigationMenuLink>
                                                )}
                                                {item.requestStatus !== "pending" && item?.property_type !== "rented" && item?.property_type !== "sold" && item?.type !== "Rented" && item?.type !== "Sold" && (
                                                    <NavigationMenuLink className='rtl:text-start px-3 py-2 flex justify-start items-center gap-2 bg-white hover:primaryBgLight hover:primaryColor hover:cursor-pointer text-sm' onClick={() => handleEditListing(item)}>
                                                        <AiOutlineEdit />{t("edit")}
                                                    </NavigationMenuLink>
                                                )}
                                                {item.requestStatus !== "pending" && item?.is_feature_available && (
                                                    <NavigationMenuLink onClick={(e) => handleFeatureClick(e, item?.id)} className='rtl:text-start px-3 py-2 flex justify-start items-center gap-2 bg-white hover:primaryBgLight hover:primaryColor hover:cursor-pointer text-sm'>
                                                        <FaCrown />{t("featured")}
                                                    </NavigationMenuLink>
                                                )}
                                                {activeTab === "properties" && item.requestStatus !== "pending" && item.statusActive && item?.property_type !== "sold" && item?.type !== "Sold" && (
                                                    <NavigationMenuLink className='rtl:text-start px-3 py-2 flex justify-start items-center gap-2 bg-white hover:primaryBgLight hover:primaryColor hover:cursor-pointer text-sm' onClick={() => handleOpenStatusModal(item)}>
                                                        <MdOutlineSell />{t("changeStatus")}
                                                    </NavigationMenuLink>
                                                )}
                                                <NavigationMenuLink className='rtl:text-start px-3 py-2 flex justify-start items-center gap-2 bg-white hover:primaryBgLight hover:primaryColor hover:cursor-pointer text-sm' onClick={() => handleDeleteListing(item)}>
                                                    <MdDeleteOutline />{t("delete")}
                                                </NavigationMenuLink>
                                            </>
                                        )}
                                    </NavigationMenuContent>
                                </NavigationMenuItem>
                            </NavigationMenuList>
                        </NavigationMenu>
                    </div>
                );
            },
        },
    ];

    return (
        <div className="flex flex-col rounded-2xl border newBorderColor w-full h-full bg-white overflow-auto">
            <div className="flex flex-col items-center gap-4 p-6 border-b newBorderColor lg:flex-row lg:items-center lg:justify-between">
                <h1 className="text-xl font-bold brandColor">
                    {activeTab === "properties" ? t("myProperties") : t("myProjects")}
                </h1>
                <div className="w-full sm:w-fit flex flex-col gap-3 sm:flex-row sm:items-center">
                    <button type="button" onClick={handleAddListing} className="px-3 py-2 brandBg text-white font-medium text-base rounded-lg cursor-pointer">
                        {activeTab === "projects" ? t("addProject") : t("addProperty")}
                    </button>
                    <div className="w-full flex items-center justify-between sm:w-fit gap-1 rounded-lg newBorder primaryBackgroundBg p-1.5">
                        <button
                            type="button"
                            onClick={() => handleTabChange("properties")}
                            aria-pressed={activeTab === "properties"}
                            className={`w-full sm:w-fit rounded-lg px-3 py-2 text-base font-medium transition-colors ${activeTab === "properties" ? "primaryBg text-white shadow-sm" : "brandColor hover:bg-white"}`}
                        >
                            {t("properties")}
                        </button>
                        <button
                            type="button"
                            onClick={() => handleTabChange("projects")}
                            aria-pressed={activeTab === "projects"}
                            className={`w-full sm:w-fit rounded-lg px-3 py-2 text-base font-medium transition-colors ${activeTab === "projects" ? "primaryBg text-white shadow-sm" : "brandColor hover:bg-white"}`}
                        >
                            {t("projects")}
                        </button>
                    </div>
                </div>
            </div>

            <div className="flex flex-col gap-4 max-h-[775px]">
                <div className="hidden lg:block bg-white p-2 md:p-6 overflow-x-auto overflow-y-visible">
                    {isLoading ? (
                        <TableLoadingSkeleton itemLength={itemsPerPage} />
                    ) : (
                        <ReusableTable
                            parentclassname="overflow-visible [&>div]:overflow-visible !border-0"
                            headerClassName="primaryBackgroundBg [&>tr]:!border-b-0 [&_tr_th:first-child]:rounded-l-xl [&_tr_th:last-child]:rounded-r-xl"
                            columns={desktopTableColumns}
                            data={currentListings}
                            isLoading={false}
                            emptyMessage={t("noDataAvailable")}
                        />
                    )}

                    {showPagination && (
                        <CustomPagination
                            className="border-t !border-b-0 !border-x-0"
                            currentPage={currentPage}
                            totalItems={totalItems}
                            itemsPerPage={itemsPerPage}
                            onPageChange={handlePageChange}
                            isLoading={isLoading}
                            translations={{
                                showing: t("showing"),
                                to: t("to"),
                                of: t("of"),
                                entries: t("entries"),
                            }}
                        />
                    )}
                </div>
            </div>

            <div className="p-4 flex flex-col gap-[30px] mt-4 lg:hidden">
                {isLoading ? Array.from({ length: itemsPerPage }).map((_, index) => (
                    <div key={index} className="h-[360px] rounded-[12px] border-[1px] newBorderColor animate-pulse bg-gray-50" />
                )) : currentListings.map((item) => (
                    <div key={item.id} className="flex flex-col gap-2 items-start justify-start lg:items-center lg:justify-between p-4 rounded-[12px] border-[1px] newBorderColor hover:shadow-sm transition-shadow">
                        <div className="flex w-full items-center xl:gap-4 2">
                            <div className="flex flex-raw justify-between w-full border-b ">
                                <p className="flex flex-col text-sm leadColor">
                                    {activeTab === "projects" ? t("projects") : t("property")}
                                </p>
                                <div className="flex flex-col items-end justify-end lg:items-start">
                                    <div className="flex flex-raw w-[80px] h-[80px] lg:w-[120px] lg:h-[120px] shrink-0 rounded-[8px] overflow-hidden">
                                        <ImageWithPlaceholder src={item.image} alt={item.title} className="w-full h-full object-cover " />
                                    </div>
                                    <h3 className="text-[16px] font-bold secondaryBorderColor flex text-end ">{item.title}</h3>
                                </div>
                            </div>
                        </div>

                        <div className="flex w-full justify-between pb-2 border-b">
                            <p className="text-sm leadColor">{activeTab === "projects" ? t("type") : t("rentSale")}</p>
                            <span className={`px-4 py-[2px] rounded-[8px] text-sm font-semibold ${item.type === "Sell" ? "primarySellBg primarySellText" : "premiumBgColor primaryRentText"}`}>
                                {item.type}
                            </span>
                        </div>
                        <div className="flex flex-col gap-2 text-sm facilitiesTextColor w-full">
                            <div className="flex justify-between pb-2 border-b">
                                <p className="text-sm leadColor">{t("location")}</p>
                                <div className="flex items-center">
                                    <BiMap size={16} />
                                    <span>{item.location}</span>
                                </div>
                            </div>
                            <div className="flex justify-between pb-2 border-b">
                                <p className="leadColor text-sm">{t("type")}:</p>
                                {item.propertyType}
                            </div>
                            <div className="flex justify-between pb-2 border-b">
                                <p className="leadColor text-sm">{t("views")}:</p>
                                <div className="flex items-center">
                                    <FiEye size={16} />
                                    <span>{item.views}</span>
                                </div>
                            </div>
                        </div>

                        <div className="flex flex-col justify-center gap-2 w-full">
                            <div className="flex justify-between items-center pb-2 border-b">
                                <span className="text-sm font-medium leadColor">{t("adminStatus")}</span>
                                <div className="flex justify-start items-center gap-2">
                                    {renderStatusBadge(item.requestStatus, t)}
                                    {item.requestStatus === 'rejected' && <RejectionTooltip reason={item?.reject_reason?.reason} t={t} />}
                                    {item?.edit_reason && <EditReasonTooltip reason={item.edit_reason} />}
                                </div>
                            </div>

                            <div className="flex justify-between pb-2 border-b">
                                <span className="text-sm font-medium leadColor w-full text-left">{t("status")}</span>
                                <div className="flex items-center gap-1">
                                    <Switch
                                        checked={item.statusActive}
                                        onCheckedChange={() => handleToggleStatus(item.id)}
                                        disabled={item.requestStatus === "draft" || item.requestStatus === "pending" || item.requestStatus === "rejected" || item.is_expired === 1}
                                        className={`${item.statusActive ? "!primaryBg" : "!bg-[#28252566]"} rounded-[100px] h-4 w-8 transition-colors duration-300 sm:h-5 sm:w-10 [&>span]:h-2.5 [&>span]:w-2.5 ${isRtl ? "data-[state=checked]:[&>span]:-translate-x-4" : "data-[state=checked]:[&>span]:translate-x-4"} sm:[&>span]:h-3 sm:[&>span]:w-3 ${isRtl ? "sm:data-[state=checked]:[&>span]:-translate-x-5" : "sm:data-[state=checked]:[&>span]:translate-x-5"}`}
                                    />
                                    <span className={`text-sm ${item.statusActive ? "primaryColor" : "secondryTextColor"} capitalize`}>
                                        {item.statusActive ? t("active") : t("deactive")}
                                    </span>
                                </div>
                            </div>

                            {item?.price !== "-" && (
                                <div className="flex justify-between pb-2 border-b">
                                    <span className="text-sm font-medium leadColor ">{t("price")}</span>
                                    <span className="text-base font-bold primaryColor">{formatPriceAbbreviated(item.price)}</span>
                                </div>
                            )}

                            <div className="flex justify-between ">
                                <p className="items-center justify-center text-sm leadColor">{t("actions")}:</p>
                                <div className="flex gap-2 items-center ">
                                    <Button size="sm" variant="outline" className="bg-transparent h-8 w-8 p-0" onClick={() => handleViewListing(item)}>
                                        <MdRemoveRedEye className="h-4 w-4" />
                                    </Button>
                                    {item?.request_status === "draft" ? (
                                        <Button size="sm" variant="ghost" className="primaryBg primaryText hover:primaryBg hover:primaryColor h-8 w-8 p-0" onClick={() => handleSubmitListingForApproval(item)}>
                                            <FaCheck className="text-white h-4 w-4" />
                                        </Button>
                                    ) : null}
                                    <NavigationMenu className={`${isRtl ? "[&_div:nth-child(2)]:!left-5 [&_div:nth-child(2)]:!right-auto" : "[&_div:nth-child(2)]:!right-5 [&_div:nth-child(2)]:!left-auto"} [&_div:nth-child(2)>div]:!z-[9999] [&_div:nth-child(2)>div]:!bg-white [&_div:nth-child(2)>div]:!shadow-lg [&_div:nth-child(2)>div]:!border [&_div:nth-child(2)>div]:!rounded-lg`}>
                                        <NavigationMenuList>
                                            <NavigationMenuItem>
                                                <NavigationMenuTrigger className="primaryRentBg primaryRentText [&_svg.lucide]:hidden hover:primaryRentBg data-[state=open]:!primaryRentBg data-[state=open]:!primaryRentText hover:primaryRentText data-[state=active]:!primaryRentBg data-[state=active]:!primaryRentText p-3 focus:primaryRentBg focus:primaryRentText">
                                                    <BsThreeDotsVertical className="h-4 w-4" />
                                                </NavigationMenuTrigger>
                                                <NavigationMenuContent className="grid !w-[150px] gap-1 relative z-[9999] !max-w-[150px] !min-w-[150px]">
                                                    {item.is_expired === 1 ? (
                                                        <NavigationMenuLink className='rtl:text-start px-3 py-2 flex justify-start items-center gap-2 bg-white hover:primaryBgLight hover:primaryColor hover:cursor-pointer' onClick={() => handleRenewListing(item)}>
                                                            <MdAutorenew />{t("renewListing")}
                                                        </NavigationMenuLink>
                                                    ) : (
                                                        <>
                                                            {item.requestStatus !== "pending" && item?.property_type !== "rented" && item?.property_type !== "sold" && item?.type !== "Rented" && item?.type !== "Sold" && (
                                                                <NavigationMenuLink className='rtl:text-start px-3 py-2 flex justify-start items-center gap-2 bg-white hover:primaryBgLight hover:primaryColor hover:cursor-pointer' onClick={() => handleEditListing(item)}>
                                                                    <AiOutlineEdit />{t("edit")}
                                                                </NavigationMenuLink>
                                                            )}
                                                            {item.requestStatus !== "pending" && item?.is_feature_available && (
                                                                <NavigationMenuLink onClick={(e) => handleFeatureClick(e, item?.id)} className='rtl:text-start px-3 py-2 flex justify-start items-center gap-2 bg-white hover:primaryBgLight hover:primaryColor hover:cursor-pointer'>
                                                                    <FaCrown />{t("featured")}
                                                                </NavigationMenuLink>
                                                            )}
                                                            {item.slugId && item.interestedUsers?.length > 0 && (
                                                                <NavigationMenuLink className='rtl:text-start px-3 py-2 flex justify-start items-center gap-2 bg-white hover:primaryBgLight hover:primaryColor hover:cursor-pointer' onClick={() => handleInterestedUsers(item)}>
                                                                    <FiEye />{t("interestedUsers")}
                                                                </NavigationMenuLink>
                                                            )}
                                                            {activeTab === "properties" && item.requestStatus !== "pending" && item.statusActive && item?.property_type !== "sold" && item?.type !== "Sold" && (
                                                                <NavigationMenuLink className='rtl:text-start px-3 py-2 flex justify-start items-center gap-2 bg-white hover:primaryBgLight hover:primaryColor hover:cursor-pointer' onClick={() => handleOpenStatusModal(item)}>
                                                                    <MdOutlineSell />{t("changeStatus")}
                                                                </NavigationMenuLink>
                                                            )}
                                                            <NavigationMenuLink className='rtl:text-start px-3 py-2 flex justify-start items-center gap-2 bg-white hover:primaryBgLight hover:primaryColor hover:cursor-pointer' onClick={() => handleDeleteListing(item)}>
                                                                <MdDeleteOutline />{t("delete")}
                                                            </NavigationMenuLink>
                                                        </>
                                                    )}
                                                </NavigationMenuContent>
                                            </NavigationMenuItem>
                                        </NavigationMenuList>
                                    </NavigationMenu>
                                </div>
                            </div>
                        </div>
                    </div>
                ))}
            </div>

            {selectedListing && (
                <ChangeStatusModal
                    open={statusModalOpen}
                    onOpenChange={setStatusModalOpen}
                    propertyId={selectedListing.id}
                    propertyType={selectedListing.property_type}
                    onStatusChanged={() => listingsQuery.refetch()}
                />
            )}

            <PayAsYouGoModal
                isOpen={showPayAsYouGoModal}
                onClose={clearPayAsYouGoFlow}
                payAsYouGoData={payAsYouGoData}
                onContinuePayment={() => setShowPaymentSelection(true)}
                onViewMorePlans={() => {
                    clearPayAsYouGoFlow();
                    router.push(`/subscription-plan?lang=${lang || "en"}`);
                }}
                isProcessing={paymentLoading}
                isProject={activeTab === "projects"}
            />

            <PaymentSelectionModalWrapper
                payAsYouGoData={payAsYouGoData}
                paymentSettingsData={paymentSettingsData}
                showPaymentSelection={showPaymentSelection}
                setShowPaymentSelection={setShowPaymentSelection}
                paymentLoading={paymentLoading}
                setPaymentLoading={setPaymentLoading}
                router={router}
                websettings={webSettings}
                onPaymentSuccess={handlePostPaymentRenew}
                onPaymentFailed={clearPayAsYouGoFlow}
            />
        </div>
    )
}

export default MyListings