import json
import re
import sys
import urllib.request

url = sys.argv[1] if len(sys.argv) > 1 else "https://homes.sukoon.group/property-details/home-for-rent/?lang=en"
html = urllib.request.urlopen(url).read().decode("utf-8", "replace")
scripts = re.findall(
    r'<script type="application/ld\+json"[^>]*>(.*?)</script>',
    html,
    re.S,
)
print("ld+json blocks:", len(scripts))
for i, block in enumerate(scripts):
    data = json.loads(block)
    text = json.dumps(data)
    print(f"--- block {i} ---")
    print("RealEstateListing:", "RealEstateListing" in text)
    print("streetAddress:", "streetAddress" in text)
    print("latitude:", '"latitude"' in text)
    print("Gali Wala in JSON:", "Gali Wala" in text)
    if "mainEntity" in str(data):
        print("mainEntity:", json.dumps(data.get("@graph", data), indent=2)[:800])
