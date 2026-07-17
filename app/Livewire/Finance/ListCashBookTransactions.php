<?php

namespace App\Livewire\Finance;

use App\Models\CashBookTransaction;
use App\Services\Finance\CashBookService;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\WithPagination;

class ListCashBookTransactions extends Component
{
    use WithPagination;

    public string $search = '';

    #[On('finance-cash-book-search')]
    public function updateSearch(string $query): void
    {
        $this->search = $query;
        $this->resetPage();
    }

    public function render(): View
    {
        $service = app(CashBookService::class);

        $transactions = CashBookTransaction::query()
            ->with(['createdBy', 'sale', 'saleReturn', 'fundTransfer', 'expense', 'expensePayment'])
            ->when(filled($this->search), function ($query): void {
                $needle = '%' . $this->search . '%';
                $query->where(function ($inner) use ($needle): void {
                    $inner->where('description', 'like', $needle)
                        ->orWhere('notes', 'like', $needle);
                });
            })
            ->orderByDesc('transaction_date')
            ->orderByDesc('id')
            ->paginate(25);

        return view('livewire.finance.cash-book.list', [
            'transactions' => $transactions,
            'balance' => $service->balance(),
            'money' => fn (float $amount): string => number_format($amount, 2) . ' PKR',
        ]);
    }
}
