<?php
namespace App\Http\Livewire\Project;

use App\RedcapLaravel;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;
use IU\PHPCap\PhpCapException;
use Livewire\Component;

class UpdateProjectForm extends Component
{
    public $project_id;
    public $url;
    public $name;
    public $token;
    public $error;

    public function render()
    {
        return view('livewire.project.update-project-form');
    }

    protected $rules = [
        'name' => 'required',
        'url' => 'required|url',
        'token' => 'required|alpha_num|max:32|min:32'
    ];

    protected $validationAttributes = [
        'name' => 'Project name',
        'url' => 'REDCap url',
        'token' => 'REDCap token',
    ];

    public function mount()
    {
        $project = Auth::user()->currentProject;
        $this->project_id = $project->id;
        $this->old_name = $project->name;
        $this->name = $project->name;
        $this->url = $project->redcap_url;
        $this->token = $project->redcap_token;

    }

    public function updated( $propertyName )
    {
        $this->validateOnly( $propertyName );
    }

    public function updateProject()
    {
        $user = Auth::user();
        $project = $user->currentProject;

        if ( $project->id != $this->project_id )
        {

            $error_msg = 'Current project that active is "' . $project->name .
                '" . Please switch to one "' . $this->old_name . '" if you want to make update to. "' . $this->old_name . '"' ;

            return redirect("/projects")->with('error', $error_msg);
        }



        Gate::allowIf( $project->isOwnerOrSupperAdmin( $user ) );

        $this->validate();

        $newProject = $project;

        $newProject->redcap_url =  $this->url;
        $newProject->redcap_token =  $this->token;

        $errors = $this->getErrorBag();

        $record_id="";
        try {
            $redcap = new RedcapLaravel( $newProject );
            //try to connect
            $record_id = $redcap->getRecordID();

        } catch (PhpCapException $e) {

            $eMessage = "Connection test to REDCap server failed. ";

            $errors->add( 'connection', $eMessage . $e->getMessage() );

            Log::error( $e );
        }


        if ( $errors->isEmpty() )
        {
            $currentProject = Auth::user()->currentProject;

            $currentProject->name = $this->name;
            $currentProject->redcap_url = $this->url;
            $currentProject->redcap_token = $this->token;
            $currentProject->fill( [
                'minimisation_setting->record_id' => $record_id]);
            $currentProject->save();

            session()->flash('message', 'Project successfully updated.');
        }
    }
}

