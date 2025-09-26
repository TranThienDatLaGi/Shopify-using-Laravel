<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>{{ $title ?? 'My Shopify App' }}</title>
</head>
<body>
    {{-- Navbar --}}
    <nav style="padding: 10px; background: #f4f6f8; border-bottom: 1px solid #ddd;">
        <button id="goHome" style="margin-right: 15px;">Home</button>
        <button id="goProducts" style="margin-right: 15px;">Products</button>
        <button id="goOrders">Orders</button>
    </nav>

    {{-- Nội dung trang --}}
    <div style="padding: 20px;">
        @yield('content')
    </div>

    {{-- Shopify App Bridge CDN --}}
    <script src="https://unpkg.com/@shopify/app-bridge@3"></script>

    <script>
        document.addEventListener("DOMContentLoaded", function () {
            var AppBridge = window['app-bridge'];
            var createApp = AppBridge.default;
            var actions = AppBridge.actions;

            // Khởi tạo App Bridge
            var app = createApp({
                apiKey: "{{ config('shopify-app.api_key') }}",
                host: new URLSearchParams(window.location.search).get("host"),
                forceRedirect: true
            });

            // TitleBar
            var TitleBar = actions.TitleBar;
            TitleBar.create(app, { title: "{{ $title ?? 'Dashboard' }}" });

            // Toast test
            var Toast = actions.Toast;
            var toast = Toast.create(app, { message: "Xin chào từ Blade + App Bridge!" });
            toast.dispatch(Toast.Action.SHOW);

            // Redirect
            var Redirect = actions.Redirect;

            document.getElementById("goHome").addEventListener("click", function () {
                Redirect.create(app).dispatch(Redirect.Action.APP, "/");
            });

            document.getElementById("goProducts").addEventListener("click", function () {
                Redirect.create(app).dispatch(Redirect.Action.APP, "/products");
            });

            document.getElementById("goOrders").addEventListener("click", function () {
                Redirect.create(app).dispatch(Redirect.Action.APP, "/orders");
            });
        });
    </script>
</body>

</html>