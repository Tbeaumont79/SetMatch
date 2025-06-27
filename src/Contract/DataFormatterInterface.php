<?php

namespace App\Contract;

use App\Entity\User;
use App\Entity\Message;


interface DataFormatterInterface
{

    public function formatUserForApi(User $user): array;


    public function formatMessageForApi(Message $message, User $currentUser): array;


    public function extractUsernameFromEmail(string $email): string;
}
