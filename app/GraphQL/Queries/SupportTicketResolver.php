<?php

namespace App\GraphQL\Queries;

use App\Models\SupportTicket;
use App\Models\SupportTicketResponse;
use App\Services\AuthService;
use Illuminate\Support\Facades\Gate;
use App\GraphQL\Traits\GraphQLResponse;

final class SupportTicketResolver
{
    use GraphQLResponse;
    /**
     * Get a specific support ticket
     */
    public function getSupportTicket($_, array $args)
    {
        $user = auth('api')->user();
        
        if (!isset($args['id'])) {
            return $this->error('Ticket ID is required', 400);
        }
        
        $ticket = SupportTicket::find($args['id']);
        if (!$ticket) {
            return $this->error('Ticket not found', 404);
        }
        
        // Check if user can view this ticket
        if (Gate::denies('view', $ticket)) {
            return $this->error('You are not authorized to view this ticket', 403);
        }
        
        return $this->success('Ticket retrieved successfully', 200, [
            'supportTicket' => $ticket,
        ]);
    }
    
    /**
     * Get support tickets with filtering
     */
    public function getSupportTickets($_, array $args)
    {
        $user = auth('api')->user();
        
        // Initialize query
        $query = SupportTicket::query();
        
        // Apply user filter based on roles
        if ($user->isAdmin() || $user->isStaff()) {
            // Admin/staff can filter by user_id if provided
            if (isset($args['user_id'])) {
                $query->where('user_id', $args['user_id']);
            }
        } else {
            // Regular users can only see their own tickets
            $query->where('user_id', $user->id);
        }
        
        // Apply additional filters
        if (isset($args['status']) && in_array($args['status'], SupportTicket::VALID_STATUSES)) {
            $query->where('status', $args['status']);
        }
        
        if (isset($args['created_after'])) {
            $query->where('created_at', '>=', $args['created_after']);
        }
        
        if (isset($args['created_before'])) {
            $query->where('created_at', '<=', $args['created_before']);
        }
        
        // Get tickets
        $tickets = $query->get();

        return $this->success('Tickets retrieved successfully', 200, [
            'supportTickets' => $tickets,
        ]);
    }
    
    /**
     * Get responses for a support ticket
     */
    public function getSupportTicketResponses($_, array $args)
    {
        $user = auth('api')->user();
        
        if (!isset($args['ticket_id'])) {
            return $this->error('Ticket ID is required', 400);
        }
        
        $ticket = SupportTicket::with('responses')->find($args['ticket_id']);
        if (!$ticket) {
            return $this->error('Ticket not found', 404);
        }
        
        // Check if user can view this ticket
        if (Gate::denies('view', $ticket)) {
            return $this->error('You are not authorized to view this ticket', 403);
        }
        
        return $this->success('Responses retrieved successfully', 200, [
            'supportTicket' => $ticket,
        ]);
    }
}