//For localization of app

import 'dart:convert';

import 'package:ebroker/utils/hive_utils.dart';
import 'package:flutter/material.dart';
import 'package:flutter/services.dart';

class AppLocalization {
  AppLocalization(this.locale);
  final Locale locale;

  /// Bundled Sukoon strings always win over API language packs (wrteam defaults).
  static const _sukoonOverrideKeys = <String>{
    'appName',
    'onboarding_1_title',
    'onboarding_1_description',
    'onboarding_2_title',
    'onboarding_2_description',
    'onboarding_3_title',
    'onboarding_3_description',
  };

  //it will hold key of text and it's values in given language
  late Map<String, String> _localizedValues;

  static String _sanitizeBranding(String value) {
    var text = value;
    final lower = text.toLowerCase();
    if (lower.contains('welcome to ebroker') ||
        lower.contains('welcome to e broker')) {
      return 'Welcome To Sukoon Homes';
    }
    if (RegExp(r'\bebroker\b', caseSensitive: false).hasMatch(text)) {
      text = text.replaceAll(
        RegExp(r'\beBroker\b', caseSensitive: false),
        'Sukoon Homes',
      );
      text = text.replaceAll(
        RegExp(r'\bebroker\b', caseSensitive: false),
        'Sukoon Homes',
      );
    }
    return text;
  }

  //to access app-localization instance any where in app using context
  static AppLocalization? of(BuildContext context) {
    return Localizations.of(context, AppLocalization);
  }

  //to load json(language) from assets
  Future<dynamic> loadJson() async {
    final jsonStringValues =
        await rootBundle.loadString('assets/languages/template.json');
    final template =
        json.decode(jsonStringValues) as Map<String, dynamic>;
    final getLanguage = HiveUtils.getLanguage() as Map<dynamic, dynamic>?;
    final languageIsNull = getLanguage == null || getLanguage['data'] == null;

    final mappedJson = languageIsNull
        ? Map<String, dynamic>.from(template)
        : {
            ...template,
            ...Map<String, dynamic>.from(
              getLanguage['data'] as Map<dynamic, dynamic>,
            ),
          };

    for (final key in _sukoonOverrideKeys) {
      if (template.containsKey(key)) {
        mappedJson[key] = template[key];
      }
    }

    _localizedValues = mappedJson.map(
      (key, value) => MapEntry(key, _sanitizeBranding(value.toString())),
    );
  }

  //to get translated value of given title/key
  String? getTranslatedValues(String? key) {
    final value = _localizedValues[key!];
    if (value == null) return null;
    return _sanitizeBranding(value);
  }

  //need to declare custom delegate
  static const LocalizationsDelegate<AppLocalization> delegate =
      _AppLocalizationDelegate();
}

//Custom app delegate
class _AppLocalizationDelegate extends LocalizationsDelegate<AppLocalization> {
  const _AppLocalizationDelegate();

  //providing all supported languages
  @override
  bool isSupported(Locale locale) {
    //
    return true;
  }

  //load languageCode.json files
  @override
  Future<AppLocalization> load(Locale locale) async {
    final localization = AppLocalization(locale);
    await localization.loadJson();
    return localization;
  }

  @override
  bool shouldReload(LocalizationsDelegate<AppLocalization> old) {
    return true;
  }
}
