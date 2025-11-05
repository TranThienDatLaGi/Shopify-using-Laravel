@extends('layouts.app')

@section('content')
    <div class="container bg-white p-3 rounded shadow-sm" style="max-width: 900px; font-size: 0.9rem;">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h4 class="fw-bold text-primary m-0">Dashboard / My Rules</h4>
            <button type="button" class="btn btn-primary px-4" id="createRule">Create Rule</button>
        </div>
        <ul class="nav nav-tabs mb-3" id="ruleTabs">
            <li class="nav-item">
                <a class="nav-link fw-semibold {{ $tab === 'main' ? 'active' : '' }}" id="main-rule"
                    href="javascript:void(0)">Main</a>
            </li>
            <li class="nav-item">
                <a class="nav-link fw-semibold {{ $tab === 'archived' ? 'active' : '' }}" id="archived-rule"
                    href="javascript:void(0)">Archived</a>
            </li>
        </ul>
        <div id="mainContainer" class="{{ $tab === 'archived' ? 'd-none' : '' }}">
            @include('rules.partials.main_rule')
        </div>

        <div id="archivedContainer" class="{{ $tab === 'main' ? 'd-none' : '' }}">
            @include('rules.partials.archived_rule')
        </div>
    </div>
    <script src="https://unpkg.com/@shopify/app-bridge@3.0.0/umd/index.js"></script>
    <script src="https://unpkg.com/@shopify/app-bridge-utils@3.0.0/umd/index.js"></script>
    <script>
        const HOST = new URLSearchParams(window.location.search).get("host");
        const SHOP = new URLSearchParams(window.location.search).get("shop");
        var AppBridge = window["app-bridge"];
        var AppBridgeUtils = window["app-bridge-utils"];
        const { Toast } = AppBridge.actions;

        const app = AppBridge.createApp({
            apiKey: "{{ config('shopify-app.api_key') }}",
            host: HOST,
            forceRedirect: true
        });

        function showToast(message, isError = false) {
            const toast = Toast.create(app, { message, duration: 3000, isError });
            toast.dispatch(Toast.Action.SHOW);
        }
    </script>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const mainTab = document.getElementById('main-rule');
            const archivedTab = document.getElementById('archived-rule');
            const mainContainer = document.getElementById('mainContainer');
            const archivedContainer = document.getElementById('archivedContainer');
            async function activateTab(selected, deselected, showContainer, hideContainer, tabName) {
                selected.classList.add('active');
                deselected.classList.remove('active');
                showContainer.classList.remove('d-none');
                hideContainer.classList.add('d-none');
                try {
                    const url = '/rules';
                    const fetchUrl = new URL(url, window.location.origin);
                    fetchUrl.searchParams.set('tab', tabName);
                    // console.log('tabName', tabName);
                    const token = await window.AppBridgeUtils.getSessionToken(window.app);
                    const response = await fetch(fetchUrl, {
                        method: "GET",
                        headers: {
                            "Accept": "application/json",
                            "Authorization": `Bearer ${token}`,
                        },
                    });
                    if (!response.ok) {
                        throw new Error(`❌ Fetch failed with status ${response.status}`);
                    }
                    const data = await response.json();
                    const tab = data.tab;
                    if (tab === 'main') {
                        ruleTableBody = document.getElementById('ruleTableBody');
                    } else {
                        ruleTableBody = document.getElementById('ruleArchivedTableBody');
                    }
                    ruleTableBody.innerHTML = data.tbody;
                    const paginationContainer = document.querySelector(".pagination-wrapper");
                    if (paginationContainer && data.pagination) {
                        paginationContainer.innerHTML = data.pagination;
                    }
                    attachPaginationEvents();
                } catch (err) {
                    console.error('💥 Error loading rules:', err);
                    ruleTableBody.innerHTML = `<tr><td colspan="10" class="text-danger text-center">Failed to load data</td></tr>`;

                }
            }
            async function attachPaginationEvents() {
                document.querySelectorAll(".pagination a").forEach(link => {
                    link.addEventListener("click", async (e) => {
                        e.preventDefault();
                        const url = e.target.closest("a").href;
                        await applyFilter(url);
                    });
                });
            }
            mainTab.addEventListener('click', () => {
                activateTab(mainTab, archivedTab, mainContainer, archivedContainer, 'main');

            });
            archivedTab.addEventListener('click', () => {
                activateTab(archivedTab, mainTab, archivedContainer, mainContainer, 'archived');
            });
        });
    </script>
@endsection