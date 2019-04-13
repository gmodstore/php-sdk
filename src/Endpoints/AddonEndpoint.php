<?php

namespace GmodStore\API\Endpoints;

use GmodStore\API\Collection;
use GmodStore\API\Endpoint;
use GmodStore\API\Models\Addon;
use GmodStore\API\Models\AddonVersion;
use GmodStore\API\Models\Coupon;
use GmodStore\API\Models\Purchase;

class AddonEndpoint extends Endpoint
{
    public static $endpointPath = 'addons';

    public static $model = Addon::class;

    public function get($id = null)
    {
        $data = parent::get($id);
        $data = $data['data'] ?? [];

        $model = new Collection();

        if (empty($this->id) && !empty($data)) {
            foreach ($data as $addon) {
                $model[] = new self::$model($addon, $this);
            }
        }

        if (!empty($this->id)) {
            $model = new self::$model($data, $this);
        }

        return $model;
    }

    /**
     * Get an Addon's coupons.
     *
     * @param null $id
     *
     * @return array|\GmodStore\API\Collection
     * @throws \GmodStore\API\Exceptions\EndpointException
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function getCoupons($id = null)
    {
        return $this->getGeneralSubEndpoint($id, 'coupons', Coupon::class);
    }

    /**
     * Get an Addon's purchases.
     *
     * @param null $id
     *
     * @return array|\GmodStore\API\Collection
     * @throws \GmodStore\API\Exceptions\EndpointException
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function getPurchases($id = null)
    {
        return $this->getGeneralSubEndpoint($id, 'reviews');
    }

    /**
     * Get an Addon's reviews.
     *
     * @param null $id
     *
     * @return array|\GmodStore\API\Collection
     * @throws \GmodStore\API\Exceptions\EndpointException
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function getReviews($id = null)
    {
        return $this->getGeneralSubEndpoint($id, 'purchases', Purchase::class);
    }

    /**
     * Get an Addon's versions.
     *
     * @param null $id
     *
     * @return array|\GmodStore\API\Collection
     * @throws \GmodStore\API\Exceptions\EndpointException
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function getVersions($id = null)
    {
        return $this->getGeneralSubEndpoint($id, 'versions', AddonVersion::class);
    }
}
