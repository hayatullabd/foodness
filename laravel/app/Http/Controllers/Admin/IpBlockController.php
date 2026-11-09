<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\IpBlock;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class IpBlockController extends Controller
{
    public function index(): View
    {
        $ipBlocks = IpBlock::query()
            ->latest()
            ->paginate(25);

        return view('admin.ip-blocks.index', [
            'ipBlocks' => $ipBlocks,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'ip' => ['required', 'ip', Rule::unique('ip_blocks', 'ip')],
            'reason' => ['required', 'string', 'max:500'],
        ]);

        IpBlock::query()->create($validated);

        return redirect()
            ->route('admin.ip-blocks.index')
            ->with('success', 'IP blocked.');
    }

    public function destroy(IpBlock $ipBlock): RedirectResponse
    {
        $ipBlock->delete();

        return redirect()
            ->route('admin.ip-blocks.index')
            ->with('success', 'IP unblocked.');
    }
}
