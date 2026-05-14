#!/bin/sh
IFACE="${1:-wlan2}"
INTERVAL="${FIELDWATCH_INTERVAL:-30}"
MODULE_DIR="/pineapple/ui/modules/av-field-watch"
SCAN_FILE="/tmp/fw_scan.txt"

echo "FieldWatch watcher starting on $IFACE every ${INTERVAL}s"

while true; do
  iwinfo "$IFACE" scan > "$SCAN_FILE" 2>/tmp/fw_scan_error.txt

  awk '
  function trim(s) {
    gsub(/^[ \t]+|[ \t]+$/, "", s)
    return s
  }

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
    sub(/^[ \t]*ESSID: /, "")
    gsub(/"/, "")
    ssid=trim($0)
  }

  /Mode: Master  Channel:/ {
    for (i=1; i<=NF; i++) {
      if ($i == "Channel:") {
        channel=$(i+1)
      }
    }
  }

  /Signal:/ {
    signal=$2
  }

  END {
    if (bssid != "") {
      print bssid "|" ssid "|" channel "|" signal
    }
  }
  ' "$SCAN_FILE" | while IFS='|' read -r bssid ssid channel signal; do
    [ -z "$bssid" ] && continue

    python3 - "$bssid" "$ssid" "$channel" "$signal" <<'PY'
import sys
import json
import pathlib
import time

bssid, ssid, channel, signal = sys.argv[1:5]

base = pathlib.Path('/pineapple/ui/modules/av-field-watch/data')
base.mkdir(parents=True, exist_ok=True)

events_file = base / 'events.json'
seen_file = base / 'seen_devices.json'
ignore_file = base / 'ignore_macs.json'

def load(path, default):
    if not path.exists():
        path.write_text(json.dumps(default, indent=2))
    try:
        return json.loads(path.read_text())
    except Exception:
        return default

def save(path, data):
    path.write_text(json.dumps(data, indent=2))

events = load(events_file, [])
seen = load(seen_file, {})
ignore = load(ignore_file, {})

now = time.strftime('%Y-%m-%dT%H:%M:%SZ', time.gmtime())
mac = bssid.upper()

if mac in ignore:
    sys.exit(0)

is_new = mac not in seen

if is_new:
    seen[mac] = {
        "first_seen": now,
        "last_seen": now,
        "count": 1,
        "last_ssid": ssid,
        "signals": [signal],
        "channels": [channel]
    }

    events.insert(0, {
        "id": "fw_%s_%s" % (int(time.time()), mac.replace(":", "")),
        "type": "NEW_AP",
        "mac": mac,
        "bssid": mac,
        "ssid": ssid,
        "signal": signal,
        "channel": channel,
        "source": "fieldwatch-watcher",
        "first_seen": now,
        "last_seen": now
    })
else:
    seen[mac]["last_seen"] = now
    seen[mac]["count"] = seen[mac].get("count", 0) + 1
    seen[mac]["last_ssid"] = ssid

    seen[mac].setdefault("signals", []).append(signal)
    seen[mac]["signals"] = seen[mac]["signals"][-20:]

    seen[mac].setdefault("channels", [])
    if channel not in seen[mac]["channels"]:
        seen[mac]["channels"].append(channel)

save(seen_file, seen)
save(events_file, events[:1000])
PY

  done

  sleep "$INTERVAL"
done
