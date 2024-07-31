<?php
require __DIR__ . '/../src/bootstrap.php';

$config_filename = APPLICATION_PATH . 'conf/config.php';

require APPLICATION_PATH . "load-translation.php";

$config = require $config_filename;


$config['app.lcode'] = $lcode;

if(($_SERVER['CONTENT_TYPE'] ?? '') == 'application/json') {
    $json = file_get_contents('php://input');
    $decoded = json_decode($json, true);
    if($decoded) {
        $_POST = $decoded;
    }
}
// create the App instance
$app = MapasCulturais\App::i('web');
$app->init($config);

// use Doctrine\ORM\ORMSetup;
// use Doctrine\ORM\EntityManager;
// use Doctrine\DBAL\DriverManager;
// use Doctrine\Common\Annotations\AnnotationReader;

// // annotation driver
// $doctrine_config = ORMSetup::createAnnotationMetadataConfiguration(
//     paths: [dirname(__DIR__,1) . '/Entities/'],
//     isDevMode: (bool) $config['doctrine.isDev'],
//     // cache: $cache->adapter
// );

// // tells the doctrine to ignore hook annotation.
// AnnotationReader::addGlobalIgnoredName('hook');

// $doctrine_config->setProxyDir(DOCTRINE_PROXIES_PATH);
// $doctrine_config->setProxyNamespace('MapasCulturais\DoctrineProxies');

// /** DOCTRINE2 SPATIAL */

// $doctrine_config->addCustomStringFunction('st_asbinary', 'CrEOF\Spatial\ORM\Query\AST\Functions\PostgreSql\STAsBinary');
// $doctrine_config->addCustomStringFunction('st_astext', 'CrEOF\Spatial\ORM\Query\AST\Functions\PostgreSql\STAsText');
// $doctrine_config->addCustomNumericFunction('st_area', 'CrEOF\Spatial\ORM\Query\AST\Functions\PostgreSql\STArea');
// $doctrine_config->addCustomStringFunction('st_centroid', 'CrEOF\Spatial\ORM\Query\AST\Functions\PostgreSql\STCentroid');
// $doctrine_config->addCustomStringFunction('st_closestpoint', 'CrEOF\Spatial\ORM\Query\AST\Functions\PostgreSql\STClosestPoint');
// $doctrine_config->addCustomNumericFunction('st_contains', 'CrEOF\Spatial\ORM\Query\AST\Functions\PostgreSql\STContains');
// $doctrine_config->addCustomNumericFunction('st_containsproperly', 'CrEOF\Spatial\ORM\Query\AST\Functions\PostgreSql\STContainsProperly');
// $doctrine_config->addCustomNumericFunction('st_covers', 'CrEOF\Spatial\ORM\Query\AST\Functions\PostgreSql\STCovers');
// $doctrine_config->addCustomNumericFunction('st_coveredby', 'CrEOF\Spatial\ORM\Query\AST\Functions\PostgreSql\STCoveredBy');
// $doctrine_config->addCustomNumericFunction('st_crosses', 'CrEOF\Spatial\ORM\Query\AST\Functions\PostgreSql\STCrosses');
// $doctrine_config->addCustomNumericFunction('st_disjoint', 'CrEOF\Spatial\ORM\Query\AST\Functions\PostgreSql\STDisjoint');
// $doctrine_config->addCustomNumericFunction('st_distance', 'CrEOF\Spatial\ORM\Query\AST\Functions\PostgreSql\STDistance');
// $doctrine_config->addCustomStringFunction('st_geomfromtext', 'CrEOF\Spatial\ORM\Query\AST\Functions\PostgreSql\STGeomFromText');
// $doctrine_config->addCustomNumericFunction('st_length', 'CrEOF\Spatial\ORM\Query\AST\Functions\PostgreSql\STLength');
// $doctrine_config->addCustomNumericFunction('st_linecrossingdirection', 'CrEOF\Spatial\ORM\Query\AST\Functions\PostgreSql\STLineCrossingDirection');
// $doctrine_config->addCustomStringFunction('st_startpoint', 'CrEOF\Spatial\ORM\Query\AST\Functions\PostgreSql\STStartPoint');
// $doctrine_config->addCustomStringFunction('st_summary', 'CrEOF\Spatial\ORM\Query\AST\Functions\PostgreSql\STSummary');


// $doctrine_config->addCustomStringFunction('string_agg', 'MapasCulturais\DoctrineMappings\Functions\StringAgg');
// $doctrine_config->addCustomStringFunction('unaccent', 'MapasCulturais\DoctrineMappings\Functions\Unaccent');
// $doctrine_config->addCustomStringFunction('recurring_event_occurrence_for', 'MapasCulturais\DoctrineMappings\Functions\RecurringEventOcurrenceFor');
// $doctrine_config->addCustomNumericFunction('CAST', 'MapasCulturais\DoctrineMappings\Functions\Cast');
// $doctrine_config->addCustomStringFunction('CAST', 'MapasCulturais\DoctrineMappings\Functions\Cast');

// $doctrine_config->addCustomNumericFunction('st_dwithin', 'MapasCulturais\DoctrineMappings\Functions\STDWithin');
// $doctrine_config->addCustomStringFunction('st_envelope', 'MapasCulturais\DoctrineMappings\Functions\STEnvelope');
// $doctrine_config->addCustomNumericFunction('st_within', 'MapasCulturais\DoctrineMappings\Functions\STWithin');
// $doctrine_config->addCustomNumericFunction('st_makepoint', 'MapasCulturais\DoctrineMappings\Functions\STMakePoint');

// // $metadata_cache_adapter = new \Symfony\Component\Cache\Adapter\PhpFilesAdapter();
// // $doctrine_config->setMetadataCache($metadata_cache_adapter);
// // $doctrine_config->setQueryCache($mscache->adapter);
// // $doctrine_config->setResultCache($mscache->adapter);

// $doctrine_config->setAutoGenerateProxyClasses(\Doctrine\Common\Proxy\AbstractProxyFactory::AUTOGENERATE_FILE_NOT_EXISTS);

// // obtaining the entity manager
// $connection = DriverManager::getConnection([
//     'driver' => 'pdo_pgsql',
//     'dbname' => $config['db.dbname'],
//     'user' => $config['db.user'],
//     'password' => $config['db.password'],
//     'host' => $config['db.host'],
//     'wrapperClass' => MapasCulturais\Connection::class
// ], $doctrine_config);


// // obtaining the entity manager
// // $em = new EntityManager($connection, $doctrine_config);

