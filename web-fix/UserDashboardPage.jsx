import React, { useState } from 'react'
import dynamic from 'next/dynamic'
import { useRouter } from 'next/router'
import { useSelector } from 'react-redux'
const Layout = dynamic(() => import('@/components/layout/Layout'), { ssr: false })
const UserRoot = dynamic(() => import('@/components/user/UserRoot'), { ssr: false })
const UserSideBar = dynamic(() => import('@/components/user/UserSideBar'), { ssr: false })
const PushNotificationLayout = dynamic(() => import('@/components/wrapper/PushNotificationLayout'), { ssr: false })

const UserDashboardPage = () => {
    const [notificationData, setNotificationData] = useState(null)
    const router = useRouter()
    const userLoading = useSelector((state) => state.User?.loading)
    const slugArray = Array.isArray(router.query?.slug)
        ? router.query.slug
        : router.query?.slug
            ? [router.query.slug]
            : []
    const mainSection = slugArray[0]
    const hideSidebar = mainSection === 'add-property' || mainSection === 'edit-property' || mainSection === 'add-project' || mainSection === 'edit-project' || mainSection === 'my-property' || mainSection === 'my-project' || mainSection === 'verification-form';
    const isLoading = userLoading || !router.isReady

    const handleNotificationReceived = (data) => {
        setNotificationData(data)
    }

    return (
        <Layout>
            <PushNotificationLayout onNotificationReceived={handleNotificationReceived}>
                <div className='primaryBackgroundBg'>
                    <div className={`${hideSidebar ? "" : "xl:flex"} container mx-auto py-[60px] gap-3 2xl:gap-6 px-2`}>
                        {!hideSidebar && <UserSideBar isLoading={isLoading} />}
                        <UserRoot notificationData={notificationData} isLoading={isLoading} />
                    </div>
                </div>
            </PushNotificationLayout>
        </Layout>
    )
}

export default UserDashboardPage