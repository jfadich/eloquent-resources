<?php

namespace jfadich\EloquentResources\Console;

use Illuminate\Filesystem\Filesystem;

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
     * The type of class being generated
     *
     * @var string
     */
    protected $type = 'Presenter';

    /**
     * Set the base class then call the parent
     *
     * @param Filesystem $files
     */
    public function __construct(Filesystem $files)
    {
        parent::__construct($files);
    }
}