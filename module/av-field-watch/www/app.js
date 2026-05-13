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

loadStatus();
