<?php

namespace App\Livewire;

use App\Enums\BatchStatus;
use App\Models\ImportBatch;
use App\Services\BatchImporter;
use App\Services\BatchProcessor;
use App\Services\BatchValidator;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\Attributes\Layout;

#[Layout('layouts.clean')]
class InvoiceImporter extends Component
{
    use WithFileUploads;

    public $file;

    public ?int $batchId = null;

    public function import(BatchImporter $importer)
    {
        $this->validate([
            'file' => ['required', 'file', 'max:5120'],
        ]);

        $batch = $importer->import(
            Auth::user(),
            $this->file->getRealPath(),
            $this->file->getClientOriginalName(),
        );

        $this->batchId = $batch->id;
        $this->reset('file');
    }

    public function validateBatch(BatchValidator $validator)
    {
        if (! $this->batch) {
            return;
        }

        $validator->validate($this->batch);
        unset($this->batch);
    }

    public function process(BatchValidator $validator, BatchProcessor $processor)
    {
        if (! $this->batch) {
            return;
        }

        if ($this->batch->status === BatchStatus::Pending) {
            $validator->validate($this->batch);
            unset($this->batch);
        }

        $processor->process($this->batch);
        unset($this->batch);
    }

    public function resetImport()
    {
        $this->batchId = null;
        unset($this->batch);
    }

    #[Computed]
    public function batch(): ?ImportBatch
    {
        if ($this->batchId === null) {
            return null;
        }

        return ImportBatch::with('invoices')->find($this->batchId);
    }

    #[Computed]
    public function invalidCount(): int
    {
        if (! $this->batch || $this->batch->status === BatchStatus::Pending) {
            return 0;
        }

        return $this->batch->total_rows - $this->batch->valid_rows;
    }

    public function render()
    {
        return view('livewire.invoice-importer');
    }
}
