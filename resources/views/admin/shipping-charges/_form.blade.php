@php($shippingCharge = $shippingCharge ?? null)

<div>
    <label class="admin-label" for="name">Name</label>
    <input id="name" name="name" type="text" value="{{ old('name', $shippingCharge?->name) }}" required class="field">
    @error('name') <p class="mt-1 text-sm text-accent">{{ $message }}</p> @enderror
</div>
<div>
    <label class="admin-label" for="amount">Amount (BDT)</label>
    <input id="amount" name="amount" type="number" step="0.01" min="0" value="{{ old('amount', $shippingCharge?->amount ?? 0) }}" required class="field">
    @error('amount') <p class="mt-1 text-sm text-accent">{{ $message }}</p> @enderror
</div>
<label class="flex items-center gap-2 text-sm">
    <input type="checkbox" name="is_active" value="1" @checked(old('is_active', $shippingCharge?->is_active ?? true))>
    Active
</label>
