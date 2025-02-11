<?php

namespace App\View\Components;

use Illuminate\View\Component;
use Illuminate\Contracts\Pagination\LengthAwarePaginator; // Import the paginator class

class Pagination extends Component
{
    public LengthAwarePaginator $paginator; // Make the paginator property public

    /**
     * Create a new component instance.
     *
     * @return void
     */
    public function __construct(LengthAwarePaginator $paginator)
    {
        $this->paginator = $paginator;
    }

    /**
     * Get the view / contents that represent the component.
     *
     * @return \Illuminate\Contracts\View\View|\Closure|string
     */
    public function render()
    {
        return view('components.pagination');
    }
}
