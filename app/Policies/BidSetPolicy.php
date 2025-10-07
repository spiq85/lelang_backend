<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\BidSet;
use Illuminate\Auth\Access\HandlesAuthorization;

class BidSetPolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:BidSet');
    }

    public function view(AuthUser $authUser, BidSet $bidSet): bool
    {
        return $authUser->can('View:BidSet');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:BidSet');
    }

    public function update(AuthUser $authUser, BidSet $bidSet): bool
    {
        return $authUser->can('Update:BidSet');
    }

    public function delete(AuthUser $authUser, BidSet $bidSet): bool
    {
        return $authUser->can('Delete:BidSet');
    }

    public function restore(AuthUser $authUser, BidSet $bidSet): bool
    {
        return $authUser->can('Restore:BidSet');
    }

    public function forceDelete(AuthUser $authUser, BidSet $bidSet): bool
    {
        return $authUser->can('ForceDelete:BidSet');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:BidSet');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:BidSet');
    }

    public function replicate(AuthUser $authUser, BidSet $bidSet): bool
    {
        return $authUser->can('Replicate:BidSet');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:BidSet');
    }

}