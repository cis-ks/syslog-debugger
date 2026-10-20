<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name', 'Syslog Debugger') }} - Live Dashboard</title>

    <!-- Reverb & Echo Config Meta Tags -->
    <meta name="reverb-key" content="{{ $reverb['key'] ?? '' }}">
    <meta name="reverb-host" content="{{ $reverb['host'] ?? 'localhost' }}">
    <meta name="reverb-port" content="{{ $reverb['port'] ?? 8080 }}">
    <meta name="reverb-scheme" content="{{ $reverb['scheme'] ?? 'http' }}">

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600,700" rel="stylesheet" />

    <!-- Scripts and Styles -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-gray-900 text-gray-100 min-h-screen flex flex-col font-sans antialiased">

    <!-- Top Navigation Bar -->
    <header class="bg-gray-800 border-b border-gray-700 sticky top-0 z-30 shadow-md">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex items-center justify-between h-16">
                
                <!-- Left: Branding & Status -->
                <div class="flex items-center space-x-4">
                    <div class="flex items-center space-x-3">
                        <div class="p-2 bg-blue-600 rounded-lg text-white shadow">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                            </svg>
                        </div>
                        <div>
                            <h1 class="text-lg font-bold text-white tracking-wide">Syslog Debugger</h1>
                            <p class="text-xs text-gray-400">Echtzeit UDP & TLS Log-Analyse</p>
                        </div>
                    </div>

                    <!-- Connection Status Indicator -->
                    <div id="connection-status" class="hidden sm:inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-gray-900 border border-gray-700">
                        <span id="connection-dot" class="w-2.5 h-2.5 rounded-full mr-2 bg-amber-400 animate-ping"></span>
                        <span id="connection-text" class="text-gray-300">Verbinde mit Reverb...</span>
                    </div>
                </div>

                <!-- Right: Navigation, Docs & User Info -->
                <div class="flex items-center space-x-3">
                    <a href="/docs" class="inline-flex items-center px-3 py-1.5 text-xs font-medium text-gray-300 bg-gray-700 hover:bg-gray-600 hover:text-white rounded-lg transition-colors border border-gray-600">
                        <svg class="w-4 h-4 mr-1.5 text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/>
                        </svg>
                        Dokumentation
                    </a>

                    @auth
                        <div class="flex items-center space-x-2 pl-2 border-l border-gray-700">
                            <span class="text-xs text-gray-300 font-medium hidden md:inline">{{ auth()->user()->name }}</span>
                            <a href="{{ route('saml.sls') }}" class="px-2.5 py-1.5 text-xs font-medium text-red-400 hover:text-red-300 bg-red-950/40 hover:bg-red-900/60 rounded-lg border border-red-800 transition-colors">
                                Abmelden
                            </a>
                        </div>
                    @else
                        <a href="{{ route('saml.login') }}" class="inline-flex items-center px-3 py-1.5 text-xs font-medium text-white bg-blue-600 hover:bg-blue-500 rounded-lg shadow transition-colors">
                            <svg class="w-3.5 h-3.5 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1"/>
                            </svg>
                            SAML Login
                        </a>
                    @endauth
                </div>

            </div>
        </div>
    </header>

    <!-- Main Content Area -->
    <main class="flex-1 max-w-7xl w-full mx-auto px-4 sm:px-6 lg:px-8 py-6 flex flex-col space-y-5">

        <!-- Stat Counter Badges -->
        <div class="grid grid-cols-2 sm:grid-cols-5 gap-3">
            <div class="bg-gray-800/80 border border-gray-700/80 rounded-xl p-3.5 flex flex-col justify-between shadow-sm">
                <span class="text-xs font-medium text-gray-400">Gesamtnachrichten</span>
                <div class="flex items-baseline justify-between mt-1">
                    <span id="stat-total-count" class="text-2xl font-bold text-white font-mono">0</span>
                    <span class="text-[10px] text-gray-500 font-mono">im RAM</span>
                </div>
            </div>

            <div class="bg-gray-800/80 border border-gray-700/80 rounded-xl p-3.5 flex flex-col justify-between shadow-sm">
                <span class="text-xs font-medium text-gray-400">Gefilterte Ansicht</span>
                <div class="flex items-baseline justify-between mt-1">
                    <span id="stat-filtered-count" class="text-2xl font-bold text-blue-400 font-mono">0</span>
                    <span class="text-[10px] text-blue-500 font-mono">sichtbar</span>
                </div>
            </div>

            <div class="bg-gray-800/80 border border-gray-700/80 rounded-xl p-3.5 flex flex-col justify-between shadow-sm">
                <span class="text-xs font-medium text-gray-400">Kritisch & Fehler</span>
                <div class="flex items-baseline justify-between mt-1">
                    <span id="stat-error-count" class="text-2xl font-bold text-rose-400 font-mono">0</span>
                    <span class="text-[10px] text-rose-500 font-mono">Lev 0-3</span>
                </div>
            </div>

            <div class="bg-gray-800/80 border border-gray-700/80 rounded-xl p-3.5 flex flex-col justify-between shadow-sm">
                <span class="text-xs font-medium text-gray-400">UDP :514</span>
                <div class="flex items-baseline justify-between mt-1">
                    <span id="stat-udp-count" class="text-2xl font-bold text-cyan-400 font-mono">0</span>
                    <span class="text-[10px] text-cyan-500 font-mono">Datagram</span>
                </div>
            </div>

            <div class="bg-gray-800/80 border border-gray-700/80 rounded-xl p-3.5 flex flex-col justify-between shadow-sm col-span-2 sm:col-span-1">
                <span class="text-xs font-medium text-gray-400">TLS :6514</span>
                <div class="flex items-baseline justify-between mt-1">
                    <span id="stat-tls-count" class="text-2xl font-bold text-purple-400 font-mono">0</span>
                    <span class="text-[10px] text-purple-500 font-mono">Stream TLS</span>
                </div>
            </div>
        </div>

        <!-- Filter & Control Toolbar -->
        <div class="bg-gray-800 border border-gray-700 rounded-xl p-4 shadow-sm">
            <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-3.5">
                
                <!-- Filter Controls (Left / Center) -->
                <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-3 flex-1">
                    
                    <!-- Search Input -->
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-gray-400">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                        </div>
                        <input type="text" id="filter-search" placeholder="Meldung, Host, App durchsuchen..."
                            class="w-full pl-9 pr-3 py-2 text-xs bg-gray-900 border border-gray-700 rounded-lg text-white placeholder-gray-500 focus:outline-none focus:ring-1 focus:ring-blue-500 focus:border-blue-500">
                    </div>

                    <!-- Severity Filter -->
                    <div>
                        <select id="filter-severity" class="w-full py-2 px-3 text-xs bg-gray-900 border border-gray-700 rounded-lg text-white focus:outline-none focus:ring-1 focus:ring-blue-500 focus:border-blue-500">
                            <option value="all">Alle Schweregrade (Severity)</option>
                            <option value="0">0 - Emergency (Notfall)</option>
                            <option value="1">1 - Alert (Alarm)</option>
                            <option value="2">2 - Critical (Kritisch)</option>
                            <option value="3">3 - Error (Fehler)</option>
                            <option value="4">4 - Warning (Warnung)</option>
                            <option value="5">5 - Notice (Hinweis)</option>
                            <option value="6">6 - Informational (Info)</option>
                            <option value="7">7 - Debug</option>
                        </select>
                    </div>

                    <!-- Facility Filter -->
                    <div>
                        <select id="filter-facility" class="w-full py-2 px-3 text-xs bg-gray-900 border border-gray-700 rounded-lg text-white focus:outline-none focus:ring-1 focus:ring-blue-500 focus:border-blue-500">
                            <option value="all">Alle Facilities</option>
                            <option value="kern">0 - kern (Kernel)</option>
                            <option value="user">1 - user (User-Level)</option>
                            <option value="mail">2 - mail (Mail-System)</option>
                            <option value="daemon">3 - daemon (Dienste)</option>
                            <option value="auth">4 - auth (Sicherheit/Auth)</option>
                            <option value="syslog">5 - syslog</option>
                            <option value="local0">16 - local0</option>
                            <option value="local1">17 - local1</option>
                            <option value="local2">18 - local2</option>
                            <option value="local3">19 - local3</option>
                            <option value="local4">20 - local4</option>
                            <option value="local5">21 - local5</option>
                            <option value="local6">22 - local6</option>
                            <option value="local7">23 - local7</option>
                        </select>
                    </div>

                    <!-- Protocol Filter -->
                    <div>
                        <select id="filter-protocol" class="w-full py-2 px-3 text-xs bg-gray-900 border border-gray-700 rounded-lg text-white focus:outline-none focus:ring-1 focus:ring-blue-500 focus:border-blue-500">
                            <option value="all">Alle Protokolle (UDP & TLS)</option>
                            <option value="udp">Nur UDP (Port 514)</option>
                            <option value="tls">Nur TLS (Port 6514)</option>
                        </select>
                    </div>

                </div>

                <!-- Action Buttons (Right) -->
                <div class="flex items-center space-x-2 shrink-0">
                    <button id="btn-reset-filters" type="button" class="px-3 py-2 text-xs font-medium text-gray-300 bg-gray-700 hover:bg-gray-600 rounded-lg border border-gray-600 transition-colors" title="Filter zurücksetzen">
                        Zurücksetzen
                    </button>

                    <button id="btn-pause-stream" type="button" class="px-3 py-2 text-xs font-medium text-gray-300 bg-gray-700 hover:bg-gray-600 rounded-lg border border-gray-600 transition-colors inline-flex items-center">
                        <svg class="w-3.5 h-3.5 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 9v6m4-6v6m7-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        <span id="btn-pause-text">Pausieren</span>
                    </button>

                    <button id="btn-clear-buffer" type="button" class="px-3 py-2 text-xs font-medium text-rose-300 bg-rose-950/40 hover:bg-rose-900/60 rounded-lg border border-rose-800 transition-colors" title="In-Memory Speicher leeren">
                        Leeren
                    </button>

                    <button id="btn-export-json" type="button" class="px-3 py-2 text-xs font-medium text-gray-300 bg-gray-700 hover:bg-gray-600 rounded-lg border border-gray-600 transition-colors" title="Gefilterte Meldungen als JSON herunterladen">
                        Export (JSON)
                    </button>

                    <button id="btn-simulate-msg" type="button" class="px-3 py-2 text-xs font-medium text-white bg-blue-600 hover:bg-blue-500 rounded-lg shadow transition-colors" title="Test-Nachricht über WebSocket senden">
                        + Simulation
                    </button>
                </div>

            </div>
        </div>

        <!-- Live Syslog Message Table -->
        <div class="bg-gray-800 border border-gray-700 rounded-xl overflow-hidden shadow-sm flex-1 flex flex-col min-h-[400px]">
            <div class="overflow-x-auto flex-1">
                <table class="w-full text-left border-collapse">
                    <thead class="bg-gray-850 border-b border-gray-700 text-gray-300 uppercase text-[10px] tracking-wider font-semibold sticky top-0 z-10">
                        <tr>
                            <th scope="col" class="py-2.5 px-3">Zeit</th>
                            <th scope="col" class="py-2.5 px-3">Level</th>
                            <th scope="col" class="py-2.5 px-3">Facility</th>
                            <th scope="col" class="py-2.5 px-3">Port</th>
                            <th scope="col" class="py-2.5 px-3">Host / Quelle</th>
                            <th scope="col" class="py-2.5 px-3">App[PID]</th>
                            <th scope="col" class="py-2.5 px-3">Syslog-Meldung</th>
                            <th scope="col" class="py-2.5 px-3 text-right">Details</th>
                        </tr>
                    </thead>
                    <tbody id="syslog-table-body" class="divide-y divide-gray-700/60 bg-gray-900">
                        <!-- Dynamically filled by JavaScript without data loss -->
                    </tbody>
                </table>

                <!-- Empty State -->
                <div id="empty-state" class="py-20 text-center flex flex-col items-center justify-center">
                    <div class="p-3 bg-gray-700/60 rounded-full text-gray-400 mb-3">
                        <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/>
                        </svg>
                    </div>
                    <h3 class="text-sm font-semibold text-gray-200">Keine Syslog-Nachrichten im aktuellen Filter</h3>
                    <p class="text-xs text-gray-400 mt-1 max-w-sm">Warte auf UDP Port 514 / TLS Port 6514 Pakete oder starte eine Test-Simulation über den Button oben.</p>
                </div>
            </div>
        </div>

    </main>

    <!-- Footer -->
    <footer class="bg-gray-800/60 border-t border-gray-800 py-3 text-center text-xs text-gray-500">
        <div class="max-w-7xl mx-auto px-4 flex flex-col sm:flex-row items-center justify-between">
            <span>Syslog Debugger &bull; Laravel Reverb WebSocket &bull; Flowbite & Tailwind CSS</span>
            <span class="mt-1 sm:mt-0 font-mono text-[11px]">UDP: 514 | TLS: 6514 | WS: 8080</span>
        </div>
    </footer>

    <!-- Detail & Inspector Side Drawer (Offcanvas Sidebar) -->
    <div id="detail-drawer" class="fixed top-0 right-0 z-50 h-screen w-full sm:w-[32rem] md:w-[38rem] max-w-full bg-gray-900 border-l border-gray-700 shadow-2xl transition-transform duration-300 ease-in-out translate-x-full flex flex-col" tabindex="-1" aria-labelledby="drawer-title">
        
        <!-- Drawer Header -->
        <div class="flex items-center justify-between p-4 border-b border-gray-750 bg-gray-950 shrink-0">
            <div class="flex items-center space-x-2.5">
                <div class="p-1.5 bg-blue-500/10 text-blue-400 rounded-lg">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                </div>
                <div>
                    <h3 id="drawer-title" class="text-sm font-bold text-white">
                        Syslog Nachrichtendetails
                    </h3>
                    <p class="text-[11px] text-gray-400">Reaktive Inspektion in Echtzeit</p>
                </div>
            </div>
            
            <div class="flex items-center space-x-2">
                <span id="drawer-active-badge" class="text-[11px] font-mono px-2 py-0.5 rounded bg-gray-800 text-gray-300 border border-gray-700"></span>
                <button type="button" id="drawer-close-btn" class="text-gray-400 hover:text-white p-1.5 rounded-lg hover:bg-gray-800 transition-colors" title="Seitenleiste schließen (Esc)">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>
        </div>

        <!-- Drawer Body -->
        <div class="p-4 overflow-y-auto space-y-4 flex-1 font-mono text-xs bg-gray-900">
            
            <!-- Quick Meta Info Badges -->
            <div class="grid grid-cols-2 gap-2 font-sans text-xs">
                <div class="p-2.5 bg-gray-950/80 rounded-lg border border-gray-800">
                    <span class="text-[10px] text-gray-400 block uppercase font-medium tracking-wider">Severity & Facility</span>
                    <div class="mt-1 flex items-center flex-wrap gap-1.5" id="drawer-meta-sev-fac">
                        <!-- badges injected -->
                    </div>
                </div>
                <div class="p-2.5 bg-gray-950/80 rounded-lg border border-gray-800">
                    <span class="text-[10px] text-gray-400 block uppercase font-medium tracking-wider">Protokoll & Zeit</span>
                    <div class="mt-1 flex items-center space-x-1.5 text-xs text-gray-200" id="drawer-meta-proto-time">
                        <!-- protocol & time -->
                    </div>
                </div>
                <div class="p-2.5 bg-gray-950/80 rounded-lg border border-gray-800">
                    <span class="text-[10px] text-gray-400 block uppercase font-medium tracking-wider">Host / Quelle</span>
                    <div class="mt-1 text-xs text-blue-400 font-medium truncate" id="drawer-meta-host">
                        <!-- host / ip -->
                    </div>
                </div>
                <div class="p-2.5 bg-gray-950/80 rounded-lg border border-gray-800">
                    <span class="text-[10px] text-gray-400 block uppercase font-medium tracking-wider">App / Process ID</span>
                    <div class="mt-1 text-xs text-gray-200 truncate" id="drawer-meta-app">
                        <!-- app / pid -->
                    </div>
                </div>
            </div>

            <!-- Message Summary Box -->
            <div class="p-3 bg-gray-950 rounded-lg border border-gray-800 font-sans">
                <span class="text-[10px] font-semibold text-gray-400 uppercase tracking-wider block mb-1">Meldungstext</span>
                <p id="drawer-meta-message" class="text-xs text-gray-100 font-mono break-words select-all"></p>
            </div>

            <!-- Extracted Key-Value Pairs Section -->
            <div id="drawer-kv-section" class="hidden">
                <div class="flex items-center justify-between mb-1.5">
                    <span class="text-[11px] font-semibold text-blue-400 uppercase tracking-wider font-sans flex items-center">
                        <svg class="w-3.5 h-3.5 mr-1 text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"/></svg>
                        Extrahierte Key/Value Attribute
                    </span>
                    <span id="drawer-kv-count" class="text-[10px] font-mono px-1.5 py-0.5 rounded bg-blue-900/40 text-blue-300 border border-blue-700/50"></span>
                </div>
                <div id="drawer-kv-container" class="bg-gray-950 border border-gray-800 rounded-lg p-2 max-h-48 overflow-y-auto space-y-1 font-mono text-xs">
                    <!-- Key-Value pairs injected dynamically -->
                </div>
            </div>

            <!-- Raw String Box -->
            <div>
                <div class="flex items-center justify-between mb-1.5">
                    <span class="text-[11px] font-semibold text-gray-400 uppercase tracking-wider font-sans">Raw Syslog Payload</span>
                    <button type="button" id="btn-copy-raw" class="text-xs text-blue-400 hover:text-blue-300 font-sans transition-colors cursor-pointer">Rohdaten kopieren</button>
                </div>
                <pre id="drawer-raw-content" class="p-3 bg-gray-950 border border-gray-800 rounded-lg text-emerald-400 whitespace-pre-wrap break-all select-all text-xs max-h-48 overflow-y-auto"></pre>
            </div>

            <!-- Structured JSON Inspector -->
            <div>
                <div class="flex items-center justify-between mb-1.5">
                    <span class="text-[11px] font-semibold text-gray-400 uppercase tracking-wider font-sans">Geparste Attribute (JSON)</span>
                    <button type="button" id="btn-copy-json" class="text-xs text-blue-400 hover:text-blue-300 font-sans transition-colors cursor-pointer">JSON kopieren</button>
                </div>
                <pre id="drawer-json-content" class="p-3 bg-gray-950 border border-gray-800 rounded-lg text-gray-200 overflow-x-auto select-all text-xs max-h-72 overflow-y-auto"></pre>
            </div>

        </div>

        <!-- Drawer Footer -->
        <div class="p-3 bg-gray-950 border-t border-gray-800 flex items-center justify-between shrink-0">
            <span class="text-[11px] text-gray-400 font-sans">Klick auf andere Zeilen aktualisiert die Ansicht sofort.</span>
            <button type="button" id="drawer-footer-close-btn" class="px-3.5 py-1.5 text-xs font-medium text-gray-300 bg-gray-800 hover:bg-gray-700 rounded-lg border border-gray-600 transition-colors cursor-pointer">
                Schließen
            </button>
        </div>

    </div>

</body>
</html>
