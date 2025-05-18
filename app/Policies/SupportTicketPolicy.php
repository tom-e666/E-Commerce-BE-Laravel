<?php

namespace App\Policies;

use App\Models\SupportTicket;
use App\Models\UserCredential;

class SupportTicketPolicy
{

    public function viewAny(UserCredential $user): bool
    {
        return $user->isAdmin() || $user->isStaff();
    }

    public function view(UserCredential $user, SupportTicket $ticket): bool
    {
        return $user->id === $ticket->user_id || $user->isAdmin() || $user->isStaff();
    }
    
    public function create(UserCredential $user): bool
    {
        return true;
    }
    
    public function update(UserCredential $user, SupportTicket $ticket): bool
    {
        return $user->id === $ticket->user_id || $user->isAdmin() || $user->isStaff();
    }
    

    public function updateStatus(UserCredential $user, SupportTicket $ticket): bool
    {
        return $user->isAdmin() || $user->isStaff();
    }
    
    public function delete(UserCredential $user, SupportTicket $ticket): bool
    {
        return $user->id === $ticket->user_id || $user->isAdmin() || $user->isStaff();
    }
    

    public function respond(UserCredential $user, SupportTicket $ticket): bool
    {
        return $user->id === $ticket->user_id || $user->isAdmin() || $user->isStaff();
    }
}