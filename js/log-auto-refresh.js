document.addEventListener('DOMContentLoaded', function () {
  const checkbox = document.getElementById('wpff-sp-auto-refresh-logs')
  const countdown = document.getElementById('wpff-sp-refresh-countdown')
  const logOutput = document.getElementById('wpff-sp-log-output')

  if (!checkbox || !countdown || !logOutput) return

  let refreshInterval = null
  let countdownInterval = null
  let secondsLeft = 5

  function updateLogs() {
    const isScrolledToBottom = logOutput.scrollHeight - logOutput.scrollTop <= logOutput.clientHeight + 50

    fetch(ajaxurl, {
      method: 'POST',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
      body: new URLSearchParams({
        action: 'wpff_sp_get_logs',
        nonce: wpffSpLogs.nonce
      })
    })
      .then(r => r.text())
      .then(content => {
        logOutput.textContent = content
        if (isScrolledToBottom) {
          logOutput.scrollTop = logOutput.scrollHeight
        }
      })
  }

  function startCountdown() {
    secondsLeft = 5
    countdown.textContent = `(${secondsLeft}s)`

    clearInterval(countdownInterval)
    countdownInterval = setInterval(() => {
      secondsLeft--
      countdown.textContent = secondsLeft > 0 ? `(${secondsLeft}s)` : ''
    }, 1000)
  }

  checkbox.addEventListener('change', function () {
    if (this.checked) {
      updateLogs()
      startCountdown()
      refreshInterval = setInterval(() => {
        updateLogs()
        startCountdown()
      }, 5000)
    } else {
      clearInterval(refreshInterval)
      clearInterval(countdownInterval)
      countdown.textContent = ''
    }
  })
})
