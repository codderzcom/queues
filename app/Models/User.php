<?php

namespace App\Models;

use App\Models\Support\PersistTrait;
use Codderz\Platypus\Exceptions\AuthenticationFailedException;
use Codderz\Platypus\Exceptions\UserExistsException;
use Codderz\Platypus\Infrastructure\Support\GuidBasedImmutableId;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable, PersistTrait;

    protected $keyType = 'string';

    public $incrementing = false;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'id',
        'name',
        'username',
        'phone',
        'password',
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'registration_initiated_at' => 'datetime',
    ];

    public function persistUnique(): static
    {
        try {
            $this->save();
        } catch (UniqueConstraintViolationException $exception) {
            throw new UserExistsException('User with given identity already exists');
        }
        return $this;
    }

    public static function findByCredentialsOrFail(string $username, string $password): static
    {
        $user = static::query()
            ->where('username', $username)
            ->firstOrFail();

        if (!Hash::check($password, $user->password)) {
            throw new AuthenticationFailedException('Unknown credentials');
        }

        return $user;
    }

    public function ownWorkspaces(): HasMany
    {
        return $this->hasMany(Workspace::class, 'keeper_id');
    }

    public function workspaces(): BelongsToMany
    {
        return $this->belongsToMany(Workspace::class, 'relations', 'collaborator_id', 'workspace_id');
    }

    public function cards(): HasMany
    {
        return $this->hasMany(Card::class, 'customer_id');
    }

    public function getWorkspaces(): Collection
    {
        return $this->workspaces()->get();
    }

    public function getWorkspace(string $id): Workspace
    {
        /** @var Workspace $workspace */
        $workspace = $this->workspaces()->where('workspaces.workspace_id', '=', $id)->firstOrFail();
        return $workspace;
    }

    public function getOwnWorkspace(string $id): Workspace
    {
        /** @var Workspace $workspace */
        $workspace = $this->ownWorkspaces()->where('workspaces.workspace_id', '=', $id)->firstOrFail();
        return $workspace;
    }

    public function addWorkspace(array $properties): Workspace
    {
        $properties['workspace_id'] = GuidBasedImmutableId::makeValue();
        /** @var Workspace $workspace */
        $workspace = $this->ownWorkspaces()->create($properties);
        return $workspace;
    }

    public function getCards(): Collection
    {
        return $this->cards()->get();
    }

    public function getCard(string $cardId): Card
    {
        /** @var Card $card */
        $card = $this->cards()->where('card_id', '=', $cardId)->firstOrFail();
        return $card;
    }
}
