<?php

namespace App\Http\Livewire\Project;

use App\Models\Project;
use App\RedcapLaravel;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use Livewire\Component;

class UpdateMinimisationTimeRandomisedForm extends Component
{
    public $time_randomised, $project_id, $options;

    public $readyToLoad=false;

    public function render()
    {
        return view('livewire.project.update-minimisation-time-randomised-form');
    }

    public function mount()
    {
        $project = Auth::user()->currentProject;
        $this->project_id = $project->id;
        $this->options = [ ];
        $this->time_randomised = $project->minimisation_setting['randTime']??'';
//        $this->updateOptions();
    }

    public function loadTime()
    {
        $this->readyToLoad= true;
        $this->updateOptions();
    }

    protected $validationAttributes = [
        'time_randomised' => 'field name to store time randomised',
    ];


    public function rules()
    {
        $distance_list = collect($this->options)->keys()->toArray();
        return [
            'time_randomised' =>
                [
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

        $this->validate();

        $project = Auth::user()->currentProject;

        $project->fill( [
            'minimisation_setting->randTime' => $this->time_randomised,
        ] );

        $project->save();
        session()->flash('message', 'Field name to store time randomised successfully updated.');
    }

    public function cancel()
    {
        $this->mount();
    }

    public function updateOptions()
    {
        $options = [''=>"Please select ..."];

        if ( $this->readyToLoad )
        {
            try {
                $redcap = new RedcapLaravel( Project::find( $this->project_id ) );

                $time_fields = $redcap->getTimeField()->toArray();

                foreach ( $time_fields as $field_name => $meta ) {
                    $options[ $field_name ] = $meta[ 'field_label' ];
                }
            } catch (\Exception $e) {

                Log::error( $e );
                $message = $e->getMessage();
                $redcapError = "Failed to get metadata from REDCap. $message";
                $this->addError('redcap', $redcapError);
            }
        }
        else
        {
            if ($this->time_randomised != '' )
            $options[ $this->time_randomised ] = $this->time_randomised;
        }

        $this->options = $options;
        $this->json_radio_fields = json_encode( $options );
    }
}
