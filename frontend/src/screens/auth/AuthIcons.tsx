import React from 'react';
import { StyleSheet, TouchableOpacity, type StyleProp, type ViewStyle } from 'react-native';
import Svg, { Path } from 'react-native-svg';

/**
 * Eyelashes / Eye Toggle Icon matching the design
 */
export function PasswordEyeIcon({
  visible,
  color = '#94A3B8',
  size = 22,
}: {
  visible: boolean;
  color?: string;
  size?: number;
}): React.JSX.Element {
  if (visible) {
    // Open Eye Icon
    return (
      <Svg width={size} height={size} viewBox="0 0 24 24" fill="none">
        <Path
          d="M1 12C1 12 5 4 12 4C19 4 23 12 23 12C23 12 19 20 12 20C5 20 1 12 1 12Z"
          stroke={color}
          strokeWidth="1.8"
          strokeLinecap="round"
          strokeLinejoin="round"
        />
        <Path
          d="M12 15C13.6569 15 15 13.6569 15 12C15 10.3431 13.6569 9 12 9C10.3431 9 9 10.3431 9 12C9 13.6569 10.3431 15 12 15Z"
          stroke={color}
          strokeWidth="1.8"
          strokeLinecap="round"
          strokeLinejoin="round"
        />
      </Svg>
    );
  }

  // Eyelashes (Closed Eye) matching the exact screenshot shape
  return (
    <Svg width={size} height={size} viewBox="0 0 24 24" fill="none">
      <Path
        d="M3 9C6 14.5 18 14.5 21 9"
        stroke={color}
        strokeWidth="1.8"
        strokeLinecap="round"
      />
      <Path d="M7 13.5L5 17" stroke={color} strokeWidth="1.6" strokeLinecap="round" />
      <Path d="M12 14.5L12 18.5" stroke={color} strokeWidth="1.6" strokeLinecap="round" />
      <Path d="M17 13.5L19 17" stroke={color} strokeWidth="1.6" strokeLinecap="round" />
    </Svg>
  );
}

/**
 * Checkbox Component
 */
export function Checkbox({
  checked,
  onChange,
  style,
}: {
  checked: boolean;
  onChange: (checked: boolean) => void;
  style?: StyleProp<ViewStyle>;
}): React.JSX.Element {
  return (
    <TouchableOpacity
      activeOpacity={0.8}
      onPress={() => onChange(!checked)}
      style={[
        styles.checkbox,
        checked ? styles.checkboxChecked : styles.checkboxUnchecked,
        style,
      ]}
    >
      {checked && (
        <Svg width={12} height={12} viewBox="0 0 12 12" fill="none">
          <Path
            d="M2.5 6.5L4.8 8.8L9.5 3.5"
            stroke="#FFFFFF"
            strokeWidth="1.8"
            strokeLinecap="round"
            strokeLinejoin="round"
          />
        </Svg>
      )}
    </TouchableOpacity>
  );
}

/**
 * Backspace / Delete Icon for Custom Keypad
 */
export function BackspaceIcon({
  color = '#1E2538',
  size = 22,
}: {
  color?: string;
  size?: number;
}): React.JSX.Element {
  return (
    <Svg width={size} height={size} viewBox="0 0 24 24" fill="none">
      <Path
        d="M21 4H8L2 12L8 20H21C21.5304 20 22.0391 19.7893 22.4142 19.4142C22.7893 19.0391 23 18.5304 23 18V6C23 5.46957 22.7893 4.96086 22.4142 4.58579C22.0391 4.21071 21.5304 4 21 4Z"
        stroke={color}
        strokeWidth="1.8"
        strokeLinecap="round"
        strokeLinejoin="round"
      />
      <Path
        d="M18 9L12 15M12 9L18 15"
        stroke={color}
        strokeWidth="1.8"
        strokeLinecap="round"
        strokeLinejoin="round"
      />
    </Svg>
  );
}

const styles = StyleSheet.create({
  checkbox: {
    width: 18,
    height: 18,
    borderRadius: 4,
    alignItems: 'center',
    justifyContent: 'center',
  },
  checkboxChecked: {
    backgroundColor: '#1E2538',
  },
  checkboxUnchecked: {
    backgroundColor: '#FFFFFF',
    borderWidth: 1.5,
    borderColor: '#CBD5E1',
  },
});
