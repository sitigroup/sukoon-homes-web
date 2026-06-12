import 'package:ebroker/data/model/languages_model.dart';
import 'package:ebroker/data/model/propery_filter_model.dart';
import 'package:ebroker/data/model/system_settings_model.dart';
import 'package:ebroker/exports/main_export.dart';

const String svgPath = 'assets/svg/';

abstract class Constant {
  static const String appName = AppSettings.applicationName;
  static const String androidPackageName = AppSettings.androidPackageName;
  static String iOSAppId = AppSettings.iOSAppId;
  static String playstoreURLAndroid = AppSettings.playstoreURLAndroid;
  static String appstoreURLios = AppSettings.appstoreURLios;
  static List<LanguagesModel> languages = AppSettings.languages;

  static ScrollPhysics scrollPhysics = AlwaysScrollableScrollPhysics(
    parent: Platform.isIOS ? const ClampingScrollPhysics() : null,
  );

  //backend url
  static String baseUrl = AppSettings.baseUrl;

  ///These task IDs are for load task parallel into the isolate .
  static int? languageTaskId;
  static int? appSettingTaskId;

  ///admob
  static bool isAdmobAdsEnabled = false;
  //Banner
  static String admobBannerAndroid = '';
  static String admobBannerIos = '';
  //Interstitial
  static String admobInterstitialAndroid = '';
  static String admobInterstitialIos = '';

  //Native ads ids
  static String admobNativeAndroid = '';
  static String admobNativeIos = '';

  static bool isSandBoxMode = AppSettings.isSandBoxMode; //testing mode

  /////////////////////////////////

  // static late Session session;
  static String currencySymbol = '\u{20B9}';
  //
  static int otpTimeOutSecond = AppSettings.otpTimeOutSecond; //otp time out
  static int otpResendSecond = AppSettings.otpResendSecond; // resend otp timer
  static int otpResendSecondForEmail = AppSettings.otpResendSecondForEmail;
  //

  static String logintypeMobile = '1'; //always 1
  //
  static String maintenanceMode = '0'; //OFF
  static bool isUserDeactivated = false;
  //
  static String valSellBuy = '0';
  static String valRent = '1';
  //
  static int loadLimit = AppSettings.apiDataLoadLimit;

  static const String defaultCountryCode = AppSettings.defaultCountryCode;

  ///This maxCategoryLength is for show limited number of categories and show "More" button,
  ///You have to set less than [loadLimit] constant

  static const int maxCategoryLength =
      AppSettings.maxCategoryShowLengthInHomeScreen;

  //

  ///Lottie animation
  static const String progressLottieFile = AppSettings.progressLottieFile;
  static const String progressLottieFileWhite = AppSettings
      .progressLottieFileWhite; //When there is dark background and you want to show progress so it will be used

  static const String maintenanceModeLottieFile =
      AppSettings.maintenanceModeLottieFile;

  ///

  ///Put your loading json file in assets/lottie/ folder
  static const bool useLottieProgress = AppSettings
      .useLottieProgress; //if you don't want to use lottie progress then set it to false'

  static const String notificationChannel = AppSettings.notificationChannel;
  static int uploadImageQuality = AppSettings.uploadImageQuality; //0 to 100

  static String? subscriptionPackageId;
  static PropertyFilterModel? propertyFilter;
  static List<int>? filterFacilities;
  static GlobalKey<NavigatorState> navigatorKey = GlobalKey<NavigatorState>(
    debugLabel: 'navigatorKey from constants',
  );

  static Future<void> navigateTo(String routeName, {Object? arguments}) async {
    await navigatorKey.currentState?.pushNamed(routeName, arguments: arguments);
  }

  static String typeRent = 'rent';
  static String generalNotification = '0';
  static String enquiryNotification = '1';
  static String notificationPropertyEnquiry = 'property_inquiry';
  static String notificationDefault = 'default';

  //

  //

  static List<int> interestedPropertyIds = [];

  static Map<dynamic, dynamic> addProperty = {};

  static Map<SystemSetting, String> systemSettingKeys = {
    SystemSetting.currencySymbol: 'currency_symbol',
    SystemSetting.privacyPolicy: 'privacy_policy',
    SystemSetting.contactUs: '',
    SystemSetting.maintenanceMode: 'maintenance_mode',
    SystemSetting.termsConditions: 'terms_conditions',
    SystemSetting.subscription: 'subscription',
    SystemSetting.languageType: 'languages',
    SystemSetting.defaultLanguage: 'default_language',
    SystemSetting.forceUpdate: 'force_update',
    SystemSetting.androidVersion: 'android_version',
    SystemSetting.numberWithSuffix: 'number_with_suffix',
    SystemSetting.iosVersion: 'ios_version',
    SystemSetting.language: 'default_language_name',
    SystemSetting.numberWithOtpLogin: 'number_with_otp_login',
    SystemSetting.socialLogin: 'social_login',
    SystemSetting.emailPasswordLogin: 'email_password_login',
  };

  ///This is limit of minimum chat messages load count , make sure you set it grater than 25;
  static int minChatMessages = 35;

  static bool showExperimentals = true;
  //Don't touch this settings
  static bool isUpdateAvailable = false;
  static String newVersionNumber = '';
  static bool isNumberWithSuffix = false;

  //Demo mode settings
  static bool isDemoModeOn = false;
  static String demoCountryCode = '91';
  static String demoMobileNumber = '1234567890';
  static String demoFirebaseID = '6a1Zdl2TxORQGbCazj4XDGfgBBG3';
  static String demoModeOTP = '123456';

  static const String terminalLogMode = 'debug';
}
