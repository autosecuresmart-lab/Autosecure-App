import React, { useState } from 'react';
import {
  Alert,
  ImageBackground,
  StatusBar,
  StyleSheet,
  Text,
  TouchableOpacity,
  View,
} from 'react-native';
import { SafeAreaView } from 'react-native-safe-area-context';

import { CloseIcon, FlashIcon } from '../components/HomeIcons';

interface CameraScreenProps {
  onClose?: () => void;
}

export function CameraScreen({ onClose }: CameraScreenProps): React.JSX.Element {
  const [zoomLevel, setZoomLevel] = useState<'1x' | '2x'>('2x');
  const [flashOn, setFlashOn] = useState(false);
  const [isCapturing, setIsCapturing] = useState(false);

  const handleCapture = () => {
    setIsCapturing(true);
    setTimeout(() => {
      setIsCapturing(false);
      Alert.alert('Snapshot Saved', 'DashCam snapshot saved to vehicle gallery.');
    }, 400);
  };

  const toggleZoom = () => {
    setZoomLevel((prev) => (prev === '1x' ? '2x' : '1x'));
  };

  const toggleFlash = () => {
    setFlashOn((prev) => !prev);
  };

  return (
    <View style={styles.container}>
      <StatusBar barStyle="light-content" translucent backgroundColor="transparent" />

      {/* Camera Live Feed Background */}
      <ImageBackground
        source={require('../../assets/images/onboarding_car_1.jpg')}
        style={styles.feedImage}
        resizeMode="cover"
      >
        <View style={styles.darkGradientOverlay} />

        <SafeAreaView style={styles.overlayContainer} edges={['top', 'bottom']}>
          {/* Top Header Bar */}
          <View style={styles.topBar}>
            <TouchableOpacity
              style={[styles.topIconBtn, flashOn && styles.topIconBtnActive]}
              onPress={toggleFlash}
              activeOpacity={0.7}
            >
              <FlashIcon color={flashOn ? '#FBBF24' : '#FFFFFF'} size={20} />
            </TouchableOpacity>

            <Text style={styles.headerTitle}>Camera</Text>

            <TouchableOpacity
              style={styles.topIconBtn}
              onPress={onClose}
              activeOpacity={0.7}
            >
              <CloseIcon color="#FFFFFF" size={20} />
            </TouchableOpacity>
          </View>

          {/* Center Viewfinder / Stream Indicator */}
          <View style={styles.centerArea}>
            <View style={styles.liveIndicator}>
              <View style={styles.liveDot} />
              <Text style={styles.liveText}>LIVE DASHCAM FEED</Text>
            </View>
          </View>

          {/* Bottom Control Bar */}
          <View style={styles.bottomControls}>
            {/* Zoom Toggle */}
            <TouchableOpacity
              style={styles.controlCircleBtn}
              onPress={toggleZoom}
              activeOpacity={0.75}
            >
              <Text style={styles.zoomText}>{zoomLevel}</Text>
            </TouchableOpacity>

            {/* Shutter Button */}
            <TouchableOpacity
              style={[styles.shutterOuter, isCapturing && styles.shutterCapturing]}
              onPress={handleCapture}
              activeOpacity={0.85}
            >
              <View style={styles.shutterInner} />
            </TouchableOpacity>

            {/* Flash / Light toggle */}
            <TouchableOpacity
              style={[styles.controlCircleBtn, flashOn && styles.flashBtnActive]}
              onPress={toggleFlash}
              activeOpacity={0.75}
            >
              <FlashIcon color={flashOn ? '#FBBF24' : '#FFFFFF'} size={22} />
            </TouchableOpacity>
          </View>
        </SafeAreaView>
      </ImageBackground>
    </View>
  );
}

const styles = StyleSheet.create({
  container: {
    flex: 1,
    backgroundColor: '#000000',
  },
  feedImage: {
    flex: 1,
    width: '100%',
    height: '100%',
  },
  darkGradientOverlay: {
    ...StyleSheet.absoluteFill,
    backgroundColor: 'rgba(0, 0, 0, 0.25)',
  },
  overlayContainer: {
    flex: 1,
    justifyContent: 'space-between',
  },
  topBar: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'space-between',
    paddingHorizontal: 20,
    paddingTop: 10,
  },
  topIconBtn: {
    width: 44,
    height: 44,
    borderRadius: 22,
    backgroundColor: 'rgba(30, 37, 56, 0.65)',
    alignItems: 'center',
    justifyContent: 'center',
    borderWidth: 1,
    borderColor: 'rgba(255, 255, 255, 0.15)',
  },
  topIconBtnActive: {
    backgroundColor: 'rgba(251, 191, 36, 0.25)',
    borderColor: '#FBBF24',
  },
  headerTitle: {
    fontFamily: 'Helvetica',
    fontSize: 18,
    fontWeight: '700',
    color: '#FFFFFF',
    letterSpacing: 0.3,
  },
  centerArea: {
    flex: 1,
    alignItems: 'center',
    justifyContent: 'flex-start',
    paddingTop: 16,
  },
  liveIndicator: {
    flexDirection: 'row',
    alignItems: 'center',
    backgroundColor: 'rgba(0, 0, 0, 0.55)',
    paddingHorizontal: 12,
    paddingVertical: 5,
    borderRadius: 14,
    gap: 6,
    borderWidth: 1,
    borderColor: 'rgba(255, 255, 255, 0.1)',
  },
  liveDot: {
    width: 7,
    height: 7,
    borderRadius: 3.5,
    backgroundColor: '#EF4444',
  },
  liveText: {
    fontFamily: 'Aeonik',
    fontSize: 10,
    fontWeight: '700',
    color: '#FFFFFF',
    letterSpacing: 0.8,
  },
  bottomControls: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'space-between',
    paddingHorizontal: 36,
    paddingBottom: 28,
  },
  controlCircleBtn: {
    width: 52,
    height: 52,
    borderRadius: 26,
    backgroundColor: 'rgba(30, 37, 56, 0.75)',
    alignItems: 'center',
    justifyContent: 'center',
    borderWidth: 1,
    borderColor: 'rgba(255, 255, 255, 0.2)',
  },
  flashBtnActive: {
    backgroundColor: 'rgba(251, 191, 36, 0.3)',
    borderColor: '#FBBF24',
  },
  zoomText: {
    fontFamily: 'Aeonik',
    fontSize: 15,
    fontWeight: '700',
    color: '#FFFFFF',
  },
  shutterOuter: {
    width: 80,
    height: 80,
    borderRadius: 40,
    borderWidth: 4,
    borderColor: '#FFFFFF',
    alignItems: 'center',
    justifyContent: 'center',
    backgroundColor: 'transparent',
  },
  shutterCapturing: {
    transform: [{ scale: 0.92 }],
    opacity: 0.8,
  },
  shutterInner: {
    width: 64,
    height: 64,
    borderRadius: 32,
    backgroundColor: '#FFFFFF',
  },
});
