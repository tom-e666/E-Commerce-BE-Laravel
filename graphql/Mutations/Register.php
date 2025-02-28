// app/GraphQL/Mutations/Register.php

namespace App\GraphQL\Mutations;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use GraphQL\Error\Error;

class Register
{
public function __invoke($_, array $args)
{
$validator = Validator::make($args, [
'name' => 'required|string|max:255',
'email' => 'required|string|email|max:255|unique:users',
'password' => 'required|string|min:8',
]);

if ($validator->fails()) {
throw new Error($validator->errors()->first());
}

$user = User::create([
'name' => $args['name'],
'email' => $args['email'],
'password' => Hash::make($args['password']),
]);

return $user;
}
}