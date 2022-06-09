<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Project extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $guarded =[];

    protected $casts = [
        'minimisation_setting' => 'json',
    ];

    public function owner()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function users()
    {
        return $this->belongsToMany(
            User::class, ProjectTeam::class
        ) ->withPivot('role')
            ->withTimestamps()
            ->as('membership');
    }

    public function allUsers()
    {
        return $this->users
            ->merge([$this->owner])
            ->sortBy('email');
    }

    public function hasUserWithEmail($email)
    {
        return $this->allUsers()
            ->pluck('email')->contains($email);
    }

    public function getGroupsAttribute(){

        return $this->minimisation_setting['groups'];
    }

    public function isOwnerOrSupperAdmin( $user )
    {
        return $this->supperAdmin()->contains($user);
    }

    public function supperAdmin()
    {

        return $this->users
            ->where('membership.role', 'super_admin')
            ->merge( [$this->owner] )
            ->sortBy('email');
    }

    public function removeUser($user)
    {
        if ($user->current_project_id === $this->id) {
            $user->forceFill([
                'current_project_id' => null,
            ])->save();
        }

        $this->users()->detach($user);

    }

}
