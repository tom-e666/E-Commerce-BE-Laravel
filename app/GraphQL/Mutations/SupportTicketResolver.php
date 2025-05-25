<?php
namespace App\GraphQL\Mutations;

use App\Models\SupportTicket;
use App\Models\SupportTicketResponse;
use App\Services\AuthService;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Validator;
use App\GraphQL\Traits\GraphQLResponse;

final class SupportTicketResolver
{

    use GraphQLResponse;
    public function createSupportTicket($_, array $args)
    {
        $user = auth('api')->user();
        
        $validator = Validator::make($args, [
            'subject' => 'required|string|max:255',
            'message' => 'required|string',
        ]);
        
        if ($validator->fails()) {
            return $this->error($validator->errors()->first(), 400);
        }
        
        if (Gate::denies('create', SupportTicket::class)) {
            return $this->error('You are not authorized to create tickets', 403);
        }
        
        $supportTicket = SupportTicket::create([
            'user_id' => $user->id, 
            'subject' => $args['subject'],
            'message' => $args['message'],
            'status' => SupportTicket::STATUS_OPEN,
        ]);

        return $this->success([
            'supportTicket' => $supportTicket,
        ], 'Ticket created successfully', 200);
    }
    
    public function updateSupportTicket($_, array $args)
    {
        $user = auth('api')->user();
        
        if (!isset($args['id'])) {
            return $this->error('Ticket ID is required', 400);
        }
        
        $ticket = SupportTicket::find($args['id']);
        if (!$ticket) {
            return $this->error('Ticket not found', 404);
        }
        
        // Check if user can update this ticket
        if (Gate::denies('update', $ticket)) {
            return $this->error('You are not authorized to update this ticket', 403);
        }
        
        // If status is being updated, check if user can update status
        if (isset($args['status'])) {
            // Validate status
            if (!in_array($args['status'], SupportTicket::VALID_STATUSES)) {
                return $this->error('Invalid status value', 400);
            }
            
            // Check if user can update status
            if (Gate::denies('updateStatus', $ticket)) {
                return $this->error('You are not authorized to update the ticket status', 403);
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

        return $this->success([
            'supportTicket' => $ticket,
        ], 'Ticket updated successfully', 200);
    }
    
    /**
     * Create a response to a support ticket
     */
    public function createSupportTicketResponse($_, array $args)
    {
        $user = auth('api')->user();
        
        $validator = Validator::make($args, [
            'ticket_id' => 'required|integer|exists:support_tickets,id',
            'message' => 'required|string',
            'subject' => 'nullable|string|max:255',
        ]);
        
        if ($validator->fails()) {
            return $this->error($validator->errors()->first(), 400);
        }
        
        // Find the ticket
        $ticket = SupportTicket::find($args['ticket_id']);
        if (!$ticket) {
            return $this->error('Ticket not found', 404);
        }
        
        // Check if user can respond to this ticket
        if (Gate::denies('respond', $ticket)) {
            return $this->error('You are not authorized to respond to this ticket', 403);
        }
        
        // Create the response
        $ticketResponse = SupportTicketResponse::create([
            'ticket_id' => $args['ticket_id'],
            'user_id' => $user->id,
            'subject' => $args['subject'] ?? null,
            'message' => $args['message'],
        ]);
        
        // Update ticket status if staff/admin responds
        if ($user->isAdmin() || $user->isStaff()) {
            $ticket->status = SupportTicket::STATUS_IN_PROGRESS;
            $ticket->save();
        }
        
        return $this->success([
            'supportTicketResponse' => $ticketResponse,
        ], 'Response added successfully', 200);
    }
    
    /**
     * Update an existing support ticket response
     */
    public function updateSupportTicketResponse($_, array $args)
    {
        $user = auth('api')->user();
        
        $validator = Validator::make($args, [
            'id' => 'required|integer|exists:support_ticket_responses,id',
            'message' => 'nullable|string',
            'subject' => 'nullable|string|max:255',
        ]);
        
        if ($validator->fails()) {
            return $this->error($validator->errors()->first(), 400);
        }
        
        // Find the response
        $response = SupportTicketResponse::find($args['id']);
        if (!$response) {
            return $this->error('Response not found', 404);
        }
        
        // Check if user can update this response
        if ($response->user_id !== $user->id && !$user->isAdmin() && !$user->isStaff()) {
            return $this->error('You are not authorized to update this response', 403);
        }
        
        // Update fields
        if (isset($args['message'])) {
            $response->message = $args['message'];
        }
        
        if (isset($args['subject'])) {
            $response->subject = $args['subject'];
        }
        
        $response->save();
        
        return $this->success([
            'supportTicketResponse' => $response,
        ], 'Response updated successfully', 200);
    }
    
    /**
     * Delete a support ticket response
     */
    public function deleteSupportTicketResponse($_, array $args)
    {
        $user = auth('api')->user();
        
        if (!isset($args['id'])) {
            return $this->error('Response ID is required', 400);
        }
        
        $response = SupportTicketResponse::find($args['id']);
        if (!$response) {
            return $this->error('Response not found', 404);
        }
        
        // Check if user can delete this response
        if ($response->user_id !== $user->id && !$user->isAdmin() && !$user->isStaff()) {
            return $this->error('You are not authorized to delete this response', 403);
        }
        
        $response->delete();
        
        return $this->success([
            'supportTicketResponse' => $response,
        ], 'Response deleted successfully', 200);
    }
    
    /**
     * Delete a support ticket
     */
    public function deleteSupportTicket($_, array $args)
    {
        $user = auth('api')->user();
        
        if (!isset($args['id'])) {
            return $this->error('Ticket ID is required', 400);
        }
        
        $ticket = SupportTicket::find($args['id']);
        if (!$ticket) {
            return $this->error('Ticket not found', 404);
        }
        
        // Check if user can delete this ticket
        if (Gate::denies('delete', $ticket)) {
            return $this->error('You are not authorized to delete this ticket', 403);
        }
        
        $ticket->delete();
        
        return $this->success('Ticket deleted successfully', 200, [
            'supportTicket' => $ticket,
        ]);
    }
    
}