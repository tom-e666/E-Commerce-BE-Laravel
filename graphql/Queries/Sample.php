<?php

namespace App\GraphQL\Queries; // Add this namespace!

class Sample
{

    public function getSample() #root, args,context,resolve info
    {
        return "this is the kind of message we're expecting for";
    }
}
