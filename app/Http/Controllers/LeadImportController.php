<?php

namespace App\Http\Controllers;

use App\Actions\ImportLeads;
use App\Http\Requests\ImportLeadsRequest;
use Illuminate\Database\QueryException;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class LeadImportController extends Controller
{
    public function index(): View
    {
        return view('lead-import');
    }

    public function store(ImportLeadsRequest $request, ImportLeads $import): RedirectResponse
    {
        try {
            $result = $import->handle($request->file('file')->path());
        } catch (QueryException $exception) {
            report($exception);

            return to_route('home')->withErrors(['file' => 'Не вдалося записати дані. Імпорт скасовано, спробуйте ще раз.']);
        }

        return to_route('home')->with('import', [
            ...$result,
            'filename' => $request->file('file')->getClientOriginalName(),
        ]);
    }
}
