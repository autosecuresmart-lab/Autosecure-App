import React from 'react';
import Svg, { Path, Circle, Rect } from 'react-native-svg';

interface IconProps {
  color?: string;
  size?: number;
}

export function BellIcon({ color = '#1E2538', size = 22 }: IconProps): React.JSX.Element {
  return (
    <Svg width={size} height={size} viewBox="0 0 24 24" fill="none">
      <Path
        d="M18 8A6 6 0 0 0 6 8C6 15 3 17 3 17H21S18 15 18 8Z"
        stroke={color}
        strokeWidth="1.8"
        strokeLinecap="round"
        strokeLinejoin="round"
      />
      <Path
        d="M13.73 21A2 2 0 0 1 10.27 21"
        stroke={color}
        strokeWidth="1.8"
        strokeLinecap="round"
        strokeLinejoin="round"
      />
    </Svg>
  );
}

export function SearchIcon({ color = '#94A3B8', size = 20 }: IconProps): React.JSX.Element {
  return (
    <Svg width={size} height={size} viewBox="0 0 24 24" fill="none">
      <Circle cx="11" cy="11" r="8" stroke={color} strokeWidth="1.8" />
      <Path d="M21 21L16.65 16.65" stroke={color} strokeWidth="1.8" strokeLinecap="round" />
    </Svg>
  );
}

export function FilterSlidersIcon({ color = '#1E2538', size = 20 }: IconProps): React.JSX.Element {
  return (
    <Svg width={size} height={size} viewBox="0 0 24 24" fill="none">
      <Path d="M4 21V14M4 10V3M12 21V12M12 8V3M20 21V16M20 12V3" stroke={color} strokeWidth="1.8" strokeLinecap="round" />
      <Circle cx="4" cy="12" r="2" fill="#FFFFFF" stroke={color} strokeWidth="1.8" />
      <Circle cx="12" cy="10" r="2" fill="#FFFFFF" stroke={color} strokeWidth="1.8" />
      <Circle cx="20" cy="14" r="2" fill="#FFFFFF" stroke={color} strokeWidth="1.8" />
    </Svg>
  );
}

export function PinIcon({ color = '#DE8635', size = 16 }: IconProps): React.JSX.Element {
  return (
    <Svg width={size} height={size} viewBox="0 0 24 24" fill="none">
      <Path
        d="M21 10C21 17 12 23 12 23C12 23 3 17 3 10C3 5.02944 7.02944 1 12 1C16.9706 1 21 5.02944 21 10Z"
        stroke={color}
        strokeWidth="1.8"
      />
      <Circle cx="12" cy="10" r="3" stroke={color} strokeWidth="1.8" />
    </Svg>
  );
}

export function SpeedArrowIcon({ color = '#10B981', size = 14 }: IconProps): React.JSX.Element {
  return (
    <Svg width={size} height={size} viewBox="0 0 24 24" fill="none">
      <Path d="M12 19V5M5 12L12 5L19 12" stroke={color} strokeWidth="2.2" strokeLinecap="round" strokeLinejoin="round" />
    </Svg>
  );
}

export function BackArrowIcon({ color = '#1E2538', size = 24 }: IconProps): React.JSX.Element {
  return (
    <Svg width={size} height={size} viewBox="0 0 24 24" fill="none">
      <Path d="M15 18L9 12L15 6" stroke={color} strokeWidth="2.2" strokeLinecap="round" strokeLinejoin="round" />
    </Svg>
  );
}

export function CloseIcon({ color = '#FFFFFF', size = 22 }: IconProps): React.JSX.Element {
  return (
    <Svg width={size} height={size} viewBox="0 0 24 24" fill="none">
      <Path d="M18 6L6 18M6 6L18 18" stroke={color} strokeWidth="2" strokeLinecap="round" strokeLinejoin="round" />
    </Svg>
  );
}

export function FlashIcon({ color = '#FFFFFF', size = 22 }: IconProps): React.JSX.Element {
  return (
    <Svg width={size} height={size} viewBox="0 0 24 24" fill="none">
      <Path d="M13 2L3 14H12L11 22L21 10H12L13 2Z" stroke={color} strokeWidth="1.8" strokeLinecap="round" strokeLinejoin="round" />
    </Svg>
  );
}

/* Vehicle Control Grid Icons */

export function LiveTrackIcon({ color = '#1E2538', size = 26 }: IconProps): React.JSX.Element {
  return (
    <Svg width={size} height={size} viewBox="0 0 24 24" fill="none">
      <Circle cx="12" cy="12" r="9" stroke={color} strokeWidth="1.8" strokeDasharray="3 3" />
      <Path d="M12 2V6M12 18V22M2 12H6M18 12H22" stroke={color} strokeWidth="1.8" strokeLinecap="round" />
      <Circle cx="12" cy="12" r="3" fill={color} />
    </Svg>
  );
}

export function ShareLocationIcon({ color = '#1E2538', size = 26 }: IconProps): React.JSX.Element {
  return (
    <Svg width={size} height={size} viewBox="0 0 24 24" fill="none">
      <Circle cx="18" cy="5" r="3" stroke={color} strokeWidth="1.8" />
      <Circle cx="6" cy="12" r="3" stroke={color} strokeWidth="1.8" />
      <Circle cx="18" cy="19" r="3" stroke={color} strokeWidth="1.8" />
      <Path d="M8.59 13.51L15.42 17.49M15.41 6.51L8.59 10.49" stroke={color} strokeWidth="1.8" />
    </Svg>
  );
}

export function CallVehicleIcon({ color = '#1E2538', size = 26 }: IconProps): React.JSX.Element {
  return (
    <Svg width={size} height={size} viewBox="0 0 24 24" fill="none">
      <Path
        d="M22 16.92V19.92C22 20.48 21.54 20.94 20.98 20.92C10.43 20.41 3.59 13.57 3.08 3.02C3.06 2.46 3.52 2 4.08 2H7.08C7.58 2 8 2.37 8.08 2.86C8.26 4.02 8.62 5.14 9.15 6.17C9.33 6.52 9.24 6.94 8.94 7.24L7.42 8.76C9.17 12.01 11.99 14.83 15.24 16.58L16.76 15.06C17.06 14.76 17.48 14.67 17.83 14.85C18.86 15.38 19.98 15.74 21.14 15.92C21.63 16 22 16.42 22 16.92Z"
        stroke={color}
        strokeWidth="1.8"
        strokeLinecap="round"
        strokeLinejoin="round"
      />
    </Svg>
  );
}

export function PlaybackIcon({ color = '#1E2538', size = 26 }: IconProps): React.JSX.Element {
  return (
    <Svg width={size} height={size} viewBox="0 0 24 24" fill="none">
      <Circle cx="12" cy="12" r="9" stroke={color} strokeWidth="1.8" />
      <Path d="M10 8L16 12L10 16V8Z" fill={color} />
    </Svg>
  );
}

export function InfoIcon({ color = '#1E2538', size = 26 }: IconProps): React.JSX.Element {
  return (
    <Svg width={size} height={size} viewBox="0 0 24 24" fill="none">
      <Circle cx="12" cy="12" r="9" stroke={color} strokeWidth="1.8" />
      <Path d="M12 8V8.01M12 11V16" stroke={color} strokeWidth="2" strokeLinecap="round" />
    </Svg>
  );
}

export function ReportStolenIcon({ color = '#EF4444', size = 26 }: IconProps): React.JSX.Element {
  return (
    <Svg width={size} height={size} viewBox="0 0 24 24" fill="none">
      <Path
        d="M12 22S20 18 20 12V5L12 2L4 5V12C4 18 12 22 12 22Z"
        stroke={color}
        strokeWidth="1.8"
        strokeLinecap="round"
        strokeLinejoin="round"
      />
      <Path d="M12 8V12M12 16V16.01" stroke={color} strokeWidth="2" strokeLinecap="round" />
    </Svg>
  );
}

export function SwitchEngineIcon({ color = '#1E2538', size = 26 }: IconProps): React.JSX.Element {
  return (
    <Svg width={size} height={size} viewBox="0 0 24 24" fill="none">
      <Circle cx="12" cy="12" r="9" stroke={color} strokeWidth="1.8" />
      <Path d="M12 7V12M8 9A6 6 0 1 0 16 9" stroke={color} strokeWidth="2" strokeLinecap="round" />
    </Svg>
  );
}

export function GeoFenceIcon({ color = '#1E2538', size = 26 }: IconProps): React.JSX.Element {
  return (
    <Svg width={size} height={size} viewBox="0 0 24 24" fill="none">
      <Path
        d="M12 2L21 7V17L12 22L3 17V7L12 2Z"
        stroke={color}
        strokeWidth="1.8"
        strokeLinecap="round"
        strokeLinejoin="round"
      />
      <Circle cx="12" cy="12" r="3" stroke={color} strokeWidth="1.8" />
    </Svg>
  );
}

export function CustomCommandIcon({ color = '#1E2538', size = 26 }: IconProps): React.JSX.Element {
  return (
    <Svg width={size} height={size} viewBox="0 0 24 24" fill="none">
      <Rect x="3" y="4" width="18" height="16" rx="3" stroke={color} strokeWidth="1.8" />
      <Path d="M7 8L11 12L7 16M13 16H17" stroke={color} strokeWidth="1.8" strokeLinecap="round" strokeLinejoin="round" />
    </Svg>
  );
}

/* Floating Bottom Tab Icons matching design screenshot */

export function TabHomeIcon({ active, size = 22 }: { active: boolean; size?: number }): React.JSX.Element {
  const color = active ? '#FFFFFF' : '#707D8E';
  return (
    <Svg width={size} height={size} viewBox="0 0 24 24" fill="none">
      <Path
        d="M4 10.5L10.8 4.6C11.5 4 12.5 4 13.2 4.6L20 10.5C20.6 11 21 11.8 21 12.6V18C21 19.4 19.9 20.5 18.5 20.5H5.5C4.1 20.5 3 19.4 3 18V12.6C3 11.8 3.4 11 4 10.5Z"
        stroke={color}
        strokeWidth="1.8"
        strokeLinecap="round"
        strokeLinejoin="round"
        fill={active ? 'rgba(255, 255, 255, 0.15)' : 'none'}
      />
      <Path
        d="M10 20.5V15.5C10 14.4 10.9 13.5 12 13.5C13.1 13.5 14 14.4 14 15.5V20.5"
        stroke={color}
        strokeWidth="1.8"
        strokeLinecap="round"
        strokeLinejoin="round"
      />
    </Svg>
  );
}

export function TabMapIcon({ active, size = 22 }: { active: boolean; size?: number }): React.JSX.Element {
  const color = active ? '#FFFFFF' : '#707D8E';
  return (
    <Svg width={size} height={size} viewBox="0 0 24 24" fill="none">
      <Path
        d="M9 18.5L3 21.5V5.5L9 2.5M9 18.5L15 21.5M9 18.5V2.5M15 21.5L21 18.5V2.5L15 5.5M15 21.5V5.5M15 5.5L9 2.5"
        stroke={color}
        strokeWidth="1.8"
        strokeLinecap="round"
        strokeLinejoin="round"
        fill={active ? 'rgba(255, 255, 255, 0.15)' : 'none'}
      />
    </Svg>
  );
}

export function TabChartIcon({ active, size = 22 }: { active: boolean; size?: number }): React.JSX.Element {
  const color = active ? '#FFFFFF' : '#707D8E';
  return (
    <Svg width={size} height={size} viewBox="0 0 24 24" fill="none">
      <Rect
        x="3"
        y="3"
        width="18"
        height="18"
        rx="5"
        stroke={color}
        strokeWidth="1.8"
        fill={active ? 'rgba(255, 255, 255, 0.15)' : 'none'}
      />
      <Path
        d="M8 16V13M12 16V10M16 16V7"
        stroke={color}
        strokeWidth="2"
        strokeLinecap="round"
      />
    </Svg>
  );
}

export function TabPieIcon({ active, size = 22 }: { active: boolean; size?: number }): React.JSX.Element {
  const color = active ? '#FFFFFF' : '#707D8E';
  return (
    <Svg width={size} height={size} viewBox="0 0 24 24" fill="none">
      <Path
        d="M11 3.05C6.5 3.55 3 7.37 3 12C3 16.97 7.03 21 12 21C16.63 21 20.45 17.5 20.95 13H11V3.05Z"
        stroke={color}
        strokeWidth="1.8"
        strokeLinecap="round"
        strokeLinejoin="round"
        fill={active ? 'rgba(255, 255, 255, 0.15)' : 'none'}
      />
      <Path
        d="M15 2.1C18.2 3.1 20.9 5.8 21.9 9H15V2.1Z"
        stroke={color}
        strokeWidth="1.8"
        strokeLinecap="round"
        strokeLinejoin="round"
        fill={active ? 'rgba(255, 255, 255, 0.15)' : 'none'}
      />
    </Svg>
  );
}

export function TabProfileIcon({ active, size = 22 }: { active: boolean; size?: number }): React.JSX.Element {
  const color = active ? '#FFFFFF' : '#707D8E';
  return (
    <Svg width={size} height={size} viewBox="0 0 24 24" fill="none">
      <Circle
        cx="12"
        cy="7.5"
        r="3.5"
        stroke={color}
        strokeWidth="1.8"
        fill={active ? color : 'none'}
      />
      <Path
        d="M4.5 19.5C4.5 16.2 7.8 14 12 14C16.2 14 19.5 16.2 19.5 19.5"
        stroke={color}
        strokeWidth="1.8"
        strokeLinecap="round"
      />
    </Svg>
  );
}

export function CalendarIcon({ color = '#94A3B8', size = 16 }: IconProps): React.JSX.Element {
  return (
    <Svg width={size} height={size} viewBox="0 0 24 24" fill="none">
      <Rect x="3" y="4" width="18" height="18" rx="3" stroke={color} strokeWidth="1.8" />
      <Path d="M16 2V6M8 2V6M3 10H21" stroke={color} strokeWidth="1.8" strokeLinecap="round" />
    </Svg>
  );
}

export function ChevronRightIcon({ color = '#94A3B8', size = 16 }: IconProps): React.JSX.Element {
  return (
    <Svg width={size} height={size} viewBox="0 0 24 24" fill="none">
      <Path d="M9 18L15 12L9 6" stroke={color} strokeWidth="2" strokeLinecap="round" strokeLinejoin="round" />
    </Svg>
  );
}

export function ChevronLeftIcon({ color = '#1E2538', size = 16 }: IconProps): React.JSX.Element {
  return (
    <Svg width={size} height={size} viewBox="0 0 24 24" fill="none">
      <Path d="M15 18L9 12L15 6" stroke={color} strokeWidth="2" strokeLinecap="round" strokeLinejoin="round" />
    </Svg>
  );
}

export function ShareIcon({ color = '#1E2538', size = 20 }: IconProps): React.JSX.Element {
  return (
    <Svg width={size} height={size} viewBox="0 0 24 24" fill="none">
      <Circle cx="18" cy="5" r="3" stroke={color} strokeWidth="1.8" />
      <Circle cx="6" cy="12" r="3" stroke={color} strokeWidth="1.8" />
      <Circle cx="18" cy="19" r="3" stroke={color} strokeWidth="1.8" />
      <Path d="M8.59 13.51L15.42 17.49M15.41 6.51L8.59 10.49" stroke={color} strokeWidth="1.8" />
    </Svg>
  );
}

export function MoreHorizontalIcon({ color = '#1E2538', size = 20 }: IconProps): React.JSX.Element {
  return (
    <Svg width={size} height={size} viewBox="0 0 24 24" fill="none">
      <Circle cx="12" cy="12" r="2" fill={color} />
      <Circle cx="19" cy="12" r="2" fill={color} />
      <Circle cx="5" cy="12" r="2" fill={color} />
    </Svg>
  );
}

export function AlertTriangleIcon({ color = '#EF4444', size = 32 }: IconProps): React.JSX.Element {
  return (
    <Svg width={size} height={size} viewBox="0 0 48 48" fill="none">
      <Circle cx="24" cy="24" r="22" fill="#EF4444" />
      <Path
        d="M24 13L35 32H13L24 13Z"
        fill="#FFFFFF"
      />
      <Path d="M24 20V25" stroke="#EF4444" strokeWidth="2.5" strokeLinecap="round" />
      <Circle cx="24" cy="29" r="1.5" fill="#EF4444" />
    </Svg>
  );
}

export function CheckVerifiedIcon({ color = '#1E2538', size = 22 }: IconProps): React.JSX.Element {
  return (
    <Svg width={size} height={size} viewBox="0 0 24 24" fill="none">
      <Circle cx="12" cy="12" r="9.5" stroke={color} strokeWidth="1.6" />
      <Path
        d="M8.5 12.2L10.8 14.5L15.5 9.5"
        stroke={color}
        strokeWidth="1.8"
        strokeLinecap="round"
        strokeLinejoin="round"
      />
    </Svg>
  );
}

export function DeviceOfflineIcon({ color = '#64748B', size = 22 }: IconProps): React.JSX.Element {
  return (
    <Svg width={size} height={size} viewBox="0 0 24 24" fill="none">
      <Path
        d="M14 2H6C4.89543 2 4 2.89543 4 4V20C4 21.1046 4.89543 22 6 22H18C19.1046 22 20 21.1046 20 20V8L14 2Z"
        stroke={color}
        strokeWidth="1.8"
        strokeLinecap="round"
        strokeLinejoin="round"
      />
      <Path d="M14 2V8H20" stroke={color} strokeWidth="1.8" strokeLinecap="round" strokeLinejoin="round" />
      <Path d="M9 13H15" stroke={color} strokeWidth="1.8" strokeLinecap="round" />
    </Svg>
  );
}

export function SleepingBellIllustration({ size = 120 }: { size?: number }): React.JSX.Element {
  return (
    <Svg width={size} height={size} viewBox="0 0 120 120" fill="none">
      {/* Background Soft Circle */}
      <Circle cx="60" cy="60" r="50" fill="#F1F4F9" />

      {/* Sleeping Bell Outline */}
      <Path
        d="M60 38C51.7 38 45 44.7 45 53C45 64 40 68 40 68H80C80 68 75 64 75 53C75 44.7 68.3 38 60 38Z"
        stroke="#8E9AA8"
        strokeWidth="3.2"
        strokeLinecap="round"
        strokeLinejoin="round"
      />
      <Path
        d="M54 74C54.8 76.3 57.2 78 60 78C62.8 78 65.2 76.3 66 74"
        stroke="#8E9AA8"
        strokeWidth="3.2"
        strokeLinecap="round"
      />

      {/* Z Sleep symbols inside bell */}
      <Path
        d="M68 47H76L68 55H76"
        stroke="#8E9AA8"
        strokeWidth="2.2"
        strokeLinecap="round"
        strokeLinejoin="round"
      />

      {/* "0" Count badge on top right */}
      <Circle cx="90" cy="34" r="14" fill="#FFFFFF" stroke="#E2E8F0" strokeWidth="1.5" />
      <Path
        d="M90 28C88 28 86.5 30.5 86.5 34C86.5 37.5 88 40 90 40C92 40 93.5 37.5 93.5 34C93.5 30.5 92 28 90 28Z"
        stroke="#1E2538"
        strokeWidth="2"
      />
    </Svg>
  );
}

export function ChatBubbleIcon({ color = '#64748B', size = 16 }: IconProps): React.JSX.Element {
  return (
    <Svg width={size} height={size} viewBox="0 0 24 24" fill="none">
      <Path
        d="M21 11.5a8.38 8.38 0 0 1-.9 3.8 8.5 8.5 0 0 1-7.6 4.7 8.38 8.38 0 0 1-3.8-.9L3 21l1.9-5.7a8.38 8.38 0 0 1-.9-3.8 8.5 8.5 0 0 1 4.7-7.6 8.38 8.38 0 0 1 3.8-.9h.5a8.48 8.48 0 0 1 8 8v.5z"
        stroke={color}
        strokeWidth="1.8"
        strokeLinecap="round"
        strokeLinejoin="round"
      />
    </Svg>
  );
}

export function ClockIcon({ color = '#94A3B8', size = 12 }: IconProps): React.JSX.Element {
  return (
    <Svg width={size} height={size} viewBox="0 0 24 24" fill="none">
      <Circle cx="12" cy="12" r="9" stroke={color} strokeWidth="1.8" />
      <Path d="M12 7V12L15 15" stroke={color} strokeWidth="1.8" strokeLinecap="round" />
    </Svg>
  );
}

export function BatteryIcon({ color = '#94A3B8', size = 14 }: IconProps): React.JSX.Element {
  return (
    <Svg width={size} height={size} viewBox="0 0 24 24" fill="none">
      <Rect x="2" y="6" width="17" height="12" rx="2.5" stroke={color} strokeWidth="1.8" />
      <Path d="M21 10V14" stroke={color} strokeWidth="2" strokeLinecap="round" />
      <Rect x="5" y="9" width="9" height="6" rx="1" fill={color} />
    </Svg>
  );
}

export function NetworkIcon({ color = '#94A3B8', size = 14 }: IconProps): React.JSX.Element {
  return (
    <Svg width={size} height={size} viewBox="0 0 24 24" fill="none">
      <Path d="M2 20H4V18H2V20ZM6 20H8V15H6V20ZM10 20H12V12H10V20ZM14 20H16V9H14V20ZM18 20H20V6H18V20Z" fill={color} />
    </Svg>
  );
}

export function DashCamIllustration({ size = 64 }: { size?: number }): React.JSX.Element {
  return (
    <Svg width={size} height={size} viewBox="0 0 80 80" fill="none">
      <Rect x="4" y="12" width="72" height="56" rx="12" fill="#1E2538" />
      <Rect x="8" y="16" width="64" height="48" rx="8" fill="#2A334B" />
      <Circle cx="40" cy="40" r="20" fill="#111827" stroke="#4B5563" strokeWidth="2" />
      <Circle cx="40" cy="40" r="14" fill="#1F2937" stroke="#60A5FA" strokeWidth="2" />
      <Circle cx="40" cy="40" r="8" fill="#2563EB" />
      <Circle cx="37" cy="37" r="3" fill="#93C5FD" />
      <Circle cx="16" cy="24" r="3" fill="#EF4444" />
      <Rect x="56" y="22" width="10" height="4" rx="2" fill="#64748B" />
    </Svg>
  );
}

/* User Profile & Account Icons */

export function EditPencilIcon({ color = '#94A3B8', size = 16 }: IconProps): React.JSX.Element {
  return (
    <Svg width={size} height={size} viewBox="0 0 24 24" fill="none">
      <Path
        d="M11 4H4C3.46957 4 2.96086 4.21071 2.58579 4.58579C2.21071 4.96086 2 5.46957 2 6V20C2 20.5304 2.21071 21.0391 2.58579 21.4142C2.96086 21.7893 3.46957 22 4 22H18C18.5304 22 19.0391 21.7893 19.4142 21.4142C19.7893 21.0391 20 20.5304 20 20V13"
        stroke={color}
        strokeWidth="1.8"
        strokeLinecap="round"
        strokeLinejoin="round"
      />
      <Path
        d="M18.5 2.50001C18.8978 2.10219 19.4374 1.87869 20 1.87869C20.5626 1.87869 21.1022 2.10219 21.5 2.50001C21.8978 2.89784 22.1213 3.4374 22.1213 4.00001C22.1213 4.56263 21.8978 5.10219 21.5 5.50001L12 15L8 16L9 12L18.5 2.50001Z"
        stroke={color}
        strokeWidth="1.8"
        strokeLinecap="round"
        strokeLinejoin="round"
      />
    </Svg>
  );
}

export function LockOutlineIcon({ color = '#94A3B8', size = 20 }: IconProps): React.JSX.Element {
  return (
    <Svg width={size} height={size} viewBox="0 0 24 24" fill="none">
      <Rect x="5" y="11" width="14" height="10" rx="2" stroke={color} strokeWidth="1.8" />
      <Path
        d="M8 11V7C8 4.79086 9.79086 3 12 3C14.2091 3 16 4.79086 16 7V11"
        stroke={color}
        strokeWidth="1.8"
        strokeLinecap="round"
      />
      <Circle cx="12" cy="16" r="1.5" fill={color} />
    </Svg>
  );
}

export function HeartOutlineIcon({ color = '#94A3B8', size = 20 }: IconProps): React.JSX.Element {
  return (
    <Svg width={size} height={size} viewBox="0 0 24 24" fill="none">
      <Path
        d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"
        stroke={color}
        strokeWidth="1.8"
        strokeLinecap="round"
        strokeLinejoin="round"
      />
    </Svg>
  );
}

export function SettingsGearIcon({ color = '#94A3B8', size = 20 }: IconProps): React.JSX.Element {
  return (
    <Svg width={size} height={size} viewBox="0 0 24 24" fill="none">
      <Circle cx="12" cy="12" r="3" stroke={color} strokeWidth="1.8" />
      <Path
        d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z"
        stroke={color}
        strokeWidth="1.8"
        strokeLinecap="round"
        strokeLinejoin="round"
      />
    </Svg>
  );
}

export function InviteFriendsIcon({ color = '#94A3B8', size = 20 }: IconProps): React.JSX.Element {
  return (
    <Svg width={size} height={size} viewBox="0 0 24 24" fill="none">
      <Path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2" stroke={color} strokeWidth="1.8" strokeLinecap="round" strokeLinejoin="round" />
      <Circle cx="8.5" cy="7" r="4" stroke={color} strokeWidth="1.8" />
      <Path d="M20 8v6M23 11h-6" stroke={color} strokeWidth="1.8" strokeLinecap="round" />
    </Svg>
  );
}

export function TrashOutlineIcon({ color = '#94A3B8', size = 20 }: IconProps): React.JSX.Element {
  return (
    <Svg width={size} height={size} viewBox="0 0 24 24" fill="none">
      <Path d="M3 6h18M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2" stroke={color} strokeWidth="1.8" strokeLinecap="round" strokeLinejoin="round" />
      <Path d="M10 11v6M14 11v6" stroke={color} strokeWidth="1.8" strokeLinecap="round" />
    </Svg>
  );
}

export function DocumentTextIcon({ color = '#94A3B8', size = 20 }: IconProps): React.JSX.Element {
  return (
    <Svg width={size} height={size} viewBox="0 0 24 24" fill="none">
      <Path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z" stroke={color} strokeWidth="1.8" strokeLinecap="round" strokeLinejoin="round" />
      <Path d="M14 2v6h6M16 13H8M16 17H8M10 9H8" stroke={color} strokeWidth="1.8" strokeLinecap="round" strokeLinejoin="round" />
    </Svg>
  );
}

export function HeadsetSupportIcon({ color = '#94A3B8', size = 20 }: IconProps): React.JSX.Element {
  return (
    <Svg width={size} height={size} viewBox="0 0 24 24" fill="none">
      <Path d="M3 18v-6a9 9 0 0 1 18 0v6" stroke={color} strokeWidth="1.8" strokeLinecap="round" strokeLinejoin="round" />
      <Path d="M21 19a2 2 0 0 1-2 2h-1a2 2 0 0 1-2-2v-3a2 2 0 0 1 2-2h3zM3 19a2 2 0 0 0 2 2h1a2 2 0 0 0 2-2v-3a2 2 0 0 0-2-2H3z" stroke={color} strokeWidth="1.8" strokeLinecap="round" strokeLinejoin="round" />
    </Svg>
  );
}

export function LogoutArrowIcon({ color = '#94A3B8', size = 20 }: IconProps): React.JSX.Element {
  return (
    <Svg width={size} height={size} viewBox="0 0 24 24" fill="none">
      <Path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4" stroke={color} strokeWidth="1.8" strokeLinecap="round" strokeLinejoin="round" />
      <Path d="M16 17l5-5-5-5M21 12H9" stroke={color} strokeWidth="1.8" strokeLinecap="round" strokeLinejoin="round" />
    </Svg>
  );
}

export function EyeIcon({ color = '#94A3B8', size = 18 }: IconProps): React.JSX.Element {
  return (
    <Svg width={size} height={size} viewBox="0 0 24 24" fill="none">
      <Path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z" stroke={color} strokeWidth="1.8" strokeLinecap="round" strokeLinejoin="round" />
      <Circle cx="12" cy="12" r="3" stroke={color} strokeWidth="1.8" />
    </Svg>
  );
}

export function EyeOffIcon({ color = '#94A3B8', size = 18 }: IconProps): React.JSX.Element {
  return (
    <Svg width={size} height={size} viewBox="0 0 24 24" fill="none">
      <Path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24M1 1l22 22" stroke={color} strokeWidth="1.8" strokeLinecap="round" strokeLinejoin="round" />
    </Svg>
  );
}

export function CameraBadgeIcon({ color = '#FFFFFF', size = 10 }: IconProps): React.JSX.Element {
  return (
    <Svg width={size} height={size} viewBox="0 0 24 24" fill="none">
      <Path d="M23 19a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h4l2-3h6l2 3h4a2 2 0 0 1 2 2z" fill={color} />
      <Circle cx="12" cy="13" r="4" fill="#1E2538" />
    </Svg>
  );
}

export function MaintenanceIcon({ color = '#FFFFFF', size = 20 }: IconProps): React.JSX.Element {
  return (
    <Svg width={size} height={size} viewBox="0 0 24 24" fill="none">
      <Path
        d="M14.7 6.3a1 1 0 0 0 0 1.4l1.6 1.6a1 1 0 0 0 1.4 0l3.77-3.77a6 6 0 0 1-7.94 7.94l-6.91 6.91a2.12 2.12 0 0 1-3-3l6.91-6.91a6 6 0 0 1 7.94-7.94l-3.76 3.76z"
        stroke={color}
        strokeWidth="1.8"
        strokeLinecap="round"
        strokeLinejoin="round"
      />
    </Svg>
  );
}

/* Theft Report & Recovery Flow Icons */

export function FaceIdIcon({ color = '#EF4444', size = 22 }: IconProps): React.JSX.Element {
  return (
    <Svg width={size} height={size} viewBox="0 0 24 24" fill="none">
      <Path d="M4 8V5a1 1 0 0 1 1-1h3M4 16v3a1 1 0 0 0 1 1h3M16 4h3a1 1 0 0 1 1 1v3M16 20h3a1 1 0 0 0 1-1v-3" stroke={color} strokeWidth="1.8" strokeLinecap="round" />
      <Circle cx="9" cy="10" r="1" fill={color} />
      <Circle cx="15" cy="10" r="1" fill={color} />
      <Path d="M9.5 15c.8.7 1.6 1 2.5 1s1.7-.3 2.5-1" stroke={color} strokeWidth="1.8" strokeLinecap="round" />
    </Svg>
  );
}

export function FingerprintIcon({ color = '#EF4444', size = 22 }: IconProps): React.JSX.Element {
  return (
    <Svg width={size} height={size} viewBox="0 0 24 24" fill="none">
      <Path d="M12 11c0-1.66-1.34-3-3-3s-3 1.34-3 3c0 3.31 2.69 6 6 6" stroke={color} strokeWidth="1.8" strokeLinecap="round" />
      <Path d="M12 7c-2.76 0-5 2.24-5 5 0 2.21 1.79 4 4 4" stroke={color} strokeWidth="1.8" strokeLinecap="round" />
      <Path d="M12 3C7.58 3 4 6.58 4 11c0 3.87 3.13 7 7 7" stroke={color} strokeWidth="1.8" strokeLinecap="round" />
      <Path d="M15 11c0-2.21-1.79-4-4-4" stroke={color} strokeWidth="1.8" strokeLinecap="round" />
      <Path d="M18 11c0-3.87-3.13-7-7-7" stroke={color} strokeWidth="1.8" strokeLinecap="round" />
    </Svg>
  );
}

export function KeypadPinIcon({ color = '#EF4444', size = 22 }: IconProps): React.JSX.Element {
  return (
    <Svg width={size} height={size} viewBox="0 0 24 24" fill="none">
      <Rect x="4" y="4" width="4" height="4" rx="1" stroke={color} strokeWidth="1.8" />
      <Rect x="10" y="4" width="4" height="4" rx="1" stroke={color} strokeWidth="1.8" />
      <Rect x="16" y="4" width="4" height="4" rx="1" stroke={color} strokeWidth="1.8" />
      <Rect x="4" y="10" width="4" height="4" rx="1" stroke={color} strokeWidth="1.8" />
      <Rect x="10" y="10" width="4" height="4" rx="1" stroke={color} strokeWidth="1.8" />
      <Rect x="16" y="10" width="4" height="4" rx="1" stroke={color} strokeWidth="1.8" />
      <Rect x="10" y="16" width="4" height="4" rx="1" stroke={color} strokeWidth="1.8" />
    </Svg>
  );
}

export function ShieldCheckIcon({ color = '#10B981', size = 24 }: IconProps): React.JSX.Element {
  return (
    <Svg width={size} height={size} viewBox="0 0 24 24" fill="none">
      <Path
        d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"
        stroke={color}
        strokeWidth="1.8"
        strokeLinecap="round"
        strokeLinejoin="round"
      />
      <Path d="M9 12l2 2 4-4" stroke={color} strokeWidth="1.8" strokeLinecap="round" strokeLinejoin="round" />
    </Svg>
  );
}

export function PhoneCallIcon({ color = '#1E2538', size = 18 }: IconProps): React.JSX.Element {
  return (
    <Svg width={size} height={size} viewBox="0 0 24 24" fill="none">
      <Path
        d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"
        stroke={color}
        strokeWidth="1.8"
        strokeLinecap="round"
        strokeLinejoin="round"
      />
    </Svg>
  );
}

export function DownloadIcon({ color = '#1E2538', size = 18 }: IconProps): React.JSX.Element {
  return (
    <Svg width={size} height={size} viewBox="0 0 24 24" fill="none">
      <Path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4M7 10l5 5 5-5M12 15V3" stroke={color} strokeWidth="1.8" strokeLinecap="round" strokeLinejoin="round" />
    </Svg>
  );
}

export function GpsSignalIcon({ color = '#10B981', size = 16 }: IconProps): React.JSX.Element {
  return (
    <Svg width={size} height={size} viewBox="0 0 24 24" fill="none">
      <Path d="M2 12h4l3-7 6 14 3-7h4" stroke={color} strokeWidth="2" strokeLinecap="round" strokeLinejoin="round" />
    </Svg>
  );
}

export function CarDeliveryIcon({ color = '#EF4444', size = 28 }: IconProps): React.JSX.Element {
  return (
    <Svg width={size} height={size} viewBox="0 0 24 24" fill="none">
      <Path d="M1 3h15v13H1zM16 8h4l3 3v5h-7V8z" stroke={color} strokeWidth="1.8" strokeLinecap="round" strokeLinejoin="round" />
      <Circle cx="5.5" cy="18.5" r="2.5" stroke={color} strokeWidth="1.8" />
      <Circle cx="18.5" cy="18.5" r="2.5" stroke={color} strokeWidth="1.8" />
    </Svg>
  );
}

export function SunIcon({ color = '#F59E0B', size = 20 }: IconProps): React.JSX.Element {
  return (
    <Svg width={size} height={size} viewBox="0 0 24 24" fill="none">
      <Circle cx="12" cy="12" r="5" stroke={color} strokeWidth="1.8" />
      <Path d="M12 1v2M12 21v2M4.22 4.22l1.42 1.42M18.36 18.36l1.42 1.42M1 12h2M21 12h2M4.22 19.78l1.42-1.42M18.36 5.64l1.42-1.42" stroke={color} strokeWidth="1.8" strokeLinecap="round" />
    </Svg>
  );
}

export function MoonIcon({ color = '#6366F1', size = 20 }: IconProps): React.JSX.Element {
  return (
    <Svg width={size} height={size} viewBox="0 0 24 24" fill="none">
      <Path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z" stroke={color} strokeWidth="1.8" strokeLinecap="round" strokeLinejoin="round" fill="none" />
    </Svg>
  );
}

export function DevicePhoneIcon({ color = '#10B981', size = 20 }: IconProps): React.JSX.Element {
  return (
    <Svg width={size} height={size} viewBox="0 0 24 24" fill="none">
      <Rect x="5" y="2" width="14" height="20" rx="2" stroke={color} strokeWidth="1.8" />
      <Path d="M12 18h.01" stroke={color} strokeWidth="2" strokeLinecap="round" />
    </Svg>
  );
}

export function CheckmarkCircleIcon({ color = '#10B981', size = 20 }: IconProps): React.JSX.Element {
  return (
    <Svg width={size} height={size} viewBox="0 0 24 24" fill="none">
      <Circle cx="12" cy="12" r="10" fill={color} />
      <Path d="M8 12.5l2.5 2.5 5.5-5.5" stroke="#FFFFFF" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round" />
    </Svg>
  );
}

export function CoinIcon({ color = '#F59E0B', size = 20 }: IconProps): React.JSX.Element {
  return (
    <Svg width={size} height={size} viewBox="0 0 24 24" fill="none">
      <Circle cx="12" cy="12" r="9" stroke={color} strokeWidth="1.8" fill="rgba(245, 158, 11, 0.15)" />
      <Circle cx="12" cy="12" r="6" stroke={color} strokeWidth="1.2" />
      <Path d="M12 9v6M10 10.5h3.5a1.5 1.5 0 0 1 0 3H10" stroke={color} strokeWidth="1.6" strokeLinecap="round" />
    </Svg>
  );
}

export function AutoDocEngineIcon({ color = '#EA580C', size = 20 }: IconProps): React.JSX.Element {
  return (
    <Svg width={size} height={size} viewBox="0 0 24 24" fill="none">
      <Path d="M3 10h2V8h3V6h2V4h4v2h2v2h3v2h2v6h-2v2h-3v2h-8v-2H5v-2H3v-6Z" stroke={color} strokeWidth="1.8" strokeLinecap="round" strokeLinejoin="round" />
      <Circle cx="12" cy="13" r="3" stroke={color} strokeWidth="1.5" />
      <Path d="M9 13h1M14 13h1" stroke={color} strokeWidth="2" strokeLinecap="round" />
    </Svg>
  );
}

export function WrenchToolIcon({ color = '#3B82F6', size = 20 }: IconProps): React.JSX.Element {
  return (
    <Svg width={size} height={size} viewBox="0 0 24 24" fill="none">
      <Path d="M14.7 6.3a1 1 0 0 0 0 1.4l1.6 1.6a1 1 0 0 0 1.4 0l3.77-3.77a6 6 0 0 1-7.94 7.94l-6.91 6.91a2.12 2.12 0 0 1-3-3l6.91-6.91a6 6 0 0 1 7.94-7.94l-3.76 3.76z" stroke={color} strokeWidth="1.8" strokeLinecap="round" strokeLinejoin="round" />
    </Svg>
  );
}

export function StarRatingIcon({ color = '#F59E0B', size = 16 }: IconProps): React.JSX.Element {
  return (
    <Svg width={size} height={size} viewBox="0 0 24 24" fill="none">
      <Path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z" fill={color} stroke={color} strokeWidth="1" strokeLinejoin="round" />
    </Svg>
  );
}

export function CameraVideoIcon({ color = '#1E2538', size = 20 }: IconProps): React.JSX.Element {
  return (
    <Svg width={size} height={size} viewBox="0 0 24 24" fill="none">
      <Rect x="2" y="6" width="14" height="12" rx="3" stroke={color} strokeWidth="1.8" />
      <Path d="M16 10l5-3v10l-5-3v-4z" stroke={color} strokeWidth="1.8" strokeLinecap="round" strokeLinejoin="round" fill={color} />
    </Svg>
  );
}

export function PowerShutdownIcon({ color = '#DC2626', size = 20 }: IconProps): React.JSX.Element {
  return (
    <Svg width={size} height={size} viewBox="0 0 24 24" fill="none">
      <Path d="M18.36 6.64a9 9 0 1 1-12.73 0M12 2v10" stroke={color} strokeWidth="2" strokeLinecap="round" strokeLinejoin="round" />
    </Svg>
  );
}

export function RefreshSyncIcon({ color = '#64748B', size = 20 }: IconProps): React.JSX.Element {
  return (
    <Svg width={size} height={size} viewBox="0 0 24 24" fill="none">
      <Path d="M23 4v6h-6M1 20v-6h6" stroke={color} strokeWidth="2" strokeLinecap="round" strokeLinejoin="round" />
      <Path d="M3.51 9a9 9 0 0 1 14.85-3.36L23 10M1 14l4.64 4.36A9 9 0 0 0 20.49 15" stroke={color} strokeWidth="2" strokeLinecap="round" strokeLinejoin="round" />
    </Svg>
  );
}

export function MapLayersIcon({ color = '#1E2538', size = 18 }: IconProps): React.JSX.Element {
  return (
    <Svg width={size} height={size} viewBox="0 0 24 24" fill="none">
      <Path d="M12 2L2 7L12 12L22 7L12 2Z" stroke={color} strokeWidth="1.8" strokeLinecap="round" strokeLinejoin="round" />
      <Path d="M2 17L12 22L22 17" stroke={color} strokeWidth="1.8" strokeLinecap="round" strokeLinejoin="round" />
      <Path d="M2 12L12 17L22 12" stroke={color} strokeWidth="1.8" strokeLinecap="round" strokeLinejoin="round" />
    </Svg>
  );
}

export function GeofenceShieldIcon({ color = '#EA580C', size = 18 }: IconProps): React.JSX.Element {
  return (
    <Svg width={size} height={size} viewBox="0 0 24 24" fill="none">
      <Path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z" stroke={color} strokeWidth="1.8" strokeLinecap="round" strokeLinejoin="round" />
      <Circle cx="12" cy="11" r="3" stroke={color} strokeWidth="1.5" />
    </Svg>
  );
}

export function SatelliteGpsIcon({ color = '#2563EB', size = 18 }: IconProps): React.JSX.Element {
  return (
    <Svg width={size} height={size} viewBox="0 0 24 24" fill="none">
      <Path d="M4 14.899A7 7 0 0 1 15.71 8h1.79a4.5 4.5 0 0 1 2.5 8.242M12 12v9M8 17l4 4 4-4" stroke={color} strokeWidth="1.8" strokeLinecap="round" strokeLinejoin="round" />
    </Svg>
  );
}

export function SpeakerBuzzerIcon({ color = '#0284C7', size = 18 }: IconProps): React.JSX.Element {
  return (
    <Svg width={size} height={size} viewBox="0 0 24 24" fill="none">
      <Path d="M11 5L6 9H2v6h4l5 4V5zM19.07 4.93a10 10 0 0 1 0 14.14M15.54 8.46a5 5 0 0 1 0 7.07" stroke={color} strokeWidth="1.8" strokeLinecap="round" strokeLinejoin="round" />
    </Svg>
  );
}

export function SpeedGaugeIcon({ color = '#F59E0B', size = 18 }: IconProps): React.JSX.Element {
  return (
    <Svg width={size} height={size} viewBox="0 0 24 24" fill="none">
      <Path d="M12 15a3 3 0 1 0 0-6 3 3 0 0 0 0 6z" stroke={color} strokeWidth="1.8" />
      <Path d="M13.5 10.5L17 7" stroke={color} strokeWidth="1.8" strokeLinecap="round" />
      <Path d="M3.34 17a10 10 0 1 1 17.32 0" stroke={color} strokeWidth="1.8" strokeLinecap="round" />
    </Svg>
  );
}

export function RoadStreetIcon({ color = '#64748B', size = 18 }: IconProps): React.JSX.Element {
  return (
    <Svg width={size} height={size} viewBox="0 0 24 24" fill="none">
      <Path d="M4 19L8 5M20 19L16 5M12 7v2M12 13v2M12 19v2" stroke={color} strokeWidth="1.8" strokeLinecap="round" />
    </Svg>
  );
}

export function SatelliteOrbitIcon({ color = '#64748B', size = 18 }: IconProps): React.JSX.Element {
  return (
    <Svg width={size} height={size} viewBox="0 0 24 24" fill="none">
      <Path d="M13 2L3 14H12L11 22L21 10H12L13 2Z" stroke={color} strokeWidth="1.8" strokeLinecap="round" strokeLinejoin="round" />
    </Svg>
  );
}

export function MountainTerrainIcon({ color = '#64748B', size = 18 }: IconProps): React.JSX.Element {
  return (
    <Svg width={size} height={size} viewBox="0 0 24 24" fill="none">
      <Path d="M8 3l4 8 5-5 5 15H2L8 3z" stroke={color} strokeWidth="1.8" strokeLinecap="round" strokeLinejoin="round" />
    </Svg>
  );
}

export function MoonNightIcon({ color = '#64748B', size = 18 }: IconProps): React.JSX.Element {
  return (
    <Svg width={size} height={size} viewBox="0 0 24 24" fill="none">
      <Path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z" stroke={color} strokeWidth="1.8" strokeLinecap="round" strokeLinejoin="round" />
    </Svg>
  );
}

export function FlagStartIcon({ color = '#10B981', size = 18 }: IconProps): React.JSX.Element {
  return (
    <Svg width={size} height={size} viewBox="0 0 24 24" fill="none">
      <Path d="M4 15s1-1 4-1 5 2 8 2 4-1 4-1V3s-1 1-4 1-5-2-8-2-4 1-4 1zM4 22v-7" stroke={color} strokeWidth="1.8" strokeLinecap="round" strokeLinejoin="round" />
    </Svg>
  );
}

export function FlagFinishIcon({ color = '#2563EB', size = 18 }: IconProps): React.JSX.Element {
  return (
    <Svg width={size} height={size} viewBox="0 0 24 24" fill="none">
      <Path d="M4 15s1-1 4-1 5 2 8 2 4-1 4-1V3s-1 1-4 1-5-2-8-2-4 1-4 1zM4 22v-7" stroke={color} strokeWidth="1.8" strokeLinecap="round" strokeLinejoin="round" fill={color} fillOpacity="0.2" />
    </Svg>
  );
}

export function UserAvatarIcon({ color = '#64748B', size = 20 }: IconProps): React.JSX.Element {
  return (
    <Svg width={size} height={size} viewBox="0 0 24 24" fill="none">
      <Path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2" stroke={color} strokeWidth="1.8" strokeLinecap="round" strokeLinejoin="round" />
      <Circle cx="12" cy="7" r="4" stroke={color} strokeWidth="1.8" />
    </Svg>
  );
}

export function SupportAgentIcon({ color = '#10B981', size = 20 }: IconProps): React.JSX.Element {
  return (
    <Svg width={size} height={size} viewBox="0 0 24 24" fill="none">
      <Path d="M3 18v-6a9 9 0 0 1 18 0v6" stroke={color} strokeWidth="1.8" strokeLinecap="round" />
      <Path d="M21 19a2 2 0 0 1-2 2h-1a2 2 0 0 1-2-2v-3a2 2 0 0 1 2-2h3zM3 19a2 2 0 0 0 2 2h1a2 2 0 0 0 2-2v-3a2 2 0 0 0-2-2H3z" stroke={color} strokeWidth="1.8" />
    </Svg>
  );
}

export function CarLicensePlateIcon({ color = '#1E2538', size = 18 }: IconProps): React.JSX.Element {
  return (
    <Svg width={size} height={size} viewBox="0 0 24 24" fill="none">
      <Rect x="2" y="6" width="20" height="12" rx="2" stroke={color} strokeWidth="1.8" />
      <Path d="M6 12h12" stroke={color} strokeWidth="1.8" strokeLinecap="round" />
    </Svg>
  );
}

export function CheckBadgeIcon({ color = '#10B981', size = 18 }: IconProps): React.JSX.Element {
  return (
    <Svg width={size} height={size} viewBox="0 0 24 24" fill="none">
      <Circle cx="12" cy="12" r="10" fill={color} />
      <Path d="M8 12l3 3 5-5" stroke="#FFFFFF" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round" />
    </Svg>
  );
}

export function LockSecurityIcon({ color = '#64748B', size = 16 }: IconProps): React.JSX.Element {
  return (
    <Svg width={size} height={size} viewBox="0 0 24 24" fill="none">
      <Rect x="3" y="11" width="18" height="11" rx="2" ry="2" stroke={color} strokeWidth="1.8" />
      <Path d="M7 11V7a5 5 0 0 1 10 0v4" stroke={color} strokeWidth="1.8" strokeLinecap="round" strokeLinejoin="round" />
    </Svg>
  );
}

export function XMarkIcon({ color = '#64748B', size = 18 }: IconProps): React.JSX.Element {
  return (
    <Svg width={size} height={size} viewBox="0 0 24 24" fill="none">
      <Path d="M18 6L6 18M6 6l12 12" stroke={color} strokeWidth="2" strokeLinecap="round" strokeLinejoin="round" />
    </Svg>
  );
}





