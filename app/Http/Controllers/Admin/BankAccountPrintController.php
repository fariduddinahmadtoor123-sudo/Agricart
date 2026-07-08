<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BankAccount;
use App\Models\BankAccountTransaction;
use App\Models\CompanyProfile;
use App\Models\PrintProfile;
use App\Models\User;
use App\Services\Finance\BankAccountService;
use App\Services\Printing\PrintProfileService;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Carbon;

class BankAccountPrintController extends Controller
{
    public function details(BankAccount $account): View
    {
        $this->authorizeFinanceAccess();

        $context = $this->printContext();

        return view('prints.bank-account-details', array_merge($context, [
            'account' => $account,
            'identifierTypeLabel' => $this->identifierTypeLabel($account),
            'bankName' => $this->localizedBankName($account, $context['locale']),
            'accountTitle' => $this->localizedAccountTitle($account, $context['locale']),
        ]));
    }

    public function summary(BankAccount $account): View
    {
        $this->authorizeFinanceAccess();

        $context = $this->printContext();
        $service = app(BankAccountService::class);

        return view('prints.bank-account-summary', array_merge($context, [
            'account' => $account,
            'balance' => $service->balance($account),
            'money' => fn (float $amount): string => $service->formatMoney($amount),
            'identifierTypeLabel' => $this->identifierTypeLabel($account),
            'bankName' => $this->localizedBankName($account, $context['locale']),
            'accountTitle' => $this->localizedAccountTitle($account, $context['locale']),
        ]));
    }

    public function history(BankAccount $account): View
    {
        $this->authorizeFinanceAccess();

        $company = CompanyProfile::current();
        $service = app(BankAccountService::class);

        $from = $this->parseDateQuery('from');
        $to = $this->parseDateQuery('to');

        $query = BankAccountTransaction::query()
            ->where('bank_account_id', $account->id)
            ->with(['counterpartyBankAccount', 'supplier', 'sale'])
            ->orderByDesc('transaction_date')
            ->orderByDesc('id');

        if ($from !== null) {
            $query->whereDate('transaction_date', '>=', $from);
        }

        if ($to !== null) {
            $query->whereDate('transaction_date', '<=', $to);
        }

        $transactions = $query->limit(500)->get();

        return view('prints.bank-account-history', [
            'account' => $account,
            'company' => $company,
            'transactions' => $transactions,
            'money' => fn (float $amount): string => $service->formatMoney($amount),
            'from' => $from,
            'to' => $to,
            'autoprint' => request()->query('autoprint', '1') !== '0',
        ]);
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
                && ($user->isSuperAdmin() || $user->hasAnyPermission(['finance.create', 'finance.edit'])),
            403,
        );
    }

    private function identifierTypeLabel(BankAccount $account): string
    {
        $type = in_array($account->account_identifier_type, BankAccount::IDENTIFIER_TYPES, true)
            ? (string) $account->account_identifier_type
            : BankAccount::IDENTIFIER_ACCOUNT_NUMBER;

        return (string) __('agricart.finance.banks.identifier_types.' . $type);
    }

    private function localizedBankName(BankAccount $account, string $locale): string
    {
        if ($locale === 'ur' && filled($account->bank_name_ur)) {
            return (string) $account->bank_name_ur;
        }

        if (filled($account->bank_name_en)) {
            return (string) $account->bank_name_en;
        }

        return $account->displayBankName() ?: '—';
    }

    private function localizedAccountTitle(BankAccount $account, string $locale): string
    {
        if ($locale === 'ur' && filled($account->name_ur)) {
            return (string) $account->name_ur;
        }

        if (filled($account->account_title)) {
            return (string) $account->account_title;
        }

        return (string) ($account->name_en ?: '—');
    }

    private function parseDateQuery(string $key): ?Carbon
    {
        $value = request()->query($key);

        if (! is_string($value) || ! filled($value)) {
            return null;
        }

        try {
            return Carbon::parse($value)->startOfDay();
        } catch (\Throwable) {
            return null;
        }
    }
}
