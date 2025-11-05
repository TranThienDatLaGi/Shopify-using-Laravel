@if ($rules->hasPages())
    <div class="d-flex justify-content-center mt-3 pagination-wrapper">
        {!! $rules->appends(request()->query())->links('pagination::bootstrap-5') !!}
    </div>
@endif