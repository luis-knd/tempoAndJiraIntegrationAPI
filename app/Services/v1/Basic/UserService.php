<?php

namespace App\Services\v1\Basic;

use App\Exceptions\UnprocessableException;
use App\Models\v1\Basic\User;
use App\Repository\Interfaces\v1\Basic\UserRepositoryInterface;
use App\Services\ProcessParamsTraits;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use JsonException;

/**
 * Class UserService
 *
 * @package   App\Services\Basic
 * @copyright 06-2024 Lcandesign
 * @author    Luis Candelario <lcandelario@lcandesign.com>
 */
class UserService
{
    use ProcessParamsTraits;

    public function __construct(readonly UserRepositoryInterface $userRepository)
    {
    }

    /**
     *  index
     *
     * @param array $params
     * @return LengthAwarePaginator
     * @throws UnprocessableException
     * @throws JsonException
     */
    public function index(array $params): LengthAwarePaginator
    {
        $users = $this->process($params);
        return $this->userRepository->findByParams(
            $users['filter'],
            $users['with'],
            $users['order'],
            $users['page']
        );
    }

    /**
     * Creates a new User object with the provided parameters and saves it to the database.
     *
     * @param array $params An associative array containing the parameters for the new User object.
     *                      The keys should be the names of the User object's properties.
     * @return User The newly created User object.
     */
    public function make(array $params): User
    {
        $user = new User();
        $this->setParams($params, $user);
        $user->save();
        return $user;
    }

    /**
     * A description of setting parameters for a user.
     *
     * @param array $params The parameters to set for the user
     * @param User  $user   The user object to set the parameters on
     */
    private function setParams(array $params, User $user): void
    {
        $user->name = $params['name'];
        $user->lastname = $params['lastname'];
        $user->email = $params['email'];
        $user->password = $params['password'];
    }

    /**
     * Loads additional data for a given User object based on the provided parameters.
     *
     * @param User  $user   The User object to load data for.
     * @param array $params An optional array of parameters to process and load additional data.
     * @return User The User object with additional data loaded.
     * @throws UnprocessableException
     * @throws JsonException
     */
    public function load(User $user, array $params = []): User
    {
        $users = $this->process($params);
        if ($users['with']) {
            $user->load($users['with']);
        }
        return $user;
    }

    public function update(User $user, array $params): User
    {
        $user->update($params);
        $user->save();
        return $user;
    }

    public function delete(User $user): ?bool
    {
        return $user->delete();
    }
}
