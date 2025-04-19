<?php declare(strict_types=1);

namespace App\GraphQL\Queries;
use App\Models\SupportTicket;
use App\Models\SupportTicketResponse;


final readonly class SupportTicketResolver
{
    /** @param  array{}  $args */
    public function __invoke(null $_, array $args)
    {
        // TODO implement the resolver
    }
    public function getSupportTicket($_, array $args)
    {
        if(!isset($args['id'])){
            return [
                'code' => 400,
                'message' => 'id is required',
                'support_ticket' => null,
            ];
        }
        $support_ticket = SupportTicket::find($args['id']);
        if ($support_ticket === null) {
            return [
                'code' => 404,
                'message' => 'Support Ticket not found',
                'support_ticket' => null,
            ];
        }
        return [
            'code' => 200,
            'message' => 'success',
            'support_ticket' => $support_ticket,
        ];
    }
    public function getSupportTickets($_, array $args)
    {

        $query= SupportTicket::query();
        if(isset($args['user_id'])){
            $query->where('user_id',$args['user_id']);
        }
        if(isset($args['created_after']))
        {
            $query->where('created_at','>',$args['created_after']);
        }
        if(isset($args['created_before']))
        {
            $query->where('created_at','<',$args['created_before']);
        }
        if(isset($args['status']))
        {
            $query->where('status',$args['status']);
        }
        $support_tickets = $query->get();
        return [
            'code' => 200,
            'message' => 'success',
            'support_tickets' => $support_tickets,
        ];
    }
    public function getSupportTicketResponses($_, array $args)
    {
        if(!isset($args['ticket_id'])){
            return [
                'code' => 400,
                'message' => 'ticket_id is required',
                'support_ticket'=>null,
                'responses' => null,
            ];
        }
        $support_ticket = SupportTicket::find($args['ticket_id']);
        if ($support_ticket === null) {
            return [
                'code' => 404,
                'message' => 'Support Ticket not found',
                'support_ticket' => null,
                'responses' => null,
            ];
        }
        $support_ticket_responses = $support_ticket->response()->get();
        return [
            'code' => 200,
            'message' => 'success',
            'support_ticket' => $support_ticket,
            'responses' => $support_ticket_responses,
        ];
    }

}
