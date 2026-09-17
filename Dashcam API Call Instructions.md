API Call Instructions
terry3 terry3
Last modified on
9 months ago
Export
⚠️ API Usage Statement and Disclaimer (Important)
To ensure service quality, the developer reserves the right to restrict, suspend, or terminate any user's API call permissions at any time , especially for frequent or abnormal call behavior, without prior notice.

Users must adhere to the principle of fair use when using the API services provided by this platform. The developer assumes no responsibility for service anomalies, data loss, or functional limitations caused by abuse of the interface, malicious scraping, or a large number of duplicate requests .

We reserve the right to modify API usage policies, adjust call frequency limits, charge fees, or discontinue free services at any time based on platform resources and business adjustments . Users are advised to pay attention and adjust their usage accordingly.

By using this platform's API, you acknowledge and agree to the above terms. We reserve the right to pursue legal action against any malicious attacks or disruptions to the platform's operation.

Invocation rule details:

Each account can modify its IP whitelist a maximum of 5 times per day.
Each account can make a maximum of 1440 calls per day + the number of valid renewed devices × 5 calls.
The trajectory interface is limited to 5 calls per device per day.
Each IP address can make a maximum of 10 requests per minute.
Website: api.yourdomain.com (Data server domain; please consult your service provider for the specific domain).
Invocation method: Use HTTP POST.
Supports both HTTP and HTTPS;
the body content should be in JSON format.
Format:
https://api.yourdomain.com/openapi?action=lastposition&token=6c1f1207c35d97a744837a19663ecdbe&serverid=0

parameter	Parameter Description	Remark
action	Various operations such as: lastposition	Query the last position
token	6c1f1207c35d97a744837a19663ecdbe	The login operation returns the current token; other calls use it.
servers	int	The login operation will return the current serverid; other calls will use it.
Return value:

parameter	Data types	illustrate	Detailed description
status	int	state	Determine if it is greater than 8900
State value	Detailed description
-1	abnormal
0	success
8901	No corresponding action found
8902	IP access frequency limit
8903	Account access restrictions
8904	The IP address is not on the whitelist
8905	Account call count exhausted
8906	Action calls are prohibited.
9903	token expired
9906	No matching token found