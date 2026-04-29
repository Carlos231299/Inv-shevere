<?php

namespace App\Http\Controllers;

use App\Models\Client;
use Illuminate\Http\Request;

class ClientController extends Controller
{
    public function index(Request $request)
    {
        $query = Client::query();
        if ($request->has('search')) {
            $query->where('name', 'like', "%{$request->search}%")
                  ->orWhere('document', 'like', "%{$request->search}%");
        }
        $clients = $query->paginate(10);
        return view('clients.index', compact('clients'));
    }

    public function create()
    {
        if (request()->ajax()) {
            return view('clients.form');
        }
        return view('clients.create');
    }

    public function store(Request $request)
    {
        $request->validate(['name' => 'required']);
        $client = Client::create($request->all());

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json(['message' => 'Cliente creado exitosamente.', 'client' => $client], 201);
        }

        return redirect()->route('clients.index')->with('success', 'Cliente creado.');
    }
    
    // API
    public function apiSearch(Request $request)
    {
        $search = $request->get('q');
        return Client::where('name', 'like', "%{$search}%")
                     ->orWhere('document', 'like', "%{$search}%")
                     ->limit(10)
                     ->get();
    }
    
    public function edit($id)
    {
        $client = Client::findOrFail($id);
        if (request()->ajax()) {
            return view('clients.form', compact('client'));
        }
        return view('clients.edit', compact('client'));
    }

    public function update(Request $request, $id)
    {
        $request->validate(['name' => 'required']);
        $client = Client::findOrFail($id);
        $client->update($request->all());

        if ($request->ajax()) {
            return response()->json(['message' => 'Cliente actualizado exitosamente.', 'client' => $client], 200);
        }

        return redirect()->route('clients.index')->with('success', 'Cliente actualizado.');
    }

    public function destroy($id)
    {
        $client = Client::findOrFail($id);
        
        // Optional: Check if client has debts before deleting? 
        // For now, allow delete.
        $client->delete();

        return redirect()->route('clients.index')->with('success', 'Cliente eliminado.');
    }
}
