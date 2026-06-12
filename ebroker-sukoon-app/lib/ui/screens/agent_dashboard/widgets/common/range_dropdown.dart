import 'package:ebroker/exports/main_export.dart';
import 'package:flutter/material.dart';

enum MostViewedListingsRange {
  weekly('weekly', 'weekly'),
  monthly('monthly', 'monthly'),
  lastThreeMonths('last_three_months', 'lastThreeMonths')
  ;

  const MostViewedListingsRange(this.apiValue, this.translationKey);

  final String apiValue;
  final String translationKey;
}

enum ListingOverviewRange {
  weekly('weekly', 'weekly'),
  monthly('monthly', 'monthly'),
  yearly('yearly', 'yearly')
  ;

  const ListingOverviewRange(this.apiValue, this.translationKey);

  final String apiValue;
  final String translationKey;
}

enum CategoryRange {
  weekly('weekly', 'weekly'),
  monthly('monthly', 'monthly'),
  yearly('yearly', 'yearly')
  ;

  const CategoryRange(this.apiValue, this.translationKey);

  final String apiValue;
  final String translationKey;
}

class RangeDropdown<T extends Enum> extends StatelessWidget {
  const RangeDropdown({
    required this.value,
    required this.options,
    required this.labelOf,
    required this.onChanged,
    super.key,
  });

  final T value;
  final List<T> options;
  final String Function(T) labelOf;
  final ValueChanged<T> onChanged;

  @override
  Widget build(BuildContext context) {
    return PopupMenuButton<T>(
      initialValue: value,
      onSelected: onChanged,
      color: context.color.secondaryColor,
      position: PopupMenuPosition.under,
      shape: RoundedRectangleBorder(
        borderRadius: BorderRadius.circular(10),
        side: BorderSide(color: context.color.borderColor),
      ),
      itemBuilder: (context) => options
          .map(
            (o) => PopupMenuItem<T>(
              value: o,
              child: CustomText(
                labelOf(o).translate(context),
                fontSize: context.font.sm,
                color: context.color.textColorDark,
              ),
            ),
          )
          .toList(),
      child: Row(
        mainAxisSize: MainAxisSize.min,
        children: [
          CustomText(
            labelOf(value).translate(context),
            fontSize: context.font.sm,
            fontWeight: FontWeight.w600,
            color: context.color.textColorDark,
            showUnderline: true,
          ),
          SizedBox(width: 4.rw(context)),
          Icon(
            Icons.keyboard_arrow_down,
            size: 16.rw(context),
            color: context.color.textColorDark,
          ),
        ],
      ),
    );
  }
}
