import React, { useState } from 'react';
import {
  Alert,
  Modal,
  ScrollView,
  StyleSheet,
  Text,
  TouchableOpacity,
  View,
} from 'react-native';
import { SafeAreaView } from 'react-native-safe-area-context';

import {
  CalendarIcon,
  ChevronLeftIcon,
  ChevronRightIcon,
  ClockIcon,
} from '../components/HomeIcons';

type DatePreset = 'Today' | 'Yesterday' | 'Last 7 Days';

interface ReportTypeOption {
  id: string;
  title: string;
  subtitle: string;
}

const REPORT_TYPES: ReportTypeOption[] = [
  {
    id: 'route',
    title: 'Route Report',
    subtitle: 'View detailed route history',
  },
  {
    id: 'trips',
    title: 'Trips Report',
    subtitle: 'Analyze trip statistics',
  },
  {
    id: 'stop',
    title: 'Stop Report',
    subtitle: 'View all stop locations',
  },
  {
    id: 'events',
    title: 'Events Report',
    subtitle: 'Track device event',
  },
  {
    id: 'summary',
    title: 'Summary Report',
    subtitle: 'Overview Statistics',
  },
];

const DEVICES = ['Toyota Corolla', 'Active Demo', 'Dashcam'];

interface ReportsScreenProps {
  onOpenEventReport?: () => void;
}

export function ReportsScreen({ onOpenEventReport }: ReportsScreenProps): React.JSX.Element {
  const [activePreset, setActivePreset] = useState<DatePreset>('Today');
  const [selectedReportType, setSelectedReportType] = useState<string>('route');
  const [selectedDevices, setSelectedDevices] = useState<string[]>(['Toyota Corolla']);
  const [dateModalVisible, setDateModalVisible] = useState(false);

  // Calendar State
  const [selectedStartDay, setSelectedStartDay] = useState<number>(6);
  const [selectedEndDay, setSelectedEndDay] = useState<number>(22);
  const [activeTimeSlot, setActiveTimeSlot] = useState<'start' | 'end'>('start');

  const customRangeText = '8/2/2026 00:00 - 8/2/2026 23:56';

  const toggleDevice = (dev: string) => {
    setSelectedDevices((prev) =>
      prev.includes(dev) ? prev.filter((d) => d !== dev) : [...prev, dev],
    );
  };

  const handleGenerateReport = () => {
    if (selectedReportType === 'events' && onOpenEventReport) {
      onOpenEventReport();
    } else {
      Alert.alert(
        'Generate Report',
        `Generating ${REPORT_TYPES.find((r) => r.id === selectedReportType)?.title} for ${selectedDevices.join(', ')} (${activePreset}).`,
      );
    }
  };

  return (
    <SafeAreaView style={styles.safeArea} edges={['top', 'left', 'right']}>
      <View style={styles.container}>
        {/* Centered Top Title */}
        <View style={styles.header}>
          <Text style={styles.headerTitle}>Reports</Text>
        </View>

        <ScrollView
          style={styles.scrollContainer}
          contentContainerStyle={styles.scrollContent}
          showsVerticalScrollIndicator={false}
        >
          {/* Section 1: Date Range Presets */}
          <View style={styles.section}>
            <Text style={styles.sectionLabel}>Date Range</Text>
            <View style={styles.presetToggleContainer}>
              {(['Today', 'Yesterday', 'Last 7 Days'] as DatePreset[]).map((preset) => {
                const isActive = activePreset === preset;
                return (
                  <TouchableOpacity
                    key={preset}
                    style={[styles.presetBtn, isActive && styles.presetBtnActive]}
                    onPress={() => setActivePreset(preset)}
                    activeOpacity={0.8}
                  >
                    <Text style={[styles.presetText, isActive && styles.presetTextActive]}>
                      {preset}
                    </Text>
                  </TouchableOpacity>
                );
              })}
            </View>
          </View>

          {/* Section 2: Custom Date Range Input */}
          <View style={styles.section}>
            <Text style={styles.sectionLabel}>Custom Date Range</Text>
            <TouchableOpacity
              style={styles.customDateBox}
              onPress={() => setDateModalVisible(true)}
              activeOpacity={0.75}
            >
              <View style={styles.customDateLeft}>
                <CalendarIcon color="#94A3B8" size={16} />
                <Text style={styles.customDateText}>{customRangeText}</Text>
              </View>
              <ChevronRightIcon color="#94A3B8" size={16} />
            </TouchableOpacity>
          </View>

          {/* Section 3: Report Type (Radio List) */}
          <View style={styles.section}>
            <Text style={styles.sectionLabel}>Report Type</Text>

            <View style={styles.reportTypeList}>
              {REPORT_TYPES.map((type) => {
                const isSelected = selectedReportType === type.id;
                return (
                  <TouchableOpacity
                    key={type.id}
                    style={styles.reportTypeItem}
                    onPress={() => setSelectedReportType(type.id)}
                    activeOpacity={0.7}
                  >
                    <View style={styles.reportTypeInfo}>
                      <Text style={styles.reportTypeTitle}>{type.title}</Text>
                      <Text style={styles.reportTypeSubtitle}>{type.subtitle}</Text>
                    </View>

                    {/* Radio Button */}
                    <View
                      style={[
                        styles.radioOuter,
                        isSelected && styles.radioOuterSelected,
                      ]}
                    >
                      {isSelected && <View style={styles.radioInner} />}
                    </View>
                  </TouchableOpacity>
                );
              })}
            </View>
          </View>

          {/* Section 4: Select Devices */}
          <View style={styles.section}>
            <Text style={styles.sectionLabel}>Select Devices</Text>
            <View style={styles.devicesRow}>
              {DEVICES.map((dev) => {
                const isSelected = selectedDevices.includes(dev);
                return (
                  <TouchableOpacity
                    key={dev}
                    style={[styles.devicePill, isSelected && styles.devicePillSelected]}
                    onPress={() => toggleDevice(dev)}
                    activeOpacity={0.75}
                  >
                    <Text
                      style={[styles.devicePillText, isSelected && styles.devicePillTextSelected]}
                    >
                      {dev}
                    </Text>
                  </TouchableOpacity>
                );
              })}
            </View>
          </View>

          {/* Generate Report Button (Bottom-Right aligned) */}
          <View style={styles.generateBtnContainer}>
            <TouchableOpacity
              style={styles.generateBtn}
              onPress={handleGenerateReport}
              activeOpacity={0.85}
            >
              <Text style={styles.generateBtnText}>Generate Report</Text>
            </TouchableOpacity>
          </View>
        </ScrollView>

        {/* Date / Time Picker Modal (Screen 2) */}
        <Modal
          visible={dateModalVisible}
          transparent
          animationType="slide"
          onRequestClose={() => setDateModalVisible(false)}
        >
          <View style={styles.modalBackdrop}>
            <TouchableOpacity
              style={styles.modalDismissArea}
              activeOpacity={1}
              onPress={() => setDateModalVisible(false)}
            />

            <View style={styles.calendarCard}>
              {/* Drag Handle */}
              <View style={styles.dragHandle} />

              {/* Time Label */}
              <Text style={styles.timeLabel}>Time</Text>

              {/* Time Chips Row */}
              <View style={styles.timeChipsRow}>
                <TouchableOpacity
                  style={[
                    styles.timeChip,
                    activeTimeSlot === 'start' && styles.timeChipActive,
                  ]}
                  onPress={() => setActiveTimeSlot('start')}
                  activeOpacity={0.8}
                >
                  <View
                    style={[
                      styles.timeClockCircle,
                      activeTimeSlot === 'start' && styles.timeClockCircleActive,
                    ]}
                  >
                    <ClockIcon
                      color={activeTimeSlot === 'start' ? '#1E2538' : '#94A3B8'}
                      size={13}
                    />
                  </View>
                  <View
                    style={[
                      styles.timeDottedDivider,
                      activeTimeSlot === 'start' && styles.timeDottedDividerActive,
                    ]}
                  />
                  <Text
                    style={[
                      styles.timeChipText,
                      activeTimeSlot === 'start' && styles.timeChipTextActive,
                    ]}
                  >
                    10 : 30  am
                  </Text>
                </TouchableOpacity>

                <TouchableOpacity
                  style={[
                    styles.timeChip,
                    activeTimeSlot === 'end' && styles.timeChipActive,
                  ]}
                  onPress={() => setActiveTimeSlot('end')}
                  activeOpacity={0.8}
                >
                  <View
                    style={[
                      styles.timeClockCircle,
                      activeTimeSlot === 'end' && styles.timeClockCircleActive,
                    ]}
                  >
                    <ClockIcon
                      color={activeTimeSlot === 'end' ? '#1E2538' : '#94A3B8'}
                      size={13}
                    />
                  </View>
                  <View
                    style={[
                      styles.timeDottedDivider,
                      activeTimeSlot === 'end' && styles.timeDottedDividerActive,
                    ]}
                  />
                  <Text
                    style={[
                      styles.timeChipText,
                      activeTimeSlot === 'end' && styles.timeChipTextActive,
                    ]}
                  >
                    05 : 30  pm
                  </Text>
                </TouchableOpacity>
              </View>

              {/* Month Navigation */}
              <View style={styles.monthNavRow}>
                <TouchableOpacity activeOpacity={0.6}>
                  <ChevronLeftIcon color="#1E2538" size={18} />
                </TouchableOpacity>

                <Text style={styles.monthNavTitle}>January 2022</Text>

                <TouchableOpacity activeOpacity={0.6}>
                  <ChevronRightIcon color="#1E2538" size={18} />
                </TouchableOpacity>
              </View>

              {/* Days of Week Header */}
              <View style={styles.daysHeaderRow}>
                {['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'].map((d) => (
                  <Text key={d} style={styles.dayHeaderText}>
                    {d}
                  </Text>
                ))}
              </View>

              {/* Monthly Calendar Grid */}
              <View style={styles.calendarGrid}>
                {/* Previous month trailing days */}
                {[26, 27, 28, 29, 30, 31].map((d) => (
                  <View key={`prev-${d}`} style={styles.dayCell}>
                    <Text style={styles.dayTextMuted}>{d}</Text>
                  </View>
                ))}

                {/* Day 1 */}
                <View style={styles.dayCell}>
                  <Text style={styles.dayText}>01</Text>
                </View>

                {/* Days 2 to 5 */}
                {[2, 3, 4, 5].map((d) => (
                  <TouchableOpacity
                    key={`curr-${d}`}
                    style={styles.dayCell}
                    onPress={() => setSelectedStartDay(d)}
                  >
                    <Text style={styles.dayText}>{d}</Text>
                  </TouchableOpacity>
                ))}

                {/* Day 6 (Selected Start) */}
                <TouchableOpacity
                  style={[
                    styles.dayCell,
                    selectedStartDay === 6 && styles.dayCellSelected,
                  ]}
                  onPress={() => setSelectedStartDay(6)}
                >
                  <Text
                    style={[
                      styles.dayText,
                      selectedStartDay === 6 && styles.dayTextSelected,
                    ]}
                  >
                    6
                  </Text>
                </TouchableOpacity>

                {/* Days 7 to 18 */}
                {[7, 8, 9, 10, 11, 12, 13, 14, 15, 16, 17, 18].map((d) => (
                  <TouchableOpacity
                    key={`curr-${d}`}
                    style={styles.dayCell}
                    onPress={() => setSelectedStartDay(d)}
                  >
                    <Text style={styles.dayText}>{d}</Text>
                  </TouchableOpacity>
                ))}

                {/* Day 19 (Selected Range) */}
                <TouchableOpacity
                  style={[styles.dayCell, styles.dayCellSelected]}
                  onPress={() => setSelectedEndDay(19)}
                >
                  <Text style={[styles.dayText, styles.dayTextSelected]}>19</Text>
                </TouchableOpacity>

                {/* Days 20, 21 */}
                {[20, 21].map((d) => (
                  <View key={`curr-${d}`} style={styles.dayCell}>
                    <Text style={styles.dayText}>{d}</Text>
                  </View>
                ))}

                {/* Day 22 (Selected End) */}
                <TouchableOpacity
                  style={[
                    styles.dayCell,
                    selectedEndDay === 22 && styles.dayCellSelected,
                  ]}
                  onPress={() => setSelectedEndDay(22)}
                >
                  <Text
                    style={[
                      styles.dayText,
                      selectedEndDay === 22 && styles.dayTextSelected,
                    ]}
                  >
                    22
                  </Text>
                </TouchableOpacity>

                {/* Days 23 to 31 */}
                {[23, 24, 25, 26, 27, 28, 29, 31].map((d) => (
                  <TouchableOpacity
                    key={`curr-${d}`}
                    style={styles.dayCell}
                    onPress={() => setSelectedEndDay(d)}
                  >
                    <Text style={styles.dayText}>{d}</Text>
                  </TouchableOpacity>
                ))}
              </View>

              {/* Modal Actions */}
              <View style={styles.modalActionsRow}>
                <TouchableOpacity
                  style={styles.cancelBtn}
                  onPress={() => setDateModalVisible(false)}
                  activeOpacity={0.7}
                >
                  <Text style={styles.cancelBtnText}>Cancel</Text>
                </TouchableOpacity>

                <TouchableOpacity
                  style={styles.doneBtn}
                  onPress={() => setDateModalVisible(false)}
                  activeOpacity={0.8}
                >
                  <Text style={styles.doneBtnText}>Done</Text>
                </TouchableOpacity>
              </View>
            </View>
          </View>
        </Modal>
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
    backgroundColor: '#FFFFFF',
  },
  header: {
    alignItems: 'center',
    justifyContent: 'center',
    paddingVertical: 14,
  },
  headerTitle: {
    fontFamily: 'Helvetica',
    fontSize: 18,
    fontWeight: '700',
    color: '#1E2538',
  },
  scrollContainer: {
    flex: 1,
  },
  scrollContent: {
    paddingHorizontal: 20,
    paddingTop: 8,
    paddingBottom: 110,
  },
  section: {
    marginBottom: 20,
  },
  sectionLabel: {
    fontFamily: 'Helvetica',
    fontSize: 14,
    fontWeight: '700',
    color: '#1E2538',
    marginBottom: 10,
  },
  presetToggleContainer: {
    flexDirection: 'row',
    alignItems: 'center',
    backgroundColor: '#F1F4F9',
    borderRadius: 12,
    padding: 3,
  },
  presetBtn: {
    flex: 1,
    paddingVertical: 9,
    alignItems: 'center',
    justifyContent: 'center',
    borderRadius: 10,
  },
  presetBtnActive: {
    backgroundColor: '#1E2538',
  },
  presetText: {
    fontFamily: 'Aeonik',
    fontSize: 12,
    fontWeight: '500',
    color: '#64748B',
  },
  presetTextActive: {
    color: '#FFFFFF',
    fontWeight: '600',
  },
  customDateBox: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'space-between',
    borderRadius: 12,
    borderWidth: 1,
    borderColor: '#E2E8F0',
    paddingHorizontal: 14,
    height: 48,
    backgroundColor: '#FFFFFF',
  },
  customDateLeft: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 8,
  },
  customDateText: {
    fontFamily: 'Aeonik',
    fontSize: 12.5,
    color: '#64748B',
  },
  reportTypeList: {
    backgroundColor: '#FFFFFF',
  },
  reportTypeItem: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'space-between',
    paddingVertical: 14,
    borderBottomWidth: 1,
    borderBottomColor: '#F1F5F9',
  },
  reportTypeInfo: {
    flex: 1,
  },
  reportTypeTitle: {
    fontFamily: 'Helvetica',
    fontSize: 14,
    fontWeight: '700',
    color: '#1E2538',
    marginBottom: 2,
  },
  reportTypeSubtitle: {
    fontFamily: 'Aeonik',
    fontSize: 11.5,
    color: '#94A3B8',
  },
  radioOuter: {
    width: 22,
    height: 22,
    borderRadius: 11,
    borderWidth: 1.8,
    borderColor: '#E2E8F0',
    alignItems: 'center',
    justifyContent: 'center',
  },
  radioOuterSelected: {
    borderColor: '#DE8635',
  },
  radioInner: {
    width: 12,
    height: 12,
    borderRadius: 6,
    backgroundColor: '#DE8635',
  },
  devicesRow: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 8,
  },
  devicePill: {
    flex: 1,
    paddingHorizontal: 4,
    paddingVertical: 9,
    borderRadius: 10,
    backgroundColor: '#FFFFFF',
    borderWidth: 1,
    borderColor: '#CBD5E1',
    alignItems: 'center',
    justifyContent: 'center',
  },
  devicePillSelected: {
    borderColor: '#1E2538',
    backgroundColor: '#FFFFFF',
  },
  devicePillText: {
    fontFamily: 'Aeonik',
    fontSize: 11.5,
    fontWeight: '500',
    color: '#64748B',
    textAlign: 'center',
  },
  devicePillTextSelected: {
    color: '#1E2538',
    fontWeight: '600',
  },
  generateBtnContainer: {
    alignItems: 'flex-end',
    marginTop: 10,
    marginBottom: 20,
  },
  generateBtn: {
    backgroundColor: '#1E2538',
    paddingVertical: 12,
    paddingHorizontal: 24,
    borderRadius: 22,
    shadowColor: '#000000',
    shadowOffset: { width: 0, height: 2 },
    shadowOpacity: 0.1,
    shadowRadius: 4,
    elevation: 3,
  },
  generateBtnText: {
    fontFamily: 'Aeonik',
    fontSize: 13,
    fontWeight: '600',
    color: '#FFFFFF',
  },
  modalBackdrop: {
    flex: 1,
    backgroundColor: 'rgba(0, 0, 0, 0.55)',
    justifyContent: 'flex-end',
  },
  modalDismissArea: {
    flex: 1,
  },
  dragHandle: {
    width: 40,
    height: 4,
    borderRadius: 2,
    backgroundColor: '#E2E8F0',
    alignSelf: 'center',
    marginBottom: 16,
  },
  calendarCard: {
    width: '100%',
    backgroundColor: '#FFFFFF',
    borderTopLeftRadius: 28,
    borderTopRightRadius: 28,
    paddingHorizontal: 20,
    paddingTop: 16,
    paddingBottom: 32,
    shadowColor: '#000000',
    shadowOffset: { width: 0, height: -4 },
    shadowOpacity: 0.15,
    shadowRadius: 16,
    elevation: 20,
  },
  timeLabel: {
    fontFamily: 'Helvetica',
    fontSize: 14,
    fontWeight: '700',
    color: '#1E2538',
    marginBottom: 10,
  },
  timeChipsRow: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 12,
    marginBottom: 18,
  },
  timeChip: {
    flex: 1,
    flexDirection: 'row',
    alignItems: 'center',
    backgroundColor: '#FFFFFF',
    borderWidth: 1,
    borderColor: '#E2E8F0',
    paddingVertical: 8,
    paddingHorizontal: 10,
    borderRadius: 12,
    gap: 8,
  },
  timeChipActive: {
    backgroundColor: '#1E2538',
    borderColor: '#1E2538',
  },
  timeClockCircle: {
    width: 24,
    height: 24,
    borderRadius: 12,
    backgroundColor: '#F8FAFC',
    alignItems: 'center',
    justifyContent: 'center',
    borderWidth: 1,
    borderColor: '#E2E8F0',
  },
  timeClockCircleActive: {
    backgroundColor: '#FFFFFF',
    borderColor: '#FFFFFF',
  },
  timeDottedDivider: {
    width: 1,
    height: 18,
    borderLeftWidth: 1.5,
    borderStyle: 'dotted',
    borderColor: '#CBD5E1',
  },
  timeDottedDividerActive: {
    borderColor: 'rgba(255, 255, 255, 0.4)',
  },
  timeChipText: {
    fontFamily: 'Aeonik',
    fontSize: 12.5,
    fontWeight: '700',
    color: '#1E2538',
    flex: 1,
    textAlign: 'center',
  },
  timeChipTextActive: {
    color: '#FFFFFF',
  },
  monthNavRow: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'space-between',
    paddingHorizontal: 10,
    marginBottom: 14,
  },
  monthNavTitle: {
    fontFamily: 'Helvetica',
    fontSize: 15,
    fontWeight: '700',
    color: '#1E2538',
  },
  daysHeaderRow: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'space-around',
    marginBottom: 10,
  },
  dayHeaderText: {
    fontFamily: 'Aeonik',
    fontSize: 11,
    fontWeight: '600',
    color: '#94A3B8',
    width: 36,
    textAlign: 'center',
  },
  calendarGrid: {
    flexDirection: 'row',
    flexWrap: 'wrap',
    justifyContent: 'space-around',
  },
  dayCell: {
    width: 36,
    height: 36,
    alignItems: 'center',
    justifyContent: 'center',
    marginVertical: 2,
    borderRadius: 18,
  },
  dayCellSelected: {
    backgroundColor: '#1E2538',
  },
  dayText: {
    fontFamily: 'Aeonik',
    fontSize: 12,
    fontWeight: '500',
    color: '#1E2538',
  },
  dayTextMuted: {
    fontFamily: 'Aeonik',
    fontSize: 12,
    color: '#CBD5E1',
  },
  dayTextSelected: {
    color: '#FFFFFF',
    fontWeight: '700',
  },
  modalActionsRow: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'space-between',
    marginTop: 18,
    gap: 12,
  },
  cancelBtn: {
    flex: 1,
    paddingVertical: 10,
    borderRadius: 12,
    borderWidth: 1,
    borderColor: '#E2E8F0',
    alignItems: 'center',
    justifyContent: 'center',
  },
  cancelBtnText: {
    fontFamily: 'Aeonik',
    fontSize: 13,
    fontWeight: '600',
    color: '#64748B',
  },
  doneBtn: {
    flex: 1,
    paddingVertical: 10,
    borderRadius: 12,
    backgroundColor: '#1E2538',
    alignItems: 'center',
    justifyContent: 'center',
  },
  doneBtnText: {
    fontFamily: 'Aeonik',
    fontSize: 13,
    fontWeight: '600',
    color: '#FFFFFF',
  },
});
