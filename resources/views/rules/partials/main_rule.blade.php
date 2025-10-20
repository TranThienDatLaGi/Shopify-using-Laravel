{{-- Search + Sort --}}
<div class="d-flex align-items-center gap-2 mb-1">
    <input type="text" id="searchInput" class="form-control w-3xl" placeholder="Search...">
    <button class="btn btn-outline-secondary" id="searchBtn">🔍</button>

    <div class="dropdown">
        <button class="btn btn-outline-secondary dropdown-toggle" type="button" id="sortBtn" data-bs-toggle="dropdown">
            ⇅ Sort
        </button>
        <div class="dropdown-menu p-2" style="min-width: 230px;">
            <p class="fw-bold mb-2">Sort By</p>
            <div class="form-check">
                <input class="form-check-input" type="radio" name="sortField" value="sortCreateAt" id="sortCreateAt"
                    checked>
                <label class="form-check-label" for="sortCreateAt">Create Time</label>
            </div>
            <div class="form-check">
                <input class="form-check-input" type="radio" name="sortField" value="sortName" id="sortName">
                <label class="form-check-label" for="sortName">Rule Name</label>
            </div>
            <div class="form-check">
                <input class="form-check-input" type="radio" name="sortField" value="sortDiscount" id="sortDiscount">
                <label class="form-check-label" for="sortDiscount">Discount Value</label>
            </div>
            <div class="form-check mb-2">
                <input class="form-check-input" type="radio" name="sortField" value="sortTimeStart" id="sortTimeStart">
                <label class="form-check-label" for="sortTimeStart">Time Start</label>
            </div>

            <hr class="my-2">

            <p class="fw-bold mb-2">Order</p>
            <div class="form-check">
                <input class="form-check-input" type="radio" name="sortOrder" value="orderOld" id="orderOld">
                <label class="form-check-label" for="orderOld">↑ Oldest First</label>
            </div>
            <div class="form-check">
                <input class="form-check-input" type="radio" name="sortOrder" value="orderNew" id="orderNew" checked>
                <label class="form-check-label" for="orderNew">↓ Newest First</label>
            </div>
        </div>
    </div>
</div>

{{-- Filter Section --}}
<div class="filter-section bg-white p-2 rounded shadow-sm border mb-4">
    <div class="row g-3">
        <!-- Status -->
        <div class="col-md-3">
            <label for="statusFilter" class="form-label fw-bold">Status</label>
            <select id="statusFilter" class="form-select">
                <option value="">All</option>
                <option value="active">Active</option>
                <option value="inactive">Inactive</option>
                <option value="stopped">Stopped</option>
            </select>
        </div>

        <!-- Apply To -->
        <div class="col-md-3">
            <label for="applyFilter" class="form-label fw-bold">Apply To</label>
            <select id="applyFilter" class="form-select">
                <option value="">All</option>
                <option value="products">Product</option>
                <option value="tags">Tags</option>
                <option value="vendors">Vendor</option>
                <option value="collections">Collection</option>
                <option value="whole_store">Whole Store</option>
            </select>
        </div>

        <!-- Start Date -->
        <div class="col-md-3">
            <label for="startDate" class="form-label fw-bold">Start Date</label>
            <input type="date" id="startDate" class="form-control">
        </div>

        <!-- End Date -->
        <div class="col-md-3">
            <label for="endDate" class="form-label fw-bold">End Date</label>
            <input type="date" id="endDate" class="form-control">
        </div>
    </div>

    <div class="row g-3 mt-3">
        <!-- Discount Value Range -->
        <div class="col-md-6">
            <label class="form-label fw-bold">Discount</label>
            <div class="input-group">
                <select id="discountType" name="discount_type" class="form-select" style="max-width: 120px;">
                    <option value=""></option>
                    <option value="percent">%</option>
                    <option value="fixed">USD</option>
                </select>

                <input id="discountMin" type="number" min="0" step="0.01" class="form-control" placeholder="Min value">
                <span class="input-group-text">to</span>
                <input id="discountMax" type="number" min="0" step="0.01" class="form-control" placeholder="Max value">
            </div>
        </div>

        <!-- Action Buttons -->
        <div class="col-md-6 d-flex align-items-end justify-content-end gap-2">
            <button id="filterReset" class="btn btn-outline-secondary">Reset</button>
            <button id="filterApply" class="btn btn-primary">Apply Filter</button>
        </div>
    </div>
</div>

{{-- Table --}}
<div class="table-responsive">
    <table class="table table-hover align-middle">
        <thead class="table-light">
            <tr>
                <th><input type="checkbox" id="selectAll"></th>
                <th>Name</th>
                <th>Details</th>
                <th>Status</th>
                <th>Active</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody id="ruleTableBody">
            @include('rules.partials.table_body', ['rules' => $rules])
        </tbody>
    </table>
    <div class="pagination-wrapper">
        @include('rules.partials.pagination', ['rules' => $rules])
    </div>
</div>

{{-- Shopify SDK --}}
<script>
    document.addEventListener("DOMContentLoaded", function () {
        const filterApply = document.getElementById("filterApply");
        const filterReset = document.getElementById("filterReset");
        const ruleTableBody = document.getElementById("ruleTableBody");
        async function applyFilter(url = "/rules") {
            const filters = {
                search: document.getElementById("searchInput")?.value || "",
                status: document.getElementById("statusFilter")?.value || "",
                applies_to: document.getElementById("applyFilter")?.value || "",
                discount_type: document.getElementById("discountType")?.value || "",
                discount_min: document.getElementById("discountMin")?.value || "",
                discount_max: document.getElementById("discountMax")?.value || "",
                sort_field: document.querySelector('input[name="sortField"]:checked')?.value || "",
                sort_order: document.querySelector('input[name="sortOrder"]:checked')?.value || "",
            };
            const fetchUrl = new URL(url, window.location.origin);
            Object.keys(filters).forEach(key => {
                if (filters[key]) fetchUrl.searchParams.set(key, filters[key]);
            });
            const token = await window.AppBridgeUtils.getSessionToken(window.app);
            try {
                const response = await fetch(fetchUrl, {
                    method: "GET",
                    headers: {
                        "Accept": "application/json",
                        "Authorization": `Bearer ${token}`,
                    },
                });
                if (!response.ok) throw new Error(`Error ${response.status}`);

                const data = await response.json();
                ruleTableBody.innerHTML = data.tbody;

                const paginationContainer = document.querySelector(".pagination-wrapper");
                if (paginationContainer && data.pagination) {
                    paginationContainer.innerHTML = data.pagination;
                }
                attachPaginationEvents();

            } catch (error) {
                console.error("Filter error:", error);
                ruleTableBody.innerHTML = `<tr><td colspan="10" class="text-danger text-center">Failed to load data</td></tr>`;
            }
        }
        filterApply.addEventListener("click", () => applyFilter());
        filterReset.addEventListener("click", () => {
            document.querySelectorAll("input, select").forEach(el => {
                if (el.type === "radio" || el.type === "checkbox") el.checked = false;
                else el.value = "";
            });
            applyFilter();
        });
        document.addEventListener("change", function (e) {
            if (e.target.name === "sortField" || e.target.name === "sortOrder") {
                applyFilter();
            }
        });
        function attachPaginationEvents() {
            document.querySelectorAll(".pagination a").forEach(link => {
                link.addEventListener("click", async (e) => {
                    e.preventDefault();
                    const url = e.target.closest("a").href;
                    await applyFilter(url);
                });
            });
        }
        let switchRuleId = null;
        let switchRow = null;
        const switchModal = new bootstrap.Modal(document.getElementById("confirmSwitchModal"));
        const confirmSwitchBtn = document.getElementById("confirmSwitchBtn");
        // Hàm mở modal xác nhận
        window.confirmSwitch = function (id, status) {
            switchRuleId = id;
            const modalTitle = document.querySelector('#confirmSwitchModal .modal-title');
            const modalBody = document.querySelector('#confirmSwitchModal .modal-body p');
            const confirmBtn = document.getElementById('confirmSwitchBtn');

            if (status === 'active') {
                modalTitle.textContent = 'CONFIRM SWITCH TO ACTIVE';
                modalBody.innerHTML = `
                Are you sure you want to <strong>activate</strong> this rule?<br>
                This action <span class="text-danger fw-bold">cannot be undone</span>.
            `;
                confirmBtn.className = 'btn btn-success';
                confirmBtn.innerHTML = '✅ Activate';
            }
            else if (status === 'inactive') {
                modalTitle.textContent = 'CONFIRM SWITCH TO INACTIVE';
                modalBody.innerHTML = `
                Are you sure you want to <strong>deactivate</strong> this rule?<br>
                This action <span class="text-danger fw-bold">cannot be undone</span>.
            `;
                confirmBtn.className = 'btn btn-warning';
                confirmBtn.innerHTML = '⛔ Deactivate';
            }
            else { // archived
                modalTitle.textContent = 'CONFIRM SWITCH TO ARCHIVED';
                modalBody.innerHTML = `
                Are you sure you want to move this <strong>rule</strong> to the <strong>archived</strong> status?<br>
                This action <span class="text-danger fw-bold">cannot be undone</span>.
            `;
                confirmBtn.className = 'btn btn-danger';
                confirmBtn.innerHTML = '🗃️ Archive';
            }
            // Gắn lại status để xử lý khi nhấn xác nhận
            confirmBtn.setAttribute('data-status', status);
            // Hiển thị modal
            switchModal.show();
        };
        function confirmSwitch(id, status) {
            switchRuleId = id;
            switchRow = document.querySelector(`tr[data-rule-id="${id}"]`);
            confirmSwitchBtn.setAttribute("data-status", status);
            switchModal.show();
        }
        confirmSwitchBtn.addEventListener("click", async () => {
            if (!switchRuleId) return;
            const row = switchRow;
            const status = confirmSwitchBtn.getAttribute("data-status");
            const token = await window.AppBridgeUtils.getSessionToken(window.app);
            try {
                document.activeElement?.blur();
                switchModal.hide();

                const response = await fetch(`/rules/${switchRuleId}/status?shop=${encodeURIComponent(SHOP)}`, {
                    method: "PUT",
                    headers: {
                        "Content-Type": "application/json",
                        "Accept": "application/json",
                        "Authorization": `Bearer ${token}`,
                        "X-CSRF-TOKEN": "{{ csrf_token() }}",
                    },
                    body: JSON.stringify({ status: status, shop: SHOP }),
                });
                const data = await response.json();
                if (!response.ok) {
                    showToast(data.error || `Failed to switch rule to ${status}!`, true);
                    return;
                }
                // console.log('row: ', row);
                // if (!row) {
                //     showToast("Row not found in DOM ❌", true);
                //     return;
                // }
                // Nếu là archived → xóa luôn dòng
                if (status === "archived") {
                    // row.remove();
                    showToast("Rule has been archived and removed from the list ✅");
                    applyFilter()
                    return;
                }
                if (status === "active" || status === "inactive") {
                    // console.log('hehehehehhe');
                    const batchId = data.batch_id || null;
                    if (batchId) await waitForBatchToFinish(batchId);

                //     const statusCell = row.querySelector("td:nth-child(4)");
                //     const actionCell = row.querySelector("td:nth-child(5)");
                //     const buttonsCell = row.querySelector("td:nth-child(6)");

                //     if (statusCell) {
                //         statusCell.innerHTML =
                //             status === "active"
                //                 ? `<span class="badge bg-success">Active</span>`
                //                 : `<span class="badge bg-secondary">Inactive</span>`;
                //     }

                //     if (actionCell) {
                //         actionCell.innerHTML =
                //             status === "inactive"
                //                 ? `<button class="btn btn-sm btn-success rounded-pill shadow-sm px-3"
                //             onclick="confirmSwitch(${switchRuleId}, 'active')">
                //             <i class="bi bi-power"></i> Turn On
                //         </button>`
                //                 : `<button class="btn btn-sm btn-danger rounded-pill shadow-sm px-3"
                //             onclick="confirmSwitch(${switchRuleId}, 'inactive')">
                //             <i class="bi bi-power"></i> Turn Off
                //         </button>`;
                //     }

                //     if (buttonsCell) {
                //         let html = `
                //     <button class="btn btn-sm btn-light border" onclick="duplicateRule(${switchRuleId})">📄</button>
                //     <button class="btn btn-sm btn-light border" onclick="editRule(${switchRuleId})">✏️</button>
                // `;
                //         if (status === "inactive") {
                //             html += `<button class="btn btn-sm btn-light border" onclick="confirmSwitch(${switchRuleId}, 'archived')">🗃️</button>`;
                //         }
                //         buttonsCell.innerHTML = html;
                //     }
                    showToast(`Rule has been switched to ${status.toUpperCase()} ✅`);
                        applyFilter();

                }
                switchRuleId = null;
                switchRow = null;

            } catch (err) {
                console.error(err);
                showToast("An error occurred while updating the rule status ❌", true);
            }
        });
        // --- Hàm chuyển sang trang edit rule
        window.editRule = async function (id) {
            const token = await window.AppBridgeUtils.getSessionToken(window.app);
            if (!id) return;
            try {
                const response = await fetch(`/rules/${id}/edit?shop=${SHOP}`, {
                    method: "GET",
                    headers: {
                        "Accept": "text/html",
                        "Authorization": `Bearer ${token}`,
                        "X-CSRF-TOKEN": document.querySelector('meta[name="csrf-token"]').content,
                    },
                });
                if (!response.ok) {
                    alert("Không thể tải trang chỉnh sửa rule!");
                    return;
                }
                const html = await response.text();
                document.open();
                document.write(html);
                document.close();
            } catch (error) {
                console.error(error);
                alert("Lỗi khi tải trang chỉnh sửa rule!");
            }
        };
        // --- Hàm duplicate dữ liệu
        window.duplicateRule = async function (id) {
            if (!id) return;
            const token = await window.AppBridgeUtils.getSessionToken(window.app);
            try {
                const response = await fetch(`/rules/${id}/duplicate`, {
                    method: "POST",
                    headers: {
                        "Accept": "application/json",
                        "Authorization": `Bearer ${token}`,
                        "X-CSRF-TOKEN": document.querySelector('meta[name="csrf-token"]').content,
                    },
                });
                const data = await response.json();
                if (!response.ok) {
                    alert(data.message || "Không thể duplicate rule!");
                    return;
                }
                if (data.status === "success") {
                    showToast("Duplicate rule success");
                    applyFilter(); // refresh danh sách
                } else {
                    alert(data.message || "Đã xảy ra lỗi khi duplicate rule!");
                }
            } catch (error) {
                console.error(error);
                alert("Lỗi kết nối đến server khi duplicate rule!");
            }
        };
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
                // console.log("Batch status:", data);
                if (data.finished) {
                    showToast("✅ Create rule successfully! Go back to My Rules");
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

<!-- Modal Xác nhận Xóa -->
<div class="modal" id="confirmSwitchModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content shadow-lg">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title">CONFIRM SWITCH TO ARCHIVED</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"
                    aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p class="mb-0">
                    Are you sure you want to move this <strong>rule</strong> to the <strong>archived</strong>
                    status?<br>
                    This action <span class="text-danger fw-bold">cannot be undone</span>
                </p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">❌ Cancel</button>
                <button type="button" class="btn btn-danger" id="confirmSwitchBtn">🗃️ Archive</button>
            </div>
        </div>
    </div>
</div>