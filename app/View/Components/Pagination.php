<?php

namespace App\View\Components;

use Illuminate\View\Component;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class Pagination extends Component
{
    public LengthAwarePaginator $paginator;

    /**
     * Create a new component instance.
     */
    public function __construct(LengthAwarePaginator $paginator)
    {
        $this->paginator = $paginator;
    }

    /**
     * Get the view / contents that represent the component.
     */
    public function render()
    {
        return view('components.pagination');
    }
}
