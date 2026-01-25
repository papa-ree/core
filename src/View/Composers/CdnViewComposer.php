<?php

namespace Bale\Core\View\Composers;

use Bale\Core\Support\Cdn;
use Illuminate\View\View;

class CdnViewComposer
{
    /**
     * The CDN instance.
     *
     * @var \Bale\Core\Support\Cdn
     */
    protected $cdn;

    /**
     * Create a new CDN view composer.
     */
    public function __construct(Cdn $cdn)
    {
        $this->cdn = $cdn;
    }

    /**
     * Bind data to the view.
     *
     * @param  \Illuminate\View\View  $view
     * @return void
     */
    public function compose(View $view)
    {
        $view->with('cdn', $this->cdn);
    }
}
