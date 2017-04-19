<?php

namespace jfadich\EloquentResources;

use jfadich\EloquentResources\Contracts\Presentable;

/**
 * Base presenter class to manage calling property presenter methods on the model
 */
class Presenter
{
    /**
     * Model to present data from
     *
     * @var Presentable
     */
    protected $model;

    /**
     * Default format for dates
     *
     * @var string
     */
    protected $dateFormat = 'Y-m-d H:i:s';

    /**
     * @param Presentable $model
     */
    function __construct(Presentable $model)
    {
        $this->model = $model;
    }

    /**
     * When a property is referenced search for the associated method on the presenter
     * If it doesn't exist defer to the model
     *
     * @param string $property , [...]
     * @param array $options
     * @return mixed
     */
    public function present($property, ...$options)
    {
        if (method_exists($this, $property)) {
            return call_user_func_array([$this, $property], $options);
        }

        if (in_array( $property, $this->model->getDates() )) {
            array_unshift($property,$options);
            return call_user_func_array([$this, 'presentDate'], $options );
        }

        return $this->model->{$property};
    }

    /**
     * Present the date on the model in the given format
     *
     * @param $dateField
     * @param null $format
     * @return mixed
     */
    public function presentDate($dateField, $format = null, $default = null)
    {
        if ( $format === null )
            $format = $this->dateFormat;

        if (($date = $this->model->{$dateField}) === null || $date->timestamp <= 0)
            return $default;

        if($format === 'timestamp')
            return $date->timestamp;

        if($format === 'diff' || $format === 'diffForHumans')
            return $date->diffForHumans();

        return $date->format( $format );
    }

    /**
     * If the model ID is a UUID, convert the binary representation into something that can be displayed
     *
     * @return mixed
     */
    public function id()
    {
        if(!ctype_print($this->model->id))
            return bin2hex($this->model->id);

        return $this->model->id;
    }

    /**
     * Magic shortcut to present($property)
     *
     * @param string $property
     * @return mixed
     */
    public function __get($property)
    {
        return $this->present($property);
    }

    /**
     * If a property is referenced and there is no presenter method, check if the property is a date
     * if so sent it to presentDate()
     *
     * @param $method
     * @param $arguments
     * @return mixed
     */
    public function __call($method, $arguments)
    {
        array_unshift($arguments, $method);

        if (in_array($method, $this->model->getDates())) {
            return call_user_func_array([$this, 'presentDate'], $arguments);
        }

        return call_user_func_array([$this, 'present'], $arguments);
    }
}