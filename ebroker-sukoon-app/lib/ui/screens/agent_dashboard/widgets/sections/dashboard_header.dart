import 'package:ebroker/exports/main_export.dart';

class DashboardHeader extends StatelessWidget {
  const DashboardHeader({super.key});

  @override
  Widget build(BuildContext context) {
    final userName = context.watch<UserDetailsCubit>().state.user?.name ?? '';

    return Padding(
      padding: EdgeInsets.fromLTRB(
        16.rw(context),
        12.rh(context),
        16.rw(context),
        32.rh(context),
      ),
      child: Row(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                CustomText(
                  'hello'.translate(context),
                  fontSize: context.font.md,
                  fontWeight: FontWeight.w500,
                  color: context.color.textColorDark,
                ),
                SizedBox(height: 4.rh(context)),
                CustomText(
                  userName,
                  fontSize: 24,
                  fontWeight: FontWeight.w700,
                  color: context.color.tertiaryColor,
                ),
              ],
            ),
          ),
          GestureDetector(
            onTap: () => Navigator.pushNamed(context, Routes.notificationPage),
            child: Container(
              width: 40.rw(context),
              height: 40.rh(context),
              decoration: BoxDecoration(
                color: context.color.secondaryColor,
                borderRadius: BorderRadius.circular(6.rw(context)),
                border: Border.all(color: context.color.borderColor),
              ),
              child: Center(
                child: CustomImage(
                  imageUrl: AppIcons.notification,
                  width: 20.rw(context),
                  height: 20.rh(context),
                  color: context.color.textColorDark,
                ),
              ),
            ),
          ),
        ],
      ),
    );
  }
}
