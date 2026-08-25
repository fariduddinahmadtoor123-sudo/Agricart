<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CompanyProfile;
use App\Models\FundTransfer;
use App\Models\PrintProfile;
use App\Models\User;
use App\Services\Finance\FundTransferService;
use App\Services\Printing\PrintProfileService;
use Illuminate\Contracts\View\View;

class FundTransferPrintController extends Controller
{
    public function show(FundTransfer $transfer): View
    {
        $this->authorizeFinanceAccess();

        $transfer->loadMissing([
            'sourceBankAccount',
            'destinationBankAccount',
            'destinationSupplier',
            'destinationCustomer',
            'destinationPurchaser',
            'destinationExpense',
            'createdBy',
        ]);

        $context = $this->printContext();
        $service = app(FundTransferService::class);

        return view('prints.fund-transfer', array_merge($context, [
            'transfer' => $transfer,
            'typeLabel' => (string) (__('agricart.finance.fund_transfers.types.'.$transfer->transaction_type) ?: $transfer->transaction_type),
            'sourceLabel' => $this->sourceLabel($transfer),
            'destinationLabel' => $this->destinationLabel($transfer),
            'money' => fn (float $amount): string => $service->formatMoney($amount),
            'receiptUrl' => $transfer->bankReceiptUrl(),
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
                    || \App\Services\Access\ModulePageAccess::can($user, 'finance', 'cash-bank-transfers', 'view')),
            403,
        );
    }

    private function sourceLabel(FundTransfer $transfer): string
    {
        if ($transfer->transaction_type === FundTransfer::TYPE_EXTERNAL_DEPOSIT) {
            return __('agricart.finance.fund_transfers.source_wallets.external');
        }

        if ($transfer->transaction_type === FundTransfer::TYPE_CUSTOMER_RECEIPT) {
            return $transfer->source_wallet === FundTransfer::WALLET_CASH
                ? __('agricart.finance.fund_transfers.source_wallets.cash')
                : ($transfer->sourceBankAccount?->displayName() ?? '—');
        }

        if ($transfer->transaction_type === FundTransfer::TYPE_DEPOSIT || $transfer->source_wallet === FundTransfer::WALLET_CASH) {
            return __('agricart.finance.fund_transfers.source_wallets.cash');
        }

        return $transfer->sourceBankAccount?->displayName() ?? '—';
    }

    private function destinationLabel(FundTransfer $transfer): string
    {
        return match ($transfer->transaction_type) {
            FundTransfer::TYPE_DEPOSIT, FundTransfer::TYPE_EXTERNAL_DEPOSIT, FundTransfer::TYPE_TRANSFER => $transfer->destinationBankAccount?->displayName() ?? '—',
            FundTransfer::TYPE_WITHDRAWAL => __('agricart.finance.fund_transfers.source_wallets.cash'),
            FundTransfer::TYPE_EXTERNAL_WITHDRAWAL => __('agricart.finance.fund_transfers.source_wallets.external'),
            FundTransfer::TYPE_VENDOR_PAYMENT => $transfer->destinationSupplier?->displayBusinessName() ?? '—',
            FundTransfer::TYPE_CUSTOMER_RECEIPT => $transfer->destinationCustomer?->displayName() ?? '—',
            FundTransfer::TYPE_EXPENSE_PAYMENT => $transfer->destinationExpense?->displayName()
                ?? (string) ($transfer->destinationPurchaser?->name ?? '—'),
            FundTransfer::TYPE_PURCHASER_PAYMENT => (string) ($transfer->destinationPurchaser?->name ?? '—'),
            default => '—',
        };
    }
}
