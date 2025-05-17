document.addEventListener('DOMContentLoaded', function () {
  const button = document.getElementById('wpff-sp-run-now-button')
  const spinner = document.getElementById('wpff-sp-spinner')
  const resultBox = document.getElementById('wpff-sp-preload-result')

  if (!button || !spinner) return

  button.addEventListener('click', function () {
    // Disable all buttons
    document.querySelectorAll('button, input[type="submit"]').forEach(btn => (btn.disabled = true))
    spinner.classList.add('is-active')
    resultBox.textContent = wpff.i18n.running

    fetch(ajaxurl, {
      method: 'POST',
      credentials: 'same-origin',
      headers: {
        'Content-Type': 'application/x-www-form-urlencoded'
      },
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
