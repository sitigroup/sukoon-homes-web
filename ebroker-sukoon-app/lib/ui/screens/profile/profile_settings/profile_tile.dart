import 'package:ebroker/exports/main_export.dart';
import 'package:flutter/material.dart';

class ProfileTile extends StatelessWidget {
  const ProfileTile({
    required this.svgImagePath,
    required this.title,
    required this.onTap,
    super.key,
    this.isSwitchBox = false,
  });

  final String svgImagePath;
  final String title;
  final VoidCallback onTap;
  final bool isSwitchBox;

  @override
  Widget build(BuildContext context) {
    return GestureDetector(
      onTap: () async {
        final hasInternet = await HelperUtils.checkInternet();
        if (!hasInternet) {
          return HelperUtils.showSnackBarMessage(
            context,
            'noInternet',
            type: MessageType.error,
          );
        }
        onTap.call();
      },
      child: AbsorbPointer(
        absorbing: !isSwitchBox,
        child: Row(
          children: [
            Container(
              padding: const EdgeInsets.all(8),
              decoration: BoxDecoration(
                color: context.color.textColorDark.withValues(alpha: 0.1),
                borderRadius: BorderRadius.circular(4),
              ),
              child: FittedBox(
                fit: BoxFit.none,
                child: isSwitchBox
                    ? Icon(
                        Icons.brightness_6_outlined,
                        size: 24.rh(context),
                        color: context.color.textColorDark,
                      )
                    : CustomImage(
                        imageUrl: svgImagePath,
                        height: 24.rh(context),
                        width: 24.rw(context),
                        color: context.color.textColorDark,
                      ),
              ),
            ),
            SizedBox(width: 8.rw(context)),
            Expanded(
              child: CustomText(
                title,
                fontSize: context.font.md,
                fontWeight: FontWeight.w700,
                color: context.color.textColorDark,
              ),
            ),
            if (isSwitchBox) ...[const _ThemeModeSelector()],
          ],
        ),
      ),
    );
  }
}

class _ThemeModeSelector extends StatefulWidget {
  const _ThemeModeSelector();

  @override
  State<_ThemeModeSelector> createState() => _ThemeModeSelectorState();
}

class _ThemeModeSelectorState extends State<_ThemeModeSelector> {
  Offset _lastPointer = Offset.zero;

  @override
  Widget build(BuildContext context) {
    return BlocBuilder<AppThemeCubit, ThemeMode>(
      builder: (context, mode) {
        final isDark = context.read<AppThemeCubit>().isDarkMode;
        final activeIconColor = context.color.tertiaryColor;
        final inactiveIconColor =
            context.color.textColorDark.withValues(alpha: 0.35);

        return Listener(
          onPointerDown: (e) => _lastPointer = e.position,
          child: Row(
            mainAxisSize: MainAxisSize.min,
            children: [
              Icon(
                Icons.light_mode,
                size: 22,
                color: isDark ? inactiveIconColor : activeIconColor,
              ),
              const SizedBox(width: 4),
              Switch(
                thumbColor: const WidgetStatePropertyAll(Colors.white),
                trackOutlineColor: const WidgetStatePropertyAll(
                  Colors.transparent,
                ),
                inactiveThumbColor: Colors.white,
                inactiveTrackColor: Colors.grey.shade400,
                activeTrackColor: context.color.tertiaryColor,
                value: isDark,
                onChanged: (_) async {
                  final newTheme = isDark ? ThemeMode.light : ThemeMode.dark;
                  final cubit = context.read<AppThemeCubit>();
                  await ThemeSwitcher.of(context).reveal(
                    _lastPointer,
                    () => cubit.changeTheme(newTheme),
                  );
                },
              ),
              const SizedBox(width: 4),
              Icon(
                Icons.dark_mode,
                size: 22,
                color: isDark ? activeIconColor : inactiveIconColor,
              ),
            ],
          ),
        );
      },
    );
  }
}
