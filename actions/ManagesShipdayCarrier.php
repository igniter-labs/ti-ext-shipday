<?php

namespace IgniterLabs\Shipday\Actions;

use Exception;
use Igniter\Flame\Database\Model;
use Igniter\Flame\Traits\ExtensionTrait;
use IgniterLabs\Shipday\Classes\Client;
use IgniterLabs\Shipday\Models\Delivery;
use System\Actions\ModelAction;

class ManagesShipdayCarrier extends ModelAction
{
    use ExtensionTrait;

    public function __construct(Model $model)
    {
        parent::__construct($model);

        $this->model->relation['hasOne']['shipday_delivery'] = [Delivery::class, 'delete' => true];
    }

    public function shipdayId()
    {
        return $this->model->shipday_id;
    }

    public function createOrGetShipdayCarrier()
    {
        if ($this->hasShipdayCarrier())
            return $this->asShipdayCarrier();

        return $this->createAsShipdayCarrier();
    }

    public function hasShipdayCarrier()
    {
        return !is_null($this->model->shipday_id);
    }

    public function asShipdayCarrier()
    {
        if (!$this->hasShipdayCarrier()) {
            throw new Exception(class_basename($this).' is not a Shipday carrier yet. See the createAsShipdayCarrier method.');
        }

        return resolve(Client::class)->getCarrier($this->shipdayId());
    }

    public function createAsShipdayCarrier(array $params = [])
    {
        if ($this->hasShipdayCarrier()) {
            throw new Exception(class_basename($this)." is already a Shipday carrier with ID {$this->shipdayId()}.");
        }

        $carrier = resolve(Client::class)->createCarrier([
            'name' => $this->model->full_name,
            'email' => $this->model->email,
            'phoneNumber' => $this->model->telephone,
        ]);

        $this->storeShipdayId($carrier);

        return $carrier;
    }

    protected function storeShipdayId($response)
    {
        if (strlen($response['carrierId'])) {
            $this->model->shipday_id = $response['carrierId'];
            $this->model->save();
        }

        return $this;
    }
}
