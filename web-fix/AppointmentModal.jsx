import { useEffect, useMemo, useState } from "react";

// Import Shadcn UI components
import {
    Dialog,
    DialogContent,
    DialogHeader,
    DialogTitle,
    DialogDescription,
} from "@/components/ui/dialog";
import {
    Sheet,
    SheetContent,
    SheetHeader,
    SheetTitle,
    SheetDescription,
} from "@/components/ui/sheet";
import { useMobile } from "@/hooks/use-mobile";

// Import reusable components
import StepIndicator from "./AppointmentStepIndicator";
import ProgressBar from "./progress-bar/ProgressBar";
import AppointmentContent from "./AppointmentContent";
import AppointmentHeader from "./AppointmentHeader";
import AppointmentButton from "./AppointmentButton";
import { useTranslation } from "../context/TranslationContext";
import { FaArrowLeft, FaArrowRight } from "react-icons/fa";
import { bookAppointmentApi, checkAgentBookingAvailabilityApi, getAgentPropertiesApi, getMonthWiseTimeSchedulesApi } from "@/api/apiRoutes";
import { formatAppointmentData, formatMeetingTypes, getTimeSlotsForDate, formatAvailableDates, formatDate } from "@/utils/appointmentHelper";
import toast from "react-hot-toast";
import { useMediaQuery } from "@/hooks/use-media-query";

const AppointmentScheduleModal = ({
    isOpen = false,
    onClose,
    handlePrev,
    onContinue,
    // properties = [],
    selectedProperty: propSelectedProperty = null,
    currentStep = 1,
    totalSteps = 3,
    isLoading = false,
    isBookingFromProperty = false,
    handleBookingStep = () => { },
    agentDetails: agent = {}
}) => {

    const t = useTranslation();
    const [searchQuery, setSearchQuery] = useState("");
    const [selectedProperty, setSelectedProperty] = useState(propSelectedProperty);
    const [filteredProperties, setFilteredProperties] = useState([]);
    const [date, setDate] = useState(new Date());
    const [selectedTimeSlot, setSelectedTimeSlot] = useState(null);
    const [selectedMeetingType, setSelectedMeetingType] = useState(null);
    const [availableMeetingTypes, setAvailableMeetingTypes] = useState([]);
    const [appointmentData, setAppointmentData] = useState({});

    const [selectedDate, setSelectedDate] = useState(new Date());
    const [agentDetails, setAgentDetails] = useState(agent);
    const [meetingNotes, setMeetingNotes] = useState("");
    const [properties, setProperties] = useState([]);
    const [totalProperties, setTotalProperties] = useState(0);
    const [offset, setOffset] = useState(0);
    const limit = 10;
    const [hasMore, setHasMore] = useState(false);
    const [isSubmitting, setIsSubmitting] = useState(false);

    const handleFetchAgentProperties = async (searchQuery = "", currentOffset = 0) => {
        try {
            const response = await getAgentPropertiesApi({
                slug_id: agentDetails?.slug_id,
                is_admin: agentDetails?.is_admin === true ? 1 : "",
                search: searchQuery,
                limit,
                offset: currentOffset
            });
            if (response?.data) {
                if (currentOffset === 0) {
                    setProperties(response.data?.properties_data);
                } else {
                    setProperties((prev) => [...prev, ...response.data?.properties_data]);
                }
                if (totalProperties === 0) {
                    setTotalProperties(response.total);
                }
                const loadedValues = currentOffset + limit;
                setHasMore(response?.total > loadedValues);
            } else {
                setProperties([]);
                setTotalProperties(0);
                setHasMore(false);
            }
        } catch (error) {
            console.error("Error fetching agent properties", error);
        }
    }

    const handleLoadMoreProperties = () => {
        if (!hasMore) return;
        const newOffset = offset + limit;
        setOffset(newOffset);
        handleFetchAgentProperties(searchQuery, newOffset);
    };

    useEffect(() => {
        if (currentStep === 1 && isOpen && searchQuery.trim() === "") {
            handleFetchAgentProperties();
        }
    }, [isOpen, searchQuery])

    const validateStep = (step) => {
        switch (step) {
            case 1:
                // Validate step 1
                if (!selectedProperty) {
                    toast.error(t("plsSelectProperty"));
                    return false;
                }
                return true;
            case 2:
                // When moving from step 1 to 2
                if (currentStep === 1 && selectedProperty) {
                    handleBookingStep(2);
                    return true;
                }
                
                // When moving from step 2 to 3, validate all required fields
                if (!selectedProperty) {
                    toast.error(t("plsSelectProperty"));
                    return false;
                }
                if (!selectedDate) {
                    toast.error(t("plsSelectValidDate"));
                    return false;
                }
                if (!selectedTimeSlot) {
                    toast.error(t("plsSelectMeetingTime"));
                    return false;
                }
                if (!selectedMeetingType) {
                    toast.error(t("plsSelectMeetingType"));
                    return false;
                }
                return true;
            case 3:
                // Validate all required fields again before submission
                if (!selectedProperty) {
                    toast.error(t("plsSelectProperty"));
                    return false;
                }
                if (!selectedDate) {
                    toast.error(t("plsSelectValidDate"));
                    return false;
                }
                if (!selectedTimeSlot) {
                    toast.error(t("plsSelectMeetingTime"));
                    return false;
                }
                if (!selectedMeetingType) {
                    toast.error(t("plsSelectMeetingType"));
                    return false;
                }
                return true;
            default:
                return false;
        }
    };


    // Filter properties based on search query
    // useEffect(() => {
    //     // if (searchQuery.trim()) {
    //     //     const filtered = properties.filter(property =>
    //     //         property.title?.toLowerCase().includes(searchQuery.toLowerCase()) ||
    //     //         property.location?.toLowerCase().includes(searchQuery.toLowerCase())
    //     //     );
    //     //     setFilteredProperties(filtered);
    //     // } else {
    //     //     setFilteredProperties(properties);
    //     // }
    //     // handleFetchAgentProperties({ search: searchQuery.trim() });
    //     if()
    // }, [searchQuery, properties]);


    // Get time slots for selected date
    const timeSlots = useMemo(() => {
        if (!date || !appointmentData) {
            return [];
        }
        return getTimeSlotsForDate(
            appointmentData,
            selectedDate.getDate(),
            appointmentData.currentMonthNumber,
            appointmentData.currentYear
        );
    }, [selectedDate, appointmentData, appointmentData.currentMonthNumber, appointmentData.currentYear]);

    // Get available dates from appointment data
    const availableDates = useMemo(() => {
        if (!appointmentData) {
            return [];
        }
        return formatAvailableDates(appointmentData);
    }, [appointmentData]);

    const handleDateSelect = (selectedDate) => {
        setSelectedDate(selectedDate);
        // Reset time slot when date changes
        setSelectedTimeSlot(null);
    };

    const handleMeetingNotesChange = (value) => {
        setMeetingNotes(value);
    };

    const handleTimeSlotSelect = (timeSlot) => {
        setSelectedTimeSlot(timeSlot);
    };

    const handleMeetingTypeSelect = (meetingType) => {
        setSelectedMeetingType(meetingType);
    };

    const handleResetAppointmentDetails = () => {
        setSelectedDate(new Date());
        setSelectedTimeSlot(null);
        setSelectedMeetingType(null);
        handleBookingStep(2); // Navigate back to step 2
    };

    const handleSearch = (query) => {
        handleFetchAgentProperties(query);
    };

    const handlePropertySelect = (property) => {
        setSelectedProperty(property);
    };

    const handleCheckAvailability = async () => {
        if (isSubmitting) return; // Prevent double clicks
        
        setIsSubmitting(true);
        try {
            const formattedDate = `${selectedDate.getFullYear()}-${(selectedDate.getMonth() + 1).toString().padStart(2, '0')}-${selectedDate.getDate().toString().padStart(2, '0')}`;
            const response = await checkAgentBookingAvailabilityApi({
                agent_id: selectedProperty?.added_by,
                date: formattedDate,
                start_time: selectedTimeSlot?.start_time,
                end_time: selectedTimeSlot?.end_time
            });
            if (response?.data && response.data?.available === true) {
                handleSubmitAppointment();
            } else {
                toast.error(response?.error || t("selectedSlotNotAvailable"));
            }
        } catch (error) {
            console.error("Error checking agent booking availability", error);
            toast.error(error?.message || t("somethingWentWrong"));
        } finally {
            setIsSubmitting(false);
        }
    };

    const handleSubmitAppointment = async () => {
        try {
            const formattedDate = `${selectedDate.getFullYear()}-${(selectedDate.getMonth() + 1).toString().padStart(2, '0')}-${selectedDate.getDate().toString().padStart(2, '0')}`;
            const response = await bookAppointmentApi({
                property_id: selectedProperty?.id,
                meeting_type: selectedMeetingType,
                date: formattedDate,
                start_time: selectedTimeSlot?.start_time,
                end_time: selectedTimeSlot?.end_time,
                notes: meetingNotes
            });
            if (response?.data) {
                toast.success(t("appointmentBookingRequestSuccess"));
                onClose && onClose();
            }
        } catch (error) {
            console.error("Error booking appointment", error);
            toast.error(error?.message || t("somethingWentWrong"));
        } finally {
            setIsSubmitting(false);
        }
    };

    const handleContinue = () => {
        if (isSubmitting) return; // Prevent double clicks
        
        if (currentStep === totalSteps) {
            handleCheckAvailability();
            return;
        }
        if (!validateStep(currentStep)) {
            return;
        }
        // Call onContinue to move to next step
        if (onContinue) {
            onContinue(selectedProperty);
        }
    };

    const handleOpenChange = (open) => {
        if (!open) {
            onClose && onClose();
        }
    };


    const fetchMonthlyTimeSlots = async () => {
        try {
            const response = await getMonthWiseTimeSchedulesApi({
                month: date.getMonth() + 1,
                year: date.getFullYear(),
                agent_id: selectedProperty?.added_by
            });
            if (response?.data) {
                setAppointmentData(formatAppointmentData(response?.data, date));
                let meetingTypes = formatMeetingTypes(response?.data?.availability_types);
                setAvailableMeetingTypes(meetingTypes);
            }
        } catch (error) {
            console.error("Error fetching monthly time slots", error);
        }
    };

    useEffect(() => {
        if ((isBookingFromProperty && currentStep === 2 && isOpen) || (currentStep === 2 && isOpen && selectedProperty)) {
            fetchMonthlyTimeSlots();
        }
    }, [isOpen, isBookingFromProperty, date, selectedProperty, currentStep]);

    useEffect(() => {
        if (propSelectedProperty) {
            const agentDetails = {
                name: propSelectedProperty.customer_name,
                email: propSelectedProperty.email,
                is_verified: propSelectedProperty.is_verified,
                profile: propSelectedProperty.profile
            };
            setAgentDetails(agentDetails);
        }
    }, [propSelectedProperty, selectedProperty]);

    const displayProperties = filteredProperties.length > 0 ? filteredProperties : properties;

    const checkandHandleBookingStep = (step) => {
        if (!validateStep(step)) {
            return;
        } else {
            handleBookingStep(step);
        }
    };
    const isMobile = useMediaQuery("(max-width: 768px)")

    const renderContent = () => (
        <div className="flex flex-col md:flex-row overflow-hidden">
            {/* Modal Sidebar */}
            <div className={`${isMobile ? 'hidden md:flex' : 'flex'} w-full md:max-w-80 flex-shrink-0 flex-col`}>
                {/* Header */}
                {isMobile ? (
                    <SheetHeader className="p-6 border-b newBorderColor text-left space-y-2">
                        <SheetTitle className="brandColor font-bold text-xl">
                            {t("scheduleAnAppointment")}
                        </SheetTitle>
                        <SheetDescription className="text-sm leadColor font-medium">
                            {t("bookMeetingDescription")}
                        </SheetDescription>
                    </SheetHeader>
                ) : (
                    <DialogHeader className="p-6 border-b border-r newBorderColor text-left space-y-2">
                        <DialogTitle className="brandColor font-bold text-xl">
                            {t("scheduleAnAppointment")}
                        </DialogTitle>
                        <DialogDescription className="text-sm leadColor font-medium">
                            {t("bookMeetingDescription")}
                        </DialogDescription>
                    </DialogHeader>
                )}

                {/* Steps */}
                <nav className="flex-1 p-6 space-y-6 border-r" aria-label="Appointment steps">
                    <StepIndicator
                        stepNumber={1}
                        title={t("chooseProperty")}
                        isActive={currentStep === 1}
                        disabled={isBookingFromProperty && currentStep === 2}
                    />
                    <StepIndicator
                        stepNumber={2}
                        title={t("pickDateAndMeetPrefs")}
                        isActive={currentStep === 2}
                        onClick={() => checkandHandleBookingStep(2)}
                    />
                    <StepIndicator
                        stepNumber={3}
                        title={t("confirmation")}
                        isActive={currentStep === 3}
                        onClick={() => checkandHandleBookingStep(3)}
                    />
                </nav>

                {/* Progress Bar */}
                <ProgressBar currentStep={currentStep} totalSteps={totalSteps} />
            </div>

            {/* Modal Body */}
            <main className="w-full flex flex-col primaryBackgroundBg relative">
                {/* Header */}
                <AppointmentHeader currentStep={currentStep} onClose={onClose} />

                {/* Content */}
                <div className={`flex flex-col gap-6 px-6 overflow-hidden ${isMobile ? 'pb-6' : ''}`}>
                    <AppointmentContent
                        selectedDate={selectedDate}
                        calendarDate={date}
                        selectedTimeSlot={selectedTimeSlot}
                        selectedMeetingType={selectedMeetingType}
                        availableMeetingTypes={availableMeetingTypes}
                        appointmentData={{
                            ...appointmentData,
                            availableDates: availableDates,
                            availableMeetingTypes: availableMeetingTypes
                        }}
                        timeSlots={timeSlots}
                        currentStep={currentStep}
                        displayProperties={displayProperties}
                        selectedProperty={selectedProperty}
                        searchQuery={searchQuery}
                        handleSearch={handleSearch}
                        totalProperties={totalProperties}
                        handlePropertySelect={handlePropertySelect}
                        setSearchQuery={setSearchQuery}
                        onDateSelect={handleDateSelect}
                        onTimeSlotSelect={handleTimeSlotSelect}
                        onMeetingTypeSelect={handleMeetingTypeSelect}
                        onCalendarDateChange={setDate}
                        agentDetails={agentDetails}
                        meetingNotes={meetingNotes}
                        onMeetingNotesChange={handleMeetingNotesChange}
                        onResetAppointmentDetails={handleResetAppointmentDetails}
                        handleLoadMoreProperties={handleLoadMoreProperties}
                        hasMore={hasMore}
                        isMobile={isMobile}
                    />
                </div>

                {/* Modal Footer */}
                <footer className={`flex mt-auto flex-row justify-end gap-2 px-6 py-4 ${isMobile ? 'sticky bottom-0 bg-white border-t newBorderColor' : ''}`}>
                    <AppointmentButton
                        onClick={handlePrev}
                        variant="outline"
                        icon={FaArrowLeft}
                        iconPosition="left"
                        className="mr-4"
                        disabled={isBookingFromProperty && currentStep === 2}
                    >
                        {t("previous")}
                    </AppointmentButton>

                    <AppointmentButton
                        onClick={handleContinue}
                        icon={FaArrowRight}
                        variant="primary"
                        disabled={isSubmitting}
                        aria-describedby={!selectedProperty ? "continue-button-disabled" : undefined}
                    >
                        {isSubmitting ? t("loading") : isLoading ? t("loading") : currentStep === totalSteps ? t("submit") : t("continue")}
                    </AppointmentButton>
                    {!selectedProperty && (
                        <div id="continue-button-disabled" className="sr-only">
                            {t("plsSelectProperty")}
                        </div>
                    )}
                </footer>
            </main>
        </div>
    );

    return isMobile ? (
        <Sheet open={isOpen} onOpenChange={handleOpenChange}>
            <SheetContent 
                side="bottom" 
                className="rounded-t-2xl w-full p-0 gap-0 h-[85vh] !border-0 outline-none"
                onInteractOutside={(e) => e.preventDefault()}
            >
                <div className="flex flex-col h-full overflow-hidden">
                    <div className="flex-1 overflow-y-auto no-scrollbar">
                        {renderContent()}
                    </div>
                </div>
            </SheetContent>
        </Sheet>
    ) : (
        <Dialog open={isOpen} onOpenChange={handleOpenChange}>
            <DialogContent 
                className="max-w-[310px] md:max-w-7xl rounded-2xl w-full p-0 gap-0 max-h-[500px] md:max-h-[900px] !border-0 overflow-auto outline-none no-scrollbar [&>button]:hidden"
                onInteractOutside={(e) => e.preventDefault()}
            >
                <DialogTitle className="sr-only">{t("scheduleAnAppointment")}</DialogTitle>
                <DialogDescription className="sr-only">{t("bookMeetingDescription")}</DialogDescription>
                {renderContent()}
            </DialogContent>
        </Dialog>
    );
};

export default AppointmentScheduleModal;