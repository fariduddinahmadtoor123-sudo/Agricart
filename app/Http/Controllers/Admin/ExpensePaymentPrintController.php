<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CompanyProfile;
use App\Models\ExpensePayment;
use App\Models\PrintProfile;
use App\Models\User;
use App\Services\Printing\PrintProfileService;
use Illuminate\Contracts\View\View;

class ExpensePaymentPrintController extends Controller
{
    public function show(ExpensePayment $payment): View
    {
        $this->authorizeFinanceAccess();

        $payment->loadMissing(['expense.category', 'bankAccount', 'createdBy']);

        $context = $this->printContext();

        return view('prints.expense-payment', array_merge($context, [
            'payment' => $payment,
            'walletLabel' => (string) __('agricart.finance.expense_payments.wallets.'.$payment->source_wallet),
            'bankLabel' => $payment->bankAccount?->displayName() ?? '—',
            'expenseLabel' => $payment->expense?->displayName() ?? '—',
            'money' => fn (float $amount): string => number_format($amount, 2, '.', ''),
            'receiptUrl' => $payment->bankReceiptUrl(),
        ]));
    }

    /**
     * @return array<string, mixed>
     */
    private function printContext(): array
    {
        $company = CompanyProfile::current();
        $localeQuery = request()->query('locale');
        $locale = is_string($localeQuery) && in_array($localeQuery, ['en', 'ur'], true)
            ? $localeQuery
            : app()->getLocale();
        app()->setLocale($locale);

        $paper = request()->query('paper', 'a4');
        if (! in_array($paper, ['a4', 'receipt'], true)) {
            $paper = 'a4';
        }

        $receiptProfile = app(PrintProfileService::class)
            ->defaultForCategory(PrintProfile::CATEGORY_RECEIPT);

        return [
            'company' => $company,
            'storeName' => (string) ($company->localized('business_name') ?? config('app.name')),
            'storePhone' => (string) (($company->primaryPhone() ?? [])['phone_number'] ?? ''),
            'storeAddress' => $company->formattedPrimaryAddress() ?? '',
            'storeLogoUrl' => $company->displayLogoUrl(),
            'locale' => $locale,
            'paper' => $paper,
            'receiptProfile' => $receiptProfile,
            'autoprint' => request()->query('autoprint', '1') !== '0',
        ];
    }

    private function authorizeFinanceAccess(): void
    {
        $user = auth()->user();

        abort_unless(
            $user instanceof User
                && ($user->isSuperAdmin()
                    || \App\Services\Access\ModulePageAccess::can($user, 'finance', 'expense-payments', 'view')),
            403,
        );
    }
}
