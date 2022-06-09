<?php

namespace App\Http\Livewire\Project;

use App\Models\Project;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Livewire\Component;

class UpdateMinimisationProbabilityForm extends Component
{
    public $prob_method, $base_prob, $project_id, $prob_label;

    public $list_prob = [
        'naive' => 'Naive',
        'bcm' => 'Biased Coin Method'
    ];

    public function render()
    {
        return view('livewire.project.update-minimisation-probability-form');
    }

    protected $validationAttributes = [
        'base_prob' => 'High Probability' ,
        'prob_method' => 'Probability method'
    ];


    public function rules()
    {
        $list_prob = collect($this->list_prob)->keys()->toArray();
        return [
            'base_prob' => 'required|integer|min:1|max:99',
            'prob_method' =>
                [
                    'required',
                    Rule::in($list_prob),
                ]
        ];
    }

    public function updated( $propertyName, $value )
    {
        $this->validateOnly( $propertyName );

        if ($propertyName == 'prob_method' )
        {
            $this->prob_label = $value != 'bcm' ?
                'High Probability' : 'Min high probability ';
        }
    }

    public function mount()
    {
        $this->list_prob = [
            'naive' => 'Naive',
            'bcm' => 'Biased Coin Method'
        ];
        $project = Auth::user()->currentProject;
        $this->project_id = $project->id;

        $this->prob_method = $project->minimisation_setting['prob_method']??'';
        $this->base_prob = $project->minimisation_setting['base_prob']??'';
        $this->prob_label = $this->prob_method != 'bcm' ?
            'High Probability' : 'Min high probability ';
    }

    public function updateSetting()
    {
        $this->validate();

        $project = Auth::user()->currentProject;

        $project->fill( [
            'minimisation_setting->prob_method' => $this->prob_method,
            'minimisation_setting->base_prob' => $this->base_prob,
        ] );

        $project->save();
        session()->flash('message', 'Probability setting successfully updated.');
    }

    public function cancel()
    {
        $this->mount();
    }
}
