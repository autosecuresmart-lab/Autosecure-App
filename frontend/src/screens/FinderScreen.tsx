import React, { useCallback, useEffect, useState } from 'react';
import {
  ActivityIndicator,
  Alert,
  ScrollView,
  StyleSheet,
  Text,
  TextInput,
  TouchableOpacity,
  View,
} from 'react-native';
import { SafeAreaView } from 'react-native-safe-area-context';

import { finderApi } from '../api/endpoints';
import type { FinderCategory, FinderVendor } from '../api/types';
import {
  BackArrowIcon,
  SearchIcon,
  ShieldCheckIcon,
  StarRatingIcon,
} from '../components/HomeIcons';

interface VendorItem {
  id: string;
  uuid: string;
  name: string;
  category: string;
  rating: number;
  reviewCount: number;
  distance: string;
  location: string;
  services: string[];
  serviceUuid?: string;
  startingPrice: number;
  isVerified: boolean;
  isOpenNow: boolean;
}

const DEFAULT_CATEGORIES = ['All', 'Mechanics', 'Auto Parts', 'Car Wash', 'Towing', 'Locksmith'];

const SAMPLE_VENDORS: VendorItem[] = [
  {
    id: 'ven-1',
    uuid: 'sample-ven-1',
    name: 'Apex Precision AutoCare',
    category: 'Mechanics',
    rating: 4.9,
    reviewCount: 128,
    distance: '1.2 km',
    location: 'Plot 14 Admiralty Way, Lekki Phase 1',
    services: ['OBD Diagnostics', 'Brake Servicing', 'Transmission Flush'],
    startingPrice: 35000,
    isVerified: true,
    isOpenNow: true,
  },
  {
    id: 'ven-2',
    uuid: 'sample-ven-2',
    name: 'Prime OEM Parts & Logistics',
    category: 'Auto Parts',
    rating: 4.8,
    reviewCount: 94,
    distance: '2.5 km',
    location: 'Victoria Island, Lagos',
    services: ['Genuine Toyota Brake Pads', 'O2 Sensors', 'Synthetic Engine Oil'],
    startingPrice: 18500,
    isVerified: true,
    isOpenNow: true,
  },
  {
    id: 'ven-3',
    uuid: 'sample-ven-3',
    name: 'Lekki Hydro Detailers & Wash',
    category: 'Car Wash',
    rating: 4.9,
    reviewCount: 215,
    distance: '0.8 km',
    location: 'Freedom Way, Lekki',
    services: ['Ceramic Coating', 'Underbody Steam Wash', 'Interior Detailing'],
    startingPrice: 12000,
    isVerified: true,
    isOpenNow: true,
  },
  {
    id: 'ven-4',
    uuid: 'sample-ven-4',
    name: 'RapidRescue Flatbed Towing 24/7',
    category: 'Towing',
    rating: 4.7,
    reviewCount: 62,
    distance: '3.1 km',
    location: 'Lagos Island & Expressway Dispatch',
    services: ['Flatbed Emergency Towing', 'Battery Jumpstart', 'Fuel Delivery'],
    startingPrice: 25000,
    isVerified: true,
    isOpenNow: true,
  },
];

interface FinderScreenProps {
  onNavigateToBooking?: (params: {
    vendorName: string;
    vendorUuid?: string;
    serviceTitle: string;
    serviceUuid?: string;
    price: number;
  }) => void;
  onBack?: () => void;
}

export function FinderScreen({
  onNavigateToBooking,
  onBack,
}: FinderScreenProps): React.JSX.Element {
  const [categories, setCategories] = useState<string[]>(DEFAULT_CATEGORIES);
  const [activeCategory, setActiveCategory] = useState('All');
  const [searchQuery, setSearchQuery] = useState('');
  const [vendors, setVendors] = useState<VendorItem[]>(SAMPLE_VENDORS);
  const [loading, setLoading] = useState(false);

  const loadData = useCallback(async () => {
    setLoading(true);
    try {
      const [catRes, venRes] = await Promise.allSettled([
        finderApi.categories(),
        finderApi.vendors({
          search: searchQuery.trim() || undefined,
          category: activeCategory !== 'All' ? activeCategory : undefined,
        }),
      ]);

      if (catRes.status === 'fulfilled' && catRes.value.data.length > 0) {
        setCategories(['All', ...catRes.value.data.map((c: FinderCategory) => c.name)]);
      }

      if (venRes.status === 'fulfilled' && venRes.value.data.length > 0) {
        const mapped: VendorItem[] = venRes.value.data.map((v: FinderVendor) => ({
          id: String(v.id),
          uuid: v.uuid,
          name: v.business_name,
          category: v.category?.name || 'Automotive',
          rating: v.rating_average || 5.0,
          reviewCount: v.rating_count || 0,
          distance: 'Nearby',
          location: [v.address_line, v.city, v.state].filter(Boolean).join(', ') || 'Lagos, Nigeria',
          services: v.services_preview && v.services_preview.length > 0
            ? v.services_preview.map((s) => s.name)
            : ['General Inspection & Service'],
          serviceUuid: v.services_preview?.[0]?.uuid,
          startingPrice: v.starting_price || 20000,
          isVerified: v.is_verified,
          isOpenNow: true,
        }));
        setVendors(mapped);
      } else if (searchQuery.trim() === '' && activeCategory === 'All') {
        setVendors(SAMPLE_VENDORS);
      } else {
        // Filter sample fallback
        const filtered = SAMPLE_VENDORS.filter((v) => {
          const matchesCat = activeCategory === 'All' || v.category === activeCategory;
          const matchesSearch =
            searchQuery.trim() === '' ||
            v.name.toLowerCase().includes(searchQuery.toLowerCase()) ||
            v.services.some((s) => s.toLowerCase().includes(searchQuery.toLowerCase()));
          return matchesCat && matchesSearch;
        });
        setVendors(filtered);
      }
    } catch {
      // Offline fallback
    } finally {
      setLoading(false);
    }
  }, [activeCategory, searchQuery]);

  useEffect(() => {
    loadData();
  }, [loadData]);

  const handleBookVendor = (vendor: VendorItem) => {
    if (onNavigateToBooking) {
      onNavigateToBooking({
        vendorName: vendor.name,
        vendorUuid: vendor.uuid,
        serviceTitle: vendor.services[0] || 'Standard Vehicle Service',
        serviceUuid: vendor.serviceUuid,
        price: vendor.startingPrice,
      });
    } else {
      Alert.alert('Book Service', `Initiating booking for ${vendor.name}.`);
    }
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

          <Text style={styles.headerTitle}>AutoSecure Finder</Text>

          <View style={styles.headerRightSpacer} />
        </View>

        <ScrollView
          style={styles.scrollContainer}
          contentContainerStyle={styles.scrollContent}
          showsVerticalScrollIndicator={false}
        >
          {/* Search Bar */}
          <View style={styles.searchBarContainer}>
            <SearchIcon color="#94A3B8" size={18} />
            <TextInput
              style={styles.searchInput}
              placeholder="Search mechanics, OEM parts, towing..."
              placeholderTextColor="#94A3B8"
              value={searchQuery}
              onChangeText={setSearchQuery}
            />
          </View>

          {/* Category Filter Pills */}
          <ScrollView
            horizontal
            showsHorizontalScrollIndicator={false}
            style={styles.categoryScroll}
            contentContainerStyle={styles.categoryScrollContent}
          >
            {categories.map((cat) => (
              <TouchableOpacity
                key={cat}
                style={[
                  styles.categoryPill,
                  activeCategory === cat && styles.categoryPillActive,
                ]}
                activeOpacity={0.7}
                onPress={() => setActiveCategory(cat)}
              >
                <Text
                  style={[
                    styles.categoryText,
                    activeCategory === cat && styles.categoryTextActive,
                  ]}
                >
                  {cat}
                </Text>
              </TouchableOpacity>
            ))}
          </ScrollView>

          {/* Marketplace Banner */}
          <View style={styles.promoBanner}>
            <View style={styles.promoIconBox}>
              <ShieldCheckIcon color="#059669" size={24} />
            </View>
            <View style={styles.promoInfo}>
              <Text style={styles.promoTitle}>Verified Automotive Network</Text>
              <Text style={styles.promoSub}>
                Every listed mechanic and parts vendor is vetted for quality, transparent pricing, and warranty guarantee.
              </Text>
            </View>
          </View>

          {/* Vendors List */}
          <View style={styles.sectionHeader}>
            <Text style={styles.sectionTitle}>
              Verified Providers ({vendors.length})
            </Text>
            {loading && <ActivityIndicator color="#EA580C" size="small" />}
          </View>

          {vendors.length === 0 && !loading ? (
            <View style={styles.emptyStateCard}>
              <Text style={styles.emptyStateTitle}>No Providers Found</Text>
              <Text style={styles.emptyStateSub}>
                Try adjusting your search query or selecting a different service category.
              </Text>
            </View>
          ) : (
            <View style={styles.vendorsList}>
              {vendors.map((vendor) => (
                <View key={vendor.id} style={styles.vendorCard}>
                  <View style={styles.vendorCardHeader}>
                    <View style={styles.vendorMainInfo}>
                      <View style={styles.vendorTitleRow}>
                        <Text style={styles.vendorName} numberOfLines={1}>{vendor.name}</Text>
                        {vendor.isVerified && (
                          <View style={styles.verifiedBadge}>
                            <ShieldCheckIcon color="#059669" size={12} />
                            <Text style={styles.verifiedBadgeText}>Verified</Text>
                          </View>
                        )}
                      </View>

                      <Text style={styles.vendorLocation} numberOfLines={1}>
                        {vendor.location} • <Text style={styles.distanceHighlight}>{vendor.distance}</Text>
                      </Text>
                    </View>

                    <View style={styles.ratingBadge}>
                      <StarRatingIcon color="#F59E0B" size={12} />
                      <Text style={styles.ratingValue}>{vendor.rating}</Text>
                      <Text style={styles.reviewCount}>({vendor.reviewCount})</Text>
                    </View>
                  </View>

                  {/* Service Tags */}
                  <View style={styles.servicesRow}>
                    {vendor.services.map((srv, idx) => (
                      <View key={idx} style={styles.servicePill}>
                        <Text style={styles.servicePillText}>{srv}</Text>
                      </View>
                    ))}
                  </View>

                  {/* Footer & Booking Action */}
                  <View style={styles.vendorCardFooter}>
                    <View>
                      <Text style={styles.priceFromLabel}>From</Text>
                      <Text style={styles.priceValue}>₦{vendor.startingPrice.toLocaleString()}</Text>
                    </View>

                    <TouchableOpacity
                      style={styles.bookServiceBtn}
                      activeOpacity={0.8}
                      onPress={() => handleBookVendor(vendor)}
                    >
                      <Text style={styles.bookServiceBtnText}>Book Appointment</Text>
                    </TouchableOpacity>
                  </View>
                </View>
              ))}
            </View>
          )}
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
  searchBarContainer: {
    flexDirection: 'row',
    alignItems: 'center',
    paddingHorizontal: 14,
    height: 48,
    borderRadius: 16,
    backgroundColor: '#FFFFFF',
    borderWidth: 1,
    borderColor: '#E2E8F0',
    marginBottom: 14,
  },
  searchInput: {
    flex: 1,
    marginLeft: 10,
    fontSize: 13,
    fontFamily: 'Aeonik',
    color: '#111827',
  },
  categoryScroll: {
    marginBottom: 18,
  },
  categoryScrollContent: {
    gap: 8,
  },
  categoryPill: {
    paddingHorizontal: 14,
    paddingVertical: 8,
    borderRadius: 12,
    backgroundColor: '#FFFFFF',
    borderWidth: 1,
    borderColor: '#E2E8F0',
  },
  categoryPillActive: {
    backgroundColor: '#111827',
    borderColor: '#111827',
  },
  categoryText: {
    fontSize: 12,
    fontFamily: 'Aeonik',
    color: '#64748B',
    fontWeight: '600',
  },
  categoryTextActive: {
    color: '#FFFFFF',
    fontFamily: 'Helvetica',
    fontWeight: '700',
  },
  promoBanner: {
    flexDirection: 'row',
    alignItems: 'center',
    padding: 16,
    borderRadius: 18,
    backgroundColor: '#ECFDF5',
    borderWidth: 1,
    borderColor: '#D1FAE5',
    marginBottom: 20,
  },
  promoIconBox: {
    width: 44,
    height: 44,
    borderRadius: 12,
    backgroundColor: '#D1FAE5',
    alignItems: 'center',
    justifyContent: 'center',
    marginRight: 12,
  },
  promoInfo: {
    flex: 1,
  },
  promoTitle: {
    fontSize: 13,
    fontFamily: 'Helvetica',
    fontWeight: '700',
    color: '#065F46',
    marginBottom: 2,
  },
  promoSub: {
    fontSize: 11,
    fontFamily: 'Aeonik',
    color: '#047857',
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
  vendorsList: {
    gap: 14,
  },
  vendorCard: {
    padding: 16,
    borderRadius: 18,
    backgroundColor: '#FFFFFF',
    borderWidth: 1,
    borderColor: '#E2E8F0',
  },
  vendorCardHeader: {
    flexDirection: 'row',
    alignItems: 'flex-start',
    justifyContent: 'space-between',
    marginBottom: 10,
  },
  vendorMainInfo: {
    flex: 1,
    paddingRight: 8,
  },
  vendorTitleRow: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 6,
    marginBottom: 2,
  },
  vendorName: {
    fontSize: 15,
    fontFamily: 'Helvetica',
    fontWeight: '700',
    color: '#111827',
  },
  verifiedBadge: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 3,
    paddingHorizontal: 6,
    paddingVertical: 2,
    borderRadius: 6,
    backgroundColor: '#ECFDF5',
  },
  verifiedBadgeText: {
    fontSize: 9,
    fontFamily: 'Helvetica',
    fontWeight: '700',
    color: '#059669',
  },
  vendorLocation: {
    fontSize: 11,
    fontFamily: 'Aeonik',
    color: '#64748B',
  },
  distanceHighlight: {
    color: '#EA580C',
    fontWeight: '700',
  },
  ratingBadge: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 3,
    backgroundColor: '#FFFBEB',
    paddingHorizontal: 7,
    paddingVertical: 4,
    borderRadius: 8,
  },
  ratingValue: {
    fontSize: 11,
    fontFamily: 'Helvetica',
    fontWeight: '700',
    color: '#111827',
  },
  reviewCount: {
    fontSize: 10,
    fontFamily: 'Aeonik',
    color: '#94A3B8',
  },
  servicesRow: {
    flexDirection: 'row',
    flexWrap: 'wrap',
    gap: 6,
    marginBottom: 14,
  },
  servicePill: {
    paddingHorizontal: 8,
    paddingVertical: 3,
    borderRadius: 8,
    backgroundColor: '#F1F5F9',
  },
  servicePillText: {
    fontSize: 10,
    fontFamily: 'Aeonik',
    color: '#475569',
  },
  vendorCardFooter: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'space-between',
    paddingTop: 10,
    borderTopWidth: 1,
    borderTopColor: '#F1F5F9',
  },
  priceFromLabel: {
    fontSize: 10,
    fontFamily: 'Aeonik',
    color: '#94A3B8',
  },
  priceValue: {
    fontSize: 15,
    fontFamily: 'Helvetica',
    fontWeight: '800',
    color: '#111827',
  },
  bookServiceBtn: {
    paddingHorizontal: 16,
    paddingVertical: 10,
    borderRadius: 12,
    backgroundColor: '#111827',
  },
  bookServiceBtnText: {
    fontSize: 12,
    fontFamily: 'Helvetica',
    fontWeight: '700',
    color: '#FFFFFF',
  },
  emptyStateCard: {
    padding: 24,
    borderRadius: 16,
    backgroundColor: '#FFFFFF',
    borderWidth: 1,
    borderColor: '#E2E8F0',
    alignItems: 'center',
    marginTop: 12,
  },
  emptyStateTitle: {
    fontSize: 15,
    fontFamily: 'Helvetica',
    fontWeight: '700',
    color: '#111827',
    marginBottom: 4,
  },
  emptyStateSub: {
    fontSize: 12,
    fontFamily: 'Aeonik',
    color: '#64748B',
    textAlign: 'center',
    lineHeight: 18,
  },
});
