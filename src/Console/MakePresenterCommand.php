<?php

namespace jfadich\JsonResponder\Console;

use jfadich\JsonResponder\Presenter;

/**
 * Command to generate a new presenter class
 */
class MakePresenterCommand extends GeneratorCommand
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $name = 'make:presenter';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a new Presenter class';

    /**
     * Base class the the generated class extends.
     *
     * @var string
     */
    protected $parentClass = Presenter::class;
}