<?php

namespace App\Repository\Eloquent\Basic;

use App\Models\v1\Basic\User;
use App\Repository\Eloquent\BaseRepository;
use App\Repository\Interfaces\Basic\UserRespositoryInterface;

/**
 * Class UserRepository
 *
 * @package   App\Repository\Eloquent\Basic
 * @copyright 06-2024 Lcandesign
 * @author    Luis Candelario <lcandelario@lcandesign.com>
 */
class UserRepository extends BaseRepository implements UserRespositoryInterface
{
    public function __construct(User $user)
    {
        parent::__construct($user);
    }
}
