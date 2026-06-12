import 'package:firebase_core/firebase_core.dart' show FirebaseOptions;
import 'package:flutter/foundation.dart'
    show TargetPlatform, defaultTargetPlatform, kIsWeb;

/// Sukoon Homes — generated from android/app/google-services.json
class DefaultFirebaseOptions {
  static FirebaseOptions get currentPlatform {
    if (kIsWeb) {
      throw UnsupportedError('Firebase is not configured for web.');
    }
    switch (defaultTargetPlatform) {
      case TargetPlatform.android:
        return android;
      case TargetPlatform.iOS:
        return ios;
      default:
        throw UnsupportedError(
          'Firebase is not configured for $defaultTargetPlatform.',
        );
    }
  }

  static const FirebaseOptions android = FirebaseOptions(
    apiKey: 'AIzaSyCD1bFsOfmtu1h79eQASoj1zYe9RCcbnIg',
    appId: '1:168614703336:android:95b41d2b7d01a836d7d95d',
    messagingSenderId: '168614703336',
    projectId: 'sukoon-groups',
    storageBucket: 'sukoon-groups.firebasestorage.app',
  );

  static const FirebaseOptions ios = FirebaseOptions(
    apiKey: 'AIzaSyCD1bFsOfmtu1h79eQASoj1zYe9RCcbnIg',
    appId: '1:168614703336:android:95b41d2b7d01a836d7d95d',
    messagingSenderId: '168614703336',
    projectId: 'sukoon-groups',
    storageBucket: 'sukoon-groups.firebasestorage.app',
    iosBundleId: 'homes.sukoon.group',
  );
}
