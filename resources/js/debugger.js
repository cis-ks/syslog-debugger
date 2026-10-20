import Swal from 'sweetalert2';

/**
 * Syslog Debugger Client-Side Application
 * In-Memory Message Store & Reactive Filter System via Laravel Echo / Reverb
 */

class SyslogDebugger {
    constructor() {
        /** @type {Array<Object>} In-memory store of all received messages */
        this.messages = [];
        this.maxBuffer = 2000;
        this.isPaused = false;
        this.pausedBuffer = 0;
        this.activeDetailMessage = null;

        // Filter state
        this.filters = {
            search: '',
            severity: 'all',
            facility: 'all',
            protocol: 'all',
        };

        // DOM Elements
        this.initElements();
        this.bindEvents();
        this.initEcho();
        this.render();
    }

    initElements() {
        this.elements = {
            connectionStatus: document.getElementById('connection-status'),
            connectionText: document.getElementById('connection-text'),
            connectionDot: document.getElementById('connection-dot'),
            tableBody: document.getElementById('syslog-table-body'),
            emptyState: document.getElementById('empty-state'),
            totalCount: document.getElementById('stat-total-count'),
            filteredCount: document.getElementById('stat-filtered-count'),
            errorCount: document.getElementById('stat-error-count'),
            udpCount: document.getElementById('stat-udp-count'),
            tlsCount: document.getElementById('stat-tls-count'),
            searchInput: document.getElementById('filter-search'),
            severitySelect: document.getElementById('filter-severity'),
            facilitySelect: document.getElementById('filter-facility'),
            protocolSelect: document.getElementById('filter-protocol'),
            resetFiltersBtn: document.getElementById('btn-reset-filters'),
            pauseBtn: document.getElementById('btn-pause-stream'),
            pauseBtnText: document.getElementById('btn-pause-text'),
            clearBufferBtn: document.getElementById('btn-clear-buffer'),
            exportJsonBtn: document.getElementById('btn-export-json'),
            simulateBtn: document.getElementById('btn-simulate-msg'),
            detailDrawer: document.getElementById('detail-drawer'),
            drawerCloseBtn: document.getElementById('drawer-close-btn'),
            drawerFooterCloseBtn: document.getElementById('drawer-footer-close-btn'),
            drawerActiveBadge: document.getElementById('drawer-active-badge'),
            drawerMetaSevFac: document.getElementById('drawer-meta-sev-fac'),
            drawerMetaProtoTime: document.getElementById('drawer-meta-proto-time'),
            drawerMetaHost: document.getElementById('drawer-meta-host'),
            drawerMetaApp: document.getElementById('drawer-meta-app'),
            drawerMetaMessage: document.getElementById('drawer-meta-message'),
            drawerRawContent: document.getElementById('drawer-raw-content'),
            drawerJsonContent: document.getElementById('drawer-json-content'),
            drawerKvSection: document.getElementById('drawer-kv-section'),
            drawerKvCount: document.getElementById('drawer-kv-count'),
            drawerKvContainer: document.getElementById('drawer-kv-container'),
            drawerCopyRawBtn: document.getElementById('btn-copy-raw'),
            drawerCopyJsonBtn: document.getElementById('btn-copy-json'),
        };
    }

    bindEvents() {
        if (this.elements.searchInput) {
            this.elements.searchInput.addEventListener('input', (e) => {
                this.filters.search = e.target.value.trim().toLowerCase();
                this.render();
            });
        }

        if (this.elements.severitySelect) {
            this.elements.severitySelect.addEventListener('change', (e) => {
                this.filters.severity = e.target.value;
                this.render();
            });
        }

        if (this.elements.facilitySelect) {
            this.elements.facilitySelect.addEventListener('change', (e) => {
                this.filters.facility = e.target.value;
                this.render();
            });
        }

        if (this.elements.protocolSelect) {
            this.elements.protocolSelect.addEventListener('change', (e) => {
                this.filters.protocol = e.target.value;
                this.render();
            });
        }

        if (this.elements.resetFiltersBtn) {
            this.elements.resetFiltersBtn.addEventListener('click', () => {
                this.resetFilters();
            });
        }

        if (this.elements.pauseBtn) {
            this.elements.pauseBtn.addEventListener('click', () => {
                this.togglePause();
            });
        }

        if (this.elements.clearBufferBtn) {
            this.elements.clearBufferBtn.addEventListener('click', () => {
                this.clearBuffer();
            });
        }

        if (this.elements.exportJsonBtn) {
            this.elements.exportJsonBtn.addEventListener('click', () => {
                this.exportJson();
            });
        }

        if (this.elements.simulateBtn) {
            this.elements.simulateBtn.addEventListener('click', () => {
                this.simulateMessage();
            });
        }

        if (this.elements.drawerCloseBtn) {
            this.elements.drawerCloseBtn.addEventListener('click', () => {
                this.closeDetailDrawer();
            });
        }

        if (this.elements.drawerFooterCloseBtn) {
            this.elements.drawerFooterCloseBtn.addEventListener('click', () => {
                this.closeDetailDrawer();
            });
        }

        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape' && this.isDrawerOpen()) {
                this.closeDetailDrawer();
            }
        });

        if (this.elements.drawerCopyRawBtn) {
            this.elements.drawerCopyRawBtn.addEventListener('click', () => {
                if (this.activeDetailMessage && this.activeDetailMessage.raw) {
                    navigator.clipboard.writeText(this.activeDetailMessage.raw);
                    this.showToast('Rohdaten in die Zwischenablage kopiert');
                }
            });
        }

        if (this.elements.drawerCopyJsonBtn) {
            this.elements.drawerCopyJsonBtn.addEventListener('click', () => {
                if (this.activeDetailMessage) {
                    navigator.clipboard.writeText(JSON.stringify(this.activeDetailMessage, null, 2));
                    this.showToast('JSON in die Zwischenablage kopiert');
                }
            });
        }
    }

    initEcho() {
        if (!window.Echo) {
            this.updateConnectionStatus('error', 'Echo nicht initialisiert');
            return;
        }

        this.updateConnectionStatus('connecting', 'Verbinde mit Reverb...');

        // Listen for Echo connection state
        if (window.Echo.connector && window.Echo.connector.pusher) {
            const pusher = window.Echo.connector.pusher;
            pusher.connection.bind('connected', () => {
                this.updateConnectionStatus('connected', 'Verbunden (Live WebSocket)');
            });
            pusher.connection.bind('disconnected', () => {
                this.updateConnectionStatus('disconnected', 'Verbindung getrennt');
            });
            pusher.connection.bind('connecting', () => {
                this.updateConnectionStatus('connecting', 'Verbinde mit Reverb...');
            });
            pusher.connection.bind('failed', () => {
                this.updateConnectionStatus('error', 'Verbindung fehlgeschlagen');
            });
            pusher.connection.bind('unavailable', () => {
                this.updateConnectionStatus('error', 'Server nicht erreichbar');
            });
        }

        // Subscribe to syslog channel
        window.Echo.channel('syslog')
            .listen('.SyslogMessageReceived', (message) => {
                this.handleIncoming(message);
            })
            .listen('SyslogMessageReceived', (message) => {
                this.handleIncoming(message);
            });
    }

    updateConnectionStatus(state, label) {
        if (!this.elements.connectionStatus) return;

        if (this.elements.connectionText) {
            this.elements.connectionText.textContent = label;
        }

        if (this.elements.connectionDot) {
            this.elements.connectionDot.className = 'w-3 h-3 rounded-full mr-2 ';
            if (state === 'connected') {
                this.elements.connectionDot.classList.add('bg-emerald-500', 'animate-pulse');
            } else if (state === 'connecting') {
                this.elements.connectionDot.classList.add('bg-amber-400', 'animate-ping');
            } else {
                this.elements.connectionDot.classList.add('bg-rose-500');
            }
        }
    }

    handleIncoming(message) {
        // Unshift onto in-memory buffer (newest first)
        this.messages.unshift(message);

        // Cap buffer limit
        if (this.messages.length > this.maxBuffer) {
            this.messages.pop();
        }

        if (this.isPaused) {
            this.pausedBuffer++;
            this.updatePauseButton();
            this.updateStats();
        } else {
            this.render();
        }
    }

    togglePause() {
        this.isPaused = !this.isPaused;
        if (!this.isPaused) {
            this.pausedBuffer = 0;
            this.render();
        }
        this.updatePauseButton();
    }

    updatePauseButton() {
        if (!this.elements.pauseBtn || !this.elements.pauseBtnText) return;

        if (this.isPaused) {
            this.elements.pauseBtn.classList.remove('bg-gray-100', 'dark:bg-gray-800', 'text-gray-700', 'dark:text-gray-300');
            this.elements.pauseBtn.classList.add('bg-amber-100', 'dark:bg-amber-950', 'text-amber-700', 'dark:text-amber-300', 'border-amber-400');
            this.elements.pauseBtnText.textContent = this.pausedBuffer > 0
                ? `Pausiert (${this.pausedBuffer} gepuffert)`
                : 'Stream pausiert';
        } else {
            this.elements.pauseBtn.classList.remove('bg-amber-100', 'dark:bg-amber-950', 'text-amber-700', 'dark:text-amber-300', 'border-amber-400');
            this.elements.pauseBtn.classList.add('bg-gray-100', 'dark:bg-gray-800', 'text-gray-700', 'dark:text-gray-300');
            this.elements.pauseBtnText.textContent = 'Stream pausieren';
        }
    }

    resetFilters() {
        this.filters = {
            search: '',
            severity: 'all',
            facility: 'all',
            protocol: 'all',
        };

        if (this.elements.searchInput) this.elements.searchInput.value = '';
        if (this.elements.severitySelect) this.elements.severitySelect.value = 'all';
        if (this.elements.facilitySelect) this.elements.facilitySelect.value = 'all';
        if (this.elements.protocolSelect) this.elements.protocolSelect.value = 'all';

        this.render();
        this.showToast('Filter zurückgesetzt');
    }

    clearBuffer() {
        if (this.messages.length === 0) {
            this.showToast('Puffer ist bereits leer');
            return;
        }

        Swal.fire({
            title: 'Puffer leeren?',
            text: 'Möchten Sie alle empfangenen Syslog-Nachrichten aus dem temporären Speicher löschen?',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Ja, leeren',
            cancelButtonText: 'Abbrechen',
            confirmButtonColor: '#dc2626',
            cancelButtonColor: '#4b5563',
            background: '#1f2937',
            color: '#f9fafb',
            customClass: {
                popup: 'border border-gray-700 shadow-2xl rounded-2xl',
                title: 'text-lg font-bold text-white',
                htmlContainer: 'text-sm text-gray-300',
                confirmButton: 'px-4 py-2 text-sm font-semibold text-white rounded-lg shadow transition-colors cursor-pointer',
                cancelButton: 'px-4 py-2 text-sm font-medium text-gray-200 bg-gray-700 hover:bg-gray-600 rounded-lg shadow transition-colors cursor-pointer',
            },
        }).then((result) => {
            if (result.isConfirmed) {
                this.messages = [];
                this.pausedBuffer = 0;
                this.render();
                this.showToast('Puffer erfolgreich geleert');
            }
        });
    }

    getFilteredMessages() {
        return this.messages.filter((msg) => {
            // Severity filter
            if (this.filters.severity !== 'all') {
                if (String(msg.severity) !== String(this.filters.severity)) {
                    return false;
                }
            }

            // Facility filter
            if (this.filters.facility !== 'all') {
                if (
                    String(msg.facility) !== String(this.filters.facility) &&
                    String(msg.facility_name).toLowerCase() !== String(this.filters.facility).toLowerCase()
                ) {
                    return false;
                }
            }

            // Protocol filter
            if (this.filters.protocol !== 'all') {
                if (String(msg.protocol).toLowerCase() !== String(this.filters.protocol).toLowerCase()) {
                    return false;
                }
            }

            // Search query filter (search across message, hostname, app_name, raw, process_id, message_id)
            if (this.filters.search) {
                const q = this.filters.search;
                const matchMsg = msg.message && msg.message.toLowerCase().includes(q);
                const matchHost = msg.hostname && msg.hostname.toLowerCase().includes(q);
                const matchApp = msg.app_name && msg.app_name.toLowerCase().includes(q);
                const matchRaw = msg.raw && msg.raw.toLowerCase().includes(q);
                const matchProc = msg.process_id && String(msg.process_id).toLowerCase().includes(q);
                const matchMsgId = msg.message_id && String(msg.message_id).toLowerCase().includes(q);
                const matchIp = msg.source_ip && msg.source_ip.toLowerCase().includes(q);

                if (!matchMsg && !matchHost && !matchApp && !matchRaw && !matchProc && !matchMsgId && !matchIp) {
                    return false;
                }
            }

            return true;
        });
    }

    render() {
        this.updateStats();

        const filtered = this.getFilteredMessages();
        if (!this.elements.tableBody) return;

        if (filtered.length === 0) {
            this.elements.tableBody.innerHTML = '';
            if (this.elements.emptyState) {
                this.elements.emptyState.classList.remove('hidden');
            }
            return;
        }

        if (this.elements.emptyState) {
            this.elements.emptyState.classList.add('hidden');
        }

        const fragment = document.createDocumentFragment();

        filtered.forEach((msg, index) => {
            const tr = document.createElement('tr');
            tr.setAttribute('data-msg-idx', index);
            tr.className = 'border-b border-gray-800 hover:bg-gray-800/80 transition-colors text-xs cursor-pointer font-mono';

            // Check if active selected message
            const isSelected = this.activeDetailMessage && this.isSameMessage(this.activeDetailMessage, msg);
            if (isSelected) {
                tr.classList.add('ring-2', 'ring-blue-500', 'bg-blue-950/60', 'border-l-4', 'border-l-blue-500');
            } else if (msg.severity <= 3) {
                // Highlight row on severity
                tr.classList.add('bg-rose-950/30', 'hover:bg-rose-900/40');
            } else if (msg.severity === 4) {
                tr.classList.add('bg-amber-950/30', 'hover:bg-amber-900/40');
            }

            const sevBadge = this.getSeverityBadge(msg.severity, msg.severity_name);
            const protoBadge = this.getProtocolBadge(msg.protocol);
            const timeFormatted = this.formatTime(msg.timestamp || msg.received_at);

            tr.innerHTML = `
                <td class="py-2.5 px-3 whitespace-nowrap text-gray-300 font-mono">${timeFormatted}</td>
                <td class="py-2.5 px-3 whitespace-nowrap">${sevBadge}</td>
                <td class="py-2.5 px-3 whitespace-nowrap text-gray-200 font-semibold">${this.escapeHtml(msg.facility_name || 'fac:' + msg.facility)}</td>
                <td class="py-2.5 px-3 whitespace-nowrap">${protoBadge}</td>
                <td class="py-2.5 px-3 whitespace-nowrap text-blue-400 font-medium">${this.escapeHtml(msg.hostname || msg.source_ip || '-')}</td>
                <td class="py-2.5 px-3 whitespace-nowrap text-gray-200">${this.escapeHtml(msg.app_name ? `${msg.app_name}${msg.process_id ? '[' + msg.process_id + ']' : ''}` : '-')}</td>
                <td class="py-2.5 px-3 text-gray-100 break-words font-sans text-sm">${this.highlightMatch(this.escapeHtml(msg.message || ''), this.filters.search)}</td>
                <td class="py-2.5 px-3 text-right whitespace-nowrap">
                    <button type="button" class="view-detail-btn text-gray-400 hover:text-blue-400 p-1 transition-colors" title="Details ansehen">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                    </button>
                </td>
            `;

            tr.addEventListener('click', () => {
                this.openDetailDrawer(msg);
            });

            fragment.appendChild(tr);
        });

        this.elements.tableBody.innerHTML = '';
        this.elements.tableBody.appendChild(fragment);
    }

    updateStats() {
        const total = this.messages.length;
        const filtered = this.getFilteredMessages().length;
        const errors = this.messages.filter(m => m.severity <= 3).length;
        const udp = this.messages.filter(m => String(m.protocol).toLowerCase() === 'udp').length;
        const tls = this.messages.filter(m => String(m.protocol).toLowerCase() === 'tls').length;

        if (this.elements.totalCount) this.elements.totalCount.textContent = total;
        if (this.elements.filteredCount) this.elements.filteredCount.textContent = filtered;
        if (this.elements.errorCount) this.elements.errorCount.textContent = errors;
        if (this.elements.udpCount) this.elements.udpCount.textContent = udp;
        if (this.elements.tlsCount) this.elements.tlsCount.textContent = tls;
    }

    getSeverityBadge(severity, name) {
        const s = parseInt(severity, 10);
        let colorClass = 'bg-gray-800 text-gray-200 border-gray-600 font-semibold';

        if (s === 0 || s === 1) { // Emergency / Alert
            colorClass = 'bg-red-600 text-white border-red-500 font-bold animate-pulse shadow-sm';
        } else if (s === 2) { // Critical
            colorClass = 'bg-rose-600 text-white border-rose-500 font-bold shadow-sm';
        } else if (s === 3) { // Error
            colorClass = 'bg-orange-600 text-white border-orange-500 font-bold shadow-sm';
        } else if (s === 4) { // Warning
            colorClass = 'bg-amber-400 text-gray-950 border-amber-300 font-bold shadow-sm';
        } else if (s === 5) { // Notice
            colorClass = 'bg-emerald-900/80 text-emerald-200 border-emerald-500/70 font-semibold';
        } else if (s === 6) { // Info
            colorClass = 'bg-blue-900/80 text-blue-200 border-blue-500/70 font-semibold';
        } else if (s === 7) { // Debug
            colorClass = 'bg-gray-800 text-gray-300 border-gray-600 font-semibold';
        }

        return `<span class="inline-flex items-center px-2 py-0.5 rounded text-[11px] font-semibold border ${colorClass}">${this.escapeHtml(name || 'Lev:' + s)}</span>`;
    }

    getProtocolBadge(protocol) {
        const p = String(protocol).toLowerCase();
        if (p === 'tls') {
            return `<span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold bg-purple-900/80 text-purple-200 border border-purple-500/70">TLS:6514</span>`;
        }
        return `<span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold bg-cyan-900/80 text-cyan-200 border border-cyan-500/70">UDP:514</span>`;
    }

    formatTime(ts) {
        if (!ts) return '-';
        try {
            const date = new Date(ts);
            if (isNaN(date.getTime())) return ts;
            return date.toLocaleTimeString('de-DE', { hour12: false }) + '.' + String(date.getMilliseconds()).padStart(3, '0');
        } catch (e) {
            return ts;
        }
    }

    highlightMatch(text, query) {
        if (!query) return text;
        const regex = new RegExp(`(${this.escapeRegex(query)})`, 'gi');
        return text.replace(regex, '<mark class="bg-yellow-400 text-gray-950 font-bold px-1 rounded-sm">$1</mark>');
    }

    isDrawerOpen() {
        return this.elements.detailDrawer && !this.elements.detailDrawer.classList.contains('translate-x-full');
    }

    isSameMessage(a, b) {
        if (!a || !b) return false;
        if (a === b) return true;
        return (a.received_at === b.received_at && a.raw === b.raw && a.message === b.message) ||
               (a.timestamp === b.timestamp && a.raw === b.raw && a.process_id === b.process_id);
    }

    openDetailDrawer(msg) {
        this.activeDetailMessage = msg;
        if (!this.elements.detailDrawer) return;

        this.updateDrawerContent(msg);

        // Slide open drawer
        this.elements.detailDrawer.classList.remove('translate-x-full');

        // Update active row visual indicators
        this.updateActiveRowHighlight();
    }

    updateDrawerContent(msg) {
        if (!msg) return;

        if (this.elements.drawerActiveBadge) {
            this.elements.drawerActiveBadge.textContent = this.formatTime(msg.timestamp || msg.received_at);
        }

        if (this.elements.drawerMetaSevFac) {
            const sevBadge = this.getSeverityBadge(msg.severity, msg.severity_name);
            const facBadge = `<span class="inline-flex items-center px-2 py-0.5 rounded text-[11px] font-semibold bg-gray-800 text-gray-300 border border-gray-700">${this.escapeHtml(msg.facility_name || 'fac:' + msg.facility)}</span>`;
            this.elements.drawerMetaSevFac.innerHTML = `${sevBadge} ${facBadge}`;
        }

        if (this.elements.drawerMetaProtoTime) {
            const protoBadge = this.getProtocolBadge(msg.protocol);
            this.elements.drawerMetaProtoTime.innerHTML = `${protoBadge} <span class="font-mono text-[11px] text-gray-300 ml-1">${this.formatTime(msg.timestamp || msg.received_at)}</span>`;
        }

        if (this.elements.drawerMetaHost) {
            this.elements.drawerMetaHost.textContent = msg.hostname || msg.source_ip || '-';
        }

        if (this.elements.drawerMetaApp) {
            this.elements.drawerMetaApp.textContent = msg.app_name ? `${msg.app_name}${msg.process_id ? '[' + msg.process_id + ']' : ''}` : '-';
        }

        if (this.elements.drawerMetaMessage) {
            this.elements.drawerMetaMessage.textContent = msg.message || '-';
        }

        if (this.elements.drawerRawContent) {
            this.elements.drawerRawContent.textContent = msg.raw || '(Kein Raw-Payload vorhanden)';
        }

        if (this.elements.drawerJsonContent) {
            this.elements.drawerJsonContent.textContent = JSON.stringify(msg, null, 2);
        }

        // Render extracted key-value pairs if present
        if (this.elements.drawerKvSection && this.elements.drawerKvContainer) {
            const attributes = msg.attributes || msg.key_value_pairs || {};
            const keys = Object.keys(attributes);

            if (keys.length > 0) {
                this.elements.drawerKvSection.classList.remove('hidden');
                if (this.elements.drawerKvCount) {
                    this.elements.drawerKvCount.textContent = `${keys.length} ${keys.length === 1 ? 'Attribut' : 'Attribute'}`;
                }

                this.elements.drawerKvContainer.innerHTML = keys.map(key => {
                    const val = attributes[key];
                    const valDisplay = typeof val === 'object' && val !== null ? JSON.stringify(val) : String(val);
                    let valClass = 'text-gray-200';
                    if (typeof val === 'number') {
                        valClass = 'text-amber-400';
                    } else if (typeof val === 'boolean') {
                        valClass = 'text-purple-400';
                    } else if (typeof val === 'string') {
                        valClass = 'text-emerald-300';
                    }

                    return `
                        <div class="flex items-start justify-between py-1 px-2 rounded bg-gray-900/80 border border-gray-800 hover:border-gray-700 transition-colors">
                            <span class="text-blue-400 font-semibold mr-2 shrink-0 select-all font-mono">${this.escapeHtml(key)}:</span>
                            <span class="${valClass} break-all text-right select-all font-mono">${this.escapeHtml(valDisplay)}</span>
                        </div>
                    `;
                }).join('');
            } else {
                this.elements.drawerKvSection.classList.add('hidden');
                this.elements.drawerKvContainer.innerHTML = '';
            }
        }
    }

    closeDetailDrawer() {
        this.activeDetailMessage = null;
        if (this.elements.detailDrawer) {
            this.elements.detailDrawer.classList.add('translate-x-full');
        }
        this.updateActiveRowHighlight();
    }

    updateActiveRowHighlight() {
        if (!this.elements.tableBody) return;
        const rows = this.elements.tableBody.querySelectorAll('tr[data-msg-idx]');
        const filtered = this.getFilteredMessages();
        rows.forEach(tr => {
            const idx = parseInt(tr.getAttribute('data-msg-idx'), 10);
            const msg = filtered[idx];
            if (this.activeDetailMessage && msg && this.isSameMessage(this.activeDetailMessage, msg)) {
                tr.classList.add('ring-2', 'ring-blue-500', 'bg-blue-950/60', 'border-l-4', 'border-l-blue-500');
            } else {
                tr.classList.remove('ring-2', 'ring-blue-500', 'bg-blue-950/60', 'border-l-4', 'border-l-blue-500');
            }
        });
    }

    exportJson() {
        const filtered = this.getFilteredMessages();
        const dataStr = 'data:text/json;charset=utf-8,' + encodeURIComponent(JSON.stringify(filtered, null, 2));
        const downloadAnchor = document.createElement('a');
        downloadAnchor.setAttribute('href', dataStr);
        downloadAnchor.setAttribute('download', `syslog-export-${new Date().toISOString().replace(/[:.]/g, '-')}.json`);
        document.body.appendChild(downloadAnchor);
        downloadAnchor.click();
        downloadAnchor.remove();
        this.showToast(`${filtered.length} Nachrichten als JSON exportiert`);
    }

    simulateMessage() {
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
        fetch('/debugger/simulate', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken || '',
                'Accept': 'application/json',
            },
            body: JSON.stringify({}),
        })
        .then(res => res.json())
        .then(() => {
            this.showToast('Test-Syslog-Nachricht generiert & via WebSocket gesendet');
        })
        .catch(err => {
            console.error('Simulation error', err);
            this.showToast('Fehler bei der Test-Simulation');
        });
    }

    showToast(message) {
        let toast = document.getElementById('app-toast');
        if (!toast) {
            toast = document.createElement('div');
            toast.id = 'app-toast';
            toast.className = 'fixed bottom-5 right-5 z-50 px-4 py-2 bg-gray-900 text-white text-xs rounded-lg shadow-xl transition-opacity duration-300 border border-gray-700 pointer-events-none opacity-0';
            document.body.appendChild(toast);
        }

        toast.textContent = message;
        toast.classList.remove('opacity-0');
        toast.classList.add('opacity-100');

        setTimeout(() => {
            toast.classList.remove('opacity-100');
            toast.classList.add('opacity-0');
        }, 2500);
    }

    escapeHtml(str) {
        if (!str) return '';
        return String(str)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    escapeRegex(str) {
        return str.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
    }
}

// Auto-initialize on DOM ready
document.addEventListener('DOMContentLoaded', () => {
    window.syslogDebugger = new SyslogDebugger();
});
