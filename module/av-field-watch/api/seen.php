<?php
header('Content-Type: application/json');

$dataFile = __DIR__ . '/../data/seen_devices.json';

if (!file_exists($dataFile)) {
  file_put_contents($dataFile, json_encode(new stdClass(), JSON_PRETTY_PRINT));
}

$data = json_decode(file_get_contents($dataFile), true);

if (!is_array($data)) {
  $data = [];
}

uasort($data, function ($a, $b) {
  return strtotime($b['last_seen'] ?? '') <=> strtotime($a['last_seen'] ?? '');
});

echo json_encode($data, JSON_PRETTY_PRINT);
