<?php

namespace App\Http\Controllers;

use App\Models\CreditNote;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;

class CreditNoteController extends Controller
{
    public function index()
    {
        if (!auth()->user()?->hasAnyRole(['super_admin', 'admin', 'finance', 'director', 'manager', 'accounting', 'audit'])) {
            abort(403);
        }

        $creditNotes = CreditNote::with(['invoice', 'customer', 'workOrder'])
            ->latest()
            ->get();

        return view('credit_notes.index', compact('creditNotes'));
    }

    public function show(CreditNote $creditNote)
    {
        if (!auth()->user()?->hasAnyRole(['super_admin', 'admin', 'finance', 'director', 'manager', 'accounting', 'audit'])) {
            abort(403);
        }

        $creditNote->load(['invoice', 'customer', 'workOrder']);

        return view('credit_notes.show', compact('creditNote'));
    }

    public function print(CreditNote $creditNote)
    {
        if (!auth()->user()?->hasAnyRole(['super_admin', 'admin', 'finance', 'director', 'manager', 'accounting', 'audit'])) {
            abort(403);
        }

        $creditNote->load(['invoice', 'customer', 'workOrder.proformaInvoice.discountLines', 'workOrder.items.item.smallestUom', 'workOrder.labors']);

        return view('credit_notes.print', compact('creditNote'));
    }
}
