<?php

namespace IgniterLabs\Shipday\Controllers;

use Admin\Facades\AdminMenu;

/**
 * Deliveries Admin Controller
 */
class Deliveries extends \Admin\Classes\AdminController
{
    public $implement = [
        'Admin\Actions\FormController',
        'Admin\Actions\ListController',
    ];

    public $listConfig = [
        'list' => [
            'model' => 'IgniterLabs\FoodOnline\Models\Delivery',
            'title' => 'Deliveries',
            'emptyMessage' => 'lang:admin::lang.list.text_empty',
            'defaultSort' => ['id', 'DESC'],
            'configFile' => 'delivery',
        ],
    ];

    public $formConfig = [
        'name' => 'Deliveries',
        'model' => 'IgniterLabs\FoodOnline\Models\Delivery',
        'create' => [
            'title' => 'lang:admin::lang.form.create_title',
            'redirect' => 'igniterlabs/foodonline/deliveries/edit/{id}',
            'redirectClose' => 'igniterlabs/foodonline/deliveries',
            'redirectNew' => 'igniterlabs/foodonline/deliveries/create',
        ],

        'edit' => [
            'title' => 'lang:admin::lang.form.edit_title',
            'redirect' => 'igniterlabs/foodonline/deliveries/edit/{id}',
            'redirectClose' => 'igniterlabs/foodonline/deliveries',
            'redirectNew' => 'igniterlabs/foodonline/deliveries/create',
        ],
        'preview' => [
            'title' => 'lang:admin::lang.form.preview_title',
            'redirect' => 'igniterlabs/foodonline/deliveries',
        ],
        'delete' => [
            'redirect' => 'igniterlabs/foodonline/deliveries',
        ],
        'configFile' => 'delivery',
    ];

    protected $requiredPermissions = 'IgniterLabs.FoodOnline.Shipday';

    public function __construct()
    {
        parent::__construct();

        AdminMenu::setContext('delivery', 'sales');
    }
}
