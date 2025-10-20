@extends('layouts.app')

@section('content')
    <style>
        /* -------------------------
           COMPACT FORM STYLE
           ------------------------- */
        .compact-form {
            font-size: 0.9rem;
            max-width: 1100px;
            margin: auto;
        }

        /* Card & container spacing */
        .compact-form .card {
            margin-bottom: 0.75rem;
            border-radius: 0.5rem;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.08);
        }

        .compact-form .card-body {
            padding: 0.75rem 1rem;
        }

        .compact-form h5.card-title {
            font-size: 1rem;
            margin-bottom: 0.5rem;
        }

        /* Input, select, button sizing */
        .compact-form .form-control,
        .compact-form .form-select {
            font-size: 0.85rem;
            padding: 0.3rem 0.5rem;
        }

        .compact-form .btn {
            font-size: 0.85rem;
            padding: 0.35rem 0.6rem;
            border-radius: 0.3rem;
        }

        .compact-form .btn-sm {
            padding: 0.25rem 0.45rem !important;
            font-size: 0.8rem !important;
        }

        /* Reduce list group item height */
        .compact-form .list-group-item {
            padding: 0.35rem 0.75rem;
            font-size: 0.85rem;
        }

        /* Tighten spacing between rows and columns */
        .compact-form .row.g-4 {
            --bs-gutter-x: 1rem;
            --bs-gutter-y: 1rem;
        }

        /* Compact checkbox and radio labels */
        .compact-form .form-check-label {
            font-size: 0.85rem;
        }

        /* Preview and small text spacing */
        .compact-form p {
            margin-bottom: 0.3rem;
        }

        /* Smaller item container */
        .compact-form .selected-items-container {
            max-height: 150px;
            font-size: 0.85rem;
        }

        /* Submit buttons */
        .compact-form .mt-4 {
            margin-top: 1rem !important;
        }
    </style>
        <div class="container my-3 compact-form">
            @csrf
            <div class="row g-4">
                {{-- Cột bên trái --}}
                <div class="col-lg-8">
                    {{-- Rule name + Discount Type --}}
                    <div class="card mb-3">
                        <div class="card-body">
                            <div class="row g-3 align-items-end">
                                <!-- Discount Rule Name -->
                                <div class="col-md-6">
                                    <h5 class="card-title mb-2">Discount Rule Name</h5>
                                    <input type="text" class="form-control" name="name" placeholder="Enter rule name"
                                        value="{{ old('name', $rule->name) }}" required>
                                </div>

                                <!-- Discount Type -->
                                <div class="col-md-6">
                                    <h5 class="card-title mb-2">Discount Type</h5>
                                    <div class="input-group">
                                        <input type="number" name="discount_value" class="form-control" placeholder="0"
                                            value="{{ old('discount_value', $rule->discount_value) }}">
                                        <select name="discount_unit" class="form-select" style="max-width: 100px;">
                                            <option value="percent" @if (old('discount_unit', $rule->discount_type) === 'percent')
                                            selected @endif>%</option>
                                            <option value="fixed" @if (old('discount_unit', $rule->discount_type) === 'fixed')
                                            selected @endif>USD</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    {{-- Based on + Status--}}
                    <div class="card mb-3">
                        <div class="card-body">
                            <h5 class="card-title">Set discount based on</h5>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="based_on" id="basedCurrent" value="current"
                                    {{ old('based_on', $rule->based_on) === 'current_price' ? 'checked' : '' }}>
                                <label class="form-check-label" for="basedCurrent">Current price</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="based_on" id="basedCompare" value="compare"
                                    {{ old('based_on', $rule->based_on) === 'compare_at_price' ? 'checked' : '' }}>
                                <label class="form-check-label" for="basedCompare">Compare at price</label>
                            </div>
                        </div>
                    </div>
                    {{-- Applies to --}}
                    <div class="card mb-3">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <h5 class="card-title mb-0">Applies to</h5>
                                <input type="hidden" id="apply-to-choosen">
                                <!-- Nút mở modal -->
                                <button type="button" id="chooseButton" class="btn btn-outline-primary btn-sm"
                                    data-bs-toggle="modal">
                                    <i class="bi bi-plus-lg"></i> Select
                                </button>
                            </div>
                            @php $applyOptions = ['products' => 'Products and variants', 'collections' => 'Collections', 'tags' => 'Tags', 'vendors' => 'Vendors', 'whole_store' => 'Whole store'];@endphp
                            @foreach ($applyOptions as $value => $label)
                                <div class="form-check">
                                    <input class="form-check-input apply-to-radio" type="radio" id="applyTo-{{ $value }}"
                                        name="apply_to" value="{{ $value }}" {{ old('apply_to', $rule->applies_to) === $value ? 'checked' : '' }}>
                                    <label class="form-check-label" for="applyTo-{{ $value }}">{{ $label }}</label>
                                </div>
                            @endforeach

                        </div>
                    </div>
                    {{-- Item picker --}}
                    <div class="card mb-3" id="selectedBlock">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <h5 class="card-title mb-0" id="selectedTitle">Selected products</h5> <button id="removeAllBtn"
                                    type="button" class="btn btn-sm btn-outline-danger">Remove All</button>
                            </div>
                            <div class="selected-items-container"
                                style=" max-height: 200px; overflow-y: auto; border: 1px solid #dee2e6; border-radius: 0.5rem; ">
                                <ul class="list-group list-group-flush mb-0" id="selectedList">
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
                {{-- Cột bên phải --}}
                <div class="col-lg-4">
                    {{-- Preview --}}
                    <div class="card mb-3">
                        <div class="card-body">
                            <h5 class="card-title">Preview</h5>
                            <p class="mb-1"><strong>SALE</strong></p>

                            <p class="mb-1">
                                Rule Name:
                                <span id="previewName" class="text-muted">{{ $rule->name }}</span>
                            </p>

                            <p class="mb-1">
                                <span id="previewDiscount">
                                    Discount
                                    {{ $rule->discount_value }}
                                    {{ $rule->discount_type === 'percent' ? '%' : 'USD' }}
                                    for
                                    {{ is_array($rule->applies_to_value) ? count($rule->applies_to_value) : 0 }}
                                    {{ ucfirst(str_replace('_', ' ', $rule->applies_to)) }}
                                </span>
                            </p>

                            <p class="mb-1">
                                Based on
                                <span id="previewBasedOn">{{ ucfirst($rule->based_on) }}</span>
                            </p>

                            <p class="mb-1">
                                Start:
                                <span id="previewStart">
                                    {{ $rule->start_at ? \Carbon\Carbon::parse($rule->start_at)->format('d M Y, h:i A') : '-' }}
                                </span>
                            </p>

                            <p>
                                Tags:
                                <span id="previewTags">{{ $rule->add_tag ?? 'No tag' }}</span>
                            </p>
                        </div>
                    </div>
                    {{-- Custom Tags --}}
                    <div class="card mb-3">
                        <div class="card-body">
                            <h5 class="card-title">Custom Tags</h5>
                            <div class="input-group mb-2">
                                <input type="text" id="customTagInput" class="form-control" placeholder="Add a tag">
                                <button id="addTagBtn" class="btn btn-outline-secondary" type="button">Add</button>
                            </div>
                            <div id="tagContainer">
                                @if ($rule->add_tag)
                                    <span class="badge bg-primary me-1">{{ $rule->add_tag }}</span>
                                @endif
                            </div>
                        </div>
                    </div>
                    {{-- Start Date --}}
                    <div class="card">
                        <div class="card-body">
                            <h5 class="card-title">Start Date & Time</h5>
                            <div class="mb-2">
                                <input type="datetime-local" class="form-control" name="start_at"
                                    value="{{ old('start_at', \Carbon\Carbon::parse($rule->start_at)->format('Y-m-d\TH:i')) }}">
                            </div>

                            <div class="form-check mb-2">
                                <input class="form-check-input" type="checkbox" id="setEnd">
                                <label class="form-check-label" for="setEnd">Set End Date</label>
                            </div>

                            <!-- End Date block -->
                            <div id="endDateBlock" class="d-none">
                                <h5 class="card-title">End Date & Time</h5>
                                <div class="mb-2">
                                    <input type="datetime-local" class="form-control" name="end_at"
                                        value="{{ old('end_at', $rule->end_at ? \Carbon\Carbon::parse($rule->end_at)->format('Y-m-d\TH:i') : '') }}">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Submit --}}
            <div class="mt-4">
                <button type="button" id="updateRuleBtn" class="btn btn-primary">Save Rule</button>
                <a id="goRules" href="javascript:void(0)" class="btn btn-secondary">Cancel</a>
            </div>
        </div>
        <!-- Item Modal -->
        <div class="modal fade" id="itemModal" tabindex="-1" data-bs-backdrop="static" data-bs-keyboard="false">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">

                    <div class="modal-header">
                        <h5 id="modal-title" class="modal-title">Select items</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>

                    <!-- 🟩 Khu vực hiển thị các item đã chọn -->
                    <div class="p-3 border-bottom" style="max-height:200px; overflow-y:auto;">
                        <div id="modalItemSelected">
                            <h6>Selected items:</h6>
                            <div id="selectedItemsContainer" class="d-flex flex-wrap gap-2"></div>
                        </div>
                    </div>

                    <!-- 🟦 Nội dung chính (search + danh sách) -->
                    <div class="modal-body" style="max-height:500px; overflow-y:auto;">
                        <!-- 🔍 Search box -->
                        <div id="searchContainer" class="input-group mb-3 d-none">
                            <input type="text" id="modalSearch" class="form-control" placeholder="Search products...">
                            <button class="btn btn-outline-secondary" id="searchBtn" type="button">Search</button>
                        </div>

                        <div id="modalLoading" class="text-center my-3 d-none">
                            <div class="spinner-border text-primary" role="status">
                                <span class="visually-hidden">Loading...</span>
                            </div>
                        </div>

                        <!-- 📦 Product list -->
                        <div id="modalBody"></div>
                    </div>

                    <!-- 🟥 Footer -->
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    </div>

                </div>
            </div>
        </div>
        <script src="https://unpkg.com/@shopify/app-bridge@3.0.0/umd/index.js"></script>
        <script src="https://unpkg.com/@shopify/app-bridge/actions@3.0.0/umd/index.js"></script>
        <script src="https://unpkg.com/@shopify/app-bridge-utils@3.0.0/umd/index.js"></script>
        <script>
            const HOST = new URLSearchParams(window.location.search).get("host");
            const SHOP = new URLSearchParams(window.location.search).get("shop");

            var AppBridge = window['app-bridge'];
            var AppBridgeUtils = window['app-bridge-utils'];
            var actions = AppBridge.actions;
            const { Toast } = AppBridge.actions;

            const app = AppBridge.createApp({
                apiKey: "{{ config('shopify-app.api_key') }}",
                host: new URLSearchParams(window.location.search).get("host"),
                forceRedirect: true
            });
            var Redirect = actions.Redirect;
            function showToast(message, isError = false) {
                const toast = Toast.create(app, {
                    message: message,
                    duration: 3000,
                    isError: isError
                });
                toast.dispatch(Toast.Action.SHOW);
            }
        </script>
        <script>
            document.addEventListener("DOMContentLoaded", function () {
                // --- Rule Name
                const nameInput = document.querySelector('input[name="name"]');
                const previewName = document.getElementById('previewName');
                if (nameInput && previewName) {
                    const updateName = () => {
                        previewName.textContent = nameInput.value.trim() || '(no name)';
                    };
                    nameInput.addEventListener('input', updateName);
                    updateName();
                }
                // --- Based On
                const basedOnRadios = document.querySelectorAll('input[name="based_on"]');
                const previewBasedOn = document.getElementById('previewBasedOn');
                if (basedOnRadios.length && previewBasedOn) {
                    const updateBasedOn = () => {
                        const checked = document.querySelector('input[name="based_on"]:checked');
                        if (checked) {
                            previewBasedOn.textContent =
                                checked.value === 'compare' ? 'Compare at price' : 'Current price';
                        }
                    };
                    basedOnRadios.forEach(radio => radio.addEventListener('change', updateBasedOn));
                    updateBasedOn();
                }

                // --- Start Date & Time
                const startInput = document.querySelector('input[name="start_at"]');
                const previewStart = document.getElementById('previewStart');
                if (startInput && previewStart) {
                    const updateStart = () => {
                        if (startInput.value) {
                            const date = new Date(startInput.value);
                            // Hiển thị đẹp hơn: "08 Oct 2025 10:00"
                            const formatted = date.toLocaleString('en-GB', {
                                day: '2-digit',
                                month: 'short',
                                year: 'numeric',
                                hour: '2-digit',
                                minute: '2-digit'
                            });
                            previewStart.textContent = formatted;
                        } else {
                            previewStart.textContent = '(not set)';
                        }
                    };
                    startInput.addEventListener('input', updateStart);
                    updateStart();
                }
                // --- Discount (Apply To)
                const applyToRadios = document.querySelectorAll('input[name="apply_to"]');
                const previewDiscount = document.getElementById('previewDiscount');
                if (applyToRadios.length && previewDiscount) {
                    const updateDiscount = () => {
                        const checked = document.querySelector('input[name="apply_to"]:checked');
                        const removeAllBtn = document.getElementById('removeAllBtn');
                        if (checked) {
                            previewDiscount.textContent = `Discount (${checked.value})`;
                        } else {
                            previewDiscount.textContent = 'Discount (not set)';
                        }
                        removeAllBtn?.click()

                    };
                    applyToRadios.forEach(radio => radio.addEventListener('change', updateDiscount));
                    updateDiscount();
                }
                // --- Set End Date
                const setEndCheckbox = document.getElementById("setEnd");
                const endDateBlock = document.getElementById("endDateBlock");

                // Khi thay đổi checkbox
                setEndCheckbox.addEventListener("change", function () {
                    if (this.checked) {
                        // Hiện khối chọn end date
                        endDateBlock.classList.remove("d-none");
                    } else {
                        // Ẩn khối chọn end date
                        endDateBlock.classList.add("d-none");

                        // Xóa giá trị cũ (nếu muốn)
                        const endInput = endDateBlock.querySelector('input[name="end_at"]');
                        if (endInput) endInput.value = "";
                    }
                });

            });
        </script>
        <script>
            document.addEventListener("DOMContentLoaded", () => {
                const chooseButton = document.getElementById('chooseButton');
                const modalEl = document.getElementById('itemModal');
                const modalItemSelected = document.getElementById('modalItemSelected');
                const selectedBlockList = document.querySelector('#selectedBlock ul.list-group');
                const selectedTitle = document.getElementById('selectedTitle');
                const removeAllBtn = document.getElementById('removeAllBtn');
                const searchInput = document.getElementById('searchInput'); // tìm trong selectedBlock
                const searchContainer = document.getElementById('searchContainer'); // modal search wrapper
                const modalLoading = document.getElementById('modalLoading');
                const modalSearchInput = document.getElementById('modalSearch');
                const modalSearchBtn = document.getElementById('searchBtn');
                const searchItemBtn = document.getElementById('searchItemBtn');
                const typeMap = {
                    'products': 'products',
                    'collections': 'collections',
                    'tags': 'tags',
                    'vendors': 'vendors',
                    'whole_store': null
                };
                window.selectedItems = window.selectedItems || [];
                let selectedItems = window.selectedItems;

                function safeBlurActiveInModal() {
                    try {
                        const active = document.activeElement;
                        if (modalEl && active && modalEl.contains(active)) active.blur();
                    } catch (err) {
                        // ignore
                        console.warn('safeBlurActiveInModal error', err);
                    }
                }

                function renderModalData(type, data) {
                    if (!Array.isArray(data) || data.length === 0) {
                        return '<div class="text-muted">No data available</div>';
                    }
                    switch (type) {
                        case "collections":
                            return data.map(item => {
                                const node = item.node || item;
                                const title = node.title || node;
                                const id = node.id || title;
                                return `
                                            <div class="d-flex justify-content-between align-items-center py-2 border-bottom">
                                              <span>${title}</span>
                                              <button class="btn btn-sm btn-outline-primary" data-id="${id}" data-type="${type}">Select</button>
                                            </div>`;
                            }).join('');

                        case "tags":
                        case "vendors":
                            return data.map(item => {
                                const name = (item.node && typeof item.node === 'string') ? item.node : (item.node?.title || item.node || '');
                                const id = name;
                                return `
                                            <div class="d-flex justify-content-between align-items-center py-2 border-bottom">
                                              <span>${name}</span>
                                              <button class="btn btn-sm btn-outline-primary" data-id="${id}" data-type="${type}">Select</button>
                                            </div>`;
                            }).join('');

                        case "products":
                            return data.map(item => {
                                const node = item.node || item;
                                const id = node.id || node;
                                const title = node.title || node;
                                const image = node?.media?.edges?.[0]?.node?.image?.url || 'https://via.placeholder.com/40';
                                return `
                                            <div class="d-flex justify-content-between align-items-center py-2 border-bottom">
                                              <div class="d-flex align-items-center gap-2">
                                                <img src="${image}" alt="${title}" width="40" height="40" class="rounded">
                                                <span>${title}</span>
                                              </div>
                                              <button class="btn btn-sm btn-outline-primary" data-id="${id}" data-type="${type}">Select</button>
                                            </div>`;
                            }).join('');

                        default:
                            return '<div class="text-muted">No data available</div>';
                    }
                }

                // ---- load dữ liệu modal (hỗ trợ search & pagination) 
                async function loadModalData(itemType, searchQuery = "", cursor = null) {
                    const modalBody = document.getElementById("modalBody");
                    if (!modalBody) return;
                    modalBody.innerHTML = "";
                    if (modalLoading) modalLoading.classList.remove("d-none");

                    try {
                        const token = await window.AppBridgeUtils.getSessionToken(window.app);

                        // ✅ Dùng domain thật của app thay vì window.location.origin
                        const baseUrl = window.APP_URL || "{{ url('/') }}";
                        const url = new URL(`/shopify/data`, baseUrl);
                        url.searchParams.set("type", itemType);
                        url.searchParams.set("shop", window.SHOP);
                        if (searchQuery) url.searchParams.set("searchQuery", searchQuery);
                        if (cursor) url.searchParams.set("after", cursor);

                        const response = await fetch(url, {
                            headers: {
                                "Accept": "application/json",
                                "Authorization": `Bearer ${token}`,
                                "X-CSRF-TOKEN": "{{ csrf_token() }}"
                            }
                        });
                        if (!response.ok) throw new Error(`HTTP ${response.status}`);
                        const result = await response.json();

                        const raw = result.data || result || {};
                        let payload = [];
                        if (Array.isArray(raw.products)) payload = raw.products;
                        else if (Array.isArray(raw.edges)) payload = raw.edges;
                        else if (Array.isArray(raw.products?.edges)) payload = raw.products.edges;
                        else if (Array.isArray(raw)) payload = raw;

                        const pageInfo = raw.pageInfo || raw.products?.pageInfo || {};

                        if (modalLoading) modalLoading.classList.add("d-none");

                        if (payload.length > 0) {
                            modalBody.innerHTML = `
                                          ${renderModalData(itemType, payload)}
                                          <div class="d-flex justify-content-between mt-3">
                                            <button id="prevPage" class="btn btn-outline-secondary btn-sm" ${!pageInfo.hasPreviousPage ? 'disabled' : ''}>← Previous</button>
                                            <button id="nextPage" class="btn btn-outline-secondary btn-sm" ${!pageInfo.hasNextPage ? 'disabled' : ''}>Next →</button>
                                          </div>
                                        `;

                            // bind pagination (gán lại mỗi lần render để không double-bind)
                            document.getElementById("nextPage")?.addEventListener("click", () => {
                                if (pageInfo.endCursor) loadModalData(itemType, searchQuery, pageInfo.endCursor);
                            });
                            document.getElementById("prevPage")?.addEventListener("click", () => {
                                if (pageInfo.startCursor) loadModalData(itemType, searchQuery, pageInfo.startCursor);
                            });
                        } else {
                            modalBody.innerHTML = `<p class="text-muted">No ${itemType} found.</p>`;
                        }

                    } catch (err) {
                        if (modalLoading) modalLoading.classList.add("d-none");
                        if (modalBody) modalBody.innerHTML = `<p class="text-danger">Error: ${err.message}</p>`;
                        console.error(err);
                    }
                }

                // ---- khi nhấn Choose (mở modal) 
                chooseButton?.addEventListener('click', () => {
                    const selectedRadio = document.querySelector('.apply-to-radio:checked');
                    const modalTitle = document.getElementById('modal-title');
                    const applyToChoosen = document.getElementById('apply-to-choosen');
                    if (!selectedRadio) return;
                    const itemType = typeMap[selectedRadio.value];
                    if (!itemType) return;
                    // console.log('itemType', itemType);
                    // console.log('applyToChoosen', applyToChoosen.value);
                    // console.log('selectedItems before', selectedItems);

                    if (!applyToChoosen || !applyToChoosen.value || applyToChoosen.value !== itemType) {
                        selectedItems = [];
                    }
                    window.selectedItems = selectedItems;
                    // console.log('selectedItems After', selectedItems);

                    if (!selectedItems) modalItemSelected.innerHTML = "";
                    if (!selectedItems) selectedBlockList.innerHTML = "";
                    if (selectedTitle) selectedTitle.textContent = `Selected ${itemType}`;
                    if (modalTitle) modalTitle.textContent = `Select ${itemType}`;
                    if (itemType === 'products') {
                        if (searchContainer) searchContainer.classList.remove('d-none');
                    } else {
                        if (searchContainer) searchContainer.classList.add('d-none');
                    }
                    loadModalData(itemType);
                    if (modalEl) {
                        try {
                            const bsModal = new bootstrap.Modal(modalEl);
                            bsModal.show();
                        } catch (err) {
                            console.warn('Bootstrap modal show error', err);
                        }
                    }
                    applyToChoosen.value = itemType;
                });

                // ---- Remove All (an toàn) ----
                removeAllBtn?.addEventListener('click', () => {
                    safeBlurActiveInModal(); // blur active trước khi thay đổi DOM
                    selectedItems = [];
                    window.selectedItems = selectedItems;
                    if (modalItemSelected) modalItemSelected.innerHTML = "";
                    if (selectedBlockList) selectedBlockList.innerHTML = "";
                });

                // ---- Tìm kiếm trong selectedBlock (chỉ ẩn/hiện) 
                searchItemBtn?.addEventListener('click', () => {
                    const keyword = (searchInput.value || "").trim().toLowerCase();
                    const items = selectedBlockList?.querySelectorAll("li") || [];
                    items.forEach(li => {
                        const text = (li.textContent || "").toLowerCase();
                        li.style.display = text.includes(keyword) ? "" : "none";
                    });
                });
                // ---- Modal search (button + Enter) using API----
                modalSearchBtn?.addEventListener('click', () => {
                    const selectedRadio = document.querySelector('.apply-to-radio:checked');
                    if (!selectedRadio) return;
                    const itemType = typeMap[selectedRadio.value];
                    if (!itemType) return;
                    const q = (modalSearchInput?.value || "").trim();
                    loadModalData(itemType, q);
                });
                modalSearchInput?.addEventListener('keypress', (e) => {
                    if (e.key === 'Enter') {
                        e.preventDefault();
                        modalSearchBtn?.click();
                    }
                });

                // ---- Delegate clicks: select item / remove selected / remove from block ----
                document.addEventListener('click', (e) => {
                    const target = e.target;
                    // chọn item từ modal
                    if (target.matches('button[data-type]')) {
                        const type = target.dataset.type;
                        const id = target.dataset.id;
                        const container = target.closest('div');
                        let name = "", image = null;
                        if (type === 'products') {
                            const imgTag = container?.querySelector('img');
                            image = imgTag ? imgTag.src : null;
                            name = container?.querySelector('span')?.textContent || id;
                        } else {
                            name = container?.querySelector('span')?.textContent || id;
                        }
                        if (!id) return;
                        if (selectedItems.some(it => it.id === id)) {
                            // đã tồn tại -> ignore
                            return;
                        }
                        selectedItems.push({ id, name, type, image });
                        window.selectedItems = selectedItems;
                        renderSelectedItems();
                        renderSelectedBlock();
                        return;
                    }

                    // xóa item trong modal
                    if (target.classList.contains('remove-selected-item')) {
                        const id = target.dataset.id;
                        if (!id) return;
                        selectedItems = selectedItems.filter(it => it.id !== id);
                        window.selectedItems = selectedItems;
                        renderSelectedItems();
                        renderSelectedBlock();
                        return;
                    }

                    // xóa item trong selectedBlock (nút Remove ở card bên ngoài)
                    if (target.dataset?.action === 'remove-block' || (target.classList.contains('btn-outline-danger') && target.closest('#selectedBlock'))) {
                        const li = target.closest('li');
                        if (!li) return;
                        const id = li.dataset.id;
                        if (!id) return;
                        selectedItems = selectedItems.filter(it => it.id !== id);
                        window.selectedItems = selectedItems;
                        renderSelectedItems();
                        renderSelectedBlock();
                        return;
                    }
                });

                // ---- render selected items inside modal (top area) ----
                function renderSelectedItems() {
                    if (!modalItemSelected) return;
                    if (selectedItems.length === 0) {
                        modalItemSelected.innerHTML = "";
                        return;
                    }
                    modalItemSelected.innerHTML = `
                                                <div class="p-2 border-bottom">
                                                    <h6 class="mb-2">Selected items:</h6>
                                                    ${selectedItems.map(item => `
                                                    <div class="d-flex align-items-center justify-content-between mb-2">
                                                        <div class="d-flex align-items-center gap-2">
                                                        ${item.image ? `<img src="${item.image}" width="40" height="40" class="rounded">` : ''}
                                                        <span>${item.name}</span>
                                                        </div>
                                                        <button class="btn btn-sm btn-outline-danger remove-selected-item" data-id="${item.id}">Remove</button>
                                                    </div>
                                                    `).join('')}
                                                </div>
                                                `;
                    updatePreviewDiscount();
                }

                // ---- render selected items into the card (selectedBlock) ----
                function renderSelectedBlock() {
                    if (!selectedBlockList) return;
                    selectedBlockList.innerHTML = selectedItems.map(item => `
                                            <li class="list-group-item d-flex justify-content-between align-items-center" data-id="${item.id}">
                                                <div class="d-flex align-items-center gap-2">
                                                ${item.image ? `<img src="${item.image}" width="40" height="40" class="rounded">` : ''}
                                                <span>${item.name}</span>
                                                </div>
                                                <button class="btn btn-sm btn-outline-danger" data-action="remove-block" data-id="${item.id}">Remove</button>
                                            </li>
                                            `).join('');
                    updatePreviewDiscount();
                }
                function updatePreviewDiscount() {
                    const previewEl = document.getElementById("previewDiscount");
                    const discountUnitEl = document.querySelector('select[name="discount_unit"]');
                    const unit = discountUnitEl?.value || "%";

                    if (!previewEl) return;
                    // Lấy loại item hiện tại
                    const selectedRadio = document.querySelector('.apply-to-radio:checked');
                    const itemType = selectedRadio ? selectedRadio.value.toLowerCase() : "items";

                    const count = selectedItems.length;
                    const discountInput = document.querySelector('input[name="discount_value"]');
                    const discountValue = discountInput ? discountInput.value : 0;

                    let label = itemType;
                    if (itemType.includes("product")) label = count > 1 ? "products" : "product";
                    if (itemType.includes("vendor")) label = count > 1 ? "vendors" : "vendor";
                    if (itemType.includes("collection")) label = count > 1 ? "collections" : "collection";
                    if (itemType.includes("tag")) label = count > 1 ? "tags" : "tag";
                    const displayUnit = unit === "percent" ? "%" : "USD";
                    previewEl.textContent = count > 0
                        ? `Discount ${discountValue}${displayUnit} for ${count} ${label}`
                        : "No items selected.";
                }
                const discountValueInput = document.querySelector('input[name="discount_value"]');
                const discountUnitSelect = document.querySelector('select[name="discount_unit"]');
                if (discountValueInput) {
                    discountValueInput.addEventListener('input', updatePreviewDiscount);
                }
                if (discountUnitSelect) {
                    discountUnitSelect.addEventListener('change', updatePreviewDiscount);
                }
                function myInitFunction() {
                    let selectedItems = window.selectedItems || [];
                    // console.log('selectedItems Before', selectedItems);

                    const itemType = @json($rule->applies_to ?? null);

                    if (itemType === 'products') {
                        const productsSelect = @json($itemselect['products'] ?? []);
                        // console.log('productsSelect', productsSelect);

                        productsSelect.forEach(item => {
                            if (selectedItems.some(it => it.id === item.id)) return;
                            selectedItems.push({
                                id: item.id,
                                name: item.title,
                                type: itemType,
                                image: item.media?.edges?.[0]?.node?.image?.url || null
                            });
                        });

                    } else if (itemType === 'collections') {
                        const collectionSelect = @json($itemselect['collections'] ?? []);
                        const itemselect = @json($itemselect ?? []);
                        // console.log('collectionSelect', collectionSelect);
                        // console.log('itemselect', itemselect);

                        collectionSelect.forEach(item => {
                            if (selectedItems.some(it => it.id === item.id)) return;
                            selectedItems.push({
                                id: item.id,
                                name: item.title,
                                type: itemType,
                                image: item.media?.edges?.[0]?.node?.image?.url || null
                            });
                        });

                    } else {
                        const otherSelect = @json($rule->applies_to_value ?? []);
                        // console.log('otherSelect', otherSelect);

                        otherSelect.forEach(item => {
                            if (selectedItems.some(it => it.id === item.id)) return;
                            selectedItems.push({
                                id: item,
                                name: item,
                                type: itemType,
                                image: null
                            });
                        });
                    }

                    window.selectedItems = selectedItems;
                    // console.log('selectedItems After', selectedItems);

                    renderSelectedItems();
                    renderSelectedBlock();
                }
                myInitFunction();
            });
        </script>
        {{-- Create rule --}}
        <script>
            document.addEventListener("DOMContentLoaded", function () {
                const btn = document.getElementById("updateRuleBtn");
                if (!btn) return;

                btn.addEventListener("click", async function () {
                    btn.disabled = true;
                    btn.textContent = "Updating...";

                    try {
                        const token = await window.AppBridgeUtils.getSessionToken(window.app);
                        // console.log("Session token:", token);

                        // --- Lấy dữ liệu từ các input ---
                        const name = document.querySelector('[name="name"]').value.trim();
                        const based_on_raw = document.querySelector('[name="based_on"]:checked')?.value || 'current';
                        const discount_value = parseFloat(document.querySelector('[name="discount_value"]').value) || 0;
                        const discount_unit = document.querySelector('[name="discount_unit"]').value;
                        const apply_to_raw = document.querySelector('[name="apply_to"]:checked')?.value || '';
                        const start_at = document.querySelector('[name="start_at"]').value || null;
                        const end_at = document.querySelector('[name="end_at"]').value || null;
                        const status = @json($rule->status);
                        // console.log('discount_unit', discount_unit);

                        const add_tag = document.querySelector('#tagContainer span')
                            ? document.querySelector('#tagContainer span').childNodes[0].textContent.trim()
                            : null
                        // --- Map dữ liệu ---
                        const based_on_map = { 'current': 'current_price', 'compare': 'compare_at_price' };
                        // --- Payload gửi lên API ---
                        const payload = {
                            name,
                            based_on: based_on_map[based_on_raw] || 'current_price',
                            discount_value,
                            discount_type: discount_unit || 'percent',
                            applies_to: apply_to_raw || 'products',
                            applies_to_value: (window.selectedItems || []).map(it => it.id),
                            status: status,
                            start_at,
                            end_at,
                            shop: SHOP,
                            add_tag: add_tag
                        };
                        console.log("payload:", payload);

                        // --- Gửi request AJAX ---
                        const response = await fetch(`/rules/{{ $rule->id }}`, {
                            method: "PUT",
                            headers: {
                                "Content-Type": "application/json",
                                "Accept": "application/json",
                                "Authorization": `Bearer ${token}`,
                                "X-CSRF-TOKEN": "{{ csrf_token() }}"
                            },
                            body: JSON.stringify(payload)
                        });
                        const result = await response.json();
                        if (!response.ok) {
                            showToast(result.message || "Error updating rule", true);
                            btn.disabled = false;
                            btn.textContent = "Update Rule";
                            return;
                        }
                        // --- Kiểm tra batch nếu có ---
                        const batchId = result.batch_id || result.batchId;
                        if (batchId) {
                            await waitForBatchToFinish(batchId);
                        }
                        // --- Redirect sau khi hoàn tất ---
                        const Redirect = actions.Redirect;
                        const redirect = Redirect.create(app);
                        redirect.dispatch(Redirect.Action.APP, "/rules");

                    } catch (err) {
                        console.error(err);
                        showToast(err.message || "Unexpected error", true);
                    } finally {
                        btn.disabled = false;
                        btn.textContent = "Update Rule";
                    }
                });

                // --- Hàm chờ batch hoàn tất ---
                async function waitForBatchToFinish(batchId) {
                    const maxAttempts = 60; // tối đa 60s
                    let attempts = 0;

                    showToast("⏳ Processing... Please wait");

                    while (attempts < maxAttempts) {
                        const res = await fetch(`/bulk/status/${batchId}`, {
                            headers: { "Accept": "application/json" }
                        });
                        const data = await res.json();
                        if (data.finished) {
                            showToast("✅ Update rule successfully! Go back to My Rules");
                            return;
                        }
                        if (data.failed) {
                            showToast("❌ Batch failed!", true);
                            throw new Error("Batch failed");
                        }
                        await new Promise(r => setTimeout(r, 1000)); // đợi 1s rồi kiểm tra lại
                        attempts++;
                    }

                    throw new Error("Batch check timeout");
                }
            });
        </script>
        <script>
            const input = document.getElementById('customTagInput');
            const addBtn = document.getElementById('addTagBtn');
            const tagContainer = document.getElementById('tagContainer');
            const previewTags = document.getElementById('previewTags');

            addBtn.addEventListener('click', () => {
                const tagValue = input.value.trim();
                if (!tagValue) return;

                // Xóa tag cũ nếu có (chỉ cho phép 1 tag)
                tagContainer.innerHTML = '';

                // Tạo phần tử tag
                const tag = document.createElement('span');
                tag.className = 'badge bg-primary me-2';
                tag.style.padding = '8px 12px';
                tag.style.fontSize = '14px';
                tag.style.display = 'inline-flex';
                tag.style.alignItems = 'center';
                tag.innerHTML = `
                          ${tagValue}
                          <button type="button" class="btn-close btn-close-white ms-2" aria-label="Close"></button>
                        `;

                // Thêm sự kiện xóa tag
                tag.querySelector('button').addEventListener('click', () => {
                    tagContainer.innerHTML = '';
                    previewTags.textContent = 'ADD TAG HERE';
                });

                // Gắn tag vào container
                tagContainer.appendChild(tag);

                // Hiển thị tag trong phần preview
                previewTags.textContent = tagValue;

                // Xóa input
                input.value = '';
            });
        </script>


@endsection