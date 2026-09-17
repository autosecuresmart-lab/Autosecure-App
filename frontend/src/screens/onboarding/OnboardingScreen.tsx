import React, { useRef, useState } from 'react';
import {
  Animated,
  Dimensions,
  FlatList,
  ImageBackground,
  NativeScrollEvent,
  NativeSyntheticEvent,
  Platform,
  StyleSheet,
  Text,
  TouchableOpacity,
  View,
} from 'react-native';
import { StatusBar } from 'expo-status-bar';
import { useSafeAreaInsets } from 'react-native-safe-area-context';
import { LinearGradient } from 'expo-linear-gradient';
import { LogoBadge } from '../../components/AutoSecureLogo';
import { fonts } from '../../theme';

const { width: SCREEN_WIDTH, height: SCREEN_HEIGHT } = Dimensions.get('window');

const CAR_IMAGE_1 = require('../../../assets/images/onboarding_car_1.jpg');
const CAR_IMAGE_2 = require('../../../assets/images/onboarding_car_2.jpg');

interface OnboardingScreenProps {
  onComplete: () => void;
}

interface SlideItem {
  id: string;
  image: any;
  type: 'welcome' | 'features';
}

const SLIDES: SlideItem[] = [
  {
    id: '1',
    image: CAR_IMAGE_1,
    type: 'welcome',
  },
  {
    id: '2',
    image: CAR_IMAGE_2,
    type: 'features',
  },
];

export function OnboardingScreen({ onComplete }: OnboardingScreenProps): React.JSX.Element {
  const insets = useSafeAreaInsets();
  const flatListRef = useRef<FlatList>(null);
  const [currentIndex, setCurrentIndex] = useState(0);
  const scrollX = useRef(new Animated.Value(0)).current;

  const handleNext = () => {
    if (currentIndex < SLIDES.length - 1) {
      flatListRef.current?.scrollToIndex({
        index: currentIndex + 1,
        animated: true,
      });
      setCurrentIndex(currentIndex + 1);
    } else {
      onComplete();
    }
  };

  const onMomentumScrollEnd = (e: NativeSyntheticEvent<NativeScrollEvent>) => {
    const offsetX = e.nativeEvent.contentOffset.x;
    const index = Math.round(offsetX / SCREEN_WIDTH);
    setCurrentIndex(index);
  };

  const renderSlide = ({ item, index }: { item: SlideItem; index: number }) => {
    return (
      <View style={[styles.slide, { width: SCREEN_WIDTH }]}>
        <ImageBackground
          source={item.image}
          style={styles.backgroundImage}
          resizeMode="cover"
        >
          {/* Gradient Overlay for Depth and Contrast */}
          <LinearGradient
            colors={[
              'rgba(10, 15, 26, 0.65)',
              'rgba(10, 15, 26, 0.25)',
              'rgba(10, 15, 26, 0.40)',
              'rgba(9, 13, 22, 0.92)',
            ]}
            locations={[0, 0.28, 0.62, 0.95]}
            style={styles.gradientOverlay}
          >
            {/* Header with Circular Logo Badge */}
            <View style={[styles.header, { paddingTop: Math.max(insets.top, 24) + 12 }]}>
              <LogoBadge size={54} />
            </View>

            {/* Slide 1 Content */}
            {item.type === 'welcome' && (
              <View style={styles.contentContainer}>
                <View style={styles.titleSection}>
                  <Text style={styles.welcomeText}>Welcome to</Text>
                  <View style={styles.brandTitleRow}>
                    <Text style={styles.brandAutoText}>auto</Text>
                    <Text style={styles.brandSecureText}>Secure</Text>
                  </View>
                </View>
              </View>
            )}

            {/* Slide 2 Content */}
            {item.type === 'features' && (
              <View style={styles.contentContainer}>
                <View style={styles.titleSection}>
                  <Text style={styles.featureTitle}>Real-Time Location,</Text>
                  <Text style={styles.featureTitle}>Simplified</Text>
                </View>

                <View style={styles.featureDescWrapper}>
                  <Text style={styles.featureDesc}>
                    Easily track and monitor the locations that matter most in real time. Stay
                    informed, stay safe, and make smarter decisions with instant updates—no hassle,
                    no delays
                  </Text>
                </View>
              </View>
            )}
          </LinearGradient>
        </ImageBackground>
      </View>
    );
  };

  return (
    <View style={styles.container}>
      <StatusBar style="light" />

      {/* Slide Carousel */}
      <Animated.FlatList
        ref={flatListRef}
        data={SLIDES}
        keyExtractor={(item) => item.id}
        renderItem={renderSlide}
        horizontal
        pagingEnabled
        showsHorizontalScrollIndicator={false}
        bounces={false}
        onMomentumScrollEnd={onMomentumScrollEnd}
        onScroll={Animated.event(
          [{ nativeEvent: { contentOffset: { x: scrollX } } }],
          { useNativeDriver: false }
        )}
        scrollEventThrottle={16}
        style={styles.carousel}
      />

      {/* Floating Bottom Control Bar */}
      <View
        style={[
          styles.bottomControls,
          {
            paddingBottom: Math.max(insets.bottom, 16) + 16,
          },
        ]}
      >
        {/* Pagination Dots (Shown on Slide 2 or dynamically) */}
        {currentIndex === 1 && (
          <View style={styles.paginationRow}>
            {SLIDES.map((_, dotIndex) => {
              const isActive = dotIndex === currentIndex;
              return (
                <View
                  key={`dot-${dotIndex}`}
                  style={[
                    styles.indicatorDot,
                    isActive ? styles.indicatorActivePill : styles.indicatorInactiveDot,
                  ]}
                />
              );
            })}
          </View>
        )}

        {/* CTA Button */}
        <TouchableOpacity
          activeOpacity={0.88}
          onPress={handleNext}
          style={styles.actionButton}
        >
          <Text style={styles.actionButtonText}>
            {currentIndex === 0 ? 'Next' : 'Get Started'}
          </Text>
        </TouchableOpacity>
      </View>
    </View>
  );
}

const styles = StyleSheet.create({
  container: {
    flex: 1,
    backgroundColor: '#0A0F1A',
  },
  carousel: {
    flex: 1,
  },
  slide: {
    flex: 1,
    height: SCREEN_HEIGHT,
  },
  backgroundImage: {
    flex: 1,
    width: '100%',
    height: '100%',
  },
  gradientOverlay: {
    flex: 1,
    paddingHorizontal: 28,
  },
  header: {
    alignItems: 'flex-start',
  },
  contentContainer: {
    flex: 1,
    justifyContent: 'space-between',
    paddingTop: 48,
    paddingBottom: 150,
  },
  titleSection: {
    marginTop: 10,
  },
  welcomeText: {
    fontFamily: fonts.header,
    fontSize: 34,
    fontWeight: '700',
    color: '#FFFFFF',
    letterSpacing: -0.6,
    lineHeight: 42,
  },
  brandTitleRow: {
    flexDirection: 'row',
    alignItems: 'baseline',
    marginTop: 2,
  },
  brandAutoText: {
    fontFamily: fonts.header,
    fontSize: 34,
    fontWeight: '700',
    color: '#FFFFFF',
    letterSpacing: -0.6,
  },
  brandSecureText: {
    fontFamily: fonts.header,
    fontSize: 34,
    fontWeight: '700',
    color: '#DE8635',
    letterSpacing: -0.6,
  },

  featureTitle: {
    fontFamily: fonts.header,
    fontSize: 32,
    fontWeight: '700',
    color: '#FFFFFF',
    letterSpacing: -0.5,
    lineHeight: 40,
  },
  featureDescWrapper: {
    marginTop: 'auto',
    marginBottom: 20,
    paddingRight: 10,
  },
  featureDesc: {
    fontFamily: fonts.body,
    fontSize: 13.5,
    lineHeight: 20.5,
    fontWeight: '400',
    color: '#CBD5E1',
    letterSpacing: 0.1,
  },

  bottomControls: {
    position: 'absolute',
    left: 24,
    right: 24,
    bottom: 0,
    alignItems: 'center',
  },
  paginationRow: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'center',
    marginBottom: 24,
    gap: 6,
  },
  indicatorDot: {
    height: 5,
    borderRadius: 3,
  },
  indicatorInactiveDot: {
    width: 5,
    backgroundColor: '#64748B',
  },
  indicatorActivePill: {
    width: 20,
    backgroundColor: '#CBD5E1',
  },

  actionButton: {
    width: '100%',
    height: 54,
    backgroundColor: '#1E2538',
    borderRadius: 12,
    alignItems: 'center',
    justifyContent: 'center',
    shadowColor: '#000000',
    shadowOffset: { width: 0, height: 4 },
    shadowOpacity: 0.3,
    shadowRadius: 8,
    elevation: 4,
    borderWidth: Platform.OS === 'ios' ? 0.5 : 0,
    borderColor: 'rgba(255, 255, 255, 0.08)',
  },
  actionButtonText: {
    fontFamily: fonts.header,
    fontSize: 16,
    fontWeight: '600',
    color: '#FFFFFF',
    letterSpacing: 0.2,
  },
});
