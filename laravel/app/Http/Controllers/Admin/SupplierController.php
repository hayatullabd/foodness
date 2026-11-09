<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Supplier;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SupplierController extends Controller
{
    public function index(): View
    {
        $suppliers = Supplier::query()
            ->withSum('purchases as due_amount', 'due')
            ->orderBy('name')
            ->get();

        return view('admin.suppliers.index', [
            'suppliers' => $suppliers,
            'editing' => null,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $this->validated($request);

        Supplier::query()->create($validated);

        return redirect()
            ->route('admin.suppliers.index')
            ->with('success', 'Supplier saved successfully.');
    }

    public function edit(Supplier $supplier): View
    {
        $suppliers = Supplier::query()
            ->withSum('purchases as due_amount', 'due')
            ->orderBy('name')
            ->get();

        return view('admin.suppliers.index', [
            'suppliers' => $suppliers,
            'editing' => $supplier,
        ]);
    }

    public function update(Request $request, Supplier $supplier): RedirectResponse
    {
        $validated = $this->validated($request);

        $supplier->update($validated);

        return redirect()
            ->route('admin.suppliers.index')
            ->with('success', 'Supplier updated successfully.');
    }

    public function destroy(Supplier $supplier): RedirectResponse
    {
        if ($supplier->purchases()->exists()) {
            return back()->with('error', 'Cannot delete a supplier that has purchases.');
        }

        $supplier->delete();

        return redirect()
            ->route('admin.suppliers.index')
            ->with('success', 'Supplier deleted successfully.');
    }

    /**
     * @return array{name: string, phone: ?string, email: ?string, address: ?string}
     */
    private function validated(Request $request): array
    {
        /** @var array{name: string, phone: ?string, email: ?string, address: ?string} $validated */
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'email' => ['nullable', 'email', 'max:255'],
            'address' => ['nullable', 'string', 'max:1000'],
        ]);

        return $validated;
    }
}
