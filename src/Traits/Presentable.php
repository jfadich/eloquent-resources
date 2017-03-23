<?php

namespace jfadich\JsonResponder\Traits;

use jfadich\JsonResponder\Presenter;

/**
 * Trait to easily add a presenter to a model
 */
trait Presentable
{
    /**
     * View presenter instance
     *
     * @var mixed
     */
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
        if ($this->presenter === null)
            $this->presenter = $this->resolvePresenterName();

        if (is_string($this->presenter)) {
            if (!class_exists($this->presenter))
                $this->presenter = Presenter::class;

            $this->presenter = new $this->presenter($this);
        }

        return $this->presenter;
    }

    protected function resolvePresenterName()
    {
        $namespaces = config('transformers.namespaces');

        return str_replace($namespaces['models'], $namespaces['presenters'], get_class($this)) . 'Presenter';
    }
}