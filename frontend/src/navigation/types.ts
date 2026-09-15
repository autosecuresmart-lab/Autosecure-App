/**
 * Navigation types.
 *
 * The five tabs follow the recommended main navigation in the AUTOSECURE 2.0
 * proposal: Home, Security, Camera, Finder, My AUTOSECURE.
 */

export type MainTabParamList = {
  Home: undefined;
  Security: undefined;
  Camera: undefined;
  Finder: undefined;
  /** "My AUTOSECURE" — vehicle records, AutoDoc, coins, subscription, settings. */
  Account: undefined;
};

export type AuthScreenName = 'login' | 'register';
