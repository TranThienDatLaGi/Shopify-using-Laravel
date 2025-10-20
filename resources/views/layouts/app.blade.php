<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? 'My Shopify App' }}</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</head>
<body>
    {{-- Navbar Bootstrap đẹp hơn --}}
    <nav class="navbar navbar-expand-lg navbar-light bg-white shadow-sm border-bottom">
        <div class="container">
            <div class="collapse navbar-collapse" id="navbarMenu">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a id="goHome" class="nav-link px-3 fw-semibold text-primary" href="javascript:void(0)">Home</a>
                    </li>
                    <li class="nav-item">
                        <a id="goProducts" class="nav-link px-3 fw-semibold text-primary"
                            href="javascript:void(0)">Products</a>
                    </li>
                    <li class="nav-item">
                        <a id="goRules" class="nav-link px-3 fw-semibold text-primary" href="javascript:void(0)">My
                            Rules</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>
    {{-- Nội dung trang --}}
    <div style="padding: 20px;">
        @yield('content')
    </div>
    {{-- Shopify App Bridge CDN --}}
    <script src="https://unpkg.com/@shopify/app-bridge@3"></script>
    <script src="https://unpkg.com/@shopify/app-bridge@3.0.0/umd/index.js"></script>
    <script src="https://unpkg.com/@shopify/app-bridge/actions@3.0.0/umd/index.js"></script>
    <script src="https://unpkg.com/@shopify/app-bridge-utils@3.0.0/umd/index.js"></script>
    <script>
        document.addEventListener("DOMContentLoaded", () => {
            // --- Setup ---
            const AppBridge = window["app-bridge"];
            const AppBridgeUtils = window["app-bridge-utils"];
            const createApp = AppBridge.default;
            const { TitleBar, Toast, Redirect } = AppBridge.actions;

            const HOST = new URLSearchParams(window.location.search).get("host");
            const SHOP = new URLSearchParams(window.location.search).get("shop");

            const app = createApp({
                apiKey: "{{ config('shopify-app.api_key') }}",
                host: HOST,
                forceRedirect: true
            });
            window.app = app;
            window.AppBridgeUtils = AppBridgeUtils;
            window.SHOP = SHOP;
            TitleBar.create(app, { title: "{{ $title ?? 'Dashboard' }}" });
            function showToast(message, isError = false) {
                const toast = Toast.create(app, {
                    message,
                    duration: 3000,
                    isError
                });
                toast.dispatch(Toast.Action.SHOW);
            }
            showToast("Xin chào từ Blade + App Bridge!");
            const redirect = Redirect.create(app);
            const go = path => redirect.dispatch(Redirect.Action.APP, path);
            function onClick(id, handler) {
                const el = document.getElementById(id);
                if (el) el.addEventListener("click", handler);
            }

            onClick("goHome", () => go("/"));
            onClick("goProducts", () => go("/products"));
            onClick("goRules", () => go("/rules"));
            onClick("createRule", () => go("/rules/create"));
        });
    </script>
    
</body>

</html>