<?php

namespace App\Repositories;

use App\Models\User;
use Illuminate\Database\ConnectionInterface;

class UserRepository
{
    /**
     * @var ConnectionInterface
     */
    private $db;

    public function __construct()
    {
        $this->db = app('db');
    }

    public function getUser(string $userId)
    {
        return User::all()->where('user_id', $userId)->first();
    }

    public function saveUser(string $userId, string $type)
    {
        User::firstOrCreate([
            'user_id' => $userId,
            'type' => $type
        ]);
    }

    public function updateUser(string $userId, ?bool $isFollowing = null, ?string $displayName = null, ?string $pictureUrl = null)
    {
        $data = [];
        if ($isFollowing !== null) $data['is_following'] = $isFollowing;
        if ($displayName) $data['name'] = $displayName;
        if ($pictureUrl) $data['picture_url'] = $pictureUrl;

        User::updateOrCreate(['user_id' => $userId], $data);
    }
}
