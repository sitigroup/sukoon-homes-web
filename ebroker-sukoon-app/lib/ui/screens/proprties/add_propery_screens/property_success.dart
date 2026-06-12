import 'dart:async';

import 'package:ebroker/app/routes.dart';
import 'package:ebroker/data/cubits/property/fetch_my_properties_cubit.dart';
import 'package:ebroker/data/model/property_model.dart';
import 'package:ebroker/utils/app_icons.dart';
import 'package:ebroker/utils/custom_image.dart';
import 'package:ebroker/utils/custom_text.dart';
import 'package:ebroker/utils/extensions/extensions.dart';
import 'package:ebroker/utils/helper_utils.dart';
import 'package:ebroker/utils/hive_utils.dart';
import 'package:ebroker/utils/responsive_size.dart';
import 'package:ebroker/utils/ui_utils.dart';
import 'package:flutter/material.dart';
import 'package:flutter_bloc/flutter_bloc.dart';

class PropertyAddSuccess extends StatelessWidget {
  const PropertyAddSuccess({required this.model, super.key});

  final PropertyModel model;

  @override
  Widget build(BuildContext context) {
    return PopScope(
      canPop: false,
      onPopInvokedWithResult: (didPop, _) async {
        if (didPop) return;
        Navigator.popUntil(context, (route) => route.isFirst);
        return Future.value(false);
      },
      child: Scaffold(
        backgroundColor: context.color.backgroundColor,
        body: Center(
          child: Column(
            mainAxisAlignment: .center,
            children: [
              CustomImage(
                imageUrl: AppIcons.propertySubmitted,
                width: 260.rs(context),
                height: 260.rs(context),
              ),
              const SizedBox(height: 35),
              CustomText(
                'congratulations'.translate(context),
                fontWeight: .bold,
                fontSize: context.font.xl,
                color: context.color.tertiaryColor,
              ),
              const SizedBox(height: 8),
              CustomText(
                'submittedSuccess'.translate(context),
                textAlign: .center,
                fontSize: context.font.md,
              ),
              const SizedBox(height: 32),
              UiUtils.buildButton(
                context,
                onPressed: () async {
                  try {
                    await HelperUtils.loadAndNavigateToPropertyDetails(
                      context: context,
                      propertyId: model.id!,
                      isMyProperty:
                          model.addedBy.toString() == HiveUtils.getUserId(),
                      fromMyProperty: true,
                      fromSuccess: true,
                      showLoader: true,
                    );
                  } on Exception catch (_) {
                    // Property navigation errors are handled by the shared helper flow.
                  }
                },
                height: 48.rh(context),
                width: 224.rw(context),
                buttonColor: context.color.primaryColor,
                textColor: context.color.tertiaryColor,
                border: BorderSide(color: context.color.tertiaryColor),
                buttonTitle: 'previewProperty'.translate(context),
              ),
              const SizedBox(height: 8),
              GestureDetector(
                onTap: () async {
                  await context
                      .read<FetchMyPropertiesCubit>()
                      .fetchMyProperties(
                        status: '',
                        type: '',
                      );
                  await Navigator.of(context).pushNamedAndRemoveUntil(
                    Routes.main,
                    (route) => false,
                    arguments: {'from': 'propertySuccess'},
                  );
                },
                child: CustomText(
                  'backToHome'.translate(context),
                  fontSize: context.font.md,
                ),
              ),
            ],
          ),
        ),
      ),
    );
  }
}
