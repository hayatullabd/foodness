@extends('layouts.admin')

@section('title', 'Supplier Management')
@section('heading', 'Supplier Management')
@section('subheading', 'Manage your supplier list and track dues.')

@section('content')
    <div class="grid gap-3 lg:grid-cols-12">
        <div class="admin-card admin-card-body lg:col-span-4">
            <h2 class="mb-2.5 flex items-center gap-2 admin-section-title">
                <span class="inline-flex h-5 w-5 items-center justify-center rounded-full bg-sage/15 text-xs text-sage">+</span>
                {{ $editing ? 'Edit Supplier' : 'Add New Supplier' }}
            </h2>

            <form
                method="POST"
                action="{{ $editing ? route('admin.suppliers.update', $editing) : route('admin.suppliers.store') }}"
                class="space-y-4"
            >
                @csrf
                @if ($editing)
                    @method('PUT')
                @endif

                <div>
                    <label class="mb-1 block text-xs font-medium uppercase tracking-wide text-muted" for="name">Full Name *</label>
                    <input id="name" type="text" name="name" value="{{ old('name', $editing?->name) }}" placeholder="e.g. John Doe" class="field" required>
                    @error('name') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="mb-1 block text-xs font-medium uppercase tracking-wide text-muted" for="phone">Phone Number</label>
                    <input id="phone" type="text" name="phone" value="{{ old('phone', $editing?->phone) }}" placeholder="e.g. 017xxxxxxxxx" class="field">
                    @error('phone') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="mb-1 block text-xs font-medium uppercase tracking-wide text-muted" for="email">Email Address</label>
                    <input id="email" type="email" name="email" value="{{ old('email', $editing?->email) }}" placeholder="supplier@example.com" class="field">
                    @error('email') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="mb-1 block text-xs font-medium uppercase tracking-wide text-muted" for="address">Address</label>
                    <textarea id="address" name="address" rows="3" placeholder="Full address here..." class="field">{{ old('address', $editing?->address) }}</textarea>
                    @error('address') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>

                <div class="flex gap-2">
                    <button type="submit" class="btn-primary w-full">{{ $editing ? 'Update Supplier' : 'Save Supplier' }}</button>
                    @if ($editing)
                        <a href="{{ route('admin.suppliers.index') }}" class="admin-btn-secondary">Cancel</a>
                    @endif
                </div>
            </form>
        </div>

        <div class="border border-ink/10 bg-white lg:col-span-8">
            <div class="flex items-center justify-between border-b border-ink/10 px-3 py-2">
                <h2 class="admin-section-title">Registered Suppliers</h2>
                <span class="rounded-full bg-mist px-2.5 py-0.5 text-xs font-medium text-ink">{{ $suppliers->count() }} Found</span>
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-full text-left text-sm">
                    <thead class="bg-mist text-muted">
                        <tr>
                            <th class="px-3 py-2 font-medium">#</th>
                            <th class="px-3 py-2 font-medium">Supplier Info</th>
                            <th class="px-3 py-2 font-medium">Contact</th>
                            <th class="px-3 py-2 font-medium">Address</th>
                            <th class="px-3 py-2 font-medium">Due Amount</th>
                            <th class="px-3 py-2 font-medium">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($suppliers as $index => $supplier)
                            @php $due = (float) ($supplier->due_amount ?? 0); @endphp
                            <tr class="border-t border-ink/5">
                                <td class="px-3 py-2 text-muted">{{ $index + 1 }}</td>
                                <td class="px-3 py-2">
                                    <div class="font-medium text-ink">{{ $supplier->name }}</div>
                                    <div class="text-xs text-muted">{{ $supplier->phone ?: '—' }}</div>
                                </td>
                                <td class="px-3 py-2 text-muted">{{ $supplier->email ?: '—' }}</td>
                                <td class="px-3 py-2 text-muted">{{ $supplier->address ?: '—' }}</td>
                                <td class="px-3 py-2">
                                    @if ($due > 0)
                                        <span class="font-medium text-red-600">{{ number_format($due, 2) }} ৳</span>
                                    @else
                                        <span class="inline-flex rounded-full bg-emerald-100 px-2 py-0.5 text-xs font-medium text-emerald-800">Paid</span>
                                    @endif
                                </td>
                                <td class="px-3 py-2">
                                    <div class="flex items-center gap-2">
                                        <a href="{{ route('admin.suppliers.edit', $supplier) }}" class="rounded-md bg-sage/15 px-2 py-1 text-xs font-medium text-sage hover:bg-sage/25">Edit</a>
                                        <form method="POST" action="{{ route('admin.suppliers.destroy', $supplier) }}" onsubmit="return confirm('Delete this supplier?')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="rounded-md bg-red-50 px-2 py-1 text-xs font-medium text-red-700 hover:bg-red-100">Delete</button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-4 py-8 text-center text-muted">No suppliers registered yet.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection
