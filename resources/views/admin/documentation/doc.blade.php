<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Documentation API - Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <style>
        /* Keep all your existing CSS - unchanged */
        :root {
            --sidebar-width: 280px;
            --sidebar-bg: #1a1d23;
            --sidebar-hover: #2a2d35;
            --sidebar-active: #0d6efd;
            --sidebar-text: #a0a4b0;
            --sidebar-text-active: #ffffff;
            --topbar-height: 60px;
        }

        body {
            font-family: 'Segoe UI', system-ui, -apple-system, sans-serif;
            background-color: #f0f2f5;
            overflow-x: hidden;
        }

        .sidebar {
            position: fixed;
            top: 0;
            left: 0;
            width: var(--sidebar-width);
            height: 100vh;
            background: var(--sidebar-bg);
            color: var(--sidebar-text);
            z-index: 1050;
            display: flex;
            flex-direction: column;
            transition: transform 0.3s ease;
        }

        .sidebar-brand {
            padding: 1.25rem 1.5rem;
            border-bottom: 1px solid rgba(255,255,255,0.08);
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .sidebar-brand .logo-icon {
            width: 38px;
            height: 38px;
            background: linear-gradient(135deg, #0d6efd, #6610f2);
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.1rem;
            color: #fff;
            flex-shrink: 0;
        }

        .sidebar-brand h5 {
            margin: 0;
            color: #fff;
            font-size: 1rem;
            font-weight: 700;
            line-height: 1.2;
        }

        .sidebar-brand small {
            color: var(--sidebar-text);
            font-size: var(--app-font-min, 15px);
            font-weight: 400;
        }

        .sidebar-nav {
            flex: 1;
            overflow-y: auto;
            padding: 1rem 0;
        }

        .sidebar-nav .nav-section-title {
            padding: 0.5rem 1.5rem;
            font-size: var(--app-font-min, 15px);
            text-transform: uppercase;
            letter-spacing: 1.2px;
            color: #5a5e6a;
            font-weight: 700;
            margin-top: 0.5rem;
        }

        .sidebar-nav .nav-link {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            padding: 0.6rem 1.5rem;
            color: var(--sidebar-text);
            font-size: var(--app-font-min, 15px);
            font-weight: 500;
            border-radius: 0;
            transition: all 0.15s ease;
            border-left: 3px solid transparent;
            text-decoration: none;
        }

        .sidebar-nav .nav-link:hover {
            background: var(--sidebar-hover);
            color: #d0d4dc;
        }

        .sidebar-nav .nav-link.active {
            background: rgba(13, 110, 253, 0.12);
            color: var(--sidebar-text-active);
            border-left-color: var(--sidebar-active);
            font-weight: 600;
        }

        .sidebar-nav .nav-link .bi {
            font-size: 1.15rem;
            width: 1.3rem;
            text-align: center;
        }

        .sidebar-nav .nav-link .endpoint-count {
            margin-left: auto;
            background: rgba(255,255,255,0.08);
            color: var(--sidebar-text);
            font-size: var(--app-font-min, 15px);
            padding: 0.15rem 0.55rem;
            border-radius: 50px;
            font-weight: 600;
        }

        .sidebar-nav .nav-link.active .endpoint-count {
            background: rgba(255,255,255,0.18);
            color: #fff;
        }

        .sidebar-footer {
            padding: 1rem 1.5rem;
            border-top: 1px solid rgba(255,255,255,0.08);
            font-size: var(--app-font-min, 15px);
        }

        .endpoint-card .row {
            align-items: stretch;
        }

        .code-block {
            min-height: 220px;
        }

        .main-content {
            margin-left: var(--sidebar-width);
            min-height: 100vh;
            transition: margin-left 0.3s ease;
        }

        .topbar {
            height: var(--topbar-height);
            background: #fff;
            border-bottom: 1px solid #e3e6ec;
            display: flex;
            align-items: center;
            padding: 0 1.75rem;
            position: sticky;
            top: 0;
            z-index: 1020;
            gap: 1rem;
        }

        .topbar .base-url-badge {
            background: #eef2ff;
            border: 1px solid #c7d2fe;
            color: #3b52b5;
            padding: 0.35rem 0.85rem;
            border-radius: 8px;
            font-family: 'SFMono-Regular', Consolas, monospace;
            font-size: var(--app-font-min, 15px);
            font-weight: 600;
        }

        .topbar .hamburger {
            display: none;
            background: none;
            border: none;
            font-size: 1.4rem;
            color: #333;
            cursor: pointer;
            padding: 0.25rem;
        }

        .page-content {
            padding: 1.75rem;
        }

        .page-title {
            font-size: 1.5rem;
            font-weight: 700;
            color: #1a1d23;
            margin-bottom: 0.25rem;
        }

        .page-subtitle {
            font-size: var(--app-font-min, 15px);
            color: #6c757d;
            margin-bottom: 1.5rem;
        }

        .endpoint-card {
            border: 1px solid #e3e6ec;
            border-radius: 12px;
            background: #fff;
            margin-bottom: 1rem;
            overflow: hidden;
            transition: box-shadow 0.2s ease;
        }

        .endpoint-card:hover {
            box-shadow: 0 4px 15px rgba(0,0,0,0.06);
        }

        .endpoint-card .ep-header {
            padding: 0.9rem 1.25rem;
            display: flex;
            align-items: center;
            gap: 0.75rem;
            cursor: pointer;
            user-select: none;
            transition: background 0.15s ease;
            flex-wrap: wrap;
        }

        .endpoint-card .ep-header:hover {
            background: #f8f9fb;
        }

        .endpoint-card .ep-header .method-badge {
            padding: 0.3rem 0.7rem;
            border-radius: 6px;
            font-size: var(--app-font-min, 15px);
            font-weight: 700;
            letter-spacing: 0.5px;
            color: #fff;
            min-width: 56px;
            text-align: center;
        }

        .method-get { background: #0d6efd; }
        .method-post { background: #198754; }
        .method-put { background: #fd7e14; }
        .method-delete { background: #dc3545; }
        .method-patch { background: #ffc107; color: #000; }

        .endpoint-card .ep-header .ep-path {
            font-family: 'SFMono-Regular', Consolas, monospace;
            font-size: 0.95rem;
            font-weight: 600;
            color: #1a1d23;
        }

        .endpoint-card .ep-header .ep-desc {
            color: #6c757d;
            font-size: var(--app-font-min, 15px);
            font-style: italic;
        }

        .endpoint-card .ep-header .chevron {
            margin-left: auto;
            color: #adb5bd;
            transition: transform 0.3s ease;
            font-size: var(--app-font-min, 15px);
        }

        .endpoint-card .ep-header.collapsed .chevron {
            transform: rotate(-90deg);
        }

        .endpoint-card .ep-body {
            border-top: 1px solid #e3e6ec;
            padding: 1.25rem;
        }

        .section-title {
            font-size: var(--app-font-min, 15px);
            text-transform: uppercase;
            letter-spacing: 1px;
            color: #6c757d;
            font-weight: 700;
            border-bottom: 2px solid #e3e6ec;
            padding-bottom: 0.5rem;
            margin-bottom: 0.85rem;
        }

        .param-table {
            font-size: var(--app-font-min, 15px);
        }

        .param-table th {
            font-size: var(--app-font-min, 15px);
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: #6c757d;
            font-weight: 600;
            border-bottom-width: 1px;
        }

        .param-table td {
            vertical-align: middle;
        }

        .param-table code {
            color: #d63384;
            font-weight: 600;
        }

        .code-block {
            background: #1e2127;
            color: #abb2bf;
            border-radius: 10px;
            padding: 1rem 1.25rem;
            font-size: var(--app-font-min, 15px);
            line-height: 1.65;
            overflow-x: auto;
            border: 1px solid #2c313a;
        }

        .code-block .json-key { color: #e06c75; }
        .code-block .json-string { color: #98c379; }
        .code-block .json-number { color: #d19a66; }
        .code-block .json-boolean { color: #56b6c2; }
        .code-block .json-null { color: #569cd6; }

        .auth-card {
            border-radius: 12px;
            border: 1px solid #e3e6ec;
            background: linear-gradient(135deg, #f8f9fb 0%, #fff 100%);
        }

        .response-badge {
            display: inline-flex;
            align-items: center;
            gap: 0.4rem;
            padding: 0.3rem 0.75rem;
            border-radius: 50px;
            font-size: var(--app-font-min, 15px);
            font-weight: 500;
        }

        .badge-requis {
            background-color: #dc3545;
            color: white;
            font-size: var(--app-font-min, 15px);
            padding: 0.2rem 0.4rem;
            margin-left: 0.25rem;
        }

        .badge-optionnel {
            background-color: #6c757d;
            color: white;
            font-size: var(--app-font-min, 15px);
            padding: 0.2rem 0.4rem;
            margin-left: 0.25rem;
        }

        .badge-type {
            background-color: #0d6efd;
            color: white;
            font-size: var(--app-font-min, 15px);
            padding: 0.2rem 0.4rem;
            margin-left: 0.25rem;
        }

        .sidebar-overlay {
            display: none;
            position: fixed;
            inset: 0;
            background: rgba(0,0,0,0.45);
            z-index: 1040;
        }

        @media (max-width: 991.98px) {
            .sidebar {
                transform: translateX(-100%);
            }
            .sidebar.show {
                transform: translateX(0);
            }
            .sidebar-overlay.show {
                display: block;
            }
            .main-content {
                margin-left: 0;
            }
            .topbar .hamburger {
                display: block;
            }
        }

        .param-constraints {
            font-size: var(--app-font-min, 15px);
            color: #6c757d;
            display: block;
        }

        .param-example {
            font-family: monospace;
            background: #f8f9fa;
            padding: 0.1rem 0.3rem;
            border-radius: 4px;
            font-size: var(--app-font-min, 15px);
        }
    </style>
    <link rel="stylesheet" href="{{ asset('files/assets/css/typography.css') }}?v=20260923">
</head>
<body>

@php
    // Déterminer le premier module non vide pour l'affichage par défaut
    $firstModule = null;
    foreach ($docs['modules'] as $key => $module) {
        if (!isset($module['empty']) || !$module['empty']) {
            $firstModule = $key;
            break;
        }
    }
    $firstModule = $firstModule ?? array_key_first($docs['modules']);
@endphp

        <!-- ===================== SIDEBAR OVERLAY (mobile) ===================== -->
<div class="sidebar-overlay" id="sidebarOverlay"></div>

<!-- ===================== SIDEBAR ===================== -->
<nav class="sidebar" id="sidebar">
    <!-- Brand -->
    <div class="sidebar-brand">
        <div class="logo-icon"><i class="bi bi-braces-asterisk"></i></div>
        <div>
            <h5>API Docs</h5>
            <small>v1.0 - REST API</small>
        </div>
    </div>

    <!-- Navigation -->
    <div class="sidebar-nav">
        <div class="nav-section-title">Navigation</div>
        @adminroute('admin.dashboard.index')
        <a href="{{ route('admin.dashboard.index') }}" class="nav-link">
            <i class="bi bi-house"></i>
            RETOUR
        </a>
        @endadminroute

        <div class="nav-section-title">Modules</div>
        @foreach($docs['modules'] as $key => $module)
            @if(!isset($module['visible']) || $module['visible'] !== false)
                <a href="#" class="nav-link {{ $key === $firstModule ? 'active' : '' }}"
                   data-target="{{ $key }}-pane"
                   onclick="switchTab(event, '{{ $key }}-pane')">
                    <i class="bi {{ $module['icon'] ?? 'bi-folder' }}"></i>
                    {{ $module['title'] }}
                    @if(isset($module['endpoints']))
                        <span class="endpoint-count">{{ count($module['endpoints']) }}</span>
                    @endif
                </a>
            @endif
        @endforeach

        @if(isset($docs['auth']))
            <div class="nav-section-title mt-3">Informations</div>
            <a href="#" class="nav-link" data-target="auth-pane" onclick="switchTab(event, 'auth-pane')">
                <i class="bi {{ $docs['auth']['icon'] ?? 'bi-shield-lock' }}"></i>
                {{ $docs['auth']['title'] ?? 'Authentification' }}
            </a>
        @endif
    </div>

    <!-- Footer -->
    <div class="sidebar-footer text-center">
        <span class="text-secondary">Powered by <strong class="text-light">Bootstrap 5</strong></span>
    </div>
</nav>

<!-- ===================== MAIN CONTENT ===================== -->
<div class="main-content">

    <!-- Topbar -->
    <div class="topbar">
        <button class="hamburger" id="hamburgerBtn" aria-label="Menu">
            <i class="bi bi-list"></i>
        </button>
        <span class="fw-semibold text-dark" id="currentPageTitle">
            {{ $docs['modules'][$firstModule]['title'] ?? 'Documentation' }}
        </span>
        <div class="ms-auto d-flex align-items-center gap-3">
            @if(isset($docs['base_url']))
                <span class="base-url-badge d-none d-md-inline-block">
                <i class="bi bi-globe2 me-1"></i> {{ $docs['base_url'] }}
            </span>
            @endif
        </div>
    </div>

    <!-- Page Content -->
    <div class="page-content">
        @foreach($docs['modules'] as $key => $module)
            @if(!isset($module['visible']) || $module['visible'] !== false)
                <div class="tab-pane-content {{ $key !== $firstModule ? 'd-none' : '' }}" id="{{ $key }}-pane">
                    <h2 class="page-title">
                        <i class="bi {{ $module['icon'] ?? 'bi-folder' }} me-2"></i>
                        {{ $module['title'] }}
                    </h2>
                    <p class="page-subtitle">{{ $module['description'] ?? '' }}</p>

                    @foreach($module['endpoints'] as $endpoint)
                        @php
                            // Nettoyer le nom du module (enlever espaces, apostrophes, etc.)
                            $cleanKey = preg_replace('/[^a-zA-Z0-9]/', '', $key);
                            $cleanPath = preg_replace('/[\/{}]/', '', $endpoint['path']);
                            $endpointId = 'ep-' . $cleanKey . '-' . $cleanPath;
                            $methodClass = 'method-' . strtolower($endpoint['method']);
                        @endphp
                        <div class="endpoint-card">
                            <div class="ep-header collapsed" data-bs-toggle="collapse" data-bs-target="#{{ $endpointId }}">
                                <span class="method-badge {{ $methodClass }}">{{ $endpoint['method'] }}</span>
                                <span class="ep-path">{{ $endpoint['path'] }}</span>
                                <span class="ep-desc">{{ $endpoint['summary'] }}</span>
                                <i class="bi bi-chevron-down chevron"></i>
                            </div>
                            <div class="collapse" id="{{ $endpointId }}">
                                <div class="ep-body">

                                    {{-- Paramètres d'URL --}}
                                    @if(!empty($endpoint['url_parameters']))
                                        <h6 class="section-title">Paramètres d'URL</h6>
                                        <table class="table param-table">
                                            <thead>
                                            <tr>
                                                <th>Paramètre</th>
                                                <th>Type</th>
                                                <th>Description</th>
                                            </tr>
                                            </thead>
                                            <tbody>
                                            @foreach($endpoint['url_parameters'] as $param)
                                                <tr>
                                                    <td><code>{{ $param['name'] }}</code></td>
                                                    <td>{{ $param['type'] }}</td>
                                                    <td>{{ $param['description'] }}</td>
                                                </tr>
                                            @endforeach
                                            </tbody>
                                        </table>
                                    @endif

                                    {{-- Paramètres du corps --}}
                                    @if(!empty($endpoint['body_parameters']))
                                        <h6 class="section-title">Paramètres du corps (Body)</h6>
                                        <table class="table param-table">
                                            <thead>
                                            <tr>
                                                <th>Paramètre</th>
                                                <th>Type</th>
                                                <th>Requis</th>
                                                <th>Description</th>
                                            </tr>
                                            </thead>
                                            <tbody>
                                            @foreach($endpoint['body_parameters'] as $param)
                                                <tr>
                                                    <td><code>{{ $param['name'] }}</code></td>
                                                    <td>{{ $param['type'] }}</td>
                                                    <td>
                                                        @if($param['required'] ?? false)
                                                            <span class="badge bg-danger">Requis</span>
                                                        @else
                                                            <span class="badge bg-secondary">Optionnel</span>
                                                        @endif
                                                    </td>
                                                    <td>
                                                        {{ $param['description'] }}
                                                        @if(isset($param['example']))
                                                            <br><small>Ex: <code>{{ $param['example'] }}</code></small>
                                                        @endif
                                                    </td>
                                                </tr>
                                            @endforeach
                                            </tbody>
                                        </table>
                                    @endif

                                    {{-- Exemple de requête --}}
                                    @if(isset($endpoint['request_example']))
                                        <h6 class="section-title">Exemple de requête</h6>
                                        <pre class="code-block"><code>@php
                                                    $example = is_array($endpoint['request_example'])
                                                        ? json_encode($endpoint['request_example'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)
                                                        : $endpoint['request_example'];
                                                    echo htmlspecialchars($example);
                                                @endphp</code></pre>
                                    @endif

                                    {{-- Exemple de réponse --}}
                                    @if(isset($endpoint['response_example']))
                                        <h6 class="section-title">Exemple de réponse</h6>
                                        <pre class="code-block"><code>@php
                                                    $example = is_array($endpoint['response_example'])
                                                        ? json_encode($endpoint['response_example'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)
                                                        : $endpoint['response_example'];
                                                    echo htmlspecialchars($example);
                                                @endphp</code></pre>
                                    @endif

                                    {{-- Codes de réponse --}}
                                    @if(!empty($endpoint['response_codes']))
                                        <h6 class="section-title">Codes de réponse</h6>
                                        <div class="d-flex flex-wrap gap-2">
                                            @foreach($endpoint['response_codes'] as $code)
                                                <span class="response-badge bg-{{ $code['type'] }} bg-opacity-10 text-{{ $code['type'] }}">
                                                    <span class="badge bg-{{ $code['type'] }}">{{ $code['code'] }}</span>
                                                    {{ $code['message'] }}
                                                </span>
                                            @endforeach
                                        </div>
                                    @endif

                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        @endforeach
    </div>
</div>

<!-- ===================== SCRIPTS ===================== -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
    const sidebar = document.getElementById('sidebar');
    const overlay = document.getElementById('sidebarOverlay');
    const hamburger = document.getElementById('hamburgerBtn');
    const pageTitle = document.getElementById('currentPageTitle');


    // Dans la section <script> à la fin, AJOUTEZ :
    document.addEventListener('DOMContentLoaded', function() {
        // Initialiser tous les collapse Bootstrap
        var collapseElements = document.querySelectorAll('.collapse');
        collapseElements.forEach(function(el) {
            new bootstrap.Collapse(el, {
                toggle: false
            });
        });

        // S'assurer que les chevrons fonctionnent
        document.querySelectorAll('.ep-header').forEach(function(header) {
            header.addEventListener('click', function() {
                setTimeout(function() {
                    if (header.classList.contains('collapsed')) {
                        header.querySelector('.chevron').style.transform = 'rotate(-90deg)';
                    } else {
                        header.querySelector('.chevron').style.transform = 'rotate(0deg)';
                    }
                }, 100);
            });
        });
    });

    const titleMap = {
        @foreach($docs['modules'] as $key => $module)
                @if(!isset($module['visible']) || $module['visible'] !== false)
        '{{ $key }}-pane': '{{ $module['title'] }}',
        @endif
                @endforeach
                @if(isset($docs['auth']))
        'auth-pane': '{{ $docs['auth']['title'] ?? 'Authentification' }}'
        @endif
    };

    function switchTab(event, targetId) {
        event.preventDefault();

        // Hide all panes
        document.querySelectorAll('.tab-pane-content').forEach(p => p.classList.add('d-none'));

        // Show target
        const targetPane = document.getElementById(targetId);
        if (targetPane) {
            targetPane.classList.remove('d-none');
        }

        // Update sidebar active
        document.querySelectorAll('.sidebar-nav .nav-link').forEach(l => l.classList.remove('active'));
        event.currentTarget.classList.add('active');

        // Update topbar title
        if (titleMap[targetId]) {
            pageTitle.textContent = titleMap[targetId];
        }

        // Close sidebar on mobile
        closeSidebar();
    }

    function closeSidebar() {
        sidebar.classList.remove('show');
        overlay.classList.remove('show');
    }

    if (hamburger) {
        hamburger.addEventListener('click', () => {
            sidebar.classList.toggle('show');
            overlay.classList.toggle('show');
        });
    }

    if (overlay) {
        overlay.addEventListener('click', closeSidebar);
    }

    // Activer le premier onglet par défaut
    document.addEventListener('DOMContentLoaded', function() {
        const firstNavLink = document.querySelector('.sidebar-nav .nav-link.active');
        if (firstNavLink) {
            const targetId = firstNavLink.getAttribute('data-target');
            if (targetId && titleMap[targetId]) {
                pageTitle.textContent = titleMap[targetId];
            }
        }
    });
</script>

</body>
</html>