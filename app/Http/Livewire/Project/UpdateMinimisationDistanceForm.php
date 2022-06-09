<?php

namespace App\Http\Livewire\Project;

use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Livewire\Component;

class UpdateMinimisationDistanceForm extends Component
{
    public $distance_method, $project_id, $distance_list;

    public function render()
    {
        return view('livewire.project.update-minimisation-distance-form');
    }

    public function mount()
    {
        $project = Auth::user()->currentProject;
        $this->project_id = $project->id;
        $this->old_name = $project->name;
        $this->distance_list = [
            'range' => 'Range',
            'variance' => 'Variance',
            'st_dev' => 'Standard deviation',
            'marginal_balance' => 'Marginal balance'
        ];

        $this->distance_method = $project->minimisation_setting['distance_method']??'';
    }

    protected $validationAttributes = [
        'distance_method' => 'distance method',
    ];


    public function rules()
    {
        $distance_list = collect($this->distance_list)->keys()->toArray();
        return [
            'distance_method' =>
                [
                    'required',
                    Rule::in($distance_list),
                ]
        ];
    }

    public function updated( $propertyName )
    {
        $this->validateOnly( $propertyName );

    }

    public function updateSetting()
    {
        $user = Auth::user();
        $project = $user->currentProject;

        if ( $project->id != $this->project_id )
        {
            $error_msg = 'Current project that active is "' . $project->name .
                '" . Please switch to "' . $this->old_name . '" if you want to make update to "' . $this->old_name . '"' ;

            return redirect("/projects")->with('error', $error_msg);
        }


        $distance_list = collect($this->distance_list)->keys()->toArray();
        $this->validate();

        $project->fill( [
            'minimisation_setting->distance_method' => $this->distance_method,
        ] );

        $project->save();
        session()->flash('message', 'Distance method successfully updated.');
    }

    public function cancel()
    {
        $this->mount();
    }
}
