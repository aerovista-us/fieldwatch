async function loadStatus() {
  const el = document.getElementById('status');

  try {
    const res = await fetch('../api/status.php');
    const data = await res.json();
    el.textContent = JSON.stringify(data, null, 2);
  } catch (err) {
    el.textContent = 'Status endpoint not reachable yet. Module shell loaded.';
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
        <strong>${event.type || 'EVENT'}</strong>
        <span>${event.ssid || event.mac || event.bssid || 'unknown'}</span>
        <small>${event.last_seen || ''}</small>
      </div>
    `).join('');
  } catch (err) {
    el.innerHTML = '<p class="muted">Events endpoint not reachable yet.</p>';
  }
}

async function addTestEvent() {
  await fetch('../api/events.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({
      type: 'TEST_EVENT',
      ssid: 'FieldWatch Lab',
      mac: 'AA:BB:CC:DD:EE:FF',
      channel: 6,
      signal: -42
    })
  });

  await loadEvents();
}

document.addEventListener('DOMContentLoaded', () => {
  loadStatus();
  loadEvents();

  const btn = document.getElementById('add-test-event');
  if (btn) btn.addEventListener('click', addTestEvent);
});
