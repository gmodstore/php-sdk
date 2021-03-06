<?php

namespace GmodStore\API\Models;

use GmodStore\API\Model;

class AddonVersion extends Model
{
    /**
     * {@inheritdoc}
     */
    public static $validWithRelations = [
        'addon',
    ];

    /**
     * {@inheritdoc}
     */
    public static $modelRelations = [
        'addon' => Addon::class,
    ];
}
