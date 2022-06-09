<?php

namespace App\Http\Livewire\Project;

use App\Models\Project;
use App\RedcapLaravel;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Livewire\Component;
use Illuminate\Validation\Rule;

class UpdateMinimisationGroupForm extends Component
{
    public $project_id, $field_name, $json_radio_fields, $groups;
    public $readyToLoad = false;
    private $redcap_radio_fields, $list_fields;

    protected $validationAttributes = [
        'groups.*.ratio' => 'ratio',
    ];

    public function rules()
    {
        $this->updateRadioList();
        return [
            'groups.*.ratio' => 'required|integer|min:1',
            'field_name' =>
            [
                'required',
                Rule::in($this->list_fields),
            ]
        ];
    }

    public function render()
    {
        return view('livewire.project.update-minimisation-group-form',
            [
                'json_radio_fields' =>$this->json_radio_fields
            ]);
    }

    public function loadRadio()
    {
        $this->updateRadioList();

        $this->readyToLoad= true;
    }

    public function mount()
    {
        $project = Auth::user()->currentProject;
        $this->project_id = $project->id;
        $this->field_name = $project->minimisation_setting[ 'randGroup' ] ?? '';
        $this->groups = $project->minimisation_setting[ 'groups' ] ?? [];
        $this->json_radio_fields = json_encode( [] );
        $this->list_fields =[$this->field_name];
    }



    public function updated( $propertyName )
    {
        $this->validateOnly( $propertyName );
    }

    public function updateRadioList()
    {
       $radioFields = [];
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
        $this->list_fields = collect($radioFields)->keys()->toArray();
    }

    public function updating( $name, $value )
    {
        $this->updateRadioList();
        // load new group if new field name is different
        if ( $name == 'field_name' &&
            isset( $this->redcap_radio_fields[ $value ] ) &&
            $this->field_name != $value )
        {
            foreach ( $this->redcap_radio_fields[ $value ][ 'options' ]
                      as $key => $label ) {
                $groups[] =
                    [
                        'name' => $label,
                        'coded_value' => $key,
                        'ratio' => 1,
                    ];
            }
            $this->groups = $groups;
        }
    }

    public function updateSetting()
    {
        $project = Project::find( $this->project_id );

        $this->validate();

        // save groups setting
        $project->fill( [
            'minimisation_setting->randGroup' => $this->field_name,
            'minimisation_setting->groups' => $this->groups
        ] );

        $project->save();
        session()->flash('message', 'Group allocation successfully updated.');
    }

    public function cancel()
    {
        $project = Auth::user()->currentProject;
        $this->project_id = $project->id;
        $this->field_name = $project->minimisation_setting[ 'randGroup' ] ?? '';
        $this->groups = $project->minimisation_setting[ 'groups' ] ?? [];
        $this->updateRadioList();
    }


}
