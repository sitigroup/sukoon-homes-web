import 'package:ebroker/exports/main_export.dart';
import 'package:flutter/material.dart';

class UpdateTile extends StatelessWidget {
  const UpdateTile({
    required this.title,
    required this.newVersion,
    required this.isUpdateAvailable,
    required this.svgImagePath,
    required this.onTap,
    super.key,
  });

  final String title;
  final String newVersion;
  final bool isUpdateAvailable;
  final String svgImagePath;
  final VoidCallback onTap;

  @override
  Widget build(BuildContext context) {
    return Padding(
      padding: const EdgeInsets.symmetric(horizontal: 25),
      child: GestureDetector(
        onTap: () {
          if (isUpdateAvailable) onTap.call();
        },
        child: Row(
          children: [
            Container(
              width: 40.rw(context),
              height: 40.rh(context),
              decoration: BoxDecoration(
                color: context.color.tertiaryColor.withValues(alpha: 0.1),
                borderRadius: BorderRadius.circular(10),
              ),
              child: FittedBox(
                fit: BoxFit.none,
                child: !isUpdateAvailable
                    ? const Icon(Icons.done)
                    : CustomImage(
                        imageUrl: svgImagePath,
                        color: context.color.tertiaryColor,
                      ),
              ),
            ),
            SizedBox(width: 25.rw(context)),
            Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                CustomText(
                  !isUpdateAvailable ? 'uptoDate'.translate(context) : title,
                  fontWeight: FontWeight.w700,
                  color: context.color.textColorDark,
                ),
                if (isUpdateAvailable)
                  CustomText(
                    'v$newVersion',
                    fontWeight: FontWeight.w300,
                    fontStyle: FontStyle.italic,
                    color: context.color.textColorDark,
                    fontSize: context.font.xs,
                  ),
              ],
            ),
            if (isUpdateAvailable) ...[
              const Spacer(),
              Container(
                width: 32.rw(context),
                height: 32.rh(context),
                decoration: BoxDecoration(
                  border: Border.all(
                    color: context.color.borderColor,
                    width: 1.5,
                  ),
                  color: context.color.secondaryColor.withValues(alpha: 0.1),
                  borderRadius: BorderRadius.circular(4),
                ),
                child: FittedBox(
                  fit: BoxFit.none,
                  child: CustomImage(
                    imageUrl: AppIcons.arrowRight,
                    matchTextDirection: true,
                    color: context.color.textColorDark,
                  ),
                ),
              ),
            ],
          ],
        ),
      ),
    );
  }
}
