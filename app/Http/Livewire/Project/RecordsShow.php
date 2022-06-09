<?php

namespace App\Http\Livewire\Project;

use App\Models\Project;
use Illuminate\Support\Collection;
use App\RedcapLaravel;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Livewire\Component;
use Livewire\WithPagination;

class RecordsShow extends Component
{
    protected $poject;
    public $field;
    private $records;
    public $readyToLoad = false;
    private $perPage=20;

    use WithPagination;

    public function mount()
    {
        $project = Auth::user()->currentProject;
        $this->project = $project;
        $this->projectId = $project->id;
        $this->field = [
            'recordId' => '',
            'randGroup' => $project->minimisation_setting[ 'randGroup' ],
            'randTime' => $project->minimisation_setting[ 'randTime' ],
            'factors'=> $project->minimisation_setting[ 'factors' ]
        ];
        if ( $project->minimisation_setting[ 'randGroup' ] == "" ||
            ! ( $project->minimisation_setting[ 'factors' ] ) )
        {
            $mesage = "Randomisation group or prognostic factor is not configured correctly.";
            session()->flash( 'error', $mesage );
            Log::error($mesage, [ 'project_id' => $project->project_id, 'user_id' =>  Auth::user()->id ]);
        }

        $this->records = collect( [] );
    }

    public function startLoad()
    {
        $this->readyToLoad = true;
    }

    public function loadRecords()
    {
        try {
            $redcap = new RedcapLaravel( $this->project );
            $this->field[ 'recordId' ] = $redcap->getRecordID();
            $records = collect( $redcap->allRecords() );
            if ( $records ) {
                $this->records = $records;
            }
        } catch (\Exception $e) {
            Log::error( $e );
            $message = $e->getMessage();
            $this->redcapError = "Failed to get records from REDCap. $message";
            session()->flash( 'error', "Failed to get records  from REDCap. $message" );
        }
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

    public function render()
    {
        $perPage = $this->perPage;
        $count = 0;
        $items = collect( [] );
        // If page is ready to load, Get records from REDCap and paginate it
        if ( $this->readyToLoad ) {
            $this->loadRecords();
            $collection = $this->records;
            if ( $collection ) {
                $items = $collection->sortByDesc( $this->field[ 'recordId' ] )
                    ->forPage( $this->page, $perPage );
                $count = $items->count();
            }
        }
        $paginator = $this->paginate( $this->records, $perPage, $this->page );

        return view( 'livewire.project.records-show', [ 'records' => $paginator ] );
    }

}
