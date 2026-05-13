<?php
header('Content-Type: application/json');

echo json_encode([
  'ok' => true,
  'module' => 'AeroVista FieldWatch',
  'version' => '0.1.0',
  'mode' => 'passive-observability',
  'features' => [
    'status_endpoint',
    'dashboard_shell',
    'local_event_store_planned',
    'new_mac_detection_planned',
    'new_ssid_detection_planned'
  ],
  'timestamp' => date('c')
], JSON_PRETTY_PRINT);
