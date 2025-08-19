<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\EmployeeDocument;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class DocumentController extends Controller
{
    // All documents view
    public function index()
    {
        $documents = EmployeeDocument::with('employee')->get();
        return response()->json($documents);
    }

    // certain document view
    public function show($id)
    {
        $document = EmployeeDocument::with('employee')->findOrFail($id);
        return response()->json($document);
    }

    // new document create and save
    public function store(Request $request)
    {
        $request->validate([
            'employee_id' => 'required|exists:employees,id',
            'type' => 'required|string',
            'file' => 'required|file|max:5120', // max 5MB
        ]);

        // ফাইল আপলোড
        $path = $request->file('file')->store('documents', 'public');

        $document = EmployeeDocument::create([
            'employee_id' => $request->employee_id,
            'type' => $request->type,
            'file_path' => $path,
        ]);

        return response()->json($document, 201);
    }

    // document update
    public function update(Request $request, $id)
    {
        $document = EmployeeDocument::findOrFail($id);

        $request->validate([
            'type' => 'sometimes|string',
            'file' => 'sometimes|file|max:5120',
        ]);

        if ($request->has('type')) {
            $document->type = $request->type;
        }

        if ($request->hasFile('file')) {
            // delete old file
            if ($document->file_path && Storage::exists($document->file_path)) {
                Storage::delete($document->file_path);
            }
            // add new file(s)
            $document->file_path = $request->file('file')->store('employee_documents');
        }

        $document->save();

        return response()->json($document);
    }

    // document delete (include files)
    public function destroy($id)
    {
        $document = EmployeeDocument::findOrFail($id);

        if ($document->file_path && Storage::exists($document->file_path)) {
            Storage::delete($document->file_path);
        }

        $document->delete();

        return response()->json(['message' => 'Document deleted successfully']);
    }
}
