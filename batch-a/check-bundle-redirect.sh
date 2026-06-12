#!/bin/bash
cd /www/wwwroot/homes.sukoon.group/.next/static/chunks
echo "OLD relative (no leading slash):"
grep -l '"user/listings?tab=properties"' *.js 2>/dev/null | head -3
grep -l 'user/listings?tab=properties"&lang' *.js 2>/dev/null | head -3
echo "FIXED absolute:"
grep -l '"/user/listings?tab=properties&lang=' *.js 2>/dev/null | head -3
grep -l '/user/listings?tab=properties&lang=' *.js 2>/dev/null | head -5
