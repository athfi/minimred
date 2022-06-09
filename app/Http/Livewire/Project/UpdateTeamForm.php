<?php

namespace App\Http\Livewire\Project;

use App\Models\User;
use \Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Livewire\Component;

class UpdateTeamForm extends Component
{
    public $roles = [
        [
            'key' => 'super_admin',
            'name'=> 'Super administrator',
            'description' => ''
        ],
        [
            'key' => 'admin',
            'name'=> 'Administrator',
            'description' => ''
        ]
    ];

    public $addTeamMemberForm, $users, $email, $role, $project, $team;

    public $currentRole='';

    public $teamMemberIdBeingRemoved = null;

    public $currentlyManagingRole = false;

    public $confirmingLeavingTeam = false;

    public $confirmingTeamMemberRemoval = false;

    public function mount()
    {
        $this->user = Auth::user();
        $this->resetAddMember();

        $project = $this->user->currentProject;

        $this->project = $project;

        $this->users = $project->users;

    }

    public function render()
    {
        return view('livewire.project.update-team-form');
    }

    public function add()
    {
        $project = Auth::user()->currentProject;

        Gate::allowIf( $project->isOwnerOrSupperAdmin( Auth::user() ) );

        $project = Auth::user()->currentProject;

        $this->validate( );

        $newTeamMember = User::where('email',$this->addTeamMemberForm[ 'email' ])
            ->firstOrFail();

        $project->users()->attach(
            $newTeamMember, ['role' => $this->addTeamMemberForm[ 'role' ] ]
        );

        $this->resetAddMember();

        $this->refreshTeamMember();

        $this->resetErrorBag();
    }

    private function getRoles()
    {
        return collect($this->roles)->pluck('key')->implode(',');
    }

    public function validate( $rules = null, $messages = [], $attributes = []  )
    {
        $project = Auth::user()->currentProject;

        Validator::make( [
            'email' => $this->addTeamMemberForm[ 'email' ],
            'role' => $this->addTeamMemberForm[ 'role' ],
        ],
            [
                'email' => [ 'required', 'email', 'exists:users' ],
                'role' => [ 'required', 'string', 'in:' . $this->getRoles() ],
            ],
            [
                'email.exists' => __( 'We were unable to find a registered user with this email address.' ),
            ] )->after(
            $this->ensureUserIsNotAlreadyOnTeam( $project, $this->addTeamMemberForm[ 'email' ] )
        )->validateWithBag( 'addTeamMember' );


        $user = Auth::user();
        $project = $user->currentProject;

        if ( $project->id !=  $this->project->id )
        {

            $error_msg = 'Current project that active is "' . $project->name .
                '" . Please switch to one "' . $this->project->name . '" if you want to make update to. "' . $this->project->name . '"' ;

            return redirect("/projects")->with('error', $error_msg);
        }

    }

    private function ensureUserIsNotAlreadyOnTeam( $project, $email )
    {
        return function ($validator) use ($project, $email) {
            $validator->errors()->addIf(
                $project->hasUserWithEmail($email),
                'email',
                __('This user already belongs to the team.')
            );
        };
    }

    public function manageRole($userId)
    {
        $this->currentlyManagingRole = true;
        $this->managingRoleFor = User::findOrFail($userId);
        $this->currentRole = $this->managingRoleFor->projectRole($this->project);

    }

    public function updateRole( )
    {
        $project = Auth::user()->currentProject;

        Gate::allowIf( $project->isOwnerOrSupperAdmin( Auth::user() ) );

//        dd($this->currentRole, 'in:' . $this->getRoles());

        Validator::make([
            'role' => $this->currentRole,
        ], [
            'role' => ['required', 'string', 'in:' . $this->getRoles() ],
        ])->validate();

        $project->users()->updateExistingPivot( $this->managingRoleFor->id, [
            'role' => $this->currentRole,
        ]);

        $this->project = $project->fresh();

        $this->stopManagingRole();
    }


    public function stopManagingRole()
    {
        $this->currentlyManagingRole = false;
    }

    public function confirmTeamMemberRemoval($userId)
    {
        $this->teamMemberIdBeingRemoved = $userId;

        $this->confirmingTeamMemberRemoval=true;

    }

    public function confirmLeavingTeam($userId)
    {
        $this->teamMemberIdBeingRemoved = $userId;

        $this->confirmingLeavingTeam=true;

    }

    public function removeTeamMember()
    {
        $user = Auth::user();
        $project = $this->project;

        Gate::allowIf( $this->isAuthorised( $project, $user ) );

        $teamMember = User::findOrfail($this->teamMemberIdBeingRemoved);

        $this->ensureUserDoesNotOwnTeam( $this->teamMemberIdBeingRemoved, $project);

        $project->removeUser($teamMember);

        $this->confirmingTeamMemberRemoval = false;
        $this->teamMemberIdBeingRemoved = null;

        $this->refreshTeamMember();
    }

    public function leaveTeam()
    {


        $user = Auth::user();
        $project = $this->project;

        Gate::allowIf( $this->isAuthorised( $project, $user ) );

        $teamMember = User::findOrfail($this->teamMemberIdBeingRemoved);
        $this->ensureUserDoesNotOwnTeam( $this->teamMemberIdBeingRemoved, $project);

        $project->removeUser($teamMember);

        return redirect("/projects")
            ->with('message', 'You successfully leave from project: '. $project->name . '.');;
    }

    protected function ensureUserDoesNotOwnTeam($teamMemberId, $project)
    {
        if ($teamMemberId === $project->owner->id) {
            throw ValidationException::withMessages([
                'team' => [__('You may not leave a team that you created.')],
            ])->errorBag('removeTeamMember');
        }
    }

    private function resetAddMember(): void
    {
        $this->addTeamMemberForm = [
            'role' => '',
            'email' => ''
        ];
    }

    private function refreshTeamMember(): void
    {
        $this->project = $this->project->fresh();
        $this->users = $this->project->users;
    }

    /**
     * @param $project
     * @param \Illuminate\Contracts\Auth\Authenticatable|null $user
     * @return bool
     */
    private function isAuthorised( $project, User $user ): bool
    {
        $isSupperAdmin = $project->isOwnerOrSupperAdmin( Auth::user() );
        $isSelf = $this->teamMemberIdBeingRemoved == $user->id;

        $isAutorised = $isSupperAdmin || $isSelf;
        return $isAutorised;
    }
}
