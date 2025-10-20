@extends('layouts.app')
@section('content')
    <style>
        /* Giảm toàn bộ kích thước, khoảng cách, font */
        .compact-form {
            font-size: 0.9rem;
            max-width: 1100px;
        }

        .compact-form .card {
            margin-bottom: 0.75rem;
        }

        .compact-form .card-body {
            padding: 0.75rem 1rem;
        }

        .compact-form .card-title {
            font-size: 1rem;
            margin-bottom: 0.5rem;
        }

        .compact-form .form-control,
        .compact-form .form-select,
        .compact-form .btn {
            font-size: 0.85rem;
            padding: 0.3rem 0.5rem;
        }

        .compact-form .btn-sm {
            padding: 0.25rem 0.5rem !important;
            font-size: 0.8rem !important;
        }

        .compact-form .list-group-item {
            padding: 0.35rem 0.75rem;
            font-size: 0.85rem;
        }

        .compact-form h5 {
            font-size: 1rem;
        }

        .compact-form p {
            margin-bottom: 0.3rem;
        }

        .compact-form .selected-items-container {
            max-height: 150px;
        }

        .compact-form .row.g-4 {
            --bs-gutter-x: 1rem;
            --bs-gutter-y: 1rem;
        }
    </style>
    <div class="container my-3 compact-form">
        @csrf
        <div class="row g-4">
            {{-- Cột bên trái --}}
            <div class="col-lg-8">
                {{-- Rule name --}}
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title">Discount Rule Name</h5>
                        <input type="text" class="form-control" name="name" placeholder="Enter rule name"
                            value="{{ old('name') }}" required>
                    </div>
                </div>

                {{-- Discount type --}}
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title">Discount Type</h5>
                        <div class="row g-2">
                            <div class="col-auto">
                                <input type="number" name="discount_value" class="form-control" placeholder="0"
                                    value="{{ old('discount_value') }}">
                            </div>
                            <div class="col-auto">
                                <select name="discount_unit" class="form-select">
                                    <option value="%" {{ old('discount_unit') === '%' ? 'selected' : '' }}>%</option>
                                    <option value="amount" {{ old('discount_unit') === 'amount' ? 'selected' : '' }}>
                                        USD
                                    </option>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Based on --}}
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title">Set Discount Based On</h5>
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="based_on" id="basedCurrent" value="current"
                                {{ old('based_on', 'current') === 'current' ? 'checked' : '' }}>
                            <label class="form-check-label" for="basedCurrent">Current price</label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="based_on" id="basedCompare" value="compare"
                                {{ old('based_on') === 'compare' ? 'checked' : '' }}>
                            <label class="form-check-label" for="basedCompare">Compare at price</label>
                        </div>
                    </div>
                </div>

                {{-- Applies to --}}
                <div class="card">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <h5 class="card-title mb-0">Applies to</h5>
                            <input type="hidden" id="apply-to-choosen">
                            <button type="button" id="chooseButton" class="btn btn-outline-primary btn-sm"
                                data-bs-toggle="modal">
                                <i class="bi bi-plus-lg"></i> Select
                            </button>
                        </div>

                        @foreach (['Products and variants', 'Collections', 'Tags', 'Vendors', 'Whole store'] as $k => $label)
                            <div class="form-check">
                                <input class="form-check-input apply-to-radio" type="radio" id="applyTo{{ $k }}" name="apply_to"
                                    value="{{ $label }}" {{ old('apply_to', $k == 0 ? $label : '') === $label ? 'checked' : '' }}>
                                <label class="form-check-label" for="applyTo{{ $k }}">{{ $label }}</label>
                            </div>
                        @endforeach
                    </div>
                </div>

                {{-- Selected items --}}
                <div class="card" id="selectedBlock">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <h5 class="card-title mb-0" id="selectedTitle">Selected Products</h5>
                            <button id="removeAllBtn" type="button" class="btn btn-sm btn-outline-danger">Remove
                                All</button>
                        </div>
                        <div class="selected-items-container border rounded" style="
                                        max-height: 200px;
                                        overflow-y: auto;
                                        border: 1px solid #dee2e6;
                                        border-radius: 0.5rem;
                                    ">
                            <ul class="list-group list-group-flush mb-0" id="selectedList"></ul>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Cột phải --}}
            <div class="col-lg-4">
                {{-- Preview --}}
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title">Preview</h5>
                        <p><strong>SALE</strong></p>
                        <p>Rule Name: <span id="previewName" class="text-muted"></span></p>
                        <p><span id="previewDiscount"></span></p>
                        <p>Based on <span id="previewBasedOn">Current price</span></p>
                        <p>Start: <span id="previewStart"></span></p>
                        <p>Tags: <span id="previewTags">ADD TAG HERE</span></p>
                    </div>
                </div>

                {{-- Custom tags --}}
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title">Custom Tags</h5>
                        <div class="input-group mb-2">
                            <input type="text" id="customTagInput" class="form-control" placeholder="Add a tag">
                            <button id="addTagBtn" class="btn btn-outline-secondary btn-sm" type="button">Add</button>
                        </div>
                        <div id="tagContainer"></div>
                    </div>
                </div>

                {{-- Start date --}}
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title">Start Date & Time</h5>
                        <div class="mb-2">
                            <input type="datetime-local" class="form-control" name="start_at" value="{{ old('start_at') }}">
                        </div>

                        <div class="form-check mb-2">
                            <input class="form-check-input" type="checkbox" id="setEnd">
                            <label class="form-check-label" for="setEnd">Set End Date</label>
                        </div>

                        <div id="endDateBlock" class="d-none">
                            <h5 class="card-title">End Date & Time</h5>
                            <div class="mb-2">
                                <input type="datetime-local" class="form-control" name="end_at" value="{{ old('end_at') }}">
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Submit --}}
        <div class="mt-3">
            <button type="button" id="saveRuleBtn" class="btn btn-sm btn-primary px-3">Save Rule</button>
            <a id="goRules" href="javascript:void(0)" class="btn btn-sm btn-secondary px-3">Cancel</a>
        </div>
    </div>
    <!-- Item Modal -->
    <div class="modal" id="itemModal" tabindex="-1" data-bs-backdrop="static" data-bs-keyboard="false">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 id="modal-title" class="modal-title">Select items</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>

                <!-- 🟩 Khu vực hiển thị các item đã chọn -->
                <div class="selected-items-container" style="
                                        max-height: 200px;
                                        overflow-y: auto;
                                        border: 1px solid #dee2e6;
                                        border-radius: 0.5rem;
                                    ">
                    <div id="modalItemSelected" class="p-3 border-bottom">
                        <h6>Selected items:</h6>
                        <div id="selectedItemsContainer" class="d-flex flex-wrap gap-2"></div>
                    </div>
                </div>

                <div class="selected-items-container" style="
                                                        max-height: 600px;
                                                        overflow-y: auto;
                                                        border: 1px solid #dee2e6;
                                                        border-radius: 0.5rem;
                                                    ">
                    <div class="modal-body">
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
                </div>
                <!-- 🟥 Footer thêm nút đóng -->
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
                'Products and variants': 'products',
                'Collections': 'collections',
                'Tags': 'tags',
                'Vendors': 'vendors',
                'Whole store': null
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
                    // console.log("result", result);

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
                console.log('itemType', itemType);
                console.log('applyToChoosen', applyToChoosen.value);
                console.log('selectedItems before', selectedItems);

                if (!applyToChoosen || !applyToChoosen.value || applyToChoosen.value !== itemType) {
                    selectedItems = [];
                }
                window.selectedItems = selectedItems;
                console.log('selectedItems After', selectedItems);

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
                const displayUnit = unit === "%" ? "%" : "USD";
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
        });
    </script>
    {{-- Create rule --}}
    <script>
        document.addEventListener("DOMContentLoaded", function () {
            const btn = document.getElementById("saveRuleBtn");
            if (!btn) return;

            btn.addEventListener("click", async function () {
                btn.disabled = true;
                btn.textContent = "Saving...";

                try {
                    const token = await window.AppBridgeUtils.getSessionToken(window.app);
                    // --- Lấy dữ liệu từ các input ---
                    const name = document.querySelector('[name="name"]').value.trim();
                    const based_on_raw = document.querySelector('[name="based_on"]:checked')?.value || 'current';
                    const discount_value = parseFloat(document.querySelector('[name="discount_value"]').value) || 0;
                    const discount_unit = document.querySelector('[name="discount_unit"]').value;
                    const apply_to_raw = document.querySelector('[name="apply_to"]:checked')?.value || '';
                    const start_at = document.querySelector('[name="start_at"]').value || null;
                    const end_at = document.querySelector('[name="end_at"]').value || null;
                    const add_tag = document.querySelector('#tagContainer span')
                        ? document.querySelector('#tagContainer span').childNodes[0].textContent.trim()
                        : null
                    // --- Map dữ liệu ---
                    const based_on_map = { 'current': 'current_price', 'compare': 'compare_at_price' };
                    const discount_type_map = { '%': 'percent', 'amount': 'fixed' };
                    const applies_to_map = {
                        'Products and variants': 'products',
                        'Collections': 'collections',
                        'Tags': 'tags',
                        'Vendors': 'vendors',
                        'Whole store': 'whole_store'
                    };
                    // --- Payload gửi lên API ---
                    const payload = {
                        name,
                        based_on: based_on_map[based_on_raw] || 'current_price',
                        discount_value,
                        discount_type: discount_type_map[discount_unit] || 'percent',
                        applies_to: applies_to_map[apply_to_raw] || 'products',
                        applies_to_value: (window.selectedItems || []).map(it => it.id),
                        status: 'inactive',
                        start_at,
                        end_at,
                        shop: SHOP,
                        add_tag: add_tag
                    };
                    console.log("payload:", payload);
                    // --- Gửi request AJAX ---
                    const response = await fetch("/rules", {
                        method: "POST",
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
                        // console.error('result ',result);
                        showToast(result.message || "Error creating rule", true);
                        btn.disabled = false;
                        btn.textContent = "Save Rule";
                        showToast("✅ Rule created successfully!");
                        return;
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
                    btn.textContent = "Save Rule";
                }
            });
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