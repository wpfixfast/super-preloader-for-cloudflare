document.addEventListener('DOMContentLoaded', function () {
  const node = document.getElementById('wp-admin-bar-wpff-sp-preload')
  if (!node) return

  const link = node.querySelector('a.ab-item')
  const label = node.querySelector('.wpff-sp-preload-label')
  if (!link || !label) return

  let toastTimeout = null
  let pollInterval = null

  function showToast(message, isError) {
    const existing = document.querySelector('.wpff-sp-toast')
    if (existing) existing.remove()
    clearTimeout(toastTimeout)

    const toast = document.createElement('div')
    toast.className = 'wpff-sp-toast' + (isError ? ' wpff-sp-toast-error' : '')
    toast.textContent = message
    document.body.appendChild(toast)

    requestAnimationFrame(() => toast.classList.add('wpff-sp-toast-visible'))

    toastTimeout = setTimeout(() => {
      toast.classList.remove('wpff-sp-toast-visible')
      setTimeout(() => toast.remove(), 300)
    }, 4000)
  }

  // Busy covers the whole background run, not just the click's own request —
  // this is what keeps repeat clicks from spamming the server while it runs.
  function setBusy(isBusy, remaining) {
    node.classList.toggle('wpff-sp-admin-bar-loading', isBusy)

    if (!isBusy) {
      label.textContent = wpffSpAdminBar.i18n.startLabel
    } else if (typeof remaining === 'number') {
      label.textContent = wpffSpAdminBar.i18n.runningWithCount.replace('%d', remaining)
    } else {
      label.textContent = wpffSpAdminBar.i18n.running
    }
  }

  function stopPolling() {
    clearInterval(pollInterval)
    pollInterval = null
  }

  function pollStatus() {
    fetch(wpffSpAdminBar.ajaxUrl, {
      method: 'POST',
      credentials: 'same-origin',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
      body: new URLSearchParams({
        action: 'wpff_sp_get_status',
        nonce: wpffSpAdminBar.statusNonce
      })
    })
      .then(res => res.json())
      .then(data => {
        if (!data.success) return

        if (data.data.running) {
          setBusy(true, data.data.remaining)
        } else {
          stopPolling()
          setBusy(false)
          showToast(wpffSpAdminBar.i18n.complete)
        }
      })
      .catch(() => {
        // Silently fail — don't disrupt the UI on poll errors
      })
  }

  function startPolling() {
    if (pollInterval) return
    pollInterval = setInterval(pollStatus, 5000)
  }

  // PHP already rendered the real running state into the markup, so only
  // resume polling if we loaded onto a page where a run is genuinely active —
  // this is what makes the "complete" toast survive page navigation, and
  // means idle page loads never make a single status request.
  if (node.classList.contains('wpff-sp-admin-bar-loading')) {
    startPolling()
  }

  link.addEventListener('click', function (event) {
    event.preventDefault()

    if (node.classList.contains('wpff-sp-admin-bar-loading')) return

    setBusy(true)
    showToast(wpffSpAdminBar.i18n.starting)

    fetch(wpffSpAdminBar.ajaxUrl, {
      method: 'POST',
      credentials: 'same-origin',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
      body: new URLSearchParams({
        action: 'wpff_sp_run_preloader',
        nonce: wpffSpAdminBar.nonce
      })
    })
      .then(res => res.json())
      .then(data => {
        const payload = data.data || {}

        if (data.success) {
          const remaining = typeof payload.remaining === 'number' ? payload.remaining : 0
          let note = payload.done ? wpffSpAdminBar.i18n.complete : wpffSpAdminBar.i18n.remaining.replace('%d', remaining)
          if (payload.alreadyRunning) note = wpffSpAdminBar.i18n.alreadyRunning
          showToast(note)

          if (payload.done) {
            setBusy(false)
          } else {
            // Stay busy and keep polling until the background run actually finishes —
            // refresh the count now so the label doesn't wait for the first poll tick
            setBusy(true, remaining)
            startPolling()
          }
        } else {
          showToast(wpffSpAdminBar.i18n.error + (payload || wpffSpAdminBar.i18n.unknown), true)
          setBusy(false)
        }
      })
      .catch(() => {
        showToast(wpffSpAdminBar.i18n.ajaxFailed, true)
        setBusy(false)
      })
  })
})
