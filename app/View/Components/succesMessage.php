<?php

namespace App\View\Components;

use Illuminate\View\Component;

class succesMessage extends Component
{
    public $message, $title;
    /**
     * Create a new component instance.
     *
     * @return void
     */
    public function __construct($title="Success", $message = "")
    {
        $this->message = $message;
        $this->title = $title;
    }

    /**
     * Get the view / contents that represent the component.
     *
     * @return \Illuminate\Contracts\View\View|\Closure|string
     */
    public function render()
    {
        return view('components.succes-message');
    }
}
