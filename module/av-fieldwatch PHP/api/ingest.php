<?php
header('Content-Type: application/json');

$baseDir = realpath(__DIR__ . '/../data');

$eventsFile = $baseDir . '/events.json';
$seenFile = $baseDir . '/seen_devices.json';
$watchMacsFile = $baseDir . '/watch_macs.json';
$watchSsidsFile = $baseDir . '/watch_ssids.json';

function loadJson($path, $default = []) {
  if (!file_exists($path)) {
    file_put_contents($path, json_encode($default, JSON_PRETTY_PRINT));
  }

  $data = json_decode(file_get_contents($path), true);

  return is_array($data) ? $data : $default;
}

function saveJson($path, $data) {
  file_put_contents($path, json_encode($data, JSON_PRETTY_PRINT));
}

$input = json_decode(file_get_contents('php://input'), true);

if (!$input) {
  http_response_code(400);

  echo json_encode([
    'ok' => false,
    'error' => 'Invalid JSON'
  ]);

  exit;
}

$mac = strtoupper(trim($input['mac'] ?? 'UNKNOWN'));
$ssid = trim($input['ssid'] ?? '');
$type = trim($input['type'] ?? 'DEVICE_OBSERVED');
$signal = $input['signal'] ?? null;
$channel = $input['channel'] ?? null;

$now = date('c');

$events = loadJson($eventsFile, []);
$seen = loadJson($seenFile, []);
$watchMacs = loadJson($watchMacsFile, []);
$watchSsids = loadJson($watchSsidsFile, []);

$isNewDevice = !isset($seen[$mac]);

if ($isNewDevice) {
  $seen[$mac] = [
    'first_seen' => $now,
    'last_seen' => $now,
    'count' => 1,
    'vendor' => null,
    'last_ssid' => $ssid,
    'signals' => [$signal],
    'channels' => [$channel]
  ];
} else {
  $seen[$mac]['last_seen'] = $now;
  $seen[$mac]['count']++;

  if ($ssid) {
    $seen[$mac]['last_ssid'] = $ssid;
  }

  if ($signal !== null) {
    $seen[$mac]['signals'][] = $signal;
    $seen[$mac]['signals'] = array_slice($seen[$mac]['signals'], -20);
  }

  if ($channel !== null) {
    $seen[$mac]['channels'][] = $channel;
    $seen[$mac]['channels'] = array_unique($seen[$mac]['channels']);
  }
}

saveJson($seenFile, $seen);

$watchedMac = isset($watchMacs[$mac]);
$watchedSsid = $ssid && isset($watchSsids[$ssid]);

$shouldCreateEvent =
  $isNewDevice ||
  $watchedMac ||
  $watchedSsid;

if ($shouldCreateEvent) {
  $eventType = 'DEVICE_SEEN';

  if ($isNewDevice) {
    $eventType = 'NEW_DEVICE';
  }

  if ($watchedMac) {
    $eventType = 'WATCHED_MAC';
  }

  if ($watchedSsid) {
    $eventType = 'WATCHED_SSID';
  }

  $event = [
    'id' => uniqid('fw_', true),
    'type' => $eventType,
    'mac' => $mac,
    'ssid' => $ssid,
    'signal' => $signal,
    'channel' => $channel,
    'source' => 'fieldwatch',
    'first_seen' => $seen[$mac]['first_seen'],
    'last_seen' => $now
  ];

  array_unshift($events, $event);

  $events = array_slice($events, 0, 1000);

  saveJson($eventsFile, $events);
}

echo json_encode([
  'ok' => true,
  'new_device' => $isNewDevice,
  'watched_mac' => $watchedMac,
  'watched_ssid' => $watchedSsid,
  'device' => $seen[$mac]
], JSON_PRETTY_PRINT);
