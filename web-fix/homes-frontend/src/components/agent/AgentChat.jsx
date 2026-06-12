import { useState, useEffect, useRef } from "react";
import dynamic from "next/dynamic";
import { useRouter } from "next/router";
import { Button } from "@/components/ui/button";
import { BsMic, BsSend, BsPaperclip } from "react-icons/bs";
import { useDispatch, useSelector } from "react-redux";
import {
  blockUserApi,
  deleteChatApi,
  getChatListsApi,
  getChatMessagesApi,
  sendMessageApi,
  unblockUserApi,
} from "@/api/apiRoutes";
import ChatList from "../chat/ChatList";
import ChatHeader from "../chat/ChatHeader";
import ChatMessage from "../chat/ChatMessage";
import { useTranslation } from "../context/TranslationContext";
import toast from "react-hot-toast";
import Image from "next/image";
import { IoIosCloseCircleOutline } from "react-icons/io";
import NoChatFoundImg from "@/assets/no_chat_found.svg";
import { setCacheChat } from "@/redux/slices/cacheSlice";
import Swal from "sweetalert2";
import { useIsMobile } from "@/hooks/use-mobile";
import AudioPlayer from "../chat/AudioPlayer";
import {
  UserChatSkeleton,
  ChatHeaderSkeleton,
  ChatMessageSkeleton,
  ChatInputSkeleton
} from "@/components/skeletons";
import AgentBlockModal from "./AgentBlockModal";

const TenantApplicantReliabilityPanel = dynamic(
  () => import("@/plugins/trust-verification/ui/TenantApplicantReliabilityPanel"),
  { ssr: false, loading: () => null },
);

const AgentChat = ({
  notificationData,
  setNotification,
  routeBase = "/agent/chat",
  showRoundedBorders = false,
  showHeader = true,
}) => {
  const router = useRouter();
  const searchParams = router?.query;
  const { lang } = router?.query;
  const t = useTranslation();
  const isMobile = useIsMobile();
  const dispatch = useDispatch();
  const userData = useSelector((state) => state.User?.data);
  const cacheChat = useSelector((state) => state.cacheData?.cacheChat);
  const language = useSelector((state) => state.LanguageSettings?.active_language);
  const [activeChat, setActiveChat] = useState(null);
  const [chatList, setChatList] = useState([]);
  const [messages, setMessages] = useState([]);
  const [newMessage, setNewMessage] = useState("");
  const [loading, setLoading] = useState(true);
  const [hasMore, setHasMore] = useState(true);
  const [isLoading, setIsLoading] = useState(false);
  const [selectedFile, setSelectedFile] = useState(null);
  const [page, setPage] = useState(0);
  const [recording, setRecording] = useState(false);
  const mediaRecorderRef = useRef(null);
  const audioChunksRef = useRef([]);
  const [audioBlob, setAudioBlob] = useState(null);
  const [showChat, setShowChat] = useState(false);
  const [isSending, setIsSending] = useState(false);
  const [showBlockModal, setShowBlockModal] = useState(false);
  const [blockReason, setBlockReason] = useState("");
  const perPage = 10;

  const getChatId = (chat) => {
    if (!chat) {
      return "";
    }

    const propertyId = chat?.property_id ?? "";
    const userId = chat?.user_id ?? chat?.sender_id ?? "";
    return `${String(propertyId)}-${String(userId)}`;
  };

  const isSameChat = (chat, targetChat) =>
    Boolean(getChatId(chat)) && getChatId(chat) === getChatId(targetChat);

  const dedupeChatList = (chats = []) => {
    const chatMap = new Map();

    chats.forEach((chat) => {
      const chatId = getChatId(chat);
      if (!chatId) {
        return;
      }

      const existingChat = chatMap.get(chatId);
      chatMap.set(chatId, existingChat ? { ...existingChat, ...chat } : chat);
    });

    return Array.from(chatMap.values());
  };

  const buildChatUrl = (propertyId, userId) => {
    const query = new URLSearchParams();

    if (propertyId !== undefined && propertyId !== null) {
      query.set("propertyId", String(propertyId));
    }

    if (userId !== undefined && userId !== null) {
      query.set("userId", String(userId));
    }

    if (lang) {
      query.set("lang", String(lang));
    }

    const queryString = query.toString();
    return queryString ? `${routeBase}?${queryString}` : routeBase;
  };

  const parseId = (value) => {
    const parsed = Number.parseInt(value, 10);
    return Number.isNaN(parsed) ? null : parsed;
  };

  const getNotificationChatData = (data) => {
    const senderId = parseId(data?.sender_id);
    const receiverId = parseId(data?.receiver_id);
    const propertyId = parseId(data?.property_id);
    const messageId = parseId(data?.message_id);
    const currentUserId = parseId(userData?.id);

    const participantId =
      senderId === currentUserId ? receiverId ?? senderId : senderId ?? receiverId;

    return {
      senderId,
      receiverId,
      propertyId,
      messageId,
      participantId,
    };
  };

  const buildNotificationMessage = (data) => {
    const chatData = getNotificationChatData(data);

    return {
      id: String(chatData.messageId ?? data?.messageId ?? data?.id ?? ""),
      sender_id: chatData.senderId,
      user_id: chatData.participantId,
      receiver_id: chatData.receiverId,
      property_id: chatData.propertyId,
      title: data?.title,
      title_image: data?.property_title_image,
      chat_message_type: data?.chat_message_type,
      profile: data?.sender_profile,
      user_profile: data?.user_profile,
      message: data?.message,
      file: data?.file,
      unread_count: 1,
      audio: data?.audio,
      created_at: data?.created_at,
      name: data?.sender_name,
      date: data?.created_at,
    };
  };

  const isNotificationForActiveChat = (data) => {
    if (!activeChat) {
      return false;
    }

    const chatData = getNotificationChatData(data);
    return (
      activeChat.property_id === chatData.propertyId &&
      activeChat.user_id === chatData.participantId
    );
  };

  const Spinner = () => {
    return (
      <svg
        className="primaryColor h-4 w-4 animate-spin"
        xmlns="http://www.w3.org/2000/svg"
        fill="none"
        viewBox="0 0 24 24"
      >
        <circle
          className="opacity-25"
          cx="12"
          cy="12"
          r="10"
          stroke="currentColor"
          strokeWidth="4"
        ></circle>
        <path
          className="opacity-75"
          fill="currentColor"
          d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"
        ></path>
      </svg>
    );
  };

  const toggleRecording = async () => {
    if (!recording) {
      // Ask for mic permission and start recording
      // Check if any audio input devices are available
      const devices = await navigator.mediaDevices.enumerateDevices();
      const hasAudioInput = devices.some(
        (device) => device.kind === "audioinput",
      );

      if (!hasAudioInput) {
        Swal.fire({
          title: t("noMicroPhoneFound"),
          text: t("pleaseConnectAMircoPhone"),
          icon: "warning",
          confirmButtonText: t("ok"),
        });
        return;
      }
      try {
        const stream = await navigator.mediaDevices.getUserMedia({
          audio: true,
        });
        const mediaRecorder = new MediaRecorder(stream, {
          mimeType: "audio/mp4",
        });
        mediaRecorderRef.current = mediaRecorder;
        audioChunksRef.current = [];

        mediaRecorder.ondataavailable = (event) => {
          if (event.data.size > 0) {
            audioChunksRef.current.push(event.data);
          }
        };

        mediaRecorder.onstop = () => {
          const blob = new Blob(audioChunksRef.current, {
            type: "audio/mp4",
          });
          setAudioBlob(blob);
        };

        mediaRecorder.start(400); // collect data in chunks every second
        setRecording(true);
      } catch (err) {
        console.error("Microphone access denied or error:", err);
      }
    } else {
      // Stop recording
      mediaRecorderRef.current?.stop();
      setRecording(false);
    }
  };

  // Fetch chat messages
  const fetchChatMessages = async (chatData) => {
    if (!chatData || isLoading) return;
    try {
      setIsLoading(true);

      const res = await getChatMessagesApi({
        user_id: chatData.user_id,
        property_id: chatData.property_id,
        page: 0,
        per_page: perPage,
      });

      if (!res?.error) {
        // Sort messages by creation timestamp (newest at bottom)
        const sortedMessages = res?.data?.data.sort(
          (a, b) => new Date(b.created_at) - new Date(a.created_at),
        );
        setMessages(sortedMessages);
        setPage(0);
        setHasMore(res?.data?.data.length === perPage);
      } else {
        console.error("Error in fetching chat messages", res?.message);
        setMessages([]);
      }
    } catch (error) {
      console.error("Error in fetching chat messages", error);
      setMessages([]);
    } finally {
      setIsLoading(false);
    }
  };

  // Handle Reload Chat
  const handleReloadChat = async (chatData) => {
    if (!chatData || isLoading) return;
    try {
      setIsLoading(true);

      const res = await getChatMessagesApi({
        user_id: chatData.user_id,
        property_id: chatData.property_id,
        page: 0,
        per_page: perPage,
      });

      if (!res?.error) {
        // Sort messages by creation timestamp (newest at bottom)
        const sortedMessages = res?.data?.data.sort(
          (a, b) => new Date(b.created_at) - new Date(a.created_at),
        );
        setMessages(sortedMessages);
        setPage(0);
        setHasMore(res?.data?.data.length === perPage);
      } else {
        console.error("Error in fetching chat messages", res?.message);
        setMessages([]);
      }
    } catch (error) {
      console.error("Error in fetching chat messages", error);
      setMessages([]);
    } finally {
      setIsLoading(false);
    }
  };

  // Fetch chat lists only once on component mount
  const fetchChatLists = async () => {
    try {
      setLoading(true);
      const res = await getChatListsApi();
      if (!res?.error) {

        const fetchedChatList = dedupeChatList(res?.data || []);
        let nextChatList = fetchedChatList;
        if (cacheChat) {
          const existingCacheChat = fetchedChatList.find((chat) =>
            isSameChat(chat, cacheChat),
          );

          if (!existingCacheChat) {
            nextChatList = dedupeChatList([...fetchedChatList, cacheChat]);
          }
        }

        if (nextChatList?.length > 0) {
          setChatList(nextChatList);
        } else {
          setChatList([]);
          setShowChat(true)
        }

        const propertyId = searchParams?.propertyId;
        const userId = searchParams?.userId;
        const routeChat = { property_id: propertyId, user_id: userId };

        const selectedChat = propertyId && userId
          ? nextChatList?.find(
            (chat) => isSameChat(chat, routeChat),
          )
          : nextChatList?.[0];
        if (cacheChat) {
          const resolvedCacheChat = nextChatList.find((chat) =>
            isSameChat(chat, cacheChat),
          ) || cacheChat;
          setActiveChat(resolvedCacheChat);
          router.push(buildChatUrl(resolvedCacheChat?.property_id, resolvedCacheChat?.user_id));
          dispatch(setCacheChat(null));
        } else if (selectedChat) {
          setActiveChat(selectedChat);
        }
      } else {
        console.error("Error in fetching chat lists", res?.message);
        setChatList([]);
      }
    } catch (error) {
      console.error("Error in fetching chat lists", error);
      setChatList([]);
    } finally {
      setLoading(false);
    }
  };
  useEffect(() => {

    fetchChatLists();
    return () => {
      dispatch(setCacheChat(null));
    };
  }, [language]);

  const getSupportedMimeType = () => {
    const possibleMimeTypes = [
      "audio/mp4",
      "audio/mpeg",
      "audio/mp3",
      "audio/m4a",
    ];

    for (const mimeType of possibleMimeTypes) {
      if (MediaRecorder.isTypeSupported(mimeType)) {
        return mimeType;
      }
    }

    // Fallback to default
    return "audio/webm;codecs=opus";
  };
  useEffect(() => {
    if (activeChat) {
      setMessages([]);
      setPage(0);
      setHasMore(true);
      setNewMessage("");
      setSelectedFile(null);
      fetchChatMessages(activeChat);
    }
  }, [activeChat]);

  useEffect(() => {
    if (activeChat) {
      const currentPropertyId = searchParams?.propertyId;
      const currentUserId = searchParams?.userId;
      if (
        currentPropertyId !== String(activeChat.property_id) ||
        currentUserId !== String(activeChat.user_id)
      ) {
        router.push(buildChatUrl(activeChat.property_id, activeChat.user_id));
      }
    }
  }, [activeChat]);

  useEffect(() => {
    if (notificationData?.type === "chat_message_deleted") {
      fetchChatLists();
      setActiveChat(null);
      return;
    }
    if (notificationData) {
      if (
        notificationData?.type === "delete_message" &&
        notificationData?.message_id
      ) {
        setMessages((prev) =>
          prev.filter(
            (msg) => msg.id !== parseInt(notificationData?.message_id),
          ),
        );
        return;
      }
      const newMessage = buildNotificationMessage(notificationData);
      const isActiveChatMessage = isNotificationForActiveChat(notificationData);

      setChatList((prevChatLists) => {
        const existingChat = prevChatLists.find((chat) =>
          isSameChat(chat, newMessage),
        );

        if (existingChat === undefined) {
          return dedupeChatList([...prevChatLists, newMessage]);
        }

        return prevChatLists.map((chat) =>
          isSameChat(chat, newMessage)
            ? {
              ...chat,
              ...newMessage,
              unread_count: isActiveChatMessage
                ? 0
                : (chat.unread_count || 0) + 1,
            }
            : chat,
        );
      });

      if (isActiveChatMessage) {
        setMessages((prevMessages) => {
          if (
            newMessage.id &&
            prevMessages.some((msg) => String(msg.id) === String(newMessage.id))
          ) {
            return prevMessages;
          }

          const updated = [...prevMessages, newMessage];
          return updated.sort(
            (a, b) =>
              new Date(b.created_at).getTime() -
              new Date(a.created_at).getTime(),
          );
        });
      }
    }
  }, [notificationData]);

  const handleLoadMore = async () => {
    // Use ref to prevent multiple simultaneous calls
    if (!activeChat || isLoading || !hasMore) {
      return;
    }

    setIsLoading(true);
    const nextPage = page + 1;

    try {
      const res = await getChatMessagesApi({
        user_id: activeChat?.user_id,
        property_id: activeChat?.property_id,
        page: nextPage,
        per_page: perPage,
      });

      if (!res?.error) {
        if (res?.data?.data?.length > 0) {
          // Sort older messages by creation timestamp
          const olderMessages = res.data.data.sort(
            (a, b) => new Date(b.created_at) - new Date(a.created_at),
          );
          // Update messages state by prepending older messages
          setMessages((prevMessages) => {
            // Create a new array with older messages first
            const uniqueOlderMessages = olderMessages.filter(
              (oldMsg) => !prevMessages.some((msg) => msg.id === oldMsg.id),
            );
            const newMessages = uniqueOlderMessages.sort(
              (a, b) => new Date(b.created_at) - new Date(a.created_at),
            );

            // Prepend unique older messages to existing messages
            return [...prevMessages, ...newMessages];
          });

          setPage(nextPage);
          setHasMore(res?.data?.data?.length < perPage);
        } else {
          setHasMore(false);
        }
      } else {
        console.error("Error in fetching more chat messages", res?.message);
        setHasMore(false);
      }
    } catch (error) {
      console.error("Error in fetching more chat messages", error);
      setHasMore(false);
    } finally {
      setIsLoading(false);
    }
  };

  const handleDeleteChat = async () => {
    Swal.fire({
      title: t("areYouSure"),
      text: t("areYouSureDeleteChat"),
      icon: "warning",
      showCancelButton: true,
      confirmButtonText: t("yesDelete"),
      cancelButtonText: t("noDelete"),
      reverseButtons: true,
      customClass: {
        confirmButton: "Swal-confirm-buttons",
        cancelButton: "Swal-cancel-buttons",
      },
    }).then(async (result) => {
      if (!result.isConfirmed) {
        return;
      }

      try {
        const res = await deleteChatApi({
          sender_id: userData?.id,
          receiver_id: activeChat?.user_id,
          property_id: activeChat?.property_id,
        });

        if (!res?.error) {
          Swal.fire({
            title: t("deleted"),
            text: res.message,
            icon: "success",
            customClass: {
              confirmButton: "Swal-confirm-buttons",
              cancelButton: "Swal-cancel-buttons",
            },
          });

          // Remove the deleted chat from state.
          setChatList((prevList) => {
            const newList = prevList.filter(
              (chat) =>
                !(
                  chat.property_id === activeChat.property_id &&
                  chat.user_id === activeChat.user_id
                ),
            );

            if (newList.length > 0) {
              const nextChat = newList[0];
              setActiveChat(nextChat);
              fetchChatMessages(nextChat);
            } else {
              setActiveChat(null);
              setMessages([]);
            }

            return newList;
          });

          if (chatList.length === 1) {
            router.push("/");
          }
        } else {
          toast.error(t("errorDeletingChat"));
        }
      } catch (error) {
        console.error("Error deleting chat:", error);
        toast.error(t("errorDeletingChat"));
      }
    });
  };

  const handleDeleteMessage = async (message) => {
    try {
      const res = await deleteChatApi({
        message_id: message?.id,
        receiver_id: message?.receiver_id,
      });
      if (!res?.error) {
        setMessages((prevMessages) =>
          prevMessages.filter((msg) => msg.id !== message.id),
        );
      } else {
        console.error("Error deleting message", res?.message);
      }
    } catch (error) {
      console.error("Error deleting message", error);
    }
  };

  const handleSelectChat = (conversation) => {
    dispatch(setCacheChat(null));
    if (activeChat?.property_id === conversation?.property_id && activeChat?.user_id === conversation?.user_id) {
      return;
    }
    setActiveChat(conversation);
    setChatList((prevChatLists) =>
      prevChatLists.map((chat) =>
        chat.property_id === conversation?.property_id && chat.user_id === conversation?.user_id
          ? { ...chat, unread_count: 0 }
          : chat,
      ),
    );
    const propertyId = conversation?.property_id;
    const userId = conversation?.user_id;
    router.push(buildChatUrl(propertyId, userId));
  };

  const activeChatRef = useRef(activeChat);
  const messagesRef = useRef(messages);
  const searchParamsRef = useRef(searchParams);

  useEffect(() => {
    activeChatRef.current = activeChat;
  }, [activeChat]);

  useEffect(() => {
    messagesRef.current = messages;
  }, [messages]);

  useEffect(() => {
    searchParamsRef.current = searchParams;
  }, [searchParams]);

  const handleBgNotification = (data) => {
    const notificationData = data?.data ?? data;
    const messageId = String(
      data?.messageId ??
      notificationData?.message_id ??
      notificationData?.messageId ??
      notificationData?.id ??
      "",
    );

    if (notificationData?.type === "delete_message" && messageId) {
      setMessages((prev) =>
        prev.filter((msg) => String(msg.id) !== messageId),
      );
      return;
    }
    const chatData = buildNotificationMessage(notificationData);

    const currentSearchParams = searchParamsRef.current;
    const currentPropertyId = parseId(currentSearchParams.propertyId || "0");
    const currentUserId = parseId(currentSearchParams.userId || "0");
    const active = activeChatRef.current;

    const isCurrentChatOpen =
      active?.property_id === chatData.property_id &&
      active?.user_id === chatData.user_id &&
      currentPropertyId === chatData.property_id &&
      currentUserId === chatData.user_id;

    setChatList((prev) => {
      const existing = prev.find((chat) => isSameChat(chat, chatData));
      if (existing === undefined) {
        return dedupeChatList([...prev, chatData]);
      }

      return prev.map((chat) =>
        isSameChat(chat, chatData)
          ? {
            ...chat,
            ...chatData,
            unread_count: isCurrentChatOpen
              ? 0
              : (chat.unread_count || 0) + 1,
          }
          : chat,
      );
    });

    if (isCurrentChatOpen) {
      setMessages((prev) => {
        if (chatData.id && prev.some((msg) => String(msg.id) === String(chatData.id))) {
          return prev;
        }

        const updated = [...prev, chatData];

        return updated.sort(
          (a, b) => new Date(b.created_at) - new Date(a.created_at),
        );
      });
    }
  };

  // Handle notification received from service worker as this is the way to listen to notifications in the background
  useEffect(() => {
    const handleServiceWorkerMessage = (event) => {
      if (event.data?.type === "NOTIFICATION_RECEIVED") {
        handleBgNotification(event.data.payload);
      }
    };

    if (navigator?.serviceWorker) {
      navigator.serviceWorker.addEventListener(
        "message",
        handleServiceWorkerMessage,
      );
    }

    return () => {
      if (navigator?.serviceWorker) {
        navigator.serviceWorker.removeEventListener(
          "message",
          handleServiceWorkerMessage,
        );
      }
    };
  }, []);

  const handleSendMessage = async (e) => {
    e.preventDefault();
    const hasText = newMessage?.trim();
    const hasFile = selectedFile;
    const isAudioFile = selectedFile && selectedFile.type.startsWith("audio/");

    let messageType;
    // First check for audio file specifically
    if (isAudioFile && hasText) {
      // Audio file with text
      messageType = "file_and_text";
    } else if (isAudioFile) {
      // Audio file only
      messageType = "audio";
    } else if (hasText && hasFile) {
      // Regular file with text
      messageType = "file_and_text";
    } else if (hasText) {
      // Text only
      messageType = "text";
    } else if (hasFile) {
      // File only (non-audio)
      messageType = "file";
    } else if (audioBlob) {
      // Recorded audio
      messageType = "audio";
    } else {
      toast.error(t("sendMessageError"));
      return;
    }

    if (messageType === "text" && !newMessage.trim()) {
      return toast.error(t("sendMessageError"));
    }

    // Add new message to the conversation
    let newMsg = {
      id: "", // Use unique ID
      sender_id: userData?.id,
      receiver_id: activeChat?.user_id,
      property_id: activeChat?.property_id,
      chat_message_type: messageType,
      message: newMessage,
      file: messageType === "file" || messageType === "file_and_text" ?
        (isAudioFile ? null : selectedFile) : null,
      audio: audioBlob ? audioBlob :
        (isAudioFile ? selectedFile : null),
      created_at: new Date().toISOString(),
    };

    try {
      setIsSending(true);
      const res = await sendMessageApi(newMsg);
      if (!res.error) {
        const messageId = res?.id ?? res?.data?.id;
        const savedMessage = { ...newMsg, id: messageId };
        const newMessages = [...messages, savedMessage];
        const sortedMessages = newMessages.sort(
          (a, b) => new Date(b.created_at) - new Date(a.created_at),
        );
        setMessages(sortedMessages);
        setNewMessage("");
        setSelectedFile(null);
        setAudioBlob(null);
        setRecording(false);
      } else {
        toast.error(res?.message);
      }
    } catch (error) {
      console.error("Error in sending message", error);
    } finally {
      setIsSending(false);
    }
  };

  const handleBlockUser = async (blockData) => {
    try {
      const isAdmin = blockData?.user_id === 0;
      if (blockData) {
        const res = await blockUserApi({
          to_user_id: !isAdmin ? blockData?.user_id : "",
          to_admin: isAdmin ? "1" : "",
          reason: blockReason ? blockReason : ""
        });
        if (!res?.error) {
          toast.success(t(res?.message));
          const blockedUser = chatList.find(
            (chat) => chat.user_id === blockData?.user_id,
          );
          if (blockedUser) {
            setChatList((prevChatList) => {
              return prevChatList.map((chat) =>
                chat.user_id === blockData.user_id
                  ? { ...chat, is_blocked_by_me: true }
                  : chat,
              );
            });
            blockedUser.is_blocked_by_me = true;
            setActiveChat(blockedUser);
          } else {
            console.error("User not found in chat list");
            toast.error(t(res?.message));
          }
        } else {
          console.error("Error in blocking user", res?.message);
          toast.error(t(res?.message));
        }
      }
    } catch (error) {
      console.error("Error in blocking user", error);
      toast.error(t(error?.message) || t("errorBlockingUser"));
    }
  };
  const handleUnblockUser = async (blockData) => {
    try {
      const isAdmin = blockData?.user_id === 0;
      if (blockData) {
        const res = await unblockUserApi({
          to_user_id: !isAdmin ? blockData?.user_id : "",
          to_admin: isAdmin ? "1" : "",
        });
        if (!res?.error) {
          toast.success(t(res?.message));
          const blockedUser = chatList.find(
            (chat) => chat.user_id === blockData?.user_id,
          );
          if (blockedUser) {
            setChatList((prevChatList) => {
              return prevChatList.map((chat) =>
                chat.user_id === blockData.user_id
                  ? { ...chat, is_blocked_by_me: false }
                  : chat,
              );
            });
            blockedUser.is_blocked_by_me = false;
            setActiveChat(blockedUser);
          } else {
            console.error("User not found in chat list");
            toast.error(t(res?.message));
          }
        } else {
          console.error("Error in unblocking user", res?.message);
          toast.error(t(res?.message));
        }
      }
    } catch (error) {
      console.error("Error in unblocking user", error);
      toast.error(t(error?.message) || t("errorUnblockingUser"));
    }
  };

  // Handle going back to chat list
  const handleBackToChatList = () => setShowChat(false);
  const handleSelectChatWrapper = (chat) => {
    handleSelectChat(chat);
    setShowChat(true);
  }

  const viewerCustomerId = userData?.id;
  const applicantCustomerId = activeChat?.user_id;
  const showTenantReliabilityPanel =
    applicantCustomerId &&
    viewerCustomerId &&
    String(viewerCustomerId) !== String(applicantCustomerId);

  return (
    <div className={`flex w-full h-[700px] min-h-[90%] flex-col ${showRoundedBorders ? "rounded-2xl newBorder" : ""}`}>

      <div>
        <h1 className={`secondryTextColor pb-4 pt-2 text-2xl font-semibold ${showRoundedBorders ? "px-4 border-b newBorderColor bg-white rounded-t-xl" : ""}`}>
          {t("messages")}
        </h1>
      </div>

      {loading ? (
        <UserChatSkeleton isMobile={isMobile} />
      ) : (
        <div className={`flex h-full w-full bg-white ${showRoundedBorders ? "rounded-b-xl" : ""}`}>
          {/* Chat List */}
          {chatList?.length > 0 && (!isMobile || (isMobile && !showChat)) && (
            <div className={`w-full overflow-y-auto ltr:border-r rtl:border-l sm:w-1/3`}>
              <ChatList
                conversations={chatList}
                loading={loading}
                activeChat={activeChat}
                handleSelectChat={handleSelectChatWrapper}
              />
            </div>
          )}

          {/* Chat Area */}
          {(!isMobile || (isMobile && showChat)) && (
            <div className="flex w-full flex-col">
              {isLoading && !messages.length ? (
                <>
                  <ChatHeaderSkeleton isMobile={isMobile} />
                  <div className="flex-1 overflow-hidden">
                    <ChatMessageSkeleton count={6} />
                  </div>
                  <ChatInputSkeleton isMobile={isMobile} />
                </>
              ) : activeChat || searchParams?.propertyId ? (
                <>
                  {/* Chat Header */}
                  <ChatHeader
                    isMobile={isMobile}
                    handleBackToChatList={handleBackToChatList}
                    activeChat={activeChat}
                    handleDeleteChat={handleDeleteChat}
                    handleReloadChat={handleReloadChat}
                    // setShowBlockModal={setShowBlockModal}
                    handleBlockUser={() => setShowBlockModal(true)}
                    handleUnblockUser={handleUnblockUser}
                  />

                  {showTenantReliabilityPanel ? (
                    <div className="border-b newBorderColor bg-white px-3 py-2 md:px-4">
                      <TenantApplicantReliabilityPanel customerId={applicantCustomerId} />
                    </div>
                  ) : null}

                  {/* Chat Messages */}
                  <ChatMessage
                    messages={messages}
                    activeChat={activeChat}
                    userData={userData}
                    hasMore={hasMore}
                    loadMore={handleLoadMore}
                    isLoading={isLoading}
                    handleDeleteMessage={handleDeleteMessage}
                  />

                  {/* Message Input */}
                  {activeChat?.is_blocked_by_me ? (
                    <div className="flex items-center bg-white newBorder justify-center border px-3 py-2 text-center">
                      <p className="secondryTextColor">
                        {t("youHaveBlockedUser")}
                      </p>
                    </div>
                  ) : activeChat?.is_blocked_by_user ? (
                    <div className="bg-white  newBorder flex items-center justify-center border-t px-3 py-2 text-center">
                      <p className="secondryTextColor">
                        {t("userHavebeenBlockedYou")}
                      </p>
                    </div>
                  ) : (
                    <form
                      onSubmit={handleSendMessage}
                      className="primaryBackgroundBg m-3 rounded-lg relative flex flex-col gap-3 p-3"
                    >
                      {/* File Preview */}
                      {selectedFile && (
                        <div className="primaryBackgroundBg absolute bottom-[110px] left-3 rounded-lg border p-2 md:bottom-[70px]">
                          {selectedFile?.type?.startsWith("image/") ? (
                            <Image
                              src={URL.createObjectURL(selectedFile)}
                              alt={selectedFile.name}
                              className="h-[150px] w-full rounded-lg"
                              width={0}
                              height={0}
                            />
                          ) : (
                            <span>{selectedFile.name}</span>
                          )}
                          <button
                            type="button"
                            onClick={() => setSelectedFile(null)}
                            className="secondaryTextBg absolute -right-2 -top-2 rounded-full text-white"
                          >
                            <IoIosCloseCircleOutline className="h-5 w-5" />
                          </button>
                        </div>
                      )}

                      {/* Mobile View Input */}
                      <div className="flex w-full flex-row gap-2 md:gap-3 md:hidden">
                        <label
                          htmlFor="fileInputMobile"
                          className="brandBg flex px-2 md:px-3 md:py-2 items-center hover:primaryBg justify-center rounded-md hover:cursor-pointer"
                        >
                          <BsPaperclip className="h-5 w-5 text-white rotate-90" />
                          <input
                            id="fileInputMobile"
                            type="file"
                            onChange={(e) => setSelectedFile(e.target.files[0])}
                            className="hidden"
                          />
                        </label>
                        {audioBlob && (
                          <div className="relative">
                            <div className="w-full rounded-lg border p-2">
                              <AudioPlayer
                                audioSrc={URL.createObjectURL(audioBlob)}
                              />
                            </div>
                            <button
                              type="button"
                              onClick={() => setAudioBlob(null)}
                              className="secondaryTextBg absolute -right-[20px] -top-[20px] -translate-x-1/2 translate-y-1/2 rounded-full text-white"
                            >
                              <IoIosCloseCircleOutline className="h-5 w-5" />
                            </button>
                          </div>
                        )}
                        <input
                          type="text"
                          placeholder={t("typeYourMessage")}
                          value={newMessage}
                          onChange={(e) => setNewMessage(e.target.value)}
                          className="w-full rounded-md md:rounded-full border-none p-1 focus:outline-none focus:ring-0"
                        />
                        <div className="flex justify-center gap-2">

                          <Button
                            type="button"
                            size="icon"
                            onClick={toggleRecording}
                            variant="outline"
                            className={`secondryTextColor hover:brandBg border border-black hover:text-white h-10 ${recording ? "bg-red-600 text-white" : ""}`}
                          >
                            <BsMic className="h-5 w-5" />
                          </Button>
                          <button
                            type="submit"
                            disabled={isSending}
                            className="flex h-10 w-fit items-center gap-1 rounded-lg bg-primary px-3 py-2 text-white hover:primaryBg"
                          >
                            {isSending ? (
                              <Spinner />
                            ) : (
                              <>

                                <BsSend className="h-4 w-4 rtl:-rotate-90" />
                              </>
                            )}
                          </button>
                        </div>
                      </div>

                      {/* Desktop View Input */}
                      <div className="hidden w-full gap-3 md:flex md:flex-row md:items-center">
                        <label
                          htmlFor="fileInputDesktop"
                          className="brandBg flex px-3 py-2 hover:primaryBg items-center justify-center rounded-md hover:cursor-pointer"
                        >
                          <BsPaperclip className="h-5 w-5 text-white rotate-90" />
                          <input
                            id="fileInputDesktop"
                            type="file"
                            onChange={(e) => setSelectedFile(e.target.files[0])}
                            className="hidden"
                          />
                        </label>
                        <input
                          type="text"
                          placeholder={t("typeYourMessage")}
                          value={newMessage}
                          onChange={(e) => setNewMessage(e.target.value)}
                          className="w-full rounded-full border-none p-1.5 focus:outline-none focus:ring-0"
                        />
                        <div className="flex justify-center gap-2">
                          <Button
                            type="button"
                            size="icon"
                            onClick={toggleRecording}
                            variant="outline"
                            className={`secondryTextColor h-10 w-10 ${recording ? "primaryBg text-white" : ""}`}
                          >
                            <BsMic className="h-5 w-5" />
                          </Button>
                          <button
                            type="submit"
                            disabled={isSending}
                            className="flex h-10 w-fit items-center gap-1 rounded-lg bg-primary px-3 py-2 text-white hover:bg-primary/90"
                          >
                            {isSending ? (
                              <Spinner />
                            ) : (
                              <>
                                <span>{t("send")}</span>
                                <BsSend className="h-4 w-4 rtl:-rotate-90" />
                              </>
                            )}
                          </button>
                        </div>
                      </div>
                    </form>
                  )}
                </>
              ) :
                chatList?.length === 0 && !loading && !activeChat && !searchParams?.propertyId ? (
                  <div className="flex flex-col items-center justify-center gap-6 h-full">
                    <Image
                      width={0}
                      height={0}
                      src={NoChatFoundImg}
                      alt="No Chat Found"
                      className="h-[200px] w-[200px] md:h-[300px] md:w-[300px]"
                    />
                    <p className="primaryColor text-2xl font-medium">
                      {t("noChatsAvailable")}
                    </p>
                  </div>
                ) : (
                  <div className="flex flex-1 items-center justify-center text-center">
                    <p className="secondryTextColor">
                      {t("selectConversationToStartChatting")}
                    </p>
                  </div>
                )}
            </div>
          )}
        </div>
      )}
      {/* Block User Modal */}
      {showBlockModal && (
        <AgentBlockModal
          isOpen={showBlockModal}
          onClose={() => setShowBlockModal(false)}
          onConfirm={handleBlockUser}
          reason={blockReason}
          activeChat={activeChat}
          setReason={setBlockReason}
        />
      )}
    </div>
  );
};

export default AgentChat;
