#!/bin/sh
MODULE_DIR="/pineapple/ui/modules/av-field-watch"
PID_FILE="/tmp/fieldwatch.pid"
LOG_FILE="/tmp/fieldwatch.log"
IFACE="${2:-wlan2}"

case "$1" in
  start)
    if [ -f "$PID_FILE" ] && kill -0 "$(cat "$PID_FILE")" 2>/dev/null; then
      echo "FieldWatch already running: $(cat "$PID_FILE")"
      exit 0
    fi

    "$MODULE_DIR/scripts/watcher.sh" "$IFACE" >> "$LOG_FILE" 2>&1 &
    echo $! > "$PID_FILE"
    echo "FieldWatch started on $IFACE: $(cat "$PID_FILE")"
    ;;

  stop)
    if [ -f "$PID_FILE" ]; then
      kill "$(cat "$PID_FILE")" 2>/dev/null
      rm -f "$PID_FILE"
      echo "FieldWatch stopped"
    else
      echo "FieldWatch not running"
    fi
    ;;

  status)
    if [ -f "$PID_FILE" ] && kill -0 "$(cat "$PID_FILE")" 2>/dev/null; then
      echo "FieldWatch running: $(cat "$PID_FILE")"
    else
      echo "FieldWatch stopped"
    fi
    ;;

  log)
    if [ -f "$LOG_FILE" ]; then
      tail -80 "$LOG_FILE"
    else
      echo "No FieldWatch log yet"
    fi
    ;;

  *)
    echo "Usage: $0 {start|stop|status|log} [iface]"
    exit 1
    ;;
esac
