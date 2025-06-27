<?php

namespace App\Contract;

use App\Entity\Chat;
use App\Entity\User;


interface ChatManagementInterface
{

    public function createOrGetExistingChat(User $initiator, User $participant): Chat;

    public function canUserAccessChat(Chat $chat, User $user): bool;
}
