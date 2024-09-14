<?php

declare(strict_types = 1);

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\Pivot;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use OpenApi\Annotations as OA;

/**
 * @OA\Schema(@OA\Xml(name="Source"))
 */
class Source extends Pivot
{
    use HasFactory;

    /**
     * @OA\Property(format="int64")
     *
     * @var int
     */
    public int $id;

    /**
     * Subscription URL
     *
     * @var string
     *
     * @OA\Property(@OA\Xml(name='url', wrapped=true))
     */
    public string $url;

    /**
     * Announce product name
     *
     * @var string
     *
     * @OA\Property(@OA\Xml(name='name', wrapped=true))
     */
    public string $name;

    /**
     * Email for notifications
     *
     * @var string
     *
     * @OA\Property(@OA\Xml(name='email', wrapped=true))
     */
    public string $email;

    /**
     * Product price
     *
     * @var int
     *
     * @OA\Property(@OA\Xml(name='price', wrapped=true))
     */
    public int $price;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'url',
        'name',
        'email',
        'price',
    ];

    protected function casts(): array
    {
        return [
            'url' => 'string',
            'name' => 'string',
            'email' => 'string',
            'price' => 'integer',
        ];
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class);
    }

}
