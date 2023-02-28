<?php

declare(strict_types=1);

/**
 * Contains the ShippingMethod class.
 *
 * @copyright   Copyright (c) 2022 Attila Fulop
 * @author      Attila Fulop
 * @license     MIT
 * @since       2022-03-26
 *
 */

namespace Vanilo\Shipment\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Collection;
use Konekt\Address\Contracts\Zone;
use Konekt\Address\Models\ZoneProxy;
use Vanilo\Shipment\Contracts\ShippingMethod as ShippingMethodContract;
use Vanilo\Shipment\Traits\BelongsToCarrier;
use Vanilo\Support\Traits\ConfigurableModel;

/**
 * @property int $id
 * @property string $name
 * @property string|null $calculator
 * @property int|null $carrier_id
 * @property int|null $zone_id
 * @property boolean $is_active
 * @property array $configuration
 *
 * @property-read Zone|null $zone
 *
 * @method static Builder actives()
 * @method static Builder inactives()
 * @method static Builder forZone(Zone|int $zone)
 * @method static Builder forZones(array|Collection $zones)
 *
 * @method static ShippingMethod create(array $attributes)
 */
class ShippingMethod extends Model implements ShippingMethodContract
{
    use BelongsToCarrier;
    use ConfigurableModel;

    protected $guarded = ['id', 'created_at', 'updated_at'];

    protected $casts = [
        'configuration' => 'json',
        'is_active' => 'bool',
    ];

    public static function availableOnesForZone(Zone|int $zone): Collection
    {
        return self::forZone($zone)->actives()->get();
    }

    public static function availableOnesForZones(Zone|int ...$zones): Collection
    {
        return self::forZones(array_map(fn (Zone|int $zone) => is_int($zone) ? $zone : $zone->id, $zones))
            ->actives()
            ->get();
    }

    public function zone(): BelongsTo
    {
        return $this->belongsTo(ZoneProxy::modelClass(), 'zone_id', 'id');
    }

    public function scopeActives(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeInactives(Builder $query): Builder
    {
        return $query->where('is_active', false);
    }

    public function scopeForZone(Builder $query, Zone|int $zone): Builder
    {
        return $query->where('zone_id', is_int($zone) ? $zone : $zone->id);
    }

    public function scopeForZones(Builder $query, array|Collection $zones): Builder
    {
        if (is_array($zones)) {
            $zones = array_map(fn (Zone|int $zone) => is_int($zone) ? $zone : $zone->id, $zones);
        } else {
            $zones = $zones->map(fn (Zone $zone) => $zone->id);
        }

        return $query->whereIn('zone_id', $zones);
    }
}
