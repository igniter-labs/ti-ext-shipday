<?php

declare(strict_types=1);

namespace IgniterLabs\Shipday\Actions;

use Igniter\Flame\Exception\SystemException;
use Igniter\System\Actions\ModelAction;
use Igniter\User\Models\User;
use IgniterLabs\Shipday\Classes\Client;

/**
 * @property User $model
 */
class ManagesShipdayDriver extends ModelAction
{
    public function __construct(User $model)
    {
        parent::__construct($model);
    }

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
        if ($this->hasShipdayDriver()) {
            return $this->asShipdayDriver();
        }

        return $this->createAsShipdayDriver();
    }

    public function hasShipdayDriver(): bool
    {
        return !is_null($this->model->shipday_id);
    }

    public function assertShipdayDriver(): void
    {
        if (!$this->hasShipdayDriver()) {
            throw new SystemException(sprintf('Staff %s is not a Shipday driver yet. See the createAsShipdayDriver method.', $this->shipdayName()));
        }
    }

    public function asShipdayDriver()
    {
        $response = resolve(Client::class)->getCarrier($this->shipdayEmail());

        $this->storeShipdayId($response);

        return $response;
    }

    public function createAsShipdayDriver(array $params = [])
    {
        throw_if(
            $this->hasShipdayDriver(),
            new SystemException(sprintf('%s is already a Shipday driver with ID %s.', $this->shipdayName(), $this->shipdayId())),
        );

        throw_unless(
            $telephone = $this->shipdayTelephone(),
            new SystemException(sprintf('Staff %s must have a telephone number to create a Shipday driver.', $this->shipdayName())),
        );

        if (is_null(resolve(Client::class)->getCarrier($this->shipdayEmail()))) {
            resolve(Client::class)->createCarrier([
                'name' => $this->shipdayName(),
                'email' => $this->shipdayEmail(),
                'phoneNumber' => $telephone,
            ]);
        }

        return $this->asShipdayDriver();
    }

    protected function storeShipdayId($response): static
    {
        $this->model->shipday_id = array_get($response, 'id');

        if ($this->model->isDirty('shipday_id')) {
            $this->model->saveQuietly();
        }

        return $this;
    }
}
