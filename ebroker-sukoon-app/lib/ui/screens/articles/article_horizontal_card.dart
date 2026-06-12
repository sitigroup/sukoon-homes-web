import 'dart:async';

import 'package:ebroker/app/routes.dart';
import 'package:ebroker/data/cubits/fetch_single_article_cubit.dart';
import 'package:ebroker/data/model/article_model.dart';
import 'package:ebroker/utils/app_icons.dart';
import 'package:ebroker/utils/custom_image.dart';
import 'package:ebroker/utils/custom_text.dart';
import 'package:ebroker/utils/extensions/extensions.dart';
import 'package:ebroker/utils/responsive_size.dart';
import 'package:ebroker/utils/ui_utils.dart';
import 'package:flutter/material.dart';
import 'package:flutter_bloc/flutter_bloc.dart';

class ArticleHorizontalCard extends StatelessWidget {
  const ArticleHorizontalCard({
    required this.article,
    super.key,
  });
  final ArticleModel article;

  @override
  Widget build(BuildContext context) {
    return GestureDetector(
      onTap: () async {
        unawaited(
          context.read<FetchSingleArticleCubit>().fetchArticlesById(
            article.id.toString(),
          ),
        );
        await Navigator.pushNamed(
          context,
          Routes.articleDetailsScreenRoute,
        );
      },
      child: Container(
        padding: const EdgeInsets.all(8),
        height: 98.rh(context),
        decoration: BoxDecoration(
          color: context.color.secondaryColor,
          borderRadius: BorderRadius.circular(8),
          border: Border.all(
            color: context.color.borderColor,
          ),
        ),
        child: Row(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            ClipRRect(
              borderRadius: BorderRadius.circular(4),
              child: CustomImage(
                imageUrl: article.image ?? '',
                width: 140.rw(context),
                height: double.infinity,
              ),
            ),
            const SizedBox(width: 12),
            Expanded(
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  const SizedBox(height: 4),
                  CustomText(
                    (article.translatedTitle ?? article.title ?? '')
                        .firstUpperCase(),
                    maxLines: 2,
                    color: context.color.textColorDark,
                    fontWeight: FontWeight.w500,
                    fontSize: context.font.lg,
                  ),
                  const Spacer(),
                  UiUtils.getDivider(context),
                  const SizedBox(height: 8),
                  Row(
                    children: [
                      Container(
                        height: 16.rh(context),
                        width: 16.rw(context),
                        alignment: Alignment.center,
                        child: CustomImage(
                          imageUrl: AppIcons.calendar,
                          color: context.color.textLightColor,
                        ),
                      ),
                      const SizedBox(width: 4),
                      Expanded(
                        child: CustomText(
                          article.date == null
                              ? article.postedOn == null
                                    ? ''
                                    : article.postedOn.toString()
                              : article.date.toString().formatDate(),
                          color: context.color.textLightColor,
                          fontWeight: FontWeight.w400,
                          fontSize: context.font.xxs,
                          maxLines: 1,
                        ),
                      ),
                      const SizedBox(width: 8),
                      Container(
                        height: 16.rh(context),
                        width: 16.rw(context),
                        alignment: Alignment.center,
                        child: CustomImage(
                          imageUrl: AppIcons.eye,
                          color: context.color.textLightColor,
                        ),
                      ),
                      const SizedBox(width: 4),
                      CustomText(
                        article.viewCount ?? '',
                        color: context.color.textLightColor,
                        fontWeight: FontWeight.w400,
                        maxLines: 1,
                        fontSize: context.font.xxs,
                      ),
                    ],
                  ),
                  const SizedBox(height: 4),
                ],
              ),
            ),
          ],
        ),
      ),
    );
  }
}

String stripHtmlTags(String htmlString) {
  final exp = RegExp('<[^>]*>', multiLine: true);
  final strippedString = htmlString.replaceAll(exp, '');
  return strippedString;
}
