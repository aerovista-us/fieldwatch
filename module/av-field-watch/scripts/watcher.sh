#!/bin/sh
# AeroVista FieldWatch passive AP watcher
# Requires: iwinfo, curl
# Usage:
#   FIELDWATCH_INGEST_URL="http://127.0.0.1:8199/api/ingest.php" ./watcher.sh wlan0

IFACE="${1:-wlan0}"
INGEST_URL="${FIELDWATCH_INGEST_URL:-http://127.0.0.1:8199/api/ingest.php}"
INTERVAL="${FIELDWATCH_INTERVAL:-30}"

echo "FieldWatch watcher starting"
echo "Interface: $IFACE"
echo "Ingest URL: $INGEST_URL"
echo "Interval: ${INTERVAL}s"

while true; do
  iwinfo "$IFACE" scan 2>/dev/null | awk '
    /Cell/ {
      if (bssid != "") {
        print bssid "|" ssid "|" channel "|" signal
      }
      bssid=$5
      ssid=""
      channel=""
      signal=""
    }

    /ESSID:/ {
      gsub(/ESSID: /, "")
      gsub(/"/, "")
      ssid=$0
      sub(/^[ \t]+/, "", ssid)
    }

    /Channel:/ {
      channel=$2
    }

    /Signal:/ {
      signal=$2
    }

    END {
      if (bssid != "") {
        print bssid "|" ssid "|" channel "|" signal
      }
    }
  ' | while IFS='|' read -r bssid ssid channel signal; do
    [ -z "$bssid" ] && continue

    json=$(cat <<JSON
{
  "type": "AP_OBSERVED",
  "mac": "$bssid",
  "bssid": "$bssid",
  "ssid": "$ssid",
  "channel": "$channel",
  "signal": "$signal",
  "source": "fieldwatch-passive-ap-scan"
}
JSON
)

    curl -s -X POST "$INGEST_URL" \
      -H "Content-Type: application/json" \
      -d "$json" >/dev/null
  done

  sleep "$INTERVAL"
done
