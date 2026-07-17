<?php

namespace App\Policies;

use App\Models\Server;
use App\Models\User;

class ServerPolicy
{
    /**
     * Admins see every server; everyone else only sees their own -
     * used both to gate single-server actions (power, console, files)
     * and to scope server list queries.
     */
    public function view(User $user, Server $server): bool
    {
        return $user->root_admin || $server->owner_id === $user->id;
    }

    public function update(User $user, Server $server): bool
    {
        return $user->root_admin;
    }

    public function delete(User $user, Server $server): bool
    {
        return $user->root_admin;
    }
}
