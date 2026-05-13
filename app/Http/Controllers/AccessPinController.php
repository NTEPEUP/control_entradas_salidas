<?php

namespace App\Http\Controllers;

use App\Models\AccessPin;
use Illuminate\Http\Request;

class AccessPinController extends Controller
{
    public function index()
    {
        $pins = AccessPin::latest()->get();

        return view('access-pins.index', compact('pins'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'pin' => 'required|string|size:4|regex:/^[0-9]+$/|unique:access_pins,pin',
            'owner_name' => 'required|string|max:255',
            'is_active' => 'nullable|boolean',
        ]);

        AccessPin::create([
            'pin' => $validated['pin'],
            'owner_name' => $validated['owner_name'],
            'is_active' => $request->boolean('is_active', true),
        ]);

        return redirect()
            ->route('access-pins.index')
            ->with('status', 'PIN creado correctamente.');
    }

    public function toggle(AccessPin $accessPin)
    {
        $accessPin->update([
            'is_active' => ! $accessPin->is_active,
        ]);

        return redirect()
            ->route('access-pins.index')
            ->with('status', 'Estado del PIN actualizado.');
    }
}
