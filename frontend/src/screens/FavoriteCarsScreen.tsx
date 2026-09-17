import React, { useState } from 'react';
import {
  Alert,
  ScrollView,
  StyleSheet,
  Text,
  TouchableOpacity,
  View,
} from 'react-native';
import { SafeAreaView } from 'react-native-safe-area-context';

import {
  BackArrowIcon,
  HeartOutlineIcon,
  LiveTrackIcon,
} from '../components/HomeIcons';

interface FavoriteVehicle {
  id: string;
  name: string;
  plate: string;
  color: string;
  isPrimary: boolean;
  status: 'driving' | 'parked' | 'armed';
  location: string;
  speed: string;
}

const SAMPLE_FAVORITES: FavoriteVehicle[] = [
  {
    id: 'fav-1',
    name: 'Toyota Corolla',
    plate: 'ABC-123DE',
    color: 'Midnight Black',
    isPrimary: true,
    status: 'driving',
    location: 'Lekki Phase 1, Lagos',
    speed: '42 km/h',
  },
  {
    id: 'fav-2',
    name: 'Toyota Camry',
    plate: 'KJA-982XY',
    color: 'Silver Metallic',
    isPrimary: false,
    status: 'armed',
    location: 'Victoria Island, Lagos',
    speed: '0 km/h (Armed)',
  },
  {
    id: 'fav-3',
    name: 'Honda Accord',
    plate: 'IKJ-443ZA',
    color: 'Pearl White',
    isPrimary: false,
    status: 'parked',
    location: 'Ikeja GRA, Lagos',
    speed: '0 km/h (Safe)',
  },
];

interface FavoriteCarsScreenProps {
  onBack?: () => void;
  onNavigateToMap?: () => void;
  onNavigateToVehicleDetail?: (vehicleName: string) => void;
}

export function FavoriteCarsScreen({
  onBack,
  onNavigateToMap,
  onNavigateToVehicleDetail,
}: FavoriteCarsScreenProps): React.JSX.Element {
  const [vehicles, setVehicles] = useState<FavoriteVehicle[]>(SAMPLE_FAVORITES);

  const handleSetPrimary = (id: string) => {
    setVehicles((prev) =>
      prev.map((v) => ({
        ...v,
        isPrimary: v.id === id,
      }))
    );
    Alert.alert('Primary Vehicle Updated', 'Default dashboard vehicle updated.');
  };

  const handleRemoveFavorite = (id: string) => {
    Alert.alert('Remove Favorite', 'Remove this vehicle from your quick favorites list?', [
      { text: 'Cancel', style: 'cancel' },
      {
        text: 'Remove',
        style: 'destructive',
        onPress: () => {
          setVehicles((prev) => prev.filter((v) => v.id !== id));
        },
      },
    ]);
  };

  return (
    <SafeAreaView style={styles.safeArea} edges={['top', 'left', 'right']}>
      <View style={styles.container}>
        {/* Header */}
        <View style={styles.header}>
          <TouchableOpacity
            style={styles.headerCircleBtn}
            onPress={onBack}
            activeOpacity={0.7}
            hitSlop={{ top: 10, bottom: 10, left: 10, right: 10 }}
          >
            <BackArrowIcon color="#1E2538" size={18} />
          </TouchableOpacity>

          <Text style={styles.headerTitle}>Favorite Cars</Text>

          <View style={styles.headerRightSpacer} />
        </View>

        <ScrollView
          style={styles.scrollContainer}
          contentContainerStyle={styles.scrollContent}
          showsVerticalScrollIndicator={false}
        >
          <View style={styles.bannerCard}>
            <View style={styles.bannerIconBox}>
              <HeartOutlineIcon color="#EA580C" size={24} />
            </View>
            <View style={styles.bannerInfo}>
              <Text style={styles.bannerTitle}>Priority Fleet Access</Text>
              <Text style={styles.bannerSub}>
                Bookmarked vehicles receive priority status alerts and instant one-tap tracking.
              </Text>
            </View>
          </View>

          <View style={styles.sectionHeader}>
            <Text style={styles.sectionTitle}>Your Favorite Vehicles ({vehicles.length})</Text>
          </View>

          <View style={styles.vehiclesList}>
            {vehicles.map((car) => (
              <View key={car.id} style={styles.vehicleCard}>
                <View style={styles.cardHeader}>
                  <View>
                    <View style={styles.titleRow}>
                      <Text style={styles.vehicleName}>{car.name}</Text>
                      {car.isPrimary && (
                        <View style={styles.primaryBadge}>
                          <Text style={styles.primaryBadgeText}>Primary</Text>
                        </View>
                      )}
                    </View>
                    <Text style={styles.plateText}>{car.plate} • {car.color}</Text>
                  </View>

                  <TouchableOpacity
                    style={styles.removeBtn}
                    onPress={() => handleRemoveFavorite(car.id)}
                    hitSlop={{ top: 8, bottom: 8, left: 8, right: 8 }}
                  >
                    <HeartOutlineIcon color="#EA580C" size={18} />
                  </TouchableOpacity>
                </View>

                {/* Telemetry Strip */}
                <View style={styles.telemetryStrip}>
                  <View style={styles.statusPill}>
                    <View
                      style={[
                        styles.statusDot,
                        car.status === 'driving'
                          ? styles.statusDriving
                          : car.status === 'armed'
                          ? styles.statusArmed
                          : styles.statusSafe,
                      ]}
                    />
                    <Text style={styles.statusText}>{car.speed}</Text>
                  </View>

                  <Text style={styles.locationText} numberOfLines={1}>
                    {car.location}
                  </Text>
                </View>

                {/* Actions Row */}
                <View style={styles.cardActionsRow}>
                  {!car.isPrimary && (
                    <TouchableOpacity
                      style={styles.setPrimaryBtn}
                      activeOpacity={0.7}
                      onPress={() => handleSetPrimary(car.id)}
                    >
                      <Text style={styles.setPrimaryText}>Set as Primary</Text>
                    </TouchableOpacity>
                  )}

                  <TouchableOpacity
                    style={styles.trackBtn}
                    activeOpacity={0.8}
                    onPress={() => {
                      if (onNavigateToVehicleDetail) {
                        onNavigateToVehicleDetail(car.name);
                      } else if (onNavigateToMap) {
                        onNavigateToMap();
                      }
                    }}
                  >
                    <LiveTrackIcon color="#FFFFFF" size={16} />
                    <Text style={styles.trackBtnText}>Live Telematics</Text>
                  </TouchableOpacity>
                </View>
              </View>
            ))}
          </View>
        </ScrollView>
      </View>
    </SafeAreaView>
  );
}

const styles = StyleSheet.create({
  safeArea: {
    flex: 1,
    backgroundColor: '#FFFFFF',
  },
  container: {
    flex: 1,
    backgroundColor: '#F8FAFC',
  },
  header: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'space-between',
    paddingHorizontal: 20,
    paddingTop: 10,
    paddingBottom: 14,
    backgroundColor: '#FFFFFF',
    borderBottomWidth: 1,
    borderBottomColor: '#F0F2F5',
  },
  headerCircleBtn: {
    width: 38,
    height: 38,
    borderRadius: 19,
    borderWidth: 1,
    borderColor: '#E5E7EB',
    backgroundColor: '#FFFFFF',
    alignItems: 'center',
    justifyContent: 'center',
  },
  headerTitle: {
    fontSize: 17,
    fontFamily: 'Helvetica',
    fontWeight: '700',
    color: '#111827',
  },
  headerRightSpacer: {
    width: 38,
  },
  scrollContainer: {
    flex: 1,
  },
  scrollContent: {
    padding: 20,
    paddingBottom: 90,
  },
  bannerCard: {
    flexDirection: 'row',
    alignItems: 'center',
    padding: 16,
    borderRadius: 18,
    backgroundColor: '#FFF7ED',
    borderWidth: 1,
    borderColor: '#FFEDD5',
    marginBottom: 20,
  },
  bannerIconBox: {
    width: 44,
    height: 44,
    borderRadius: 12,
    backgroundColor: '#FFEDD5',
    alignItems: 'center',
    justifyContent: 'center',
    marginRight: 12,
  },
  bannerInfo: {
    flex: 1,
  },
  bannerTitle: {
    fontSize: 13,
    fontFamily: 'Helvetica',
    fontWeight: '700',
    color: '#C2410C',
    marginBottom: 2,
  },
  bannerSub: {
    fontSize: 11,
    fontFamily: 'Aeonik',
    color: '#EA580C',
    lineHeight: 15,
  },
  sectionHeader: {
    marginBottom: 12,
  },
  sectionTitle: {
    fontSize: 13,
    fontFamily: 'Helvetica',
    fontWeight: '700',
    color: '#64748B',
    textTransform: 'uppercase',
    letterSpacing: 0.5,
  },
  vehiclesList: {
    gap: 12,
  },
  vehicleCard: {
    padding: 16,
    borderRadius: 18,
    backgroundColor: '#FFFFFF',
    borderWidth: 1,
    borderColor: '#E2E8F0',
  },
  cardHeader: {
    flexDirection: 'row',
    alignItems: 'flex-start',
    justifyContent: 'space-between',
    marginBottom: 12,
  },
  titleRow: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 8,
    marginBottom: 2,
  },
  vehicleName: {
    fontSize: 15,
    fontFamily: 'Helvetica',
    fontWeight: '700',
    color: '#111827',
  },
  primaryBadge: {
    paddingHorizontal: 8,
    paddingVertical: 2,
    borderRadius: 6,
    backgroundColor: '#0F172A',
  },
  primaryBadgeText: {
    fontSize: 10,
    fontFamily: 'Helvetica',
    fontWeight: '700',
    color: '#FFFFFF',
  },
  plateText: {
    fontSize: 12,
    fontFamily: 'Aeonik',
    color: '#64748B',
  },
  removeBtn: {
    padding: 4,
  },
  telemetryStrip: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'space-between',
    padding: 10,
    borderRadius: 12,
    backgroundColor: '#F8FAFC',
    marginBottom: 14,
  },
  statusPill: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 6,
  },
  statusDot: {
    width: 7,
    height: 7,
    borderRadius: 3.5,
  },
  statusDriving: {
    backgroundColor: '#10B981',
  },
  statusArmed: {
    backgroundColor: '#F59E0B',
  },
  statusSafe: {
    backgroundColor: '#64748B',
  },
  statusText: {
    fontSize: 11,
    fontFamily: 'Helvetica',
    fontWeight: '700',
    color: '#1E293B',
  },
  locationText: {
    fontSize: 11,
    fontFamily: 'Aeonik',
    color: '#64748B',
    maxWidth: '55%',
  },
  cardActionsRow: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 10,
  },
  setPrimaryBtn: {
    flex: 1,
    height: 40,
    borderRadius: 10,
    backgroundColor: '#F1F5F9',
    alignItems: 'center',
    justifyContent: 'center',
  },
  setPrimaryText: {
    fontSize: 12,
    fontFamily: 'Helvetica',
    fontWeight: '700',
    color: '#475569',
  },
  trackBtn: {
    flex: 1,
    height: 40,
    borderRadius: 10,
    backgroundColor: '#111827',
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'center',
    gap: 6,
  },
  trackBtnText: {
    fontSize: 12,
    fontFamily: 'Helvetica',
    fontWeight: '700',
    color: '#FFFFFF',
  },
});
