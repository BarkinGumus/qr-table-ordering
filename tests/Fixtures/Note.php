<?php

namespace Tests\Fixtures;

use App\Tenancy\BelongsToRestaurant;
use Illuminate\Database\Eloquent\Model;

/**
 * A stand-in tenant model, used to test the tenancy rules before the real
 * tenant tables (tables, menu, orders) exist.
 */
class Note extends Model
{
    use BelongsToRestaurant;

    protected $table = 'test_notes';

    protected $guarded = [];

    public $timestamps = false;
}
