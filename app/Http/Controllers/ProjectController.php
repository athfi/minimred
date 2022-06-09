<?php

namespace App\Http\Controllers;

use App\Minimization;
use App\Models\Project;
use App\RedcapLaravel;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;

class ProjectController extends Controller
{

    public function index()
    {
        $projects = Auth::user()->allProjects();
        return view( 'project.index', [ 'projects' => $projects ] );
    }

    public function minimisationSetting()
    {
        $project = Auth::user()->currentProject;

        if ( $project ) {
            Gate::allowIf( $project->isOwnerOrSupperAdmin(Auth::user()) );

            return view( 'project.minimisation', [ 'project' => $project ] );
        }

        return redirect()->route( 'projects' )
            ->with( 'error', 'You dont have any project assosiated. Please switch to one of the project, or create new project' );

    }

    public function show()
    {
        $project = Auth::user()->currentProject;

        if ( $project ) {
            return view( 'project.show', [ 'project' => $project ] );
        }

        return redirect()->route( 'projects' )
            ->with( 'error',
                "<span class='text-xl'>Failed to open project page!</span><br>
                    You dont have any project assosiated. Please switch to one
                    of your's project, or create new project." );

    }

    public function create()
    {
        return view( 'project.create' );

    }

    public function switch( Project $project )
    {
        Gate::allowIf( Auth::user()->switchProject( $project ) );

        if ( $project->isOwnerOrSupperAdmin(Auth::user()) )
        {
            return redirect()->route( 'project' );
        }

        return redirect()->route( 'project.records' );

    }

    public function destroy( Project $project )
    {
        Gate::allowIf( $project->isOwnerOrSupperAdmin(Auth::user()) );

        $project->delete();

        return redirect()->route( 'projects' )
            ->with( 'message', 'Project deleted successfully.' );
    }

    public function records()
    {
        $user = Auth::user();
        $project = $user->currentProject;

        if (! $project ){
            return redirect()->route( 'projects' )
                ->with( 'error',
                    'You dont have any project assosiated. Please switch to one
                    of the project, or create new project.' );
        }

        Gate::allowIf( $user->belongsToProject( $project ) );

        $page = 'records';
        $error = $this->validateProject( $project );
        if ($error){
            return redirect()->route( 'minimisation' )
                ->with( 'error', "Failed to open $page page. ". $error );
        }

        return view( 'project.records', [ 'project' => $project ] );


    }

    public function record( $redcap_record_id )
    {
        $action = 'show';

        return $this->showRecord( $redcap_record_id, $action );

    }

    public function randomise( $redcap_record_id )
    {
        $action = 'randomise';

        return $this->showRecord( $redcap_record_id, $action );
    }


    private function showRecord( $redcap_record_id, string $action )
    {
        $user = Auth::user();
        $project = $user->currentProject;

        if (! $project ){
            return redirect()->route( 'projects' )
                ->with( 'error',
                    '<span class="text-xl">Failed to open record page!</span><br>
                    You dont have any project assosiated. Please switch to one
                    of the project, or create new project.' );
        }

        Gate::allowIf( $user->belongsToProject( $project ) );

        list( $fields, $record, $metadata, $errors ) =
            $this->getRecordDetails( $project, $redcap_record_id, $action );

        return view( 'project.record',
            [
                'project' => $project,
                'record' => $record,
                'metadata' => $metadata,
                'fields' => $fields,
                'action' => $action,
                'minim_errors' => $errors
            ] );
    }

    public function minimise( $redcap_record_id )
    {
        $user = Auth::user();
        $project = $user->currentProject;

        Gate::allowIf( $user->belongsToProject( $project ) );

        $action = 'randomise';

        list( $fields, $record, $metadata, $errors, $setting, $redcap) =
            $this->getRecordDetails( $project, $redcap_record_id );

        if ($errors){
            return redirect()->route( 'project.record', ['record_id' => $redcap_record_id ] );
        }

        $minim = new Minimization( $setting, $metadata);

        // Get all records
        $records = collect($redcap->records());

        // get treatment allocation
        list( $group , $imbalance ) = $minim->enroll( $redcap_record_id, $records );

        // Prepare data to be saved to REDCap
        $time = now();
        $data[ 0 ] = [
            $redcap->getRecordID() => $redcap_record_id,
            $setting[ 'randGroup' ] => $group,
            $setting[ 'randTime' ] => $time,
        ];
        // Save treatment allocation to REDCap
        $test = $redcap->importRecords( $data );


        return redirect()->route( 'project.record', ['record_id' => $redcap_record_id ] )
            ->with( 'message', 'Participants have been successfully randomised' );
    }


    private function getRecordDetails( $project, $redcap_record_id, $action = 'show'): array
    {
        $setting = $project->minimisation_setting;
        $factors = collect( $setting[ 'factors' ] )->pluck( 'field_name' );
        $redcap = new RedcapLaravel( $project );

        $fields = collect( [ 'record_id' => $redcap->getRecordID() ] )
            ->merge( $factors );

        if ( $action != 'randomise')
        {
            $fields = $fields->merge(
                [
                    'randGroup' => $setting[ 'randGroup' ],
                    'randTime' => $setting[ 'randTime' ],
                ]
            );
        }

        $record = $redcap->record( $redcap_record_id );

        $metadata = $redcap->getMetadata();

        // validate factors
        $errors = [];
        foreach ( $factors as $factor ) {
            if ( !( $metadata->has( $factor ) ) ) {
                $errors[ $factor ][ 'meta' ] = "The $factor could not be not found in REDCap metadata";
            } elseif ( isset( $record[ $factor ] ) ) {
                $label = strip_tags( $metadata[ $factor ][ 'field_label' ] );

                if ( $record[ $factor ] == "" ) {
                    $errors[ $factor ][ 'required' ] = "The [$label] field is required";
                }

                if ( !( $metadata[ $factor ][ 'field_type' ] == 'radio' ) ) {
                    $errors[ $factor ][ 'type' ] = "The' [$label] field type in REDCap must be radio";
                }
            }
        }

        if ( $action == 'randomise' && $record[ $setting[ 'randGroup' ] ] != "" ) {
            $errors[ $setting[ 'randGroup' ] ][ 'random' ] = "Participants have been allocated/randomised to a group.";
        }

        return array( $fields, $record, $metadata, $errors, $setting, $redcap );
    }

    public function resetRedcap()
    {
        $user = Auth::user();
        $project = $user->currentProject;

        $project->refresh();

        $page = 'reset';
        $error = $this->validateProject( $project );
        if ($error){
            return redirect()->route( 'minimisation' )
                ->with( 'error', "Failed to open $page page. ". $error );
        }

        Gate::allowIf( $user->belongsToProject( $project ) );

        $setting = $project->minimisation_setting;
        $rand_group = $setting[ 'randGroup' ];
        $rand_time = $setting[ 'randTime' ];
        $record_id = $setting[ 'record_id' ];

        $redcap = new RedcapLaravel( $project );
        $records = $redcap->records();

        $data = [];
        foreach ($records as $record)
        {
            if ($rand_group){
                $data[] = [
                    'record' => $record[$record_id],
                    'field_name' => $rand_group,
                    'value' => ""
                ];
            }
            if ($rand_time) {
                $data[] = [
                    'record' => $record[ $record_id ],
                    'field_name' => $rand_time,
                    'value' => ""
                ];
            }
        }

        $format = 'php';
        $type = 'eav';
        $overwriteBehavior = 'overwrite';
        $test = $redcap
            ->importRecords( $data, $format, $type, $overwriteBehavior );

        return response('REDCap randomisation data has been reset', 200);
    }

    private function validateProject( $project )
    {
        $minim_setting = $project->minimisation_setting;
        //check if randomisation field is configured
        if ( ! isset( $minim_setting['randGroup'] ) ||
            ! is_string( $minim_setting['randGroup'] ) ||
            $minim_setting['randGroup'] == "" ||
            ! isset($minim_setting['factors']) ||
            ! is_array($minim_setting['factors']) ||
            count($minim_setting['factors']) < 1
        )
        {
            $error = "Minimisation is not configured correctly.";
            return $error;
        }
        return null;
    }
}
