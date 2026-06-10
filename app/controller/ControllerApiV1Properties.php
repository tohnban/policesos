<?php

namespace App\controller;

use App\model\Property;

class ControllerApiV1Properties
{
    use ApiControllerSupport;

    public function properties(): void
    {
        $apiToken = $this->beginV1Request();
        $this->assertScope($apiToken, 'read:properties');

        $cursorRaw = isset($_GET['cursor']) ? (string) $_GET['cursor'] : '';
        $cursor = $this->decodeCursor($cursorRaw);

        $page = max(1, (int) ($_GET['page'] ?? 1));
        $limit = min(50, max(1, (int) ($_GET['per_page'] ?? 20)));
        $offset = ($page - 1) * $limit;
        $filters = [];

        foreach (['type', 'purpose', 'min_price', 'max_price', 'location', 'country_id', 'region_id', 'keyword'] as $field) {
            if (isset($_GET[$field]) && $_GET[$field] !== '') {
                $filters[$field] = $_GET[$field];
            }
        }

        if ($cursor) {
            $properties = Property::getFilteredCursor(
                $filters,
                $limit,
                isset($cursor['created_at']) ? (string) $cursor['created_at'] : null,
                isset($cursor['id']) ? (int) $cursor['id'] : null
            );
            $total = null;
        } else {
            $properties = Property::getFiltered($filters, $limit, $offset);
            $total = Property::countFiltered($filters);
        }

        $nextCursor = null;
        if (!empty($properties)) {
            $last = $properties[count($properties) - 1];
            $nextCursor = $this->encodeCursor($last['created_at'] ?? null, isset($last['id']) ? (int) $last['id'] : null);
        }

        $this->logApiRequest($apiToken, 'api.properties.list', 200, 'Properties list retrieved');
        $this->respond(true, [
            'page' => $cursor ? null : $page,
            'per_page' => $limit,
            'total' => $total,
            'cursor' => $cursorRaw !== '' ? $cursorRaw : null,
            'next_cursor' => $nextCursor,
            'properties' => $properties,
        ]);
    }

    public function property($id): void
    {
        $apiToken = $this->beginV1Request();
        $this->assertScope($apiToken, 'read:properties');

        $propertyId = (int) $id;
        $property = Property::find($propertyId);
        if (!$property || ($property['status'] ?? '') !== 'disponivel') {
            $this->logApiRequest($apiToken, 'api.property.detail', 404, 'Property not found');
            $this->respond(false, null, 'Property not found', 404);
        }

        $this->logApiRequest($apiToken, 'api.property.detail', 200, 'Property details retrieved');
        $this->respond(true, ['property' => $property]);
    }
}
