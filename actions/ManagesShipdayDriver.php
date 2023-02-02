<?php

namespace IgniterLabs\Shipday\Actions;

use Exception;
use Igniter\Flame\Exception\SystemException;
use IgniterLabs\Shipday\Classes\Client;
use System\Actions\ModelAction;

class ManagesShipdayDriver extends ModelAction
{
    public function shipdayId()
    {
        return $this->model->shipday_id;
    }

    public function shipdayEmail()
    {
        return $this->model->staff_email;
    }

    public function shipdayName()
    {
        return $this->model->full_name;
    }

    public function shipdayTelephone()
    {
        return $this->model->telephone;
    }

    public function createOrGetShipdayDriver()
    {
        if ($this->hasShipdayDriver())
            return $this->asShipdayDriver();

        return $this->createAsShipdayDriver();
    }

    public function hasShipdayDriver()
    {
        return !is_null(resolve(Client::class)->getCarrier($this->shipdayEmail()));
    }

    public function assertShipdayDriver()
    {
        if (!$this->hasShipdayDriver())
            throw new SystemException("Staff {$this->shipdayName()} is not a Shipday driver yet. See the createAsShipdayDriver method.");
    }

    public function asShipdayDriver()
    {
        $response = resolve(Client::class)->getCarrier($this->shipdayEmail());

        $this->storeShipdayId($response);

        return $response;
    }

    public function createAsShipdayDriver(array $params = [])
    {
        if ($this->hasShipdayDriver()) {
            throw new Exception("{$this->shipdayName()} is already a Shipday driver with ID {$this->shipdayId()}.");
        }

        resolve(Client::class)->createCarrier([
            'name' => $this->shipdayName(),
            'email' => $this->shipdayEmail(),
            'phoneNumber' => $this->shipdayTelephone(),
        ]);

        return $this->asShipdayDriver();
    }

    protected function storeShipdayId($response)
    {
        $this->model->shipday_id = array_get($response, 'id');

        if ($this->model->isDirty('shipday_id'))
            $this->model->save();

        return $this;
    }
}
