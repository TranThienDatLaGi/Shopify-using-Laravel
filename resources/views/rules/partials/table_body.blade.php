@forelse ($rules as $rule)
    <tr data-rule-id="{{ $rule->id }}">
        <td><input type="checkbox" value="{{ $rule->id }}"></td>
        <td class="fw-bold">{{ $rule->name }}</td>
        <td>
            <small>
                • Based on: <b>{{ ucfirst($rule->based_on) }}</b><br>
                • Discount: {{ $rule->discount_value }}
                {{ $rule->discount_type === 'percent' ? '%' : 'USD' }}<br>
                • Applies to: <b>{{ count($rule->applies_to_value) }}
                    {{ ucfirst(str_replace('_', ' ', $rule->applies_to)) }}</b><br>
                • Start: {{ optional($rule->start_at)->format('h:i a d M Y') ?? '-' }}<br>
                • End: {{ optional($rule->end_at)->format('h:i a d M Y') ?? '-' }}
            </small>
        </td>
        <td>
            @php
                $now = now('Asia/Ho_Chi_Minh');
                $start = \Carbon\Carbon::parse($rule->start_at)->setTimezone('Asia/Ho_Chi_Minh');
                $end = \Carbon\Carbon::parse($rule->end_at)->setTimezone('Asia/Ho_Chi_Minh');
            @endphp

            @if ($rule->status === 'pending')
                <span class="badge bg-warning text-dark">
                    
                </span>
            @if ($rule->status === 'inactive')
                <span class="badge bg-secondary">Inactive</span>
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
            @if ($rule->status === 'inactive')
                <button class="btn btn-sm btn-success rounded-pill shadow-sm px-3" onclick="confirmSwitch({{ $rule->id }}, 'active')">
                    <i class="bi bi-power"></i> Turn On
                </button>
            @else
                <button class="btn btn-sm btn-danger rounded-pill shadow-sm px-3" onclick="confirmSwitch({{ $rule->id }}, 'inactive')">
                    <i class="bi bi-power"></i> Turn Off
                </button>
            @endif
        </td>
        <td>
            <div class="btn-group">
                <button class="btn btn-sm btn-light border" onclick="duplicateRule({{ $rule->id }})">📄</button>
                <button class="btn btn-sm btn-light border" onclick="editRule({{ $rule->id }})">✏️</button>
                @if ($rule->status === 'inactive')
                    <button class="btn btn-sm btn-light border" onclick="confirmSwitch({{ $rule->id }},'archived')"> 🗃️ </button>
                @endif
            </div>
        </td>
    </tr>
@empty
    <tr>
        <td colspan="6" class="text-center text-muted py-3">No rules found.</td>
    </tr>
@endforelse