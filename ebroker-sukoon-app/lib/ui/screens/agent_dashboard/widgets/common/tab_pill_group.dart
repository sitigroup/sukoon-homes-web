import 'package:ebroker/exports/main_export.dart';
import 'package:flutter/material.dart';

class TabPillGroup extends StatelessWidget {
  const TabPillGroup({
    required this.labels,
    required this.selectedIndex,
    required this.onChanged,
    super.key,
  });

  final List<String> labels;
  final int selectedIndex;
  final ValueChanged<int> onChanged;

  @override
  Widget build(BuildContext context) {
    return Container(
      decoration: BoxDecoration(
        color: context.color.primaryColor,
        borderRadius: BorderRadius.circular(6),
      ),
      child: Row(
        children: [
          for (int i = 0; i < labels.length; i++)
            Expanded(
              child: _TabPill(
                label: labels[i],
                isSelected: selectedIndex == i,
                onTap: () => onChanged(i),
              ),
            ),
        ],
      ),
    );
  }
}

class _TabPill extends StatelessWidget {
  const _TabPill({
    required this.label,
    required this.isSelected,
    required this.onTap,
  });

  final String label;
  final bool isSelected;
  final VoidCallback onTap;

  @override
  Widget build(BuildContext context) {
    return GestureDetector(
      behavior: HitTestBehavior.opaque,
      onTap: onTap,
      child: AnimatedContainer(
        duration: const Duration(milliseconds: 180),
        padding: EdgeInsets.symmetric(vertical: 10.rh(context)),
        alignment: Alignment.center,
        decoration: BoxDecoration(
          color: isSelected ? context.color.textColorDark : Colors.transparent,
          borderRadius: BorderRadius.circular(8),
        ),
        child: CustomText(
          label,
          fontSize: context.font.sm,
          fontWeight: FontWeight.w600,
          color: isSelected
              ? context.color.secondaryColor
              : context.color.textLightColor,
        ),
      ),
    );
  }
}
