<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * is_published controls whether this row is returned to the public site at all.
 * is_verified is a separate editorial flag meaning "a real person said this and consented
 * to it being published" — it is NOT a precondition for is_published. A clearly-worded
 * placeholder (e.g. "a story will appear here once approved") can be published with
 * is_verified left false, because the placeholder text itself makes no unverified claim.
 * What must never happen: a *specific* success story (a name, a claimed trial/scholarship)
 * going out with is_verified false — that content should stay unpublished until confirmed.
 */
class Testimonial extends Model
{
    protected $fillable = ['quote', 'name', 'role', 'image_path', 'is_verified', 'is_published'];

    protected $casts = [
        'is_verified' => 'boolean',
        'is_published' => 'boolean',
    ];
}
