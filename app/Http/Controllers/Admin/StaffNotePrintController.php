<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CompanyProfile;
use App\Models\PrintProfile;
use App\Models\StaffNote;
use App\Models\User;
use App\Services\Printing\PrintProfileService;
use App\Services\Staff\StaffNoteService;
use Illuminate\Contracts\View\View;

class StaffNotePrintController extends Controller
{
    public function show(StaffNote $note, StaffNoteService $notes): View
    {
        $user = auth()->user();
        abort_unless($user instanceof User && $user->canUseAdmin(), 403);
        abort_unless($notes->canView($user, $note), 403);

        $note->loadMissing(['creator', 'assignee', 'completedByUser']);

        $localeQuery = request()->query('locale');
        $locale = is_string($localeQuery) && in_array($localeQuery, ['en', 'ur'], true)
            ? $localeQuery
            : app()->getLocale();
        if (! in_array($locale, ['en', 'ur'], true)) {
            $locale = 'en';
        }
        app()->setLocale($locale);

        $paper = request()->query('paper', 'a4');
        if (! in_array($paper, ['a4', 'receipt'], true)) {
            $paper = 'a4';
        }

        $company = CompanyProfile::current();
        $receiptProfile = app(PrintProfileService::class)
            ->defaultForCategory(PrintProfile::CATEGORY_RECEIPT);

        return view('prints.staff-note', [
            'note' => $note,
            'locale' => $locale,
            'paper' => $paper,
            'company' => $company,
            'storeName' => (string) ($company->localized('business_name') ?? config('app.name')),
            'storePhone' => (string) (($company->primaryPhone() ?? [])['phone_number'] ?? ''),
            'storeAddress' => $company->formattedPrimaryAddress() ?? '',
            'storeLogoUrl' => $company->displayLogoUrl(),
            'receiptProfile' => $receiptProfile,
            'autoprint' => request()->query('autoprint', '1') !== '0',
        ]);
    }
}
