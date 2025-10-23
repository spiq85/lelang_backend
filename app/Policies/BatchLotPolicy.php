<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\BatchLot;
use Illuminate\Auth\Access\HandlesAuthorization;

class BatchLotPolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:BatchLot');
    }

    public function view(AuthUser $authUser, BatchLot $batchLot): bool
    {
        return $authUser->can('View:BatchLot');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:BatchLot');
    }

    public function update(AuthUser $authUser, BatchLot $batchLot): bool
    {
        return $authUser->can('Update:BatchLot');
    }

    public function delete(AuthUser $authUser, BatchLot $batchLot): bool
    {
        return $authUser->can('Delete:BatchLot');
    }

    public function restore(AuthUser $authUser, BatchLot $batchLot): bool
    {
        return $authUser->can('Restore:BatchLot');
    }

    public function forceDelete(AuthUser $authUser, BatchLot $batchLot): bool
    {
        return $authUser->can('ForceDelete:BatchLot');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:BatchLot');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:BatchLot');
    }

    public function replicate(AuthUser $authUser, BatchLot $batchLot): bool
    {
        return $authUser->can('Replicate:BatchLot');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:BatchLot');
    }

}