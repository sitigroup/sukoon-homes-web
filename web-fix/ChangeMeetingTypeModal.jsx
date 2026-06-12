import {
    Dialog,
    DialogContent,
    DialogTitle,
    DialogDescription,
} from '../ui/dialog';
import {
    Sheet,
    SheetContent
} from '../ui/sheet';
import { useTranslation } from '../context/TranslationContext';
import { useIsMobile } from '@/hooks/use-mobile';
import { IoMdClose } from 'react-icons/io';
import { PiVideoCamera } from 'react-icons/pi';
import { BiPhoneCall, BiUserPlus } from 'react-icons/bi';
import { MdOutlineMeetingRoom } from 'react-icons/md';


// Icon mapping for meeting types
const getIconComponent = (iconName) => {
    const iconMap = {
        'PiVideoCamera': PiVideoCamera,
        'BiPhoneCall': BiPhoneCall,
        'BiUserPlus': BiUserPlus
    };
    return iconMap[iconName] || MdOutlineMeetingRoom;
};



const availableMeetingTypes = [
    {
        id: 'virtual',
        label: 'virtual', // Translation key from en.json
        icon: 'PiVideoCamera'
    },
    {
        id: 'phone',
        label: 'phone', // Translation key from en.json
        icon: 'BiPhoneCall'
    },
    {
        id: 'in_person',
        label: 'in_person', // Translation key from en.json
        icon: 'BiUserPlus'
    }
]
const ChangeMeetingTypeModal = ({
    availableMeetingTypes = "virtual,phone,in_person", // Now receives comma-separated string
    isOpen = false,
    onClose = () => { },
    currentMeetingType = "",
    onChangeMeetingType = () => { },
    handleSubmit = () => { }
}) => {
    const t = useTranslation();
    const isMobile = useIsMobile();

    // Convert comma-separated string to array of meeting type objects
    const getAvailableMeetingTypes = () => {
        const typesArray = availableMeetingTypes.split(',').map(type => type.trim());
        
        return typesArray.map(type => {
            return {
                id: type,
                label: type,
                icon: type === 'virtual' ? 'PiVideoCamera' : 
                      type === 'phone' ? 'BiPhoneCall' : 
                      type === 'in_person' ? 'BiUserPlus' : 'MdOutlineMeetingRoom'
            };
        });
    };

    const meetingTypes = getAvailableMeetingTypes();

    // Common content for both modal and sheet
    const renderContent = () => (
        <>
            {/* Header */}
            <div className='p-6 flex flex-row justify-between items-center'>
                <div className='font-bold text-xl brandColor'>{t("updateMeetingType")}</div>
                <div className='flex items-center justify-center rounded-xl primaryBackgroundBg p-1.5 hover:cursor-pointer'>
                    <IoMdClose className='w-7 h-7' onClick={onClose} />
                </div>
            </div>

            {/* Meeting Types */}
            <div className='p-4 flex flex-col primaryBackgroundBg'>
                <div className='font-semibold text-base rounded-t-2xl border-x-[1.5px] border-t-[1.5px] newBorderColor p-4 brandColor bg-white'>
                    {t("selectType")}
                </div>
                <div className='rounded-b-2xl border-x-[1.5px] border-b-[1.5px] newBorderColor p-4 bg-white flex flex-col gap-4'>
                    {meetingTypes.map((type) => {
                        const IconComponent = getIconComponent(type.icon);
                        return (
                            <div 
                                key={type.id} 
                                className='flex items-center justify-between py-2 px-3 hover:cursor-pointer rounded-lg hover:bg-gray-50 transition-colors border newBorderColor'
                                onClick={() => onChangeMeetingType(type.id)}
                            >
                                <div className="flex items-center">
                                    <IconComponent className="w-5 h-5 leadColor mr-3" />
                                    <span className='leadColor font-medium text-base'>{t(type.label)}</span>
                                </div>
                                <input
                                    type="radio"
                                    name="meetingType"
                                    value={type.id}
                                    checked={currentMeetingType === type.id}
                                    onChange={() => onChangeMeetingType(type.id)}
                                    className="w-5 h-5 cursor-pointer primaryCheckbox"
                                    onClick={(e) => e.stopPropagation()} // Prevent double triggering
                                />
                            </div>
                        )
                    })}
                </div>
            </div>

            {/* Buttons Container */}
            <div className='flex items-center justify-end p-4 rounded'>
                <div className='flex items-center justify-between gap-4'>
                    <button className='font-medium text-base' onClick={onClose}>
                        {t("cancel")}
                    </button>
                    <button className='font-medium text-base rounded-lg text-white brandBg py-3 px-4'
                        onClick={handleSubmit}
                    >
                        {t("update")}
                    </button>
                </div>
            </div>
        </>
    );

    // Render as bottom sheet on mobile, modal on desktop
    return isMobile ? (
        <Sheet open={isOpen} onOpenChange={onClose}>
            <SheetContent 
                side="bottom" 
                className="rounded-t-2xl w-full p-0 gap-0 h-auto max-h-[80vh] !border-0 outline-none"
            >
                {renderContent()}
            </SheetContent>
        </Sheet>
    ) : (
        <Dialog open={isOpen} onOpenChange={onClose}>
            <DialogContent className="max-w-md rounded-2xl p-0 gap-0 border-none overflow-hidden [&>button]:hidden">
                <DialogTitle className="sr-only">{t("updateMeetingType")}</DialogTitle>
                <DialogDescription className="sr-only">{t("selectType")}</DialogDescription>
                {renderContent()}
            </DialogContent>
        </Dialog>
    )
}

export default ChangeMeetingTypeModal