import ImageWithPlaceholder from "../image-with-placeholder/ImageWithPlaceholder";
import { FiRefreshCw } from "react-icons/fi";
import { BsThreeDotsVertical } from "react-icons/bs";
import {
  DropdownMenu,
  DropdownMenuContent,
  DropdownMenuItem,
  DropdownMenuTrigger,
} from "@/components/ui/dropdown-menu";
import { MdArrowBack, MdDeleteOutline } from "react-icons/md";
import { FaUser, FaUserAltSlash } from "react-icons/fa";
import { useTranslation } from "../context/TranslationContext";
import { isRTL, VerifiedAgentBadge, VerifiedUserBadge } from "@/utils/helperFunction";
import { useRouter } from "next/router";
import CustomLink from "../context/CustomLink";
import { useSelector } from "react-redux";

const ChatHeader = ({
  isMobile,
  handleBackToChatList,
  activeChat,
  handleDeleteChat,
  handleReloadChat,
  handleBlockUser,
  handleUnblockUser,
}) => {
  const t = useTranslation();
  const router = useRouter();
  const webSettings = useSelector((state) => state?.WebSetting?.data);
  const isRtl = isRTL();


  const isUserChat = router?.asPath?.includes("/user/chat");

  return (
    <div className="flex items-center justify-between gap-3 border-b px-2 py-4 md:p-4">
      <div className="flex items-center rtl:gap-2">
        {isMobile && (
          <button
            onClick={handleBackToChatList}
            className="mr-2 font-semibold text-primary"
          >
            <MdArrowBack className="h-5 w-5 rtl:rotate-180" />
          </button>
        )}
        <div className="relative">
          <div className="mr-3 h-7 w-7 overflow-hidden rounded-full bg-gray-200 md:h-12 md:w-12">
            {activeChat?.title_image && (
              <ImageWithPlaceholder
                src={activeChat?.title_image}
                alt={activeChat?.title}
                className="h-7 w-7 md:h-12 md:w-12 aspect-[28/28] md:aspect-[48/48]"
                loading="lazy"
              />
            )}
          </div>
          {activeChat?.isActive && (
            <div className="absolute -bottom-1 right-[10px] h-4 w-4 rounded-full border-2 border-white primaryBg"></div>
          )}
        </div>
        <div>
          <h3 className="break-words font-medium flex items-center gap-1">
            {activeChat?.name}
            {/* {isUserChat && activeChat?.is_user_verified ? (
              <VerifiedUserBadge color={webSettings?.system_color} width={14} height={14} />
            ) : activeChat?.is_agent_verified && (
              <VerifiedAgentBadge color={webSettings?.system_color} width={14} height={14} />
            )} */}
          </h3>
          <div className="flex items-center gap-2">
            {/* <p className="text-sm">{t("property")}:</p>
            <CustomLink href={`/property-details/${activeChat?.property_slug_id}`} className="break-words text-xs text-gray-600 md:text-sm underline primaryColor">
              {activeChat?.translated_title || activeChat?.title}
            </CustomLink> */}
            <p className="text-sm">{activeChat?.translated_title || activeChat?.title}</p>
          </div>
        </div>
      </div>

      <DropdownMenu>
        <DropdownMenuTrigger className="focus:outline-none">
          <BsThreeDotsVertical className="text-xl text-gray-600" />
        </DropdownMenuTrigger>
        <DropdownMenuContent align={isRtl ? "start" : "end"} className="w-48">
          {/* {activeChat?.is_agent ? (
            <DropdownMenuItem
              className="flex items-center gap-2 hover:cursor-pointer"
              onClick={() => router?.push(`/agent-details/${activeChat?.agent_slug_id}`)}
            >
              <FaUser /> {t("agentDetails")}
            </DropdownMenuItem>
          ) : null} */}
          <DropdownMenuItem
            className="flex items-center gap-2 hover:cursor-pointer"
            onClick={() => handleDeleteChat(activeChat)}
          >
            <MdDeleteOutline /> {t("deleteAllMessages")}
          </DropdownMenuItem>
          <DropdownMenuItem
            className="flex items-center gap-2 hover:cursor-pointer"
            onClick={() => handleReloadChat(activeChat)}
          >
            <FiRefreshCw /> {t("refreshMessages")}
          </DropdownMenuItem>
          {!activeChat?.is_blocked_by_me && (
            <DropdownMenuItem
              className="flex items-center gap-2 hover:cursor-pointer"
              onClick={() => handleBlockUser(activeChat)}
            >
              <FaUserAltSlash /> {t("blockUser")}
            </DropdownMenuItem>
          )}
          {activeChat?.is_blocked_by_me && (
            <DropdownMenuItem
              className="flex items-center gap-2 hover:cursor-pointer"
              onClick={() => handleUnblockUser(activeChat)}
            >
              <FaUserAltSlash /> {t("unblockUser")}
            </DropdownMenuItem>
          )}
        </DropdownMenuContent>
      </DropdownMenu>
    </div>
  );
};

export default ChatHeader;
