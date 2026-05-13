<?php
header('Content-Type: application/json');

$dataFile = __DIR__ . '/../data/events.json';

if (!file_exists($dataFile)) {
  file_put_contents($dataFile, json_encode([]));
}

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
  echo file_get_contents($dataFile);
  exit;
}

if ($method === 'POST') {
  $raw = file_get_contents('php://input');
  $input = json_decode($raw, true);

  if (!$input) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'Invalid JSON']);
    exit;
  }

  $events = json_decode(file_get_contents($dataFile), true);
  if (!is_array($events)) {
    $events = [];
  }

  $event = [
    'id' => uniqid('fw_', true),
    'type' => $input['type'] ?? 'TEST_EVENT',
    'ssid' => $input['ssid'] ?? null,
    'mac' => $input['mac'] ?? null,
    'bssid' => $input['bssid'] ?? null,
    'channel' => $input['channel'] ?? null,
    'signal' => $input['signal'] ?? null,
    'source' => $input['source'] ?? 'fieldwatch',
    'first_seen' => $input['first_seen'] ?? date('c'),
    'last_seen' => $input['last_seen'] ?? date('c')
  ];

  array_unshift($events, $event);
  $events = array_slice($events, 0, 500);

  file_put_contents($dataFile, json_encode($events, JSON_PRETTY_PRINT));

  echo json_encode(['ok' => true, 'event' => $event], JSON_PRETTY_PRINT);
  exit;
}

http_response_code(405);
echo json_encode(['ok' => false, 'error' => 'Method not allowed']);
