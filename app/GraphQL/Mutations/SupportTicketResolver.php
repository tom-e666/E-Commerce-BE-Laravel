<?php
namespace App\GraphQL\Mutations;

use App\Models\SupportTicket;
use App\Models\SupportTicketResponse;
use App\Services\AuthService;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Validator;

final class SupportTicketResolver
{

    public function createSupportTicket($_, array $args)
    {
        $user = AuthService::Auth();
        if (!$user) {
            return [
                'code' => 401,
                'message' => 'Unauthorized',
                'supportTicket' => null
            ];
        }
        
        $validator = Validator::make($args, [
            'subject' => 'required|string|max:255',
            'message' => 'required|string',
        ]);
        
        if ($validator->fails()) {
            return [
                'code' => 400,
                'message' => $validator->errors()->first(),
                'supportTicket' => null,
            ];
        }
        
        if (Gate::denies('create', SupportTicket::class)) {
            return [
                'code' => 403,
                'message' => 'You are not authorized to create tickets',
                'supportTicket' => null,
            ];
        }
        
        $supportTicket = SupportTicket::create([
            'user_id' => $user->id, 
            'subject' => $args['subject'],
            'message' => $args['message'],
            'status' => SupportTicket::STATUS_OPEN,
        ]);
        
        return [
            'code' => 200,
            'message' => 'Ticket created successfully',
            'supportTicket' => $supportTicket,
        ];
    }
    
    public function updateSupportTicket($_, array $args)
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
        
        // Check if user can update this ticket
        if (Gate::denies('update', $ticket)) {
            return [
                'code' => 403,
                'message' => 'You are not authorized to update this ticket',
                'supportTicket' => null
            ];
        }
        
        // If status is being updated, check if user can update status
        if (isset($args['status'])) {
            // Validate status
            if (!in_array($args['status'], SupportTicket::VALID_STATUSES)) {
                return [
                    'code' => 400,
                    'message' => 'Invalid status value',
                    'supportTicket' => null
                ];
            }
            
            // Check if user can update status
            if (Gate::denies('updateStatus', $ticket)) {
                return [
                    'code' => 403,
                    'message' => 'Only administrators can update ticket status',
                    'supportTicket' => null
                ];
            }
            
            $ticket->status = $args['status'];
        }
        
        // Update other fields
        if (isset($args['subject'])) {
            $ticket->subject = $args['subject'];
        }
        
        if (isset($args['message'])) {
            $ticket->message = $args['message'];
        }
        
        $ticket->save();
        
        return [
            'code' => 200,
            'message' => 'Ticket updated successfully',
            'supportTicket' => $ticket
        ];
    }
    
    /**
     * Create a response to a support ticket
     */
    public function updateSupportTicketResponse($_, array $args)
    {
        $user = AuthService::Auth();
        if (!$user) {
            return [
                'code' => 401,
                'message' => 'Unauthorized',
                'supportTicket' => null
            ];
        }
        
        $validator = Validator::make($args, [
            'ticket_id' => 'required|string',
            'message' => 'required|string',
            'subject' => 'required|string',
        ]);
        
        if ($validator->fails()) {
            return [
                'code' => 400,
                'message' => $validator->errors()->first(),
                'supportTicket' => null,
            ];
        }
        
        // Find the ticket
        $ticket = SupportTicket::find($args['ticket_id']);
        if (!$ticket) {
            return [
                'code' => 404,
                'message' => 'Ticket not found',
                'supportTicket' => null
            ];
        }
        
        // Check if user can respond to this ticket
        if (Gate::denies('respond', $ticket)) {
            return [
                'code' => 403,
                'message' => 'You are not authorized to respond to this ticket',
                'supportTicket' => null
            ];
        }
        
        // Create the response
        $ticketResponse = SupportTicketResponse::create([
            'ticket_id' => $args['ticket_id'],
            'user_id' => $user->id, // Add the authenticated user
            'subject' => $args['subject'],
            'message' => $args['message'],
        ]);
        
        // Update ticket status if staff/admin responds
        if ($user->isAdmin() || $user->isStaff()) {
            $ticket->status = SupportTicket::STATUS_IN_PROGRESS;
            $ticket->save();
        }
        
        return [
            'code' => 200,
            'message' => 'Response added successfully',
            'supportTicket' => $ticket, // Return the ticket with the response
        ];
    }
    
    /**
     * Delete a support ticket
     */
    public function deleteSupportTicket($_, array $args)
    {
        $user = AuthService::Auth();
        if (!$user) {
            return [
                'code' => 401,
                'message' => 'Unauthorized',
            ];
        }
        
        if (!isset($args['id'])) {
            return [
                'code' => 400,
                'message' => 'Ticket ID is required',
            ];
        }
        
        $ticket = SupportTicket::find($args['id']);
        if (!$ticket) {
            return [
                'code' => 404,
                'message' => 'Ticket not found',
            ];
        }
        
        // Check if user can delete this ticket
        if (Gate::denies('delete', $ticket)) {
            return [
                'code' => 403,
                'message' => 'You are not authorized to delete this ticket',
            ];
        }
        
        $ticket->delete();
        
        return [
            'code' => 200,
            'message' => 'Ticket deleted successfully',
        ];
    }
}