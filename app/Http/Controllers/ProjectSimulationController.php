<?php

namespace App\Http\Controllers;

use App\Minimization;
use App\RedcapLaravel;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;

class ProjectSimulationController extends Controller
{
    public function index()
    {
        $project = Auth::user()->currentProject;

        if ( !$project ) {
            return redirect()->route( 'projects' )
                ->with( 'error',
                    '<span class="text-xl">Failed to open simulation page!</span><br>
                    You dont have any project assosiated. Please switch to one
                    of the project, or create new project.' );

        }

        $page = 'simulation';
        $error = $this->validateProject( $project );
        if ($error){
           return redirect()->route( 'minimisation' )
                ->with( 'error', "Failed to open $page page. ". $error );
        }

        $num_participants = 300;
        $num_simulation = 4;

        $setting = $project->minimisation_setting;
        $randGroup = $setting[ 'randGroup' ];
        $minim = new Minimization( $setting );
        $dummy_records = $this->createDummyRecords( $num_participants, $setting );

        $minim->buildMiniTable($dummy_records);

        $result = [];
        for ($i=1; $i <= $num_simulation; $i++ )
        {
            list($new_records, $freq_table) =
                $this->simulate( $dummy_records, $minim, $randGroup );
            $result[] =  $freq_table;
        }

        return view( 'project.simulation', [ 'result' => $result , 'setting' => $setting] );
    }


    private function simulate( Collection $records, Minimization $minim, $randGroup ): array
    {
        $newMinim = clone $minim;
        $result = [];
        foreach ( $records as $record ) {
            $allocation = $newMinim->randomise( $record )[ 0 ];
            $record[ $randGroup ] = $allocation;
            $result[] = $record;
        }

        $freq_table = $newMinim->buildFreqTable();
        return array( $result, $freq_table );
    }

    private function createDummyRecords( int $num_participants, $setting )
    {
        $dummy_records = [];
        for ( $i = 1; $i <= $num_participants; $i ++ ) {
            $record = [];
            $dummy_id = "Test_" . substr( "0000" . $i, - 5 );
            $record[ $setting[ 'record_id' ] ] = $dummy_id;

            foreach ( $setting[ 'factors' ] as $factor ) {
                $levels = $factor[ 'levels' ];
                $value = $levels[ array_rand( $levels, 1 ) ][ 'coded_value' ];
                $record[ $factor[ 'field_name' ] ] = "$value";
            }
            $dummy_records[] = $record;
        }
        return collect($dummy_records);
    }

    private function validateProject( $project )
    {

        $minim_setting = $project->minimisation_setting;
        //check if randomisation field is configured
        if ( ! isset( $minim_setting['randGroup'] ) ||
            ! is_string( $minim_setting['randGroup'] ) ||
            $minim_setting['randGroup'] = "" ||
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
