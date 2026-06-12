import { useState, useEffect } from 'react';
import { IoMdClose, IoMdTime, IoMdUndo } from 'react-icons/io';
import { FaPlus, FaMinus } from 'react-icons/fa';
import {
    Dialog,
    DialogContent,
    DialogHeader,
    DialogTitle,
    DialogDescription,
} from '@/components/ui/dialog';
import {
    Sheet,
    SheetContent,
    SheetHeader,
    SheetTitle,
} from "@/components/ui/sheet";
import { useMediaQuery } from '@/hooks/use-media-query';
// COMMENTED OUT: Select components no longer needed since editing is disabled
// import { Select, SelectItem, SelectTrigger, SelectValue, SelectContent } from '@/components/ui/select';
import { getFormattedDate } from '@/utils/helperFunction';
import { useTranslation } from '@/components/context/TranslationContext';
import { MdAddCircleOutline } from 'react-icons/md';
import { formatMinutesToTime, parseTimeToMinutes, formatTimeToAMPM, filterExtraSlotsByDate } from '@/utils/appointmentHelper';
import TimePicker from '@/components/reusable-components/TimePicker';
import toast from 'react-hot-toast';

const AddExtraTimeSlotModal = ({
    isOpen,
    onClose,
    date,
    timeSlots = [],
    onRemoveTimeSlot,
    removeSlotIds,
    setRemoveSlotIds,
    onSave,
    onCancel,
    preferencesData = {},
    scheduleData = {}, // Add scheduleData prop for duplicate checking
}) => {
    const t = useTranslation();

    // Filter timeSlots for the selected date
    const getFilteredTimeSlotsForDate = () => {
        if (!date || !timeSlots) return [];

        const year = date.getFullYear();
        const month = String(date.getMonth() + 1).padStart(2, '0');
        const day = String(date.getDate()).padStart(2, '0');
        const targetDate = `${year}-${month}-${day}`;

        return filterExtraSlotsByDate(timeSlots, targetDate);
    };

    const [newTimeSlots, setNewTimeSlots] = useState(getFilteredTimeSlotsForDate());
    const meetingDurationMinutes = preferencesData.meeting_duration_minutes;
    const bufferTimeMinutes = preferencesData.buffer_time_minutes;
    const [showUndo, setShowUndo] = useState(false);

    // Update filtered slots when date or timeSlots change
    useEffect(() => {
        setNewTimeSlots(getFilteredTimeSlotsForDate());
    }, [date, timeSlots]);

    // COMMENTED OUT: Complex conflict detection and schedule checking logic
    /*
    // Helper function to get existing schedule slots for the selected date
    const getExistingScheduleSlotsForDate = (selectedDate) => {
        if (!selectedDate || !scheduleData) return [];

        const year = selectedDate.getFullYear();
        const month = selectedDate.getMonth() + 1; // JavaScript months are 0-indexed
        const day = selectedDate.getDate();

        const formattedMonth = String(month).padStart(2, '0');
        const formattedDay = String(day).padStart(2, '0');
        const dateKey = `${year}-${formattedMonth}-${formattedDay}`;

        const daySchedule = scheduleData[dateKey];
        if (!daySchedule || !daySchedule.weekSchedules) return [];

        // Extract time slots from schedule data
        return daySchedule.weekSchedules.map(schedule => ({
            start: schedule.startTime,
            end: schedule.endTime,
            id: schedule.id
        }));
    };

    // Check if a time slot conflicts with existing schedule or extra slots
    const isTimeSlotConflicting = (startTime, endTime, excludeId = null) => {
        const startMinutes = parseTimeToMinutes(startTime);
        const endMinutes = parseTimeToMinutes(endTime);

        // Check against existing schedule slots for this date
        const existingScheduleSlots = getExistingScheduleSlotsForDate(date);
        const allExistingSlots = [
            ...existingScheduleSlots,
            ...newTimeSlots.filter(slot => slot.id !== excludeId)
        ];

        return allExistingSlots.some(slot => {
            const slotStart = parseTimeToMinutes(slot.start || slot.start_time || slot.startTime);
            const slotEnd = parseTimeToMinutes(slot.end || slot.end_time || slot.endTime);

            // Check for overlap (including buffer time)
            const slotEndWithBuffer = slotEnd + bufferTimeMinutes;
            return (startMinutes < slotEndWithBuffer && endMinutes > slotStart);
        });
    };
    */

    // COMMENTED OUT: Complex time slot generation with conflict detection
    /*
    const handleAddTimeSlot = () => {
        setNewTimeSlots(prev => {
            const currentSlots = Array.isArray(prev) ? prev?.map((val) => ({
                id: val.id,
                start: val.start_time || val.start,
                end: val.end_time || val.end
            })) : [];
            const maxSlotsAllowed = dailyBookingLimit;

            // Check if we've reached the daily booking limit
            if (currentSlots.length >= maxSlotsAllowed) {
                toast.error(`${t('maximum')} ${maxSlotsAllowed} time slots allowed per day based on your daily booking limit.`);
                return prev;
            }

            // Get all existing slots (schedule + current extra slots) for conflict detection
            const existingScheduleSlots = getExistingScheduleSlotsForDate(date);
            const allExistingSlots = [...existingScheduleSlots, ...currentSlots];
            // Find the next available time slot
            const availableOptions = generateAvailableTimeOptions(allExistingSlots, meetingDurationMinutes, bufferTimeMinutes);

            if (availableOptions.length === 0) {
                toast.error('No available time slots remaining for this day. All time slots conflict with existing appointments and buffer times.');
                return prev;
            }

            // Use the first available time slot (earliest available time)
            const nextAvailableTime = availableOptions[0]?.value;

            if (!nextAvailableTime) {
                toast.error('Unable to determine next available time slot.');
                return prev;
            }

            const startMinutes = parseTimeToMinutes(nextAvailableTime);
            const endMinutes = startMinutes + meetingDurationMinutes;

            const newSlot = {
                id: `new-${Date.now()}`, // New slot, ID to be assigned by backend
                start_time: nextAvailableTime,
                end_time: formatMinutesToTime(endMinutes),
                start: nextAvailableTime,
                end: formatMinutesToTime(endMinutes),
                startTime: nextAvailableTime,
                endTime: formatMinutesToTime(endMinutes)
            };

            return [...prev, newSlot];
        });
    };
    */

    // NEW SIMPLIFIED HANDLER - Simple time slot addition without complex validation
    const handleAddTimeSlot = () => {
        setNewTimeSlots(prev => {
            const currentSlots = Array.isArray(prev) ? prev : [];

            let newStartTime = "09:00"; // Default start time

            // If there are existing slots, calculate start time from the latest slot's end time + buffer time
            if (currentSlots.length > 0 && bufferTimeMinutes && meetingDurationMinutes) {
                // Find the slot with the latest end time
                const sortedSlots = [...currentSlots].sort((a, b) => {
                    const endA = parseTimeToMinutes(a.end_time || a.end || a.endTime);
                    const endB = parseTimeToMinutes(b.end_time || b.end || b.endTime);
                    return endB - endA;
                });
                const latestSlot = sortedSlots[0];
                const latestEndMinutes = parseTimeToMinutes(latestSlot.end_time || latestSlot.end || latestSlot.endTime);

                // Calculate new start time: latest end time + buffer time
                const newStartMinutes = latestEndMinutes + bufferTimeMinutes;

                // Check if the new slot would go beyond the day (24:00)
                const newEndMinutes = newStartMinutes + meetingDurationMinutes;
                if (newEndMinutes < 24 * 60) {
                    newStartTime = formatMinutesToTime(newStartMinutes);
                } else {
                    // If it would exceed the day, keep the default 09:00 start time
                    toast.error(t("cannotAddSlotWouldExceedDay"));
                }
            }

            // Calculate end time - create a time range (default 3 hours for flexibility)
            const startMinutes = parseTimeToMinutes(newStartTime);
            const endMinutes = startMinutes + meetingDurationMinutes

            // If it would exceed the day, set end time to a reasonable hour (e.g., 6 PM)
            const maxEndMinutes = endMinutes >= 24 * 60 ? parseTimeToMinutes("18:00") : endMinutes;
            const newEndTime = formatMinutesToTime(maxEndMinutes);

            const newSlot = {
                id: `new-${Date.now()}`, // New slot, ID to be assigned by backend
                start_time: newStartTime,
                end_time: newEndTime,
                start: newStartTime,
                end: newEndTime,
                startTime: newStartTime,
                endTime: newEndTime
            };

            return [...prev, newSlot];
        });
    };

    // COMMENTED OUT: Complex available time options generation and editing logic
    /*
    // Generate available time options considering existing slots and buffer times
    const getAvailableTimeOptions = (currentSlotIndex = null) => {
        // Get existing schedule slots for this date
        const existingScheduleSlots = getExistingScheduleSlotsForDate(date);

        // Exclude the current slot being edited from conflict detection
        const otherExtraSlots = currentSlotIndex !== null
            ? newTimeSlots.filter((_, index) => index !== currentSlotIndex)
            : newTimeSlots;

        const formattedOtherExtraSlots = otherExtraSlots.map(slot => ({
            start: slot.start_time || slot.start || slot.startTime,
            end: slot.end_time || slot.end || slot.endTime,
            id: slot.id
        }));

        // Combine schedule slots and other extra slots
        const allExistingSlots = [...existingScheduleSlots, ...formattedOtherExtraSlots];
        const rawOptions = generateAvailableTimeOptions(allExistingSlots, meetingDurationMinutes, bufferTimeMinutes);
        // Format options to show just the start time in AM/PM format for dropdown
        return rawOptions.map(option => ({
            value: option.value,
            label: formatTimeToAMPM(option.value) // Show just start time in AM/PM format
        }));
    };

    // Handle time slot change
    const handleTimeSlotChange = (slotIndex, field, value) => {
        if (field !== 'start') return; // Only handle start time changes, end time is auto-calculated

        const endTime = formatMinutesToTime(parseTimeToMinutes(value) + meetingDurationMinutes);

        // Check for conflicts with this new time
        if (isTimeSlotConflicting(value, endTime, newTimeSlots[slotIndex]?.id)) {
            toast.error('This time slot conflicts with existing schedule or appointments.');
            return;
        }

        setNewTimeSlots(prev =>
            prev.map((slot, index) =>
                index === slotIndex
                    ? {
                        ...slot,
                        start_time: value,
                        end_time: endTime,
                        start: value,
                        end: endTime,
                        startTime: value,
                        endTime: endTime
                    }
                    : slot
            )
        );
    };
    */

    // Helper function to determine if a slot is new (editable) or existing (read-only)
    const isNewSlot = (slot) => {
        // New slots have IDs that start with "new-" or are null/undefined
        return !slot.id || slot.id.toString().startsWith('new-');
    };

    // Handler for TimePicker changes on new slots
    const handleNewSlotTimeChange = (slotIndex, field, newTime) => {
        if (!newTime) return;

        try {
            // Parse the time from TimePicker (format: "HH:MM AM/PM")
            const timeMatch = newTime.match(/^(\d{1,2}):(\d{2})\s*(AM|PM)$/i);
            if (!timeMatch) {
                console.warn('Invalid time format received from TimePicker:', newTime);
                return;
            }

            const [, hours, minutes, period] = timeMatch;
            let hour24 = parseInt(hours);

            // Convert to 24-hour format
            if (period.toUpperCase() === 'PM' && hour24 !== 12) {
                hour24 += 12;
            } else if (period.toUpperCase() === 'AM' && hour24 === 12) {
                hour24 = 0;
            }

            const newTimeValue = `${hour24.toString().padStart(2, '0')}:${minutes}`;
            const newTimeMinutes = parseTimeToMinutes(newTimeValue);

            // Check if the time is disabled
            if (isTimeDisabled(newTime, slotIndex)) {
                toast.error(t("timeSlotNotAvailable"));
                return;
            }

            setNewTimeSlots(prev =>
                prev.map((slot, index) => {
                    if (index !== slotIndex) return slot;

                    const currentStartMinutes = parseTimeToMinutes(slot.start_time || slot.start || slot.startTime);
                    const currentEndMinutes = parseTimeToMinutes(slot.end_time || slot.end || slot.endTime);

                    let updatedStartTime, updatedEndTime;

                    if (field === 'start') {
                        updatedStartTime = newTimeValue;
                        // Keep the same duration if possible, otherwise use default meeting duration
                        const currentDuration = currentEndMinutes - currentStartMinutes;
                        const duration = currentDuration > 0 ? currentDuration : (meetingDurationMinutes || 60);
                        const newEndMinutes = newTimeMinutes + duration;

                        // Check if end time would go beyond 24 hours
                        if (newEndMinutes >= 24 * 60) {
                            toast.error(t("cannotScheduleBeyondEndOfDay"));
                            return slot;
                        }

                        updatedEndTime = formatMinutesToTime(newEndMinutes);
                    } else if (field === 'end') {
                        updatedEndTime = newTimeValue;
                        updatedStartTime = slot.start_time || slot.start || slot.startTime;

                        // Validate that end time is after start time
                        if (newTimeMinutes <= currentStartMinutes) {
                            toast.error(t("endTimeMustBeAfterStartTime") || "End time must be after start time");
                            return slot;
                        }
                    }

                    return {
                        ...slot,
                        start_time: updatedStartTime,
                        end_time: updatedEndTime,
                        start: updatedStartTime,
                        end: updatedEndTime,
                        startTime: updatedStartTime,
                        endTime: updatedEndTime
                    };
                })
            );
        } catch (error) {
            console.error('Error handling time change:', error);
            toast.error('Error updating time slot. Please try again.');
        }
    };

    const handleRemoveSlot = (slotId) => {
        const slotLength = newTimeSlots.length;
        if (slotLength <= 1 && slotId && !slotId.toString().startsWith('new-')) {
            setShowUndo(true);
            setRemoveSlotIds(prev => [...prev, slotId]);
            return;
        }
        setNewTimeSlots(prev => {
            const updatedSlots = prev.filter(slot => slot.id !== slotId);
            return updatedSlots;
        });
        if (slotId) {
            setRemoveSlotIds(prev => [...prev, slotId]);
        }
    };

    // Helper function to get all disabled time slots including buffer times
    const getDisabledTimeSlots = (currentSlotIndex = null) => {
        const disabledRanges = [];

        // Get all slots except the current one being edited
        const otherSlots = newTimeSlots.filter((_, index) => index !== currentSlotIndex);

        // Add buffer time to start and end of each slot
        otherSlots.forEach(slot => {
            const startTime = slot.start_time || slot.start || slot.startTime;
            const endTime = slot.end_time || slot.end || slot.endTime;

            // Convert to minutes for easier calculation
            const startMinutes = parseTimeToMinutes(startTime);
            const endMinutes = parseTimeToMinutes(endTime);

            // Add buffer time to both start and end
            const bufferStart = Math.max(0, startMinutes - (bufferTimeMinutes || 0));
            const bufferEnd = Math.min(24 * 60, endMinutes + (bufferTimeMinutes || 0));

            disabledRanges.push({
                start: formatMinutesToTime(bufferStart),
                end: formatMinutesToTime(bufferEnd)
            });
        });

        return disabledRanges;
    };

    // Helper function to check if a time is within disabled ranges
    const isTimeDisabled = (time, currentSlotIndex) => {
        // Convert AM/PM time to 24-hour format for comparison
        const timeMatch = time.match(/^(\d{1,2}):(\d{2})\s*(AM|PM)$/i);
        if (!timeMatch) return false;

        const [, hours, minutes, period] = timeMatch;
        let hour24 = parseInt(hours);

        // Convert to 24-hour format
        if (period.toUpperCase() === 'PM' && hour24 !== 12) {
            hour24 += 12;
        } else if (period.toUpperCase() === 'AM' && hour24 === 12) {
            hour24 = 0;
        }

        const timeValue = `${hour24.toString().padStart(2, '0')}:${minutes}`;
        const timeMinutes = parseTimeToMinutes(timeValue);
        const disabledRanges = getDisabledTimeSlots(currentSlotIndex);

        // Get existing schedule slots for this date
        const year = date.getFullYear();
        const month = String(date.getMonth() + 1).padStart(2, '0');
        const day = String(date.getDate()).padStart(2, '0');
        const dateKey = `${year}-${month}-${day}`;

        // Get weekly schedule slots
        const weeklySlots = scheduleData[dateKey]?.weekSchedules || [];

        // Check against weekly schedule slots
        const isInWeeklySlot = weeklySlots.some(slot => {
            const slotStart = parseTimeToMinutes(slot.startTime);
            const slotEnd = parseTimeToMinutes(slot.endTime);
            const slotStartWithBuffer = slotStart - (bufferTimeMinutes || 0);
            const slotEndWithBuffer = slotEnd + (bufferTimeMinutes || 0);
            return timeMinutes >= slotStartWithBuffer && timeMinutes <= slotEndWithBuffer;
        });

        // Check against extra time slots
        const isInDisabledRange = disabledRanges.some(range => {
            const rangeStart = parseTimeToMinutes(range.start);
            const rangeEnd = parseTimeToMinutes(range.end);
            return timeMinutes >= rangeStart && timeMinutes <= rangeEnd;
        });

        return isInWeeklySlot || isInDisabledRange;
    };

    // Helper function to check if two time ranges overlap considering buffer time
    const doTimeSlotsOverlap = (start1, end1, start2, end2) => {
        // Only add buffer time between slots
        const bufferTime = bufferTimeMinutes || 0;

        // If slot2 starts after slot1
        if (start2 >= end1) {
            return (start2 - end1) < bufferTime; // Check if gap is less than buffer time
        }

        // If slot1 starts after slot2
        if (start1 >= end2) {
            return (start1 - end2) < bufferTime; // Check if gap is less than buffer time
        }

        // Direct overlap
        return (start1 < end2 && end1 > start2);
    };

    // Helper function to validate time slots
    const validateTimeSlots = (slots) => {
        const errors = [];

        // Sort slots by start time for easier validation
        const sortedSlots = [...slots].sort((a, b) => {
            const startA = parseTimeToMinutes(a.start_time || a.start || a.startTime);
            const startB = parseTimeToMinutes(b.start_time || b.start || b.startTime);
            return startA - startB;
        });

        sortedSlots.forEach((slot, index) => {
            const startTime = slot.start_time || slot.start || slot.startTime;
            const endTime = slot.end_time || slot.end || slot.endTime;
            const startMinutes = parseTimeToMinutes(startTime);
            const endMinutes = parseTimeToMinutes(endTime);

            // Check if end time is after start time
            if (endMinutes <= startMinutes) {
                errors.push(`${t("slot")} ${index + 1}: ${t("endTimeMustBeAfterStartTime")}`);
                return; // Skip further validation for this slot
            }

            // Check for minimum duration (at least meeting duration)
            const duration = endMinutes - startMinutes;
            if (duration < (meetingDurationMinutes || 15)) {
                errors.push(`${t("slot")} ${index + 1}: ${t("durationMustBeAtLeast15Minutes")}`);
                return; // Skip further validation for this slot
            }

            // Check for conflicts with existing schedule slots
            const year = date.getFullYear();
            const month = String(date.getMonth() + 1).padStart(2, '0');
            const day = String(date.getDate()).padStart(2, '0');
            const dateKey = `${year}-${month}-${day}`;
            const weeklySlots = scheduleData[dateKey]?.weekSchedules || [];

            // Check against weekly schedule slots
            const hasWeeklyConflict = weeklySlots.some(weeklySlot => {
                const weeklyStart = parseTimeToMinutes(weeklySlot.startTime);
                const weeklyEnd = parseTimeToMinutes(weeklySlot.endTime);
                return doTimeSlotsOverlap(startMinutes, endMinutes, weeklyStart, weeklyEnd);
            });

            if (hasWeeklyConflict) {
                errors.push(`${t("slot")} ${index + 1}: ${t("timeSlotConflictsWithWeeklySchedule")}`);
                return; // Skip further validation for this slot
            }

            // Check for conflicts with other extra slots
            const otherSlots = sortedSlots.filter((_, i) => i !== index);
            const hasExtraSlotConflict = otherSlots.some(otherSlot => {
                const otherStart = parseTimeToMinutes(otherSlot.start_time || otherSlot.start || otherSlot.startTime);
                const otherEnd = parseTimeToMinutes(otherSlot.end_time || otherSlot.end || otherSlot.endTime);
                return doTimeSlotsOverlap(startMinutes, endMinutes, otherStart, otherEnd);
            });

            if (hasExtraSlotConflict) {
                errors.push(`${t("slot")} ${index + 1}: ${t("timeSlotConflictsWithExisting")}`);
            }
        });

        return errors;
    };

    // Helper function to format time slots for API call with range support
    const formatTimeSlotsForAPI = (slots) => {
        return slots.map(slot => {
            const startTime = slot.start_time || slot.start || slot.startTime;
            const endTime = slot.end_time || slot.end || slot.endTime;

            // For new slots with time ranges, we can send them as ranges
            // Format: "10:00 to 13:00" or maintain individual slot format
            return {
                ...slot,
                start_time: startTime,
                end_time: endTime,
                // Add range format for display purposes
                timeRange: `${formatTimeToAMPM(startTime)} to ${formatTimeToAMPM(endTime)}`,
                // Keep original format for API compatibility
                start: startTime,
                end: endTime,
                startTime: startTime,
                endTime: endTime
            };
        });
    };

    const handleSave = async () => {
        try {
            // Validate time slots before saving
            const validationErrors = validateTimeSlots(newTimeSlots);
            if (validationErrors.length > 0) {
                toast.error(`${t("pleaseFixFollowingIssues")}:\n${validationErrors.join('\n')}`);
                return;
            }

            const formattedSlots = formatTimeSlotsForAPI(newTimeSlots);

            // Wait for the save operation to complete and check its result
            const success = await onSave?.(formattedSlots, date);

            // Only close the modal and reset state if save was successful
            if (success) {
                setShowUndo(false);
                onClose?.();
            }
        } catch (error) {
            // Don't close modal on error, just show the error message
            console.error("Error saving time slots:", error);
            // Don't show duplicate toast as it's already handled in parent component
        }
    };

    const handleCancel = () => {
        setNewTimeSlots(getFilteredTimeSlotsForDate());
        onCancel?.();
        onClose?.();
    };


    const isMobile = useMediaQuery("(max-width: 768px)");

    // Mobile content component
    const MobileContent = () => (
        <div className="flex flex-col h-full">
            {/* Header */}
            <div className="flex items-center justify-between w-full px-4 py-4 border-b newBorderColor shrink-0">
                <div className="flex flex-col md:flex-row items-start md:items-center gap-2">
                    <span className="text-base font-bold brandColor">{t("addATimeSlot")}</span>
                    <span className="text-base font-bold brandColor md:block hidden">-</span>
                    <span className="text-base font-bold brandColor">{getFormattedDate(date?.toString(), t)}</span>
                </div>
                <div
                    className='flex items-center justify-center w-10 h-10 primaryBackgroundBg rounded-xl hover:cursor-pointer'
                    onClick={onClose}
                    aria-label='Close'
                >
                    <IoMdClose size={16} className='text-black' aria-hidden='true' />
                </div>
            </div>

            {/* Content */}
            <div className="flex-1 overflow-y-auto">
                <div className="p-4">
                    {newTimeSlots.length === 0 ? (
                        // Empty State
                        <div className="flex flex-col items-center justify-center gap-6 p-4 primaryBackgroundBg rounded-2xl newBorder">
                            <p className="text-sm leadColor text-center">
                                {t("noTimeSlotsAvailable")}
                            </p>
                            <button
                                onClick={handleAddTimeSlot}
                                className="flex items-center gap-1 px-3 py-2 primaryBg text-white rounded-lg hover:opacity-90 transition-opacity"
                                aria-label="Add time slot"
                            >
                                <MdAddCircleOutline className="w-5 h-5" />
                                <span className="text-base font-medium">{t("addSlot")}</span>
                            </button>
                        </div>
                    ) : (
                        <div className="flex flex-col gap-4">
                            {/* Slots */}
                            <div className="flex flex-col gap-4">
                                {newTimeSlots.map((slot, index) => (
                                    <div
                                        key={slot.id}
                                        className="flex flex-col items-start gap-2"
                                    >
                                        {/* Label */}
                                        <div className="flex items-center gap-1">
                                            {index === 0 && (
                                                <>
                                                    <IoMdTime className="w-5 h-5 brandColor" />
                                                    <span className="text-sm font-bold brandColor">{t("time")}:</span>
                                                </>
                                            )}
                                        </div>

                                        {/* Time Slots Container */}
                                        <div className="grid grid-cols-2 gap-2 w-full">
                                            {/* Start Time */}
                                            <div className="flex flex-col gap-1">
                                                <span className="text-xs font-medium leadColor">{t("openHour")}</span>
                                                {isNewSlot(slot) ? (
                                                    <TimePicker
                                                        value={formatTimeToAMPM(slot.start_time || slot.start)}
                                                        onChange={(newTime) =>
                                                            handleNewSlotTimeChange(index, "start", newTime)
                                                        }
                                                        className="h-10 w-full [&>button]:h-full [&>button]:primaryBackgroundBg [&>button]:newBorderColor [&>button]:border-[1.5px] [&>button]:rounded-lg"
                                                        placeholder="Select start time"
                                                        format12Hour
                                                        aria-label="Select start time"
                                                        disabledTimes={(time) => isTimeDisabled(time, index)}
                                                        minTime="00:00"
                                                        maxTime="23:59"
                                                    />
                                                ) : (
                                                    <div className="h-10 w-full primaryBackgroundBg newBorderColor border-[1.5px] rounded-lg px-3 text-xs flex items-center bg-gray-50">
                                                        <span className="text-gray-700 font-medium">
                                                            {formatTimeToAMPM(slot.start_time || slot.start)}
                                                        </span>
                                                    </div>
                                                )}
                                            </div>

                                            {/* End Time */}
                                            <div className="flex flex-col gap-1">
                                                <span className="text-xs font-medium leadColor">{t("closedHour")}</span>
                                                {isNewSlot(slot) ? (
                                                    <TimePicker
                                                        value={formatTimeToAMPM(slot.end_time || slot.end)}
                                                        onChange={(newTime) =>
                                                            handleNewSlotTimeChange(index, "end", newTime)
                                                        }
                                                        className="h-10 w-full [&>button]:h-full [&>button]:primaryBackgroundBg [&>button]:newBorderColor [&>button]:border-[1.5px] [&>button]:rounded-lg"
                                                        placeholder="Select end time"
                                                        format12Hour
                                                        aria-label="Select end time"
                                                        disabledTimes={(time) => isTimeDisabled(time, index)}
                                                        minTime={slot.start_time || slot.start}
                                                        maxTime="23:59"
                                                    />
                                                ) : (
                                                    <div className="h-10 w-full primaryBackgroundBg newBorderColor border-[1.5px] rounded-lg px-3 text-xs flex items-center bg-gray-50">
                                                        <span className="text-gray-700 font-medium">
                                                            {formatTimeToAMPM(slot.end_time || slot.end)}
                                                        </span>
                                                    </div>
                                                )}
                                            </div>
                                        </div>

                                        {/* Actions */}
                                        <div className="flex justify-end gap-2 w-full">
                                            {showUndo && !slot.id.toString().startsWith("new-") ?
                                                <button>
                                                    <IoMdUndo className="w-5 h-5 primaryColor" />
                                                </button>
                                                :
                                                <button
                                                    onClick={() => handleRemoveSlot(slot.id)}
                                                    className="w-8 h-8 flex items-center justify-center rounded-lg redBgLight12"
                                                    aria-label="Remove time slot"
                                                >
                                                    <FaMinus className="w-3 h-3 redColor" />
                                                </button>}
                                            <button
                                                onClick={handleAddTimeSlot}
                                                className="w-8 h-8 flex items-center justify-center rounded-lg primaryBgLight12"
                                                aria-label="Add time slot"
                                            >
                                                <FaPlus className="w-3 h-3 primaryColor" />
                                            </button>
                                        </div>
                                    </div>
                                ))}
                            </div>
                        </div>
                    )}
                </div>
            </div>

            {/* Footer - Only show when there are time slots */}
            {newTimeSlots.length > 0 && (
                <div className="flex items-center justify-center gap-3 px-4 py-4 border-t newBorderColor mt-auto shrink-0">
                    <button
                        onClick={handleSave}
                        className="flex items-center justify-center gap-1 px-3 py-2 flex-1 primaryBg text-white rounded-lg hover:opacity-90 transition-opacity"
                    >
                        <span className="text-sm font-medium">{t("save")}</span>
                    </button>
                    <button
                        onClick={handleCancel}
                        className="flex items-center justify-center gap-1 px-3 py-2 flex-1 bg-transparent border brandBorder rounded-lg hover:bg-gray-50 transition-colors"
                    >
                        <span className="text-sm font-medium brandColor">{t("cancel")}</span>
                    </button>
                </div>
            )}
        </div>
    );

    // Desktop content component
    const DesktopContent = () => (
        <>
            {/* Modal Header */}
            <DialogHeader className="flex items-center justify-between px-6 py-4 border-b newBorderColor w-full">
                <DialogTitle className="flex items-center justify-between w-full text-xl font-bold brandColor">
                    <div className="flex items-center gap-2">
                        <span>{t("addATimeSlot")}</span>
                        <span>-</span>
                        <span>{getFormattedDate(date?.toString(), t)}</span>
                    </div>
                    <div className='flex items-center justify-center w-12 h-12 primaryBackgroundBg rounded-xl hover:cursor-pointer' onClick={onClose} aria-label='Close'>
                        <IoMdClose size={20} className='text-black' aria-hidden='true' />
                    </div>
                </DialogTitle>
                <DialogDescription className="sr-only">
                    {t("addATimeSlot")} - {getFormattedDate(date?.toString(), t)}
                </DialogDescription>
            </DialogHeader>

            <div className="p-4 overflow-y-scroll max-h-[calc(90vh-200px)]">
                {newTimeSlots.length === 0 ? (
                    // Empty State
                    <div className="flex flex-col items-center justify-center gap-6 p-4 primaryBackgroundBg rounded-2xl newBorder">
                        <p className="text-sm leadColor text-center">
                            {t("noTimeSlotsAvailable")}
                        </p>
                        <button
                            onClick={handleAddTimeSlot}
                            className="flex items-center gap-1 px-3 py-2 primaryBg text-white rounded-lg hover:opacity-90 transition-opacity"
                            aria-label="Add time slot"
                        >
                            <MdAddCircleOutline className="w-5 h-5" />
                            <span className="text-base font-medium">{t("addSlot")}</span>
                        </button>
                    </div>
                ) : (
                    <div className="flex flex-col gap-6">
                        {/* Header Row */}
                        <div className="grid grid-cols-[100px_1fr_1fr_100px] items-center gap-4">
                            <div></div>
                            <h3 className="text-base font-bold leadColor text-center">{t("openHour")}</h3>
                            <h3 className="text-base font-bold leadColor text-center">{t("closedHour")}</h3>
                            <div></div>
                        </div>

                        {/* Slots */}
                        <div className="flex flex-col gap-6">
                            {newTimeSlots.map((slot, index) => (
                                <div
                                    key={slot.id}
                                    className="grid grid-cols-[100px_1fr_1fr_100px] items-center gap-4"
                                >
                                    {/* Label */}
                                    <div className="flex items-center gap-1">
                                        {index === 0 && (
                                            <>
                                                <IoMdTime className="w-6 h-6 brandColor" />
                                                <span className="text-base font-bold brandColor">{t("time")}:</span>
                                            </>
                                        )}
                                    </div>

                                    {/* Start Time */}
                                    <div>
                                        {isNewSlot(slot) ? (
                                            <TimePicker
                                                value={formatTimeToAMPM(slot.start_time || slot.start)}
                                                onChange={(newTime) =>
                                                    handleNewSlotTimeChange(index, "start", newTime)
                                                }
                                                className="h-12 w-full [&>button]:h-full [&>button]:primaryBackgroundBg [&>button]:newBorderColor [&>button]:border-[1.5px] [&>button]:rounded-lg"
                                                placeholder="Select start time"
                                                format12Hour
                                                aria-label="Select start time"
                                                disabledTimes={(time) => isTimeDisabled(time, index)}
                                                minTime="00:00"
                                                maxTime="23:59"
                                            />
                                        ) : (
                                            <div className="h-12 w-full primaryBackgroundBg newBorderColor border-[1.5px] rounded-lg px-4 text-sm flex items-center bg-gray-50">
                                                <span className="text-gray-700 font-medium">
                                                    {formatTimeToAMPM(slot.start_time || slot.start)}
                                                </span>
                                            </div>
                                        )}
                                    </div>

                                    {/* End Time */}
                                    <div>
                                        {isNewSlot(slot) ? (
                                            <TimePicker
                                                value={formatTimeToAMPM(slot.end_time || slot.end)}
                                                onChange={(newTime) =>
                                                    handleNewSlotTimeChange(index, "end", newTime)
                                                }
                                                className="h-12 w-full [&>button]:h-full [&>button]:primaryBackgroundBg [&>button]:newBorderColor [&>button]:border-[1.5px] [&>button]:rounded-lg"
                                                placeholder="Select end time"
                                                format12Hour
                                                aria-label="Select end time"
                                                disabledTimes={(time) => isTimeDisabled(time, index)}
                                                minTime={slot.start_time || slot.start}
                                                maxTime="23:59"
                                            />
                                        ) : (
                                            <div className="h-12 w-full primaryBackgroundBg newBorderColor border-[1.5px] rounded-lg px-4 text-sm flex items-center bg-gray-50">
                                                <span className="text-gray-700 font-medium">
                                                    {formatTimeToAMPM(slot.end_time || slot.end)}
                                                </span>
                                            </div>
                                        )}
                                    </div>

                                    {/* Actions */}
                                    <div className="flex justify-end gap-2">
                                        {showUndo && !slot.id.toString().startsWith("new-") ?
                                            <button>
                                                <IoMdUndo className="w-6 h-6 primaryColor" />
                                            </button>
                                            :
                                            <button
                                                onClick={() => handleRemoveSlot(slot.id)}
                                                className="w-9 h-9 flex items-center justify-center rounded-lg redBgLight12"
                                                aria-label="Remove time slot"
                                            >
                                                <FaMinus className="w-3.5 h-3.5 redColor" />
                                            </button>}
                                        <button
                                            onClick={handleAddTimeSlot}
                                            className="w-9 h-9 flex items-center justify-center rounded-lg primaryBgLight12"
                                            aria-label="Add time slot"
                                        >
                                            <FaPlus className="w-3.5 h-3.5 primaryColor" />
                                        </button>
                                    </div>
                                </div>
                            ))}
                        </div>
                    </div>
                )}
            </div>

            {/* Modal Footer - Only show when there are time slots */}
            {newTimeSlots.length > 0 && (
                <div className="flex items-center justify-center gap-4 px-6 py-4 border-t newBorderColor">
                    <button
                        onClick={handleSave}
                        className="flex items-center justify-center gap-1 px-3 py-2 w-[181px] primaryBg text-white rounded-lg hover:opacity-90 transition-opacity"
                    >
                        <span className="text-base font-medium">{t("save")}</span>
                    </button>
                    <button
                        onClick={handleCancel}
                        className="flex items-center justify-center gap-1 px-3 py-2 w-[181px] bg-transparent border brandBorder rounded-lg hover:bg-gray-50 transition-colors"
                    >
                        <span className="text-base font-medium brandColor">{t("cancel")}</span>
                    </button>
                </div>
            )}
        </>
    );

    return isMobile ? (
        <Sheet open={isOpen} onOpenChange={onClose}>
            <SheetContent side="bottom" className="h-[90vh] p-0 gap-0 [&>button]:hidden">
                <MobileContent />
            </SheetContent>
        </Sheet>
    ) : (
        <Dialog open={isOpen} onOpenChange={onClose}>
            <DialogContent className="p-0 gap-0 overflow-visible [&>button]:hidden max-w-xl">
                <DesktopContent />
            </DialogContent>
        </Dialog>
    );
};

export default AddExtraTimeSlotModal;