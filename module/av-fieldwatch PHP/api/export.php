<?php
$dataFile = __DIR__ . '/../data/events.json';
$events = file_exists($dataFile) ? json_decode(file_get_contents($dataFile), true) : [];

header('Content-Type: text/csv');
header('Content-Disposition: attachment; filename="fieldwatch-events.csv"');

$out = fopen('php://output', 'w');
fputcsv($out, ['id', 'type', 'ssid', 'mac', 'bssid', 'channel', 'signal', 'source', 'first_seen', 'last_seen']);

foreach ($events as $event) {
  fputcsv($out, [
    $event['id'] ?? '',
    $event['type'] ?? '',
    $event['ssid'] ?? '',
    $event['mac'] ?? '',
    $event['bssid'] ?? '',
    $event['channel'] ?? '',
    $event['signal'] ?? '',
    $event['source'] ?? '',
    $event['first_seen'] ?? '',
    $event['last_seen'] ?? ''
  ]);
}

fclose($out);
