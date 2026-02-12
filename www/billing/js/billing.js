// ============================================================
// billing.js — Global utilities for Billing Control Panel
// ES5 compatible (no build step)
// ============================================================

// ------------------------------------------------------------
// AJAX API FRAMEWORK
// ------------------------------------------------------------
var API_BASE = 'api.php';

function apiGet(action, params, callback) {
    var url = API_BASE + '?action=' + encodeURIComponent(action);
    if (params) {
        for (var key in params) {
            if (params.hasOwnProperty(key) && params[key] !== null && params[key] !== undefined) {
                url += '&' + encodeURIComponent(key) + '=' + encodeURIComponent(params[key]);
            }
        }
    }
    var xhr = new XMLHttpRequest();
    xhr.open('GET', url);
    xhr.onload = function() {
        if (xhr.status >= 200 && xhr.status < 300) {
            try { callback(null, JSON.parse(xhr.responseText)); }
            catch (e) { callback('Invalid JSON response'); }
        } else {
            try {
                var err = JSON.parse(xhr.responseText);
                callback(err.error || 'HTTP ' + xhr.status);
            } catch (e) { callback('HTTP ' + xhr.status); }
        }
    };
    xhr.onerror = function() { callback('Network error'); };
    xhr.send();
}

function apiPost(action, body, callback) {
    var xhr = new XMLHttpRequest();
    xhr.open('POST', API_BASE + '?action=' + encodeURIComponent(action));
    xhr.onload = function() {
        if (xhr.status >= 200 && xhr.status < 300) {
            try { callback(null, JSON.parse(xhr.responseText)); }
            catch (e) { callback('Invalid JSON response'); }
        } else {
            try {
                var err = JSON.parse(xhr.responseText);
                callback(err.error || 'HTTP ' + xhr.status);
            } catch (e) { callback('HTTP ' + xhr.status); }
        }
    };
    xhr.onerror = function() { callback('Network error'); };
    if (body instanceof FormData) {
        xhr.send(body);
    } else {
        xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
        xhr.send(body);
    }
}

function showLoading(containerId) {
    var el = document.getElementById(containerId);
    if (el) el.innerHTML = '<div class="loading-skeleton">' +
        '<div class="skeleton-bar w75"></div>' +
        '<div class="skeleton-bar w90"></div>' +
        '<div class="skeleton-bar w50"></div>' +
        '<p>Loading...</p></div>';
}

function showAjaxError(containerId, message) {
    var el = document.getElementById(containerId);
    if (el) el.innerHTML = '<div class="ajax-error"><div class="flash flash-error">' +
        escapeHtml(message) + '</div></div>';
    showToast(message, 'error');
}

function buildPagination(pag, baseUrl, params) {
    if (!pag || pag.total_pages <= 1) return '';
    var buildUrl = function(page) {
        var p = [];
        for (var k in params) {
            if (params.hasOwnProperty(k) && k !== 'page' && params[k]) {
                p.push(encodeURIComponent(k) + '=' + encodeURIComponent(params[k]));
            }
        }
        p.push('page=' + page);
        var sep = baseUrl.indexOf('?') !== -1 ? '&' : '?';
        return baseUrl + sep + p.join('&');
    };
    var cur = pag.current, total = pag.total_pages;
    var html = '<div class="pagination">';
    if (pag.has_prev) { html += '<a href="' + escapeHtml(buildUrl(cur - 1)) + '">&laquo; Prev</a>'; }
    else { html += '<span class="disabled">&laquo; Prev</span>'; }
    var range = 2, start = Math.max(1, cur - range), end = Math.min(total, cur + range);
    if (start > 1) {
        html += '<a href="' + escapeHtml(buildUrl(1)) + '">1</a>';
        if (start > 2) html += '<span class="ellipsis">...</span>';
    }
    for (var i = start; i <= end; i++) {
        if (i === cur) { html += '<span class="active">' + i + '</span>'; }
        else { html += '<a href="' + escapeHtml(buildUrl(i)) + '">' + i + '</a>'; }
    }
    if (end < total) {
        if (end < total - 1) html += '<span class="ellipsis">...</span>';
        html += '<a href="' + escapeHtml(buildUrl(total)) + '">' + total + '</a>';
    }
    if (pag.has_next) { html += '<a href="' + escapeHtml(buildUrl(cur + 1)) + '">Next &raquo;</a>'; }
    else { html += '<span class="disabled">Next &raquo;</span>'; }
    html += '</div>';
    html += '<div class="pagination-info">Showing ' + pag.from + '-' + pag.to + ' of ' + pag.total + '</div>';
    return html;
}

// ------------------------------------------------------------
// BACKGROUND JOB SYSTEM
// ------------------------------------------------------------
var JOB_POLL_INTERVAL_MS = 1000;

function startJob(type, params, title) {
    var modal = document.getElementById('job-modal');
    modal.style.display = '';
    document.getElementById('job-modal-title').textContent = title || 'Processing...';
    document.getElementById('job-modal-fill').style.width = '0%';
    document.getElementById('job-modal-fill').textContent = '0%';
    document.getElementById('job-modal-fill').className = 'fill green';
    document.getElementById('job-modal-step').textContent = 'Starting...';
    document.getElementById('job-modal-log').innerHTML = '';
    document.getElementById('job-modal-result').style.display = 'none';

    var xhr = new XMLHttpRequest();
    xhr.open('POST', API_BASE + '?action=job_start');
    xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
    xhr.onload = function() {
        try {
            var resp = JSON.parse(xhr.responseText);
            if (resp.job_id) {
                pollJob(resp.job_id);
            } else {
                showJobError(resp.error || 'Failed to start job');
            }
        } catch (e) {
            showJobError('Invalid response from server');
        }
    };
    xhr.onerror = function() {
        showJobError('Network error starting job');
    };
    xhr.send('job_type=' + encodeURIComponent(type) +
             '&params=' + encodeURIComponent(JSON.stringify(params)));
}

function pollJob(jobId) {
    var pollInterval = setInterval(function() {
        var xhr = new XMLHttpRequest();
        xhr.open('GET', API_BASE + '?action=job_status&id=' + encodeURIComponent(jobId));
        xhr.onload = function() {
            try {
                var job = JSON.parse(xhr.responseText);
            } catch (e) {
                return;
            }
            if (!job || job.error) {
                clearInterval(pollInterval);
                showJobError(job ? job.error : 'Job not found');
                return;
            }

            var pct = job.total_steps > 0
                ? Math.round((job.progress / job.total_steps) * 100) : 0;
            var fillEl = document.getElementById('job-modal-fill');
            fillEl.style.width = pct + '%';
            fillEl.textContent = pct + '%';

            var stepText = job.current_step || 'Working...';
            if (job.total_steps > 0) {
                stepText = 'Step ' + job.progress + ' of ' + job.total_steps + ': ' + stepText;
            }
            document.getElementById('job-modal-step').textContent = stepText;

            var logEl = document.getElementById('job-modal-log');
            if (job.log && job.log.length > 0) {
                var html = '';
                for (var i = 0; i < job.log.length; i++) {
                    html += '<div>' + escapeHtml(job.log[i]) + '</div>';
                }
                logEl.innerHTML = html;
                logEl.scrollTop = logEl.scrollHeight;
            }

            if (job.status === 'complete') {
                clearInterval(pollInterval);
                fillEl.className = 'fill green';
                fillEl.style.width = '100%';
                fillEl.textContent = '100%';
                document.getElementById('job-modal-step').textContent = 'Complete!';
                var resultEl = document.getElementById('job-modal-result');
                resultEl.style.display = '';
                resultEl.innerHTML = '<div class="flash flash-success">' + escapeHtml(job.result) +
                    '</div><p style="margin-top:15px; text-align:center;"><button onclick="location.reload();" class="btn btn-success">Done</button></p>';
            }
            if (job.status === 'failed') {
                clearInterval(pollInterval);
                fillEl.className = 'fill red';
                var resultEl = document.getElementById('job-modal-result');
                resultEl.style.display = '';
                resultEl.innerHTML = '<div class="flash flash-error">Error: ' + escapeHtml(job.error) +
                    '</div><p style="margin-top:15px; text-align:center;"><button onclick="location.reload();" class="btn">Close</button></p>';
            }
        };
        xhr.send();
    }, JOB_POLL_INTERVAL_MS);
}

function showJobError(msg) {
    document.getElementById('job-modal-fill').className = 'fill red';
    document.getElementById('job-modal-step').textContent = 'Error';
    var resultEl = document.getElementById('job-modal-result');
    resultEl.style.display = '';
    resultEl.innerHTML = '<div class="flash flash-error">' + escapeHtml(msg) +
        '</div><p style="margin-top:15px; text-align:center;"><button onclick="document.getElementById(\'job-modal\').style.display=\'none\';" class="btn">Close</button></p>';
}

// ------------------------------------------------------------
// UTILITY FUNCTIONS
// ------------------------------------------------------------
function escapeHtml(str) {
    if (!str) return '';
    var div = document.createElement('div');
    div.appendChild(document.createTextNode(str));
    return div.innerHTML;
}

function numberFormat(n, decimals) {
    if (n === null || n === undefined) return '';
    return parseFloat(n).toLocaleString('en-US', decimals !== undefined ? {minimumFractionDigits: decimals, maximumFractionDigits: decimals} : {});
}

function formatFilesize(bytes) {
    var units = ['B', 'KB', 'MB', 'GB'];
    var i = 0;
    while (bytes >= 1024 && i < units.length - 1) { bytes /= 1024; i++; }
    return (Math.round(bytes * 100) / 100) + ' ' + units[i];
}

// ------------------------------------------------------------
// TOAST NOTIFICATION SYSTEM
// ------------------------------------------------------------
var TOAST_AUTO_DISMISS_MS = 4000;
var TOAST_FADE_DURATION_MS = 300;

/**
 * Show a floating toast notification.
 * @param {string} message - Text to display (will be HTML-escaped)
 * @param {string} type    - 'success', 'error', or 'info'
 * @param {object} options - Optional: { autoDismiss: bool, duration: number }
 */
function showToast(message, type, options) {
    type = type || 'info';
    options = options || {};

    var container = document.getElementById('toast-container');
    if (!container) {
        container = document.createElement('div');
        container.id = 'toast-container';
        container.className = 'toast-container';
        document.body.appendChild(container);
    }

    var toast = document.createElement('div');
    toast.className = 'toast toast-' + type;
    toast.innerHTML = '<span>' + escapeHtml(message) + '</span>' +
        '<button class="toast-close" type="button">&times;</button>';

    toast.querySelector('.toast-close').onclick = function() {
        dismissToast(toast);
    };

    container.appendChild(toast);

    // Force reflow then add visible class for CSS transition
    toast.offsetHeight;
    toast.className += ' toast-visible';

    // Auto-dismiss: success and info auto-close; errors stay
    var autoDismiss = options.autoDismiss !== undefined
        ? options.autoDismiss
        : (type !== 'error');
    var duration = options.duration || TOAST_AUTO_DISMISS_MS;

    if (autoDismiss) {
        setTimeout(function() {
            dismissToast(toast);
        }, duration);
    }
}

function dismissToast(toast) {
    if (toast._dismissed) return;
    toast._dismissed = true;
    toast.className = toast.className.replace('toast-visible', '') + ' toast-fade-out';
    setTimeout(function() {
        if (toast.parentNode) {
            toast.parentNode.removeChild(toast);
        }
    }, TOAST_FADE_DURATION_MS);
}
