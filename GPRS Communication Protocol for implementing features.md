
18

Automatic Zoom
Shenzhen Sanjitongchuang Electronic Co.,Ltd. Page 9 of
11
positioning status for a continuous period of time.
12. Power-off instruction
The platform sends:
[CS*YYYYYYYYYY*LEN*POWEROFF]
Example: [3G*5678901234*0008*POWEROFF]
The terminal replies:
[CS*YYYYYYYYYY*LEN*POWEROFF]
Example: [3G*5678901234*0008*POWEROFF]
Description: Power-off function.
13. Step counting time period setting
The platform sends:
[CS*YYYYYYYYYY*LEN*WALKTIME,time period, time period, time period]
Example: [3G*5678901234*002A*WALKTIME,8:10-9:30,10:10-11:30,12:10-13:30]
The terminal replies:
[CS*YYYYYYYYYY*LEN*ANY]
Example: [3G*5678901234*0008*WALKTIME]
Description: Set the time period range for step counting on.
14. Flip detection time period setting
The platform sends:
Shenzhen Sanjitongchuang Electronic Co.,Ltd. Page 10 of
11
[CS*YYYYYYYYYY*LEN*SLEEPTIME,time period]
Example: [3G*5678901234*0014*SLEEPTIME,21:10-7:30]
The terminal replies:
[CS*YYYYYYYYYY*LEN*ANY]
Example: [3G*5678901234*0009*SLEEPTIME]
Description: Set the time period range of flip detection.
15. DND time period setting
The platform sends:
[CS*YYYYYYYYYY*LEN*SILENCETIME,time period, time period, time period, time period]
Example:
[3G*5678901234*0037*SILENCETIME,21:10-7:30,21:10-7:30,21:10-7:30,21:10-7:30]
The terminal replies:
[CS*YYYYYYYYYY*LEN*SILENCETIME]
Example: [3G*5678901234*000B*SILENCETIME]
Note: Set the DND time period range.
16. Find-watch instruction
The platform sends:
[CS*YYYYYYYYYY*LEN*FIND]
Example: [3G*5678901234*0004*FIND]
The terminal replies:
Shenzhen Sanjitongchuang Electronic Co.,Ltd. Page 11 of
11
[CS*YYYYYYYYYY*LEN*FIND]
Example: [3G*5678901234*0004*FIND]
Description: Ring for 1 minute while sending the command.
17. Alarm setting instructions
The platform sends:
[CS*YYYYYYYYYY*LEN*REMIND,alarm clock 1, alarm clock 2, alarm clock 3]
Example: [3G*5678901234*0018*REMIND,08:10-1-1,08:10-1-2, 08:10-1-3-0111110]
The terminal replies:
[CS*YYYYYYYYYY*LEN*REMIND]
Example: [3G*5678901234*0006*REMIND]
Description:Alarm time-switch-frequency -type.
18. Set a command for the phone book
The platform sends (1-5):
[CS*YYYYYYYYYY*LEN*PHB,number 1,name 1,number 2,name 2,number 3,name 3, number
4,name 4,number 5,name 5]
Example: [3G*5678901234*001B*PHB,313131,597D003100320033,,,,,,,,]
The terminal replies:
[CS*YYYYYYYYYY*LEN*PHB]
Example: [3G*5678901234*0003*PHB]
The platform sends (6-10):
Shenzhen Sanjitongchuang Electronic Co.,Ltd. Page 12 of
11
[CS*YYYYYYYYYY*LEN*PHB2,number 6,name 6,number 7,name 7,number 8,name 8,
number 9,name 9,number 10,name 10]
Example: [3G*8800000015*001C*PHB2,313131,597D003100320033,,,,,,,,]
The terminal replies:
[CS*YYYYYYYYYY*LEN*PHB2]
Example: [3G*8800000015*0004*PHB2]
Description: The command is set for the phone book at the terminal.
19. Intercom function
The platform sends:
[CS*YYYYYYYYYY*LEN*TK,AMR format audio data]
The terminal replies:
[CS*YYYYYYYYYY*LEN*TK,receive result]
Audio data in ARM format needs to be unescaped:
0X7D 0X01 --> 0X7D
0X7D 0X02 --> 0X5B
0X7D 0X03 --> 0X5D
0X7D 0X04 --> 0X2C
0X7D 0X05 --> 0X2A
Receive result: 1—success
0- fail
The terminal sends:
Shenzhen Sanjitongchuang Electronic Co.,Ltd. Page 13 of
11
[CS*YYYYYYYYYY*LEN*TK,AMR format audio data]
The platform replies:[CS*YYYYYYYYYY*LEN*TK,receive result]
Audio data in ARM format needs to be escaped:
0X7D --> 0X7D 0X01
0X5B --> 0X7D 0X02
0X5D --> 0X7D 0X03
0X2C --> 0X7D 0X04
0X2A --> 0X7D 0X05
Receive result: 1—success
0-fail
The terminal requests issuing an autio clip:
[CS*YYYYYYYYYY*LEN*TKQ]
Shenzhen Sanjitongchuang Electronic Co.,Ltd. Page 14 of
11
III. Appendix:
Appendix 1: Position Data Description
Name Example (ASII
code)
Description
Date 120414 (Day, month, year) 12, April, 2014
Time 101930 (Hour, minute, second) 10:19:30
Whether to locate A A: Yes, V: No
Shenzhen Sanjitongchuang Electronic Co.,Ltd. Page 15 of
11
Latitude 22.564025 According to the definition of DD.DDDDDD
format, this latitude value is: 22.564025
Latitude
identification
N N indicates north latitude, S indicates south
latitude
Longitude 113.242329 According to the definition of DDD.DDDDDD
format, this longitude value is: 113.242329
Longitude
identification
E E indicates east longitude, W indicates west
longitude
Speed 5.21 5.21 km/h
Direction 152 The direction is at 152°
Altitude 100 Unit is meter
Number of satellites 9 Indicates the number of GPS satellites
GSM signal strength 100 Indicates the current GSM signal strength (0-100)
Power 90 Indicates the current power level percentage
Step count 1000 Step count is 1000
Number of rolls 50 Roll 50 times
Terminal status 00000000 Expressed as a hexadecimal string, the meaning is
as follows:
High 16bit means alarm, low 16bit means status
Bit (start from 0) meaning (1 valid)
0 Low power
1 Out of the fence
Shenzhen Sanjitongchuang Electronic Co.,Ltd. Page 16 of
11
2 In the fence
3 Wristband status
16 SOS alarm
17 Low battery alarm
18 Out of fence alarm
19 In fence alarm
20 Low battery alarm
Number of base
stations
4 Number of base stations reported, 0 means no
base station information reported
Connect to base
station ta
1 GSM delay
MCC country code 460 460 for China
MNC 02 02 for China Mobile
Area code of the
connected base
station
10133 Area code
Number of the
connected base
station
5173 Base station number
Signal strength of
the connected base
station
100 Signal strength
Shenzhen Sanjitongchuang Electronic Co.,Ltd. Page 17 of
11
Location code of
nearby base station
1
10133 Area code
Number of nearby
base station 1
5173 Base station number
Signal strength of
nearby base station
1
100 Signal strength
Location code of
nearby base station
2
10133 Area code
Number of nearby
base station 2
5173 Base station number
Signal strength of
nearby base station
2
100 Signal strength
Location code of
nearby base station
3
10133 Area code
Number of nearby
base station 3
5173 Base station number
Signal strength of 100 Signal strength
Shenzhen Sanjitongchuang Electronic Co.,Ltd. Page 18 of
11
nearby base station
3
... ... ...
Number of Wifi
information
5 Number of Wifi (up to 5), sorted by signal
strength
Wifi 1 name rrr Name of 1st wifi
Wifi 1 MAC address 1c:fa:68:13:a5:b
4
MAC address of 1st wifi
Wifi 1 signal
strength
-61 Signal strength of 1st wifi
Wifi 1 name abc Name of 2nd wifi
Wifi 1 MAC address 1c:fa:68:13:a5:b
5
MAC address of 2nd wifi
Wifi 1 signal
strength
-87 Signal strength of 2nd wifi
... ... ...
Positioning accuracy 6.5 Unit: m