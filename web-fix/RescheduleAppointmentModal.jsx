import React, { useState, useEffect, useMemo } from 'react';
import { X } from 'lucide-react';
import {
    Dialog,
    DialogContent,
    DialogHeader,
    DialogFooter,
    DialogTitle,
    DialogDescription,
} from '@/components/ui/dialog';
import {
    Sheet,
    SheetContent
} from '@/components/ui/sheet';
import CalendarCard from './CalendarCard';
import SelectTimeSlotCard from './SelectTimeSlotCard';
import MeetingTypeCard from './MeetingTypeCard';
import { useTranslation } from '../context/TranslationContext';
import { useIsMobile } from '@/hooks/use-mobile';
import { IoMdClose } from 'react-icons/io';
import { getMonthWiseTimeSchedulesApi } from '@/api/apiRoutes';
import { formatAppointmentData, formatMeetingTypes, getTimeSlotsForDate, formatAvailableDates } from '@/utils/appointmentHelper';
import toast from 'react-hot-toast';

/**
 * RescheduleAppointmentModal component for rescheduling property appointments
 * Matches the Figma design with header, calendar, time slots, and meeting type selection
 */
const RescheduleAppointmentModal = ({
    isOpen = false,
    onClose = () => { },
    appointment = null, // The appointment being rescheduled
    onReschedule = () => { },
    onCancel = () => { },
    onContinue = () => { }
}) => {
    const t = useTranslation();
    const isMobile = useIsMobile();

    // Extract property title and agent info from appointment
    const propertyTitle = appointment?.property?.translated_title ? appointment.property?.translated_title : appointment?.property?.title || appointment?.property_title
    const existingAppointment = appointment;


    const isAdminAppointment = existingAppointment?.is_admin_appointment === true



    // State management
    const [selectedDate, setSelectedDate] = useState(new Date());
    const [calendarDate, setCalendarDate] = useState(new Date());
    const [selectedTimeSlot, setSelectedTimeSlot] = useState(null);
    const [selectedMeetingType, setSelectedMeetingType] = useState(appointment?.meeting_type || '');
    const [rescheduleReason, setRescheduleReason] = useState('');
    const [appointmentData, setAppointmentData] = useState({});
    const [availableMeetingTypes, setAvailableMeetingTypes] = useState([]);
    const [isLoading, setIsLoading] = useState(false);

    // Fetch monthly time slots for the agent
    const fetchMonthlyTimeSlots = async () => {
        setIsLoading(true);
        try {
            // Get agent_id from existing appointment data - try different possible paths
            // For admin appointments, use agent_id 0, otherwise try to get the agent ID from various possible paths
            const agentId = isAdminAppointment ? 0 : (
                existingAppointment?.agent_id ||
                existingAppointment?.property?.added_by ||
                existingAppointment?.added_by ||
                existingAppointment?.property_details?.added_by
            );


            const response = await getMonthWiseTimeSchedulesApi({
                month: calendarDate.getMonth() + 1,
                year: calendarDate.getFullYear(),
                agent_id: agentId
            });

            if (response?.data) {
                setAppointmentData(formatAppointmentData(response?.data, calendarDate));
                let meetingTypes = formatMeetingTypes(response?.data?.availability_types);
                setAvailableMeetingTypes(meetingTypes);
            }
        } catch (error) {
            console.error("Error fetching monthly time slots", error);
            toast.error(error?.message || t("somethingWentWrong") || "Something went wrong");
        } finally {
            setIsLoading(false);
        }
    };

    // Fetch data when modal opens or calendar date changes
    useEffect(() => {
        if (isOpen && existingAppointment) {
            fetchMonthlyTimeSlots();
        }
    }, [isOpen, calendarDate, existingAppointment]);

    // Computed values for time slots and available dates
    const timeSlots = useMemo(() => {
        if (!selectedDate || !appointmentData) {
            return [];
        }
        return getTimeSlotsForDate(
            appointmentData,
            selectedDate.getDate(),
            appointmentData.currentMonthNumber,
            appointmentData.currentYear
        );
    }, [selectedDate, appointmentData, appointmentData?.currentMonthNumber, appointmentData?.currentYear]);

    // Get available dates from appointment data
    const availableDates = useMemo(() => {
        if (!appointmentData) {
            return [];
        }
        return formatAvailableDates(appointmentData);
    }, [appointmentData]);

    const handleDateSelect = (date) => {
        setSelectedDate(date);
        setSelectedTimeSlot(null); // Reset time slot when date changes
    };

    const handleTimeSlotSelect = (timeSlot) => {
        setSelectedTimeSlot(timeSlot);
    };

    const handleMeetingTypeSelect = (type) => {
        setSelectedMeetingType(type);
    };

    const handleContinue = () => {
        if (selectedDate && selectedTimeSlot && selectedMeetingType && rescheduleReason.trim()) {
            onContinue({
                date: selectedDate,
                timeSlot: selectedTimeSlot,
                meetingType: selectedMeetingType,
                reason: rescheduleReason.trim()
            });
        }
    };

    const handleCancel = () => {
        // Reset state
        setSelectedDate(null);
        setSelectedTimeSlot(null);
        setSelectedMeetingType(appointment?.meeting_type || '');
        setRescheduleReason('');
        onCancel();
        onClose();
    };

    const isFormValid = selectedDate && selectedTimeSlot && selectedMeetingType && rescheduleReason.trim();

    // Common content for both modal and sheet
    const renderContent = () => (
        <>
            {/* Custom Header */}
            <div className="flex items-center justify-between p-4 md:p-6 border-b newBorderColor bg-white sticky top-0 z-10">
                <div className="flex flex-col gap-2">
                    <h2 className="text-lg md:text-xl font-bold brandColor">
                        {t('rescheduleAppointment')}
                    </h2>
                    <div className="flex items-center gap-1 primaryBgLight12 px-2 py-1 rounded-sm w-fit">
                        <span className="text-sm md:text-base font-medium leadColor">
                            {t('property')}: {propertyTitle}
                        </span>
                    </div>
                </div>
                <button
                    onClick={handleCancel}
                    className="w-10 h-10 flex items-center justify-center rounded-xl primaryBackgroundBg leadColor hover:opacity-80 transition-opacity"
                    aria-label="Close"
                >
                    <IoMdClose className="w-6 h-6" />
                </button>
            </div>

            {/* Modal Body */}
            <div className="flex flex-col gap-4 lg:gap-6 p-4 lg:p-6 primaryBackgroundBg overflow-y-auto max-h-[550px]">
                {/* Left Column - Calendar */}
                <div className="flex-1 w-full">
                    <CalendarCard
                        availableDates={availableDates}
                        selectedDate={selectedDate}
                        onDateSelect={handleDateSelect}
                        date={calendarDate}
                        onCalendarDateChange={setCalendarDate}
                    />
                </div>

                {/* Right Column - Time Slots and Meeting Type */}
                <div className="grid grid-cols-1 md:grid-cols-2 gap-4 flex-1 w-full">
                    <SelectTimeSlotCard
                        selectedDate={selectedDate}
                        timeSlots={timeSlots}
                        selectedTimeSlot={selectedTimeSlot}
                        onSelectTimeSlot={handleTimeSlotSelect}
                    />
                    <MeetingTypeCard
                        meetingTypes={availableMeetingTypes}
                        selectedType={selectedMeetingType}
                        onSelectMeetingType={handleMeetingTypeSelect}
                    />
                </div>

                {/* Reason Card */}
                <div className="w-full">
                    <div className="bg-white rounded-2xl p-4 border newBorderColor">
                        <div className="flex flex-col gap-3">
                            <div className="flex items-center gap-2">
                                <span className="text-base font-semibold brandColor">{t("reasonForReschedule")}</span>
                                <span className="text-red-500 text-sm">*</span>
                            </div>
                            <textarea
                                value={rescheduleReason}
                                onChange={(e) => setRescheduleReason(e.target.value)}
                                placeholder={t("pleaseProvideReasonForReschedule") || "Please provide a reason for rescheduling..."}
                                className="w-full p-3 border newBorderColor rounded-lg resize-none focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                rows={3}
                                maxLength={500}
                            />
                            <div className="flex justify-between items-center text-sm text-gray-500">

                                <span>{rescheduleReason.length}/500</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {/* Modal Footer */}
            <div className="flex flex-col-reverse sm:flex-row justify-end items-center gap-4 p-4 md:p-6 bg-white border-t newBorderColor">
                <button
                    onClick={handleCancel}
                    className="w-full sm:w-auto px-6 py-3 rounded-lg newBorder brandColor font-medium hover:primaryBackgroundBg transition-colors"
                    aria-label="Cancel"
                >
                    {t('cancel')}
                </button>
                <button
                    onClick={handleContinue}
                    disabled={!isFormValid || isLoading}
                    className={`w-full sm:w-auto px-6 py-3 rounded-lg font-medium transition-colors ${(isFormValid && !isLoading)
                        ? 'primaryBg primaryTextColor hover:opacity-90'
                        : 'primaryBackgroundBg text-gray-400 cursor-not-allowed'
                        }`}
                    aria-label="Continue"
                >
                    {isLoading ? t('loading') : t('continue')}
                </button>
            </div>
        </>
    );

    // Render as bottom sheet on mobile, modal on desktop
    return isMobile ? (
        <Sheet open={isOpen} onOpenChange={onClose}>
            <SheetContent
                side="bottom"
                className="rounded-t-2xl w-full p-0 gap-0 h-[90vh] !border-0 outline-none"
            >
                {renderContent()}
            </SheetContent>
        </Sheet>
    ) : (
        <Dialog open={isOpen} onOpenChange={onClose}>
            <DialogContent className="max-w-7xl w-full bg-white newBorder rounded-2xl p-0 gap-0 overflow-hidden">
                <DialogTitle className="sr-only">{t('rescheduleAppointment')}</DialogTitle>
                <DialogDescription className="sr-only">{t('rescheduleAppointment')}</DialogDescription>
                {renderContent()}
            </DialogContent>
        </Dialog>
    );
};

export default RescheduleAppointmentModal;