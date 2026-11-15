<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\TrialRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/** Superadmin: richieste di prova arrivate dal sito. */
class RichiesteController extends Controller
{
    public function index(): View
    {
        return view('admin.richieste', [
            'richieste' => TrialRequest::with('hotelCreato')->latest()->paginate(50),
            'stati' => TrialRequest::STATI,
        ]);
    }

    public function update(Request $request, TrialRequest $richiesta): RedirectResponse
    {
        $richiesta->update($request->validate(['stato' => ['required', Rule::in(array_keys(TrialRequest::STATI))]]));

        return back()->with('ok', "Richiesta di {$richiesta->hotel}: ".TrialRequest::STATI[$richiesta->stato].'.');
    }
}
