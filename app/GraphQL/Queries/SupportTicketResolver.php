<?php

namespace App\GraphQL\Queries;

use App\Models\SupportTicket;
use App\Models\SupportTicketResponse;
use App\Services\AuthService;
use Illuminate\Support\Facades\Gate;

final class SupportTicketResolver
{
    /**
     * Get a specific support ticket
     */
    public function getSupportTicket($_, array $args)
    {
        $user = AuthService::Auth();
        if (!$user) {
            return [
                'code' => 401,
                'message' => 'Unauthorized',
                'supportTicket' => null
            ];
        }
        
        if (!isset($args['id'])) {
            return [
                'code' => 400,
                'message' => 'Ticket ID is required',
                'supportTicket' => null
            ];
        }
        
        $ticket = SupportTicket::find($args['id']);
        if (!$ticket) {
            return [
                'code' => 404,
                'message' => 'Ticket not found',
                'supportTicket' => null
            ];
        }
        
        // Check if user can view this ticket
        if (Gate::denies('view', $ticket)) {
            return [
                'code' => 403,
                'message' => 'You are not authorized to view this ticket',
                'supportTicket' => null
            ];
        }
        
        return [
            'code' => 200,
            'message' => 'Success',
            'supportTicket' => $ticket
        ];
    }
    
    /**
     * Get support tickets with filtering
     */
    public function getSupportTickets($_, array $args)
    {
        $user = AuthService::Auth();
        if (!$user) {
            return [
                'code' => 401,
                'message' => 'Unauthorized',
                'supportTickets' => []
            ];
        }
        
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
        
        return [
            'code' => 200,
            'message' => 'Success',
            'supportTickets' => $tickets
        ];
    }
    
    /**
     * Get responses for a support ticket
     */
    public function getSupportTicketResponses($_, array $args)
    {
        $user = AuthService::Auth();
        if (!$user) {
            return [
                'code' => 401,
                'message' => 'Unauthorized',
                'supportTicket' => null
            ];
        }
        
        if (!isset($args['ticket_id'])) {
            return [
                'code' => 400,
                'message' => 'Ticket ID is required',
                'supportTicket' => null
            ];
        }
        
        $ticket = SupportTicket::with('responses')->find($args['ticket_id']);
        if (!$ticket) {
            return [
                'code' => 404,
                'message' => 'Ticket not found',
                'supportTicket' => null
            ];
        }
        
        // Check if user can view this ticket
        if (Gate::denies('view', $ticket)) {
            return [
                'code' => 403,
                'message' => 'You are not authorized to view this ticket',
                'supportTicket' => null
            ];
        }
        
        return [
            'code' => 200,
            'message' => 'Success',
            'supportTicket' => $ticket
        ];
    }
}