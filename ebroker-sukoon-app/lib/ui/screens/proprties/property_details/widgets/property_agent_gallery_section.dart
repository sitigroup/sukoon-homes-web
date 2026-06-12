import 'package:ebroker/exports/main_export.dart';
import 'package:ebroker/ui/screens/proprties/widgets/agent_profile.dart';
import 'package:ebroker/ui/screens/proprties/widgets/property_gallery.dart';

class PropertyAgentGallerySection extends StatelessWidget {
  const PropertyAgentGallerySection({
    required this.property,
    required this.gallery,
    this.onScheduleAppointment,
    super.key,
  });

  final PropertyModel property;
  final List<Gallery> gallery;
  final Future<void> Function()? onScheduleAppointment;

  @override
  Widget build(BuildContext context) {
    final isAddedByMe = property.addedBy.toString() == HiveUtils.getUserId();
    final galleryIsEmpty =
        gallery.isEmpty && (property.video?.trim().isEmpty ?? true);

    if (isAddedByMe && galleryIsEmpty) {
      return const SizedBox.shrink();
    }

    return Container(
      padding: const EdgeInsets.all(8),
      decoration: BoxDecoration(
        color: context.color.secondaryColor,
        borderRadius: BorderRadius.circular(4),
        border: Border.all(
          color: context.color.borderColor,
        ),
      ),
      child: Column(
        children: [
          if (!isAddedByMe)
            AgentProfileWidget(
              addedBy: property.addedBy ?? '',
              name: property.customerName ?? '',
              email: property.customerEmail ?? '',
              profileImage: property.customerProfile ?? '',
              isUserVerified: property.isUserVerified ?? false,
              isAgentVerified: property.isAgentVerified ?? false,
              isAgent: property.isAgent ?? false,
              propertiesCount: property.propertiesCount ?? '',
              projectsCount: property.projectsCount ?? '',
              canScheduleAppointment: property.isAppointmentAvailable ?? false,
              isAdmin: property.isAdmin ?? false,
              agentProfile: property.agentProfile,
              roleContext: property.roleContext,
            ),
          if (gallery.isNotEmpty) ...[
            if (!isAddedByMe) ...[
              const SizedBox(height: 8),
              UiUtils.getDivider(context),
              const SizedBox(height: 8),
            ],
            PropertyGallery(
              gallary: gallery,
            ),
          ],
          if (!isAddedByMe &&
              (property.isAppointmentAvailable ?? false) &&
              onScheduleAppointment != null &&
              property.roleContext == 'agent') ...[
            const SizedBox(height: 8),
            UiUtils.getDivider(context),
            const SizedBox(height: 8),
            UiUtils.buildButton(
              context,
              height: 42.rh(context),
              onPressed: () async {
                await onScheduleAppointment!.call();
              },
              buttonTitle: 'scheduleAppointment'.translate(context),
            ),
          ],
        ],
      ),
    );
  }
}
