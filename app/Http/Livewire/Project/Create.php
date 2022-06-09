<?php

namespace App\Http\Livewire\Project;

use App\Models\Project;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\URL;
use IU\PHPCap\PhpCapException;
use IU\PHPCap\RedCapProject;
use Livewire\Component;

class Create extends Component
{
    public function render()
    {
        return view('livewire.project.create');
    }

    public $url;
    public $name;
    public $token;
    public $error;

    protected $rules = [
        'name' => 'required',
        'url' => 'required|url',
        'token' => 'required|alpha_num|max:32|min:32'
    ];

    protected $validationAttributes = [
        'name' => 'project name',
        'url' => 'REDCap url',
        'token' => 'REDCap token',
    ];

    public function updated( $propertyName )
    {
        $this->validateOnly( $propertyName );
    }

    public function createProject()
    {
        $this->validate();

        $apiUrl = $this->url;
        $apiToken = $this->token;
        $sslVerify = true;

        $errors = $this->getErrorBag();
        $record_id="";
        try {
            $redcap = new RedCapProject( $apiUrl, $apiToken, $sslVerify );
            //try to connect
            $metadata = collect($redcap->exportMetadata());
            $record_id = $metadata->first()['field_name'];

        } catch (PhpCapException $e) {

            $eMessage = "Connection test to REDCap server failed. ";

            $errors->add( 'connection', $eMessage . $e->getMessage() );

            Log::error( $e );
        }


        if ( $errors->isEmpty() ) {
            $newProject = Project::create( [
                'name' => $this->name,
                'redcap_url' => $this->url,
                'redcap_token' => $this->token,
                'user_id' => Auth::id(),
            ] );

            $newProject->fill( [
                'minimisation_setting->record_id' => $record_id,
                'minimisation_setting->randGroup' => '',
                'minimisation_setting->randTime' => '',
                'minimisation_setting->groups' => [],
                'minimisation_setting->factors' => [],
                'minimisation_setting->prob_method' => 'naive',
                'minimisation_setting->distance_method' => 'range',
                'minimisation_setting->base_prob' => '90',
            ] );

            $newProject->save();

            Auth::user()->switchProject($newProject);

            return redirect()->route( 'minimisation' )
                ->with('message', 'Project successfully created! Next, use this page to setup minimisation.');
        }
    }
}
