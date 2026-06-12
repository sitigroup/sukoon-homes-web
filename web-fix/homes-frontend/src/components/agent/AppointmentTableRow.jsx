import { useState } from 'react';
import { BiChevronDown, BiX } from 'react-icons/bi';
import ImageWithPlaceholder from '../image-with-placeholder/ImageWithPlaceholder';
import { useTranslation } from '../context/TranslationContext';
import AppointmentDetailsCard from './appointment/AppointmentDetailsCard';
import { MdHistory } from 'react-icons/md';
import AppointmentActionModal from '../reusable-components/appointment/AppointmentActionModal';
import { reportUserApi, updateAppointmentStatusApi, updateMeetingTypeApi } from '@/api/apiRoutes';
import toast from 'react-hot-toast';
import RescheduleAppointmentModal from '../appointment-modal/RescheduleAppointmentModal';
import { LuPencilLine } from 'react-icons/lu';
import ChangeMeetingTypeModal from '../appointment-modal/ChangeMeetingTypeModal';
import { motion, AnimatePresence } from 'framer-motion';


const StatusBadge = ({ status, t }) => {
    const getStatusStyles = (status) => {
        switch (status.toLowerCase()) {
            case 'confirmed':
                return {
                    bg: 'bg-green-100',
                    text: 'text-green-600',
                    label: t('confirmed')
                };
            case 'completed':
                return {
                    bg: 'bg-green-100',
                    text: 'text-green-600',
                    label: t('completed')
                };
            case 'rescheduled':
                return {
                    bg: 'bg-blue-100',
                    text: 'text-blue-600',
                    label: t('rescheduled')
                };
            case 'pending':
                return {
                    bg: 'bg-blue-100',
                    text: 'text-blue-600',
                    label: t('pending')
                };
            case 'rejected':
                return {
                    bg: 'bg-red-100',
                    text: 'text-red-600',
                    label: t('rejected')
                };
            case 'cancelled':
                return {
                    bg: 'bg-red-100',
                    text: 'text-red-600',
                    label: t('cancelled')
                };
            case 'auto_cancelled':
                return {
                    bg: 'bg-red-100',
                    text: 'text-red-600',
                    label: t('cancelled')
                };
            default:
                return {
                    bg: 'bg-gray-100',
                    text: 'text-gray-600',
                    label: t(status)
                };
        }
    };

    const styles = getStatusStyles(status);

    return (
        <div className={`px-3 py-2 rounded flex justify-center ${styles.bg}`}>
            <span className={`text-sm font-medium ${styles.text}`}>
                {styles.label}
            </span>
        </div>
    );
};

const AppointmentTableRow = ({
    appointment,
    onEdit,
    onCancel,
    onAccept,
    isAgent = false,
    activeTab = '',
    refetchData = () => { },
    isRequestedAppointment = false
}) => {
    const t = useTranslation();

    let user = appointment?.user || {};
    let propertyDetails = appointment?.property || {};
    // Handle both requested (admin) and booked (agent) appointments
    let agent = isRequestedAppointment ? (appointment?.admin || appointment?.agent) : (appointment?.agent || appointment?.admin);

    const [isExpanded, setIsExpanded] = useState(false);
    const [isReportModalOpen, setIsReportModalOpen] = useState(false);
    const [showRescheduleModal, setShowRescheduleModal] = useState(false);
    const [editMeetingTypeModal, setEditMeetingTypeModal] = useState(false)
    const [updatedMeetingType, setUpdatedMeetingType] = useState(appointment?.meeting_type)

    const handleViewDetailsClick = (e) => {
        e.stopPropagation();
        setIsExpanded(!isExpanded);
    };


    const handleRescheduleClick = (e) => {
        setShowRescheduleModal(true);
    };

    const handleCancelClick = (e) => {
        e.stopPropagation();
        onCancel(appointment?.id);
    };

    const handleAcceptClick = (e) => {
        e.stopPropagation();
        if (onAccept) {
            onAccept(appointment);
        }
    };

    // Extract time in HH:MM AM/PM format
    const getFormattedTime = (time) => {
        const date = new Date(time);
        return date.toLocaleTimeString('en-US', {
            hour: '2-digit',
            minute: '2-digit',
            hour12: true
        });
    };

    const handleReportUser = async (reason) => {
        if (!reason) {
            toast.error(t('pleaseProvideReasonForReport'))
            return
        }
        try {
            const response = await reportUserApi({
                user_id: isAgent ? user.id : agent.id,
                reason: reason,
            });

            if (response?.error === false && response?.data) {
                setIsReportModalOpen(false);
                toast.success(t('reportSubmittedSuccessfully'));
            }
        } catch (error) {
            console.error("Error reporting user:", error.message);
            toast.error(error.message)
        }
    };

    const handleRescheduleContinue = async (data) => {
        try {
            const formattedDate = `${data?.date.getFullYear()}-${(data?.date.getMonth() + 1).toString().padStart(2, '0')}-${data?.date.getDate().toString().padStart(2, '0')}`;
            const response = await updateAppointmentStatusApi({
                appointment_id: appointment?.id,
                status: 'rescheduled',
                reason: data?.reason,
                date: formattedDate,
                start_time: data?.timeSlot?.start_time,
                end_time: data?.timeSlot?.end_time,
                meeting_type: data?.meetingType
            })

            // Check if API returned an error
            if (response?.error === true) {
                toast.error(response?.message || t('somethingWentWrong') || 'Something went wrong');
                return;
            }

            if (response?.error === false && response?.data) {
                setShowRescheduleModal(false);
                toast.success(response?.message);
                if (refetchData) {
                    refetchData();
                }
            }
        } catch (error) {
            console.error("Error rescheduling appointment:", error);
            toast.error(error?.message || t('somethingWentWrong') || 'Something went wrong');
        }
    }

    const handleMeetingTypeChange = async () => {
        try {
            const response = await updateMeetingTypeApi({
                appointment_id: appointment?.id,
                meeting_type: updatedMeetingType
            })
            if (response?.error === false && response?.data) {
                setEditMeetingTypeModal(false);
                toast.success(t('meetingTypeUpdatedSuccessfully'));
                if (refetchData) {
                    refetchData();
                }
            }
        } catch (error) {

        }
    }

    return (
        <div className={`border border-gray-200 rounded-2xl overflow-hidden ${isExpanded ? '' : ''}`}>
            {/* Main Row */}
            <div className={`md:overflow-x-auto no-scrollbar ${isExpanded ? 'border-b mb-2 newBorderColor' : ''}`}>
                <div className="flex flex-col md:flex-row justify-between items-start md:items-center gap-6 md:gap-8 p-4 w-full no-scrollbar">
                    {/* User/Agent Info */}
                    <div className="flex justify-start items-center gap-4 w-full md:w-[300px] md:max-w-[300px]">
                        <div className="relative w-12 h-12 flex-shrink-0">
                            <ImageWithPlaceholder
                                src={isAgent ? user?.profile : agent?.profile}
                                alt={isAgent ? (user?.name || "User Image") : (agent?.name || "Agent Image")}
                                className="w-12 h-12 rounded object-cover shadow-md"
                            />
                        </div>
                        <div className="flex flex-col gap-1 w-full md:w-[220px] md:max-w-[220px]">
                            <span className="font-bold text-base text-gray-800 truncate text-start">
                                {isAgent ? user?.name : agent?.name}
                            </span>
                            <span className="text-base text-gray-600 truncate text-start">
                                {isAgent ? user?.email : agent?.email}
                            </span>
                        </div>
                    </div>

                    {/* Property Info - Only show when isAgent is true */}
                    {isAgent && propertyDetails && (
                        <div className="flex justify-start items-center gap-4 w-full md:w-[300px] md:max-w-[300px]">
                            <div className="relative w-12 h-12 flex-shrink-0">
                                <ImageWithPlaceholder
                                    src={propertyDetails?.title_image}
                                    alt={propertyDetails?.translated_title || propertyDetails?.title || "Property Image"}
                                    className="w-12 h-12 rounded-lg object-cover"
                                />
                            </div>
                            <div className="flex flex-col gap-1 w-full">
                                <span className="text-base text-gray-600 text-start">{t("propertyBook")}</span>
                                <span className="font-bold text-base text-gray-800 truncate text-start">
                                    {propertyDetails.translated_title || propertyDetails?.title}
                                </span>
                            </div>
                        </div>
                    )}

                    {/* Meeting Type */}
                    <div className="flex flex-col gap-2 w-full md:w-[150px] md:max-w-[150px] items-start md:items-center">
                        <span className="text-base text-start md:text-center text-gray-600 whitespace-nowrap">{t("meetingType")}</span>
                        <div className="flex items-center gap-2 text-start md:text-center text-gray-800">
                            <span className='font-bold text-base text-nowrap'>{t(appointment?.meeting_type)}</span>
                            {!["cancelled", "auto_cancelled", "completed"].includes(appointment?.status?.toLowerCase()) && (
                                <span className='primaryBgLight08 flex items-center justify-center w-6 h-6 rounded-sm hover:cursor-pointer'
                                    onClick={() => setEditMeetingTypeModal(true)}
                                >
                                    <LuPencilLine className='primaryColor' />
                                </span>
                            )}
                        </div>
                    </div>

                    {/* Meeting Time */}
                    <div className="flex flex-col gap-2 w-full md:w-[150px] md:max-w-[150px] items-start md:items-center">
                        <span className="text-base text-center text-gray-600 whitespace-nowrap">{t("meetingTime")}</span>
                        <span className="font-bold text-base text-center text-gray-800">{getFormattedTime(appointment?.start_at)} - {getFormattedTime(appointment?.end_at)}</span>
                    </div>

                    {/* Status */}
                    <div className="flex flex-col gap-2 w-full md:w-[150px] md:max-w-[150px] items-center">
                        <span className="text-base text-start md:text-center text-gray-600 whitespace-nowrap">{t("status")}</span>
                        <div className="flex justify-start md:justify-center w-full">
                            <StatusBadge status={appointment?.status} t={t} />
                        </div>
                    </div>

                    {/* Actions */}
                    <div className="flex flex-wrap md:flex-nowrap justify-start sm:justify-center items-start sm:items-center gap-2 md:gap-4 w-full md:w-auto">
                        {/* See Details Button */}
                        <button
                            onClick={handleViewDetailsClick}
                            className="primaryBackgroundBg rounded flex items-center gap-2.5 px-2 py-1 h-10 hover:bg-gray-200 transition-colors"
                        >
                            <motion.div
                                animate={{ rotate: isExpanded ? 180 : 0 }}
                                transition={{ duration: 0.3, ease: "easeInOut" }}
                            >
                                <BiChevronDown size={24} className="text-gray-800" />
                            </motion.div>
                            <span className="text-base text-black whitespace-nowrap">{t("seeDetails")}</span>
                        </button>

                        {/* Accept Button - Only show for pending appointments */}
                        {isAgent && appointment?.status?.toLowerCase() === 'pending' && (
                            <button
                                onClick={handleAcceptClick}
                                className="bg-[#83B8071F] rounded flex items-center gap-2.5 px-2 py-1 h-10 transition-colors"
                            >
                                <span className="text-base font-medium text-[#83B807] whitespace-nowrap">{t("accept")}</span>
                            </button>
                        )}

                        {!["cancelled", "auto_cancelled", "completed"].includes(appointment?.status?.toLowerCase()) && activeTab === 'upcoming' && (
                            <button
                                onClick={handleRescheduleClick}
                                className="primarySellBgLight12 rounded flex items-center gap-2.5 px-2 py-1 h-10 transition-colors"
                            >
                                <MdHistory className='w-5 h-5' />
                            </button>
                        )}

                        {/* Cancel Button */}
                        {!["cancelled", "approved", "auto_cancelled", "completed"].includes(appointment?.status?.toLowerCase()) && (
                            <button
                                onClick={handleCancelClick}
                                className="bg-red-100 rounded flex items-center gap-2.5 px-2 py-1 h-10 hover:bg-red-200 transition-colors"
                            >
                                <BiX size={24} className="text-red-600" />
                            </button>
                        )}
                    </div>
                </div>
            </div>
            <AnimatePresence>
                {isExpanded && (
                    <motion.div
                        initial={{ height: 0, opacity: 0 }}
                        animate={{ height: "auto", opacity: 1 }}
                        exit={{ height: 0, opacity: 0 }}
                        transition={{
                            duration: 0.4,
                            ease: "easeInOut",
                            opacity: { duration: 0.2 }
                        }}
                        style={{ overflow: "hidden" }}
                    >
                        <AppointmentDetailsCard
                            appointmentData={appointment}
                            is_agent={isAgent}
                            onClose={() => setIsExpanded(false)}
                            setIsReportModalOpen={setIsReportModalOpen}
                            isReportModalOpen={isReportModalOpen}
                            onReportUser={(e) => {
                                e?.preventDefault();
                                setIsReportModalOpen(true);
                            }}
                        />
                    </motion.div>
                )}
            </AnimatePresence>

            {/* Expanded Content - Reject Reason */}
            {appointment?.status.toLowerCase() === 'cancelled' && appointment?.reason && (
                <div className="mx-4 mb-4 bg-red-50 rounded-lg p-4">
                    <div className="flex items-center gap-1 mb-2">
                        <span className="font-semibold text-sm brandColor">{t("cancellationReason")}</span>
                        <span className="font-semibold text-sm brandColor">:</span>
                    </div>
                    <div>
                        <p className="text-base text-red-600">{appointment?.reason}</p>
                    </div>
                </div>
            )}

            {isReportModalOpen && (
                <AppointmentActionModal
                    isOpen={isReportModalOpen}
                    onClose={() => setIsReportModalOpen(false)}
                    title='reportUser'
                    initialReason=''
                    isRequired={true}
                    submitButtonText='submit'
                    submitButtonColor='brandBg'
                    handleSubmit={handleReportUser}
                />
            )}
            {showRescheduleModal && (
                <RescheduleAppointmentModal
                    isOpen={showRescheduleModal}
                    onClose={() => setShowRescheduleModal(false)}
                    appointment={appointment}
                    onContinue={handleRescheduleContinue}
                />
            )}
            {editMeetingTypeModal && (
                <ChangeMeetingTypeModal
                    availableMeetingTypes={appointment?.availability_types}
                    currentMeetingType={updatedMeetingType}
                    isOpen={editMeetingTypeModal}
                    onClose={() => setEditMeetingTypeModal(false)}
                    onChangeMeetingType={setUpdatedMeetingType}
                    handleSubmit={handleMeetingTypeChange}
                />
            )}
        </div>
    );
};

export default AppointmentTableRow;
