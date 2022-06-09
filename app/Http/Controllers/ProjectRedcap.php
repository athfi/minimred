<?php

namespace App\Http\Controllers;

use App\Minimization;
use App\Models\Project;
use App\Models\RedcapUser;
use App\RedcapLaravel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use function now;
use function session;

class ProjectRedcap extends Controller
{
    public function login(Request $request)
    {
        $project_id = $request->project_id;
        $project = Project::findOrFail($project_id);

        $this->validateRedcapBookmarkUrl( $project, $request );

        $error = $this->validateProject( $project );
        if ($error)
        {
            abort(401, $error);
        }

        list( $local_user, $page ) = $this->authRedcapUser($project_id, $project, $request );

        $message = "Login success. Welcome ". $local_user['name'] . " to minimRed." ;

        if ($page == 'records')
        {
            return redirect()->route( "redcap.$page" )
                ->with( 'message', $message);
        }
        if ($page == 'randomise')
        {
            $record_id = $request->record;

            if (! $record_id ){
                return redirect()->route( "redcap.records" )
                    ->with( 'message', $message)
                    ->with('error', 'Failed to open randomisation page. No participant has been selected in REDCap.');
            }

            return redirect()->route( "redcap.$page",$record_id)
                ->with( 'message', $message);
        }

        return abort(401);
    }

    public function records(Request $request)
    {

        $this->checkRedcapSession( $request );

        $redcap_session = $request->session()->get('redcap');
        $project = $redcap_session[ 'project' ];

        $error = $this->validateProject( $project );
        if ($error)
        {
            abort(401, $error);
        }

        $message = $request->session()->get('message');

        return view( 'redcap.records', ['project' => $project] )
            ->with( 'message', $message);
    }

    public function record(Request $request, $record_id )
    {
        $action = 'show';

        return $this->showRecord($request, $record_id, $action );

    }

    public function randomise(Request $request, $record_id )
    {
        $action = 'randomise';

        return $this->showRecord($request, $record_id, $action );
    }


    private function showRecord($request, $record_id, string $action )
    {
        $this->checkRedcapSession( $request );

        $redcap_session = $request->session()->get('redcap');
        $project = $redcap_session[ 'project' ];

        $error = $this->validateProject( $project );
        if ($error)
        {
            abort(401, $error);
        }

        list( $fields, $record, $metadata, $errors ) =
            $this->getRecordDetails( $project, $record_id, $action );


        return view( 'redcap.record',
            [
                'project' => $project,
                'record' => $record,
                'metadata' => $metadata,
                'fields' => $fields,
                'action' => $action,
                'minim_errors' => $errors
            ] );
    }

    public function minimise(Request $request, $record_id )
    {
        $this->checkRedcapSession( $request );

        $redcap_session = $request->session()->get('redcap');
        $project = $redcap_session[ 'project' ];

        $error = $this->validateProject( $project );
        if ($error)
        {
            abort(401, $error);
        }

        list( $fields, $record, $metadata, $errors, $setting, $redcap) =
            $this->getRecordDetails( $project, $record_id );

        if ($errors){
            return redirect()->route( 'redcap.record', ['record_id' => $record_id ] );
        }

        $minim = new Minimization( $setting, $metadata);

        // Get all records
        $records = collect($redcap->records());

        // get treatment allocation
        list( $group , $imbalance ) = $minim->enroll( $record_id, $records );

        // Prepare data to be saved to REDCap
        $time = now();
        $data[ 0 ] = [
            $redcap->getRecordID() => $record_id,
            $setting[ 'randGroup' ] => $group,
            $setting[ 'randTime' ] => $time,
        ];
        // Save treatment allocation to REDCap
        $test = $redcap->importRecords( $data );

        Log::info('Participants have been successfully randomised',
            [ $data[ 0 ] , 'user_id' => $redcap_session['local_user']->redcap_user_id]);


        return redirect()->route( 'redcap.record', ['record_id' => $record_id ] )
            ->with( 'message', 'Participants have been successfully randomised');
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


    public function logout(Request $request)
    {
        $name = session('redcap')['local_user']['name']??'';
        $request->session()->forget( [ 'redcap', 'redcap_time_expire' ]);

        $message='';
        if ($name )
        {
            $message = "We have successfully logged you out!";
        }

        return view( 'redcap.logout', ['name' => $name] )
            ->with( 'message', $message);
    }

    private function getRedcapBookmarkUrl( Request $request)
    {
        return route( 'redcap.login', [
            'check' => $request->check,
            'page' => $request->page,
            'project_id' => $request->project_id,
            'signature' => $request->signature,
        ] );

    }

    private function validateRedcapBookmarkUrl( $project, Request $request ): void
    {
        $records_bookmark = $project->redcap_bookmark_records;
        $randomise_bookmark = $project->redcap_bookmark_records;
        $check_urls = $this->getRedcapBookmarkUrl( $request );

        if ( ! ( ( $records_bookmark == $check_urls ) ||
            ! ( $randomise_bookmark == $check_urls ) )
        ) {
            abort( 401 );
        }
    }

    private function checkRedcapSession( Request $request ): void
    {
        $time_expire = $request->session()->get( 'redcap_time_expire' );
        if ($time_expire == "")
        {
            abort(401);
        }
        elseif ( $time_expire < now() )
        {
            $request->session()->forget( [ 'redcap', 'redcap_time_expire' ]);
            abort( 401, "Sorry your session expired, please re-login using REDCap's Project Bookmarks." );
        }
        else
        {
            session( [ 'redcap_time_expire' => now()->addMinutes( 30 ) ] );

            $redcap_session = session('redcap');
            $project = $redcap_session['project']->refresh();
            $local_user = $redcap_session['local_user']->refresh();

            session([
                'redcap' =>
                    [
                        'local_user' => $local_user,
                        'redcap_user' => $redcap_session['redcap_user'],
                        'project' => $project,
                        'redcap_info' => $redcap_session['redcap_info'],
                    ],
                'redcap_time_expire' => now()->addMinutes(30)
            ]);
        }

    }

    private function connectToRedcap( $project )
    {
        try {
            return new RedcapLaravel( $project );
        } catch (\Exception $e) {
            Log::error( $e );
            $message = $e->getMessage();
            $this->redcapError = "Connection to REDCap server failed. $message";
            session()->flash( 'error', "Connection to REDCap server failed. $message" );
        }
        return null;
    }

    private function updateOrCreateLocalUser( $project_id, $redcap_user_data )
    {
        $local_user = RedcapUser::updateOrCreate(
            [
                'project_id' => $project_id,
                'redcap_user_id' => $redcap_user_data[ 'username' ],

            ],
            [
                'name' => $redcap_user_data[ 'firstname' ] . " " . $redcap_user_data[ 'lastname' ],
                'email' => $redcap_user_data[ 'email' ],
                'redcap_expired_date' => $redcap_user_data[ 'expiration' ] ?? null
            ]
        );
        return $local_user;
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

    private function authRedcapUser($project_id, $project, Request $request ): array
    {
        $response = Http::asForm()->post(
            $project->redcap_url,
            [
                'authkey' => $request->authkey,
                'format' => 'json'
            ]
        );

        if ( $response->status() != 200 ) {
            abort( 401 );
        }

        $redcap_user = $response->json();
        $redcap = $this->connectToRedcap( $project );
        $redcap_user_data = $redcap->getUser( $redcap_user[ 'username' ] );

        if ($redcap_user_data['expiration'] != "")
        {
            $expiration = date_create($redcap_user_data['expiration']);
            if ($expiration < now() )
            {
                abort( 401 , "Your REDCap account has been expired.");
            }
        }

        $local_user = $this->updateOrCreateLocalUser( $project_id, $redcap_user_data );
        $redcap_info = $redcap->exportProjectInfo();

        $request->session()->forget( [ 'redcap', 'redcap_time_expire' ] );
        session( [
            'redcap' =>
                [
                    'local_user' => $local_user,
                    'redcap_user' => $redcap_user_data,
                    'project' => $project,
                    'redcap_info' => $redcap_info,
                ],
            'redcap_time_expire' => now()->addMinutes( 30 )
        ] );

        $page = $request->page;
        return array( $local_user, $page );
    }

}
