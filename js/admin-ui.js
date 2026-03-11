document.addEventListener('DOMContentLoaded', function () {
  const button = document.getElementById('wpff-sp-run-now-button')
  const stopForm = document.getElementById('wpff-sp-stop-preloader-form')
  const spinner = document.getElementById('wpff-sp-spinner')
  const resultBox = document.getElementById('wpff-sp-preload-result')
  const statusBadge = document.querySelector('.wpff-sp-status-badge')
  const logBox = document.getElementById('wpff-sp-log-output')

  let statusPollInterval = null

  // Auto scroll to the bottom of the log output box
  if (logBox) logBox.scrollTop = logBox.scrollHeight

  // ============================================================
  // Update UI based on running state
  // ============================================================
  function setRunningState(isRunning) {
    if (button) button.style.display = isRunning ? 'none' : ''
    if (stopForm) stopForm.style.display = isRunning ? 'block' : 'none'

    if (statusBadge) {
      if (isRunning) {
        statusBadge.textContent = wpff.i18n.statusRunning
        statusBadge.classList.remove('wpff-sp-status-idle')
        statusBadge.classList.add('wpff-sp-status-running')
      } else {
        statusBadge.textContent = wpff.i18n.statusIdle
        statusBadge.classList.remove('wpff-sp-status-running')
        statusBadge.classList.add('wpff-sp-status-idle')
      }
    }
  }

  // ============================================================
  // Poll server for real running state
  // ============================================================
  function pollStatus() {
    fetch(ajaxurl, {
      method: 'POST',
      credentials: 'same-origin',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
      body: new URLSearchParams({
        action: 'wpff_sp_get_status',
        nonce: wpff.statusNonce
      })
    })
      .then(res => res.json())
      .then(data => {
        if (data.success) {
          setRunningState(data.data.running)
          // if (!data.data.running) {
          //   clearInterval(statusPollInterval)
          //   statusPollInterval = null
          // }
        }
      })
      .catch(() => {
        // Silently fail — don't disrupt the UI on poll errors
      })
  }

  function startPolling() {
    if (statusPollInterval) return
    statusPollInterval = setInterval(pollStatus, 5000)
  }

  // ============================================================
  // Initial status check and polling on page load
  // ============================================================
  pollStatus()
  startPolling()

  // ============================================================
  // Start Manual Preload button
  // ============================================================
  if (!button || !spinner) return

  button.addEventListener('click', function () {
    document.querySelectorAll('button, input[type="submit"]').forEach(btn => (btn.disabled = true))
    spinner.classList.add('is-active')
    resultBox.textContent = wpff.i18n.running
    //setRunningState(true) Optimistically update status immediately

    fetch(ajaxurl, {
      method: 'POST',
      credentials: 'same-origin',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
      body: new URLSearchParams({
        action: 'wpff_sp_run_preloader',
        nonce: wpff.nonce
      })
    })
      .then(res => res.json())
      .then(data => {
        spinner.classList.remove('is-active')
        const payload = data.data || {}

        if (data.success) {
          const message = payload.message || ''
          const remaining = typeof payload.remaining === 'number' ? payload.remaining : 0
          const note = payload.done ? wpff.i18n.complete : wpff.i18n.remaining.replace('%d', remaining)
          resultBox.innerHTML = `${message}<br>${note}`
        } else {
          resultBox.textContent = wpff.i18n.error + (payload || wpff.i18n.unknown)
        }
      })
      .catch(() => {
        spinner.classList.remove('is-active')
        resultBox.textContent = wpff.i18n.ajaxFailed
      })
      .finally(() => {
        document.querySelectorAll('button, input[type="submit"]').forEach(btn => (btn.disabled = false))
      })
  })
})
