import 'package:ebroker/app/routes.dart';
import 'package:ebroker/data/cubits/agents/fetch_property_cubit.dart';
import 'package:ebroker/data/model/agent_profile_model.dart';
import 'package:ebroker/utils/app_icons.dart';
import 'package:ebroker/utils/custom_image.dart';
import 'package:ebroker/utils/custom_text.dart';
import 'package:ebroker/utils/extensions/extensions.dart';
import 'package:ebroker/utils/responsive_size.dart';
import 'package:flutter/material.dart';
import 'package:flutter_bloc/flutter_bloc.dart';

class AgentProfileWidget extends StatefulWidget {
  const AgentProfileWidget({
    required this.addedBy,
    required this.profileImage,
    required this.name,
    required this.email,
    required this.isAdmin,
    required this.isAgent,
    required this.isAgentVerified,
    required this.isUserVerified,
    required this.propertiesCount,
    required this.projectsCount,
    required this.canScheduleAppointment,
    this.agentProfile,
    this.roleContext,
    super.key,
  });
  final String addedBy;
  final String profileImage;
  final String name;
  final String email;
  final bool isAdmin;
  final bool isAgent;
  final bool isAgentVerified;
  final bool isUserVerified;
  final String propertiesCount;
  final String projectsCount;
  final bool canScheduleAppointment;
  final AgentProfileModel? agentProfile;
  final String? roleContext;

  @override
  State<AgentProfileWidget> createState() => _AgentProfileWidgetState();
}

class _AgentProfileWidgetState extends State<AgentProfileWidget> {
  bool? isAdmin;

  String get _name =>
      (widget.roleContext == 'agent' ? widget.agentProfile?.agentName : null) ??
      widget.name;
  String get _email =>
      (widget.roleContext == 'agent'
          ? widget.agentProfile?.agentEmail
          : null) ??
      widget.email;
  String get _profileImage =>
      (widget.roleContext == 'agent'
          ? widget.agentProfile?.agentProfilePhoto
          : null) ??
      widget.profileImage;

  @override
  void initState() {
    super.initState();
    isAdmin = widget.addedBy == '0';
  }

  @override
  Widget build(BuildContext context) {
    return BlocBuilder<FetchAgentsPropertyCubit, FetchAgentsPropertyState>(
      builder: (context, state) {
        return SizedBox(
          width: double.infinity,
          child: GestureDetector(
            onTap: () async {
              if (widget.isAdmin ||
                  (widget.isAgent && widget.roleContext == 'agent')) {
                try {
                  await Navigator.pushNamed(
                    context,
                    Routes.agentDetailsScreen,
                    arguments: {
                      'agentID': widget.addedBy,
                      'isAdmin': isAdmin,
                    },
                  );
                } on Exception catch (_) {}
              }
            },
            child: Row(
              children: [
                Container(
                  width: 76.rw(context),
                  clipBehavior: .antiAlias,
                  decoration: BoxDecoration(
                    color: Colors.grey.shade200,
                    borderRadius: widget.roleContext == 'user'
                        ? null
                        : BorderRadius.circular(4),
                    shape: widget.roleContext == 'user' ? .circle : .rectangle,
                  ),
                  child: CustomImage(
                    imageUrl: _profileImage,
                  ),
                ),
                const SizedBox(width: 8),
                Expanded(
                  child: Column(
                    crossAxisAlignment: .start,
                    children: [
                      Row(
                        mainAxisSize: .min,
                        children: [
                          CustomText(
                            _name,
                            fontWeight: .w500,
                            fontSize: context.font.sm,
                          ),
                          if (widget.isAgentVerified ||
                              widget.isUserVerified ||
                              widget.isAdmin)
                            Container(
                              margin: EdgeInsetsDirectional.only(
                                start: 4.rw(context),
                              ),
                              alignment: Alignment.center,
                              child: CustomImage(
                                imageUrl:
                                    widget.isAdmin ||
                                        (widget.isAgentVerified &&
                                            widget.roleContext == 'agent')
                                    ? AppIcons.agentBadge
                                    : AppIcons.userBadge,
                                height: 16.rh(context),
                                color: context.color.tertiaryColor,
                              ),
                            ),
                        ],
                      ),
                      CustomText(
                        _email,
                        fontSize: context.font.xs,
                        color: context.color.textColorDark,
                        maxLines: 1,
                        fontWeight: .w400,
                      ),
                      Container(
                        padding: const EdgeInsets.symmetric(
                          horizontal: 8,
                          vertical: 4,
                        ),
                        margin: const EdgeInsets.only(top: 4),
                        decoration: BoxDecoration(
                          borderRadius: BorderRadius.circular(4),
                          color: context.color.textColorDark.withValues(
                            alpha: 0.1,
                          ),
                        ),
                        child: Row(
                          mainAxisAlignment: .center,
                          children: [
                            if (widget.propertiesCount != '0' ||
                                widget.propertiesCount.isNotEmpty) ...[
                              Expanded(
                                child: CustomText(
                                  '${'properties'.translate(context)}: ${widget.propertiesCount}',
                                  fontSize: context.font.xs,
                                  color: context.color.textColorDark,
                                  maxLines: 1,
                                  textAlign: .center,
                                  fontWeight: .w400,
                                ),
                              ),
                              if (widget.projectsCount != '0' ||
                                  widget.projectsCount.isNotEmpty) ...[
                                Container(
                                  height: 12,
                                  width: 1,
                                  color: context.color.textLightColor
                                      .withValues(alpha: 0.5),
                                ),
                              ],
                            ],
                            if (widget.projectsCount != '0' ||
                                widget.projectsCount.isNotEmpty) ...[
                              Expanded(
                                child: CustomText(
                                  '${'projects'.translate(context)}: ${widget.projectsCount}',
                                  fontSize: context.font.xs,
                                  color: context.color.textColorDark,
                                  maxLines: 1,
                                  textAlign: .center,
                                  fontWeight: .w400,
                                ),
                              ),
                            ],
                          ],
                        ),
                      ),
                    ],
                  ),
                ),
              ],
            ),
          ),
        );
      },
    );
  }
}
