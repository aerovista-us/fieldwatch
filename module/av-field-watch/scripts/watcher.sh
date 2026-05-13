#!/bin/sh
# AeroVista FieldWatch watcher placeholder.
# v0.1 writes test events only. Live wireless parsing comes next.

MODULE_DIR="$(cd "$(dirname "$0")/.." && pwd)"
DATA_FILE="$MODULE_DIR/data/events.json"

[ -f "$DATA_FILE" ] || echo "[]" > "$DATA_FILE"

echo "FieldWatch watcher placeholder ready."
echo "Data file: $DATA_FILE"
