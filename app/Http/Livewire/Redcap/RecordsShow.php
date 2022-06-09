<?php

namespace App\Http\Livewire\Redcap;

use App\Models\Project;
use App\RedcapLaravel;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Livewire\Component;
use Livewire\WithPagination;

class RecordsShow extends Component
{
    use WithPagination;

    public $field;
    private $records;
    private $perPage=20;

    public function mount( $project = null)
    {
        $redcap_session = session('redcap');

        if( ! $project )
        {
            $project = $redcap_session[ 'project' ];
        }
        $this->field = [
            'recordId' => $project->minimisation_setting[ 'record_id' ],
            'randGroup' => $project->minimisation_setting[ 'randGroup' ],
            'randTime' => $project->minimisation_setting[ 'randTime' ],
            'factors'=> $project->minimisation_setting[ 'factors' ]
        ];

        if ( $project->minimisation_setting[ 'randGroup' ] == "" ||
           ! ( $project->minimisation_setting[ 'factors' ] ) )
        {
            $mesage = "Randomisation group or prognostic factor is not configured correctly.";
            session()->flash( 'error', $mesage );
            Log::error($mesage, [ 'redcap_session' => $redcap_session ]);
        }

        $redcap_dag = $this->extractDag($redcap_session[ 'redcap_user' ]);

        $records = $this->loadRecords( $project, $redcap_dag);

        $this->records = $records;
    }



    public function render()
    {
        $this->mount();

        $perPage = $this->perPage;
        $collection = $this->records;
        if ( $collection ) {
            $items = $collection->sortByDesc( $this->field[ 'recordId' ] )
                ->forPage( $this->page, $perPage );
            $count = $items->count();
        }

        $paginator = $this->paginate( $this->records, $perPage, $this->page );

//        dd($paginator);

        return view( 'livewire.redcap.records-show', [ 'records' => $paginator ] );
    }

    public function paginate($items, $perPage = 15, $page = null,
                             $baseUrl = null,
                             $options = [])
    {
        $page = $page ?: (Paginator::resolveCurrentPage() ?: 1);

        $items = $items instanceof Collection ?
            $items : Collection::make($items);

        $lap = new LengthAwarePaginator($items->forPage($page, $perPage),
            $items->count(),
            $perPage, $page, $options);

        if ($baseUrl) {
            $lap->setPath($baseUrl);
        }

        return $lap;
    }

    public function loadRecords(Project $project, $redcap_dag = null )
    {
        try {
            $redcap = new RedcapLaravel( $project );
            $records = collect( $redcap->allRecords() );

            $dag_is_exported = isset( $records->first()['redcap_data_access_group']);

            if ($redcap_dag && $dag_is_exported )
            {
                return $records
                    ->where('redcap_data_access_group', $redcap_dag )
                    ->all();
            }

            return $records;

        } catch (\Exception $e) {
            Log::error( $e );
            $message = $e->getMessage();
            $this->redcapError = "Connection to REDCap server failed. $message";
            session()->flash( 'error', "Connection to REDCap server failed. $message" );
        }
    }

    private function extractDag( $redcap_user )
    {
        $user_dag = $redcap_user['data_access_group'];
        return $this->getRedcapUniqueDag($user_dag);
    }
    private function getRedcapUniqueDag( $user_dag )
    {
        return strtolower(substr( preg_replace( "/\s/", "_", $user_dag ), 0, 18 ));
    }

}
