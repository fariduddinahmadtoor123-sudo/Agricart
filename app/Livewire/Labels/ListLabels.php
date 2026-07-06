<?php

namespace App\Livewire\Labels;

use App\Models\Product;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\WithPagination;

class ListLabels extends Component
{
    use WithPagination;

    public string $search = '';

    #[On('labels-saved')]
    #[On('products-saved')]
    public function refreshList(): void
    {
        $this->resetPage();
    }

    #[On('labels-search')]
    public function updateSearch(string $query): void
    {
        $this->search = $query;
        $this->resetPage();
    }

    public function render(): View
    {
        $products = Product::query()
            ->whereNotNull('label_code')
            ->when(filled($this->search), function ($query): void {
                $needle = '%' . $this->search . '%';
                $query->where(function ($inner) use ($needle): void {
                    $inner->where('name_en', 'like', $needle)
                        ->orWhere('name_ur', 'like', $needle)
                        ->orWhere('label_code', 'like', $needle)
                        ->orWhere('code', 'like', $needle);
                });
            })
            ->latest('created_at')
            ->latest('id')
            ->paginate(12);

        return view('livewire.labels.list', [
            'products' => $products,
        ]);
    }
}
