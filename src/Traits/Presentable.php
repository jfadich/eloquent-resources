<?php

namespace jfadich\EloquentResources\Traits;

/**
 * Trait to easily add a presenter to a model
 */
trait Presentable
{
    protected $presenter;
    
    /**
     * If a property is provided, presenter will parse the property on the model
     * using any additional arguments as options.
     * Otherwise return the presenter.
     *
     * @param $property
     * @param array $options
     * @return mixed
     */
    public function present($property = null, ...$options)
    {
        $presenter = $this->getPresenter();

        if($property !== null)
            return call_user_func_array([$presenter, $property], $options );

        return $presenter;
    }

    /**
     * Get an instance of the presenter object for this model.
     *
     * @return mixed|string
     */
    public function getPresenter()
    {
        if (!isset($this->presenter) || $this->presenter === null)
            $this->presenter = $this->resolvePresenterName();

        if (is_string($this->presenter)) {
            if (!class_exists($this->presenter))
                $this->presenter = config('resources.classes.presenter');

            $this->presenter = new $this->presenter($this);
        }

        return $this->presenter;
    }

    /**
     * Get the name of the presenter class from the model namespace
     *
     * @return string
     */
    protected function resolvePresenterName()
    {
        $namespaces = config('resources.namespaces');

        return str_replace($namespaces['models'], $namespaces['presenters'], get_class($this)) . 'Presenter';
    }
}
