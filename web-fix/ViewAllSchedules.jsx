import { getAgentTimeScheduleApi } from "@/api/apiRoutes"
import { useTranslation } from "@/components/context/TranslationContext"
import { Dialog, DialogContent, DialogHeader, DialogTitle, DialogDescription } from "@/components/ui/dialog"
import { Sheet, SheetContent, SheetHeader, SheetTitle } from "@/components/ui/sheet"
import { formatTimeToAMPM, getDayOfWeekFromDate, getExtraSlotsForDate, getTimeSchedulesForDate } from "@/utils/appointmentHelper"
import { getFormattedDate } from "@/utils/helperFunction"
import { useMediaQuery } from "@/hooks/use-media-query"
import { useEffect, useState } from "react"
import { FaRegCalendarAlt } from 'react-icons/fa'
import { IoMdClose, IoMdTime } from 'react-icons/io'

const ViewAllSchedules = ({ isOpen, onClose, viewAllDate, showExtraTimeSlots = false, showDefaultTimeSchedules = true }) => {
    const t = useTranslation()
    const [timeSlots, setTimeSlots] = useState([]);
    // Build date at local noon to avoid UTC timezone shifts causing date -1
    const date = viewAllDate ? new Date(viewAllDate.year, viewAllDate.month - 1, viewAllDate.date, 12, 0, 0) : new Date();
    const [extraTimeSlots, setExtraTimeSlots] = useState([]);


    useEffect(() => {
        if (isOpen === true && viewAllDate) {
            fetchAgentTimeSchedules();
        }
    }, [isOpen, viewAllDate])

    const fetchAgentTimeSchedules = async () => {
        try {
            const res = await getAgentTimeScheduleApi();
            if (res?.error === false && res?.data) {
                // Recompute date at local noon on fetch to be safe
                const d = viewAllDate ? new Date(viewAllDate.year, viewAllDate.month - 1, viewAllDate.date, 12, 0, 0) : new Date();
                setTimeSlots(getTimeSchedulesForDate(d, d.getDay(), res?.data?.time_schedules));
                setExtraTimeSlots(getExtraSlotsForDate(d, d.getDay(), res?.data?.extra_slots));
            }
        } catch (error) {
            console.error("Error fetching agent time schedules:", error);
        }
    }

    const isMobile = useMediaQuery("(max-width: 768px)")

    const ScheduleContent = () => (
        <>
            <div className={`flex flex-row items-center justify-between brandBg ${isMobile ? "rounded-t-none" : "rounded-t-2xl"} py-4 px-6`}>
                <div className="flex items-center">
                    <div className="w-12 h-12 bg-gray-100 rounded-full flex items-center justify-center">
                        <FaRegCalendarAlt className='text-black' />
                    </div>
                    <div className="ml-2">
                        <div className="text-base text-start text-white font-bold capitalize">{getDayOfWeekFromDate(date)}</div>
                        <div className="text-sm text-white/70 font-medium">{getFormattedDate(date, t)}</div>
                    </div>
                </div>
                <div className='flex items-center justify-center w-12 h-12 bg-white/10 rounded-xl hover:cursor-pointer' onClick={onClose} aria-label='Close'>
                    <IoMdClose size={20} className='text-white' aria-hidden='true' />
                </div>
                <span className="sr-only">
                    {t("viewAllSchedulesFor")} {getDayOfWeekFromDate(date)}
                </span>
            </div>

            <div className="flex-1 overflow-y-auto">
                {showDefaultTimeSchedules && timeSlots?.length > 0 && (
                    <div className="p-4">
                        <div className="border-l-4 primaryBorderColor pl-2 mb-4">
                            <h2 className="text-base font-bold capitalize">{getDayOfWeekFromDate(date)} {t("schedule")}</h2>
                        </div>

                        <div className="flex flex-wrap gap-4 py-2">
                            {timeSlots.map((slot) => (
                                <button
                                    key={slot?.id}
                                    className="flex items-center gap-2 rounded-md newBorder p-2 cursor-pointer hover:bg-gray-50 w-[calc(50%-8px)] text-left"
                                    aria-label={`Select time slot ${slot?.start_time} to ${slot?.end_time}`}
                                >
                                    <IoMdTime size={20} className='leadColor' />
                                    <span className="font-medium text-sm brandColor">{formatTimeToAMPM(slot?.start_time)} - {formatTimeToAMPM(slot?.end_time)}</span>
                                </button>
                            ))}
                        </div>
                    </div>
                )}
                {!showExtraTimeSlots && timeSlots?.length == 0 && extraTimeSlots?.length == 0 && <hr className="border-t newBorderColor my-2" />}
                {showExtraTimeSlots && extraTimeSlots?.length > 0 && (
                    <div className="p-4">
                        <div className="border-l-4 primaryBorderColor pl-2 mb-4">
                            <h2 className="text-base font-bold">{t("dateScheduleForTimeSlots")}</h2>
                        </div>

                        <div className="flex flex-wrap gap-4 py-2">
                            {extraTimeSlots.map((slot) => (
                                <button
                                    key={slot?.id}
                                    className="flex items-center gap-2 rounded-md newBorder p-2 cursor-pointer hover:bg-gray-50 w-[calc(50%-8px)] text-left"
                                    aria-label={`Select time slot ${slot?.start_time} to ${slot?.end_time}`}
                                >
                                    <IoMdTime size={20} className="leadColor" />
                                    <span className="font-medium text-sm brandColor">{formatTimeToAMPM(slot?.start_time)} - {formatTimeToAMPM(slot?.end_time)}</span>
                                </button>
                            ))}
                        </div>
                    </div>
                )}
            </div>
        </>
    )

    if (isMobile) {
        return (
            <Sheet open={isOpen} onOpenChange={onClose}>
            <SheetContent side="bottom" className="h-[90vh] p-0 border-none [&>button]:hidden">
                <div className="flex flex-col h-full">
                    <ScheduleContent />
                </div>
            </SheetContent>
            </Sheet>
        )
    }

    return (
        <Dialog open={isOpen} onOpenChange={onClose}>
            <DialogContent className="sm:max-w-lg rounded-2xl [&>button]:hidden p-0 border-none max-h-[90vh]">
                <DialogTitle className="sr-only">
                    {t("viewAllSchedulesFor")} {getDayOfWeekFromDate(date)}
                </DialogTitle>
                <DialogDescription className="sr-only">
                    {getFormattedDate(date, t)}
                </DialogDescription>
                <div className="flex flex-col h-full">
                    <ScheduleContent />
                </div>
            </DialogContent>
        </Dialog>
    )
}

export default ViewAllSchedules
    