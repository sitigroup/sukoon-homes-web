import 'package:firebase_auth/firebase_auth.dart';

class SocialAuthHelper {
  SocialAuthHelper._();

  static String? email(User? user) => user?.email;

  static String? displayName(User? user) => user?.displayName;

  static String? phoneNumber(User? user) => user?.phoneNumber;
}
