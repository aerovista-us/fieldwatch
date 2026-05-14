async function loadStatus() {
  const el = document.getElementById('status');

  try {
    const res = await fetch('../api/status.php');
    const data = await res.json();
    el.textContent = JSON.stringify(data, null, 2);
  } catch (err) {
    el.textContent = 'Status endpoint not reachable yet.';
  }
}

async function loadEvents() {
  const el = document.getElementById('events');

  try {
    const res = await fetch('../api/events.php');
    const events = await res.json();

    if (!events.length) {
      el.innerHTML = '<p class="muted">No events logged yet.</p>';
      return;
    }

    el.innerHTML = events.map(event => `
      <div class="event">
        <strong>${event.type}</strong>
        <span>${event.ssid || event.mac}</span>
        <small>${event.last_seen}</small>
      </div>
    `).join('');
  } catch (err) {
    el.innerHTML = '<p class="muted">Events endpoint unreachable.</p>';
  }
}

async function loadSeenDevices() {
  const el = document.getElementById('seen-devices');

  try {
    const res = await fetch('../api/seen.php');
    const devices = await res.json();

    const entries = Object.entries(devices);

    if (!entries.length) {
      el.innerHTML = '<p class="muted">No known devices yet.</p>';
      return;
    }

    el.innerHTML = entries.map(([mac, device]) => `
      <div class="event">
        <strong>${mac}</strong>
        <span>${device.last_ssid || 'unknown ssid'}</span>
        <small>
          Seen ${device.count}x · Last ${device.last_seen}
        </small>
      </div>
    `).join('');
  } catch (err) {
    el.innerHTML = '<p class="muted">Seen device endpoint unreachable.</p>';
  }
}

async function addTestEvent() {
  await fetch('../api/ingest.php', {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json'
    },
    body: JSON.stringify({
      type: 'DEVICE_OBSERVED',
      mac: 'AA:BB:CC:DD:EE:FF',
      ssid: 'TestLab',
      signal: -42,
      channel: 6
    })
  });

  await loadEvents();
  await loadSeenDevices();
}

document.addEventListener('DOMContentLoaded', () => {
  loadStatus();
  loadEvents();
  loadSeenDevices();

  const btn = document.getElementById('add-test-event');

  if (btn) {
    btn.addEventListener('click', addTestEvent);
  }
});
