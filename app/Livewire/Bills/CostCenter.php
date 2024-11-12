<?php

namespace App\Livewire\Bills;

use Livewire\Component;
use Illuminate\Support\Facades\Log;

class CostCenter extends Component
{

    public $bill;
    public $cost_centres;
    public $cost_center_name;
    public $isOpen = false;

    public function closeOrOpen()
    {
        $this->isOpen = !$this->isOpen;
    }

    public function selectCostCenter($center, $name)
    {
        try {
            if ($center === $this->bill->cost_center) {
                return;
            } else {
                \App\Models\Bill::where('id', $this->bill->id)->update(['cost_center' => $center]);
                $this->bill->cost_center = $center;
                $this->isOpen = false;
                $this->cost_center_name = $name;
            }
        } catch (\Exception $e) {
            Log::error('Error when associating an invoice with a cost center', ['error' => $e]);
        }
    }

    public function mount($bill)
    {
        $this->cost_centres = \App\Models\CostCenter::pluck('name', "id")->toArray();
        $this->cost_center_name = \App\Models\CostCenter::where('id', $this->bill->cost_center)->value('name');
        $this->bill = $bill;
    }


    public function render()
    {
        return view('livewire.bills.cost-center', [
            'cost_centres' => $this->cost_centres,
            'isOpen' => $this->isOpen,
            'bill' => $this->bill,
        ]);
    }
}
