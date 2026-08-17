document.addEventListener('DOMContentLoaded', function () {
  const button = document.getElementById('wpff-sp-run-now-button')
  const stopForm = document.getElementById('wpff-sp-stop-preloader-form')
  const spinner = document.getElementById('wpff-sp-spinner')
  const resultBox = document.getElementById('wpff-sp-preload-result')
  const statusBadge = document.getElementById('wpff-sp-status-badge')
  const remainingTag = document.getElementById('wpff-sp-sidebar-remaining')
  const logBox = document.getElementById('wpff-sp-log-output')

  let statusPollInterval = null

  // Auto scroll to the bottom of the log output box
  if (logBox) logBox.scrollTop = logBox.scrollHeight

  // ============================================================
  // Worker Status card — checked once on page load (the server already
  // renders the last cached value, or "Checking…" if nothing's cached yet;
  // this just confirms/refreshes it) and again after Connect & Deploy or
  // Disconnect succeed, since either of those changes which Worker is in
  // effect and the sidebar badge would otherwise keep showing the stale
  // pre-action state until the next full page load.
  // ============================================================
  const workerStatusBadge = document.getElementById('wpff-sp-worker-status-badge')
  const workerStatusMap = {
    checking: { text: wpff.i18n.workerStatusChecking, cls: 'wpff-sp-status-idle' },
    deploying: { text: wpff.i18n.workerStatusDeploying, cls: 'wpff-sp-status-idle' },
    working: { text: wpff.i18n.workerStatusWorking, cls: 'wpff-sp-status-connected' },
    not_deployed: { text: wpff.i18n.workerStatusNotDeployed, cls: 'wpff-sp-status-idle' },
    not_responding: { text: wpff.i18n.workerStatusNotResponding, cls: 'wpff-sp-status-error' }
  }

  function applyWorkerStatus(status) {
    const entry = workerStatusMap[status]
    if (!workerStatusBadge || !entry) return
    workerStatusBadge.textContent = entry.text
    workerStatusBadge.className = 'wpff-sp-status-badge ' + entry.cls
  }

  const connectionDeletedWarning = document.getElementById('wpff-sp-connection-deleted-warning')

  // Resolves with { status, deleted } (deleted is true/false/null — only ever
  // non-null when checkDeletion was requested and status is
  // 'not_responding'), or null on a network/AJAX failure. force=true bypasses
  // the server-side cache — required while polling after a deploy, since the
  // very first (correctly negative, Worker not live yet) result would
  // otherwise get cached for a minute and every later poll would just keep
  // reading that same stale answer instead of checking again.
  function fetchWorkerStatus(force, checkDeletion) {
    return fetch(ajaxurl, {
      method: 'POST',
      credentials: 'same-origin',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
      body: new URLSearchParams({
        action: 'wpff_sp_get_worker_status',
        nonce: wpff.workerStatusNonce,
        force: force ? '1' : '0',
        check_deletion: checkDeletion ? '1' : '0'
      })
    })
      .then(res => res.json())
      .then(data => (data.success ? data.data : null))
      .catch(() => null)
  }

  function checkWorkerStatus() {
    if (!workerStatusBadge) return
    // Silently leave whatever was last shown on failure rather than
    // clobbering it with nothing on a network blip. Requests the deletion
    // check too — this is the page-load/refresh path, not the post-deploy
    // polling loop, so there's no risk of wastefully re-checking deletion
    // on a Worker that was only just created.
    fetchWorkerStatus(false, true).then(function (data) {
      if (!data) return
      applyWorkerStatus(data.status)
      if (connectionDeletedWarning) {
        connectionDeletedWarning.style.display = data.deleted ? '' : 'none'
      }
    })
  }

  // A freshly deployed Worker can take up to ~10s to actually become
  // routable on *.workers.dev. A single fixed delay is fragile — too short
  // and it still catches a false "Not Responding", too long and it wastes
  // time when the Worker happens to be ready sooner. Poll a few times
  // instead, stopping as soon as it reports "working" (~12s window, 6
  // attempts 2s apart — comfortably covers the observed ~10s while
  // resolving noticeably faster than a coarser interval would). The
  // reassuring "waiting" badge stays up for the whole window rather than
  // flashing a false "Not Responding" on an early attempt that just
  // caught the Worker before it was live yet — only the final outcome
  // (working, or a genuine failure once attempts are exhausted) replaces it.
  function pollWorkerStatusAfterDeploy(attempt) {
    attempt = attempt || 0

    fetchWorkerStatus(true, false).then(function (data) {
      const status = data && data.status
      if (status === 'working' || attempt >= 6) {
        if (status) applyWorkerStatus(status)
        return
      }

      setTimeout(function () {
        pollWorkerStatusAfterDeploy(attempt + 1)
      }, 2000)
    })
  }

  checkWorkerStatus()

  // ============================================================
  // Show immediate feedback when opening the Exclusions tab — it
  // can take a couple seconds (sitemap re-fetch), and a full page
  // reload gives no other chance to signal "working" in the meantime.
  // ============================================================
  const exclusionsTabLink = document.querySelector('.nav-tab-wrapper a[href*="tab=exclusions"]')
  if (exclusionsTabLink) {
    exclusionsTabLink.addEventListener('click', function () {
      const overlay = document.createElement('div')
      overlay.className = 'wpff-sp-tab-loading-overlay'
      overlay.innerHTML = '<span class="spinner is-active"></span><span>' + wpff.i18n.loadingTab + '</span>'
      document.body.appendChild(overlay)
    })
  }

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

  function setRemainingTag(remaining) {
    if (!remainingTag) return
    remainingTag.textContent = typeof remaining === 'number' ? wpff.i18n.remainingTag.replace('%d', remaining) : ''
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
          setRemainingTag(data.data.remaining)
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
  // Cloudflare connect / disconnect
  // ============================================================
  const deployButton = document.getElementById('wpff-sp-deploy-worker-button')
  const deploySpinner = document.getElementById('wpff-sp-deploy-spinner')
  const deployResult = document.getElementById('wpff-sp-deploy-result')
  const tokenField = document.getElementById('wpff_sp_cf_api_token')
  const accountField = document.getElementById('wpff_sp_cf_account_id')
  const accountPicker = document.getElementById('wpff-sp-account-id-picker')
  const accountRow = document.getElementById('wpff-sp-auto-deploy-account-row')
  const tokenRow = document.getElementById('wpff-sp-auto-deploy-token-row')
  const buttonRow = document.getElementById('wpff-sp-auto-deploy-button-row')
  const connectedRow = document.getElementById('wpff-sp-connected-row')
  const connectionAccountRow = document.getElementById('wpff-sp-connection-account-row')
  const connectionAccountName = document.getElementById('wpff-sp-connection-account-name')
  const connectionWorkerUrl = document.getElementById('wpff-sp-connection-worker-url')
  const disconnectButton = document.getElementById('wpff-sp-disconnect-button')
  const disconnectSpinner = document.getElementById('wpff-sp-disconnect-spinner')
  const disconnectResult = document.getElementById('wpff-sp-disconnect-result')

  function showConnected(workerUrl, accountName) {
    if (tokenRow) tokenRow.style.display = 'none'
    if (accountRow) accountRow.style.display = 'none'
    if (buttonRow) buttonRow.style.display = 'none'
    if (connectedRow) connectedRow.style.display = ''
    if (connectionWorkerUrl) connectionWorkerUrl.textContent = workerUrl
    if (connectionAccountRow) connectionAccountRow.style.display = accountName ? '' : 'none'
    if (connectionAccountName) connectionAccountName.textContent = accountName || ''
  }

  function setResultMessage(el, text, isError) {
    el.textContent = text
    el.classList.toggle('wpff-sp-result-error', !!isError)
  }

  // Populate the Account ID picker with the real account names/IDs the
  // token has access to, instead of asking the user to copy-paste an ID
  // out of an error message. The plain text input stays the actual form
  // field (name="cf_account_id") — the picker just writes into it.
  function populateAccountPicker(accounts) {
    if (!accountPicker || !accountField || !Array.isArray(accounts)) return

    accountPicker.querySelectorAll('option[data-account]').forEach(opt => opt.remove())

    accounts.forEach(account => {
      const option = document.createElement('option')
      option.value = account.id
      option.textContent = account.name
      option.setAttribute('data-account', '1')
      accountPicker.appendChild(option)
    })

    accountPicker.style.display = ''
    accountField.style.display = 'none'
    accountPicker.focus()
  }

  if (accountPicker && accountField) {
    accountPicker.addEventListener('change', function () {
      accountField.value = accountPicker.value
    })
  }

  if (deployButton && deploySpinner && deployResult) {
    deployButton.addEventListener('click', function () {
      const token = tokenField ? tokenField.value.trim() : ''

      if (!token) {
        setResultMessage(deployResult, wpff.i18n.deployMissing, true)
        return
      }

      document.querySelectorAll('button, input[type="submit"]').forEach(btn => (btn.disabled = true))
      deploySpinner.classList.add('is-active')
      setResultMessage(deployResult, wpff.i18n.deploying, false)

      fetch(ajaxurl, {
        method: 'POST',
        credentials: 'same-origin',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: new URLSearchParams({
          action: 'wpff_sp_deploy_worker',
          nonce: wpff.deployNonce,
          cf_api_token: token,
          cf_account_id: accountField ? accountField.value.trim() : ''
        })
      })
        .then(res => res.json())
        .then(data => {
          if (data.success) {
            showConnected(data.data.workerUrl, data.data.accountName)
            applyWorkerStatus('deploying')
            pollWorkerStatusAfterDeploy()
            setResultMessage(deployResult, wpff.i18n.deploySuccess, false)
          } else {
            const payload = data.data || {}
            if (payload.code === 'multiple_accounts') {
              // Not a hard error (the token is fine, it just needs the user
              // to pick which of its accounts to use) so no "Error:" prefix
              // — but still styled like one (red, spaced) for consistency
              // with every other result message.
              if (accountRow) accountRow.style.display = 'table-row'
              populateAccountPicker(payload.accounts)
              setResultMessage(deployResult, payload.message || wpff.i18n.unknown, true)
            } else {
              setResultMessage(deployResult, wpff.i18n.error + (payload.message || wpff.i18n.unknown), true)
            }
          }
        })
        .catch(() => {
          setResultMessage(deployResult, wpff.i18n.ajaxFailed, true)
        })
        .finally(() => {
          deploySpinner.classList.remove('is-active')
          document.querySelectorAll('button, input[type="submit"]').forEach(btn => (btn.disabled = false))
        })
    })
  }

  if (disconnectButton && disconnectSpinner && disconnectResult) {
    disconnectButton.addEventListener('click', function () {
      if (!window.confirm(wpff.i18n.disconnectConfirm)) return

      document.querySelectorAll('button, input[type="submit"]').forEach(btn => (btn.disabled = true))
      disconnectSpinner.classList.add('is-active')
      setResultMessage(disconnectResult, wpff.i18n.disconnecting, false)

      fetch(ajaxurl, {
        method: 'POST',
        credentials: 'same-origin',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: new URLSearchParams({
          action: 'wpff_sp_disconnect_worker',
          nonce: wpff.disconnectNonce
        })
      })
        .then(res => res.json())
        .then(data => {
          if (data.success) {
            // Reload rather than hand-resetting every piece of JS state
            // (token/account fields, the account picker, panel visibility,
            // the status badge) — the server-rendered page is the single
            // source of truth, and manually mirroring it here is exactly
            // how the leftover Account ID value slipped through before.
            window.location.reload()
            return
          }

          // Leave the connected card as-is on failure — the Worker may
          // still be live on Cloudflare, so don't strand the UI in a
          // state that implies it was removed.
          const payload = data.data || {}
          setResultMessage(disconnectResult, wpff.i18n.error + (payload.message || wpff.i18n.unknown), true)
        })
        .catch(() => {
          setResultMessage(disconnectResult, wpff.i18n.ajaxFailed, true)
        })
        .finally(() => {
          disconnectSpinner.classList.remove('is-active')
          document.querySelectorAll('button, input[type="submit"]').forEach(btn => (btn.disabled = false))
        })
    })
  }

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
