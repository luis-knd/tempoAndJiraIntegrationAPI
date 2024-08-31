<?php

namespace App\Services\v1\Tempo;

use App\Exceptions\UnprocessableException;
use App\Models\v1\Tempo\TempoUser;
use App\Repository\Interfaces\v1\Tempo\TempoUserRepositoryInterface;
use App\Services\ProcessParamsTraits;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

/**
 * Class TempoUserService
 *
 * @package   App\Services\v1\Tempo
 * @copyright 08-2024 Lcandesign
 * @author    Luis Candelario <lcandelario@lcandesign.com>
 */
class TempoUserService
{
    use ProcessParamsTraits;

    public function __construct(readonly TempoUserRepositoryInterface $tempoUserRepository)
    {
    }

    /**
     * Retrieves a paginated list of TempoUser objects based on provided parameters.
     *
     * @param array $params The parameters to filter and sort the list of TempoUsers.
     * @return LengthAwarePaginator The paginated list of TempoUser objects.
     * @throws UnprocessableException
     */
    public function index(array $params): LengthAwarePaginator
    {
        $users = $this->process($params);
        return $this->tempoUserRepository->findByParams(
            $users['filter'],
            $users['with'],
            $users['order'],
            $users['page']
        );
    }

    /**
     * Creates a new TempoUser object with the provided parameters and saves it to the database.
     *
     * @param array $params An associative array containing the parameters for the new TempoUser object.
     *                      The keys should be the names of the TempoUser object's properties.
     * @return TempoUser The newly created TempoUser object.
     */
    public function make(array $params): TempoUser
    {
        $tempoUser = new TempoUser();
        $this->setParams($params, $tempoUser);
        $tempoUser->save();
        return $tempoUser;
    }

    /**
     *  A description of setting parameters for a TempoUser.
     *
     * @param array     $params    The parameters to set for the TempoUser
     * @param TempoUser $tempoUser The TempoUser object to set the parameters on
     * @return void
     */
    private function setParams(array $params, TempoUser $tempoUser): void
    {
        $tempoUser->tempo_user_id = $params['tempo_user_id'];
        $tempoUser->name = $params['name'];
        $tempoUser->email = $params['email'];
    }

    /**
     * Loads additional data for a given TempoUser object based on the provided parameters.
     *
     * @param TempoUser $tempoUser The TempoUser object to load data for.
     * @param array     $params    An optional array of parameters to process and load additional data.
     * @return TempoUser The TempoUser object with additional data loaded.
     * @throws UnprocessableException
     */
    public function load(TempoUser $tempoUser, array $params = []): TempoUser
    {
        $users = $this->process($params);
        if ($users['with']) {
            $tempoUser->load($users['with']);
        }
        return $tempoUser;
    }

    /**
     * Updates an existing TempoUser object with the provided parameters and saves the changes.
     *
     * @param TempoUser $tempoUser The TempoUser object to update.
     * @param array     $params    The parameters to update the TempoUser object with.
     * @return TempoUser The updated TempoUser object.
     */
    public function update(TempoUser $tempoUser, array $params): TempoUser
    {
        $tempoUser->update($params);
        $tempoUser->save();
        return $tempoUser;
    }

    /**
     * Deletes the given TempoUser object from the database.
     *
     * @param TempoUser $tempoUser The TempoUser object to delete.
     * @return bool|null True if the deletion was successful, null otherwise.
     */
    public function delete(TempoUser $tempoUser): ?bool
    {
        return $tempoUser->delete();
    }
}
