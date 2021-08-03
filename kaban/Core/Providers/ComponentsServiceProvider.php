<?php

namespace Kaban\Core\Providers;

use Illuminate\Http\Request;
use Illuminate\Support\ServiceProvider;

class ComponentsServiceProvider extends ServiceProvider {
    public function boot() {
        $componentSections = config( 'components.list' );

        $publish = [];
        /** @var Request $request */
        $request  = app( \Illuminate\Http\Request::class );
        $segments = $request->segments();

        if ( count( $segments ) ) {
            if ( ! in_array( ucfirst( $segments[0] ), [ 'Admin', 'Customer' ] ) ) {
                unset( $componentSections['Admin'] );
//                unset( $componentSections['Customer'] );
//                unset($componentSections['Agency']);
            }
        }

        foreach ( $componentSections as $section => $components ) {
//            include base_path('routes/kaban/' . strtolower($section) . '-routes.php');
            foreach ( $components as $component ) {
                $componentPath = component_path( $component, $section );
                $namespace     = $section . $component;

                //load views
                $componentViewsPath = $componentPath . '/Views';
                if ( file_exists( $componentViewsPath ) ) {
                    $this->loadViewsFrom( $componentViewsPath, $namespace );
                    $publish[ $componentViewsPath ] = resource_path( 'views/vendor/' . $namespace );
                }
            }
        }

//        $this->publishes($publish);

    }

    /**
     * Register the application services.
     *
     * @return void
     */
    public function register() {
    }
}
