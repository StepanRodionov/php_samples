class Configuration implements ConfigurationInterface
{
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder();
        $rootNode    = $treeBuilder->root('recaptcha');
        $rootNode
            ->children()
                ->arrayNode('view')
                ->addDefaultsIfNotSet()
                    ->children()
                        ->scalarNode('theme')
                            ->defaultValue('light')
                        ->end()
                        ->scalarNode('type')
                            ->defaultValue('image')
                        ->end()
                        ->scalarNode('size')
                            ->defaultValue('normal')
                        ->end()
                        ->scalarNode('tabindex')
                            ->defaultValue(0)
                        ->end()
                        ->scalarNode('callback')
                            ->defaultValue(null)
                        ->end()
                        ->scalarNode('expiredCallback')
                            ->defaultValue(null)
                        ->end()
                        ->scalarNode('bind')
                            ->defaultValue(null)
                        ->end()
                        ->scalarNode('defer')
                            ->defaultValue(true)
                        ->end()
                        ->scalarNode('async')
                            ->defaultValue(true)
                        ->end()
                        ->scalarNode('badge')
                            ->defaultValue(null)
                        ->end()
                    ->end()
                ->end()
            ->end();


        return $treeBuilder;
    }
}

//    Symfony Bundle Configuration
//    Will results following settings:
//    
//    recaptcha:
//      view:
//          theme: light
//          type: image
//          size: normal
//          tabindex: 0
//          callback: null
//          expiredCallback: null
//          bind: null
//          defer: true
//          async: true
//          badge: null
