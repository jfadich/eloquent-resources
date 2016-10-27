<?php

namespace jfadich\JsonResponder\Contracts;

use jfadich\JsonResponder\Presenter;

interface Presentable
{
    /**
     * If a property is provided, presenter will parse the property on the model
     * using any additional arguments as options.
     * Otherwise return the presenter.
     *
     * @param $property
     * @param array $options
     * @return mixed
     */
    public function present($property = null, ...$options);

    /**
     * Get an instance of the presenter object for this model.
     *
     * @return Presenter
     */
    public function getPresenter();

    /**
     * Get an array of properties on the model that represent dates.
     *
     * @return array
     */
    public function getDates();
}