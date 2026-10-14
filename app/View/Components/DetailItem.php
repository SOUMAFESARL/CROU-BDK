<?php

namespace App\View\Components;

use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class DetailItem extends Component
{
    public string $label;
        public string $value = '—';
    public function __construct(
         string $label,
         string $value = '—'
    ) {
        $this->label = $label;
        $this->value = $value;
    }

    public function render(): View|Closure|string
    {
        return view('components.detail-item');
    }
}