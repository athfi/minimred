<?php

namespace App\Http\Livewire\Project;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\URL;
use Livewire\Component;

class RedcapBookmarks extends Component
{
    public $bookmard_records;
    public $bookmard_randomise;
    public $project_id;

    public function render()
    {
        return view('livewire.project.redcap-bookmarks');
    }

    public function mount()
    {
        $project = Auth::user()->currentProject;
        $this->project_id = $project->id;
        $this->old_name = $project->name;
        $this->bookmard_records = $project->redcap_bookmark_records;
        $this->bookmard_randomise = $project->redcap_bookmark_randomise;
    }

    public function generate()
    {
        $user = Auth::user();
        $project = $user->currentProject;
        if ( $project->id != $this->project_id )
        {

            $error_msg = 'Current project that active is "' . $project->name .
                '" . Please switch to "' . $this->old_name . '" if you want to make update to "' . $this->old_name . '"' ;

            return redirect("/projects")->with('error', $error_msg);
        }

        Gate::allowIf( $project->isOwnerOrSupperAdmin( $user ) );

        $random_number = random_int(100,999);
        $this->bookmard_records = URL::signedRoute('redcap.login',
            ['project_id' => $this->project_id, 'page'=>'records', 'check' => $random_number]);
        $this->bookmard_randomise = URL::signedRoute('redcap.login',
            ['project_id' => $this->project_id, 'page'=>'randomise', 'check' => $random_number]);

        $project->redcap_bookmark_records = $this->bookmard_records;
        $project->redcap_bookmark_randomise = $this->bookmard_randomise;
        $project->bookmark_check = $random_number;

        $project->save();

        session()->flash('message', 'REDCap bookmarks successfully generated.');

    }
}
