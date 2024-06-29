<?php

namespace App\Providers;

use App\Repository\Eloquent\Basic\UserRepository;
use App\Repository\Interfaces\Basic\UserRespositoryInterface;
use Illuminate\Support\ServiceProvider;

/**
 * Class RepositoryServiceProvider
 *
 * @package   App\Providers
 * @copyright 06-2024 Lcandesign
 * @author    Luis Candelario <lcandelario@lcandesign.com>
 */
class RepositoryServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(UserRespositoryInterface::class, UserRepository::class);
    }
}
