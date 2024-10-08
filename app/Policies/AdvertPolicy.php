<?php

declare(strict_types = 1);

namespace App\Policies;

use App\Models\Advert;
use App\Models\User;

class AdvertPolicy
{
    public function destroy(User $user, Advert $advert): bool
    {
        return $user->adverts()->where('adverts.id', $advert->id)->exists();
    }

}
