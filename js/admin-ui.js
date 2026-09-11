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

        // Confirmed live — reload once, now that there's a real outcome to
        // show, so the sidebar's Cache Coverage card (rendered from
        // whatever state existed at the original page load, and otherwise
        // never told anything changed) picks up the freshly connected
        // token/zone instead of sitting stale until a manual refresh.
        if (status === 'working') {
          window.location.reload()
        }
        return
      }

      setTimeout(function () {
        pollWorkerStatusAfterDeploy(attempt + 1)
      }, 2000)
    })
  }

  checkWorkerStatus()

  // ============================================================
  // Cache Coverage (sidebar card + Stats tab card) — real visitor
  // cache-hit/miss data pulled live from Cloudflare Analytics. Server
  // already knows whether it's configured (see sidebar.php/stats-table.php)
  // and renders either a "grant permission" prompt or a skeleton — these
  // fetches only ever run when the skeleton is actually present.
  // ============================================================
  // Resolves with { ok: true, summary } on success, or { ok: false, message }
  // on failure — including a permission-denied response from Cloudflare
  // itself (e.g. a token that predates the Analytics Read scope), whose
  // message is worth showing rather than collapsing into a silent dash.
  function fetchCacheCoverage(range, force) {
    return fetch(ajaxurl, {
      method: 'POST',
      credentials: 'same-origin',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
      body: new URLSearchParams({
        action: 'wpff_sp_get_cache_coverage',
        nonce: wpff.cacheCoverageNonce,
        range: range,
        force: force ? '1' : '0'
      })
    })
      .then(res => res.json())
      .then(data => {
        if (data.success) return { ok: true, summary: data.data }
        return { ok: false, message: (data.data && data.data.message) || wpff.i18n.ajaxFailed }
      })
      .catch(() => ({ ok: false, message: wpff.i18n.ajaxFailed }))
  }

  function unSkeleton(el) {
    if (el) el.classList.remove('wpff-sp-skeleton')
  }

  function formatPct(value) {
    return typeof value === 'number' ? value + '%' : '—'
  }

  // Fills the "%1$d requests - %2$d missed" i18n templates — same
  // positional tokens sprintf() uses server-side, so one translated string
  // works for both the PHP-rendered and AJAX-refreshed versions.
  function formatRequestsMissed(template, requests, misses) {
    return template.replace('%1$d', requests).replace('%2$d', misses)
  }

  // Sidebar card — trailing 24h only. Wrapped in a named function so the
  // manual refresh button can re-run it with force=true, not just the
  // initial page-load fetch.
  const coverageSidebarHitRate = document.getElementById('wpff-sp-coverage-hitrate')
  const coverageRefreshButton = document.getElementById('wpff-sp-coverage-refresh-button')
  const coverageRefreshIcon = document.getElementById('wpff-sp-coverage-refresh-icon')

  function loadSidebarCoverage(force) {
    if (!coverageSidebarHitRate) return

    if (force) {
      coverageSidebarHitRate.classList.add('wpff-sp-skeleton')
      if (coverageRefreshIcon) coverageRefreshIcon.classList.add('wpff-sp-icon-spinning')
      if (coverageRefreshButton) coverageRefreshButton.disabled = true
    }

    // A manual refresh clears both windows — the sidebar only ever displays
    // the 24h figures, but the Stats tab's 7-day data has its own separate
    // (6-hour) cache and should be fresh by the time the user gets there
    // too, rather than stuck showing whatever was last cached.
    const sevenDayRefresh = force ? fetchCacheCoverage('7d', true) : Promise.resolve(null)

    Promise.all([fetchCacheCoverage('24h', force), sevenDayRefresh]).then(function (results) {
      const result = results[0]
      const missesEl = document.getElementById('wpff-sp-coverage-misses')
      const reportLinkEl = document.getElementById('wpff-sp-coverage-report-link')
      unSkeleton(coverageSidebarHitRate)
      if (coverageRefreshIcon) coverageRefreshIcon.classList.remove('wpff-sp-icon-spinning')
      if (coverageRefreshButton) coverageRefreshButton.disabled = false

      if (!result.ok) {
        coverageSidebarHitRate.textContent = '—'
        if (missesEl) missesEl.textContent = result.message
        // The report link promises a working Stats tab section — don't show
        // it when the fetch itself failed (e.g. a permission error), since
        // the Stats tab card would just show the same failure.
        if (reportLinkEl) reportLinkEl.style.display = 'none'
        return
      }
      const data = result.summary
      coverageSidebarHitRate.textContent = formatPct(data.hitRatePct)
      if (missesEl) missesEl.textContent = formatRequestsMissed(wpff.i18n.coverageMissesToday, data.requests, data.misses)
    })
  }

  // Only auto-load on page open when the server actually deferred to a
  // skeleton (cache was cold) — when it rendered the real cached numbers
  // directly, re-fetching the same value via AJAX would just be a wasted
  // round trip (and risk a pointless skeleton flash).
  if (coverageSidebarHitRate && coverageSidebarHitRate.dataset.wpffSpLoading === '1') {
    loadSidebarCoverage(false)
  }

  if (coverageRefreshButton) {
    coverageRefreshButton.addEventListener('click', function () {
      loadSidebarCoverage(true)
    })
  }

  // Both the 24h and 7d/worst fetches below can fail for the same
  // underlying reason (usually a token missing the Analytics permission).
  // Rather than let the skeleton cards sit fully visible for the whole
  // fetch and then disappear on failure (a jarring flash), hide them
  // immediately — before either fetch even starts — and only reveal them
  // once we know at least one call actually succeeded. On failure they
  // simply stay hidden and the single error message (shown once, not
  // repeated per tile) is all that appears.
  const wpffSpCoverageContentEls = document.querySelectorAll(
    '.wpff-sp-coverage-tiles, .wpff-sp-coverage-table-caption, .wpff-sp-coverage-table-wrapper, .wpff-sp-coverage-suggestion'
  )

  function hideCoverageContent() {
    wpffSpCoverageContentEls.forEach(function (el) {
      el.style.display = 'none'
    })
  }

  function revealCoverageContent() {
    wpffSpCoverageContentEls.forEach(function (el) {
      el.style.display = ''
    })
  }

  function showCoverageError(message) {
    const errorEl = document.getElementById('wpff-sp-coverage-error')
    if (errorEl) {
      errorEl.textContent = message
      errorEl.style.display = ''
    }

    hideCoverageContent()
  }

  // Coverage tab — "Today (24h)" tile. Only fetches when the server
  // actually deferred (cache was cold) — a data-wpff-sp-loading marker is
  // only present in that case, so a warm-cache render (already showing
  // the real numbers) never triggers a redundant AJAX call.
  const coverageTodayHitRate = document.getElementById('wpff-sp-coverage-today-hitrate')
  const coverage7dHitRate = document.getElementById('wpff-sp-coverage-7d-hitrate')
  const coverageTodayIsLoading = coverageTodayHitRate && coverageTodayHitRate.dataset.wpffSpLoading === '1'
  const coverage7dIsLoading = coverage7dHitRate && coverage7dHitRate.dataset.wpffSpLoading === '1'

  // Only pre-hide when BOTH are cold — if one side already has a warm,
  // server-rendered cache, there's no reason to hide its real data before
  // even knowing whether the other fetch will succeed.
  if (coverageTodayIsLoading && coverage7dIsLoading) {
    hideCoverageContent()
  }

  if (coverageTodayIsLoading) {
    fetchCacheCoverage('24h').then(function (result) {
      const missesEl = document.getElementById('wpff-sp-coverage-today-misses')
      unSkeleton(coverageTodayHitRate)
      unSkeleton(missesEl)
      if (!result.ok) {
        coverageTodayHitRate.textContent = '—'
        if (missesEl) missesEl.textContent = ''
        showCoverageError(result.message)
        return
      }
      revealCoverageContent()
      const data = result.summary
      coverageTodayHitRate.textContent = formatPct(data.hitRatePct)
      if (missesEl) missesEl.textContent = formatRequestsMissed(wpff.i18n.coverageMissesToday, data.requests, data.misses)
    })
  }

  // Coverage tab — trailing 7d tiles, worst-country callout, per-country
  // table and the suggested-proxy-countries summary. Same deferred-only
  // fetch condition as the 24h tile above.
  if (coverage7dIsLoading) {
    fetchCacheCoverage('7d').then(function (result) {
      const missesEl = document.getElementById('wpff-sp-coverage-7d-misses')
      const worstNameEl = document.getElementById('wpff-sp-coverage-worst-country')
      const worstMissesEl = document.getElementById('wpff-sp-coverage-worst-misses')
      const tableBody = document.getElementById('wpff-sp-coverage-table-body')
      const suggestedEl = document.getElementById('wpff-sp-coverage-suggested')

      unSkeleton(coverage7dHitRate)
      unSkeleton(missesEl)
      unSkeleton(worstNameEl)
      unSkeleton(worstMissesEl)

      if (!result.ok) {
        coverage7dHitRate.textContent = '—'
        if (missesEl) missesEl.textContent = ''
        if (worstNameEl) worstNameEl.textContent = '—'
        if (worstMissesEl) worstMissesEl.textContent = ''
        showCoverageError(result.message)
        return
      }

      revealCoverageContent()
      const data = result.summary

      coverage7dHitRate.textContent = formatPct(data.hitRatePct)
      if (missesEl) missesEl.textContent = formatRequestsMissed(wpff.i18n.coverageMisses7d, data.requests, data.misses)

      if (data.worstCountry) {
        if (worstNameEl) worstNameEl.textContent = data.worstCountry.code
        if (worstMissesEl) worstMissesEl.textContent = formatRequestsMissed(wpff.i18n.coverageMissesPlain, data.worstCountry.requests, data.worstCountry.misses)
      } else if (worstNameEl) {
        worstNameEl.textContent = '—'
      }

      if (tableBody) {
        tableBody.innerHTML = ''
        data.top10.forEach(function (row) {
          const tr = document.createElement('tr')
          const tdCode = document.createElement('td')
          const tdRequests = document.createElement('td')
          const tdMisses = document.createElement('td')
          const tdRate = document.createElement('td')
          tdCode.textContent = row.code
          tdRequests.textContent = row.requests
          tdMisses.textContent = row.misses
          // Always show one decimal place (74 -> "74.0%") so the column
          // reads aligned instead of mixing whole numbers with decimals.
          tdRate.textContent = row.hitRatePct.toFixed(1) + '%'
          tr.appendChild(tdCode)
          tr.appendChild(tdRequests)
          tr.appendChild(tdMisses)
          tr.appendChild(tdRate)
          tableBody.appendChild(tr)
        })
        if (data.top10.length === 0) {
          const tr = document.createElement('tr')
          const td = document.createElement('td')
          td.colSpan = 4
          td.textContent = wpff.i18n.coverageAllGood
          tr.appendChild(td)
          tableBody.appendChild(tr)
        }
      }

      if (suggestedEl) {
        suggestedEl.textContent = data.suggested.length > 0 ? data.suggested.join(', ') : wpff.i18n.coverageNoSuggestions
      }
    })
  }

  // ============================================================
  // Highlight the relevant next step when a user arrives here via the
  // Cache Coverage card's "Grant the Analytics Read permission…" link
  // (sidebar or Stats tab). Those links point at #wpff-sp-cf-connect-section
  // (the top of the Cloudflare toggle, not the step itself) — anchoring
  // there instead of directly at the target leaves it comfortably inside
  // the viewport instead of flush against the top edge. Only a not-yet-
  // connected user has anything to highlight here (the walkthrough step);
  // an already-connected user sees the same warning on the sidebar card
  // and Coverage tab instead, so there's nothing on this page to point at.
  // ============================================================
  function highlightConnectSectionTarget() {
    if (window.location.hash !== '#wpff-sp-cf-connect-section') return

    // The walkthrough step only renders inside the "Yes" (auto-deploy)
    // panel — a user still on "No" would land here via this link and find
    // nothing highlighted, since that whole panel is display:none. Switch
    // to "Yes" first (the inline toggle script in settings-form.php is
    // already attached by now, since it runs synchronously during HTML
    // parsing, before this DOMContentLoaded handler) so the step becomes
    // visible if it's going to be at all.
    const yesBtn = document.getElementById('wpff-sp-worker-mode-yes')
    if (yesBtn && !yesBtn.classList.contains('active')) {
      yesBtn.click()
    }

    const target = document.getElementById('wpff-sp-analytics-permission-step')
    // Still hidden (display:none) for an already-connected user — the step
    // only shows while deploying, not once a Worker already exists.
    if (!target || target.offsetParent === null) return

    // Remove + force a reflow before re-adding, so the flash animation
    // restarts even on a repeat click of the same link (the sidebar's
    // Cache Coverage card and Settings itself share this same page, so
    // clicking the link there is an in-page anchor jump, not a reload —
    // no DOMContentLoaded to re-run this on, hence the hashchange listener
    // below).
    target.classList.remove('wpff-sp-highlight-flash')
    void target.offsetWidth
    target.classList.add('wpff-sp-highlight-flash')
  }

  highlightConnectSectionTarget()
  window.addEventListener('hashchange', highlightConnectSectionTarget)

  // ============================================================
  // Exclusions tab — a fresh visit renders a skeleton in place of the
  // table (see urls-list.php: $wpff_sp_defer_urls_table) instead of
  // blocking the whole page load on a live sitemap re-fetch. Fill it in
  // via AJAX once the page itself has already loaded. Pagination/search/
  // sort/bulk-action requests never render the skeleton in the first
  // place (already fast, cache reused server-side), so this only ever
  // runs on that one deferred path.
  // ============================================================
  const urlsTableSection = document.getElementById('wpff-sp-urls-table-section')
  if (urlsTableSection && urlsTableSection.dataset.wpffSpLoading === '1') {
    fetch(ajaxurl, {
      method: 'POST',
      credentials: 'same-origin',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
      body: new URLSearchParams({
        action: 'wpff_sp_get_urls_table',
        nonce: wpff.urlsTableNonce
      })
    })
      .then(res => res.text())
      .then(html => {
        urlsTableSection.innerHTML = html
      })
      .catch(() => {
        urlsTableSection.innerHTML = '<div class="notice notice-error"><p>' + wpff.i18n.ajaxFailed + '</p></div>'
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
  const zoneRow = document.getElementById('wpff-sp-auto-deploy-zone-row')
  const buttonRow = document.getElementById('wpff-sp-auto-deploy-button-row')
  const connectedRow = document.getElementById('wpff-sp-connected-row')
  const connectionAccountRow = document.getElementById('wpff-sp-connection-account-row')
  const connectionAccountName = document.getElementById('wpff-sp-connection-account-name')
  const connectionWorkerUrl = document.getElementById('wpff-sp-connection-worker-url')
  const connectionZoneRow = document.getElementById('wpff-sp-connection-zone-row')
  const connectionZoneName = document.getElementById('wpff-sp-connection-zone-name')
  const disconnectButton = document.getElementById('wpff-sp-disconnect-button')
  const disconnectSpinner = document.getElementById('wpff-sp-disconnect-spinner')
  const disconnectResult = document.getElementById('wpff-sp-disconnect-result')

  function showConnected(workerUrl, accountName, zoneName) {
    if (tokenRow) tokenRow.style.display = 'none'
    if (accountRow) accountRow.style.display = 'none'
    if (zoneRow) zoneRow.style.display = 'none'
    if (buttonRow) buttonRow.style.display = 'none'
    if (connectedRow) connectedRow.style.display = ''
    if (connectionWorkerUrl) connectionWorkerUrl.textContent = workerUrl
    if (connectionAccountRow) connectionAccountRow.style.display = accountName ? '' : 'none'
    if (connectionAccountName) connectionAccountName.textContent = accountName || ''
    if (connectionZoneRow) connectionZoneRow.style.display = zoneName ? '' : 'none'
    if (connectionZoneName) connectionZoneName.textContent = zoneName || ''
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

  // ============================================================
  // Zone ID picker — populated reactively by the Connect & Deploy handler
  // below when a token can see more than one zone (mirrors the account
  // picker's "multiple_accounts" handling), rather than via its own
  // separate lookup step.
  // ============================================================
  const zoneIdField = document.getElementById('wpff_sp_cf_zone_id')
  const zoneIdPicker = document.getElementById('wpff-sp-zone-id-picker')

  function populateZonePicker(zones) {
    if (!zoneIdPicker || !zoneIdField || !Array.isArray(zones)) return

    zoneIdPicker.querySelectorAll('option[data-zone]').forEach(opt => opt.remove())

    zones.forEach(zone => {
      const option = document.createElement('option')
      option.value = zone.id
      option.textContent = zone.name
      option.setAttribute('data-zone', '1')
      zoneIdPicker.appendChild(option)
    })

    // Pre-select whichever zone is already saved (or was just typed in) so
    // re-opening the picker doesn't look like the saved value was lost —
    // only if it's actually among the returned zones, otherwise leave the
    // "Select a zone…" placeholder rather than silently selecting nothing.
    const currentValue = zoneIdField.value.trim()
    const hasMatch = Array.prototype.some.call(zoneIdPicker.options, option => option.value === currentValue)
    zoneIdPicker.value = hasMatch ? currentValue : ''

    zoneIdPicker.style.display = ''
    zoneIdField.style.display = 'none'
    zoneIdPicker.focus()
  }

  if (zoneIdPicker && zoneIdField) {
    zoneIdPicker.addEventListener('change', function () {
      zoneIdField.value = zoneIdPicker.value
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
          cf_account_id: accountField ? accountField.value.trim() : '',
          cf_zone_id: zoneIdField ? zoneIdField.value.trim() : ''
        })
      })
        .then(res => res.json())
        .then(data => {
          if (data.success) {
            showConnected(data.data.workerUrl, data.data.accountName, data.data.zoneName)
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
            } else if (payload.code === 'select_zone') {
              // Optional, not a failure — the revealed dropdown and the
              // Zone ID field's own description are signal enough, so no
              // result message is shown here at all.
              if (zoneRow) zoneRow.style.display = 'table-row'
              populateZonePicker(payload.zones)
              setResultMessage(deployResult, '', false)
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
            // The Worker's removal from Cloudflare is best-effort (e.g. a
            // revoked API Token can't authenticate a delete call) — local
            // disconnect still goes ahead either way, but let the user know
            // if the script itself was left behind on their account.
            const payload = data.data || {}
            if (payload.workerDeleteWarning) {
              window.alert(wpff.i18n.disconnectWorkerDeleteWarning + '\n\n' + payload.workerDeleteWarning)
            }

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
