<?php declare(strict_types=1);

namespace App\GraphQL\Mutations;

final readonly class SupportTicketResolver
{
    /** @param  array{}  $args */
    public function __invoke(null $_, array $args)
    {
        // TODO implement the resolver
    }
    public function createSupportTicket($_, array $args)
    {
        $validator=Validator::make($args,[
            'user_id'=>'required|string',
            'subject'=>'required|string',
            'message'=>'required|string',
        ]);
        if($validator->fails()){
            return [
                'code' => 400,
                'message' => $validator->errors()->first(),
                'support_ticket' => null,
            ];
        }
        $support_ticket = SupportTicket::create([
            'user_id' => $args['user_id'],
            'subject' => $args['subject'],
            'message' => $args['message'],
            'status' => $args['status'],
        ]);
        return [
            'code' => 200,
            'message' => 'success',
            'support_ticket' => $support_ticket,
        ];
    }
    public function updateSupportTicket($_, array $args)
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
        $support_ticket->update([
            'status' => $args['status'] ?? $support_ticket->status,
        ]);
        return [
            'code' => 200,
            'message' => 'success',
            'support_ticket' => $support_ticket,
        ];
    }
    public function updateSupportTicketResponse($_, array $args)
    {
        $validator=Validator::make($args,[
            'ticket_id'=>'required|string',
            'subject'=>'required|string',
            'message'=>'required|string',
        ]);
        if($validator->fails())
        {
            return [
                'code'=>400,
                'message'=>$validator->errors()->first(),
                'support_ticket_response'=>null,
            ];
        }
       $supporTicketResponse = SupportTicketResponse::create([
            'ticket_id' => $args['ticket_id'],
            'subject' => $args['subject'],
            'message' => $args['message'],
        ]);
        return [
            'code' => 200,
            'message' => 'success',
            'support_ticket_response' => $supporTicketResponse,
        ];
    }
    public function updateSupportTicketResponseStatus($_, array $args)
    {
        if(!isset($args['id']))
        {
            return [
                'code'=>400,
                'message'=>'id is required',
                'support_ticket_response'=>null,
            ];
        }
        $support_ticket_response=SupportTicketResponse::find($args['id']);
        if($support_ticket_response===null)
        {
            return [
                'code'=>404,
                'message'=>'Support Ticket Response not found',
                'support_ticket_response'=>null,
            ];
        }
        $support_ticket_response->update([
            'status'=>$args['status']??$support_ticket_response->status,
        ]); 
    }
}
