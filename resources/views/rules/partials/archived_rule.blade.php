{{-- Search + Sort --}}
<div class="d-flex align-items-center gap-2 mb-3">
    <input type="text" id="searchInputArchived" class="form-control w-3xl" placeholder="Search...">
    <button class="btn btn-outline-secondary" id="searchBtnArchived">🔍</button>

    <div class="dropdown">
        <button class="btn btn-outline-secondary dropdown-toggle" type="button" id="sortBtnArchived" data-bs-toggle="dropdown">
            ⇅ Sort
        </button>
        <div class="dropdown-menu p-3" style="min-width: 230px;">
            <p class="fw-bold mb-2">Sort By</p>
            <div class="form-check">
                <input class="form-check-input" type="radio" name="sortFieldArchived" value="sortCreateAt" id="sortCreateAtArchived"
                    checked>
                <label class="form-check-label" for="sortCreateAtArchived">Create Time</label>
            </div>
            <div class="form-check">
                <input class="form-check-input" type="radio" name="sortFieldArchived" value="sortName" id="sortNameArchived">
                <label class="form-check-label" for="sortNameArchived">Rule Name</label>
            </div>
            <div class="form-check mb-2">
                <input class="form-check-input" type="radio" name="sortFieldArchived" value="sortTimeStart" id="sortTimeStartArchived">
                <label class="form-check-label" for="sortTimeStartArchived">Time Start</label>
            </div>
            <hr class="my-2">
            <p class="fw-bold mb-2">Order</p>
            <div class="form-check">
                <input class="form-check-input" type="radio" name="sortOrderArchived" value="orderOld" id="orderOldArchived">
                <label class="form-check-label" for="orderOldArchived">↑ Oldest First</label>
            </div>
            <div class="form-check">
                <input class="form-check-input" type="radio" name="sortOrderArchived" value="orderNew" id="orderNewArchived" checked>
                <label class="form-check-label" for="orderNewArchived">↓ Newest First</label>
            </div>
        </div>
    </div>
</div>
{{-- Table --}}
<div class="table-responsive">
    <table class="table table-hover align-middle">
        <thead class="table-light">
            <tr>
                <th><input type="checkbox" id="selectAll"></th>
                <th>Rule name</th>
                <th>Status</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody id="ruleArchivedTableBody">
            @include('rules.partials.archived_table_body', ['rules' => $rules])
        </tbody>
    </table>
    <div class="pagination-wrapper">
        @include('rules.partials.pagination', ['rules' => $rules])
    </div>
</div>
<script>
    document.addEventListener("DOMContentLoaded", function () {
        const searchBtnArchived = document.getElementById("searchBtnArchived");
        const ruleArchivedTableBody = document.getElementById("ruleArchivedTableBody");
        async function applyFilterArchived(url = "/rules") {
            const filtersArchived = {
                search: document.getElementById("searchInput")?.value || "",
                sort_field: document.querySelector('input[name="sortField"]:checked')?.value || "",
                sort_order: document.querySelector('input[name="sortOrder"]:checked')?.value || "",
            };
            const fetchUrlArchived = new URL(url, window.location.origin);
            fetchUrlArchived.searchParams.set('tab', tabName);
            Object.keys(filtersArchived).forEach(key => {
                if (filters[key]) fetchUrlArchived.searchParams.set(key, filters[key]);
            })
            const token = await window.AppBridgeUtils.getSessionToken(window.app);;
            try {
                const responseArchived = await fetch(fetchUrlArchived, {
                    method: "GET",
                    headers: {
                        "Accept": "application/json",
                        "Authorization": `Bearer ${token}`,
                    },
                });
                if (!responseArchived.ok) throw new Error(`Error ${responseArchived.status}`);

                const dataArchived = await response.json();
                ruleArchivedTableBody.innerHTML = dataArchived.tbody;

                const paginationContainer = document.querySelector(".pagination-wrapper");
                if (paginationContainer && dataArchived.pagination) {
                    paginationContainer.innerHTML = dataArchived.pagination;
                }
                attachPaginationEvents();

            } catch (error) {
                console.error("Filter error:", error);
                ruleArchivedTableBody.innerHTML = `<tr><td colspan="10" class="text-danger text-center">Failed to load data</td></tr>`;
            }
        }
        searchBtnArchived.addEventListener("click", () => applyFilterArchived());
        document.addEventListener("change", function (e) {
            if (e.target.name === "sortFieldArchived" || e.target.name === "sortOrderArchived") {
                applyFilterArchived();
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
        let deleteRuleId = null;
        const deleteModal = new bootstrap.Modal(document.getElementById("confirmDeleteModal"));
        const confirmDeleteBtn = document.getElementById("confirmDeleteBtn");
        // Hàm mở modal xác nhận
        window.confirmDelete = function (id) {
            deleteRuleId = id;
            deleteModal.show();
        };
        confirmDeleteBtn.addEventListener("click", async () => {
            if (!deleteRuleId) return;
            const token = await window.AppBridgeUtils.getSessionToken(window.app);
            try {
                const response = await fetch(`/rules/${deleteRuleId}?shop=${encodeURIComponent(SHOP)}`, {
                    method: "DELETE",
                    headers: {
                        "Accept": "application/json",
                        "Authorization": `Bearer ${token}`,
                        "X-CSRF-TOKEN": document.querySelector('meta[name="csrf-token"]').getAttribute("content"),
                    },
                });

                const data = await response.json();
                if (!response.ok) {
                    showToast(data.error || "Xóa thất bại!", true);
                    return;
                }
                const row = document.querySelector(`button[onclick="confirmDelete(${deleteRuleId})"]`)?.closest("tr");
                if (row) row.remove();
                showToast("Rule đã được xóa thành công ✅");
            } catch (err) {
                console.error(err);
                showToast("Lỗi không mong muốn khi xóa ❌", true);
            } finally {
                deleteModal.hide();
                deleteRuleId = null;
            }
        });
    });
</script>
<!-- Modal Xác nhận Xóa -->
<div class="modal fade" id="confirmDeleteModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content shadow-lg">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title">CONFIRM DELETE</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"
                    aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p class="mb-0">Are you sure you want to delete this<strong>rule</strong>?<br>
                    This action <span class="text-danger fw-bold">cannot be undone</span>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">❌ Cancel</button>
                <button type="button" class="btn btn-danger" id="confirmDeleteBtn">🗑️ Delete</button>
            </div>
        </div>
    </div>
</div>