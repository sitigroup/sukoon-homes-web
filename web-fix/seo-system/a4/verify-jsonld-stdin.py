import json
import re
import sys

html = sys.stdin.read()
match = re.search(r'<script id="__NEXT_DATA__"[^>]*>(.*?)</script>', html, re.S)
if not match:
    print("NO __NEXT_DATA__")
    sys.exit(1)

payload = json.loads(match.group(1))
structured = payload.get("props", {}).get("pageProps", {}).get("structuredData")
text = json.dumps(structured or {})
print("structuredData present:", bool(structured))
print("RealEstateListing:", "RealEstateListing" in text)
print("BreadcrumbList:", "BreadcrumbList" in text)
print("streetAddress:", "streetAddress" in text)
print("latitude in structuredData:", '"latitude"' in text)
print("geo in mainEntity:", '"geo"' in text)
if structured:
    graph = structured.get("@graph", [structured])
    for node in graph:
        if node.get("@type") == "RealEstateListing":
            print("address:", json.dumps(node.get("mainEntity", {}).get("address", {})))

property_obj = payload.get("props", {}).get("pageProps", {}).get("initialPropertyLoad", {}).get("property", {})
print("can_view_exact_location:", property_obj.get("can_view_exact_location"))
print("property title in pageProps:", property_obj.get("title"))
