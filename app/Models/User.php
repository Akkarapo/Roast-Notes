<?php

namespace App\Models;

use App\Enums\BrewMethod;
use App\Enums\Grind;
use App\Enums\RoastLevel;
use App\Enums\UserRole;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Collection;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'phone',
        'address',
    ];

    /**
     * Default attribute values (mirrors the DB default so new models know their role before refresh).
     *
     * @var array<string, mixed>
     */
    protected $attributes = [
        'role' => 'member',
        'member_tier_id' => MemberTier::BEAN,
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'role' => UserRole::class,
            'preferred_roast_level' => RoastLevel::class,
            'preferred_brew_method' => BrewMethod::class,
            'taste_quiz_completed_at' => 'datetime',
        ];
    }

    public function isAdmin(): bool
    {
        return $this->role === UserRole::Admin;
    }

    /** Top-level flavor categories the member likes (Taste Quiz question 1). */
    public function tastePreferences(): BelongsToMany
    {
        return $this->belongsToMany(FlavorCategory::class, 'user_taste_preferences');
    }

    public function hasTasteProfile(): bool
    {
        return $this->taste_quiz_completed_at !== null && $this->tastePreferences->isNotEmpty();
    }

    /** @return Collection<int, int> ids of liked top-level flavor categories */
    public function likedCategoryIds()
    {
        return $this->tastePreferences->pluck('id');
    }

    /** Grind preselected when adding beans to the cart. */
    public function defaultGrind(): Grind
    {
        return $this->preferred_brew_method?->defaultGrind() ?? Grind::WholeBean;
    }

    public function memberTier(): BelongsTo
    {
        return $this->belongsTo(MemberTier::class);
    }

    /** Products the member saved for later. */
    public function wishlist(): BelongsToMany
    {
        return $this->belongsToMany(Product::class, 'wishlists')->withPivot('created_at');
    }

    /** "สมชาย ใจดี" → "สมชาย ใ." (public name on reviews) */
    public function shortName(): string
    {
        $parts = preg_split('/\s+/u', trim($this->name));

        return count($parts) > 1 ? $parts[0].' '.mb_substr($parts[1], 0, 1).'.' : $parts[0];
    }

    public function cart(): HasOne
    {
        return $this->hasOne(Cart::class);
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    /** The member's cart, created on first use. */
    public function cartOrCreate(): Cart
    {
        return $this->cart()->firstOrCreate();
    }
}
