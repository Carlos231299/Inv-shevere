<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Models\Provider;

class ProviderController extends Controller
{
    // --- WEB METHODS ---

    /**
     * Display a listing of the resource (Web).
     */
    public function index(Request $request)
    {
        $query = Provider::query();

        if ($request->has('search')) {
            $search = $request->get('search');
            $query->where('name', 'like', "%{$search}%")
                  ->orWhere('nit', 'like', "%{$search}%")
                  ->orWhere('contact_name', 'like', "%{$search}%");
        }

        $providers = $query->orderBy('name')->paginate(10);

        return view('providers.index', compact('providers'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        if (request()->ajax()) {
            return view('providers.form');
        }
        return view('providers.create');
    }

    /**
     * Store a newly created resource in storage (Web).
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'nit' => 'nullable|string|max:50',
            'phone' => 'nullable|string|max:50',
            'contact_name' => 'nullable|string|max:255',
            'email' => 'nullable|email|max:255',
            'address' => 'nullable|string|max:255',
        ]);

        $provider = Provider::create($request->all());

        if ($request->ajax()) {
             return response()->json(['message' => 'Proveedor creado exitosamente.', 'provider' => $provider], 201);
        }

        return redirect()->route('providers.index')
                         ->with('success', 'Proveedor creado exitosamente.');
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Provider $provider)
    {
        if (request()->ajax()) {
            return view('providers.form', compact('provider'));
        }
        return view('providers.edit', compact('provider'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Provider $provider)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'nit' => 'nullable|string|max:50',
            'phone' => 'nullable|string|max:50',
            'contact_name' => 'nullable|string|max:255',
            'email' => 'nullable|email|max:255',
            'address' => 'nullable|string|max:255',
        ]);

        $provider->update($request->all());

        if ($request->ajax()) {
             return response()->json(['message' => 'Proveedor actualizado exitosamente.', 'provider' => $provider], 200);
        }

        return redirect()->route('providers.index')
                         ->with('success', 'Proveedor actualizado exitosamente.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Provider $provider)
    {
        // Optional: Check if provider has purchases before deleting?
        // For now, allow delete.
        $provider->delete();

        return redirect()->route('providers.index')
                         ->with('success', 'Proveedor eliminado.');
    }

    // --- API METHODS (For React Components) ---

    public function apiIndex()
    {
        return response()->json(Provider::orderBy('name')->get());
    }

    public function apiStore(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'nit' => 'nullable|string|max:50',
            'phone' => 'nullable|string|max:50',
            'address' => 'nullable|string|max:255',
        ]);

        $provider = Provider::create($request->all());

        return response()->json($provider, 201);
    }
}
