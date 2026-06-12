// Country picker widget to encapsulate country selection functionality
import 'package:ebroker/utils/app_icons.dart';
import 'package:ebroker/utils/custom_image.dart';
import 'package:ebroker/utils/custom_text.dart';
import 'package:ebroker/utils/extensions/extensions.dart';
import 'package:ebroker/utils/responsive_size.dart';
import 'package:flutter/material.dart';

class CountryPickerWidget extends StatelessWidget {
  const CountryPickerWidget({
    required this.flagEmoji,
    required this.onTap,
    required this.countryCode,
    super.key,
  });
  final String? flagEmoji;
  final VoidCallback onTap;
  final String? countryCode;

  @override
  Widget build(BuildContext context) {
    final dialCode = (countryCode ?? '').replaceAll(RegExp(r'[^\d]'), '');
    final flag = (flagEmoji ?? '').trim();

    return Material(
      color: Colors.transparent,
      child: InkWell(
        onTap: onTap,
        borderRadius: BorderRadius.circular(8),
        child: Padding(
          padding: const EdgeInsetsDirectional.only(start: 8, end: 4),
          child: Row(
            mainAxisSize: MainAxisSize.min,
            children: [
              if (flag.isNotEmpty)
                CustomText(
                  flag,
                  fontSize: context.font.xxl,
                )
              else
                Icon(
                  Icons.flag_outlined,
                  size: 22,
                  color: context.color.tertiaryColor,
                ),
              const SizedBox(width: 4),
              CustomImage(
                imageUrl: AppIcons.downArrow,
                height: 16.rh(context),
                width: 16.rw(context),
                color: context.color.tertiaryColor,
              ),
              const SizedBox(width: 6),
              Container(
                height: 24.rh(context),
                width: 1,
                color: Colors.grey.withValues(alpha: 0.5),
              ),
              const SizedBox(width: 6),
              CustomText(
                dialCode.isEmpty ? '--' : '+$dialCode',
                fontSize: context.font.md,
                color: context.color.textColorDark,
              ),
            ],
          ),
        ),
      ),
    );
  }
}

/// Room for flag + dial code in phone fields (avoids clipped prefix icon).
BoxConstraints phoneCountryPrefixConstraints(BuildContext context) {
  return BoxConstraints(
    minWidth: 100.rw(context),
    minHeight: 48.rh(context),
  );
}
