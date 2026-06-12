import AgentChat from "@/components/agent/AgentChat";

const UserChat = ({ notificationData, setNotification, showHeader, showRoundedBorders }) => {
    return (
        <AgentChat
            notificationData={notificationData}
            setNotification={setNotification}
            routeBase="/user/chat"
            showHeader={showHeader}
            showRoundedBorders={showRoundedBorders}
        />
    );
};

export default UserChat;
