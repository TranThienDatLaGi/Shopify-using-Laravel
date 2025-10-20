@forelse ($rules as $rule)
    <tr>
        <td><input type="checkbox" value="{{ $rule->id }}"></td>
        <td class="fw-bold">{{ $rule->name }}</td>
        <td>
            @php
    $now = now();
    $start = \Carbon\Carbon::parse($rule->start_time);
    $end = \Carbon\Carbon::parse($rule->end_time);
            @endphp

            @if ($rule->status === 'stopped')
                <span class="badge bg-warning text-dark">
                    Stopped at {{ \Carbon\Carbon::parse($rule->updated_at)->format('H:i d/m/Y') }}
                </span>

            @elseif ($rule->status === 'inactive')
                <span class="badge bg-secondary">
                    Inactive
                </span>
            @elseif ($rule->status === 'archived')
                <span class="badge bg-dark">
                    Archived
                </span>
            @else
                @if ($now->between($start, $end))
                    <span class="badge bg-success">
                        Stop at {{ $end->format('H:i d/m/Y') }}
                    </span>
                @elseif ($now->lt($start))
                    <span class="badge bg-warning text-dark">
                        Activate at {{ $start->format('H:i d/m/Y') }}
                    </span>
                @else
                    <span class="badge bg-secondary">
                        Expired — finished at {{ $end->format('H:i d/m/Y') }}
                    </span>
                @endif
            @endif
        </td>
        <td>
            <div class="btn-group">
                <button class="btn btn-sm btn-light border" onclick="confirmDelete({{ $rule->id }})">🗑️</button>
                <button class="btn btn-sm btn-light border" onclick="confirmSwitch({{ $rule->id }},'inactive')"> 🔄 </button>
            </div>
        </td>
    </tr>
@empty
    <tr>
        <td colspan="6" class="text-center text-muted py-3">No rules found.</td>
    </tr>
@endforelse