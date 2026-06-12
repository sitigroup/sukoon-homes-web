import 'package:ebroker/exports/main_export.dart';
import 'package:flutter/material.dart';

class AllGallaryImages extends StatelessWidget {
  const AllGallaryImages({
    required this.images,
    super.key,
    this.youtubeThumbnail,
  });
  final List<dynamic> images;
  final String? youtubeThumbnail;

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: context.color.backgroundColor,
      appBar: const CustomAppBar(),
      body: GridView.builder(
        itemCount: images.length,
        padding: const EdgeInsets.all(16),
        gridDelegate: const SliverGridDelegateWithFixedCrossAxisCount(
          crossAxisCount: 3,
          crossAxisSpacing: 5,
          mainAxisSpacing: 5,
        ),
        itemBuilder: (context, index) {
          return ClipRRect(
            borderRadius: BorderRadius.circular(18),
            child: GestureDetector(
              onTap: () async {
                if (images[index].isVideo == true) {
                  await CustomVideoPlayer.showFullScreenDialog(
                    context,
                    videoUrl:
                        (images[index] as dynamic).image?.toString() ??
                        (images[index] as dynamic).imageUrl?.toString(),
                  );
                } else {
                  final stringImages = images.map((e) => e.imageUrl).toList();
                  await UiUtils.imageGallaryView(
                    context,
                    images: stringImages,
                    initalIndex: index,
                    then: () {},
                  );
                }
              },
              child: SizedBox(
                width: 76.rw(context),
                height: 76.rh(context),
                child: images[index].isVideo == true
                    ? Stack(
                        fit: .expand,
                        children: [
                          CustomImage(
                            imageUrl: youtubeThumbnail ?? '',
                          ),
                          const Icon(
                            Icons.play_arrow,
                            size: 28,
                          ),
                        ],
                      )
                    : CustomImage(
                        imageUrl:
                            images[index].imageUrl?.toString() ??
                            images[index].name?.toString() ??
                            '',
                      ),
              ),
            ),
          );
        },
      ),
    );
  }
}
