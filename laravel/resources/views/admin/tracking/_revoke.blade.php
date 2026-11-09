@php
    /** @var string $action */
    /** @var string $confirm */
    /** @var string|null $label */
    /** @var string $scope */
    /** @var array<int, string> $fields */
    $label = $label ?? 'Revoke';
    $scope = $scope ?? 'fields';
    $fields = $fields ?? [];
    $confirm = $confirm ?? 'Revoke this tracking credential? This cannot be undone.';
    $buttonClass = $buttonClass ?? 'admin-btn-danger !px-2.5 !py-1 !text-[11px]';
    $deactivate = ! empty($deactivate);
@endphp

<button
    type="button"
    class="{{ $buttonClass }} cursor-pointer"
    data-tracking-revoke
    data-action="{{ $action }}"
    data-scope="{{ $scope }}"
    data-fields='@json($fields)'
    data-deactivate="{{ $deactivate ? '1' : '0' }}"
    data-confirm="{{ $confirm }}"
>
    {{ $label }}
</button>
