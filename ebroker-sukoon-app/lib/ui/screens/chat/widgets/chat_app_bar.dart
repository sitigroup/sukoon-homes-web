import 'package:ebroker/exports/main_export.dart';
import 'package:ebroker/ui/screens/chat/helpers/chat_helpers.dart';
import 'package:flutter/material.dart';

class ChatAppBar extends StatelessWidget implements PreferredSizeWidget {
  const ChatAppBar({
    required this.profilePicture,
    required this.userName,
    required this.propertyTitle,
    required this.propertyImage,
    required this.isBlockedByMe,
    required this.isBlockedByUser,
    required this.isAgent,
    required this.isAgentVerified,
    required this.isUserVerified,
    required this.isAdmin,
    required this.isNotificationPermissionGranted,
    required this.userId,
    required this.propertyId,
    required this.onMenuSelected,
    required this.isFrom,
    super.key,
  });

  final String profilePicture;
  final String userName;
  final String propertyTitle;
  final String propertyImage;
  final bool isBlockedByMe;
  final bool isBlockedByUser;
  final bool isAgent;
  final bool isAdmin;
  final bool isAgentVerified;
  final bool isUserVerified;
  final bool isNotificationPermissionGranted;
  final String userId;
  final String propertyId;
  final Future<void> Function(String action) onMenuSelected;
  final String isFrom;

  @override
  Size get preferredSize => const Size.fromHeight(kToolbarHeight + 10);

  @override
  Widget build(BuildContext context) {
    final color = context.color;

    return CustomAppBar(
      onTapBackButton: () async {
        final cubit = context.read<LoadChatMessagesCubit>();
        if (!cubit.isClosed) {
          await cubit.load(
            userId: int.tryParse(userId) ?? 0,
            propertyId: int.tryParse(propertyId) ?? 0,
          );
        }
        await context.read<GetChatListCubit>().fetch(forceRefresh: false);
      },
      titleWidget: Row(
        mainAxisSize: .min,
        children: [
          CustomImageHeroAnimation(
            type: CImageType.network,
            image: profilePicture.isEmpty
                ? appSettings.placeholderLogo!
                : profilePicture,
            child: CircleAvatar(
              radius: 18,
              backgroundImage: CachedNetworkImageProvider(profilePicture),
            ),
          ),
          const SizedBox(width: 4),
          Expanded(
            child: Column(
              mainAxisSize: .min,
              crossAxisAlignment: .start,
              mainAxisAlignment: .center,
              children: [
                CustomText(
                  userName,
                  fontSize: context.font.sm,
                  fontWeight: .w500,
                  maxLines: 1,
                  color: context.color.textColorDark,
                ),
                CustomText(
                  propertyTitle,
                  maxLines: 1,
                  fontSize: context.font.xs,
                  color: context.color.textColorDark,
                ),
              ],
            ),
          ),
        ],
      ),
      actions: [
        if (propertyImage.isNotEmpty)
          GestureDetector(
            onTap: () async {
              if (isFrom == 'property') {
                Navigator.pop(context);
              } else {
                await ChatHelpers.onTapPropertyDetails(
                  context,
                  userId,
                  propertyId,
                );
              }
            },
            child: ClipRRect(
              borderRadius: BorderRadius.circular(4),
              child: CustomImage(
                imageUrl: propertyImage,
                height: 46.rh(context),
                width: 46.rw(context),
              ),
            ),
          ),
        PopupMenuButton<String>(
          onSelected: onMenuSelected,
          shape: RoundedRectangleBorder(
            borderRadius: BorderRadius.circular(10),
          ),
          color: color.primaryColor,
          icon: Icon(
            Icons.more_vert,
            color: context.color.tertiaryColor,
          ),
          itemBuilder: (_) => [
            if (isAdmin || isAgent)
              PopupMenuItem(
                value: 'agentDetails',
                child: CustomText('agentDetails'.translate(context)),
              ),

            if (!isBlockedByMe)
              PopupMenuItem(
                value: 'blockUser',
                child: CustomText('blockUser'.translate(context)),
              ),
            if (isBlockedByMe)
              PopupMenuItem(
                value: 'unblockUser',
                child: CustomText('unblockUser'.translate(context)),
              ),
            if (!(isBlockedByUser || isBlockedByMe)) ...[
              PopupMenuItem(
                value: 'refreshChat',
                child: CustomText('refreshChat'.translate(context)),
              ),
              PopupMenuItem(
                value: 'deleteAllMessages',
                child: CustomText('deleteAllMessages'.translate(context)),
              ),
            ],
          ],
        ),
      ],
    );
  }
}
