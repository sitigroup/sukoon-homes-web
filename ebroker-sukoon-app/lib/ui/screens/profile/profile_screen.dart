import 'package:ebroker/data/cubits/agents/agent_profile_cubit.dart';
import 'package:ebroker/data/cubits/auth/get_user_data_cubit.dart';
import 'package:ebroker/data/model/system_settings_model.dart';
import 'package:ebroker/exports/main_export.dart';
import 'package:ebroker/ui/screens/home/widgets/custom_refresh_indicator.dart';
import 'package:ebroker/ui/screens/profile/profile_settings/profile_header.dart';
import 'package:ebroker/ui/screens/profile/widgets/profile_body.dart';
import 'package:flutter/material.dart';

/// Role display is read from the enclosing [RoleScope] — the user scaffold
/// provides [ActiveRole.user], the agent dashboard provides [ActiveRole.agent].
/// Each instance keeps its parent's role during role-switch animations so the
/// outgoing tree preserves its look while the incoming tree shows the new role.
class ProfileScreen extends StatefulWidget {
  const ProfileScreen({super.key});

  @override
  State<ProfileScreen> createState() => _ProfileScreenState();
}

class _ProfileScreenState extends State<ProfileScreen>
    with AutomaticKeepAliveClientMixin<ProfileScreen> {
  bool isGuest = false;

  @override
  void initState() {
    super.initState();
    final settings = context.read<FetchSystemSettingsCubit>();
    isGuest = GuestChecker.value;
    GuestChecker.listen().addListener(_onGuestChanged);
    if (!const bool.fromEnvironment('force-disable-demo-mode')) {
      Constant.isDemoModeOn =
          settings.getSetting(SystemSetting.demoMode) as bool? ?? false;
    }
    unawaited(
      Future.microtask(() async {
        if (!GuestChecker.value &&
            mounted &&
            context.read<UserDetailsCubit>().state.user == null) {
          await context.read<GetUserDataCubit>().getUserData(context);
        }
      }),
    );
  }

  void _onGuestChanged() {
    if (mounted) setState(() => isGuest = GuestChecker.value);
  }

  @override
  void dispose() {
    GuestChecker.listen().removeListener(_onGuestChanged);
    super.dispose();
  }

  @override
  bool get wantKeepAlive => true;

  @override
  Widget build(BuildContext context) {
    super.build(context);
    final role = RoleScope.of(context);
    final isAgent = role == ActiveRole.agent;
    if (isAgent) {
      final agentCubit = context.read<AgentProfileCubit>();
      if (agentCubit.state is! AgentProfileSuccess) {
        unawaited(agentCubit.fetchAgentProfile());
      }
    }
    final statusBarIconBrightness =
        context.color.brightness == Brightness.dark && isAgent
        ? Brightness.dark
        : Brightness.light;
    return AnnotatedRegion(
      value: SystemUiOverlayStyle(
        statusBarColor: Colors.transparent,
        statusBarIconBrightness: statusBarIconBrightness,
        statusBarBrightness: context.color.brightness,
        systemStatusBarContrastEnforced: false,
        systemNavigationBarContrastEnforced: false,
        systemNavigationBarColor: context.color.secondaryColor,
        systemNavigationBarIconBrightness: context.color.brightness == .light
            ? .dark
            : .light,
      ),
      child: Scaffold(
        backgroundColor: context.color.primaryColor,
        body: CustomRefreshIndicator(
          onRefresh: () async {
            await context.read<FetchSystemSettingsCubit>().fetchSettings(
              isAnonymous: GuestChecker.value,
            );
            await context.read<GetApiKeysCubit>().fetch();
            if (!GuestChecker.value) {
              await context.read<GetUserDataCubit>().getUserData(context);
              if (isAgent) {
                await context.read<AgentProfileCubit>().fetchAgentProfile();
              }
            }
          },
          child: Column(
            children: [
              Container(
                padding: .only(top: 48.rh(context)),
                margin: .only(bottom: 54.rh(context)),
                color: isAgent
                    ? context.color.textColorDark
                    : context.color.tertiaryColor,
                child: Transform.translate(
                  offset: Offset(0, 42.rh(context)),
                  child: ProfileHeader(isGuest: isGuest),
                ),
              ),
              Expanded(
                child: Padding(
                  padding: EdgeInsets.symmetric(horizontal: 16.rw(context)),
                  child: ProfileBody(isGuest: isGuest),
                ),
              ),
            ],
          ),
        ),
      ),
    );
  }
}
