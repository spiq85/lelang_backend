<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\LotWinner;
use Illuminate\Auth\Access\HandlesAuthorization;

class LotWinnerPolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:LotWinner');
    }

    public function view(AuthUser $authUser, LotWinner $lotWinner): bool
    {
        return $authUser->can('View:LotWinner');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:LotWinner');
    }

    public function update(AuthUser $authUser, LotWinner $lotWinner): bool
    {
        return $authUser->can('Update:LotWinner');
    }

    public function delete(AuthUser $authUser, LotWinner $lotWinner): bool
    {
        return $authUser->can('Delete:LotWinner');
    }

    public function restore(AuthUser $authUser, LotWinner $lotWinner): bool
    {
        return $authUser->can('Restore:LotWinner');
    }

    public function forceDelete(AuthUser $authUser, LotWinner $lotWinner): bool
    {
        return $authUser->can('ForceDelete:LotWinner');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:LotWinner');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:LotWinner');
    }

    public function replicate(AuthUser $authUser, LotWinner $lotWinner): bool
    {
        return $authUser->can('Replicate:LotWinner');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:LotWinner');
    }

}