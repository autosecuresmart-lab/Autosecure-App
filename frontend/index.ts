import { registerRootComponent } from 'expo';

import App from './App';

// registerRootComponent calls AppRegistry.registerComponent('main', () => App)
// and ensures the environment is set up appropriately whether the app is run in
// Expo Go or as a native build.
registerRootComponent(App);
