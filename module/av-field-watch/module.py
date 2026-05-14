#!/usr/bin/env python3

from typing import Dict, Any
import json
import logging
import pathlib
import subprocess
import time

from pineapple.modules import Module, Request
from pineapple.jobs import JobManager

pathlib.Path('/tmp/modules').mkdir(parents=True, exist_ok=True)
module = Module('av-field-watch', logging.DEBUG)
manager = JobManager('av-field-watch', log_level=logging.DEBUG, module=module)

MODULE_PATH = pathlib.Path('/pineapple/ui/modules/av-field-watch')
DATA_PATH = MODULE_PATH / 'data'
EVENTS_FILE = DATA_PATH / 'events.json'
SEEN_FILE = DATA_PATH / 'seen_devices.json'

DATA_PATH.mkdir(parents=True, exist_ok=True)

def load_json(path, default):
    if not path.exists():
        path.write_text(json.dumps(default, indent=2))
    try:
        return json.loads(path.read_text())
    except Exception:
        return default

def save_json(path, data):
    path.write_text(json.dumps(data, indent=2))

def ingest_observation(payload: Dict[str, Any]):
    now = time.strftime('%Y-%m-%dT%H:%M:%SZ', time.gmtime())

    mac = str(payload.get('mac') or payload.get('bssid') or 'UNKNOWN').upper()
    ssid = str(payload.get('ssid') or '')
    signal = payload.get('signal')
    channel = payload.get('channel')

    events = load_json(EVENTS_FILE, [])
    seen = load_json(SEEN_FILE, {})

    is_new = mac not in seen

    if is_new:
        seen[mac] = {
            'first_seen': now,
            'last_seen': now,
            'count': 1,
            'last_ssid': ssid,
            'signals': [signal] if signal is not None else [],
            'channels': [channel] if channel is not None else []
        }

        events.insert(0, {
            'id': f'fw_{int(time.time())}_{mac.replace(":", "")}',
            'type': 'NEW_DEVICE',
            'mac': mac,
            'ssid': ssid,
            'signal': signal,
            'channel': channel,
            'source': 'fieldwatch',
            'first_seen': now,
            'last_seen': now
        })
    else:
        seen[mac]['last_seen'] = now
        seen[mac]['count'] = seen[mac].get('count', 0) + 1
        if ssid:
            seen[mac]['last_ssid'] = ssid
        if signal is not None:
            seen[mac].setdefault('signals', []).append(signal)
            seen[mac]['signals'] = seen[mac]['signals'][-20:]
        if channel is not None:
            channels = seen[mac].setdefault('channels', [])
            if channel not in channels:
                channels.append(channel)

    save_json(SEEN_FILE, seen)
    save_json(EVENTS_FILE, events[:1000])

    return {
        'ok': True,
        'new_device': is_new,
        'device': seen[mac]
    }

@module.handles_action('status')
def status(request: Request):
    return {
        'ok': True,
        'module': 'AeroVista FieldWatch',
        'version': '0.1.0',
        'mode': 'passive-observability'
    }

@module.handles_action('events')
def events(request: Request):
    return load_json(EVENTS_FILE, [])

@module.handles_action('seen')
def seen(request: Request):
    return load_json(SEEN_FILE, {})

@module.handles_action('ingest')
def ingest(request: Request):
    payload = request.body if isinstance(request.body, dict) else {}
    return ingest_observation(payload)

@module.handles_action('scan_once')
def scan_once(request: Request):
    iface = request.body.get('iface', 'wlan0') if isinstance(request.body, dict) else 'wlan0'

    try:
        result = subprocess.run(
            ['iwinfo', iface, 'scan'],
            capture_output=True,
            text=True,
            timeout=30
        )

        return {
            'ok': result.returncode == 0,
            'iface': iface,
            'stdout': result.stdout,
            'stderr': result.stderr
        }
    except Exception as e:
        return {
            'ok': False,
            'error': str(e)
        }


@module.handles_action('watcher_start')
def watcher_start(request: Request):
    iface = 'wlan2'

    if isinstance(request.body, dict):
        iface = request.body.get('iface', 'wlan2')

    result = subprocess.run(
        ['/pineapple/ui/modules/av-field-watch/scripts/fieldwatchctl.sh', 'start', iface],
        capture_output=True,
        text=True
    )

    return {
        'ok': result.returncode == 0,
        'stdout': result.stdout,
        'stderr': result.stderr
    }

@module.handles_action('watcher_stop')
def watcher_stop(request: Request):
    result = subprocess.run(
        ['/pineapple/ui/modules/av-field-watch/scripts/fieldwatchctl.sh', 'stop'],
        capture_output=True,
        text=True
    )

    return {
        'ok': result.returncode == 0,
        'stdout': result.stdout,
        'stderr': result.stderr
    }

@module.handles_action('watcher_status')
def watcher_status(request: Request):
    result = subprocess.run(
        ['/pineapple/ui/modules/av-field-watch/scripts/fieldwatchctl.sh', 'status'],
        capture_output=True,
        text=True
    )

    return {
        'ok': result.returncode == 0,
        'stdout': result.stdout,
        'stderr': result.stderr
    }

@module.handles_action('watcher_log')
def watcher_log(request: Request):
    result = subprocess.run(
        ['/pineapple/ui/modules/av-field-watch/scripts/fieldwatchctl.sh', 'log'],
        capture_output=True,
        text=True
    )

    return {
        'ok': result.returncode == 0,
        'stdout': result.stdout,
        'stderr': result.stderr
    }

if __name__ == '__main__':
    module.start()
