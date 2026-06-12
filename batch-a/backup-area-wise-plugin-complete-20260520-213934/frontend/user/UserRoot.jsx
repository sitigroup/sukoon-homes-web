"use client"
import { useEffect } from 'react'
import { useRouter } from 'next/router'
import { Skeleton } from '@/components/ui/skeleton'
import UserListings from './UserListings'
import UserAdvertisements from './UserAdvertisements'
import UserAppointments from './UserAppointments'
import UserPersonalizedFeeds from './UserPersonalizedFeeds'
import UserNotifications from './UserNotifications'
import UserTransactionHistory from './UserTransactionHistory'
import UserProfile from './UserProfile'
import UserSubscription from "./UserSubscription"
import UserChat from './UserChat'
import AddProperty from '@/components/agent/property/AddProperty'
import EditProperty from '@/components/agent/property/EditProperty'
import AddProject from '../agent/project/AddProject'
import EditProject from '../agent/project/EditProject'
import UserVerificationForm from './UserVerificationForm'
import UserFavourites from './UserFavourites'
import UserInterested from './UserInterested'

const UserRootSkeleton = () => (
    <div className="flex w-full flex-1 flex-col gap-4 rounded-2xl border newBorderColor bg-white p-4 sm:p-6">
        <div className="flex flex-col gap-3 border-b pb-4 newBorderColor sm:flex-row sm:items-center sm:justify-between">
            <div className="flex flex-col gap-2">
                <Skeleton className="h-6 w-44" />
                <Skeleton className="h-4 w-72 max-w-full" />
            </div>
            <Skeleton className="h-10 w-32 rounded-lg" />
        </div>

        <div className="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
            {Array.from({ length: 3 }).map((_, index) => (
                <div key={index} className="rounded-xl border newBorderColor p-4">
                    <Skeleton className="h-4 w-20" />
                    <Skeleton className="mt-3 h-8 w-3/4" />
                    <Skeleton className="mt-4 h-4 w-full" />
                    <Skeleton className="mt-2 h-4 w-5/6" />
                </div>
            ))}
        </div>

        <div className="flex flex-col gap-3">
            {Array.from({ length: 4 }).map((_, index) => (
                <div key={index} className="flex flex-col gap-3 rounded-xl border newBorderColor p-4">
                    <div className="flex items-center justify-between gap-4">
                        <Skeleton className="h-5 w-40" />
                        <Skeleton className="h-5 w-20 rounded-full" />
                    </div>
                    <div className="flex flex-col gap-2">
                        <Skeleton className="h-4 w-full" />
                        <Skeleton className="h-4 w-5/6" />
                    </div>
                </div>
            ))}
        </div>
    </div>
)

// Root component to render the agent dashboard related pages
const UserRoot = ({ notificationData, isLoading }) => {
    const router = useRouter()
    const { slug = [] } = router.query;

    // Convert slug to array if it's not already
    const slugArray = Array.isArray(slug) ? slug : [slug].filter(Boolean);

    // Fix bad redirect /user/user/listings → /user/listings (stale EditProperty bundle or relative router.push)
    useEffect(() => {
        if (!router.isReady) return;
        const parts = Array.isArray(router.query.slug) ? router.query.slug : [router.query.slug].filter(Boolean);
        if (parts[0] === 'user' && parts[1] === 'listings') {
            router.replace({
                pathname: '/user/listings',
                query: { ...router.query, tab: router.query.tab || 'properties' },
            });
        }
    }, [router.isReady, router.query.slug, router.query.tab, router.query.lang]);

    // Get the main section from the first part of the slug
    let mainSection = slugArray[0];

    // /user/user/listings — treat as listings until replace() runs
    if (mainSection === 'user' && slugArray[1] === 'listings') {
        mainSection = 'listings';
    }

    // Default to my-listings if no section or dashboard
    if (!mainSection || mainSection === "dashboard") {
        mainSection = "listings";
    }

    // For dynamic routes like edit-property/[propertySlug]
    // we pass the additional parameters to the component
    const params = slugArray.slice(1);

    // Component mapping object for more efficient routing
    const componentMap = {
        "listings": UserListings,
        "advertisement": UserAdvertisements,
        "appointments": UserAppointments,
        "chat": UserChat,
        "notifications": UserNotifications,
        "personalized-feeds": UserPersonalizedFeeds,
        "my-subscriptions": UserSubscription,
        "transaction-history": UserTransactionHistory,
        "profile": UserProfile,
        "add-property": AddProperty,
        "edit-property": EditProperty,
        "add-project": AddProject,
        "edit-project": EditProject,
        "verification-form": UserVerificationForm,
        "favourites": UserFavourites,
        "interested": UserInterested,
    }

    // Get the Component to render based on the main section
    const Component = componentMap[mainSection]

    if (isLoading) {
        return <UserRootSkeleton />
    }

    // Return the component if it exists with any additional params
    return Component ? <Component params={params} notificationData={notificationData} showHeader={false} showRoundedBorders={true} /> : null
}

export default UserRoot