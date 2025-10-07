<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\AuctionBatch;
use Illuminate\Auth\Access\HandlesAuthorization;

class AuctionBatchPolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:AuctionBatch');
    }

    public function view(AuthUser $authUser, AuctionBatch $auctionBatch): bool
    {
        return $authUser->can('View:AuctionBatch');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:AuctionBatch');
    }

    public function update(AuthUser $authUser, AuctionBatch $auctionBatch): bool
    {
        return $authUser->can('Update:AuctionBatch');
    }

    public function delete(AuthUser $authUser, AuctionBatch $auctionBatch): bool
    {
        return $authUser->can('Delete:AuctionBatch');
    }

    public function restore(AuthUser $authUser, AuctionBatch $auctionBatch): bool
    {
        return $authUser->can('Restore:AuctionBatch');
    }

    public function forceDelete(AuthUser $authUser, AuctionBatch $auctionBatch): bool
    {
        return $authUser->can('ForceDelete:AuctionBatch');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:AuctionBatch');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:AuctionBatch');
    }

    public function replicate(AuthUser $authUser, AuctionBatch $auctionBatch): bool
    {
        return $authUser->can('Replicate:AuctionBatch');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:AuctionBatch');
    }

}