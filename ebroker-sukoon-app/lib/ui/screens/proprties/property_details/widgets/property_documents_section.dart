import 'package:ebroker/exports/main_export.dart';
import 'package:ebroker/ui/screens/proprties/widgets/download_doc.dart';

class PropertyDocumentsSection extends StatelessWidget {
  const PropertyDocumentsSection({
    required this.property,
    super.key,
  });

  final PropertyModel property;

  @override
  Widget build(BuildContext context) {
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
        crossAxisAlignment: .start,
        children: [
          CustomText(
            'Documents'.translate(context),
            fontWeight: .bold,
            fontSize: context.font.md,
          ),
          const SizedBox(height: 8),
          UiUtils.getDivider(context),
          const SizedBox(height: 8),
          ListView.builder(
            shrinkWrap: true,
            physics: const NeverScrollableScrollPhysics(),
            itemCount: property.documents?.length ?? 0,
            itemBuilder: (context, index) {
              final document = property.documents?[index];

              return Column(
                children: [
                  DownloadableDocuments(
                    url: document?.file ?? '',
                  ),
                  if (index != (property.documents?.length ?? 0) - 1) ...[
                    const SizedBox(height: 8),
                    UiUtils.getDivider(context),
                    const SizedBox(height: 8),
                  ],
                ],
              );
            },
          ),
        ],
      ),
    );
  }
}
