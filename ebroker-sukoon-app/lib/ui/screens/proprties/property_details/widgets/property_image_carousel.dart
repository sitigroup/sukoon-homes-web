import 'package:carousel_slider/carousel_slider.dart';
import 'package:ebroker/exports/main_export.dart';
import 'package:ebroker/ui/screens/widgets/like_button_widget.dart';
import 'package:ebroker/ui/screens/widgets/promoted_widget.dart';
import 'package:flutter/material.dart';

class PropertyImageCarousel extends StatelessWidget {
  const PropertyImageCarousel({
    required this.property,
    required this.currentIndex,
    required this.onPageChanged,
    super.key,
  });

  final PropertyModel property;
  final int currentIndex;
  final ValueChanged<int> onPageChanged;

  @override
  Widget build(BuildContext context) {
    final videoUrl = property.video?.trim();
    final hasVideo = videoUrl != null && videoUrl.isNotEmpty;

    final imageUrls = <String>[
      if (property.titleImage != null && property.titleImage!.isNotEmpty)
        property.titleImage!,
      ...?property.gallery
          ?.where((element) => !(element.isVideo ?? false))
          .map((e) => e.imageUrl),
    ];

    final carouselItems = <Widget>[
      if (hasVideo) _buildVideoThumbnailItem(videoUrl),
      ...imageUrls.asMap().entries.map(
        (entry) => GestureDetector(
          onTap: () async {
            await UiUtils.imageGallaryView(
              context,
              images: imageUrls,
              initalIndex: entry.key,
            );
          },
          child: CustomImage(
            imageUrl: entry.value,
            width: double.infinity,
            height: 218.rs(context),
          ),
        ),
      ),
    ];

    final totalItems = carouselItems.length;

    return SizedBox(
      height: 218.rs(context),
      child: Stack(
        children: [
          if (totalItems > 1)
            Stack(
              children: [
                CarouselSlider(
                  options: CarouselOptions(
                    autoPlay: !hasVideo,
                    viewportFraction: 1,
                    height: 218.rs(context),
                    onPageChanged: (index, reason) {
                      onPageChanged(index);
                    },
                  ),
                  items: carouselItems,
                ),
                Positioned(
                  bottom: 10,
                  left: 0,
                  right: 0,
                  child: Row(
                    mainAxisAlignment: MainAxisAlignment.center,
                    children: List.generate(totalItems, (index) {
                      if (hasVideo && index == 0) {
                        return Container(
                          margin: const EdgeInsets.symmetric(
                            vertical: 8,
                            horizontal: 4,
                          ),
                          child: Icon(
                            Icons.play_arrow_rounded,
                            size: 18,
                            color: Colors.white.withValues(
                              alpha: currentIndex == index ? 0.9 : 0.4,
                            ),
                          ),
                        );
                      }

                      return Container(
                        width: 8,
                        height: 8,
                        margin: const EdgeInsets.symmetric(
                          vertical: 8,
                          horizontal: 4,
                        ),
                        decoration: BoxDecoration(
                          shape: BoxShape.circle,
                          color: Colors.white.withValues(
                            alpha: currentIndex == index ? 0.9 : 0.4,
                          ),
                        ),
                      );
                    }),
                  ),
                ),
              ],
            )
          else if (totalItems == 1)
            carouselItems.first
          else
            GestureDetector(
              onTap: () async {
                final urls = imageUrls.isNotEmpty
                    ? imageUrls
                    : <String>[if (property.titleImage != null) property.titleImage!];
                if (urls.isEmpty) return;
                await UiUtils.imageGallaryView(
                  context,
                  images: urls,
                  initalIndex: 0,
                );
              },
              child: Container(
                alignment: Alignment.center,
                child: CustomImage(
                  imageUrl: property.titleImage ?? '',
                  width: double.infinity,
                  height: 218.rs(context),
                  loadingImageHash: property.lowQualityTitleImage,
                ),
              ),
            ),
          if (property.id != null)
            PositionedDirectional(
              top: 16.rh(context),
              end: 16.rh(context),
              child: LikeButtonWidget(
                isFromDetailsPage: true,
                propertyId: property.id!,
                isFavourite: property.isFavourite == '1',
              ),
            ),
          if (property.allPropData['is_premium'] == true)
            PositionedDirectional(
              start: 16.rh(context),
              top: 16.rh(context),
              child: Container(
                alignment: Alignment.center,
                child: CustomImage(
                  imageUrl: AppIcons.premium,
                  height: 24.rh(context),
                  width: 24.rw(context),
                ),
              ),
            ),
          if (property.promoted == true)
            PositionedDirectional(
              bottom: 16.rh(context),
              start: 16.rh(context),
              child: const PromotedCard(),
            ),
        ],
      ),
    );
  }

  Widget _buildVideoThumbnailItem(String videoUrl) {
    return CustomVideoPlayer(
      videoUrl: videoUrl,
      autoPlay: true,
    );
  }
}
