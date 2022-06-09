<?php

namespace App\Http\Livewire\Project;

use App\Models\Project;
use App\RedcapLaravel;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use Livewire\Component;

class UpdateMinimisationFactorForm extends Component
{
    public $project_id, $new_factor, $json_radio_fields, $factors;
    public $readyToLoad = false;
    private $redcap_radio_fields, $list_fields;

    protected $validationAttributes = [
        'factors.*.weight' => 'weight',
    ];

    public function mount()
    {
        $project = Auth::user()->currentProject;
        $this->project_id = $project->id;
        $this->old_name = $project->name;
        $this->factors = $project->minimisation_setting[ 'factors' ] ?? [];
        $this->json_radio_fields = json_encode( [] );
        $this->list_fields =[];
    }

    public function rules()
    {
        $this->updateRadioList();
        return [
            'factors.*.weight' => 'required|numeric|min:0.1',
            'new_factor' =>
                [
                    'required',
                    Rule::in($this->list_fields),
                ]
        ];
    }
    public function render()
    {
        return view('livewire.project.update-minimisation-factor-form');
    }

    public function loadRadio()
    {
        $this->updateRadioList();

        $this->readyToLoad= true;
    }

    public function updated( $propertyName )
    {
        $this->validateOnly( $propertyName );
    }

    public function add()
    {
        $this->updateRadioList();

//        $this->validateOnly('new_factor');

        $factors = collect($this->factors );

        $new_factor_details = $this->redcap_radio_fields[ $this->new_factor ];

        $label = $new_factor_details['label'];
        if ( strlen( $label ) > 30 )
        {
            $label = substr( $label, 0, 30 ). "...";
        }

        $levels=[];
        foreach( $new_factor_details['options'] as $coded_value => $option_label)
        {
            if ( strlen( $option_label ) > 30 )
            {
                $option_label = substr( $option_label, 0, 30 ). "...";
            }

            $levels[] =
                [
                    'label' => $option_label,
                    'coded_value' => $coded_value,
                ];
        }

        $newFactor = [
            'name' =>  $label ,
            'field_name' => $this->new_factor,
            'levels' => $levels,
            'weight' => 1
        ];

        $factors->push($newFactor);
        $this->factors = $factors;

        $this->new_factor = '';

    }

    public function destroy($field_name) {
        $this->factors = collect($this->factors)
            ->where('field_name', '!=', $field_name);;
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

        $project = Project::find( $this->project_id );

        $this->validateOnly('factors.*.weight');

        $project->fill( [
            'minimisation_setting->factors' => $this->factors,
        ] );

        $project->save();
        session()->flash('message', 'Prognostic factor(s) successfully updated.');
    }

    public function updateRadioList()
    {
        $radioFields = [''=>['label' => "Please select ...",
                    'options' => []]];
        try {
            $redcap = new RedcapLaravel( Project::find( $this->project_id ) );

            $radio_fields = $redcap->getRadioField()->toArray();

            foreach ( $radio_fields as $field_name => $meta ) {
                $list = [
                    'label' => $meta[ 'field_label' ],
                    'options' => $meta[ 'select_choices_or_calculations' ]
                ];
                $radioFields[ $field_name ] = $list;
            }
        } catch (\Exception $e) {

            Log::error( $e );
            $message = $e->getMessage();
            $redcapError = "Failed to get metadata from REDCap. $message";
            $this->addError('redcap', $redcapError);
        }

        $this->redcap_radio_fields = $radioFields;
        $this->json_radio_fields = json_encode( $radioFields );
        $this->list_fields = collect($radioFields)
            ->keys()
            ->diff(collect($this->factors)->pluck('field_name'))->all();

    }

    public function cancel()
    {
        $project = Auth::user()->currentProject;
        $this->project_id = $project->id;
        $this->factors = $project->minimisation_setting[ 'factors' ] ?? [];
        $this->updateRadioList();
    }


}
