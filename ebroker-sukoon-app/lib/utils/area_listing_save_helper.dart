class AreaListingSaveHelper {
  AreaListingSaveHelper._();

  static String titleCaseLocation(String value) {
    return value
        .trim()
        .split(RegExp(r'\s+'))
        .map(
          (word) => word.isEmpty
              ? ''
              : '${word[0].toUpperCase()}${word.substring(1).toLowerCase()}',
        )
        .join(' ');
  }
}
