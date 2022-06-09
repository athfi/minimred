<?php

namespace App\Models;

use App\RedcapLaravel;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Log;
use Illuminate\Database\Eloquent\SoftDeletes;
use Laravel\Fortify\TwoFactorAuthenticatable;
use Laravel\Jetstream\HasProfilePhoto;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable implements MustVerifyEmail
{
    use HasApiTokens;
    use HasFactory;
    use HasProfilePhoto;
    use Notifiable;
    use TwoFactorAuthenticatable;
    use SoftDeletes;


    protected $fillable = [
        'name',
        'email',
        'password',
    ];

    protected $hidden = [
        'password',
        'remember_token',
        'two_factor_recovery_codes',
        'two_factor_secret',
    ];


    protected $casts = [
        'email_verified_at' => 'datetime',
    ];


    protected $appends = [
        'profile_photo_url',
    ];


    protected $with = ['currentProject'];


    public function switchProject(Project $project)
    {
        if (! $this->belongsToProject($project)) {
            return false;
        }

        $this->forceFill([
            'current_project_id' => $project->id,
        ])->save();

        $this->setRelation('currentTeam', $project);

        return true;
    }


    public function belongsToProject($project)
    {

        return $this->allProjects()->contains(function ($t) use ($project) {
                return $t->id === $project->id;
            }) || $this->ownsProject($project);
    }

    /**
     * Get all of the projects the user belongs to.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function projects()
    {
        return $this->hasManyThrough(
            Project::class,
            ProjectTeam::class,
            'user_id',
            'id',
            'id',
            'project_id');
//        )->withPivot('role')
//            ->withTimestamps()
//            ->as('membership');

        return $this->belongsToMany(
            Project::class,
            ProjectTeam::class, )
            ->withPivot('role')
            ->withTimestamps()
            ->as('membership');
    }

    public function ownedProjects()
    {
        return $this->hasMany(Project::class);

        return $this->hasManyThrough(
            Project::class,
            ProjectTeam::class,
            'user_id',
            'id',
            'id',
            'project_id'
        );
    }

    public function allProjects()
    {
        return $this->ownedProjects->merge($this->projects)->sortBy('name');
    }


    /**
     * Determine if the user owns the given project.
     *
     * @param  mixed  $project
     * @return bool
     */
    public function ownsProject($project)
    {
        if (is_null($project)) {
            return false;
        }

        return $this->id == $project->{$this->getForeignKey()};
    }

    /**
     * Get  current user project that active
     *
     * @return BelongsTo
     */
    public function currentProject()
    {
        return $this->belongsTo(Project::class,'current_project_id');
    }

    public function isCurrentProject(Project $project)
    {
        return $this->current_project_id == $project->id;
    }

    public function projectRole($project)
    {
        if ($this->ownsProject($project)) {
            return 'owner';
        }

        if (! $this->belongsToProject($project)) {
            return;
        }

        $role = $project->users
            ->where('id', $this->id)
            ->first()
            ->membership
            ->role;

        return $role ?? null;
    }


}
