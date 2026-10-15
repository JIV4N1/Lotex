<?php

namespace App\View\Components;

use App\Models\Advisor;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class SalesTeam extends Component
{
    public function render(): View
    {
        return view('components.sales-team', ['advisors' => Advisor::where('is_active', true)->orderBy('position')->orderBy('id')->get()]);
    }
}
